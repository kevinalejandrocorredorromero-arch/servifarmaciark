<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0777, true);
}
ini_set('error_log', __DIR__ . '/logs/api_errors.log');

ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controlador.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Pedido.php';
require_once __DIR__ . '/models/Carrito.php';
require_once __DIR__ . '/controllers/ProductoControlador.php';
require_once __DIR__ . '/controllers/UsuarioControlador.php';
require_once __DIR__ . '/controllers/PedidoControlador.php';
require_once __DIR__ . '/controllers/CarritoControlador.php';
require_once __DIR__ . '/controllers/PagoConfiguracionControlador.php';

// Una vez al día, al usar la app, se envía sola la alerta de inventario
// (stock bajo y por vencer) por Telegram. No bloquea: si ya corrió hoy, sale.
require_once __DIR__ . '/notificar_telegram_helper.php';
ejecutarAlertasInventarioDiarias();

// Sesión PHP real: la identidad sale de $_SESSION, nunca del cliente.
Auth::iniciarSesion();

$accion = $_REQUEST['accion'] ?? ($_REQUEST['action'] ?? '');
$respuesta = ['success' => false];

// Permisos por acción: 'publica' | 'sesion' | 'admin'
$permisosAccion = [
    // Públicas (catálogo, carrito propio por sesión, login/registro)
    'getProducts'            => 'publica',
    'obtenerProductos'       => 'publica',
    'getProduct'             => 'publica',
    'verProducto'            => 'publica',
    'getCategories'          => 'publica',
    'obtenerCategorias'      => 'publica',
    'getPaymentConfig'       => 'publica',
    'obtenerConfigPago'      => 'publica',
    'loginUser'              => 'publica',
    'iniciarSesion'          => 'publica',
    'loginFirebase'          => 'publica',
    'iniciarSesionFirebase'  => 'publica',
    'registerUser'           => 'publica',
    'registrarUsuario'       => 'publica',
    'verificarCorreo'        => 'publica',
    'reenviarVerificacion'   => 'publica',
    'verifyEmail'            => 'publica',
    'resendVerification'     => 'publica',
    'logout'                 => 'publica',
    'sessionInfo'            => 'publica',
    'csrfToken'              => 'publica',
    'getCart'                => 'publica',
    'obtenerCarrito'         => 'publica',
    'addToCart'              => 'publica',
    'agregarCarrito'         => 'publica',
    'checkout'               => 'publica',
    'verificar'              => 'publica',
    // Requieren sesión (usuario logueado)
    'me'                     => 'sesion',
    'getUser'                => 'sesion',
    'verUsuario'             => 'sesion',
    'getOrders'              => 'sesion',
    'obtenerPedidos'         => 'sesion',
    'cancelOrder'            => 'sesion',
    'cancelarPedido'         => 'sesion',
    'updateCartItem'         => 'sesion',
    'actualizarItemCarrito'  => 'sesion',
    'removeFromCart'         => 'sesion',
    'eliminarCarrito'        => 'sesion',
    'clearCart'              => 'sesion',
    'vaciarCarrito'          => 'sesion',
    'updateProfile'          => 'sesion',
    'actualizarPerfil'       => 'sesion',
    // Requieren rol admin
    'getUsers'               => 'admin',
    'obtenerUsuarios'        => 'admin',
    'toggleUserAdmin'        => 'admin',
    'alternarAdmin'          => 'admin',
    'toggleUserSeller'       => 'admin',
    'alternarVendedor'       => 'admin',
    'deleteUser'             => 'admin',
    'eliminarUsuario'        => 'admin',
    'addProduct'             => 'admin',
    'crearProducto'          => 'admin',
    'updateProduct'          => 'admin',
    'actualizarProducto'     => 'admin',
    'deleteProduct'          => 'admin',
    'eliminarProducto'       => 'admin',
    'updateProductStock'     => 'admin',
    'actualizarStockProducto' => 'admin',
    'getInventoryAlerts'     => 'admin',
    'obtenerAlertasInventario' => 'admin',
    'confirmOrderDelivery'   => 'admin',
    'confirmarEntregaPedido' => 'admin',
    'savePaymentConfig'      => 'admin',
    'guardarConfigPago'      => 'admin',
    'uploadPaymentQr'        => 'admin',
];

