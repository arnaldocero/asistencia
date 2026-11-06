-- Convierte codigo_qr de ruta absoluta del servidor a ruta relativa al proyecto.
-- Antes:   /home/medical6/public_html/asistenciacasakids/dist/img/codigosqr/qr_nino_5.png
-- Después: dist/img/codigosqr/qr_nino_5.png
-- Se puede ejecutar más de una vez sin problema (solo toca rutas absolutas).

UPDATE test_ninos
SET codigo_qr = CONCAT('dist/img/codigosqr/', SUBSTRING_INDEX(codigo_qr, '/', -1))
WHERE codigo_qr LIKE '/%codigosqr/%';
