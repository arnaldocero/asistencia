-- Módulo PQR (peticiones, quejas, reclamos y sugerencias).
-- Recepción las registra; el administrador cambia el estado y responde.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pqr (
    id_pqr                INT(11) NOT NULL AUTO_INCREMENT,
    tipo                  ENUM('peticion','queja','reclamo','sugerencia') NOT NULL,
    nombre_solicitante    VARCHAR(150) NOT NULL,
    telefono              VARCHAR(30) NULL,
    id_nino               INT(11) NULL,
    descripcion           TEXT NOT NULL,
    estado                ENUM('pendiente','en_proceso','resuelta') NOT NULL DEFAULT 'pendiente',
    respuesta             TEXT NULL,
    id_usuario_registro   INT(11) NOT NULL,
    fecha_registro        DATETIME NOT NULL,
    id_usuario_actualiza  INT(11) NULL,
    fecha_actualizacion   DATETIME NULL,
    PRIMARY KEY (id_pqr),
    KEY idx_pqr_estado (estado),
    KEY idx_pqr_usuario (id_usuario_registro),
    CONSTRAINT fk_pqr_nino FOREIGN KEY (id_nino) REFERENCES test_ninos (id_nino),
    CONSTRAINT fk_pqr_usuario_registro FOREIGN KEY (id_usuario_registro) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_pqr_usuario_actualiza FOREIGN KEY (id_usuario_actualiza) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
