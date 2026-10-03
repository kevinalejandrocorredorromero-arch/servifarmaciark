-- Migracion 004: descuentos de productos
-- Ejecutar manualmente en la base local o de produccion despues de un respaldo.
-- No renombra columnas existentes; las nuevas columnas usan nombres espanoles.
--
-- Idempotente y compatible con MariaDB (XAMPP) y MySQL 8 (Aiven/produccion):
-- no usa "ADD COLUMN IF NOT EXISTS" porque esa sintaxis solo existe en MariaDB.
-- Se comprueba information_schema antes de agregar cada columna.

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'productos'
     AND COLUMN_NAME = 'precio_anterior') = 0,
  'ALTER TABLE productos ADD COLUMN precio_anterior DECIMAL(10,2) NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'productos'
     AND COLUMN_NAME = 'descuento_porcentaje') = 0,
  'ALTER TABLE productos ADD COLUMN descuento_porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Normaliza cualquier valor previo fuera del rango permitido.
UPDATE productos
SET descuento_porcentaje = CASE
    WHEN descuento_porcentaje < 0 THEN 0
    WHEN descuento_porcentaje > 100 THEN 100
    ELSE descuento_porcentaje
END;
