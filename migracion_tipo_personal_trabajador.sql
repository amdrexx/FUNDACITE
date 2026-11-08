-- ============================================================================
-- MIGRACIÓN FUNDACITE: traslado COMPLETO de tipo_personal a TRABAJADOR
--
-- Ejecutar sobre una base de datos existente después de realizar un respaldo.
-- Puede ejecutarse aunque tipo_personal ya exista en TRABAJADOR o ya haya sido
-- retirado de CONTRATO. No modifica status ni elimina trabajadores/contratos.
--
-- Resultado esperado:
--   * TRABAJADOR.tipo_personal: VARCHAR(50) NOT NULL, índice y restricción.
--   * CONTRATO.tipo_personal: ELIMINADO.
--   * CONTRATO.tipo_contrato: SE CONSERVA.
--   * CONSTANCIA_DE_TRABAJO.tipo_personal: SE CONSERVA como dato histórico.
--
-- Si existen varios contratos antiguos, se traslada el valor del contrato con
-- mayor id_contrato. Si el trabajador no tiene contratos, se conserva su
-- valor actual o se utiliza 'Fijo' como valor inicial.
-- ============================================================================

USE bd_rrhh_fundacite_yaracuy;

-- 1. Crear la columna física en TRABAJADOR si todavía no existe.
SET @existe_tipo_personal_trabajador := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'TRABAJADOR'
      AND COLUMN_NAME = 'tipo_personal'
);

SET @sql_migracion := IF(
    @existe_tipo_personal_trabajador = 0,
    'ALTER TABLE TRABAJADOR ADD COLUMN tipo_personal VARCHAR(50) NOT NULL DEFAULT ''Fijo'' AFTER nivel_educacion',
    'SELECT ''TRABAJADOR.tipo_personal ya existe; se conserva la columna.'' AS mensaje'
);
PREPARE sentencia_migracion FROM @sql_migracion;
EXECUTE sentencia_migracion;
DEALLOCATE PREPARE sentencia_migracion;

-- 2. Reorganizar TRABAJADOR por grupos: identificación, datos personales,
--    información complementaria y datos laborales. Género queda después de apellidos.
ALTER TABLE TRABAJADOR
    MODIFY COLUMN id_trabajador INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    MODIFY COLUMN tipo_documento VARCHAR(50) NOT NULL AFTER id_trabajador,
    MODIFY COLUMN cedula VARCHAR(10) NOT NULL AFTER tipo_documento,
    MODIFY COLUMN nombres VARCHAR(100) NOT NULL AFTER cedula,
    MODIFY COLUMN apellidos VARCHAR(100) NOT NULL AFTER nombres,
    MODIFY COLUMN genero VARCHAR(20) NULL AFTER apellidos,
    MODIFY COLUMN fecha_nacimiento DATE NOT NULL AFTER genero,
    MODIFY COLUMN estado_civil VARCHAR(30) NULL AFTER fecha_nacimiento,
    MODIFY COLUMN nacionalidad VARCHAR(50) NULL DEFAULT 'Venezolano(a)' AFTER estado_civil,
    MODIFY COLUMN telefono VARCHAR(20) NULL AFTER nacionalidad,
    MODIFY COLUMN correo VARCHAR(100) NULL AFTER telefono,
    MODIFY COLUMN hijos TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER correo,
    MODIFY COLUMN anos_servicio_administracion_publica SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER hijos,
    MODIFY COLUMN nivel_educacion VARCHAR(100) NULL AFTER anos_servicio_administracion_publica,
    MODIFY COLUMN tipo_personal VARCHAR(50) NOT NULL DEFAULT 'Fijo' AFTER nivel_educacion,
    MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Activo' AFTER tipo_personal,
    MODIFY COLUMN id_dir INT UNSIGNED NULL AFTER status,
    MODIFY COLUMN id_cargo INT UNSIGNED NULL AFTER id_dir;

-- 3. Detectar si la tabla CONTRATO todavía tiene la columna antigua.
SET @existe_tipo_personal_contrato := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'CONTRATO'
      AND COLUMN_NAME = 'tipo_personal'
);

