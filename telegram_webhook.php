<?php
/**
 * telegram_webhook.php
 * Webhook de Telegram: procesa los clics en los botones de los mensajes de
 * pedido ("Confirmar entrega" / "Cancelar pedido") y actualiza el estado del
 * pedido en la base de datos, que es la que lee la página.
 *
 * Registro del webhook (una sola vez):
 *   curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
 *     -d url="https://servifarmaciark.onrender.com/telegram_webhook.php" \
 *     -d secret_token="<obtenerSecretWebhookTelegram(TOKEN)>"
 */

require_once __DIR__ . '/notificar_telegram_helper.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'solo POST']);
    exit;
}

$config = require __DIR__ . '/config/config.php';
$tg = $config['telegram'] ?? [];
$token = (string) ($tg['bot_token'] ?? '');
if ($token === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sin token']);
    exit;
}

// Telegram envía el secret configurado en setWebhook en esta cabecera.
$secretoEsperado = obtenerSecretWebhookTelegram($token);
$secretoRecibido = (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
if ($secretoRecibido === '' || !hash_equals($secretoEsperado, $secretoRecibido)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'secret inválido']);
    exit;
}

$update = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($update)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

$callback = $update['callback_query'] ?? null;
if (!is_array($callback)) {
    // Otros updates (mensajes, etc.) se aceptan y se ignoran.
    echo json_encode(['ok' => true]);
    exit;
}

$callbackId = (string) ($callback['id'] ?? '');
$data = (string) ($callback['data'] ?? '');
$chatId = $callback['message']['chat']['id'] ?? null;
$messageId = $callback['message']['message_id'] ?? null;
$textoOriginal = (string) ($callback['message']['text'] ?? '');
$quienPulso = trim((string) ($callback['from']['first_name'] ?? '')) ?: 'repartidor';

$responder = function (string $texto, bool $alerta = false) use ($token, $callbackId): void {
    if ($callbackId !== '') {
        llamarApiTelegram($token, 'answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $texto,
            'show_alert' => $alerta ? 'true' : 'false',
        ]);
    }
};

if (!preg_match('/^(entregar|cancelar)_(\d+)$/', $data, $coincidencias)) {
    $responder('Acción no reconocida', true);
    echo json_encode(['ok' => true]);
    exit;
}
$accion = $coincidencias[1];
$pedidoId = (int) $coincidencias[2];

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Pedido.php';
$modelo = new PedidoModelo();

if ($accion === 'entregar') {
    $exito = $modelo->confirmarEntrega($pedidoId);
    $lineaEstado = "✅ ENTREGA CONFIRMADA por {$quienPulso} el " . date('Y-m-d H:i:s');
    $aviso = 'Pedido marcado como entregado';
} else {
    $exito = $modelo->cancelar($pedidoId, 0, true);
    $lineaEstado = "❌ PEDIDO CANCELADO por {$quienPulso} el " . date('Y-m-d H:i:s');
    $aviso = 'Pedido cancelado';
}

if (!$exito) {
    $responder('Este pedido ya fue procesado o no existe', true);
    echo json_encode(['ok' => true]);
    exit;
}

// Quita los botones y deja constancia del cambio en el propio mensaje.
if ($chatId !== null && $messageId !== null) {
    llamarApiTelegram($token, 'editMessageText', [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $textoOriginal . "\n─────────────────\n" . $lineaEstado,
        'reply_markup' => json_encode(['inline_keyboard' => []]),
    ]);
}
$responder($aviso);
echo json_encode(['ok' => true]);
