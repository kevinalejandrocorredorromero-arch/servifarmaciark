<?php
/**
 * notificar_telegram.php
 * Envía por Telegram alertas de:
 *   1) Productos con stock bajo (stock_total_unidades <= umbral)
 *   2) Productos por vencer (fecha_vencimiento entre hoy y hoy + N días)
 *
 * Uso:
 *   - Manual: abrir http://localhost/servi/servifarmacia/notificar_telegram.php
 *   - Forzar envío aunque no haya alertas: ?forzar=1
 *   - Programado: llamar a esta URL vía tarea/cron cada X tiempo.
 *
 * Configuración en config/config.php -> 'telegram' (bot_token, chat_id,
 * umbral_bajo_stock) y 'whatsapp' (dias_anticipacion_vencimiento).
 */

$config = require_once __DIR__ . '/config/config.php';
if (!is_array($config)) {
    $config = [];
}
require_once __DIR__ . '/core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

$cfg = $config['telegram'] ?? null;
if (!$cfg || empty($cfg['bot_token']) || empty($cfg['chat_id'])) {
    echo "ERROR: falta configuración de Telegram en config.php\n";
    exit(1);
}

$botToken = $cfg['bot_token'];
$chatId   = $cfg['chat_id'];
$umbral   = (int) ($cfg['umbral_bajo_stock'] ?? 10);
$dias     = (int) ($config['whatsapp']['dias_anticipacion_vencimiento'] ?? ($cfg['dias_anticipacion_vencimiento'] ?? 30));

$forzar   = isset($_GET['forzar']) && $_GET['forzar'] == '1';

$db = Database::getInstance();

// ---------- 1) STOCK BAJO ----------
$sqlStock = "SELECT id, nombre, stock, stock_total_unidades, umbral_stock, categoria
             FROM productos
             WHERE stock_total_unidades <= {$umbral}
             ORDER BY stock_total_unidades ASC
             LIMIT 50";
$res = $db->query($sqlStock);
$stockBajo = [];
while ($row = mysqli_fetch_assoc($res)) {
    $stockBajo[] = $row;
}

// ---------- 2) VENCIMIENTO CERCANO ----------
// Incluye los que vencen en los próximos $dias Y los ya vencidos (fecha < hoy)
$sqlVenc = "SELECT id, nombre, fecha_vencimiento, stock_total_unidades,
                    DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM productos
             WHERE fecha_vencimiento IS NOT NULL
               AND fecha_vencimiento <> '0000-00-00'
               AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL {$dias} DAY)
             ORDER BY fecha_vencimiento ASC
             LIMIT 50";
$res2 = $db->query($sqlVenc);
$porVencer = [];
while ($row = mysqli_fetch_assoc($res2)) {
    $porVencer[] = $row;
}

// En ejecución automática solo se envían alertas reales. El mensaje de estado
// completo queda disponible únicamente con ?forzar=1 para pruebas manuales.
if (empty($stockBajo) && empty($porVencer) && !$forzar) {
    echo "Sin alertas pendientes. No se envió ningún mensaje.\n";
    exit(0);
}

$lineas = [];
$lineas[] = "📋 SERVIFARMACIA RK — RESUMEN DE ALERTAS";
$lineas[] = "Generado: " . date('Y-m-d H:i:s');
$lineas[] = str_repeat('=', 42);

// Sección stock bajo
$lineas[] = "⚠️ STOCK BAJO (<= {$umbral} unidades)";
if (empty($stockBajo)) {
    $lineas[] = "  (sin productos en esta categoría)";
} else {
    foreach ($stockBajo as $p) {
        $nombre = $p['nombre'] ?? '(sin nombre)';
        $stockU = (int) $p['stock_total_unidades'];
        $lineas[] = "• {$nombre}\n   Stock: {$stockU} uds (cajas: " . (int)$p['stock'] . ")";
    }
}
$lineas[] = str_repeat('-', 42);

// Sección vencimiento
$lineas[] = "⏰ POR VENCER (en los próximos {$dias} días)";
if (empty($porVencer)) {
    $lineas[] = "  (sin productos en esta categoría)";
} else {
    $hoy = new DateTime();
    foreach ($porVencer as $p) {
        $nombre = $p['nombre'] ?? '(sin nombre)';
        $fv = $p['fecha_vencimiento'];
        $diasRest = (int) ($p['dias_restantes'] ?? 0);
        if ($diasRest < 0) {
            $estado = "YA VENCIDO (hace " . abs($diasRest) . " días)";
        } elseif ($diasRest === 0) {
            $estado = "VENCE HOY";
        } else {
            $estado = "vence en {$diasRest} días";
        }
        $lineas[] = "• {$nombre}\n   Fecha: {$fv} — {$estado}";
    }
}
$lineas[] = str_repeat('=', 42);
$lineas[] = "Total alertas: " . (count($stockBajo) + count($porVencer));

$msg = implode("\n", $lineas);
echo $msg . "\n\n";

$ok = enviarTelegram($botToken, $chatId, $msg);
echo $ok ? "✅ Mensaje enviado a Telegram.\n" : "❌ No se pudo enviar el mensaje a Telegram.\n";

/**
 * Envía un mensaje de texto a un chat de Telegram usando la API HTTP.
 * @return bool true si se envió correctamente
 */
function enviarTelegram(string $botToken, string $chatId, string $texto): bool {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $payload = http_build_query([
        'chat_id' => $chatId,
        'text'    => $texto,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        error_log("Telegram curl error (HTTP {$httpCode}): {$curlErr}");
        return false;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || ($data['ok'] ?? false) !== true) {
        $descripcion = is_array($data) ? (string) ($data['description'] ?? 'Respuesta inválida') : 'Respuesta no JSON';
        error_log("Telegram API error (HTTP {$httpCode}): {$descripcion}");
        return false;
    }
    return true;
}
