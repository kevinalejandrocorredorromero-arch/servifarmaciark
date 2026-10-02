-- Migracion 001: soporte para Firebase Authentication
-- Permite usuarios sin contrasena (Google, GitHub) y los vincula por su UID de Firebase.
-- Idempotente: se puede ejecutar varias veces sin error.

ALTER TABLE usuarios
  MODIFY COLUMN IF EXISTS contrasena VARCHAR(255) NULL;

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS firebase_uid VARCHAR(128) NULL,
  ADD COLUMN IF NOT EXISTS proveedor    VARCHAR(20)  NOT NULL DEFAULT 'password',
  ADD COLUMN IF NOT EXISTS foto_url     VARCHAR(500) NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uk_usuarios_firebase_uid
  ON usuarios (firebase_uid);
