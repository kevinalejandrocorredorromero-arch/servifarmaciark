<?php

require_once __DIR__ . '/../core/Auth.php';

class ProductoControlador extends Controlador {
    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodos(): void {
        $model = new ProductoModelo();
        $this->success(['products' => $model->obtenerTodos()]);
    }

    public function obtenerPorId(): void {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->error('ID de producto requerido');
            return;
        }
        $model = new ProductoModelo();
        $product = $model->obtenerPorId($id);
        if ($product) {
            $this->success(['product' => $product]);
        } else {
            $this->error('Producto no encontrado');
        }
    }

    public function crear(): void {
        $data = $this->getInput();
        if (empty($data['name'])) {
            $this->error('Nombre de producto requerido');
            return;
        }
        $descuento = (float) ($data['descuento_porcentaje'] ?? $data['discountPercent'] ?? 0);
        $precioAnterior = $data['precio_anterior'] ?? $data['originalPrice'] ?? null;
        if ($descuento < 0 || $descuento > 100 || ($precioAnterior !== null && $precioAnterior !== '' && (float) $precioAnterior < 0)) {
            $this->error('Descuento o precio anterior inválido');
            return;
        }
        $model = new ProductoModelo();
        $id = $model->crear($data);
        if ($id) {
            $this->success(['product_id' => $id]);
        } else {
            $this->error('Error al crear producto');
        }
    }

    public function actualizar(): void {
        $data = $this->getInput();
        $id = (int) ($data['id'] ?? 0);
        if (!$id) {
            $this->error('ID de producto requerido');
            return;
        }
        $descuento = (float) ($data['descuento_porcentaje'] ?? $data['discountPercent'] ?? 0);
        $precioAnterior = $data['precio_anterior'] ?? $data['originalPrice'] ?? null;
        if ($descuento < 0 || $descuento > 100 || ($precioAnterior !== null && $precioAnterior !== '' && (float) $precioAnterior < 0)) {
            $this->error('Descuento o precio anterior inválido');
            return;
        }
        $model = new ProductoModelo();
        if ($model->actualizar($id, $data)) {
            // Notificar por Telegram si el producto editado quedó en alerta
            require_once __DIR__ . '/../notificar_telegram_helper.php';
            notificarProductoSiAlerta($id);
            $this->success();
        } else {
            $this->error('Error al actualizar producto');
        }
    }

    public function eliminar(): void {
        $data = $this->getInput();
        $id = (int) ($data['id'] ?? 0);
        if (!$id) {
            $this->error('ID requerido');
            return;
        }
        $model = new ProductoModelo();
        if ($model->eliminar($id)) {
            $this->success();
        } else {
            $this->error('Error al eliminar producto');
        }
    }

    public function actualizarStock(): void {
        $data = $this->getInput();
        $id = (int) ($data['id'] ?? 0);
        $change = (int) ($data['change'] ?? 0);
        if (!$id) {
            $this->error('ID requerido');
            return;
        }
        $model = new ProductoModelo();
        if ($model->actualizarStock($id, $change)) {
            // El cambio de stock también puede activar una alerta de Telegram.
            require_once __DIR__ . '/../notificar_telegram_helper.php';
            notificarProductoSiAlerta($id);
            $this->success();
        } else {
            $this->error('Error al actualizar stock');
        }
    }

    public function obtenerAlertas(): void {
        $cfg = require __DIR__ . '/../config/config.php';
        $wa = $cfg['whatsapp'] ?? [];
        $umbral = (int) ($wa['umbral_bajo_stock'] ?? 5);
        $dias = (int) ($wa['dias_anticipacion_vencimiento'] ?? 30);
        $model = new ProductoModelo();
        $alertas = $model->obtenerAlertas($umbral, $dias);
        $this->success([
            'bajo_stock' => $alertas['bajo_stock'],
            'por_vencer' => $alertas['por_vencer'],
            'umbral' => $umbral,
            'dias' => $dias,
        ]);
    }
}
