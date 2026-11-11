-- Conecta CONVENIO con DISFRUTE_DE_VACACIONES (antes CONVENIO quedaba aislado).
USE bd_rrhh_fundacite_yaracuy;

ALTER TABLE DISFRUTE_DE_VACACIONES
    ADD COLUMN id_convenio INT UNSIGNED NULL AFTER id_trabajador,
    ADD INDEX idx_disfrute_convenio (id_convenio);

-- Solicitudes existentes: se enlazan al convenio cuyo periodo cubre su fecha de solicitud
-- (si hubiera varios, el de fecha_inicio más reciente).
UPDATE DISFRUTE_DE_VACACIONES d
SET d.id_convenio = (
    SELECT c.id_convenio FROM CONVENIO c
    WHERE c.fecha_inicio <= DATE(d.fecha_solicitud) AND c.fecha_vencimiento >= DATE(d.fecha_solicitud)
    ORDER BY c.fecha_inicio DESC, c.id_convenio DESC LIMIT 1
);

ALTER TABLE DISFRUTE_DE_VACACIONES
    ADD CONSTRAINT fk_disfrute_convenio FOREIGN KEY (id_convenio)
    REFERENCES CONVENIO(id_convenio) ON DELETE RESTRICT ON UPDATE CASCADE;
