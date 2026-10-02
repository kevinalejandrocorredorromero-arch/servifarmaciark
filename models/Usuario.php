<?php

class UsuarioModelo {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerTodos(): array {
        $res = $this->db->query("SELECT * FROM usuarios");
        if (!$res) return [];

        $usuarios = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $usuarios[] = $this->mapearFila($row);
        }
        return $usuarios;
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, nombre, correo, usuario, es_admin, rol, proveedor, foto_url, email_verified FROM usuarios WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        return $row ? $this->mapearFila($row) : null;
    }

    /** Busca por el UID de Firebase. Es la forma canónica de identificar usuarios OAuth. */
    public function buscarPorFirebaseUid(string $uid): ?array {
        $stmt = $this->db->prepare("SELECT id, nombre, correo, usuario, es_admin, rol, firebase_uid, proveedor, foto_url FROM usuarios WHERE firebase_uid = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $uid);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }

    public function buscarPorCorreo(string $correo): ?array {
        $stmt = $this->db->prepare("SELECT id, nombre, correo, usuario, es_admin, rol, firebase_uid, proveedor, foto_url, email_verified FROM usuarios WHERE correo = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $correo);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }

    public function vincularFirebase(int $id, string $uid, string $proveedor, ?string $fotoUrl): bool {
        $stmt = $this->db->prepare("UPDATE usuarios SET firebase_uid = ?, proveedor = ?, foto_url = ?, email_verified = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'sssi', $uid, $proveedor, $fotoUrl, $id);
        return mysqli_stmt_execute($stmt);
    }

    /** Crea un usuario OAuth: sin contraseña (NULL, no cadena vacía hasheada). */
    public function crearConFirebase(array $data): ?int {
        $nombre = (string) ($data['nombre'] ?? '');
        $correo = (string) ($data['email'] ?? '');
        $usuario = (string) ($data['usuario'] ?? '');
        $uid = (string) ($data['uid'] ?? '');
        $proveedor = (string) ($data['proveedor'] ?? 'password');
        $fotoUrl = $data['foto_url'] ?? null;

        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nombre, correo, usuario, contrasena, es_admin, rol, creado_en, firebase_uid, proveedor, foto_url, email_verified)
             VALUES (?, ?, ?, NULL, 0, 'customer', NOW(), ?, ?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt, 'ssssss', $nombre, $correo, $usuario, $uid, $proveedor, $fotoUrl);
        return mysqli_stmt_execute($stmt) ? $this->db->insertId() : null;
    }

    /** Propone un nombre de usuario libre a partir del correo (prefijo + sufijo numérico). */
    public function generarUsuarioUnico(string $base): string {
        $base = strtolower(preg_replace('/[^A-Za-z0-9_.]/', '', $base) ?? '');
        $base = substr(trim($base, '._'), 0, 40);
        if (strlen($base) < 3) {
            $base = 'usuario';
        }
        for ($sufijo = 1; $sufijo <= 999; $sufijo++) {
            $candidato = $sufijo === 1 ? $base : $base . $sufijo;
            if (!$this->usuarioExiste($candidato)) {
                return $candidato;
            }
        }
        return $base . bin2hex(random_bytes(3));
    }

    private function usuarioExiste(string $usuario): bool {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        return mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    }

    public function buscarPorUsuarioOCorreo(string $entrada): ?array {
        $entrada = $this->db->escape($entrada);
        $stmt = $this->db->prepare("SELECT id, nombre, correo, usuario, contrasena, es_admin, rol, proveedor, email_verified FROM usuarios WHERE usuario = ? OR correo = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $entrada, $entrada);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        return $row ? $row : null;
    }

    public function crear(array $data): ?int {
        $nombre = $this->db->escape($data['name'] ?? $data['nombre'] ?? '');
        $correo = $this->db->escape($data['email'] ?? $data['correo'] ?? '');
        $usuario = $this->db->escape($data['username'] ?? $data['usuario'] ?? '');
        $contrasena = $data['password'] ?? $data['contrasena'] ?? '';
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, correo, usuario, contrasena, es_admin, rol, creado_en)
                VALUES ('{$nombre}', '{$correo}', '{$usuario}', '{$hash}', 0, 'customer', NOW())";

        if ($this->db->query($sql)) {
            return $this->db->insertId();
        }
        return null;
    }

    /**
     * Verifica si ya existe un usuario con el mismo nombre de usuario o correo.
     * Devuelve 'usuario', 'correo' o null según el campo duplicado.
     */
    public function campoDuplicado(string $usuario, string $correo): ?string {
        $usuario = $this->db->escape(trim($usuario));
        $correo = $this->db->escape(trim($correo));
        if ($usuario !== '') {
            $res = $this->db->query("SELECT id FROM usuarios WHERE usuario = '{$usuario}' LIMIT 1");
            if ($res && mysqli_num_rows($res) > 0) return 'usuario';
        }
        if ($correo !== '') {
            $res = $this->db->query("SELECT id FROM usuarios WHERE correo = '{$correo}' LIMIT 1");
            if ($res && mysqli_num_rows($res) > 0) return 'correo';
        }
        return null;
    }

    /** Guarda (o reemplaza) el token de verificación de correo de un usuario. */
    public function guardarTokenVerificacion(int $id, string $token, string $expira): bool {
        $token = $this->db->escape($token);
        $expira = $this->db->escape($expira);
        return $this->db->query(
            "UPDATE usuarios SET email_token = '{$token}', email_token_expira = '{$expira}' WHERE id = " . (int) $id
        ) !== false;
    }

    /**
     * Verifica un token de correo. Si existe un token vigente (sin vencer) para
     * algún usuario, marca el correo como verificado, limpia el token y devuelve
     * el id del usuario; en cualquier otro caso devuelve null.
     */
    public function verificarTokenEmail(string $token): ?int {
        $token = $this->db->escape($token);
        $res = $this->db->query(
            "SELECT id FROM usuarios
             WHERE email_token = '{$token}' AND email_token_expira IS NOT NULL AND email_token_expira > NOW()
             LIMIT 1"
        );
        if (!$res || !($row = mysqli_fetch_assoc($res))) {
            return null;
        }
        $id = (int) $row['id'];
        $this->db->query(
            "UPDATE usuarios SET email_verified = 1, email_token = NULL, email_token_expira = NULL WHERE id = " . $id
        );
        return $id;
    }

    /** Devuelve correo y nombre del usuario, para poder enviarle el correo de verificación. */
    public function obtenerDatosCorreo(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, nombre, correo FROM usuarios WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        return $row ? $row : null;
    }

    public function alternarAdmin(int $id): bool {
        $res = $this->db->query("SELECT es_admin, rol FROM usuarios WHERE id = " . (int) $id . " LIMIT 1");
        $row = mysqli_fetch_assoc($res);
        if (!$row) return false;

        $nuevo = $row['es_admin'] ? 0 : 1;
        $rol = $nuevo ? 'admin' : (($row['rol'] ?? '') === 'seller' ? 'seller' : 'customer');

        return $this->db->query("UPDATE usuarios SET es_admin = {$nuevo}, rol = '{$rol}' WHERE id = " . (int) $id) !== false;
    }

    public function alternarVendedor(int $id): bool {
        $res = $this->db->query("SELECT es_admin, rol FROM usuarios WHERE id = " . (int) $id . " LIMIT 1");
        $row = mysqli_fetch_assoc($res);
        if (!$row) return false;

        $rolActual = $this->normalizarRol($row);
        $nuevoRol = $rolActual === 'seller' ? 'customer' : 'seller';

        return $this->db->query("UPDATE usuarios SET rol = '{$nuevoRol}', es_admin = 0 WHERE id = " . (int) $id) !== false;
    }

    public function eliminar(int $id): bool {
        return $this->db->query("DELETE FROM usuarios WHERE id = " . (int) $id) !== false;
    }

    public function actualizarPerfil(int $id, array $data): bool {
        $campos = [];
        if (isset($data['name'])) {
            $campos[] = "nombre = '" . $this->db->escape($data['name']) . "'";
        }
        if (isset($data['email'])) {
            $campos[] = "correo = '" . $this->db->escape($data['email']) . "'";
        }
        if (isset($data['username'])) {
            $campos[] = "usuario = '" . $this->db->escape($data['username']) . "'";
        }
        if (!empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $campos[] = "contrasena = '{$hash}'";
        }
        if (empty($campos)) return false;
        return $this->db->query("UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = " . (int) $id) !== false;
    }

    public function verificarContrasena(string $entrada, string $hashAlmacenado): bool {
        if ($hashAlmacenado && password_verify($entrada, $hashAlmacenado)) return true;
        return false;
    }

    /**
     * Rehashea la contraseña si el algoritmo/costo cambió (bcrypt evolution).
     * Devuelve el nuevo hash si hubo rehash, o null si no fue necesario.
     */
    public function rehashearSiNecesario(string $entrada, string $hashAlmacenado): ?string {
        if ($hashAlmacenado && password_needs_rehash($hashAlmacenado, PASSWORD_DEFAULT)) {
            return password_hash($entrada, PASSWORD_DEFAULT);
        }
        return null;
    }

    /**
     * Rate limiting de login por clave (IP o usuario).
     * Crea la tabla si no existe para funcionar en cualquier instalación.
     */
    public function asegurarTablaIntentos(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS intentos_login (
                clave VARCHAR(64) NOT NULL,
                intentos INT NOT NULL DEFAULT 0,
                ultimo_intento DATETIME NOT NULL,
                bloqueado_hasta DATETIME NULL,
                PRIMARY KEY (clave)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function obtenerIntentos(string $clave): ?array {
        $this->asegurarTablaIntentos();
        $clave = $this->db->escape($clave);
        $res = $this->db->query("SELECT * FROM intentos_login WHERE clave = '{$clave}'");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function registrarIntentoFallido(string $clave): void {
        $this->asegurarTablaIntentos();
        $clave = $this->db->escape($clave);
        $this->db->query(
            "INSERT INTO intentos_login (clave, intentos, ultimo_intento, bloqueado_hasta)
             VALUES ('{$clave}', 1, NOW(), NULL)
             ON DUPLICATE KEY UPDATE intentos = intentos + 1, ultimo_intento = NOW(),
               bloqueado_hasta = CASE WHEN intentos + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE) ELSE bloqueado_hasta END"
        );
    }

    public function limpiarIntentos(string $clave): void {
        $this->asegurarTablaIntentos();
        $clave = $this->db->escape($clave);
        $this->db->query("DELETE FROM intentos_login WHERE clave = '{$clave}'");
    }

    public function estaBloqueado(string $clave): bool {
        $clave = $this->db->escape($clave);
        // Usar NOW() de MySQL para evitar desfase de fechas entre PHP y MySQL
        $res = $this->db->query(
            "SELECT clave FROM intentos_login
             WHERE clave = '{$clave}' AND bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()"
        );
        return $res && mysqli_num_rows($res) > 0;
    }

    /**
     * Devuelve los segundos restantes de un bloqueo activo (0 si no hay bloqueo).
     * Usa TIMESTAMPDIFF de MySQL para mantener consistencia con NOW().
     */
    public function obtenerTiempoRestanteBloqueo(string $clave): int {
        $clave = $this->db->escape($clave);
        $res = $this->db->query(
            "SELECT TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) AS restante
             FROM intentos_login
             WHERE clave = '{$clave}' AND bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()"
        );
        if ($res && $row = mysqli_fetch_assoc($res)) {
            return max(0, (int) $row['restante']);
        }
        return 0;
    }

    private function normalizarRol(array $row): string {
        $rol = strtolower(trim((string) ($row['rol'] ?? '')));
        if (in_array($rol, ['admin', 'seller', 'customer'])) return $rol;
        return !empty($row['es_admin']) ? 'admin' : 'customer';
    }

    private function mapearFila(array $row): array {
        $rol = $this->normalizarRol($row);
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $row['nombre'] ?? $row['usuario'] ?? '',
            'email' => $row['correo'] ?? '',
            'username' => $row['usuario'] ?? '',
            'role' => $rol,
            'isAdmin' => $rol === 'admin',
            'isSeller' => $rol === 'seller',
            'provider' => $row['proveedor'] ?? 'password',
            'photoUrl' => $row['foto_url'] ?? null,
            'emailVerified' => (bool) ($row['email_verified'] ?? false),
            'created_at' => $row['creado_en'] ?? null,
        ];
    }
}
