<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
$config = require __DIR__ . '/config/config.php';
$appParts = parse_url((string) ($config['app']['url'] ?? '')) ?: [];
$origenPermitido = '';
if (!empty($appParts['scheme']) && !empty($appParts['host'])) {
    $origenPermitido = $appParts['scheme'] . '://' . $appParts['host'];
    if (!empty($appParts['port'])) $origenPermitido .= ':' . (int) $appParts['port'];
}
$origin = rtrim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
if ($origin !== '' && $origin !== rtrim($origenPermitido, '/')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Origen no autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

Auth::iniciarSesion();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$tokenCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$entrada = json_decode(file_get_contents('php://input'), true);
if (is_array($entrada)) {
    $GLOBALS['api_json_input'] = $entrada;
    $tokenCsrf = $tokenCsrf !== '' ? $tokenCsrf : (string) ($entrada['csrf_token'] ?? '');
}
if (Auth::tieneSesion() && !Auth::validarCsrf($tokenCsrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o ausente'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controlador.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/controllers/ChatController.php';

$controller = new ChatController();
$controller->send();
