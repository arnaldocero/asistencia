-- Define los rangos de edad de cada área en tbl_areas (una sola fuente para todo el sistema).
-- edad_max NULL = sin límite superior.
-- Rangos: área 1 de 2 a 4 años, área 2 de 5 a 6, área 3 de 7 a 8, área 4 de 9 en adelante.
SET NAMES utf8mb4;

ALTER TABLE tbl_areas
    ADD COLUMN IF NOT EXISTS edad_min TINYINT UNSIGNED NULL AFTER descripcion,
    ADD COLUMN IF NOT EXISTS edad_max TINYINT UNSIGNED NULL AFTER edad_min;

UPDATE tbl_areas SET edad_min = 2, edad_max = 4,
    nombre_area = 'Área de 2 a 4 años', descripcion = 'Área destinada para niños de 2 a 4 años'
WHERE id_area = 1;
UPDATE tbl_areas SET edad_min = 5, edad_max = 6 WHERE id_area = 2;
UPDATE tbl_areas SET edad_min = 7, edad_max = 8 WHERE id_area = 3;
UPDATE tbl_areas SET edad_min = 9, edad_max = NULL,
    nombre_area = 'Área de 9 años en adelante', descripcion = 'Área destinada para niños de 9 años en adelante'
WHERE id_area = 4;
