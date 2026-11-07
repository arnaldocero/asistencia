-- ============================================================================
-- Casa Kids - Sistema de Asistencia: estructura de la base de datos
-- Generado el 2026-11-05 desde la copia local (MariaDB 10.4).
--
-- Contiene: estructura de las 7 tablas en uso + datos de catálogo
-- (tbl_areas, consecutivo, diagnosticos_neurodivergencia).
-- NO contiene datos personales (niños, asistencias, usuarios).
--
-- Uso: crear una base vacía e importar este archivo (phpMyAdmin > Importar).
-- Después crear el primer administrador (ver el final del archivo).
--
-- Las tablas antiguas `ninos` y `area` ya no se usan y no se incluyen.
-- Cambios posteriores de estructura: sql_file/migraciones/
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_areas` (
  `id_area` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_area` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `edad_min` tinyint(3) unsigned DEFAULT NULL,
  `edad_max` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consecutivo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_area` int(11) NOT NULL,
  `consecutivo` int(11) NOT NULL,
  `fecha_consecutivo` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_area` (`id_area`),
  CONSTRAINT `consecutivo_ibfk_1` FOREIGN KEY (`id_area`) REFERENCES `tbl_areas` (`id_area`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diagnosticos_neurodivergencia` (
  `id_diagnostico` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_diagnostico`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_ninos` (
  `id_nino` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `apellido` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `nombre_padre` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `tiene_diagnostico` enum('0','1') CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT '0 = No tiene y 1 = a tiene ',
  `telefono` varchar(15) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  `codigo_qr` varchar(250) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT NULL,
  `idarea` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_baja` datetime DEFAULT NULL,
  PRIMARY KEY (`id_nino`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nino_diagnostico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_nino` int(11) NOT NULL,
  `id_diagnostico` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_nino` (`id_nino`,`id_diagnostico`),
  KEY `id_diagnostico` (`id_diagnostico`),
  CONSTRAINT `nino_diagnostico_ibfk_1` FOREIGN KEY (`id_nino`) REFERENCES `test_ninos` (`id_nino`) ON DELETE CASCADE,
  CONSTRAINT `nino_diagnostico_ibfk_2` FOREIGN KEY (`id_diagnostico`) REFERENCES `diagnosticos_neurodivergencia` (`id_diagnostico`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_nino` int(11) DEFAULT NULL,
  `id_area` int(11) DEFAULT NULL,
  `consecutivo` int(11) DEFAULT NULL,
  `fecha_asistencia` date NOT NULL,
  `hora_entrada` time NOT NULL,
  `hora_salida` time DEFAULT NULL,
  `estado` enum('0','1') DEFAULT NULL,
  PRIMARY KEY (`id_asistencia`),
  UNIQUE KEY `unique_nino_fecha` (`id_nino`,`fecha_asistencia`),
  KEY `fk_id_areas` (`id_area`),
  CONSTRAINT `fk_id_areas` FOREIGN KEY (`id_area`) REFERENCES `tbl_areas` (`id_area`),
  CONSTRAINT `fk_id_ninos` FOREIGN KEY (`id_nino`) REFERENCES `test_ninos` (`id_nino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','usuario') DEFAULT 'usuario',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Datos de catálogo
INSERT INTO `tbl_areas` VALUES (1,'Área de 3 a 4 años','Área destinada para niños de 3 a 4 años',2,4);
INSERT INTO `tbl_areas` VALUES (2,'Área de 5 a 6 años','Área destinada para niños de 5 a 6 años',5,6);
INSERT INTO `tbl_areas` VALUES (3,'Área de 7 a 8 años','Área destinada para niños de 7 a 8 años',7,8);
INSERT INTO `tbl_areas` VALUES (4,'Área de 9 a 11 años','Área destinada para niños de 9 a 11 años',9,NULL);
INSERT INTO `consecutivo` VALUES (1,1,300,'2026-10-19');
INSERT INTO `consecutivo` VALUES (2,2,500,'2026-10-19');
INSERT INTO `consecutivo` VALUES (3,3,700,'2026-10-19');
INSERT INTO `consecutivo` VALUES (4,4,900,'2026-10-19');
INSERT INTO `diagnosticos_neurodivergencia` VALUES (1,'Sensibilidad al ruido','Reacciona negativamente a ruidos fuertes o constantes',1);
INSERT INTO `diagnosticos_neurodivergencia` VALUES (2,'Evita contacto físico','No tolera abrazos o contacto corporal',1);
INSERT INTO `diagnosticos_neurodivergencia` VALUES (3,'Hipersensibilidad táctil','Molestia ante ciertas texturas',1);
INSERT INTO `diagnosticos_neurodivergencia` VALUES (4,'Dificultad con multitudes','Se altera en espacios con muchas personas',1);
INSERT INTO `diagnosticos_neurodivergencia` VALUES (5,'Necesidad de rutina','Se altera con cambios inesperados',1);
INSERT INTO `diagnosticos_neurodivergencia` VALUES (6,'Hipersensibilidad visual','Molestia ante luces intensas',1);
INSERT INTO `diagnosticos_neurodivergencia` VALUES (7,'Conducta repetitiva','Realiza movimientos repetitivos para autorregularse',1);

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Primer administrador: generar el hash de la contraseña con
--   php -r "echo password_hash('LA_CONTRASEÑA', PASSWORD_DEFAULT);"
-- y ejecutar:
--   INSERT INTO usuarios (nombre_usuario, email, contrasena, rol)
--   VALUES ('admin', 'correo@ejemplo.com', '<hash generado>', 'admin');
-- Los demás usuarios se crean desde el menú "Registrar usuario".
-- ----------------------------------------------------------------------------
