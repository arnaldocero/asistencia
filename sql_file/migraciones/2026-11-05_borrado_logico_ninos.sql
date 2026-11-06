-- Borrado lógico de niños: en lugar de borrar el niño y su historial de asistencia,
-- se marca activo = 0. Los listados y búsquedas solo muestran niños con activo = 1;
-- los reportes de asistencia conservan el historial completo.
ALTER TABLE test_ninos
    ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS fecha_baja DATETIME NULL DEFAULT NULL;
