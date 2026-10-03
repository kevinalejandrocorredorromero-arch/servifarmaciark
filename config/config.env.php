<?php

// Configuración para contenedores (Render/Docker): los valores provienen de
// variables de entorno definidas en el panel del proveedor. No contiene secretos.

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'user' => getenv('DB_USER') ?: 'servifarmacia',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'name' => getenv('DB_NAME') ?: 'servifarmacia_rk',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        'ssl' => in_array(strtolower((string) getenv('DB_SSL')), ['1', 'true', 'yes'], true),
    ],
    'ai' => [
        'provider' => 'gemini',
        'api_key' => getenv('AI_API_KEY') ?: '',
        'model' => getenv('AI_MODEL') ?: 'gemini-3.5-flash-lite',
        'api_url' => getenv('AI_API_URL') ?: 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
    ],
    'app' => [
        'name' => getenv('APP_NAME') ?: 'SERVIFARMACIA RK',
        'url' => getenv('APP_URL') ?: '/',
    ],
    'whatsapp' => [
        'numero' => getenv('WHATSAPP_NUMERO') ?: '',
        'umbral_bajo_stock' => (int) (getenv('WHATSAPP_UMBRAL_BAJO_STOCK') ?: 5),
        'dias_anticipacion_vencimiento' => (int) (getenv('WHATSAPP_DIAS_VENCIMIENTO') ?: 30),
    ],
    'firebase' => [
        'project_id' => getenv('FIREBASE_PROJECT_ID') ?: '',
        'api_key' => getenv('FIREBASE_API_KEY') ?: '',
        'auth_domain' => getenv('FIREBASE_AUTH_DOMAIN') ?: '',
    ],
    'telegram' => [
        'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'chat_id' => getenv('TELEGRAM_CHAT_ID') ?: '',
        'group_chat_id' => getenv('TELEGRAM_GROUP_CHAT_ID') ?: '',
        'umbral_bajo_stock' => (int) (getenv('TELEGRAM_UMBRAL_BAJO_STOCK') ?: 10),
    ],
    'brevo' => [
        'api_key' => getenv('BREVO_API_KEY') ?: '',
        'sender_email' => getenv('BREVO_SENDER_EMAIL') ?: '',
        'sender_name' => getenv('BREVO_SENDER_NAME') ?: 'SERVIFARMACIA RK',
    ],
];
