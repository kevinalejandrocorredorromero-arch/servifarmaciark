<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controlador.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Pedido.php';
require_once __DIR__ . '/models/Carrito.php';
require_once __DIR__ . '/controllers/PageController.php';

// Una vez al día, al abrir la app, envía sola el resumen de alertas de
// inventario (stock bajo y por vencer) por Telegram.
require_once __DIR__ . '/notificar_telegram_helper.php';
ejecutarAlertasInventarioDiarias();

$controller = new PageController();
$controller->index();
