-- =====================================================================
-- Migración: módulo de Días de Disfrute
-- Ejecutar UNA vez sobre la base de datos ya existente
-- (phpMyAdmin, consola mysql, o el panel de importar del sistema).
--
-- Agrega:
--   - dias_correspondientes: días hábiles calculados según el art. 190
--     de la LOTTT (antigüedad del trabajador) al momento de la solicitud.
--   - fecha_solicitud: marca cuándo se creó la solicitud, para contar
--     el plazo de 15 días calendario sin decisión.
--   - 'Vencida' como nuevo valor del estado.
-- =====================================================================

ALTER TABLE DISFRUTE_DE_VACACIONES
    ADD COLUMN dias_correspondientes TINYINT UNSIGNED NULL AFTER justificacion_desaprobacion,
    ADD COLUMN fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER dias_correspondientes,
    MODIFY COLUMN estado ENUM('Pendiente', 'Aprobado', 'Desaprobado', 'Vencida') NOT NULL DEFAULT 'Pendiente';