-- 4. Copiar los valores del contrato más reciente a TRABAJADOR ANTES de borrar
--    la columna antigua. El campo tipo_contrato no se modifica.
SET @sql_migracion := IF(
    @existe_tipo_personal_contrato > 0,
    'UPDATE TRABAJADOR AS t
     INNER JOIN (
         SELECT c1.id_trabajador, c1.tipo_personal
         FROM CONTRATO AS c1
         INNER JOIN (
             SELECT id_trabajador, MAX(id_contrato) AS id_contrato
             FROM CONTRATO
             GROUP BY id_trabajador
         ) AS ultimo ON ultimo.id_contrato = c1.id_contrato
     ) AS dato ON dato.id_trabajador = t.id_trabajador
     SET t.tipo_personal = COALESCE(NULLIF(TRIM(dato.tipo_personal), ''''), ''Fijo'')',
    'SELECT ''CONTRATO.tipo_personal no existe; no hay columna antigua que trasladar.'' AS mensaje'
);
PREPARE sentencia_migracion FROM @sql_migracion;
EXECUTE sentencia_migracion;
DEALLOCATE PREPARE sentencia_migracion;

-- 5. Agregar el índice de TRABAJADOR si aún no existe.
SET @existe_indice_tipo_personal := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'TRABAJADOR'
      AND INDEX_NAME = 'idx_trabajador_tipo_personal'
);
SET @sql_migracion := IF(
    @existe_indice_tipo_personal = 0,
    'ALTER TABLE TRABAJADOR ADD INDEX idx_trabajador_tipo_personal (tipo_personal)',
    'SELECT ''El índice de tipo_personal ya existe.'' AS mensaje'
);
PREPARE sentencia_migracion FROM @sql_migracion;
EXECUTE sentencia_migracion;
DEALLOCATE PREPARE sentencia_migracion;

-- 6. Agregar la restricción de valores permitidos si todavía no existe.
SET @existe_check_tipo_personal := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'TRABAJADOR'
      AND CONSTRAINT_NAME = 'chk_tipo_personal_trabajador'
      AND CONSTRAINT_TYPE = 'CHECK'
);
SET @sql_migracion := IF(
    @existe_check_tipo_personal = 0,
    'ALTER TABLE TRABAJADOR ADD CONSTRAINT chk_tipo_personal_trabajador CHECK (tipo_personal IN (''Fijo'', ''Contratado'', ''Obrero'', ''Empleado'', ''Jubilado''))',
    'SELECT ''La restricción de tipo_personal ya existe.'' AS mensaje'
);
PREPARE sentencia_migracion FROM @sql_migracion;
EXECUTE sentencia_migracion;
DEALLOCATE PREPARE sentencia_migracion;

-- 7. Eliminar tipo_personal de CONTRATO solo después de copiar los valores.
SET @sql_migracion := IF(
    @existe_tipo_personal_contrato > 0,
    'ALTER TABLE CONTRATO DROP COLUMN tipo_personal',
    'SELECT ''CONTRATO ya no contiene tipo_personal.'' AS mensaje'
);
PREPARE sentencia_migracion FROM @sql_migracion;
EXECUTE sentencia_migracion;
DEALLOCATE PREPARE sentencia_migracion;

-- 8. Verificar que tipo_personal está en TRABAJADOR y no en CONTRATO.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, ORDINAL_POSITION
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('TRABAJADOR', 'CONTRATO')
  AND COLUMN_NAME IN ('genero', 'tipo_personal', 'tipo_contrato')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- 9. Verificar los datos laborales; esta migración no modifica status ni cargos.
SELECT t.id_trabajador, t.cedula,
       CONCAT(t.nombres, ' ', t.apellidos) AS trabajador,
       t.genero, t.tipo_personal, t.status AS estatus_laboral,
       c.nombre_cargo AS cargo
FROM TRABAJADOR AS t
LEFT JOIN CARGO AS c ON c.id_cargo = t.id_cargo
ORDER BY t.id_trabajador;
