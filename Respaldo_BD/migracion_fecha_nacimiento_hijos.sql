-- ================================================================
-- FUNDACITE - Migración de HIJO_TRABAJADOR a fecha_nacimiento
-- Ejecutar UNA SOLA VEZ sobre bd_rrhh_fundacite_yaracuy.
-- MariaDB 10.5+ / MySQL 8.0.29+ (ADD COLUMN IF NOT EXISTS).
-- ================================================================

USE bd_rrhh_fundacite_yaracuy;

-- Los contadores se conservan en TRABAJADOR, pero se recalculan desde
-- las fechas de nacimiento de los hijos.
ALTER TABLE TRABAJADOR
    ADD COLUMN IF NOT EXISTS hijo_menor INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS hijo_mayor INT UNSIGNED NOT NULL DEFAULT 0;

-- Crear la tabla si todavía no existe. Si ya existe con la columna edad,
-- el bloque posterior migra los valores.
CREATE TABLE IF NOT EXISTS HIJO_TRABAJADOR (
    id_hijo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_trabajador INT(10) UNSIGNED NOT NULL,
    fecha_nacimiento DATE NULL,
    PRIMARY KEY (id_hijo),
    KEY idx_hijo_trabajador (id_trabajador),
    CONSTRAINT fk_hijo_trabajador_trabajador
        FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR (id_trabajador)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE HIJO_TRABAJADOR
    ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE NULL;

-- Si la tabla antigua tenía edad, convertirla a una fecha aproximada.
-- La edad antigua no conserva día/mes de nacimiento; por ello se usa el
-- 1 de enero del año estimado. El usuario puede corregir la fecha exacta.
SET @existe_edad := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'HIJO_TRABAJADOR'
      AND COLUMN_NAME = 'edad'
);
SET @migrar_edad_sql := IF(
    @existe_edad > 0,
    'UPDATE HIJO_TRABAJADOR SET fecha_nacimiento = STR_TO_DATE(CONCAT(YEAR(CURDATE()) - edad, ''-01-01''), ''%Y-%m-%d'') WHERE fecha_nacimiento IS NULL AND edad IS NOT NULL',
    'SELECT 1'
);
PREPARE stmt_migrar_edad FROM @migrar_edad_sql;
EXECUTE stmt_migrar_edad;
DEALLOCATE PREPARE stmt_migrar_edad;

-- Quitar la columna edad antigua después de migrar sus valores.
SET @eliminar_edad_sql := IF(
    @existe_edad > 0,
    'ALTER TABLE HIJO_TRABAJADOR DROP COLUMN edad',
    'SELECT 1'
);
PREPARE stmt_eliminar_edad FROM @eliminar_edad_sql;
EXECUTE stmt_eliminar_edad;
DEALLOCATE PREPARE stmt_eliminar_edad;

-- Dejar fecha_nacimiento obligatoria cuando todos los registros ya tengan
-- una fecha. Si hay nulos, el script los conserva para corrección manual.
SET @nulos_fecha := (SELECT COUNT(*) FROM HIJO_TRABAJADOR WHERE fecha_nacimiento IS NULL);
SET @ajustar_fecha_sql := IF(
    @nulos_fecha = 0,
    'ALTER TABLE HIJO_TRABAJADOR MODIFY fecha_nacimiento DATE NOT NULL',
    'SELECT 1'
);
PREPARE stmt_ajustar_fecha FROM @ajustar_fecha_sql;
EXECUTE stmt_ajustar_fecha;
DEALLOCATE PREPARE stmt_ajustar_fecha;

-- Sincronizar inmediatamente los contadores de todos los trabajadores.
UPDATE TRABAJADOR t
LEFT JOIN (
    SELECT id_trabajador,
           SUM(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 18) AS menores,
           SUM(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 18) AS mayores
    FROM HIJO_TRABAJADOR
    WHERE fecha_nacimiento IS NOT NULL
    GROUP BY id_trabajador
) h ON h.id_trabajador = t.id_trabajador
SET t.hijo_menor = COALESCE(h.menores, 0),
    t.hijo_mayor = COALESCE(h.mayores, 0);

-- Actualización inmediata de contadores ante altas, cambios o eliminaciones.
DROP TRIGGER IF EXISTS trg_hijo_ai_contadores;
DROP TRIGGER IF EXISTS trg_hijo_au_contadores;
DROP TRIGGER IF EXISTS trg_hijo_ad_contadores;

DELIMITER //
CREATE TRIGGER trg_hijo_ai_contadores AFTER INSERT ON HIJO_TRABAJADOR
FOR EACH ROW
BEGIN
    UPDATE TRABAJADOR t
    SET t.hijo_menor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = NEW.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) < 18),
        t.hijo_mayor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = NEW.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) >= 18)
    WHERE t.id_trabajador = NEW.id_trabajador;
END//

CREATE TRIGGER trg_hijo_au_contadores AFTER UPDATE ON HIJO_TRABAJADOR
FOR EACH ROW
BEGIN
    UPDATE TRABAJADOR t
    SET t.hijo_menor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = NEW.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) < 18),
        t.hijo_mayor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = NEW.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) >= 18)
    WHERE t.id_trabajador = NEW.id_trabajador;
    IF OLD.id_trabajador <> NEW.id_trabajador THEN
        UPDATE TRABAJADOR t
        SET t.hijo_menor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = OLD.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) < 18),
            t.hijo_mayor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = OLD.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) >= 18)
        WHERE t.id_trabajador = OLD.id_trabajador;
    END IF;
END//

CREATE TRIGGER trg_hijo_ad_contadores AFTER DELETE ON HIJO_TRABAJADOR
FOR EACH ROW
BEGIN
    UPDATE TRABAJADOR t
    SET t.hijo_menor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = OLD.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) < 18),
        t.hijo_mayor = (SELECT COUNT(*) FROM HIJO_TRABAJADOR h WHERE h.id_trabajador = OLD.id_trabajador AND h.fecha_nacimiento IS NOT NULL AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) >= 18)
    WHERE t.id_trabajador = OLD.id_trabajador;
END//
DELIMITER ;

-- Cumpleaños: los triggers no se disparan al pasar el tiempo, por lo que
-- este evento diario reclasifica automáticamente a los hijos que cumplan 18.
CREATE EVENT IF NOT EXISTS ev_actualizar_contadores_hijos_diario
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 5 MINUTE)
DO
    UPDATE TRABAJADOR t
    LEFT JOIN (
        SELECT id_trabajador,
               SUM(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 18) AS menores,
               SUM(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 18) AS mayores
        FROM HIJO_TRABAJADOR
        WHERE fecha_nacimiento IS NOT NULL
        GROUP BY id_trabajador
    ) h ON h.id_trabajador = t.id_trabajador
    SET t.hijo_menor = COALESCE(h.menores, 0),
        t.hijo_mayor = COALESCE(h.mayores, 0);

-- Verificar si el programador de eventos está habilitado:
-- SHOW VARIABLES LIKE 'event_scheduler';
-- Si aparece OFF y tienes permisos administrativos, habilitar con:
-- SET GLOBAL event_scheduler = ON;
