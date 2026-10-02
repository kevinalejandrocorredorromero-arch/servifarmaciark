-- Migracion 003: verificacion por correo electronico
-- Agrega a la tabla usuarios los campos para verificar el email del cliente
-- tras registrarse (mismo estilo idempotente de 001 y 002).

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS email_verified      TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS email_token         VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS email_token_expira  DATETIME NULL;

CREATE INDEX IF NOT EXISTS idx_usuarios_email_token ON usuarios (email_token);

-- Los usuarios ya registrados antes de esta migración tienen correos existentes
-- y legítimos: se marcan como verificados para no bloquear su acceso. Solo las
-- cuentas nuevas (creadas con contraseña desde el formulario) deberán verificar.
UPDATE usuarios SET email_verified = 1 WHERE email_verified = 0;