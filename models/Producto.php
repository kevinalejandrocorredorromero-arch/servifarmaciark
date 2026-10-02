<?php

class ProductoModelo {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerTodos(): array {
        $res = $this->db->query("SELECT * FROM productos ORDER BY categoria, nombre");
        if (!$res) return [];

        $productos = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $productos[] = $this->mapearFila($row);
        }
        return $productos;
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        return $row ? $this->mapearFila($row) : null;
    }

    public function crear(array $data): ?int {
        $nombre = $this->db->escape($data['name'] ?? $data['nombre'] ?? '');
        $precio = (float) ($data['price'] ?? $data['precio'] ?? 0);
        $stock = (int) ($data['stock'] ?? 0);
        $categoria = $this->db->escape($data['category'] ?? $data['categoria'] ?? 'general');
        $imagen = $this->db->escape($data['img'] ?? $data['imagen'] ?? '');
        $codigoBarras = $this->db->escape($data['barcode'] ?? $data['codigo_barras'] ?? '');
        $lote = $this->db->escape($data['batch'] ?? $data['lote'] ?? '');
        $ingresoRaw = $data['ingressDate'] ?? $data['fecha_ingreso'] ?? null;
        $ingreso = !empty($ingresoRaw) ? "'" . $this->db->escape($ingresoRaw) . "'" : "NULL";
        $vencimientoRaw = $data['expiryDate'] ?? $data['fecha_vencimiento'] ?? null;
        $vencimiento = !empty($vencimientoRaw) ? "'" . $this->db->escape($vencimientoRaw) . "'" : "NULL";
        $cantidadLote = (int) ($data['batchQuantity'] ?? $data['cantidad_lote'] ?? 0);
        $descripcion = $this->db->escape($data['description'] ?? $data['descripcion'] ?? '');
        $umbral = (int) ($data['stockThreshold'] ?? $data['umbral_stock'] ?? 10);

        // Venta fraccionada (acepta camelCase del frontend y snake_case)
        $esFraccionable = !empty($data['is_fractionable'] ?? $data['es_fraccionable'] ?? $data['isFractionable'] ?? 0) ? 1 : 0;
        $unidadesPorCaja = isset($data['units_per_box']) || isset($data['unitsPerBox'])
            ? (int) ($data['units_per_box'] ?? $data['unidades_por_caja'] ?? $data['unitsPerBox'] ?? 0)
            : null;
        $precioCaja = isset($data['box_price']) || isset($data['boxPrice'])
            ? (float) ($data['box_price'] ?? $data['precio_caja'] ?? $data['boxPrice'] ?? 0)
            : null;
        $precioUnidad = isset($data['unit_price']) || isset($data['unitPrice'])
            ? (float) ($data['unit_price'] ?? $data['precio_unidad'] ?? $data['unitPrice'] ?? 0)
            : null;
        // Inventario unificado en la unidad mínima (tableta para fraccionables, unidad normal para el resto)
        $stockTotalUnidades = (int) ($data['stock_total_units'] ?? $data['stock_total_unidades'] ?? $data['stockTotalUnits'] ?? $stock);

        $sql = "INSERT INTO productos (nombre, precio, stock, categoria, imagen, codigo_barras, lote, fecha_ingreso, fecha_vencimiento, cantidad_lote, descripcion, umbral_stock, es_fraccionable, unidades_por_caja, precio_caja, precio_unidad, stock_total_unidades, creado_en, actualizado_en)
                VALUES ('{$nombre}', {$precio}, {$stock}, '{$categoria}', '{$imagen}', '{$codigoBarras}', '{$lote}', {$ingreso}, {$vencimiento}, {$cantidadLote}, '{$descripcion}', {$umbral}, {$esFraccionable}, " . ($unidadesPorCaja === null ? "NULL" : $unidadesPorCaja) . ", " . ($precioCaja === null ? "NULL" : $precioCaja) . ", " . ($precioUnidad === null ? "NULL" : $precioUnidad) . ", {$stockTotalUnidades}, NOW(), NOW())";

        if ($this->db->query($sql)) {
            return $this->db->insertId();
        }
        return null;
    }

    public function actualizar(int $id, array $data): bool {
        $sets = [];
        $mapa = [
            'name' => ['nombre', 'string'], 'nombre' => ['nombre', 'string'],
            'price' => ['precio', 'float'], 'precio' => ['precio', 'float'],
            'stock' => ['stock', 'int'],
            'category' => ['categoria', 'string'], 'categoria' => ['categoria', 'string'],
            'img' => ['imagen', 'string'], 'imagen' => ['imagen', 'string'],
            'description' => ['descripcion', 'string'], 'descripcion' => ['descripcion', 'string'],
            'barcode' => ['codigo_barras', 'string'], 'codigo_barras' => ['codigo_barras', 'string'],
            'batch' => ['lote', 'string'], 'lote' => ['lote', 'string'],
            'ingressDate' => ['fecha_ingreso', 'fecha_ingreso'], 'fecha_ingreso' => ['fecha_ingreso', 'fecha_ingreso'],
            'expiryDate' => ['fecha_vencimiento', 'fecha_vencimiento'], 'fecha_vencimiento' => ['fecha_vencimiento', 'fecha_vencimiento'],
            'batchQuantity' => ['cantidad_lote', 'cantidad_lote'], 'cantidad_lote' => ['cantidad_lote', 'cantidad_lote'],
            'stockThreshold' => ['umbral_stock', 'umbral_stock'], 'umbral_stock' => ['umbral_stock', 'umbral_stock'],
            'is_fractionable' => ['es_fraccionable', 'int'], 'es_fraccionable' => ['es_fraccionable', 'int'], 'isFractionable' => ['es_fraccionable', 'int'],
            'units_per_box' => ['unidades_por_caja', 'int'], 'unidades_por_caja' => ['unidades_por_caja', 'int'], 'unitsPerBox' => ['unidades_por_caja', 'int'],
            'box_price' => ['precio_caja', 'float'], 'precio_caja' => ['precio_caja', 'float'], 'boxPrice' => ['precio_caja', 'float'],
            'unit_price' => ['precio_unidad', 'float'], 'precio_unidad' => ['precio_unidad', 'float'], 'unitPrice' => ['precio_unidad', 'float'],
            'stock_total_units' => ['stock_total_unidades', 'int'], 'stock_total_unidades' => ['stock_total_unidades', 'int'], 'stockTotalUnits' => ['stock_total_unidades', 'int'],
        ];

        // Determinar si el producto es fraccionable (del payload o de la BD) para no des-sincronizar 'stock'
        $esFraccPayload = !empty($data['is_fractionable'] ?? $data['es_fraccionable'] ?? $data['isFractionable'] ?? null);
        $prodActual = $this->obtenerPorId($id);
        $esFracc = $esFraccPayload || (!empty($prodActual['is_fractionable']) && $esFraccPayload !== false);

        foreach ($mapa as $key => [$col, $tipo]) {
            if (!isset($data[$key])) continue;
            $val = $data[$key];

            if ($tipo === 'float') {
                $sets[] = "`{$col}` = " . ((float) $val);
            } elseif ($tipo === 'fecha_ingreso' || $tipo === 'fecha_vencimiento') {
                // Las fechas deben ir como string entre comillas, no como (float)
                $sets[] = "`{$col}` = '" . $this->db->escape($val) . "'";
            } elseif ($tipo === 'int' || $tipo === 'cantidad_lote' || $tipo === 'umbral_stock' || $tipo === 'es_fraccionable' || $tipo === 'unidades_por_caja' || $tipo === 'stock_total_unidades') {
                $sets[] = "`{$col}` = " . ((int) $val);
            } else {
                $sets[] = "`{$col}` = '" . $this->db->escape($val) . "'";
            }
            // Mantener 'stock' sincronizado SOLO para productos NO fraccionables
            // (para fraccionables, 'stock' representa cajas y se gestiona aparte)
            if ($col === 'stock_total_unidades' && !$esFracc) {
                $sets[] = "`stock` = " . ((int) $val);
            }
        }

        if (empty($sets)) return false;

        $sql = "UPDATE productos SET " . implode(', ', $sets) . ", actualizado_en = NOW() WHERE id = " . (int) $id;
        return $this->db->query($sql) !== false;
    }

    public function eliminar(int $id): bool {
        return $this->db->query("DELETE FROM productos WHERE id = " . (int) $id) !== false;
    }

    public function actualizarStock(int $id, int $cambio): bool {
        return $this->db->query("UPDATE productos SET stock = stock + ({$cambio}), actualizado_en = NOW() WHERE id = " . (int) $id) !== false;
    }

    public function buscarPorCodigoBarras(string $codigoBarras): ?array {
        $codigoBarras = $this->db->escape($codigoBarras);
        $res = $this->db->query("SELECT * FROM productos WHERE codigo_barras = '{$codigoBarras}' LIMIT 1");
        if (!$res) return null;
        $row = mysqli_fetch_assoc($res);
        return $row ? $this->mapearFila($row) : null;
    }

    private function mapearFila(array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $row['nombre'] ?? '',
            'category' => $row['categoria'] ?? 'general',
            'price' => (float) ($row['precio'] ?? 0),
            'stock' => (int) ($row['stock'] ?? 0),
            'img' => $row['imagen'] ?? '',
            'description' => $row['descripcion'] ?? '',
            'barcode' => $row['codigo_barras'] ?? '',
            'batch' => $row['lote'] ?? '',
            'ingress_date' => $row['fecha_ingreso'] ?? '',
            'expiry_date' => $row['fecha_vencimiento'] ?? '',
            'batch_quantity' => (int) ($row['cantidad_lote'] ?? 0),
            'is_fractionable' => (int) ($row['es_fraccionable'] ?? 0) === 1,
            'units_per_box' => (int) ($row['unidades_por_caja'] ?? 0),
            'box_price' => $row['precio_caja'] !== null ? (float) $row['precio_caja'] : null,
            'unit_price' => $row['precio_unidad'] !== null ? (float) $row['precio_unidad'] : null,
            'stock_total_units' => (int) ($row['stock_total_unidades'] ?? (int) ($row['stock'] ?? 0)),
        ];
    }

    public function obtenerBajoStock(int $umbral = 5): array {
        $umbral = (int) $umbral;
        $res = $this->db->query("SELECT * FROM productos WHERE stock < {$umbral} ORDER BY stock ASC");
        $out = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $out[] = $this->mapearFila($row);
            }
        }
        return $out;
    }

    public function obtenerPorVencer(int $dias = 30): array {
        $dias = (int) $dias;
        $hoy = date('Y-m-d');
        $limite = date('Y-m-d', strtotime("+{$dias} days"));
        // fecha_vencimiento IS NOT NULL AND >= hoy AND <= limite
        $res = $this->db->query("SELECT * FROM productos WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento <> '' AND fecha_vencimiento >= '{$hoy}' AND fecha_vencimiento <= '{$limite}' ORDER BY fecha_vencimiento ASC");
        $out = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $out[] = $this->mapearFila($row);
            }
        }
        return $out;
    }

    public function obtenerAlertas(int $umbral = 5, int $dias = 30): array {
        return [
            'bajo_stock' => $this->obtenerBajoStock($umbral),
            'por_vencer' => $this->obtenerPorVencer($dias),
        ];
    }
}
