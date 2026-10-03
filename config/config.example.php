<?php

return [
    'db' => [
        'host' => '127.0.0.1',
        'user' => 'root',
        'pass' => '',
        'name' => 'servifarmacia_rk',
        'charset' => 'utf8',
    ],
    'ai' => [
        'provider' => 'huggingface',
        'api_key' => 'COLOCA_AQUI_TU_HUGGINGFACE_API_KEY',
        'model' => 'meta-llama/Llama-3.1-8B-Instruct',
        'api_url' => 'https://router.huggingface.co/v1/chat/completions',
    ],
    'app' => [
        'name' => 'SERVIFARMACIA RK',
        'url' => 'http://localhost/servifarmacia%20rk/',
    ],
    'whatsapp' => [
        'numero' => '573115631854',
        'umbral_bajo_stock' => 5,
        'dias_anticipacion_vencimiento' => 30,
    ],
    'firebase' => [
        'project_id'  => 'COLOCA_AQUI_TU_FIREBASE_PROJECT_ID',
        'api_key'     => 'COLOCA_AQUI_TU_FIREBASE_API_KEY',
        'auth_domain' => 'COLOCA_AQUI_TU_FIREBASE_AUTH_DOMAIN',
    ],
    'telegram' => [
        'bot_token' => 'COLOCA_AQUI_TU_TELEGRAM_BOT_TOKEN',
        'chat_id'   => 'COLOCA_AQUI_TU_TELEGRAM_CHAT_ID',
        'group_chat_id' => 'COLOCA_AQUI_EL_ID_DEL_GRUPO_DE_PEDIDOS (opcional)',
        'umbral_bajo_stock' => 10,
    ],
];