// Claves en español (canónicas). Se incluyen alias en inglés para mantener
// compatibilidad con el frontend existente.
$mapaControladores = [
    // Productos
    'obtenerProductos'     => ['ProductoControlador', 'obtenerTodos'],
    'verProducto'          => ['ProductoControlador', 'obtenerPorId'],
    'crearProducto'        => ['ProductoControlador', 'crear'],
    'actualizarProducto'   => ['ProductoControlador', 'actualizar'],
    'eliminarProducto'     => ['ProductoControlador', 'eliminar'],
    'actualizarStockProducto' => ['ProductoControlador', 'actualizarStock'],
    'obtenerAlertasInventario' => ['ProductoControlador', 'obtenerAlertas'],
    // Usuarios
    'obtenerUsuarios'      => ['UsuarioControlador', 'obtenerTodos'],
    'verUsuario'           => ['UsuarioControlador', 'obtenerPorId'],
    'iniciarSesion'        => ['UsuarioControlador', 'iniciarSesion'],
    'iniciarSesionFirebase' => ['UsuarioControlador', 'iniciarSesionFirebase'],
    'registrarUsuario'     => ['UsuarioControlador', 'registrar'],
    'verificarCorreo'      => ['UsuarioControlador', 'verificarCorreo'],
    'reenviarVerificacion' => ['UsuarioControlador', 'reenviarVerificacion'],
    'alternarAdmin'        => ['UsuarioControlador', 'alternarAdmin'],
    'alternarVendedor'     => ['UsuarioControlador', 'alternarVendedor'],
    'eliminarUsuario'      => ['UsuarioControlador', 'eliminar'],
    'actualizarPerfil'     => ['UsuarioControlador', 'actualizarPerfil'],
    // Pedidos
    'obtenerPedidos'       => ['PedidoControlador', 'obtenerTodos'],
    'verificar'            => ['PedidoControlador', 'crear'],
    'confirmarEntregaPedido' => ['PedidoControlador', 'confirmarEntrega'],
    'cancelOrder'            => ['PedidoControlador', 'cancelar'],
    'cancelarPedido'         => ['PedidoControlador', 'cancelar'],
    // Carrito
    'obtenerCarrito'       => ['CarritoControlador', 'obtener'],
    'agregarCarrito'       => ['CarritoControlador', 'agregar'],
    'actualizarItemCarrito' => ['CarritoControlador', 'actualizarItem'],
    'eliminarCarrito'      => ['CarritoControlador', 'eliminar'],
    'vaciarCarrito'        => ['CarritoControlador', 'vaciar'],
    // Pagos
    'obtenerConfigPago'    => ['PagoConfiguracionControlador', 'obtener'],
    'guardarConfigPago'    => ['PagoConfiguracionControlador', 'guardar'],
    // --- Alias en inglés (compatibilidad) ---
    'getProducts'         => ['ProductoControlador', 'obtenerTodos'],
    'getProduct'          => ['ProductoControlador', 'obtenerPorId'],
    'addProduct'          => ['ProductoControlador', 'crear'],
    'updateProduct'       => ['ProductoControlador', 'actualizar'],
    'deleteProduct'       => ['ProductoControlador', 'eliminar'],
    'getInventoryAlerts'  => ['ProductoControlador', 'obtenerAlertas'],
    'updateProductStock'  => ['ProductoControlador', 'actualizarStock'],
    'getUsers'            => ['UsuarioControlador', 'obtenerTodos'],
    'getUser'             => ['UsuarioControlador', 'obtenerPorId'],
    'loginUser'           => ['UsuarioControlador', 'iniciarSesion'],
    'loginFirebase'       => ['UsuarioControlador', 'iniciarSesionFirebase'],
    'registerUser'        => ['UsuarioControlador', 'registrar'],
    'verifyEmail'          => ['UsuarioControlador', 'verificarCorreo'],
    'resendVerification'   => ['UsuarioControlador', 'reenviarVerificacion'],
    'toggleUserAdmin'     => ['UsuarioControlador', 'alternarAdmin'],
    'toggleUserSeller'    => ['UsuarioControlador', 'alternarVendedor'],
    'deleteUser'          => ['UsuarioControlador', 'eliminar'],
    'updateProfile'      => ['UsuarioControlador', 'actualizarPerfil'],
    'getOrders'           => ['PedidoControlador', 'obtenerTodos'],
    'checkout'            => ['PedidoControlador', 'crear'],
    'confirmOrderDelivery'=> ['PedidoControlador', 'confirmarEntrega'],
    'getCart'             => ['CarritoControlador', 'obtener'],
    'addToCart'           => ['CarritoControlador', 'agregar'],
    'updateCartItem'      => ['CarritoControlador', 'actualizarItem'],
    'removeFromCart'      => ['CarritoControlador', 'eliminar'],
    'clearCart'           => ['CarritoControlador', 'vaciar'],
    'getPaymentConfig'    => ['PagoConfiguracionControlador', 'obtener'],
    'savePaymentConfig'   => ['PagoConfiguracionControlador', 'guardar'],
    'uploadPaymentQr'      => ['PagoConfiguracionControlador', 'subirQr'],
    // Sesión y autenticación
    'me'                  => ['UsuarioControlador', 'obtenerPorId'],
    'logout'              => ['UsuarioControlador', 'cerrarSesion'],
    'sessionInfo'         => ['CarritoControlador', 'infoSesion'],
    'csrfToken'           => ['UsuarioControlador', 'obtenerTokenCsrf'],
];

