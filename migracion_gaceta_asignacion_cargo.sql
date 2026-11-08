-- ============================================================================
-- MIGRACIÓN: SUSTITUIR observacion POR gaceta EN ASIGNACION_CARGO
-- Base de datos: bd_rrhh_fundacite_yaracuy
-- Ejecutar una sola vez (es idempotente) después de respaldar la BD.
-- ============================================================================

-- Añadir la columna que almacenará la Gaceta Oficial usada al asignar el cargo.
SET @existe_columna_gaceta := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ASIGNACION_CARGO'
      AND COLUMN_NAME = 'gaceta'
);
SET @sql_agregar_gaceta := IF(
    @existe_columna_gaceta = 0,
    'ALTER TABLE ASIGNACION_CARGO ADD COLUMN gaceta VARCHAR(150) NULL AFTER estado',
    'SELECT ''La columna gaceta ya existe'' AS resultado'
);
PREPARE stmt_agregar_gaceta FROM @sql_agregar_gaceta;
EXECUTE stmt_agregar_gaceta;
DEALLOCATE PREPARE stmt_agregar_gaceta;

-- Eliminar la columna observacion anterior. Sus notas internas no se copian
-- a gaceta porque no representan una referencia de Gaceta Oficial.
SET @existe_columna_observacion := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ASIGNACION_CARGO'
      AND COLUMN_NAME = 'observacion'
);
SET @sql_eliminar_observacion := IF(
    @existe_columna_observacion > 0,
    'ALTER TABLE ASIGNACION_CARGO DROP COLUMN observacion',
    'SELECT ''La columna observacion ya no existe'' AS resultado'
);
PREPARE stmt_eliminar_observacion FROM @sql_eliminar_observacion;
EXECUTE stmt_eliminar_observacion;
DEALLOCATE PREPARE stmt_eliminar_observacion;

-- Confirmar la estructura resultante.
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ASIGNACION_CARGO'
  AND COLUMN_NAME IN ('gaceta', 'observacion');
