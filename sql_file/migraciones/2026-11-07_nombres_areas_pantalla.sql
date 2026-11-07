-- Nombres de las áreas que se muestran en pantalla (buscador, reportes, gráficas).
-- Solo cambia el texto: los rangos reales para asignar el área siguen en
-- edad_min / edad_max (área 1 desde 2 años, área 4 sin límite superior).
SET NAMES utf8mb4;

UPDATE tbl_areas SET nombre_area = 'Área de 3 a 4 años',
    descripcion = 'Área destinada para niños de 3 a 4 años'
WHERE id_area = 1;

UPDATE tbl_areas SET nombre_area = 'Área de 9 a 11 años',
    descripcion = 'Área destinada para niños de 9 a 11 años'
WHERE id_area = 4;