// --- Autorización ---
$metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$esEscritura = in_array($metodoHttp, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
// Cerrar sesión es una operación segura aunque se repita y debe poder ejecutarse
// incluso si el navegador conserva un token viejo de la sesión anterior.
$requiereCsrf = $esEscritura && $accion !== 'logout';

// Protección CSRF: toda acción de escritura exige token válido (emitido al cargar la página).
if ($requiereCsrf) {
    // La cabecera es el canal preferido. El JSON es un fallback para servidores
    // que no exponen cabeceras X-* a PHP (por ejemplo, algunas configuraciones Apache).
    $tokenCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($tokenCsrf === '') {
        $entradaJson = json_decode(file_get_contents('php://input'), true);
        if (is_array($entradaJson)) {
            // Reutiliza el cuerpo ya leído; los controladores no deben leerlo otra vez.
            $GLOBALS['api_json_input'] = $entradaJson;
            $tokenCsrf = (string) ($entradaJson['csrf_token'] ?? '');
        }
    }
    if ($tokenCsrf === '') {
        $tokenCsrf = (string) ($_REQUEST['csrf_token'] ?? '');
    }
    if (!Auth::validarCsrf($tokenCsrf)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o ausente'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Autorización por rol según la acción solicitada.
if (isset($mapaControladores[$accion])) {
    $permiso = $permisosAccion[$accion] ?? 'sesion';
    if ($permiso === 'admin') {
        Auth::requerirAdmin();
    } elseif ($permiso === 'sesion') {
        Auth::requerirSesion();
    }
}

if (isset($mapaControladores[$accion])) {
    [$nombreClase, $metodo] = $mapaControladores[$accion];
    $controlador = new $nombreClase();
    $controlador->$metodo();
} else {
    $salidaExtra = trim(ob_get_clean());
    $respuesta['error'] = 'Acción no especificada o no soportada';
    $respuesta['accion_recibida'] = $accion;
    // La salida HTML residual se registra en el log en vez de devolverse al cliente.
    if ($salidaExtra !== '') {
        error_log('[api.php] Salida inesperada para acción "' . $accion . '": ' . substr($salidaExtra, 0, 500));
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
}
