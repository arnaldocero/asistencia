-- Permite desactivar usuarios (no pueden iniciar sesión) sin borrarlos.
-- En MySQL (no MariaDB) quitar "IF NOT EXISTS".
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1;
