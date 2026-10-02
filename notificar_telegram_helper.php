<?php
/**
 * notificar_telegram_helper.php
 * Funciones reutilizables para enviar alertas de productos por Telegram.
 * Se incluye tanto en notificar_telegram.php como en el controlador de productos.
 */

if (!function_exists('enviarAlertaTelegram')) {
    /**
     * Envía un mensaje de texto a un chat de Telegram usando la API HTTP.
     * @return bool true si se envió correctamente
     */
    function enviarAlertaTelegram(string $botToken, string $chatId, string $texto): bool {
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

    /**
     * Arma y envía la alerta de UN producto si está en stock bajo o por vencer.
     * Devuelve true si envió el mensaje.
     */
    function notificarProductoSiAlerta(int $productoId): bool {
        $config = require __DIR__ . '/config/config.php';
        $tg = $config['telegram'] ?? null;
        if (!$tg || empty($tg['bot_token']) || empty($tg['chat_id'])) {
            return false;
        }
        $umbral = (int) ($tg['umbral_bajo_stock'] ?? ($config['whatsapp']['umbral_bajo_stock'] ?? 10));
        $dias   = (int) ($config['whatsapp']['dias_anticipacion_vencimiento'] ?? ($tg['dias_anticipacion_vencimiento'] ?? 30));

        require_once __DIR__ . '/core/Database.php';
        $db = Database::getInstance();

        $sql = "SELECT id, nombre, stock, stock_total_unidades,
                       fecha_vencimiento,
                       DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
                FROM productos WHERE id = {$productoId} LIMIT 1";
        $res = $db->query($sql);
        $p = mysqli_fetch_assoc($res);
        if (!$p) return false;

        $lineas = [];
        $hayAlerta = false;

        $stockU = (int) $p['stock_total_unidades'];
        if ($stockU <= $umbral) {
            $hayAlerta = true;
            $lineas[] = "⚠️ STOCK BAJO";
            $lineas[] = "• {$p['nombre']} — Stock: {$stockU} uds (cajas: " . (int)$p['stock'] . ")";
        }

        $fv = $p['fecha_vencimiento'] ?? '';
        if (!empty($fv) && $fv !== '0000-00-00') {
            $dr = (int) ($p['dias_restantes'] ?? 0);
            if ($dr <= $dias) {
                $hayAlerta = true;
                if ($dr < 0)      $estado = "YA VENCIDO (hace " . abs($dr) . " días)";
                elseif ($dr === 0) $estado = "VENCE HOY";
                else               $estado = "vence en {$dr} días";
                $lineas[] = "⏰ POR VENCER";
                $lineas[] = "• {$p['nombre']} — Fecha: {$fv} — {$estado}";
            }
        }

        if (!$hayAlerta) return false;

        array_unshift($lineas, "📋 SERVIFARMACIA RK — ALERTA DE PRODUCTO");
        array_unshift($lineas, "Generado: " . date('Y-m-d H:i:s'));
        $msg = implode("\n", $lineas);

        return enviarAlertaTelegram($tg['bot_token'], $tg['chat_id'], $msg);
    }
}
