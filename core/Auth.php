<?php

/**
 * Auth.php — Middleware de sesión, roles y CSRF para SERVIFARMACIA RK.
 * La identidad SIEMPRE sale de $_SESSION (nunca de parámetros del cliente).
 */
class Auth {

    /** Inicia la sesión con parámetros seguros. Idempotente. */
    public static function iniciarSesion(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $seguro = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $seguro,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('servifarmacia_sesion');
        session_start();
        // Prevenir fijación de sesión
        if (!isset($_SESSION['_iniciada'])) {
            session_regenerate_id(true);
            $_SESSION['_iniciada'] = true;
        }
    }

    /** Devuelve el id de usuario de la sesión (0 si no hay sesión). */
    public static function idUsuario(): int {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /** ¿Hay sesión iniciada? */
    public static function tieneSesion(): bool {
        return self::idUsuario() > 0;
    }

    /** Devuelve el rol del usuario en sesión ('', 'customer', 'seller' o 'admin'). */
    public static function rol(): string {
        return (string) ($_SESSION['rol'] ?? '');
    }

    /** Establece la sesión tras un login correcto. */
    public static function establecerSesion(int $userId, string $rol): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['rol'] = $rol;
    }

    /** Cierra la sesión. */
    public static function cerrarSesion(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Exige sesión iniciada; si no, responde JSON 401 y termina. */
    public static function requerirSesion(): int {
        $id = self::idUsuario();
        if ($id <= 0) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Sesión requerida'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $id;
    }

    /** Exige rol admin; si no, responde JSON 403 y termina. */
    public static function requerirAdmin(): void {
        self::requerirSesion();
        if (self::rol() !== 'admin') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Requiere permisos de administrador'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** Devuelve (o crea) el token CSRF de la sesión. */
    public static function tokenCsrf(): string {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** Valida el token CSRF recibido. Para acciones de escritura. */
    public static function validarCsrf(?string $token): bool {
        return !empty($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
    }

    /** Devuelve el session_id anónimo de carrito (creado y servido por PHP). */
    public static function sessionIdCarrito(): string {
        if (empty($_SESSION['_carrito_sesion'])) {
            $_SESSION['_carrito_sesion'] = 'srv_' . bin2hex(random_bytes(16));
        }
        return $_SESSION['_carrito_sesion'];
    }
}
