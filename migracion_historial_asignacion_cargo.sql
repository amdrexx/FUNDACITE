-- ============================================================================
-- MIGRACIÓN: HISTORIAL DE ASIGNACIÓN, GACETA Y ELIMINACIÓN DE GACETA EN CONTRATO
-- Base de datos: bd_rrhh_fundacite_yaracuy
-- Ejecutar una sola vez, después de hacer un respaldo de la base de datos.
-- ============================================================================

CREATE TABLE IF NOT EXISTS ASIGNACION_CARGO (
    id_asignacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_trabajador INT UNSIGNED NOT NULL,
    id_cargo INT UNSIGNED NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    id_usuario_asignador INT UNSIGNED NULL,
    id_usuario_desvinculador INT UNSIGNED NULL,
    estado ENUM('ACTIVA', 'FINALIZADA') NOT NULL DEFAULT 'ACTIVA',
    gaceta VARCHAR(150) NULL,
    PRIMARY KEY (id_asignacion),
    INDEX idx_asignacion_trabajador (id_trabajador, estado),
    INDEX idx_asignacion_cargo (id_cargo, estado),
    INDEX idx_asignacion_fecha (fecha_asignacion),
    CONSTRAINT fk_asignacion_trabajador FOREIGN KEY (id_trabajador)
        REFERENCES TRABAJADOR(id_trabajador) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asignacion_cargo FOREIGN KEY (id_cargo)
        REFERENCES CARGO(id_cargo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asignacion_usuario FOREIGN KEY (id_usuario_asignador)
        REFERENCES USUARIO(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_asignacion_usuario_desvinculador FOREIGN KEY (id_usuario_desvinculador)
        REFERENCES USUARIO(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_asignacion_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_asignacion),
    CONSTRAINT chk_asignacion_estado CHECK (
        (estado = 'ACTIVA' AND fecha_fin IS NULL) OR
        (estado = 'FINALIZADA' AND fecha_fin IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Incorporar Gaceta en bases de datos donde la tabla ya existía.
-- Las notas antiguas de observacion no se convierten en Gaceta, porque no son
-- una referencia oficial; los registros históricos sin el dato mostrarán "No registrada".
SET @existe_columna_gaceta := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ASIGNACION_CARGO' AND COLUMN_NAME = 'gaceta'
);
SET @sql_columna_gaceta := IF(
    @existe_columna_gaceta = 0,
    'ALTER TABLE ASIGNACION_CARGO ADD COLUMN gaceta VARCHAR(150) NULL AFTER estado',
    'SELECT ''La columna gaceta ya existe'' AS resultado'
);
PREPARE stmt_columna_gaceta FROM @sql_columna_gaceta;
EXECUTE stmt_columna_gaceta;
DEALLOCATE PREPARE stmt_columna_gaceta;

SET @existe_columna_observacion := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ASIGNACION_CARGO' AND COLUMN_NAME = 'observacion'
);
SET @sql_eliminar_observacion := IF(
    @existe_columna_observacion > 0,
    'ALTER TABLE ASIGNACION_CARGO DROP COLUMN observacion',
    'SELECT ''La columna observacion ya no existe'' AS resultado'
);
PREPARE stmt_eliminar_observacion FROM @sql_eliminar_observacion;
EXECUTE stmt_eliminar_observacion;
DEALLOCATE PREPARE stmt_eliminar_observacion;

-- Compatibilidad con instalaciones donde ASIGNACION_CARGO ya fue creada antes
-- de incorporar el registro del usuario que realiza la desvinculación.
SET @existe_columna_desvinculador := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ASIGNACION_CARGO'
      AND COLUMN_NAME = 'id_usuario_desvinculador'
);
SET @sql_columna_desvinculador := IF(
    @existe_columna_desvinculador = 0,
    'ALTER TABLE ASIGNACION_CARGO ADD COLUMN id_usuario_desvinculador INT UNSIGNED NULL AFTER id_usuario_asignador',
    'SELECT ''La columna id_usuario_desvinculador ya existe'' AS resultado'
);
PREPARE stmt_columna_desvinculador FROM @sql_columna_desvinculador;
EXECUTE stmt_columna_desvinculador;
DEALLOCATE PREPARE stmt_columna_desvinculador;

SET @existe_fk_desvinculador := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ASIGNACION_CARGO'
      AND CONSTRAINT_NAME = 'fk_asignacion_usuario_desvinculador'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql_fk_desvinculador := IF(
    @existe_fk_desvinculador = 0,
    'ALTER TABLE ASIGNACION_CARGO ADD CONSTRAINT fk_asignacion_usuario_desvinculador FOREIGN KEY (id_usuario_desvinculador) REFERENCES USUARIO(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''La FK de usuario desvinculador ya existe'' AS resultado'
);
PREPARE stmt_fk_desvinculador FROM @sql_fk_desvinculador;
EXECUTE stmt_fk_desvinculador;
DEALLOCATE PREPARE stmt_fk_desvinculador;

-- Recuperar referencias históricas de cargos que aparezcan en contratos anteriores.
-- La fecha contractual es una aproximación de la fecha de asignación; el sistema
-- no tenía una tabla de historial antes de esta migración.
INSERT INTO ASIGNACION_CARGO
    (id_trabajador, id_cargo, fecha_asignacion, fecha_fin,
     id_usuario_asignador, estado, gaceta)
SELECT
    h.id_trabajador,
    h.id_cargo,
    h.fecha_inicio,
    CASE
        WHEN t.id_cargo = h.id_cargo THEN NULL
        ELSE GREATEST(
            COALESCE(
                (SELECT MIN(ct2.fecha_contrato)
                 FROM CONTRATO ct2
                 WHERE ct2.id_trabajador = h.id_trabajador
                   AND ct2.id_cargo <> h.id_cargo
                   AND ct2.fecha_contrato > h.fecha_inicio),
                NOW()
            ),
            h.fecha_inicio
        )
    END,
    NULL,
    CASE WHEN t.id_cargo = h.id_cargo THEN 'ACTIVA' ELSE 'FINALIZADA' END,
    NULL
FROM (
    SELECT id_trabajador, id_cargo, MIN(fecha_contrato) AS fecha_inicio
    FROM CONTRATO
    GROUP BY id_trabajador, id_cargo
) h
INNER JOIN TRABAJADOR t ON t.id_trabajador = h.id_trabajador
WHERE NOT EXISTS (
    SELECT 1 FROM ASIGNACION_CARGO a
    WHERE a.id_trabajador = h.id_trabajador
      AND a.id_cargo = h.id_cargo
);

-- Cargar cargos actuales sin contrato coincidente con la fecha real disponible.
-- Cuando el sistema anterior no guardó una fecha, se registra la fecha de migración.
INSERT INTO ASIGNACION_CARGO
    (id_trabajador, id_cargo, fecha_asignacion, id_usuario_asignador, estado, gaceta)
SELECT
    t.id_trabajador,
    t.id_cargo,
    NOW(),
    NULL,
    'ACTIVA',
    NULL
FROM TRABAJADOR t
WHERE t.id_cargo IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM ASIGNACION_CARGO a
      WHERE a.id_trabajador = t.id_trabajador AND a.id_cargo = t.id_cargo
  );

-- Eliminar Gaceta de CONTRATO solo si todavía existe la columna.
SET @existe_gaceta := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'CONTRATO'
      AND COLUMN_NAME = 'gaceta_designacion_presidente'
);
SET @sql_eliminar_gaceta := IF(
    @existe_gaceta > 0,
    'ALTER TABLE CONTRATO DROP COLUMN gaceta_designacion_presidente',
    'SELECT ''La columna gaceta_designacion_presidente ya no existe'' AS resultado'
);
PREPARE stmt_eliminar_gaceta FROM @sql_eliminar_gaceta;
EXECUTE stmt_eliminar_gaceta;
DEALLOCATE PREPARE stmt_eliminar_gaceta;

-- Verificación posterior.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME = 'CONTRATO' AND COLUMN_NAME = 'gaceta_designacion_presidente')
       OR (TABLE_NAME = 'ASIGNACION_CARGO'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;
