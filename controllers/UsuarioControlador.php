<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/FirebaseAuth.php';
require_once __DIR__ . '/../core/MailerBrevo.php';

class UsuarioControlador extends Controlador {
    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodos(): void {
        $model = new UsuarioModelo();
        $this->success(['users' => $model->obtenerTodos()]);
    }

    public function obtenerPorId(): void {
        // La identidad sale de la sesión, nunca de parámetros del cliente.
        $userIdSesion = Auth::idUsuario();
        if ($userIdSesion <= 0) {
            $this->error('Sesión requerida', 401);
            return;
        }
        // Sin parámetro: devuelve el usuario de la sesión (acción 'me').
        $userId = (int) ($_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            $userId = $userIdSesion;
        }
        // Solo el propio usuario o un admin pueden ver el perfil de otro.
        if ($userId !== $userIdSesion && Auth::rol() !== 'admin') {
            $this->error('No autorizado para ver este usuario', 403);
            return;
        }
        $model = new UsuarioModelo();
        $user = $model->obtenerPorId($userId);
        if ($user) {
            $this->success(['user' => $user]);
        } else {
            $this->error('Usuario no encontrado');
        }
    }

    public function cerrarSesion(): void {
        Auth::cerrarSesion();
        $this->success();
    }

    public function obtenerTokenCsrf(): void {
        // Permite al cliente renovar el token cuando la sesión del servidor
        // se perdió (redeploy/reinicio del contenedor) sin recargar la página.
        $this->success(['csrf_token' => Auth::tokenCsrf()]);
    }

    private function obtenerIpCliente(): string {
        // No confiar en HTTP_X_FORWARDED_FOR salvo proxy de confianza configurado.
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function validarFuerzaContrasena(string $password): ?string {
        if (mb_strlen($password) < 8) return 'La contraseña debe tener al menos 8 caracteres';
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            return 'La contraseña debe incluir al menos una letra y un número';
        }
        return null;
    }

    public function iniciarSesion(): void {
        $input = $this->getInput();
        $userInput = trim($input['userInput'] ?? $input['username'] ?? $input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (!$userInput || !$password) {
            $this->error('Faltan credenciales');
            return;
        }

        $ip = $this->obtenerIpCliente();
        $model = new UsuarioModelo();

        // Rate limiting: bloquear por cuenta (y por IP) tras 5 intentos fallidos (15 min)
        $claveUsuario = 'usr_' . md5(strtolower($userInput));
        $claveIp = 'ip_' . md5($ip);
        if ($model->estaBloqueado($claveUsuario) || $model->estaBloqueado($claveIp)) {
            $segundos = max(
                $model->obtenerTiempoRestanteBloqueo($claveUsuario),
                $model->obtenerTiempoRestanteBloqueo($claveIp)
            );
            $min = intdiv($segundos, 60);
            $seg = $segundos % 60;
            $tiempo = $min > 0
                ? ($min . ' minuto' . ($min !== 1 ? 's' : '') . ($seg > 0 ? " y $seg segundo" . ($seg !== 1 ? 's' : '') : ''))
                : "$seg segundo" . ($seg !== 1 ? 's' : '');
            $this->error("Cuenta bloqueada por demasiados intentos fallidos. Intente nuevamente en $tiempo.", 429);
            return;
        }

        $user = $model->buscarPorUsuarioOCorreo($userInput);

        if (!$user || !$model->verificarContrasena($password, $user['contrasena'] ?? '')) {
            $model->registrarIntentoFallido($claveUsuario);
            $model->registrarIntentoFallido($claveIp);
            $this->error('Credenciales inválidas');
            return;
        }

        // Las cuentas creadas con contraseña deben verificar su correo antes de
        // iniciar sesión; las de OAuth/Firebase ya fueron verificadas por el proveedor.
        if ((int) ($user['email_verified'] ?? 0) === 0 && ($user['proveedor'] ?? 'password') === 'password') {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'email_sin_verificar',
                'message' => 'Debes verificar tu correo antes de iniciar sesión. Revisa tu bandeja (o spam) y haz clic en el enlace de verificación.',
                'email'   => $user['correo'] ?? '',
            ], 403);
            return;
        }

        // Login correcto: limpiar intentos y rehashear si el algoritmo cambió
        $model->limpiarIntentos($claveUsuario);
        $model->limpiarIntentos($claveIp);
        $nuevoHash = $model->rehashearSiNecesario($password, $user['contrasena'] ?? '');
        if ($nuevoHash) {
            $this->db->query("UPDATE usuarios SET contrasena = '{$nuevoHash}' WHERE id = " . (int) $user['id']);
        }

