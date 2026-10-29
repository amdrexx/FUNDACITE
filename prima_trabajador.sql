-- Tabla de primas por trabajador (la crea sola el sistema; este script es opcional).
-- La tabla PRIMA existente NO se modifica.
CREATE TABLE IF NOT EXISTS PRIMA_TRABAJADOR (
    id_prima INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_trabajador INT UNSIGNED NOT NULL,
    tipo_prima VARCHAR(100) NOT NULL,
    condicion VARCHAR(150) NOT NULL DEFAULT '',
    porcentaje DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    monto_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    fecha DATE NOT NULL,
    PRIMARY KEY (id_prima),
    UNIQUE KEY uq_prima_trabajador_tipo (id_trabajador, tipo_prima),
    CONSTRAINT fk_prima_trabajador FOREIGN KEY (id_trabajador)
        REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
