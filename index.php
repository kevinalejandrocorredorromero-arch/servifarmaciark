<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controlador.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Pedido.php';
require_once __DIR__ . '/models/Carrito.php';
require_once __DIR__ . '/controllers/PageController.php';

$controller = new PageController();
$controller->index();