        // Sesión PHP real (HttpOnly + SameSite): la identidad ya no viaja en cookies legibles.
        $rol = $user['rol'] ?? (!empty($user['es_admin']) ? 'admin' : 'customer');
        Auth::establecerSesion((int) $user['id'], $rol);

        $this->success(['user' => $model->obtenerPorId((int) $user['id'])]);
    }

    /**
     * Inicio de sesión con un ID token de Firebase (Google, GitHub, correo/contraseña...).
     * El backend no depende del proveedor: valida el token y busca o crea la cuenta.
     */
    public function iniciarSesionFirebase(): void {
        $input = $this->getInput();
        $idToken = trim((string) ($input['idToken'] ?? ''));
        if ($idToken === '') {
            $this->error('Falta el token de Firebase');
            return;
        }

        $config = require __DIR__ . '/../config/config.php';
        try {
            $verificador = new FirebaseAuth((string) ($config['firebase']['project_id'] ?? ''));
            $datos = FirebaseAuth::resumir($verificador->verificar($idToken));
        } catch (RuntimeException $e) {
            error_log('[loginFirebase] ' . $e->getMessage());
            $this->error('No se pudo validar el inicio de sesión. Vuelve a intentarlo.', 401);
            return;
        }

        if ($datos === null) {
            $this->error('Tu cuenta de Firebase no tiene un correo válido', 401);
            return;
        }

        $model = new UsuarioModelo();
        $usuario = $model->buscarPorFirebaseUid($datos['uid']);

        if ($usuario === null) {
            $existente = $model->buscarPorCorreo($datos['email']);
            if ($existente !== null) {
                if (!empty($existente['firebase_uid'])) {
                    $this->error('Ese correo ya está vinculado a otra cuenta externa.');
                    return;
                }
                // Solo se vincula si Firebase confirmó el correo: sin eso, cualquiera que
                // conozca la dirección podría entrar a la cuenta existente.
                if (!$datos['email_verificado']) {
                    $this->error('Ese correo ya está registrado. Verifícalo en el mensaje que recibiste e inténtalo de nuevo.');
                    return;
                }
                $model->vincularFirebase((int) $existente['id'], $datos['uid'], $datos['proveedor'], $datos['foto_url']);
            } else {
                $nuevo = $model->crearConFirebase([
                    'nombre'    => $datos['nombre'],
                    'email'     => $datos['email'],
                    'usuario'   => $model->generarUsuarioUnico(explode('@', $datos['email'])[0]),
                    'uid'       => $datos['uid'],
                    'proveedor' => $datos['proveedor'],
                    'foto_url'  => $datos['foto_url'],
                ]);
                if ($nuevo === null) {
                    error_log('[loginFirebase] No se pudo crear la cuenta: ' . $this->db->error());
                    $this->error('No se pudo crear tu cuenta', 500);
                    return;
                }
            }
            $usuario = $model->buscarPorFirebaseUid($datos['uid']);
        }

        if ($usuario === null) {
            $this->error('No se pudo iniciar sesión', 500);
            return;
        }

        $perfil = $model->obtenerPorId((int) $usuario['id']);
        Auth::establecerSesion((int) $usuario['id'], $perfil['role']);

        $this->success(['user' => $perfil]);
    }

    public function registrar(): void {
        $data = $this->getInput();
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (!$name || !$email || !$username || !$password) {
            $this->error('Faltan campos');
            return;
        }

        $errorFuerza = $this->validarFuerzaContrasena($password);
        if ($errorFuerza) {
            $this->error($errorFuerza);
            return;
        }

        $model = new UsuarioModelo();

        // Validar que el usuario y el correo no estén ya registrados
        $campo = $model->campoDuplicado($username, $email);
        if ($campo === 'usuario') {
            $this->error('Ese nombre de usuario ya está registrado. Elige otro.');
            return;
        }
        if ($campo === 'correo') {
            $this->error('Ese correo electrónico (Gmail u otro) ya está registrado. Si es tuyo, inicia sesión.');
            return;
        }

        $id = $model->crear(compact('name', 'email', 'username', 'password'));
        if ($id) {
            $verificacionEnviada = $this->enviarCorreoVerificacion((int) $id, $model);
            $this->success(['user_id' => $id, 'verificacion_enviada' => $verificacionEnviada]);
        } else {
            $this->error('Error al registrar usuario');
        }
    }

    /**
     * Genera un token de verificación (24 h), lo guarda en el usuario y envía
     * el correo por Brevo. Devuelve true si el correo se envió.
     */
    private function enviarCorreoVerificacion(int $id, UsuarioModelo $model): bool {
        $config = require __DIR__ . '/../config/config.php';
        $usuario = $model->obtenerDatosCorreo($id);
        if (!$usuario || empty($usuario['correo'])) {
            error_log('[verificacion] No se pudo obtener el correo del usuario ' . $id);
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', time() + 86400); // 24 horas

        if (!$model->guardarTokenVerificacion($id, $token, $expira)) {
            error_log('[verificacion] No se pudo guardar el token del usuario ' . $id);
            return false;
        }

        return MailerBrevo::enviarVerificacion($usuario['correo'], $usuario['nombre'] ?? '', $token, $config);
    }

    /** Verifica el correo de un usuario a partir del token del enlace. */
    public function verificarCorreo(): void {
        $input = $this->getInput();
        $token = trim((string) ($input['token'] ?? $_GET['token'] ?? ''));
        if ($token === '') {
            $this->error('Falta el token de verificación');
            return;
        }

        $model = new UsuarioModelo();
        $id = $model->verificarTokenEmail($token);
        if ($id === null) {
            $this->error('El enlace de verificación no es válido o ya expiró.', 400);
            return;
        }

        $this->success([
            'user' => $model->obtenerPorId($id),
        ]);
    }

    /** Regenera y reenvía el correo de verificación a una cuenta sin verificar. */
    public function reenviarVerificacion(): void {
        $input = $this->getInput();
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Correo inválido');
            return;
        }

        $model = new UsuarioModelo();
        $usuario = $model->buscarPorCorreo($email);
        if (!$usuario || (int) ($usuario['email_verified'] ?? 0) === 1) {
            // No se revela si el correo existe: la cuenta ya está verificada o no existe.
            $this->success(['reenvio_realizado' => false]);
            return;
        }

        $enviado = $this->enviarCorreoVerificacion((int) $usuario['id'], $model);
        $this->success(['reenvio_realizado' => $enviado]);
    }

    public function alternarAdmin(): void {
        Auth::requerirAdmin();
        $data = $this->getInput();
        $id = (int) ($data['id'] ?? 0);
        if (!$id) {
            $this->error('ID requerido');
            return;
        }
        // Un admin no puede cambiarse su propio rol.
        if ($id === Auth::idUsuario()) {
            $this->error('No puedes cambiar tu propio rol', 403);
            return;
        }
        $model = new UsuarioModelo();
        if ($model->alternarAdmin($id)) {
            $this->success();
        } else {
            $this->error('Usuario no encontrado');
        }
    }

    public function alternarVendedor(): void {
        Auth::requerirAdmin();
        $data = $this->getInput();
        $id = (int) ($data['id'] ?? 0);
        if (!$id) {
            $this->error('ID requerido');
            return;
        }
        if ($id === Auth::idUsuario()) {
            $this->error('No puedes cambiar tu propio rol', 403);
            return;
        }
        $model = new UsuarioModelo();
        if ($model->alternarVendedor($id)) {
            $this->success();
        } else {
            $this->error('Usuario no encontrado');
        }
    }

    public function actualizarPerfil(): void {
        $data = $this->getInput();
        // El id SIEMPRE sale de la sesión.
        $id = Auth::idUsuario();
        if ($id <= 0) {
            $this->error('Sesión requerida', 401);
            return;
        }
        $campos = [];
        if (isset($data['name'])) $campos['name'] = trim($data['name']);
        if (isset($data['email'])) $campos['email'] = trim($data['email']);
        if (isset($data['username'])) $campos['username'] = trim($data['username']);
        if (!empty($data['password'])) {
            $errorFuerza = $this->validarFuerzaContrasena($data['password']);
            if ($errorFuerza) {
                $this->error($errorFuerza);
                return;
            }
            $campos['password'] = $data['password'];
        }

        if (empty($campos)) {
            $this->error('Nada que actualizar');
            return;
        }

        $model = new UsuarioModelo();
        if ($model->actualizarPerfil($id, $campos)) {
            $this->success(['user' => $model->obtenerPorId($id)]);
        } else {
            $this->error('Error al actualizar el perfil');
        }
    }

    public function eliminar(): void {
        Auth::requerirAdmin();
        $data = $this->getInput();
        $id = (int) ($data['id'] ?? 0);
        if (!$id) {
            $this->error('ID requerido');
            return;
        }
        // Un admin no puede eliminarse a sí mismo.
        if ($id === Auth::idUsuario()) {
            $this->error('No puedes eliminar tu propia cuenta', 403);
            return;
        }
        $model = new UsuarioModelo();
        if ($model->eliminar($id)) {
            $this->success();
        } else {
            $this->error('Error al eliminar usuario');
        }
    }
}
