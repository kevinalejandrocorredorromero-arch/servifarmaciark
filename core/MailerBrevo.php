<?php
/**
 * MailerBrevo.php
 * Envío de correos transaccionales por la API de Brevo (https://api.brevo.com/v3/smtp/email).
 * Reutiliza el patrón curl de notificar_telegram_helper.php, pero sin exponer
 * el token de verificación ni la API key en los logs.
 */

class MailerBrevo {
    /**
     * Envía el correo de verificación de cuenta.
     *
     * @param string $email   Destinatario.
     * @param string $nombre  Nombre del destinatario.
     * @param string $token   Token de verificación (se incluye solo en el enlace).
     * @param array  $config  Configuración completa de la app (espera $config['brevo'] y $config['app']['url']).
     * @return bool true si Brevo aceptó el envío.
     */
    public static function enviarVerificacion(string $email, string $nombre, string $token, array $config): bool {
        $brevo = $config['brevo'] ?? null;
        if (!$brevo || empty($brevo['api_key'])) {
            error_log('[MailerBrevo] No hay api_key de Brevo configurada.');
            return false;
        }

        $apiKey = (string) $brevo['api_key'];
        $senderEmail = (string) ($brevo['sender_email'] ?? '');
        $senderName = (string) ($brevo['sender_name'] ?? '');
        $baseUrl = rtrim((string) ($config['app']['url'] ?? ''), '/');

        if ($senderEmail === '' || $baseUrl === '') {
            error_log('[MailerBrevo] Faltan sender_email o app.url en la configuración.');
            return false;
        }

        // El enlace apunta a una página pública del mismo servidor; el token viaja
        // solo en la URL del correo.
        $enlace = $baseUrl . '/verificar-correo.php?token=' . rawurlencode($token);

        $contenido = <<<HTML
<h2>Verifica tu correo electrónico</h2>
<p>Hola <strong>{$nombre}</strong>,</p>
<p>Gracias por crear tu cuenta en SERVIFARMACIA RK. Para poder iniciar sesión, confirma tu correo con este enlace:</p>
<p><a href="{$enlace}" style="background:#198754;color:#fff;padding:12px 20px;text-decoration:none;border-radius:6px;display:inline-block;">Verificar mi correo</a></p>
<p>Si el botón no funciona, copia y pega esta dirección en tu navegador:</p>
<p>{$enlace}</p>
<p>Este enlace es válido por 24 horas. Si no solicitaste esta cuenta, ignora este mensaje.</p>
<p>— SERVIFARMACIA RK</p>
HTML;

        $payload = [
            'sender'      => ['email' => $senderEmail, 'name' => $senderName],
            'to'          => [['email' => $email, 'name' => $nombre]],
            'subject'     => 'Verifica tu correo — SERVIFARMACIA RK',
            'htmlContent' => $contenido,
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log('[MailerBrevo] curl error (HTTP ' . $httpCode . '): ' . $curlErr);
            return false;
        }

        $data = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && is_array($data)) {
            // Sin datos sensibles en el log: solo confirmación del envío.
            $messageId = (string) ($data['messageId'] ?? '');
            error_log('[MailerBrevo] Correo de verificación enviado a ' . $email . ' (messageId: ' . $messageId . ')');
            return true;
        }

        $descripcion = is_array($data) ? (string) ($data['message'] ?? $data['error'] ?? 'Respuesta inválida') : 'Respuesta no JSON';
        error_log('[MailerBrevo] API error (HTTP ' . $httpCode . '): ' . $descripcion);
        return false;
    }
}
