<?php

class PedidoControlador extends Controlador {
    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodos(): void {
        $rol = Auth::rol();
        $userId = Auth::idUsuario();
        $model = new PedidoModelo();

        // Admin ve todos; vendedor ve sus ventas; cliente solo sus pedidos.
        if ($rol === 'admin') {
            $this->success(['orders' => $model->obtenerTodos(null)]);
            return;
        }
        $this->success(['orders' => $model->obtenerTodos($userId > 0 ? $userId : null)]);
    }

    public function crear(): void {
        $data = $this->getInput();

        if (empty($data['items'])) {
            $this->error('No hay productos en el carrito');
            return;
        }

        // Las ventas de mostrador (lector de códigos de barras) no llevan datos de
        // entrega: las registra el personal y el pedido queda a su nombre.
        if (!empty($data['en_tienda'])) {
            if (!in_array(Auth::rol(), ['seller', 'admin'], true)) {
                $this->error('Solo el personal puede registrar ventas en tienda', 403);
                return;
            }
            $data['deliveryInfo'] = [
                'name'    => 'Venta en tienda',
                'phone'   => '',
                'address' => 'Mostrador',
            ];
        } else {
            $deliveryInfo = $data['deliveryInfo'] ?? [];
            if (!is_array($deliveryInfo)) {
                $this->error('Información de entrega inválida');
                return;
            }
            $nombreEntrega = trim((string) ($deliveryInfo['name'] ?? ''));
            $telefonoEntrega = trim((string) ($deliveryInfo['phone'] ?? ''));
            $direccionEntrega = trim((string) ($deliveryInfo['address'] ?? ''));
            if ($nombreEntrega === '' || $telefonoEntrega === '' || $direccionEntrega === '') {
                $this->error('Información de entrega incompleta');
                return;
            }
            if (mb_strlen($nombreEntrega) > 120 || mb_strlen($telefonoEntrega) > 30 || mb_strlen($direccionEntrega) > 300) {
                $this->error('La información de entrega supera el límite permitido');
                return;
            }
            if (!preg_match('/^[0-9+()\-\s]{7,30}$/', $telefonoEntrega)) {
                $this->error('Teléfono de entrega inválido');
                return;
            }
            $data['deliveryInfo'] = [
                'name' => $nombreEntrega,
                'phone' => $telefonoEntrega,
                'address' => $direccionEntrega,
            ];
        }

        if (!is_array($data['items']) || count($data['items']) > 100) {
            $this->error('Lista de productos inválida');
            return;
        }

        $model = new PedidoModelo();

        // Validar stock disponible para cada producto antes de crear el pedido
        $productModel = new ProductoModelo();
        foreach ($data['items'] as $item) {
            $productId = (int) ($item['id'] ?? $item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                $this->error('Producto o cantidad inválida');
                return;
            }
            $product = $productModel->obtenerPorId($productId);
            if (!$product) {
                $this->error("El producto con ID {$productId} no existe");
                return;
            }
            // Validación de stock según presentación (caja/unidad) para productos fraccionables
            $tipoVenta = strtolower(trim($item['tipo_venta'] ?? $item['tipoVenta'] ?? 'unidad'));
            $esFracc = !empty($product['is_fractionable']);
            if ($esFracc && $tipoVenta === 'caja') {
                $udsCaja = (int) ($product['units_per_box'] ?? 0);
                $stockDisp = $udsCaja > 0 ? intdiv((int) ($product['stock_total_units'] ?? 0), $udsCaja) : 0;
                $unidad = 'cajas';
            } elseif ($esFracc) {
                $stockDisp = (int) ($product['stock_total_units'] ?? 0);
                $unidad = 'unidades sueltas';
            } else {
                $stockDisp = (int) ($product['stock'] ?? 0);
                $unidad = 'unidades';
            }
            if ($quantity > $stockDisp) {
                $name = $product['name'] ?? "ID {$productId}";
                $this->error("Stock insuficiente para \"{$name}\": solicitas {$quantity} {$unidad} pero solo hay {$stockDisp} disponibles");
                return;
            }
        }

        $orderNumber = $model->crear($data, $resumenPedido);

        if ($orderNumber) {
            require_once __DIR__ . '/../notificar_telegram_helper.php';
            notificarNuevoPedidoTelegram(
                $orderNumber,
                (float) ($resumenPedido['total'] ?? 0),
                (string) ($data['paymentMethod'] ?? $data['metodo_pago'] ?? 'cash'),
                $data['deliveryInfo'],
                $resumenPedido['items'] ?? [],
                (int) ($resumenPedido['pedido_id'] ?? 0),
                (string) ($resumenPedido['estado'] ?? 'pendiente')
            );
            // Si la compra dejó algún producto en stock bajo, alerta aparte.
            notificarStockBajoTrasPedido($resumenPedido['stock_transiciones'] ?? []);

            // Devolver los datos completos del pedido para mostrar un recibo.
            $orderId = $resumenPedido['pedido_id'] ?? 0;
            $userId = Auth::idUsuario();
            $rol = Auth::rol();
            $order = $model->obtenerPorId($orderId, $userId, $rol);
            $this->success(['orderNumber' => $orderNumber, 'order' => $order]);
        } else {
            $this->error('Error al procesar el pedido');
        }
    }

    public function cancelar(): void {
        $data = $this->getInput();
        $orderId = (int) ($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            $this->error('ID de pedido inválido');
            return;
        }

        $model = new PedidoModelo();
        $esAdmin = Auth::rol() === 'admin';
        if ($model->cancelar($orderId, Auth::idUsuario(), $esAdmin)) {
            $this->success();
        } else {
            $this->error('No puedes cancelar este pedido o ya no está pendiente', 403);
        }
    }

    public function confirmarEntrega(): void {
        $data = $this->getInput();
        $orderId = (int) ($data['order_id'] ?? $_POST['order_id'] ?? 0);

        if ($orderId <= 0) {
            $this->error('ID de pedido inválido');
            return;
        }

        $model = new PedidoModelo();
        $pedido = $model->obtenerPorId($orderId, Auth::idUsuario(), Auth::rol());
        if (!$pedido) {
            $this->error('Pedido no encontrado', 404);
            return;
        }
        if (($pedido['status'] ?? '') !== 'pendiente') {
            $this->error('El pedido no está pendiente', 409);
            return;
        }
        if ($model->confirmarEntrega($orderId)) {
            $this->success();
        } else {
            $this->error('No se pudo confirmar la entrega', 409);
        }
    }
}
