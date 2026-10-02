<?php

return [
    'db' => [
        'host' => 'db',
        'port' => 3306,
        'user' => 'servifarmacia',
        'pass' => 'CHANGE_ME',
        'name' => 'servifarmacia_rk',
        'charset' => 'utf8mb4',
        'ssl' => false,
    ],
    'ai' => [
        'provider' => 'huggingface',
        'api_key' => 'CHANGE_ME_OR_DISABLE_CHAT',
        'model' => 'meta-llama/Llama-3.1-8B-Instruct',
        'api_url' => 'https://router.huggingface.co/v1/chat/completions',
    ],
    'app' => [
        'name' => 'SERVIFARMACIA RK',
        'url' => 'https://app.example.com/',
    ],
    'whatsapp' => [
        'numero' => '573115631854',
        'umbral_bajo_stock' => 5,
        'dias_anticipacion_vencimiento' => 30,
    ],
    'firebase' => [
        'project_id' => 'CHANGE_ME',
        'api_key' => 'CHANGE_ME',
        'auth_domain' => 'CHANGE_ME.firebaseapp.com',
    ],
    'telegram' => [
        'bot_token' => 'CHANGE_ME',
        'chat_id' => 'CHANGE_ME',
        'umbral_bajo_stock' => 10,
    ],
    'brevo' => [
        'api_key' => 'CHANGE_ME',
        'sender_email' => 'CHANGE_ME',
        'sender_name' => 'SERVIFARMACIA RK',
    ],
];
