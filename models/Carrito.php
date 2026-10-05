<?php

class CarritoModelo {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerItems(?int $userId = null, ?string $sessionId = null): array {
        if ($userId > 0) {
            $res = $this->db->query("SELECT * FROM carrito_compras WHERE usuario_id = " . (int) $userId);
        } elseif ($sessionId) {
            $sessionId = $this->db->escape($sessionId);
            $res = $this->db->query("SELECT * FROM carrito_compras WHERE sesion_id = '{$sessionId}'");
        } else {
            return [];
        }

        $cart = [];
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $cart[] = [
                    'id' => (int) $r['id'],
                    'product_id' => (int) $r['producto_id'],
                    'name' => $r['nombre'],
                    'price' => (float) $r['precio'],
                    'img' => $r['imagen'],
                    'quantity' => (int) $r['cantidad'],
                ];
            }
        }
        return $cart;
    }

    public function agregarItem(array $data, ?int $userId, ?string $sessionId): ?array {
        $productId = (int) ($data['product_id'] ?? $data['producto_id'] ?? 0);
        $nombre = $this->db->escape($data['name'] ?? $data['nombre'] ?? '');
        $precio = (float) ($data['price'] ?? $data['precio'] ?? 0);
        $imagen = $this->db->escape($data['img'] ?? $data['imagen'] ?? '');
        $cantidad = (int) ($data['quantity'] ?? $data['cantidad'] ?? 1);
        $userId = $userId > 0 ? $userId : null;
        $sesionId = $sessionId ? $this->db->escape($sessionId) : null;
        $tipoVenta = strtolower(trim((string) ($data['tipo_venta'] ?? $data['tipoVenta'] ?? 'unidad')));
        if (!in_array($tipoVenta, ['caja', 'unidad'])) {
            $tipoVenta = 'unidad';
        }

        if (!$userId && !$sesionId) return null;

        $where = $userId ? "usuario_id = {$userId}" : "sesion_id = '{$sesionId}'";
        // La misma presentación (caja vs unidad) cuenta por separado
        $res = $this->db->query("SELECT * FROM carrito_compras WHERE producto_id = {$productId} AND tipo_venta = '{$tipoVenta}' AND {$where} LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;

        if ($row) {
            $newQty = ((int) $row['cantidad']) + $cantidad;
            // Refrescar el precio: si cambió (ej. descuento nuevo) el item ya
            // guardado no debe conservar el precio viejo.
            $this->db->query("UPDATE carrito_compras SET cantidad = {$newQty}, precio = {$precio}, actualizado_en = NOW() WHERE id = " . (int) $row['id']);
        } else {
            $userIdVal = $userId ?: 'NULL';
            $sessionIdVal = $sesionId ? "'{$sesionId}'" : 'NULL';
            $sql = "INSERT INTO carrito_compras (usuario_id, sesion_id, producto_id, nombre, precio, imagen, cantidad, tipo_venta, creado_en, actualizado_en)
                    VALUES ({$userIdVal}, {$sessionIdVal}, {$productId}, '{$nombre}', {$precio}, '{$imagen}', {$cantidad}, '{$tipoVenta}', NOW(), NOW())";
            $this->db->query($sql);
        }

        return $this->obtenerItems($userId ?: null, $sesionId);
    }

    private function condicionPropietario(?int $userId, ?string $sessionId): string {
        if ($userId > 0) return 'usuario_id = ' . (int) $userId;
        if ($sessionId !== null && $sessionId !== '') {
            return "sesion_id = '" . $this->db->escape($sessionId) . "'";
        }
        return '1 = 0';
    }

    public function actualizarCantidad(int $cartId, int $cantidad, ?int $userId, ?string $sessionId): bool {
        $condicion = $this->condicionPropietario($userId, $sessionId);
        if ($cantidad < 1) {
            $sql = "DELETE FROM carrito_compras WHERE id = " . (int) $cartId . " AND {$condicion}";
        } else {
            $sql = "UPDATE carrito_compras SET cantidad = {$cantidad}, actualizado_en = NOW() WHERE id = " . (int) $cartId . " AND {$condicion}";
        }
        $res = $this->db->query($sql);
        return $res !== false && mysqli_affected_rows($this->db->getConnection()) > 0;
    }

    public function eliminarItem(int $cartId, ?int $userId, ?string $sessionId): bool {
        $condicion = $this->condicionPropietario($userId, $sessionId);
        $res = $this->db->query("DELETE FROM carrito_compras WHERE id = " . (int) $cartId . " AND {$condicion}");
        return $res !== false && mysqli_affected_rows($this->db->getConnection()) > 0;
    }

    public function vaciar(?int $userId = null, ?string $sessionId = null): bool {
        $condicion = $this->condicionPropietario($userId, $sessionId);
        $res = $this->db->query("DELETE FROM carrito_compras WHERE {$condicion}");
        return $res !== false;
    }
}
