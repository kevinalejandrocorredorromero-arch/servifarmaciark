-- Migracion 002: relaciones (foreign keys) entre tablas
-- Antes de esta migracion no existia ninguna FK: pedidos.usuario_id/vendedor_id,
-- detalles_pedido.pedido_id/producto_id y carrito_compras.usuario_id/producto_id
-- eran enteros sueltos sin integridad referencial.
--
-- Criterio ON DELETE (elegido para no romper los flujos actuales de la app):
--   * usuarios  -> pedidos.usuario_id / pedidos.vendedor_id : SET NULL (se conserva el historial de ventas)
--   * usuarios  -> carrito_compras.usuario_id               : CASCADE  (el carrito de un usuario borrado ya no sirve)
--   * pedidos   -> detalles_pedido.pedido_id                : CASCADE  (las lineas no existen sin su pedido)
--   * productos -> detalles_pedido.producto_id              : SET NULL (el historial guarda nombre/categoria/precio propios)
--   * productos -> carrito_compras.producto_id              : CASCADE  (un producto borrado sale de los carritos)
--
-- Idempotente: se puede ejecutar varias veces sin error. Se usa un helper con
-- information_schema porque MariaDB 10.4 no soporta "ADD CONSTRAINT IF NOT EXISTS".

-- detalles_pedido.producto_id debe poder quedar NULL cuando se borra un producto
ALTER TABLE detalles_pedido
  MODIFY COLUMN producto_id INT(11) NULL;

DELIMITER //
DROP PROCEDURE IF EXISTS add_fk_if_missing//
CREATE PROCEDURE add_fk_if_missing(IN nombre_fk VARCHAR(64), IN ddl TEXT)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = nombre_fk) = 0 THEN
    SET @ddl = ddl;
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//
DELIMITER ;

CALL add_fk_if_missing('fk_pedidos_usuario',   'ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL');
CALL add_fk_if_missing('fk_pedidos_vendedor',  'ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE SET NULL');
CALL add_fk_if_missing('fk_detalles_pedido',   'ALTER TABLE detalles_pedido ADD CONSTRAINT fk_detalles_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE');
CALL add_fk_if_missing('fk_detalles_producto', 'ALTER TABLE detalles_pedido ADD CONSTRAINT fk_detalles_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL');
CALL add_fk_if_missing('fk_carrito_usuario',   'ALTER TABLE carrito_compras ADD CONSTRAINT fk_carrito_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE');
CALL add_fk_if_missing('fk_carrito_producto',  'ALTER TABLE carrito_compras ADD CONSTRAINT fk_carrito_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE');

DROP PROCEDURE add_fk_if_missing;
