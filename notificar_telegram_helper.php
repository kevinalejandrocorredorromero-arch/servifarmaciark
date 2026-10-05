<?php
/**
 * notificar_telegram_helper.php
 * Funciones reutilizables para enviar alertas de productos por Telegram.
 * Se incluye tanto en notificar_telegram.php como en el controlador de productos.
 */

if (!function_exists('enviarAlertaTelegram')) {
    /**
     * Llama cualquier método de la API de Telegram (sendMessage, editMessageText,
     * answerCallbackQuery, ...) por POST. Devuelve la respuesta decodificada o
     * ['ok' => false] si falló (el error queda en error_log).
     */
    function llamarApiTelegram(string $botToken, string $metodo, array $params = []): array {
        $url = "https://api.telegram.org/bot{$botToken}/{$metodo}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log("Telegram curl error ({$metodo}, HTTP {$httpCode}): {$curlErr}");
            return ['ok' => false];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['ok'] ?? false) !== true) {
            $descripcion = is_array($data) ? (string) ($data['description'] ?? 'Respuesta inválida') : 'Respuesta no JSON';
            error_log("Telegram API error ({$metodo}, HTTP {$httpCode}): {$descripcion}");
            return ['ok' => false];
        }
        return $data;
    }

    /**
     * Envía un mensaje de texto a un chat de Telegram usando la API HTTP.
     * @param array|null $replyMarkup Markup de teclado (inline_keyboard) opcional.
     * @return bool true si se envió correctamente
     */
    function enviarAlertaTelegram(string $botToken, string $chatId, string $texto, ?array $replyMarkup = null): bool {
        $params = [
            'chat_id' => $chatId,
            'text'    => $texto,
        ];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        return (llamarApiTelegram($botToken, 'sendMessage', $params)['ok'] ?? false) === true;
    }

    /**
     * Secreto compartido del webhook de Telegram, derivado del token del bot
     * (evita añadir otra env var en Render). Telegram lo envía en la cabecera
     * X-Telegram-Bot-Api-Secret-Token en cada update del webhook.
     */
    function obtenerSecretWebhookTelegram(string $botToken): string {
        return substr(hash('sha256', $botToken . '|servifarmacia-webhook'), 0, 48);
    }

    /**
     * Marcador corto del entorno (local vs producción), derivado del host y
     * nombre de la base de datos. Se incrusta en el callback_data de los
     * botones para que el webhook IGNORE los clics de mensajes generados por
     * el otro entorno (comparten bot y chat de Telegram, pero los IDs de
     * pedido son de bases de datos distintas).
     */
    function obtenerMarcadorEntornoTelegram(array $config): string {
        $clave = ($config['db']['host'] ?? '') . '|' . ($config['db']['name'] ?? '');
        return substr(hash('sha256', $clave . '|servifarmacia-entorno'), 0, 8);
    }

    /**
     * Chat destino de los PEDIDOS: el grupo si está configurado; si no, el
     * chat privado. Cadena vacía si no hay ninguno. Las alertas de inventario
     * NO usan esto: van siempre al chat privado.
     */
    function obtenerChatDestinoPedidosTelegram(array $tg): string {
        $destino = trim((string) ($tg['group_chat_id'] ?? ''));
        if ($destino === '') {
            $destino = trim((string) ($tg['chat_id'] ?? ''));
        }
        return $destino;
    }

    /**
     * Arma y envía la alerta de UN producto si está en stock bajo o por vencer.
     * Devuelve true si envió el mensaje.
     */
    function notificarProductoSiAlerta(int $productoId): bool {
        $config = require __DIR__ . '/config/config.php';
        $tg = $config['telegram'] ?? null;
        if (!$tg || empty($tg['bot_token'])) {
            return false;
        }
        $destino = trim((string) ($tg['chat_id'] ?? ''));
        if ($destino === '') {
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

        return enviarAlertaTelegram($tg['bot_token'], $destino, $msg);
    }

    /**
     * Tras crear un pedido, avisa por Telegram si algún producto cruzó hacia
     * stock bajo con esa compra (stock_antes > umbral y stock_despues <= umbral).
     * Solo notifica el cruce para no repetir alertas en cada compra de un
     * producto que ya estaba bajo.
     *
     * @param array $transiciones [['id' => int, 'stock_antes' => int, 'stock_despues' => int], ...]
     * @return array IDs de los productos notificados
     */
    function notificarStockBajoTrasPedido(array $transiciones): array {
        if (empty($transiciones)) {
            return [];
        }
        $config = require __DIR__ . '/config/config.php';
        $tg = $config['telegram'] ?? null;
        if (!$tg || empty($tg['bot_token'])) {
            return [];
        }
        $umbral = (int) ($tg['umbral_bajo_stock'] ?? ($config['whatsapp']['umbral_bajo_stock'] ?? 10));

        $notificados = [];
        foreach ($transiciones as $t) {
            $id = (int) ($t['id'] ?? 0);
            $antes = (int) ($t['stock_antes'] ?? 0);
            $despues = (int) ($t['stock_despues'] ?? 0);
            if ($id > 0 && $antes > $umbral && $despues <= $umbral) {
                if (notificarProductoSiAlerta($id)) {
                    $notificados[] = $id;
                }
            }
        }
        return $notificados;
    }

    /**
     * Envía por Telegram el resumen de alertas de inventario:
     * stock bajo (stock_total_unidades <= umbral) y por vencer / vencidos.
     * Con $forzar = true envía el mensaje aunque no haya alertas.
     *
     * @return array {enviado: bool, motivo: string, stock_bajo: int, por_vencer: int, mensaje: string}
     */
    function notificarResumenAlertasTelegram(bool $forzar = false): array {
        $config = require __DIR__ . '/config/config.php';
        $tg = $config['telegram'] ?? null;
        if (!$tg || empty($tg['bot_token'])) {
            return ['enviado' => false, 'motivo' => 'sin_config', 'stock_bajo' => 0, 'por_vencer' => 0, 'mensaje' => ''];
        }
        $destino = trim((string) ($tg['chat_id'] ?? ''));
        if ($destino === '') {
            return ['enviado' => false, 'motivo' => 'sin_destino', 'stock_bajo' => 0, 'por_vencer' => 0, 'mensaje' => ''];
        }
        $umbral = (int) ($tg['umbral_bajo_stock'] ?? ($config['whatsapp']['umbral_bajo_stock'] ?? 10));
        $dias   = (int) ($config['whatsapp']['dias_anticipacion_vencimiento'] ?? ($tg['dias_anticipacion_vencimiento'] ?? 30));

        require_once __DIR__ . '/core/Database.php';
        $db = Database::getInstance();

        $sqlStock = "SELECT id, nombre, stock, stock_total_unidades, umbral_stock, categoria
                     FROM productos
                     WHERE stock_total_unidades <= {$umbral}
                     ORDER BY stock_total_unidades ASC
                     LIMIT 50";
        $res = $db->query($sqlStock);
        $stockBajo = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $stockBajo[] = $row;
            }
        }

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
        if ($res2) {
            while ($row = mysqli_fetch_assoc($res2)) {
                $porVencer[] = $row;
            }
        }

        if (empty($stockBajo) && empty($porVencer) && !$forzar) {
            return ['enviado' => false, 'motivo' => 'sin_alertas', 'stock_bajo' => 0, 'por_vencer' => 0, 'mensaje' => ''];
        }

        $lineas = [];
        $lineas[] = "📋 SERVIFARMACIA RK — RESUMEN DE ALERTAS";
        $lineas[] = "Generado: " . date('Y-m-d H:i:s');
        $lineas[] = str_repeat('=', 42);
        $lineas[] = "⚠️ STOCK BAJO (<= {$umbral} unidades)";
        if (empty($stockBajo)) {
            $lineas[] = "  (sin productos en esta categoría)";
        } else {
            foreach ($stockBajo as $p) {
                $nombre = $p['nombre'] ?? '(sin nombre)';
                $stockU = (int) $p['stock_total_unidades'];
                $lineas[] = "• {$nombre}\n   Stock: {$stockU} uds (cajas: " . (int) $p['stock'] . ")";
            }
        }
        $lineas[] = str_repeat('-', 42);
        $lineas[] = "⏰ POR VENCER (en los próximos {$dias} días)";
        if (empty($porVencer)) {
            $lineas[] = "  (sin productos en esta categoría)";
        } else {
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
        $ok = enviarAlertaTelegram($tg['bot_token'], $destino, $msg);

        return [
            'enviado' => $ok,
            'motivo' => $ok ? 'ok' : 'error_envio',
            'stock_bajo' => count($stockBajo),
            'por_vencer' => count($porVencer),
            'mensaje' => $msg,
        ];
    }

    /**
     * Revisa las alertas de inventario una vez al día y las envía sola.
     * Se llama al cargar la app (index.php / api.php): si hoy ya corrió no
     * hace nada; si un envío falla, reintenta como máximo una vez por hora.
     *
     * @return array|null Resultado del envío, o null si todavía no toca ejecutar.
     */
    function ejecutarAlertasInventarioDiarias(): ?array {
        $marcador = __DIR__ . '/logs/ultima_alerta_inventario.txt';
        $dir = dirname($marcador);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $ahora = time();
        $marca = is_file($marcador) ? (int) trim((string) @file_get_contents($marcador)) : 0;
        if ($marca > 0 && date('Y-m-d', $marca) === date('Y-m-d', $ahora)) {
            return null;
        }
        if ($marca > 0 && ($ahora - $marca) < 3600) {
            return null;
        }
        @file_put_contents($marcador, (string) $ahora, LOCK_EX);
        return notificarResumenAlertasTelegram(false);
    }

    /**
     * Arma y envía una notificación de NUEVO PEDIDO a un grupo de Telegram.
     * Usa la misma config 'telegram' (bot_token y chat_id) de config.php.
     *
     * @param string $numeroPedido Número de pedido (ej. RK20261003...)
     * @param float  $total        Total del pedido
     * @param string $metodoPago   Método de pago (cash, etc.)
     * @param array  $infoEntrega  Datos de entrega (name, phone, address)
     * @param array  $items        [{nombre, categoria, cantidad, total}]
     * @param int    $pedidoId     ID interno del pedido; si es > 0 añade botones
     *                             de confirmar entrega / cancelar para el repartidor.
     * @return bool true si se envió correctamente
     */
    function notificarNuevoPedidoTelegram(string $numeroPedido, float $total, string $metodoPago, array $infoEntrega = [], array $items = [], int $pedidoId = 0): bool {
        $config = require __DIR__ . '/config/config.php';
        $tg = $config['telegram'] ?? null;
        if (!$tg || empty($tg['bot_token'])) {
            return false;
        }

        // Los pedidos van al grupo si está configurado; si no, al chat privado.
        $chatDestino = obtenerChatDestinoPedidosTelegram($tg);
        if ($chatDestino === '') {
            return false;
        }

        $lineas = [];
        $lineas[] = "🛒🛒 SERVIFARMACIA RK — NUEVO PEDIDO";
        $lineas[] = "─────────────────";
        $lineas[] = "🧾 Pedido: {$numeroPedido}";
        $lineas[] = "🕑 Fecha: " . date('Y-m-d H:i:s');
        $lineas[] = "💰 Total: $" . number_format($total, 2);
        $lineas[] = "💳 Pago: {$metodoPago}";

        $nombre = trim((string) ($infoEntrega['name'] ?? ''));
        $telefono = trim((string) ($infoEntrega['phone'] ?? ''));
        $direccion = trim((string) ($infoEntrega['address'] ?? ''));
        if ($nombre !== '' || $telefono !== '' || $direccion !== '') {
            $lineas[] = "─────────────────";
            $lineas[] = "📬 ENTREGA:";
            if ($nombre !== '') $lineas[] = "👤 {$nombre}";
            if ($telefono !== '') $lineas[] = "📞 {$telefono}";
            if ($direccion !== '') $lineas[] = "📍 {$direccion}";
        }

        if (!empty($items)) {
            $lineas[] = "─────────────────";
            $lineas[] = "🛍️ DETALLE:";
            foreach ($items as $item) {
                $nom = trim((string) ($item['nombre'] ?? $item['name'] ?? ''));
                $cant = (int) ($item['cantidad'] ?? $item['quantity'] ?? 0);
                $tot = (float) ($item['total'] ?? 0);
                $lineas[] = "• {$nom} × {$cant} = $" . number_format($tot, 2);
            }
        }

        $lineas[] = "─────────────────";
        $lineas[] = "🔄 Estado: Pendiente";

        $markup = null;
        if ($pedidoId > 0) {
            $marcador = obtenerMarcadorEntornoTelegram($config);
            $markup = ['inline_keyboard' => [[
                ['text' => '✅ Confirmar entrega', 'callback_data' => "entregar_{$pedidoId}_{$marcador}"],
                ['text' => '❌ Cancelar pedido', 'callback_data' => "cancelar_{$pedidoId}_{$marcador}"],
            ]]];
        }

        return enviarAlertaTelegram($tg['bot_token'], $chatDestino, implode("\n", $lineas), $markup);
    }
}
