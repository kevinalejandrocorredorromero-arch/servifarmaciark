<?php

class CarritoControlador extends Controlador {
    public function __construct() {
        parent::__construct();
    }

    private function propietarioActual(): array {
        $userId = Auth::idUsuario();
        if ($userId > 0) {
            return ['user_id' => $userId, 'session_id' => null];
        }
        return ['user_id' => null, 'session_id' => Auth::sessionIdCarrito()];
    }

    private function rechazarPropietarioAjeno(array $data, array $owner): bool {
        $requestedUser = (int) ($data['user_id'] ?? $data['usuario_id'] ?? 0);
        $requestedSession = (string) ($data['session_id'] ?? $data['sesion_id'] ?? '');
        if ($requestedUser > 0 && $requestedUser !== (int) ($owner['user_id'] ?? 0)) {
            $this->error('Propietario de carrito inválido', 403);
            return true;
        }
        if ($requestedSession !== '' && $requestedSession !== (string) ($owner['session_id'] ?? '')) {
            $this->error('Sesión de carrito inválida', 403);
            return true;
        }
        return false;
    }

    public function obtener(): void {
        $owner = $this->propietarioActual();
        $requestedUser = (int) ($_GET['user_id'] ?? $_GET['usuario_id'] ?? 0);
        $requestedSession = (string) ($_GET['session_id'] ?? $_GET['sesion_id'] ?? '');
        if ($this->rechazarPropietarioAjeno([
            'user_id' => $requestedUser,
            'session_id' => $requestedSession,
        ], $owner)) return;

        $model = new CarritoModelo();
        $cart = $model->obtenerItems($owner['user_id'], $owner['session_id']);
        $this->success(['cart' => $cart]);
    }

    public function agregar(): void {
        $data = $this->getInput();
        $owner = $this->propietarioActual();
        if ($this->rechazarPropietarioAjeno($data, $owner)) return;

        $model = new CarritoModelo();
        $cart = $model->agregarItem($data, $owner['user_id'], $owner['session_id']);
        if ($cart !== null) {
            $this->jsonResponse(['success' => true, 'cart' => $cart]);
        }
        $this->error('Error al agregar al carrito');
    }

    public function actualizarItem(): void {
        $data = $this->getInput();
        $cartId = (int) ($data['cart_id'] ?? 0);
        if ($cartId <= 0) {
            $this->error('cart_id requerido');
            return;
        }
        $owner = $this->propietarioActual();
        $model = new CarritoModelo();
        $quantity = (int) ($data['quantity'] ?? 1);
        if ($model->actualizarCantidad($cartId, $quantity, $owner['user_id'], $owner['session_id'])) {
            $this->success();
            return;
        }
        $this->error('Carrito no encontrado o no autorizado', 403);
    }

    public function eliminar(): void {
        $data = $this->getInput();
        $cartId = (int) ($data['cart_id'] ?? 0);
        if ($cartId <= 0) {
            $this->error('cart_id requerido');
            return;
        }
        $owner = $this->propietarioActual();
        $model = new CarritoModelo();
        if ($model->eliminarItem($cartId, $owner['user_id'], $owner['session_id'])) {
            $this->success();
            return;
        }
        $this->error('Carrito no encontrado o no autorizado', 403);
    }

    public function vaciar(): void {
        $data = $this->getInput();
        $owner = $this->propietarioActual();
        if ($this->rechazarPropietarioAjeno($data, $owner)) return;
        $model = new CarritoModelo();
        if ($model->vaciar($owner['user_id'], $owner['session_id'])) {
            $this->success();
            return;
        }
        $this->error('Error al vaciar el carrito');
    }
}
