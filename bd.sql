CREATE DATABASE IF NOT EXISTS bd_rrhh_fundacite_yaracuy;

USE bd_rrhh_fundacite_yaracuy;

-- ================================================================================
-- 1. TABLAS DE UBICACIÓN GEOGRÁFICA
-- ================================================================================

CREATE TABLE ESTADO (
    cod_est INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (cod_est)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE MUNICIPIO (
    cod_muni INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cod_est INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (cod_muni),
    FOREIGN KEY (cod_est) REFERENCES ESTADO(cod_est) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PARROQUIA (
    cod_par INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cod_muni INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (cod_par),
    FOREIGN KEY (cod_muni) REFERENCES MUNICIPIO(cod_muni) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE DIRECCION (
    id_dir INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cod_par INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_dir),
    FOREIGN KEY (cod_par) REFERENCES PARROQUIA(cod_par) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================================
-- 2. TABLAS MAESTRAS PRINCIPALES
-- ================================================================================

CREATE TABLE CARGO (
    id_cargo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_cargo VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_cargo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE TRABAJADOR (
    id_trabajador INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_documento VARCHAR(50) NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero VARCHAR(20),
    estado_civil VARCHAR(30),
    nacionalidad VARCHAR(50) DEFAULT 'Venezolano(a)',
    telefono VARCHAR(20),
    correo VARCHAR(100),
    status VARCHAR(20) NOT NULL DEFAULT 'Activo',
    id_dir INT UNSIGNED,
    id_cargo INT UNSIGNED,
    PRIMARY KEY (id_trabajador),
    UNIQUE KEY uq_cedula (cedula),
    INDEX idx_trabajador_status (status),
    INDEX idx_trabajador_cargo (id_cargo),
    INDEX idx_trabajador_dir (id_dir),
    FOREIGN KEY (id_dir) REFERENCES DIRECCION(id_dir) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (id_cargo) REFERENCES CARGO(id_cargo) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_status CHECK (status IN ('Activo', 'Inactivo', 'Suspendido', 'Jubilado')),
    CONSTRAINT chk_tipo_documento CHECK (tipo_documento IN ('Cédula', 'Pasaporte', 'Cédula de Extranjería')),
    CONSTRAINT chk_genero CHECK (genero IS NULL OR genero IN ('Masculino', 'Femenino', 'No Binario')),
    CONSTRAINT chk_estado_civil CHECK (estado_civil IS NULL OR estado_civil IN ('Soltero(a)', 'Casado(a)', 'Divorciado(a)', 'Viudo(a)', 'Concubinato'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================================
-- 3. TABLAS DE PROCESOS Y GESTIÓN LABORAL
-- ================================================================================

CREATE TABLE CONTRATO (
    id_contrato INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_trabajador INT UNSIGNED NOT NULL,
    notas_empresa TEXT,
    tipo_contrato VARCHAR(50) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    lugar_trabajo VARCHAR(100),
    salario DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id_contrato),
    INDEX idx_contrato_trabajador (id_trabajador),
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_tipo_contrato CHECK (tipo_contrato IN ('Indefinido', 'Tiempo determinado', 'Obra determinada', 'Pasantía', 'Suplencia')),
    CONSTRAINT chk_salario_positivo CHECK (salario > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE SOLICITUD (
    id_solicitud INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_trabajador INT UNSIGNED NOT NULL,
    codigo_solicitud VARCHAR(50),
    tipo_solicitud VARCHAR(50) NOT NULL,
    motivo_solicitud TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_finalizacion DATE,
    PRIMARY KEY (id_solicitud),
    INDEX idx_solicitud_trabajador (id_trabajador),
    INDEX idx_solicitud_tipo (tipo_solicitud),
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_tipo_solicitud CHECK (tipo_solicitud IN ('Vacaciones', 'Permiso', 'Constancia de trabajo', 'Reposo', 'Otro')),
    CONSTRAINT chk_fechas_solicitud CHECK (fecha_finalizacion IS NULL OR fecha_finalizacion >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE CONSTANCIA_DE_TRABAJO (
    id_constancia INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud INT UNSIGNED NOT NULL,
    nombre_director_departamento VARCHAR(150),
    tipo_personal VARCHAR(50),
    fecha DATE NOT NULL,
    PRIMARY KEY (id_constancia),
    INDEX idx_constancia_solicitud (id_solicitud),
    FOREIGN KEY (id_solicitud) REFERENCES SOLICITUD(id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_tipo_personal CHECK (tipo_personal IS NULL OR tipo_personal IN ('Fijo', 'Contratado', 'Obrero', 'Empleado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE DISFRUTE_DE_VACACIONES (
    id_dis INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud INT UNSIGNED NOT NULL,
    nombre_cargo VARCHAR(100),
    descripcion TEXT,
    desde DATE NOT NULL,
    hasta DATE NOT NULL,
    PRIMARY KEY (id_dis),
    INDEX idx_dis_vacaciones_solicitud (id_solicitud),
    FOREIGN KEY (id_solicitud) REFERENCES SOLICITUD(id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_periodo_vacaciones CHECK (hasta >= desde)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================================
-- 4. TABLA DE SEGURIDAD Y ACCESO
-- ================================================================================

CREATE TABLE USUARIO (
    id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    tipo_usuario VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_nombre (nombre),
    INDEX idx_usuario_status (status),
    CONSTRAINT chk_usuario_status CHECK (status IN ('Activo', 'Inactivo', 'Bloqueado')),
    CONSTRAINT chk_tipo_usuario CHECK (tipo_usuario IN ('Administrador', 'Supervisor', 'Usuario', 'Invitado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================================
-- 5. VISTAS
-- ================================================================================

CREATE OR REPLACE VIEW V_TRABAJADOR_DETALLE AS
SELECT 
    id_trabajador,
    cedula,
    nombres,
    apellidos,
    fecha_nacimiento,
    TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) AS edad,
    genero,
    estado_civil,
    nacionalidad,
    telefono,
    correo,
    status,
    id_dir,
    id_cargo
FROM TRABAJADOR;

CREATE OR REPLACE VIEW V_TRABAJADOR_COMPLETO AS
SELECT 
    t.id_trabajador,
    t.cedula,
    t.nombres,
    t.apellidos,
    t.fecha_nacimiento,
    TIMESTAMPDIFF(YEAR, t.fecha_nacimiento, CURDATE()) AS edad,
    t.genero,
    t.estado_civil,
    t.nacionalidad,
    t.telefono,
    t.correo,
    t.status,
    c.nombre_cargo,
    d.nombre AS direccion,
    p.nombre AS parroquia,
    m.nombre AS municipio,
    e.nombre AS estado
FROM TRABAJADOR t
LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
LEFT JOIN DIRECCION d ON t.id_dir = d.id_dir
LEFT JOIN PARROQUIA p ON d.cod_par = p.cod_par
LEFT JOIN MUNICIPIO m ON p.cod_muni = m.cod_muni
LEFT JOIN ESTADO e ON m.cod_est = e.cod_est;