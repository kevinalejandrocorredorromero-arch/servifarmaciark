<?php

class PedidoModelo {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerTodos(?int $usuarioId = null): array {
        $sql = "SELECT * FROM pedidos";

        if ($usuarioId > 0) {
            $usuarioModelo = new UsuarioModelo();
            $usuario = $usuarioModelo->obtenerPorId($usuarioId);
            if ($usuario && $usuario['role'] === 'seller') {
                $sql .= " WHERE vendedor_id = " . (int) $usuarioId;
            } elseif ($usuario) {
                $sql .= " WHERE usuario_id = " . (int) $usuarioId;
            }
        }

        $sql .= " ORDER BY fecha_pedido DESC";
        $res = $this->db->query($sql);
        if (!$res) return [];

        $pedidos = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $pedidos[] = $this->mapearFila($row);
        }
        return $pedidos;
    }

    public function obtenerPorId(int $id, ?int $usuarioId = null, ?string $rol = null): ?array {
        $condicion = 'id = ' . (int) $id;
        if ($rol === 'admin') {
            // El administrador puede consultar cualquier pedido.
        } elseif ($rol === 'seller' && $usuarioId > 0) {
            $condicion .= ' AND vendedor_id = ' . (int) $usuarioId;
        } elseif ($usuarioId > 0) {
            $condicion .= ' AND usuario_id = ' . (int) $usuarioId;
        } else {
            return null;
        }
        $res = $this->db->query("SELECT * FROM pedidos WHERE {$condicion} LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ? $this->mapearFila($row) : null;
    }

    public function crear(array $data, ?array &$resumen = null): ?string {
        $items = $data['items'] ?? [];
        $metodoPago = $this->db->escape($data['paymentMethod'] ?? $data['metodo_pago'] ?? 'cash');
        $infoEntrega = $data['deliveryInfo'] ?? $data['info_entrega'] ?? [];
        $usuarioId = Auth::idUsuario();
        $sesionId = !empty($data['session_id']) ? $this->db->escape($data['session_id']) : null;
        if (empty($items)) return null;

        if (!$this->db->beginTransaction()) return null;
        $numeroPedido = 'RK' . date('YmdHis') . mt_rand(100, 999);
        $infoEntregaJson = $this->db->escape(json_encode($infoEntrega, JSON_UNESCAPED_UNICODE));
        $usuarioSql = $usuarioId > 0 ? ', usuario_id' : '';
        $usuarioValor = $usuarioId > 0 ? ', ' . (int) $usuarioId : '';
        $sql = "INSERT INTO pedidos (numero_pedido, total, metodo_pago, info_entrega{$usuarioSql}, estado, fecha_pedido)
                VALUES ('{$numeroPedido}', 0, '{$metodoPago}', '{$infoEntregaJson}'{$usuarioValor}, 'pendiente', NOW())";
        if (!$this->db->query($sql)) {
            $this->db->rollback();
            return null;
        }
        $pedidoId = $this->db->insertId();
        $totalCalculado = 0.0;
        $itemsNotificacion = [];
        $transicionesStock = [];

        foreach ($items as $item) {
            $productoId = (int) ($item['id'] ?? $item['product_id'] ?? 0);
            $cantidad = (int) ($item['quantity'] ?? $item['cantidad'] ?? 0);
            $tipoVenta = strtolower(trim((string) ($item['tipo_venta'] ?? $item['tipoVenta'] ?? 'unidad')));
            if ($productoId <= 0 || $cantidad <= 0 || !in_array($tipoVenta, ['caja', 'unidad'], true)) {
                $this->db->rollback();
                return null;
            }

            // El bloqueo ocurre dentro de la transacción, antes de calcular y descontar stock.
            $resProd = $this->db->query("SELECT id, nombre, categoria, precio, precio_anterior, descuento_porcentaje, precio_caja, precio_unidad,
                    es_fraccionable, unidades_por_caja, stock_total_unidades, stock
                    FROM productos WHERE id = {$productoId} FOR UPDATE");
            if (!$resProd || !($rowProd = mysqli_fetch_assoc($resProd))) {
                $this->db->rollback();
                return null;
            }

            $esFraccionable = (int) $rowProd['es_fraccionable'] === 1;
            $unidadesPorCaja = max(1, (int) $rowProd['unidades_por_caja']);
            if ($tipoVenta === 'caja' && !$esFraccionable) {
                $this->db->rollback();
                return null;
            }
            $descuento = max(0, min(100, (float) ($rowProd['descuento_porcentaje'] ?? 0)));
            $precioBase = (float) $rowProd['precio'];
            // Los precios por presentación solo aplican si están definidos (> 0):
            // un 0 guardado por error no debe reemplazar al precio base.
            $precioCaja = $rowProd['precio_caja'] !== null ? (float) $rowProd['precio_caja'] : 0.0;
            $precioUnidad = $rowProd['precio_unidad'] !== null ? (float) $rowProd['precio_unidad'] : 0.0;
            if ($tipoVenta === 'caja' && $precioCaja > 0) {
                $precioBase = $precioCaja;
            } elseif ($tipoVenta === 'unidad' && $esFraccionable && $precioUnidad > 0) {
                $precioBase = $precioUnidad;
            }
            $precio = round($precioBase * (1 - $descuento / 100), 2);
            $unidadesADescontar = ($tipoVenta === 'caja') ? $cantidad * $unidadesPorCaja : $cantidad;
            $stockTotalActual = (int) $rowProd['stock_total_unidades'];
            $stockActual = (int) $rowProd['stock'];
            $stockDisponible = ($tipoVenta === 'caja') ? intdiv($stockTotalActual, $unidadesPorCaja) : ($esFraccionable ? $stockTotalActual : $stockActual);
            if ($cantidad > $stockDisponible) {
                $this->db->rollback();
                return null;
            }

            $nombre = $this->db->escape((string) $rowProd['nombre']);
            $categoria = $this->db->escape((string) $rowProd['categoria']);
            $lineaTotal = $precio * $cantidad;
            $totalCalculado += $lineaTotal;
            $itemsNotificacion[] = [
                'nombre' => (string) $rowProd['nombre'],
                'cantidad' => $cantidad,
                'total' => round($lineaTotal, 2),
            ];
            $sqlItem = "INSERT INTO detalles_pedido (pedido_id, producto_id, nombre, categoria, precio, cantidad, total, tipo_venta, unidades_descontadas)
                        VALUES ({$pedidoId}, {$productoId}, '{$nombre}', '{$categoria}', {$precio}, {$cantidad}, {$lineaTotal}, '{$tipoVenta}', {$unidadesADescontar})";
            if (!$this->db->query($sqlItem)) {
                $this->db->rollback();
                return null;
            }

            $nuevoStockTotal = $stockTotalActual - $unidadesADescontar;
            $nuevoStock = ($esFraccionable && $unidadesPorCaja > 0)
                ? intdiv($nuevoStockTotal, $unidadesPorCaja)
                : $stockActual - $cantidad;
            $transicionesStock[] = [
                'id' => $productoId,
                'stock_antes' => $stockTotalActual,
                'stock_despues' => $nuevoStockTotal,
            ];
            if (!$this->db->query("UPDATE productos SET stock_total_unidades = {$nuevoStockTotal}, stock = {$nuevoStock} WHERE id = {$productoId}")) {
                $this->db->rollback();
                return null;
            }
        }

        if (!$this->db->query("UPDATE pedidos SET total = {$totalCalculado} WHERE id = {$pedidoId}")) {
            $this->db->rollback();
            return null;
        }
        if ($usuarioId > 0) {
            $okCarrito = $this->db->query("DELETE FROM carrito_compras WHERE usuario_id = " . (int) $usuarioId);
        } elseif ($sesionId) {
            $okCarrito = $this->db->query("DELETE FROM carrito_compras WHERE sesion_id = '{$sesionId}'");
        } else {
            $okCarrito = true;
        }
        if (!$okCarrito || !$this->db->commit()) {
            $this->db->rollback();
            return null;
        }
        $resumen = ['total' => round($totalCalculado, 2), 'items' => $itemsNotificacion, 'stock_transiciones' => $transicionesStock];
        return $numeroPedido;
    }

    public function cancelar(int $id, int $usuarioId, bool $esAdmin): bool {
        $condicion = $esAdmin
            ? "id = " . (int) $id
            : "id = " . (int) $id . " AND usuario_id = " . (int) $usuarioId;
        $res = $this->db->query("UPDATE pedidos SET estado = 'cancelado' WHERE {$condicion} AND estado = 'pendiente'");
        return $res && mysqli_affected_rows($this->db->getConnection()) > 0;
    }

    public function confirmarEntrega(int $id): bool {
        $idEscaped = (int) $id;
        $res = $this->db->query("UPDATE pedidos SET estado = 'entregado' WHERE id = {$idEscaped} AND estado = 'pendiente'");
        return $res !== false && mysqli_affected_rows($this->db->getConnection()) > 0;
    }

    public function eliminar(int $id): bool {
        $this->db->beginTransaction();

        $this->db->query("DELETE FROM detalles_pedido WHERE pedido_id = " . (int) $id);
        $this->db->query("DELETE FROM pedidos WHERE id = " . (int) $id);

        if (mysqli_affected_rows($this->db->getConnection()) === 0) {
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    private function mapearFila(array $row): array {
        $infoEntrega = json_decode($row['info_entrega'] ?? '{}', true);
        $items = $this->obtenerItems((int) $row['id']);

        return [
            'id' => (int) $row['id'],
            'order_number' => $row['numero_pedido'],
            'total' => (float) $row['total'],
            'payment_method' => $row['metodo_pago'],
            'deliveryInfo' => is_array($infoEntrega) ? $infoEntrega : [],
            'status' => $row['estado'] ?? 'pendiente',
            'can_cancel' => ($row['estado'] ?? 'pendiente') === 'pendiente',
            'order_date' => $row['fecha_pedido'],
            'seller_id' => isset($row['vendedor_id']) ? (int) $row['vendedor_id'] : null,
            'user_id' => isset($row['usuario_id']) ? (int) $row['usuario_id'] : null,
            'items' => $items,
        ];
    }

    private function obtenerItems(int $pedidoId): array {
        $res = $this->db->query(
            "SELECT oi.*, COALESCE(NULLIF(oi.nombre, ''), p.nombre) AS nombre_resuelto, COALESCE(NULLIF(oi.categoria, ''), p.categoria) AS categoria_resuelta
             FROM detalles_pedido oi LEFT JOIN productos p ON p.id = oi.producto_id
             WHERE oi.pedido_id = " . (int) $pedidoId
        );

        $items = [];
        if ($res) {
            while ($item = mysqli_fetch_assoc($res)) {
                $items[] = [
                    'id' => (int) $item['id'],
                    'order_id' => (int) $item['pedido_id'],
                    'product_id' => (int) $item['producto_id'],
                    'name' => $item['nombre_resuelto'] ?? $item['nombre'],
                    'category' => $item['categoria_resuelta'] ?? $item['categoria'],
                    'price' => (float) $item['precio'],
                    'quantity' => (int) $item['cantidad'],
                    'total' => (float) $item['total'],
                ];
            }
        }
        return $items;
    }
}
