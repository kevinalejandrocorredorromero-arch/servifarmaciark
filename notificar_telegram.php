<?php
/**
 * notificar_telegram.php
 * Envía por Telegram el resumen de alertas de inventario:
 *   1) Productos con stock bajo (stock_total_unidades <= umbral)
 *   2) Productos por vencer o ya vencidos (fecha_vencimiento dentro de N días)
 *
 * La app ya ejecuta el chequeo sola al abrirse (una vez al día, ver
 * ejecutarAlertasInventarioDiarias en notificar_telegram_helper.php). Este
 * archivo sirve como respaldo para un cron/Task Scheduler externo: sin
 * parámetros respeta el envío diario (no duplica mensajes), y con ?forzar=1
 * envía de inmediato aunque ya se haya enviado hoy.
 *
 * Uso:
 *   - Automático: abrir notificar_telegram.php en el navegador (o desde un cron)
 *   - Prueba manual forzada: ?forzar=1
 */

require_once __DIR__ . '/notificar_telegram_helper.php';

header('Content-Type: text/plain; charset=utf-8');

$forzar = isset($_GET['forzar']) && $_GET['forzar'] == '1';

if ($forzar) {
    $resultado = notificarResumenAlertasTelegram(true);
} else {
    $resultado = ejecutarAlertasInventarioDiarias();
    if ($resultado === null) {
        echo "Sin envío: el chequeo de alertas ya se ejecutó recientemente.\n";
        exit(0);
    }
}

if (in_array(($resultado['motivo'] ?? ''), ['sin_config', 'sin_destino'], true)) {
    echo "ERROR: falta configuración de Telegram en config.php\n";
    exit(1);
}
if (($resultado['motivo'] ?? '') === 'sin_alertas') {
    echo "Sin alertas pendientes. No se envió ningún mensaje.\n";
    exit(0);
}

if (!empty($resultado['mensaje'])) {
    echo $resultado['mensaje'] . "\n\n";
}
echo !empty($resultado['enviado'])
    ? "✅ Mensaje enviado a Telegram.\n"
    : "❌ No se pudo enviar el mensaje a Telegram.\n";
