-- 005_reparacion_precios_cero.sql
-- Repara los registros dañados por el bug de precios en $0:
-- al guardar un producto no fraccionable desde el panel de administración se
-- escribían precio_caja/precio_unidad = 0.00 (en vez de NULL) y PedidoModelo::crear()
-- los usaba como precio base por no ser NULL, dejando detalles_pedido.precio = 0,
-- pedidos.total = 0 y la notificación de Telegram en $0.
--
-- Aplicación MANUAL (no corre sola en el deploy):
--   mysql -u usuario -p nombre_bd < migrations/005_reparacion_precios_cero.sql
-- Compatible con MySQL 8 (Aiven/producción) y MariaDB (XAMPP local).
-- Idempotente: si ya no hay ceros, ningún UPDATE modifica filas.
--
-- El orden de los pasos importa: el paso 2 suma a pedidos.total el importe de las
-- líneas en $0 ANTES de que el paso 3 deje de marcarlas con precio = 0.

-- Diagnóstico previo (opcional, solo lectura): qué se va a tocar.
-- SELECT id, nombre, precio_caja, precio_unidad FROM productos
--   WHERE precio_caja = 0 OR precio_unidad = 0;
-- SELECT dp.pedido_id, dp.producto_id, dp.nombre, dp.precio, dp.total, p.total AS total_pedido
--   FROM detalles_pedido dp JOIN pedidos p ON p.id = dp.pedido_id
--   WHERE dp.precio = 0;

-- Paso 1: un precio de presentación en 0 equivale a "sin precio definido" -> NULL.
UPDATE productos SET precio_unidad = NULL WHERE precio_unidad = 0;
UPDATE productos SET precio_caja = NULL WHERE precio_caja = 0;

-- Paso 2: sumar al total del pedido el importe correcto de las líneas que quedaron en $0.
-- Misma fórmula que PedidoModelo::crear(): precio base por presentación con descuento.
UPDATE pedidos p
JOIN (
    SELECT dp.pedido_id,
           SUM(ROUND(
               CASE
                   WHEN dp.tipo_venta = 'caja' AND COALESCE(pr.precio_caja, 0) > 0 THEN pr.precio_caja
                   WHEN dp.tipo_venta = 'unidad' AND pr.es_fraccionable = 1 AND COALESCE(pr.precio_unidad, 0) > 0 THEN pr.precio_unidad
                   ELSE pr.precio
               END * (1 - pr.descuento_porcentaje / 100), 2) * dp.cantidad) AS importe_faltante
    FROM detalles_pedido dp
    JOIN productos pr ON pr.id = dp.producto_id
    WHERE dp.precio = 0
    GROUP BY dp.pedido_id
) s ON s.pedido_id = p.id
SET p.total = p.total + s.importe_faltante;

-- Paso 3: recalcular precio y total de las líneas en $0.
-- Nota: las líneas cuyo producto ya fue eliminado (sin fila en productos) no se
-- tocan; el JOIN interno las excluye. Detectarlas con:
--   SELECT dp.* FROM detalles_pedido dp LEFT JOIN productos pr ON pr.id = dp.producto_id
--     WHERE dp.precio = 0 AND pr.id IS NULL;
UPDATE detalles_pedido dp
JOIN productos pr ON pr.id = dp.producto_id
SET dp.precio = ROUND(
        CASE
            WHEN dp.tipo_venta = 'caja' AND COALESCE(pr.precio_caja, 0) > 0 THEN pr.precio_caja
            WHEN dp.tipo_venta = 'unidad' AND pr.es_fraccionable = 1 AND COALESCE(pr.precio_unidad, 0) > 0 THEN pr.precio_unidad
            ELSE pr.precio
        END * (1 - pr.descuento_porcentaje / 100), 2),
    dp.total = ROUND(
        CASE
            WHEN dp.tipo_venta = 'caja' AND COALESCE(pr.precio_caja, 0) > 0 THEN pr.precio_caja
            WHEN dp.tipo_venta = 'unidad' AND pr.es_fraccionable = 1 AND COALESCE(pr.precio_unidad, 0) > 0 THEN pr.precio_unidad
            ELSE pr.precio
        END * (1 - pr.descuento_porcentaje / 100), 2) * dp.cantidad
WHERE dp.precio = 0;

-- Verificación posterior: los tres conteos deben dar 0 (se imprimen al ejecutar).
SELECT COUNT(*) AS productos_con_cero FROM productos WHERE precio_caja = 0 OR precio_unidad = 0;
SELECT COUNT(*) AS lineas_en_cero FROM detalles_pedido WHERE precio = 0;
SELECT COUNT(*) AS pedidos_des_cuadrados FROM pedidos p
  WHERE ABS(p.total - (SELECT COALESCE(SUM(dp.total), 0) FROM detalles_pedido dp WHERE dp.pedido_id = p.id)) > 0.01;
