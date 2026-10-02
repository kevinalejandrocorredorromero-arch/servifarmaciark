-- Ejecutar una vez sobre la base de producción.
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS usuario_id INT NULL AFTER info_entrega;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS vendedor_id INT NULL;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS estado VARCHAR(30) NOT NULL DEFAULT 'pendiente';
CREATE INDEX IF NOT EXISTS idx_pedidos_usuario ON pedidos (usuario_id);
