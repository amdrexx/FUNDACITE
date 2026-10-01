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
    FOREIGN KEY (cod_est)
        REFERENCES ESTADO(cod_est)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PARROQUIA (
    cod_par INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cod_muni INT UNSIGNED NOT NULL,
    nombre VARCHAR(250) NOT NULL,
    PRIMARY KEY (cod_par),
    INDEX idx_parroquia_municipio (cod_muni),
    CONSTRAINT fk_parroquia_municipio
        FOREIGN KEY (cod_muni)
        REFERENCES MUNICIPIO(cod_muni)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




CREATE TABLE DIRECCION (
    id_dir INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cod_par INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NULL,
    PRIMARY KEY (id_dir),
    FOREIGN KEY (cod_par)
        REFERENCES PARROQUIA(cod_par)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
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
    cedula VARCHAR(10) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    fecha_ingreso DATE NOT NULL,
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
    FOREIGN KEY (id_dir) REFERENCES DIRECCION(id_dir) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_cargo) REFERENCES CARGO(id_cargo) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_status CHECK (status IN ('Activo', 'Inactivo', 'Jubilado')),
    CONSTRAINT chk_tipo_documento CHECK (tipo_documento IN ('Cédula', 'Pasaporte', 'Cédula de Extranjería')),
    CONSTRAINT chk_genero CHECK (genero IS NULL OR genero IN ('Masculino', 'Femenino')),
    CONSTRAINT chk_estado_civil CHECK (estado_civil IS NULL OR estado_civil IN ('Soltero(a)', 'Casado(a)', 'Divorciado(a)', 'Viudo(a)'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================================
-- 3. TABLAS DE PROCESOS Y GESTIÓN LABORAL
-- ================================================================================

CREATE TABLE CONTRATO (
    id_contrato INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_trabajador INT UNSIGNED NOT NULL,
    id_cargo INT UNSIGNED NOT NULL,
    tipo_contrato VARCHAR(50) NOT NULL,
    fecha_contrato DATE NOT NULL,
    fecha_fin DATE NULL,
    lugar_trabajo VARCHAR(255) NOT NULL,
    nombre_presidente VARCHAR(150) NOT NULL,
    cedula_presidente VARCHAR(20) NOT NULL,
    gaceta_designacion_presidente VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_contrato),
    INDEX idx_contrato_trabajador (id_trabajador),
    INDEX idx_contrato_cargo (id_cargo),
    CONSTRAINT fk_contrato_trabajador FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE,
    CONSTRAINT fk_contrato_cargo FOREIGN KEY (id_cargo) REFERENCES CARGO(id_cargo) ON DELETE RESTRICT,
    CONSTRAINT chk_periodo_contrato CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_contrato)
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
    id_trabajador INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    tipo_usuario VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_nombre (nombre),
    UNIQUE KEY uq_usuario_trabajador (id_trabajador),
    INDEX idx_usuario_status (status),
    CONSTRAINT fk_usuario_trabajador
        FOREIGN KEY (id_trabajador)
        REFERENCES TRABAJADOR(id_trabajador)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_usuario_status CHECK (status IN ('Activo', 'Inactivo', 'Bloqueado')),
    CONSTRAINT chk_tipo_usuario CHECK (tipo_usuario IN ('Administrador', 'Director', 'Analista'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================================
-- 5. TABLAS DE SALARIO Y PRIMAS
-- ================================================================================

CREATE TABLE SALARIO (
    id_salario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_trabajador INT UNSIGNED,
    id_cargo INT UNSIGNED NULL,
    fecha DATE NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Vigente',
    tipo_salario VARCHAR(20) NOT NULL DEFAULT 'base',
    PRIMARY KEY (id_salario),
    INDEX idx_salario_trabajador (id_trabajador),
    INDEX idx_salario_cargo (id_cargo),
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_salario_cargo FOREIGN KEY (id_cargo) REFERENCES CARGO(id_cargo),
    CONSTRAINT chk_salario_estado CHECK (estado IN ('Vigente', 'Deshabilitado')),
    CONSTRAINT chk_salario_tipo CHECK (tipo_salario IN ('base', 'cargo')),
    CONSTRAINT chk_salario_tipo_cargo_consistente CHECK (tipo_salario <> 'cargo' OR id_cargo IS NOT NULL),
    CONSTRAINT chk_salario_monto CHECK (monto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PRIMA (
    id_prima INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_prima VARCHAR(100) NOT NULL,
    porcentaje DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Activo',
    fecha DATE NOT NULL,
    PRIMARY KEY (id_prima),
    UNIQUE KEY uq_prima_tipo_prima (tipo_prima),
    CONSTRAINT chk_prima_estado CHECK (estado IN ('Activo', 'Inactivo')),
    CONSTRAINT chk_prima_porcentaje CHECK (porcentaje >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================================
-- 6. VISTAS
-- ================================================================================

CREATE OR REPLACE VIEW V_TRABAJADOR_DETALLE AS
SELECT 
    id_trabajador,
    cedula,
    nombres,
    apellidos,
    fecha_nacimiento,
    fecha_ingreso,
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
    t.fecha_ingreso,
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



-- ================================================================================
-- 7. DATOS INICIALES
-- ================================================================================

INSERT INTO CARGO (nombre_cargo) VALUES
('Administrador'),
('Analista'),
('Técnico');


INSERT INTO ESTADO (nombre) VALUES
('Amazonas'),
('Anzoátegui'),
('Apure'),
('Aragua'),
('Barinas'),
('Bolívar'),
('Carabobo'),
('Cojedes'),
('Delta Amacuro'),
('Distrito Capital'),
('Falcón'),
('Guárico'),
('La Guaira'),
('Lara'),
('Mérida'),
('Miranda'),
('Monagas'),
('Nueva Esparta'),
('Portuguesa'),
('Sucre'),
('Táchira'),
('Trujillo'),
('Yaracuy'),
('Zulia');

INSERT INTO MUNICIPIO (cod_muni, cod_est, nombre)
SELECT datos.id, estado.cod_est, datos.nombre
FROM ESTADO AS estado
INNER JOIN (
    SELECT 1 AS id, 'Amazonas' AS estado, 'Alto Orinoco' AS nombre
    UNION ALL SELECT 2 AS id, 'Amazonas' AS estado, 'Atabapo' AS nombre
    UNION ALL SELECT 3 AS id, 'Amazonas' AS estado, 'Atures' AS nombre
    UNION ALL SELECT 4 AS id, 'Amazonas' AS estado, 'Autana' AS nombre
    UNION ALL SELECT 5 AS id, 'Amazonas' AS estado, 'Manapiare' AS nombre
    UNION ALL SELECT 6 AS id, 'Amazonas' AS estado, 'Maroa' AS nombre
    UNION ALL SELECT 7 AS id, 'Amazonas' AS estado, 'Río Negro' AS nombre
    UNION ALL SELECT 8 AS id, 'Anzoátegui' AS estado, 'Anaco' AS nombre
    UNION ALL SELECT 9 AS id, 'Anzoátegui' AS estado, 'Aragua' AS nombre
    UNION ALL SELECT 10 AS id, 'Anzoátegui' AS estado, 'Manuel Ezequiel Bruzual' AS nombre
    UNION ALL SELECT 11 AS id, 'Anzoátegui' AS estado, 'Diego Bautista Urbaneja' AS nombre
    UNION ALL SELECT 12 AS id, 'Anzoátegui' AS estado, 'Fernando Peñalver' AS nombre
    UNION ALL SELECT 13 AS id, 'Anzoátegui' AS estado, 'Francisco Del Carmen Carvajal' AS nombre
    UNION ALL SELECT 14 AS id, 'Anzoátegui' AS estado, 'General Sir Arthur McGregor' AS nombre
    UNION ALL SELECT 15 AS id, 'Anzoátegui' AS estado, 'Guanta' AS nombre
    UNION ALL SELECT 16 AS id, 'Anzoátegui' AS estado, 'Independencia' AS nombre
    UNION ALL SELECT 17 AS id, 'Anzoátegui' AS estado, 'José Gregorio Monagas' AS nombre
    UNION ALL SELECT 18 AS id, 'Anzoátegui' AS estado, 'Juan Antonio Sotillo' AS nombre
    UNION ALL SELECT 19 AS id, 'Anzoátegui' AS estado, 'Juan Manuel Cajigal' AS nombre
    UNION ALL SELECT 20 AS id, 'Anzoátegui' AS estado, 'Libertad' AS nombre
    UNION ALL SELECT 21 AS id, 'Anzoátegui' AS estado, 'Francisco de Miranda' AS nombre
    UNION ALL SELECT 22 AS id, 'Anzoátegui' AS estado, 'Pedro María Freites' AS nombre
    UNION ALL SELECT 23 AS id, 'Anzoátegui' AS estado, 'Píritu' AS nombre
    UNION ALL SELECT 24 AS id, 'Anzoátegui' AS estado, 'San José de Guanipa' AS nombre
    UNION ALL SELECT 25 AS id, 'Anzoátegui' AS estado, 'San Juan de Capistrano' AS nombre
    UNION ALL SELECT 26 AS id, 'Anzoátegui' AS estado, 'Santa Ana' AS nombre
    UNION ALL SELECT 27 AS id, 'Anzoátegui' AS estado, 'Simón Bolívar' AS nombre
    UNION ALL SELECT 28 AS id, 'Anzoátegui' AS estado, 'Simón Rodríguez' AS nombre
    UNION ALL SELECT 29 AS id, 'Apure' AS estado, 'Achaguas' AS nombre
    UNION ALL SELECT 30 AS id, 'Apure' AS estado, 'Biruaca' AS nombre
    UNION ALL SELECT 31 AS id, 'Apure' AS estado, 'Muñóz' AS nombre
    UNION ALL SELECT 32 AS id, 'Apure' AS estado, 'Páez' AS nombre
    UNION ALL SELECT 33 AS id, 'Apure' AS estado, 'Pedro Camejo' AS nombre
    UNION ALL SELECT 34 AS id, 'Apure' AS estado, 'Rómulo Gallegos' AS nombre
    UNION ALL SELECT 35 AS id, 'Apure' AS estado, 'San Fernando' AS nombre
    UNION ALL SELECT 36 AS id, 'Aragua' AS estado, 'Atanasio Girardot' AS nombre
    UNION ALL SELECT 37 AS id, 'Aragua' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 38 AS id, 'Aragua' AS estado, 'Camatagua' AS nombre
    UNION ALL SELECT 39 AS id, 'Aragua' AS estado, 'Francisco Linares Alcántara' AS nombre
    UNION ALL SELECT 40 AS id, 'Aragua' AS estado, 'José Ángel Lamas' AS nombre
    UNION ALL SELECT 41 AS id, 'Aragua' AS estado, 'José Félix Ribas' AS nombre
    UNION ALL SELECT 42 AS id, 'Aragua' AS estado, 'José Rafael Revenga' AS nombre
    UNION ALL SELECT 43 AS id, 'Aragua' AS estado, 'Libertador' AS nombre
    UNION ALL SELECT 44 AS id, 'Aragua' AS estado, 'Mario Briceño Iragorry' AS nombre
    UNION ALL SELECT 45 AS id, 'Aragua' AS estado, 'Ocumare de la Costa de Oro' AS nombre
    UNION ALL SELECT 46 AS id, 'Aragua' AS estado, 'San Casimiro' AS nombre
    UNION ALL SELECT 47 AS id, 'Aragua' AS estado, 'San Sebastián' AS nombre
    UNION ALL SELECT 48 AS id, 'Aragua' AS estado, 'Santiago Mariño' AS nombre
    UNION ALL SELECT 49 AS id, 'Aragua' AS estado, 'Santos Michelena' AS nombre
    UNION ALL SELECT 50 AS id, 'Aragua' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 51 AS id, 'Aragua' AS estado, 'Tovar' AS nombre
    UNION ALL SELECT 52 AS id, 'Aragua' AS estado, 'Urdaneta' AS nombre
    UNION ALL SELECT 53 AS id, 'Aragua' AS estado, 'Zamora' AS nombre
    UNION ALL SELECT 54 AS id, 'Barinas' AS estado, 'Alberto Arvelo Torrealba' AS nombre
    UNION ALL SELECT 55 AS id, 'Barinas' AS estado, 'Andrés Eloy Blanco' AS nombre
    UNION ALL SELECT 56 AS id, 'Barinas' AS estado, 'Antonio José de Sucre' AS nombre
    UNION ALL SELECT 57 AS id, 'Barinas' AS estado, 'Arismendi' AS nombre
    UNION ALL SELECT 58 AS id, 'Barinas' AS estado, 'Barinas' AS nombre
    UNION ALL SELECT 59 AS id, 'Barinas' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 60 AS id, 'Barinas' AS estado, 'Cruz Paredes' AS nombre
    UNION ALL SELECT 61 AS id, 'Barinas' AS estado, 'Ezequiel Zamora' AS nombre
    UNION ALL SELECT 62 AS id, 'Barinas' AS estado, 'Obispos' AS nombre
    UNION ALL SELECT 63 AS id, 'Barinas' AS estado, 'Pedraza' AS nombre
    UNION ALL SELECT 64 AS id, 'Barinas' AS estado, 'Rojas' AS nombre
    UNION ALL SELECT 65 AS id, 'Barinas' AS estado, 'Sosa' AS nombre
    UNION ALL SELECT 66 AS id, 'Bolívar' AS estado, 'Caroní' AS nombre
    UNION ALL SELECT 67 AS id, 'Bolívar' AS estado, 'Cedeño' AS nombre
    UNION ALL SELECT 68 AS id, 'Bolívar' AS estado, 'El Callao' AS nombre
    UNION ALL SELECT 69 AS id, 'Bolívar' AS estado, 'Gran Sabana' AS nombre
    UNION ALL SELECT 70 AS id, 'Bolívar' AS estado, 'Heres' AS nombre
    UNION ALL SELECT 71 AS id, 'Bolívar' AS estado, 'Piar' AS nombre
    UNION ALL SELECT 72 AS id, 'Bolívar' AS estado, 'Angostura (Raúl Leoni)' AS nombre
    UNION ALL SELECT 73 AS id, 'Bolívar' AS estado, 'Roscio' AS nombre
    UNION ALL SELECT 74 AS id, 'Bolívar' AS estado, 'Sifontes' AS nombre
    UNION ALL SELECT 75 AS id, 'Bolívar' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 76 AS id, 'Bolívar' AS estado, 'Padre Pedro Chien' AS nombre
    UNION ALL SELECT 77 AS id, 'Carabobo' AS estado, 'Bejuma' AS nombre
    UNION ALL SELECT 78 AS id, 'Carabobo' AS estado, 'Carlos Arvelo' AS nombre
    UNION ALL SELECT 79 AS id, 'Carabobo' AS estado, 'Diego Ibarra' AS nombre
    UNION ALL SELECT 80 AS id, 'Carabobo' AS estado, 'Guacara' AS nombre
    UNION ALL SELECT 81 AS id, 'Carabobo' AS estado, 'Juan José Mora' AS nombre
    UNION ALL SELECT 82 AS id, 'Carabobo' AS estado, 'Libertador' AS nombre
    UNION ALL SELECT 83 AS id, 'Carabobo' AS estado, 'Los Guayos' AS nombre
    UNION ALL SELECT 84 AS id, 'Carabobo' AS estado, 'Miranda' AS nombre
    UNION ALL SELECT 85 AS id, 'Carabobo' AS estado, 'Montalbán' AS nombre
    UNION ALL SELECT 86 AS id, 'Carabobo' AS estado, 'Naguanagua' AS nombre
    UNION ALL SELECT 87 AS id, 'Carabobo' AS estado, 'Puerto Cabello' AS nombre
    UNION ALL SELECT 88 AS id, 'Carabobo' AS estado, 'San Diego' AS nombre
    UNION ALL SELECT 89 AS id, 'Carabobo' AS estado, 'San Joaquín' AS nombre
    UNION ALL SELECT 90 AS id, 'Carabobo' AS estado, 'Valencia' AS nombre
    UNION ALL SELECT 91 AS id, 'Cojedes' AS estado, 'Anzoátegui' AS nombre
    UNION ALL SELECT 92 AS id, 'Cojedes' AS estado, 'Tinaquillo' AS nombre
    UNION ALL SELECT 93 AS id, 'Cojedes' AS estado, 'Girardot' AS nombre
    UNION ALL SELECT 94 AS id, 'Cojedes' AS estado, 'Lima Blanco' AS nombre
    UNION ALL SELECT 95 AS id, 'Cojedes' AS estado, 'Pao de San Juan Bautista' AS nombre
    UNION ALL SELECT 96 AS id, 'Cojedes' AS estado, 'Ricaurte' AS nombre
    UNION ALL SELECT 97 AS id, 'Cojedes' AS estado, 'Rómulo Gallegos' AS nombre
    UNION ALL SELECT 98 AS id, 'Cojedes' AS estado, 'San Carlos' AS nombre
    UNION ALL SELECT 99 AS id, 'Cojedes' AS estado, 'Tinaco' AS nombre
    UNION ALL SELECT 100 AS id, 'Delta Amacuro' AS estado, 'Antonio Díaz' AS nombre
    UNION ALL SELECT 101 AS id, 'Delta Amacuro' AS estado, 'Casacoima' AS nombre
    UNION ALL SELECT 102 AS id, 'Delta Amacuro' AS estado, 'Pedernales' AS nombre
    UNION ALL SELECT 103 AS id, 'Delta Amacuro' AS estado, 'Tucupita' AS nombre
    UNION ALL SELECT 104 AS id, 'Falcón' AS estado, 'Acosta' AS nombre
    UNION ALL SELECT 105 AS id, 'Falcón' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 106 AS id, 'Falcón' AS estado, 'Buchivacoa' AS nombre
    UNION ALL SELECT 107 AS id, 'Falcón' AS estado, 'Cacique Manaure' AS nombre
    UNION ALL SELECT 108 AS id, 'Falcón' AS estado, 'Carirubana' AS nombre
    UNION ALL SELECT 109 AS id, 'Falcón' AS estado, 'Colina' AS nombre
    UNION ALL SELECT 110 AS id, 'Falcón' AS estado, 'Dabajuro' AS nombre
    UNION ALL SELECT 111 AS id, 'Falcón' AS estado, 'Democracia' AS nombre
    UNION ALL SELECT 112 AS id, 'Falcón' AS estado, 'Falcón' AS nombre
    UNION ALL SELECT 113 AS id, 'Falcón' AS estado, 'Federación' AS nombre
    UNION ALL SELECT 114 AS id, 'Falcón' AS estado, 'Jacura' AS nombre
    UNION ALL SELECT 115 AS id, 'Falcón' AS estado, 'José Laurencio Silva' AS nombre
    UNION ALL SELECT 116 AS id, 'Falcón' AS estado, 'Los Taques' AS nombre
    UNION ALL SELECT 117 AS id, 'Falcón' AS estado, 'Mauroa' AS nombre
    UNION ALL SELECT 118 AS id, 'Falcón' AS estado, 'Miranda' AS nombre
    UNION ALL SELECT 119 AS id, 'Falcón' AS estado, 'Monseñor Iturriza' AS nombre
    UNION ALL SELECT 120 AS id, 'Falcón' AS estado, 'Palmasola' AS nombre
    UNION ALL SELECT 121 AS id, 'Falcón' AS estado, 'Petit' AS nombre
    UNION ALL SELECT 122 AS id, 'Falcón' AS estado, 'Píritu' AS nombre
    UNION ALL SELECT 123 AS id, 'Falcón' AS estado, 'San Francisco' AS nombre
    UNION ALL SELECT 124 AS id, 'Falcón' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 125 AS id, 'Falcón' AS estado, 'Tocópero' AS nombre
    UNION ALL SELECT 126 AS id, 'Falcón' AS estado, 'Unión' AS nombre
    UNION ALL SELECT 127 AS id, 'Falcón' AS estado, 'Urumaco' AS nombre
    UNION ALL SELECT 128 AS id, 'Falcón' AS estado, 'Zamora' AS nombre
    UNION ALL SELECT 129 AS id, 'Guárico' AS estado, 'Camaguán' AS nombre
    UNION ALL SELECT 130 AS id, 'Guárico' AS estado, 'Chaguaramas' AS nombre
    UNION ALL SELECT 131 AS id, 'Guárico' AS estado, 'El Socorro' AS nombre
    UNION ALL SELECT 132 AS id, 'Guárico' AS estado, 'José Félix Ribas' AS nombre
    UNION ALL SELECT 133 AS id, 'Guárico' AS estado, 'José Tadeo Monagas' AS nombre
    UNION ALL SELECT 134 AS id, 'Guárico' AS estado, 'Juan Germán Roscio' AS nombre
    UNION ALL SELECT 135 AS id, 'Guárico' AS estado, 'Julián Mellado' AS nombre
    UNION ALL SELECT 136 AS id, 'Guárico' AS estado, 'Las Mercedes' AS nombre
    UNION ALL SELECT 137 AS id, 'Guárico' AS estado, 'Leonardo Infante' AS nombre
    UNION ALL SELECT 138 AS id, 'Guárico' AS estado, 'Pedro Zaraza' AS nombre
    UNION ALL SELECT 139 AS id, 'Guárico' AS estado, 'Ortíz' AS nombre
    UNION ALL SELECT 140 AS id, 'Guárico' AS estado, 'San Gerónimo de Guayabal' AS nombre
    UNION ALL SELECT 141 AS id, 'Guárico' AS estado, 'San José de Guaribe' AS nombre
    UNION ALL SELECT 142 AS id, 'Guárico' AS estado, 'Santa María de Ipire' AS nombre
    UNION ALL SELECT 143 AS id, 'Guárico' AS estado, 'Sebastián Francisco de Miranda' AS nombre
    UNION ALL SELECT 144 AS id, 'Lara' AS estado, 'Andrés Eloy Blanco' AS nombre
    UNION ALL SELECT 145 AS id, 'Lara' AS estado, 'Crespo' AS nombre
    UNION ALL SELECT 146 AS id, 'Lara' AS estado, 'Iribarren' AS nombre
    UNION ALL SELECT 147 AS id, 'Lara' AS estado, 'Jiménez' AS nombre
    UNION ALL SELECT 148 AS id, 'Lara' AS estado, 'Morán' AS nombre
    UNION ALL SELECT 149 AS id, 'Lara' AS estado, 'Palavecino' AS nombre
    UNION ALL SELECT 150 AS id, 'Lara' AS estado, 'Simón Planas' AS nombre
    UNION ALL SELECT 151 AS id, 'Lara' AS estado, 'Torres' AS nombre
    UNION ALL SELECT 152 AS id, 'Lara' AS estado, 'Urdaneta' AS nombre
    UNION ALL SELECT 179 AS id, 'Mérida' AS estado, 'Alberto Adriani' AS nombre
    UNION ALL SELECT 180 AS id, 'Mérida' AS estado, 'Andrés Bello' AS nombre
    UNION ALL SELECT 181 AS id, 'Mérida' AS estado, 'Antonio Pinto Salinas' AS nombre
    UNION ALL SELECT 182 AS id, 'Mérida' AS estado, 'Aricagua' AS nombre
    UNION ALL SELECT 183 AS id, 'Mérida' AS estado, 'Arzobispo Chacón' AS nombre
    UNION ALL SELECT 184 AS id, 'Mérida' AS estado, 'Campo Elías' AS nombre
    UNION ALL SELECT 185 AS id, 'Mérida' AS estado, 'Caracciolo Parra Olmedo' AS nombre
    UNION ALL SELECT 186 AS id, 'Mérida' AS estado, 'Cardenal Quintero' AS nombre
    UNION ALL SELECT 187 AS id, 'Mérida' AS estado, 'Guaraque' AS nombre
    UNION ALL SELECT 188 AS id, 'Mérida' AS estado, 'Julio César Salas' AS nombre
    UNION ALL SELECT 189 AS id, 'Mérida' AS estado, 'Justo Briceño' AS nombre
    UNION ALL SELECT 190 AS id, 'Mérida' AS estado, 'Libertador' AS nombre
    UNION ALL SELECT 191 AS id, 'Mérida' AS estado, 'Miranda' AS nombre
    UNION ALL SELECT 192 AS id, 'Mérida' AS estado, 'Obispo Ramos de Lora' AS nombre
    UNION ALL SELECT 193 AS id, 'Mérida' AS estado, 'Padre Noguera' AS nombre
    UNION ALL SELECT 194 AS id, 'Mérida' AS estado, 'Pueblo Llano' AS nombre
    UNION ALL SELECT 195 AS id, 'Mérida' AS estado, 'Rangel' AS nombre
    UNION ALL SELECT 196 AS id, 'Mérida' AS estado, 'Rivas Dávila' AS nombre
    UNION ALL SELECT 197 AS id, 'Mérida' AS estado, 'Santos Marquina' AS nombre
    UNION ALL SELECT 198 AS id, 'Mérida' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 199 AS id, 'Mérida' AS estado, 'Tovar' AS nombre
    UNION ALL SELECT 200 AS id, 'Mérida' AS estado, 'Tulio Febres Cordero' AS nombre
    UNION ALL SELECT 201 AS id, 'Mérida' AS estado, 'Zea' AS nombre
    UNION ALL SELECT 223 AS id, 'Miranda' AS estado, 'Acevedo' AS nombre
    UNION ALL SELECT 224 AS id, 'Miranda' AS estado, 'Andrés Bello' AS nombre
    UNION ALL SELECT 225 AS id, 'Miranda' AS estado, 'Baruta' AS nombre
    UNION ALL SELECT 226 AS id, 'Miranda' AS estado, 'Brión' AS nombre
    UNION ALL SELECT 227 AS id, 'Miranda' AS estado, 'Buroz' AS nombre
    UNION ALL SELECT 228 AS id, 'Miranda' AS estado, 'Carrizal' AS nombre
    UNION ALL SELECT 229 AS id, 'Miranda' AS estado, 'Chacao' AS nombre
    UNION ALL SELECT 230 AS id, 'Miranda' AS estado, 'Cristóbal Rojas' AS nombre
    UNION ALL SELECT 231 AS id, 'Miranda' AS estado, 'El Hatillo' AS nombre
    UNION ALL SELECT 232 AS id, 'Miranda' AS estado, 'Guaicaipuro' AS nombre
    UNION ALL SELECT 233 AS id, 'Miranda' AS estado, 'Independencia' AS nombre
    UNION ALL SELECT 234 AS id, 'Miranda' AS estado, 'Lander' AS nombre
    UNION ALL SELECT 235 AS id, 'Miranda' AS estado, 'Los Salias' AS nombre
    UNION ALL SELECT 236 AS id, 'Miranda' AS estado, 'Páez' AS nombre
    UNION ALL SELECT 237 AS id, 'Miranda' AS estado, 'Paz Castillo' AS nombre
    UNION ALL SELECT 238 AS id, 'Miranda' AS estado, 'Pedro Gual' AS nombre
    UNION ALL SELECT 239 AS id, 'Miranda' AS estado, 'Plaza' AS nombre
    UNION ALL SELECT 240 AS id, 'Miranda' AS estado, 'Simón Bolívar' AS nombre
    UNION ALL SELECT 241 AS id, 'Miranda' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 242 AS id, 'Miranda' AS estado, 'Urdaneta' AS nombre
    UNION ALL SELECT 243 AS id, 'Miranda' AS estado, 'Zamora' AS nombre
    UNION ALL SELECT 258 AS id, 'Monagas' AS estado, 'Acosta' AS nombre
    UNION ALL SELECT 259 AS id, 'Monagas' AS estado, 'Aguasay' AS nombre
    UNION ALL SELECT 260 AS id, 'Monagas' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 261 AS id, 'Monagas' AS estado, 'Caripe' AS nombre
    UNION ALL SELECT 262 AS id, 'Monagas' AS estado, 'Cedeño' AS nombre
    UNION ALL SELECT 263 AS id, 'Monagas' AS estado, 'Ezequiel Zamora' AS nombre
    UNION ALL SELECT 264 AS id, 'Monagas' AS estado, 'Libertador' AS nombre
    UNION ALL SELECT 265 AS id, 'Monagas' AS estado, 'Maturín' AS nombre
    UNION ALL SELECT 266 AS id, 'Monagas' AS estado, 'Piar' AS nombre
    UNION ALL SELECT 267 AS id, 'Monagas' AS estado, 'Punceres' AS nombre
    UNION ALL SELECT 268 AS id, 'Monagas' AS estado, 'Santa Bárbara' AS nombre
    UNION ALL SELECT 269 AS id, 'Monagas' AS estado, 'Sotillo' AS nombre
    UNION ALL SELECT 270 AS id, 'Monagas' AS estado, 'Uracoa' AS nombre
    UNION ALL SELECT 271 AS id, 'Nueva Esparta' AS estado, 'Antolín del Campo' AS nombre
    UNION ALL SELECT 272 AS id, 'Nueva Esparta' AS estado, 'Arismendi' AS nombre
    UNION ALL SELECT 273 AS id, 'Nueva Esparta' AS estado, 'García' AS nombre
    UNION ALL SELECT 274 AS id, 'Nueva Esparta' AS estado, 'Gómez' AS nombre
    UNION ALL SELECT 275 AS id, 'Nueva Esparta' AS estado, 'Maneiro' AS nombre
    UNION ALL SELECT 276 AS id, 'Nueva Esparta' AS estado, 'Marcano' AS nombre
    UNION ALL SELECT 277 AS id, 'Nueva Esparta' AS estado, 'Mariño' AS nombre
    UNION ALL SELECT 278 AS id, 'Nueva Esparta' AS estado, 'Península de Macanao' AS nombre
    UNION ALL SELECT 279 AS id, 'Nueva Esparta' AS estado, 'Tubores' AS nombre
    UNION ALL SELECT 280 AS id, 'Nueva Esparta' AS estado, 'Villalba' AS nombre
    UNION ALL SELECT 281 AS id, 'Nueva Esparta' AS estado, 'Díaz' AS nombre
    UNION ALL SELECT 282 AS id, 'Portuguesa' AS estado, 'Agua Blanca' AS nombre
    UNION ALL SELECT 283 AS id, 'Portuguesa' AS estado, 'Araure' AS nombre
    UNION ALL SELECT 284 AS id, 'Portuguesa' AS estado, 'Esteller' AS nombre
    UNION ALL SELECT 285 AS id, 'Portuguesa' AS estado, 'Guanare' AS nombre
    UNION ALL SELECT 286 AS id, 'Portuguesa' AS estado, 'Guanarito' AS nombre
    UNION ALL SELECT 287 AS id, 'Portuguesa' AS estado, 'Monseñor José Vicente de Unda' AS nombre
    UNION ALL SELECT 288 AS id, 'Portuguesa' AS estado, 'Ospino' AS nombre
    UNION ALL SELECT 289 AS id, 'Portuguesa' AS estado, 'Páez' AS nombre
    UNION ALL SELECT 290 AS id, 'Portuguesa' AS estado, 'Papelón' AS nombre
    UNION ALL SELECT 291 AS id, 'Portuguesa' AS estado, 'San Genaro de Boconoíto' AS nombre
    UNION ALL SELECT 292 AS id, 'Portuguesa' AS estado, 'San Rafael de Onoto' AS nombre
    UNION ALL SELECT 293 AS id, 'Portuguesa' AS estado, 'Santa Rosalía' AS nombre
    UNION ALL SELECT 294 AS id, 'Portuguesa' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 295 AS id, 'Portuguesa' AS estado, 'Turén' AS nombre
    UNION ALL SELECT 296 AS id, 'Sucre' AS estado, 'Andrés Eloy Blanco' AS nombre
    UNION ALL SELECT 297 AS id, 'Sucre' AS estado, 'Andrés Mata' AS nombre
    UNION ALL SELECT 298 AS id, 'Sucre' AS estado, 'Arismendi' AS nombre
    UNION ALL SELECT 299 AS id, 'Sucre' AS estado, 'Benítez' AS nombre
    UNION ALL SELECT 300 AS id, 'Sucre' AS estado, 'Bermúdez' AS nombre
    UNION ALL SELECT 301 AS id, 'Sucre' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 302 AS id, 'Sucre' AS estado, 'Cajigal' AS nombre
    UNION ALL SELECT 303 AS id, 'Sucre' AS estado, 'Cruz Salmerón Acosta' AS nombre
    UNION ALL SELECT 304 AS id, 'Sucre' AS estado, 'Libertador' AS nombre
    UNION ALL SELECT 305 AS id, 'Sucre' AS estado, 'Mariño' AS nombre
    UNION ALL SELECT 306 AS id, 'Sucre' AS estado, 'Mejía' AS nombre
    UNION ALL SELECT 307 AS id, 'Sucre' AS estado, 'Montes' AS nombre
    UNION ALL SELECT 308 AS id, 'Sucre' AS estado, 'Ribero' AS nombre
    UNION ALL SELECT 309 AS id, 'Sucre' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 310 AS id, 'Sucre' AS estado, 'Valdéz' AS nombre
    UNION ALL SELECT 341 AS id, 'Táchira' AS estado, 'Andrés Bello' AS nombre
    UNION ALL SELECT 342 AS id, 'Táchira' AS estado, 'Antonio Rómulo Costa' AS nombre
    UNION ALL SELECT 343 AS id, 'Táchira' AS estado, 'Ayacucho' AS nombre
    UNION ALL SELECT 344 AS id, 'Táchira' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 345 AS id, 'Táchira' AS estado, 'Cárdenas' AS nombre
    UNION ALL SELECT 346 AS id, 'Táchira' AS estado, 'Córdoba' AS nombre
    UNION ALL SELECT 347 AS id, 'Táchira' AS estado, 'Fernández Feo' AS nombre
    UNION ALL SELECT 348 AS id, 'Táchira' AS estado, 'Francisco de Miranda' AS nombre
    UNION ALL SELECT 349 AS id, 'Táchira' AS estado, 'García de Hevia' AS nombre
    UNION ALL SELECT 350 AS id, 'Táchira' AS estado, 'Guásimos' AS nombre
    UNION ALL SELECT 351 AS id, 'Táchira' AS estado, 'Independencia' AS nombre
    UNION ALL SELECT 352 AS id, 'Táchira' AS estado, 'Jáuregui' AS nombre
    UNION ALL SELECT 353 AS id, 'Táchira' AS estado, 'José María Vargas' AS nombre
    UNION ALL SELECT 354 AS id, 'Táchira' AS estado, 'Junín' AS nombre
    UNION ALL SELECT 355 AS id, 'Táchira' AS estado, 'Libertad' AS nombre
    UNION ALL SELECT 356 AS id, 'Táchira' AS estado, 'Libertador' AS nombre
    UNION ALL SELECT 357 AS id, 'Táchira' AS estado, 'Lobatera' AS nombre
    UNION ALL SELECT 358 AS id, 'Táchira' AS estado, 'Michelena' AS nombre
    UNION ALL SELECT 359 AS id, 'Táchira' AS estado, 'Panamericano' AS nombre
    UNION ALL SELECT 360 AS id, 'Táchira' AS estado, 'Pedro María Ureña' AS nombre
    UNION ALL SELECT 361 AS id, 'Táchira' AS estado, 'Rafael Urdaneta' AS nombre
    UNION ALL SELECT 362 AS id, 'Táchira' AS estado, 'Samuel Darío Maldonado' AS nombre
    UNION ALL SELECT 363 AS id, 'Táchira' AS estado, 'San Cristóbal' AS nombre
    UNION ALL SELECT 364 AS id, 'Táchira' AS estado, 'Seboruco' AS nombre
    UNION ALL SELECT 365 AS id, 'Táchira' AS estado, 'Simón Rodríguez' AS nombre
    UNION ALL SELECT 366 AS id, 'Táchira' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 367 AS id, 'Táchira' AS estado, 'Torbes' AS nombre
    UNION ALL SELECT 368 AS id, 'Táchira' AS estado, 'Uribante' AS nombre
    UNION ALL SELECT 369 AS id, 'Táchira' AS estado, 'San Judas Tadeo' AS nombre
    UNION ALL SELECT 370 AS id, 'Trujillo' AS estado, 'Andrés Bello' AS nombre
    UNION ALL SELECT 371 AS id, 'Trujillo' AS estado, 'Boconó' AS nombre
    UNION ALL SELECT 372 AS id, 'Trujillo' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 373 AS id, 'Trujillo' AS estado, 'Candelaria' AS nombre
    UNION ALL SELECT 374 AS id, 'Trujillo' AS estado, 'Carache' AS nombre
    UNION ALL SELECT 375 AS id, 'Trujillo' AS estado, 'Escuque' AS nombre
    UNION ALL SELECT 376 AS id, 'Trujillo' AS estado, 'José Felipe Márquez Cañizalez' AS nombre
    UNION ALL SELECT 377 AS id, 'Trujillo' AS estado, 'Juan Vicente Campos Elías' AS nombre
    UNION ALL SELECT 378 AS id, 'Trujillo' AS estado, 'La Ceiba' AS nombre
    UNION ALL SELECT 379 AS id, 'Trujillo' AS estado, 'Miranda' AS nombre
    UNION ALL SELECT 380 AS id, 'Trujillo' AS estado, 'Monte Carmelo' AS nombre
    UNION ALL SELECT 381 AS id, 'Trujillo' AS estado, 'Motatán' AS nombre
    UNION ALL SELECT 382 AS id, 'Trujillo' AS estado, 'Pampán' AS nombre
    UNION ALL SELECT 383 AS id, 'Trujillo' AS estado, 'Pampanito' AS nombre
    UNION ALL SELECT 384 AS id, 'Trujillo' AS estado, 'Rafael Rangel' AS nombre
    UNION ALL SELECT 385 AS id, 'Trujillo' AS estado, 'San Rafael de Carvajal' AS nombre
    UNION ALL SELECT 386 AS id, 'Trujillo' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 387 AS id, 'Trujillo' AS estado, 'Trujillo' AS nombre
    UNION ALL SELECT 388 AS id, 'Trujillo' AS estado, 'Urdaneta' AS nombre
    UNION ALL SELECT 389 AS id, 'Trujillo' AS estado, 'Valera' AS nombre
    UNION ALL SELECT 390 AS id, 'La Guaira' AS estado, 'Vargas' AS nombre
    UNION ALL SELECT 391 AS id, 'Yaracuy' AS estado, 'Arístides Bastidas' AS nombre
    UNION ALL SELECT 392 AS id, 'Yaracuy' AS estado, 'Bolívar' AS nombre
    UNION ALL SELECT 407 AS id, 'Yaracuy' AS estado, 'Bruzual' AS nombre
    UNION ALL SELECT 408 AS id, 'Yaracuy' AS estado, 'Cocorote' AS nombre
    UNION ALL SELECT 409 AS id, 'Yaracuy' AS estado, 'Independencia' AS nombre
    UNION ALL SELECT 410 AS id, 'Yaracuy' AS estado, 'José Antonio Páez' AS nombre
    UNION ALL SELECT 411 AS id, 'Yaracuy' AS estado, 'La Trinidad' AS nombre
    UNION ALL SELECT 412 AS id, 'Yaracuy' AS estado, 'Manuel Monge' AS nombre
    UNION ALL SELECT 413 AS id, 'Yaracuy' AS estado, 'Nirgua' AS nombre
    UNION ALL SELECT 414 AS id, 'Yaracuy' AS estado, 'Peña' AS nombre
    UNION ALL SELECT 415 AS id, 'Yaracuy' AS estado, 'San Felipe' AS nombre
    UNION ALL SELECT 416 AS id, 'Yaracuy' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 417 AS id, 'Yaracuy' AS estado, 'Urachiche' AS nombre
    UNION ALL SELECT 418 AS id, 'Yaracuy' AS estado, 'José Joaquín Veroes' AS nombre
    UNION ALL SELECT 441 AS id, 'Zulia' AS estado, 'Almirante Padilla' AS nombre
    UNION ALL SELECT 442 AS id, 'Zulia' AS estado, 'Baralt' AS nombre
    UNION ALL SELECT 443 AS id, 'Zulia' AS estado, 'Cabimas' AS nombre
    UNION ALL SELECT 444 AS id, 'Zulia' AS estado, 'Catatumbo' AS nombre
    UNION ALL SELECT 445 AS id, 'Zulia' AS estado, 'Colón' AS nombre
    UNION ALL SELECT 446 AS id, 'Zulia' AS estado, 'Francisco Javier Pulgar' AS nombre
    UNION ALL SELECT 447 AS id, 'Zulia' AS estado, 'Páez' AS nombre
    UNION ALL SELECT 448 AS id, 'Zulia' AS estado, 'Jesús Enrique Losada' AS nombre
    UNION ALL SELECT 449 AS id, 'Zulia' AS estado, 'Jesús María Semprún' AS nombre
    UNION ALL SELECT 450 AS id, 'Zulia' AS estado, 'La Cañada de Urdaneta' AS nombre
    UNION ALL SELECT 451 AS id, 'Zulia' AS estado, 'Lagunillas' AS nombre
    UNION ALL SELECT 452 AS id, 'Zulia' AS estado, 'Machiques de Perijá' AS nombre
    UNION ALL SELECT 453 AS id, 'Zulia' AS estado, 'Mara' AS nombre
    UNION ALL SELECT 454 AS id, 'Zulia' AS estado, 'Maracaibo' AS nombre
    UNION ALL SELECT 455 AS id, 'Zulia' AS estado, 'Miranda' AS nombre
    UNION ALL SELECT 456 AS id, 'Zulia' AS estado, 'Rosario de Perijá' AS nombre
    UNION ALL SELECT 457 AS id, 'Zulia' AS estado, 'San Francisco' AS nombre
    UNION ALL SELECT 458 AS id, 'Zulia' AS estado, 'Santa Rita' AS nombre
    UNION ALL SELECT 459 AS id, 'Zulia' AS estado, 'Simón Bolívar' AS nombre
    UNION ALL SELECT 460 AS id, 'Zulia' AS estado, 'Sucre' AS nombre
    UNION ALL SELECT 461 AS id, 'Zulia' AS estado, 'Valmore Rodríguez' AS nombre
    UNION ALL SELECT 462 AS id, 'Distrito Capital' AS estado, 'Libertador' AS nombre
) AS datos
ON estado.nombre = datos.estado;

INSERT INTO PARROQUIA (cod_par, cod_muni, nombre) VALUES
(1, 1, 'Alto Orinoco'),
(2, 1, 'Huachamacare Acanaña'),
(3, 1, 'Marawaka Toky Shamanaña'),
(4, 1, 'Mavaka Mavaka'),
(5, 1, 'Sierra Parima Parimabé'),
(6, 2, 'Ucata Laja Lisa'),
(7, 2, 'Yapacana Macuruco'),
(8, 2, 'Caname Guarinuma'),
(9, 3, 'Fernando Girón Tovar'),
(10, 3, 'Luis Alberto Gómez'),
(11, 3, 'Pahueña Limón de Parhueña'),
(12, 3, 'Platanillal Platanillal'),
(13, 4, 'Samariapo'),
(14, 4, 'Sipapo'),
(15, 4, 'Munduapo'),
(16, 4, 'Guayapo'),
(17, 5, 'Alto Ventuari'),
(18, 5, 'Medio Ventuari'),
(19, 5, 'Bajo Ventuari'),
(20, 6, 'Victorino'),
(21, 6, 'Comunidad'),
(22, 7, 'Casiquiare'),
(23, 7, 'Cocuy'),
(24, 7, 'San Carlos de Río Negro'),
(25, 7, 'Solano'),
(26, 8, 'Anaco'),
(27, 8, 'San Joaquín'),
(28, 9, 'Cachipo'),
(29, 9, 'Aragua de Barcelona'),
(30, 11, 'Lechería'),
(31, 11, 'El Morro'),
(32, 12, 'Puerto Píritu'),
(33, 12, 'San Miguel'),
(34, 12, 'Sucre'),
(35, 13, 'Valle de Guanape'),
(36, 13, 'Santa Bárbara'),
(37, 14, 'El Chaparro'),
(38, 14, 'Tomás Alfaro'),
(39, 14, 'Calatrava'),
(40, 15, 'Guanta'),
(41, 15, 'Chorrerón'),
(42, 16, 'Mamo'),
(43, 16, 'Soledad'),
(44, 17, 'Mapire'),
(45, 17, 'Piar'),
(46, 17, 'Santa Clara'),
(47, 17, 'San Diego de Cabrutica'),
(48, 17, 'Uverito'),
(49, 17, 'Zuata'),
(50, 18, 'Puerto La Cruz'),
(51, 18, 'Pozuelos'),
(52, 19, 'Onoto'),
(53, 19, 'San Pablo'),
(54, 20, 'San Mateo'),
(55, 20, 'El Carito'),
(56, 20, 'Santa Inés'),
(57, 20, 'La Romereña'),
(58, 21, 'Atapirire'),
(59, 21, 'Boca del Pao'),
(60, 21, 'El Pao'),
(61, 21, 'Pariaguán'),
(62, 22, 'Cantaura'),
(63, 22, 'Libertador'),
(64, 22, 'Santa Rosa'),
(65, 22, 'Urica'),
(66, 23, 'Píritu'),
(67, 23, 'San Francisco'),
(68, 24, 'San José de Guanipa'),
(69, 25, 'Boca de Uchire'),
(70, 25, 'Boca de Chávez'),
(71, 26, 'Pueblo Nuevo'),
(72, 26, 'Santa Ana'),
(73, 27, 'Bergantín'),
(74, 27, 'Caigua'),
(75, 27, 'El Carmen'),
(76, 27, 'El Pilar'),
(77, 27, 'Naricual'),
(78, 27, 'San Crsitóbal'),
(79, 28, 'Edmundo Barrios'),
(80, 28, 'Miguel Otero Silva'),
(81, 29, 'Achaguas'),
(82, 29, 'Apurito'),
(83, 29, 'El Yagual'),
(84, 29, 'Guachara'),
(85, 29, 'Mucuritas'),
(86, 29, 'Queseras del medio'),
(87, 30, 'Biruaca'),
(88, 31, 'Bruzual'),
(89, 31, 'Mantecal'),
(90, 31, 'Quintero'),
(91, 31, 'Rincón Hondo'),
(92, 31, 'San Vicente'),
(93, 32, 'Guasdualito'),
(94, 32, 'Aramendi'),
(95, 32, 'El Amparo'),
(96, 32, 'San Camilo'),
(97, 32, 'Urdaneta'),
(98, 33, 'San Juan de Payara'),
(99, 33, 'Codazzi'),
(100, 33, 'Cunaviche'),
(101, 34, 'Elorza'),
(102, 34, 'La Trinidad'),
(103, 35, 'San Fernando'),
(104, 35, 'El Recreo'),
(105, 35, 'Peñalver'),
(106, 35, 'San Rafael de Atamaica'),
(107, 36, 'Pedro José Ovalles'),
(108, 36, 'Joaquín Crespo'),
(109, 36, 'José Casanova Godoy'),
(110, 36, 'Madre María de San José'),
(111, 36, 'Andrés Eloy Blanco'),
(112, 36, 'Los Tacarigua'),
(113, 36, 'Las Delicias'),
(114, 36, 'Choroní'),
(115, 37, 'Bolívar'),
(116, 38, 'Camatagua'),
(117, 38, 'Carmen de Cura'),
(118, 39, 'Santa Rita'),
(119, 39, 'Francisco de Miranda'),
(120, 39, 'Moseñor Feliciano González'),
(121, 40, 'Santa Cruz'),
(122, 41, 'José Félix Ribas'),
(123, 41, 'Castor Nieves Ríos'),
(124, 41, 'Las Guacamayas'),
(125, 41, 'Pao de Zárate'),
(126, 41, 'Zuata'),
(127, 42, 'José Rafael Revenga'),
(128, 43, 'Palo Negro'),
(129, 43, 'San Martín de Porres'),
(130, 44, 'El Limón'),
(131, 44, 'Caña de Azúcar'),
(132, 45, 'Ocumare de la Costa'),
(133, 46, 'San Casimiro'),
(134, 46, 'Güiripa'),
(135, 46, 'Ollas de Caramacate'),
(136, 46, 'Valle Morín'),
(137, 47, 'San Sebastían'),
(138, 48, 'Turmero'),
(139, 48, 'Arevalo Aponte'),
(140, 48, 'Chuao'),
(141, 48, 'Samán de Güere'),
(142, 48, 'Alfredo Pacheco Miranda'),
(143, 49, 'Santos Michelena'),
(144, 49, 'Tiara'),
(145, 50, 'Cagua'),
(146, 50, 'Bella Vista'),
(147, 51, 'Tovar'),
(148, 52, 'Urdaneta'),
(149, 52, 'Las Peñitas'),
(150, 52, 'San Francisco de Cara'),
(151, 52, 'Taguay'),
(152, 53, 'Zamora'),
(153, 53, 'Magdaleno'),
(154, 53, 'San Francisco de Asís'),
(155, 53, 'Valles de Tucutunemo'),
(156, 53, 'Augusto Mijares'),
(157, 54, 'Sabaneta'),
(158, 54, 'Juan Antonio Rodríguez Domínguez'),
(159, 55, 'El Cantón'),
(160, 55, 'Santa Cruz de Guacas'),
(161, 55, 'Puerto Vivas'),
(162, 56, 'Ticoporo'),
(163, 56, 'Nicolás Pulido'),
(164, 56, 'Andrés Bello'),
(165, 57, 'Arismendi'),
(166, 57, 'Guadarrama'),
(167, 57, 'La Unión'),
(168, 57, 'San Antonio'),
(169, 58, 'Barinas'),
(170, 58, 'Alberto Arvelo Larriva'),
(171, 58, 'San Silvestre'),
(172, 58, 'Santa Inés'),
(173, 58, 'Santa Lucía'),
(174, 58, 'Torumos'),
(175, 58, 'El Carmen'),
(176, 58, 'Rómulo Betancourt'),
(177, 58, 'Corazón de Jesús'),
(178, 58, 'Ramón Ignacio Méndez'),
(179, 58, 'Alto Barinas'),
(180, 58, 'Manuel Palacio Fajardo'),
(181, 58, 'Juan Antonio Rodríguez Domínguez'),
(182, 58, 'Dominga Ortiz de Páez'),
(183, 59, 'Barinitas'),
(184, 59, 'Altamira de Cáceres'),
(185, 59, 'Calderas'),
(186, 60, 'Barrancas'),
(187, 60, 'El Socorro'),
(188, 60, 'Mazparrito'),
(189, 61, 'Santa Bárbara'),
(190, 61, 'Pedro Briceño Méndez'),
(191, 61, 'Ramón Ignacio Méndez'),
(192, 61, 'José Ignacio del Pumar'),
(193, 62, 'Obispos'),
(194, 62, 'Guasimitos'),
(195, 62, 'El Real'),
(196, 62, 'La Luz'),
(197, 63, 'Ciudad Bolívia'),
(198, 63, 'José Ignacio Briceño'),
(199, 63, 'José Félix Ribas'),
(200, 63, 'Páez'),
(201, 64, 'Libertad'),
(202, 64, 'Dolores'),
(203, 64, 'Santa Rosa'),
(204, 64, 'Palacio Fajardo'),
(205, 65, 'Ciudad de Nutrias'),
(206, 65, 'El Regalo'),
(207, 65, 'Puerto Nutrias'),
(208, 65, 'Santa Catalina'),
(209, 66, 'Cachamay'),
(210, 66, 'Chirica'),
(211, 66, 'Dalla Costa'),
(212, 66, 'Once de Abril'),
(213, 66, 'Simón Bolívar'),
(214, 66, 'Unare'),
(215, 66, 'Universidad'),
(216, 66, 'Vista al Sol'),
(217, 66, 'Pozo Verde'),
(218, 66, 'Yocoima'),
(219, 66, '5 de Julio'),
(220, 67, 'Cedeño'),
(221, 67, 'Altagracia'),
(222, 67, 'Ascensión Farreras'),
(223, 67, 'Guaniamo'),
(224, 67, 'La Urbana'),
(225, 67, 'Pijiguaos'),
(226, 68, 'El Callao'),
(227, 69, 'Gran Sabana'),
(228, 69, 'Ikabarú'),
(229, 70, 'Catedral'),
(230, 70, 'Zea'),
(231, 70, 'Orinoco'),
(232, 70, 'José Antonio Páez'),
(233, 70, 'Marhuanta'),
(234, 70, 'Agua Salada'),
(235, 70, 'Vista Hermosa'),
(236, 70, 'La Sabanita'),
(237, 70, 'Panapana'),
(238, 71, 'Andrés Eloy Blanco'),
(239, 71, 'Pedro Cova'),
(240, 72, 'Raúl Leoni'),
(241, 72, 'Barceloneta'),
(242, 72, 'Santa Bárbara'),
(243, 72, 'San Francisco'),
(244, 73, 'Roscio'),
(245, 73, 'Salóm'),
(246, 74, 'Sifontes'),
(247, 74, 'Dalla Costa'),
(248, 74, 'San Isidro'),
(249, 75, 'Sucre'),
(250, 75, 'Aripao'),
(251, 75, 'Guarataro'),
(252, 75, 'Las Majadas'),
(253, 75, 'Moitaco'),
(254, 76, 'Padre Pedro Chien'),
(255, 76, 'Río Grande'),
(256, 77, 'Bejuma'),
(257, 77, 'Canoabo'),
(258, 77, 'Simón Bolívar'),
(259, 78, 'Güigüe'),
(260, 78, 'Carabobo'),
(261, 78, 'Tacarigua'),
(262, 79, 'Mariara'),
(263, 79, 'Aguas Calientes'),
(264, 80, 'Ciudad Alianza'),
(265, 80, 'Guacara'),
(266, 80, 'Yagua'),
(267, 81, 'Morón'),
(268, 81, 'Yagua'),
(269, 82, 'Tocuyito'),
(270, 82, 'Independencia'),
(271, 83, 'Los Guayos'),
(272, 84, 'Miranda'),
(273, 85, 'Montalbán'),
(274, 86, 'Naguanagua'),
(275, 87, 'Bartolomé Salóm'),
(276, 87, 'Democracia'),
(277, 87, 'Fraternidad'),
(278, 87, 'Goaigoaza'),
(279, 87, 'Juan José Flores'),
(280, 87, 'Unión'),
(281, 87, 'Borburata'),
(282, 87, 'Patanemo'),
(283, 88, 'San Diego'),
(284, 89, 'San Joaquín'),
(285, 90, 'Candelaria'),
(286, 90, 'Catedral'),
(287, 90, 'El Socorro'),
(288, 90, 'Miguel Peña'),
(289, 90, 'Rafael Urdaneta'),
(290, 90, 'San Blas'),
(291, 90, 'San José'),
(292, 90, 'Santa Rosa'),
(293, 90, 'Negro Primero'),
(294, 91, 'Cojedes'),
(295, 91, 'Juan de Mata Suárez'),
(296, 92, 'Tinaquillo'),
(297, 93, 'El Baúl'),
(298, 93, 'Sucre'),
(299, 94, 'La Aguadita'),
(300, 94, 'Macapo'),
(301, 95, 'El Pao'),
(302, 96, 'El Amparo'),
(303, 96, 'Libertad de Cojedes'),
(304, 97, 'Rómulo Gallegos'),
(305, 98, 'San Carlos de Austria'),
(306, 98, 'Juan Ángel Bravo'),
(307, 98, 'Manuel Manrique'),
(308, 99, 'General en Jefe José Laurencio Silva'),
(309, 100, 'Curiapo'),
(310, 100, 'Almirante Luis Brión'),
(311, 100, 'Francisco Aniceto Lugo'),
(312, 100, 'Manuel Renaud'),
(313, 100, 'Padre Barral'),
(314, 100, 'Santos de Abelgas'),
(315, 101, 'Imataca'),
(316, 101, 'Cinco de Julio'),
(317, 101, 'Juan Bautista Arismendi'),
(318, 101, 'Manuel Piar'),
(319, 101, 'Rómulo Gallegos'),
(320, 102, 'Pedernales'),
(321, 102, 'Luis Beltrán Prieto Figueroa'),
(322, 103, 'San José (Delta Amacuro)'),
(323, 103, 'José Vidal Marcano'),
(324, 103, 'Juan Millán'),
(325, 103, 'Leonardo Ruíz Pineda'),
(326, 103, 'Mariscal Antonio José de Sucre'),
(327, 103, 'Monseñor Argimiro García'),
(328, 103, 'San Rafael (Delta Amacuro)'),
(329, 103, 'Virgen del Valle'),
(330, 10, 'Clarines'),
(331, 10, 'Guanape'),
(332, 10, 'Sabana de Uchire'),
(333, 104, 'Capadare'),
(334, 104, 'La Pastora'),
(335, 104, 'Libertador'),
(336, 104, 'San Juan de los Cayos'),
(337, 105, 'Aracua'),
(338, 105, 'La Peña'),
(339, 105, 'San Luis'),
(340, 106, 'Bariro'),
(341, 106, 'Borojó'),
(342, 106, 'Capatárida'),
(343, 106, 'Guajiro'),
(344, 106, 'Seque'),
(345, 106, 'Zazárida'),
(346, 106, 'Valle de Eroa'),
(347, 107, 'Cacique Manaure'),
(348, 108, 'Norte'),
(349, 108, 'Carirubana'),
(350, 108, 'Santa Ana'),
(351, 108, 'Urbana Punta Cardón'),
(352, 109, 'La Vela de Coro'),
(353, 109, 'Acurigua'),
(354, 109, 'Guaibacoa'),
(355, 109, 'Las Calderas'),
(356, 109, 'Macoruca'),
(357, 110, 'Dabajuro'),
(358, 111, 'Agua Clara'),
(359, 111, 'Avaria'),
(360, 111, 'Pedregal'),
(361, 111, 'Piedra Grande'),
(362, 111, 'Purureche'),
(363, 112, 'Adaure'),
(364, 112, 'Adícora'),
(365, 112, 'Baraived'),
(366, 112, 'Buena Vista'),
(367, 112, 'Jadacaquiva'),
(368, 112, 'El Vínculo'),
(369, 112, 'El Hato'),
(370, 112, 'Moruy'),
(371, 112, 'Pueblo Nuevo'),
(372, 113, 'Agua Larga'),
(373, 113, 'El Paují'),
(374, 113, 'Independencia'),
(375, 113, 'Mapararí'),
(376, 114, 'Agua Linda'),
(377, 114, 'Araurima'),
(378, 114, 'Jacura'),
(379, 115, 'Tucacas'),
(380, 115, 'Boca de Aroa'),
(381, 116, 'Los Taques'),
(382, 116, 'Judibana'),
(383, 117, 'Mene de Mauroa'),
(384, 117, 'San Félix'),
(385, 117, 'Casigua'),
(386, 118, 'Guzmán Guillermo'),
(387, 118, 'Mitare'),
(388, 118, 'Río Seco'),
(389, 118, 'Sabaneta'),
(390, 118, 'San Antonio'),
(391, 118, 'San Gabriel'),
(392, 118, 'Santa Ana'),
(393, 119, 'Boca del Tocuyo'),
(394, 119, 'Chichiriviche'),
(395, 119, 'Tocuyo de la Costa'),
(396, 120, 'Palmasola'),
(397, 121, 'Cabure'),
(398, 121, 'Colina'),
(399, 121, 'Curimagua'),
(400, 122, 'San José de la Costa'),
(401, 122, 'Píritu'),
(402, 123, 'San Francisco'),
(403, 124, 'Sucre'),
(404, 124, 'Pecaya'),
(405, 125, 'Tocópero'),
(406, 126, 'El Charal'),
(407, 126, 'Las Vegas del Tuy'),
(408, 126, 'Santa Cruz de Bucaral'),
(409, 127, 'Bruzual'),
(410, 127, 'Urumaco'),
(411, 128, 'Puerto Cumarebo'),
(412, 128, 'La Ciénaga'),
(413, 128, 'La Soledad'),
(414, 128, 'Pueblo Cumarebo'),
(415, 128, 'Zazárida'),
(416, 113, 'Churuguara'),
(417, 129, 'Camaguán'),
(418, 129, 'Puerto Miranda'),
(419, 129, 'Uverito'),
(420, 130, 'Chaguaramas'),
(421, 131, 'El Socorro'),
(422, 132, 'Tucupido'),
(423, 132, 'San Rafael de Laya'),
(424, 133, 'Altagracia de Orituco'),
(425, 133, 'San Rafael de Orituco'),
(426, 133, 'San Francisco Javier de Lezama'),
(427, 133, 'Paso Real de Macaira'),
(428, 133, 'Carlos Soublette'),
(429, 133, 'San Francisco de Macaira'),
(430, 133, 'Libertad de Orituco'),
(431, 134, 'Cantaclaro'),
(432, 134, 'San Juan de los Morros'),
(433, 134, 'Parapara'),
(434, 135, 'El Sombrero'),
(435, 135, 'Sosa'),
(436, 136, 'Las Mercedes'),
(437, 136, 'Cabruta'),
(438, 136, 'Santa Rita de Manapire'),
(439, 137, 'Valle de la Pascua'),
(440, 137, 'Espino'),
(441, 138, 'San José de Unare'),
(442, 138, 'Zaraza'),
(443, 139, 'San José de Tiznados'),
(444, 139, 'San Francisco de Tiznados'),
(445, 139, 'San Lorenzo de Tiznados'),
(446, 139, 'Ortiz'),
(447, 140, 'Guayabal'),
(448, 140, 'Cazorla'),
(449, 141, 'San José de Guaribe'),
(450, 141, 'Uveral'),
(451, 142, 'Santa María de Ipire'),
(452, 142, 'Altamira'),
(453, 143, 'El Calvario'),
(454, 143, 'El Rastro'),
(455, 143, 'Guardatinajas'),
(456, 143, 'Capital Urbana Calabozo'),
(457, 144, 'Quebrada Honda de Guache'),
(458, 144, 'Pío Tamayo'),
(459, 144, 'Yacambú'),
(460, 145, 'Fréitez'),
(461, 145, 'José María Blanco'),
(462, 146, 'Catedral'),
(463, 146, 'Concepción'),
(464, 146, 'El Cují'),
(465, 146, 'Juan de Villegas'),
(466, 146, 'Santa Rosa'),
(467, 146, 'Tamaca'),
(468, 146, 'Unión'),
(469, 146, 'Aguedo Felipe Alvarado'),
(470, 146, 'Buena Vista'),
(471, 146, 'Juárez'),
(472, 147, 'Juan Bautista Rodríguez'),
(473, 147, 'Cuara'),
(474, 147, 'Diego de Lozada'),
(475, 147, 'Paraíso de San José'),
(476, 147, 'San Miguel'),
(477, 147, 'Tintorero'),
(478, 147, 'José Bernardo Dorante'),
(479, 147, 'Coronel Mariano Peraza '),
(480, 148, 'Bolívar'),
(481, 148, 'Anzoátegui'),
(482, 148, 'Guarico'),
(483, 148, 'Hilario Luna y Luna'),
(484, 148, 'Humocaro Alto'),
(485, 148, 'Humocaro Bajo'),
(486, 148, 'La Candelaria'),
(487, 148, 'Morán'),
(488, 149, 'Cabudare'),
(489, 149, 'José Gregorio Bastidas'),
(490, 149, 'Agua Viva'),
(491, 150, 'Sarare'),
(492, 150, 'Buría'),
(493, 150, 'Gustavo Vegas León'),
(494, 151, 'Trinidad Samuel'),
(495, 151, 'Antonio Díaz'),
(496, 151, 'Camacaro'),
(497, 151, 'Castañeda'),
(498, 151, 'Cecilio Zubillaga'),
(499, 151, 'Chiquinquirá'),
(500, 151, 'El Blanco'),
(501, 151, 'Espinoza de los Monteros'),
(502, 151, 'Lara'),
(503, 151, 'Las Mercedes'),
(504, 151, 'Manuel Morillo'),
(505, 151, 'Montaña Verde'),
(506, 151, 'Montes de Oca'),
(507, 151, 'Torres'),
(508, 151, 'Heriberto Arroyo'),
(509, 151, 'Reyes Vargas'),
(510, 151, 'Altagracia'),
(511, 152, 'Siquisique'),
(512, 152, 'Moroturo'),
(513, 152, 'San Miguel'),
(514, 152, 'Xaguas'),
(515, 179, 'Presidente Betancourt'),
(516, 179, 'Presidente Páez'),
(517, 179, 'Presidente Rómulo Gallegos'),
(518, 179, 'Gabriel Picón González'),
(519, 179, 'Héctor Amable Mora'),
(520, 179, 'José Nucete Sardi'),
(521, 179, 'Pulido Méndez'),
(522, 180, 'La Azulita'),
(523, 181, 'Santa Cruz de Mora'),
(524, 181, 'Mesa Bolívar'),
(525, 181, 'Mesa de Las Palmas'),
(526, 182, 'Aricagua'),
(527, 182, 'San Antonio'),
(528, 183, 'Canagua'),
(529, 183, 'Capurí'),
(530, 183, 'Chacantá'),
(531, 183, 'El Molino'),
(532, 183, 'Guaimaral'),
(533, 183, 'Mucutuy'),
(534, 183, 'Mucuchachí'),
(535, 184, 'Fernández Peña'),
(536, 184, 'Matriz'),
(537, 184, 'Montalbán'),
(538, 184, 'Acequias'),
(539, 184, 'Jají'),
(540, 184, 'La Mesa'),
(541, 184, 'San José del Sur'),
(542, 185, 'Tucaní'),
(543, 185, 'Florencio Ramírez'),
(544, 186, 'Santo Domingo'),
(545, 186, 'Las Piedras'),
(546, 187, 'Guaraque'),
(547, 187, 'Mesa de Quintero'),
(548, 187, 'Río Negro'),
(549, 188, 'Arapuey'),
(550, 188, 'Palmira'),
(551, 189, 'San Cristóbal de Torondoy'),
(552, 189, 'Torondoy'),
(553, 190, 'Antonio Spinetti Dini'),
(554, 190, 'Arias'),
(555, 190, 'Caracciolo Parra Pérez'),
(556, 190, 'Domingo Peña'),
(557, 190, 'El Llano'),
(558, 190, 'Gonzalo Picón Febres'),
(559, 190, 'Jacinto Plaza'),
(560, 190, 'Juan Rodríguez Suárez'),
(561, 190, 'Lasso de la Vega'),
(562, 190, 'Mariano Picón Salas'),
(563, 190, 'Milla'),
(564, 190, 'Osuna Rodríguez'),
(565, 190, 'Sagrario'),
(566, 190, 'El Morro'),
(567, 190, 'Los Nevados'),
(568, 191, 'Andrés Eloy Blanco'),
(569, 191, 'La Venta'),
(570, 191, 'Piñango'),
(571, 191, 'Timotes'),
(572, 192, 'Eloy Paredes'),
(573, 192, 'San Rafael de Alcázar'),
(574, 192, 'Santa Elena de Arenales'),
(575, 193, 'Santa María de Caparo'),
(576, 194, 'Pueblo Llano'),
(577, 195, 'Cacute'),
(578, 195, 'La Toma'),
(579, 195, 'Mucuchíes'),
(580, 195, 'Mucurubá'),
(581, 195, 'San Rafael'),
(582, 196, 'Gerónimo Maldonado'),
(583, 196, 'Bailadores'),
(584, 197, 'Tabay'),
(585, 198, 'Chiguará'),
(586, 198, 'Estánquez'),
(587, 198, 'Lagunillas'),
(588, 198, 'La Trampa'),
(589, 198, 'Pueblo Nuevo del Sur'),
(590, 198, 'San Juan'),
(591, 199, 'El Amparo'),
(592, 199, 'El Llano'),
(593, 199, 'San Francisco'),
(594, 199, 'Tovar'),
(595, 200, 'Independencia'),
(596, 200, 'María de la Concepción Palacios Blanco'),
(597, 200, 'Nueva Bolivia'),
(598, 200, 'Santa Apolonia'),
(599, 201, 'Caño El Tigre'),
(600, 201, 'Zea'),
(601, 223, 'Aragüita'),
(602, 223, 'Arévalo González'),
(603, 223, 'Capaya'),
(604, 223, 'Caucagua'),
(605, 223, 'Panaquire'),
(606, 223, 'Ribas'),
(607, 223, 'El Café'),
(608, 223, 'Marizapa'),
(609, 224, 'Cumbo'),
(610, 224, 'San José de Barlovento'),
(611, 225, 'El Cafetal'),
(612, 225, 'Las Minas'),
(613, 225, 'Nuestra Señora del Rosario'),
(614, 226, 'Higuerote'),
(615, 226, 'Curiepe'),
(616, 226, 'Tacarigua de Brión'),
(617, 227, 'Mamporal'),
(618, 228, 'Carrizal'),
(619, 229, 'Chacao'),
(620, 230, 'Charallave'),
(621, 230, 'Las Brisas'),
(622, 231, 'El Hatillo'),
(623, 232, 'Altagracia de la Montaña'),
(624, 232, 'Cecilio Acosta'),
(625, 232, 'Los Teques'),
(626, 232, 'El Jarillo'),
(627, 232, 'San Pedro'),
(628, 232, 'Tácata'),
(629, 232, 'Paracotos'),
(630, 233, 'Cartanal'),
(631, 233, 'Santa Teresa del Tuy'),
(632, 234, 'La Democracia'),
(633, 234, 'Ocumare del Tuy'),
(634, 234, 'Santa Bárbara'),
(635, 235, 'San Antonio de los Altos'),
(636, 236, 'Río Chico'),
(637, 236, 'El Guapo'),
(638, 236, 'Tacarigua de la Laguna'),
(639, 236, 'Paparo'),
(640, 236, 'San Fernando del Guapo'),
(641, 237, 'Santa Lucía del Tuy'),
(642, 238, 'Cúpira'),
(643, 238, 'Machurucuto'),
(644, 239, 'Guarenas'),
(645, 240, 'San Antonio de Yare'),
(646, 240, 'San Francisco de Yare'),
(647, 241, 'Leoncio Martínez'),
(648, 241, 'Petare'),
(649, 241, 'Caucagüita'),
(650, 241, 'Filas de Mariche'),
(651, 241, 'La Dolorita'),
(652, 242, 'Cúa'),
(653, 242, 'Nueva Cúa'),
(654, 243, 'Guatire'),
(655, 243, 'Bolívar'),
(656, 258, 'San Antonio de Maturín'),
(657, 258, 'San Francisco de Maturín'),
(658, 259, 'Aguasay'),
(659, 260, 'Caripito'),
(660, 261, 'El Guácharo'),
(661, 261, 'La Guanota'),
(662, 261, 'Sabana de Piedra'),
(663, 261, 'San Agustín'),
(664, 261, 'Teresen'),
(665, 261, 'Caripe'),
(666, 262, 'Areo'),
(667, 262, 'Capital Cedeño'),
(668, 262, 'San Félix de Cantalicio'),
(669, 262, 'Viento Fresco'),
(670, 263, 'El Tejero'),
(671, 263, 'Punta de Mata'),
(672, 264, 'Chaguaramas'),
(673, 264, 'Las Alhuacas'),
(674, 264, 'Tabasca'),
(675, 264, 'Temblador'),
(676, 265, 'Alto de los Godos'),
(677, 265, 'Boquerón'),
(678, 265, 'Las Cocuizas'),
(679, 265, 'La Cruz'),
(680, 265, 'San Simón'),
(681, 265, 'El Corozo'),
(682, 265, 'El Furrial'),
(683, 265, 'Jusepín'),
(684, 265, 'La Pica'),
(685, 265, 'San Vicente'),
(686, 266, 'Aparicio'),
(687, 266, 'Aragua de Maturín'),
(688, 266, 'Chaguamal'),
(689, 266, 'El Pinto'),
(690, 266, 'Guanaguana'),
(691, 266, 'La Toscana'),
(692, 266, 'Taguaya'),
(693, 267, 'Cachipo'),
(694, 267, 'Quiriquire'),
(695, 268, 'Santa Bárbara'),
(696, 269, 'Barrancas'),
(697, 269, 'Los Barrancos de Fajardo'),
(698, 270, 'Uracoa'),
(699, 271, 'Antolín del Campo'),
(700, 272, 'Arismendi'),
(701, 273, 'García'),
(702, 273, 'Francisco Fajardo'),
(703, 274, 'Bolívar'),
(704, 274, 'Guevara'),
(705, 274, 'Matasiete'),
(706, 274, 'Santa Ana'),
(707, 274, 'Sucre'),
(708, 275, 'Aguirre'),
(709, 275, 'Maneiro'),
(710, 276, 'Adrián'),
(711, 276, 'Juan Griego'),
(712, 276, 'Yaguaraparo'),
(713, 277, 'Porlamar'),
(714, 278, 'San Francisco de Macanao'),
(715, 278, 'Boca de Río'),
(716, 279, 'Tubores'),
(717, 279, 'Los Baleales'),
(718, 280, 'Vicente Fuentes'),
(719, 280, 'Villalba'),
(720, 281, 'San Juan Bautista'),
(721, 281, 'Zabala'),
(722, 283, 'Capital Araure'),
(723, 283, 'Río Acarigua'),
(724, 284, 'Capital Esteller'),
(725, 284, 'Uveral'),
(726, 285, 'Guanare'),
(727, 285, 'Córdoba'),
(728, 285, 'San José de la Montaña'),
(729, 285, 'San Juan de Guanaguanare'),
(730, 285, 'Virgen de la Coromoto'),
(731, 286, 'Guanarito'),
(732, 286, 'Trinidad de la Capilla'),
(733, 286, 'Divina Pastora'),
(734, 287, 'Monseñor José Vicente de Unda'),
(735, 287, 'Peña Blanca'),
(736, 288, 'Capital Ospino'),
(737, 288, 'Aparición'),
(738, 288, 'La Estación'),
(739, 289, 'Páez'),
(740, 289, 'Payara'),
(741, 289, 'Pimpinela'),
(742, 289, 'Ramón Peraza'),
(743, 290, 'Papelón'),
(744, 290, 'Caño Delgadito'),
(745, 291, 'San Genaro de Boconoito'),
(746, 291, 'Antolín Tovar'),
(747, 292, 'San Rafael de Onoto'),
(748, 292, 'Santa Fe'),
(749, 292, 'Thermo Morles'),
(750, 293, 'Santa Rosalía'),
(751, 293, 'Florida'),
(752, 294, 'Sucre'),
(753, 294, 'Concepción'),
(754, 294, 'San Rafael de Palo Alzado'),
(755, 294, 'Uvencio Antonio Velásquez'),
(756, 294, 'San José de Saguaz'),
(757, 294, 'Villa Rosa'),
(758, 295, 'Turén'),
(759, 295, 'Canelones'),
(760, 295, 'Santa Cruz'),
(761, 295, 'San Isidro Labrador'),
(762, 296, 'Mariño'),
(763, 296, 'Rómulo Gallegos'),
(764, 297, 'San José de Aerocuar'),
(765, 297, 'Tavera Acosta'),
(766, 298, 'Río Caribe'),
(767, 298, 'Antonio José de Sucre'),
(768, 298, 'El Morro de Puerto Santo'),
(769, 298, 'Puerto Santo'),
(770, 298, 'San Juan de las Galdonas'),
(771, 299, 'El Pilar'),
(772, 299, 'El Rincón'),
(773, 299, 'General Francisco Antonio Váquez'),
(774, 299, 'Guaraúnos'),
(775, 299, 'Tunapuicito'),
(776, 299, 'Unión'),
(777, 300, 'Santa Catalina'),
(778, 300, 'Santa Rosa'),
(779, 300, 'Santa Teresa'),
(780, 300, 'Bolívar'),
(781, 300, 'Maracapana'),
(782, 302, 'Libertad'),
(783, 302, 'El Paujil'),
(784, 302, 'Yaguaraparo'),
(785, 303, 'Cruz Salmerón Acosta'),
(786, 303, 'Chacopata'),
(787, 303, 'Manicuare'),
(788, 304, 'Tunapuy'),
(789, 304, 'Campo Elías'),
(790, 305, 'Irapa'),
(791, 305, 'Campo Claro'),
(792, 305, 'Maraval'),
(793, 305, 'San Antonio de Irapa'),
(794, 305, 'Soro'),
(795, 306, 'Mejía'),
(796, 307, 'Cumanacoa'),
(797, 307, 'Arenas'),
(798, 307, 'Aricagua'),
(799, 307, 'Cogollar'),
(800, 307, 'San Fernando'),
(801, 307, 'San Lorenzo'),
(802, 308, 'Villa Frontado (Muelle de Cariaco)'),
(803, 308, 'Catuaro'),
(804, 308, 'Rendón'),
(805, 308, 'San Cruz'),
(806, 308, 'Santa María'),
(807, 309, 'Altagracia'),
(808, 309, 'Santa Inés'),
(809, 309, 'Valentín Valiente'),
(810, 309, 'Ayacucho'),
(811, 309, 'San Juan'),
(812, 309, 'Raúl Leoni'),
(813, 309, 'Gran Mariscal'),
(814, 310, 'Cristóbal Colón'),
(815, 310, 'Bideau'),
(816, 310, 'Punta de Piedras'),
(817, 310, 'Güiria'),
(818, 341, 'Andrés Bello'),
(819, 342, 'Antonio Rómulo Costa'),
(820, 343, 'Ayacucho'),
(821, 343, 'Rivas Berti'),
(822, 343, 'San Pedro del Río'),
(823, 344, 'Bolívar'),
(824, 344, 'Palotal'),
(825, 344, 'General Juan Vicente Gómez'),
(826, 344, 'Isaías Medina Angarita'),
(827, 345, 'Cárdenas'),
(828, 345, 'Amenodoro Ángel Lamus'),
(829, 345, 'La Florida'),
(830, 346, 'Córdoba'),
(831, 347, 'Fernández Feo'),
(832, 347, 'Alberto Adriani'),
(833, 347, 'Santo Domingo'),
(834, 348, 'Francisco de Miranda'),
(835, 349, 'García de Hevia'),
(836, 349, 'Boca de Grita'),
(837, 349, 'José Antonio Páez'),
(838, 350, 'Guásimos'),
(839, 351, 'Independencia'),
(840, 351, 'Juan Germán Roscio'),
(841, 351, 'Román Cárdenas'),
(842, 352, 'Jáuregui'),
(843, 352, 'Emilio Constantino Guerrero'),
(844, 352, 'Monseñor Miguel Antonio Salas'),
(845, 353, 'José María Vargas'),
(846, 354, 'Junín'),
(847, 354, 'La Petrólea'),
(848, 354, 'Quinimarí'),
(849, 354, 'Bramón'),
(850, 355, 'Libertad'),
(851, 355, 'Cipriano Castro'),
(852, 355, 'Manuel Felipe Rugeles'),
(853, 356, 'Libertador'),
(854, 356, 'Doradas'),
(855, 356, 'Emeterio Ochoa'),
(856, 356, 'San Joaquín de Navay'),
(857, 357, 'Lobatera'),
(858, 357, 'Constitución'),
(859, 358, 'Michelena'),
(860, 359, 'Panamericano'),
(861, 359, 'La Palmita'),
(862, 360, 'Pedro María Ureña'),
(863, 360, 'Nueva Arcadia'),
(864, 361, 'Delicias'),
(865, 361, 'Pecaya'),
(866, 362, 'Samuel Darío Maldonado'),
(867, 362, 'Boconó'),
(868, 362, 'Hernández'),
(869, 363, 'La Concordia'),
(870, 363, 'San Juan Bautista'),
(871, 363, 'Pedro María Morantes'),
(872, 363, 'San Sebastián'),
(873, 363, 'Dr. Francisco Romero Lobo'),
(874, 364, 'Seboruco'),
(875, 365, 'Simón Rodríguez'),
(876, 366, 'Sucre'),
(877, 366, 'Eleazar López Contreras'),
(878, 366, 'San Pablo'),
(879, 367, 'Torbes'),
(880, 368, 'Uribante'),
(881, 368, 'Cárdenas'),
(882, 368, 'Juan Pablo Peñalosa'),
(883, 368, 'Potosí'),
(884, 369, 'San Judas Tadeo'),
(885, 370, 'Araguaney'),
(886, 370, 'El Jaguito'),
(887, 370, 'La Esperanza'),
(888, 370, 'Santa Isabel'),
(889, 371, 'Boconó'),
(890, 371, 'El Carmen'),
(891, 371, 'Mosquey'),
(892, 371, 'Ayacucho'),
(893, 371, 'Burbusay'),
(894, 371, 'General Ribas'),
(895, 371, 'Guaramacal'),
(896, 371, 'Vega de Guaramacal'),
(897, 371, 'Monseñor Jáuregui'),
(898, 371, 'Rafael Rangel'),
(899, 371, 'San Miguel'),
(900, 371, 'San José'),
(901, 372, 'Sabana Grande'),
(902, 372, 'Cheregüé'),
(903, 372, 'Granados'),
(904, 373, 'Arnoldo Gabaldón'),
(905, 373, 'Bolivia'),
(906, 373, 'Carrillo'),
(907, 373, 'Cegarra'),
(908, 373, 'Chejendé'),
(909, 373, 'Manuel Salvador Ulloa'),
(910, 373, 'San José'),
(911, 374, 'Carache'),
(912, 374, 'La Concepción'),
(913, 374, 'Cuicas'),
(914, 374, 'Panamericana'),
(915, 374, 'Santa Cruz'),
(916, 375, 'Escuque'),
(917, 375, 'La Unión'),
(918, 375, 'Santa Rita'),
(919, 375, 'Sabana Libre'),
(920, 376, 'El Socorro'),
(921, 376, 'Los Caprichos'),
(922, 376, 'Antonio José de Sucre'),
(923, 377, 'Campo Elías'),
(924, 377, 'Arnoldo Gabaldón'),
(925, 378, 'Santa Apolonia'),
(926, 378, 'El Progreso'),
(927, 378, 'La Ceiba'),
(928, 378, 'Tres de Febrero'),
(929, 379, 'El Dividive'),
(930, 379, 'Agua Santa'),
(931, 379, 'Agua Caliente'),
(932, 379, 'El Cenizo'),
(933, 379, 'Valerita'),
(934, 380, 'Monte Carmelo'),
(935, 380, 'Buena Vista'),
(936, 380, 'Santa María del Horcón'),
(937, 381, 'Motatán'),
(938, 381, 'El Baño'),
(939, 381, 'Jalisco'),
(940, 382, 'Pampán'),
(941, 382, 'Flor de Patria'),
(942, 382, 'La Paz'),
(943, 382, 'Santa Ana'),
(944, 383, 'Pampanito'),
(945, 383, 'La Concepción'),
(946, 383, 'Pampanito II'),
(947, 384, 'Betijoque'),
(948, 384, 'José Gregorio Hernández'),
(949, 384, 'La Pueblita'),
(950, 384, 'Los Cedros'),
(951, 385, 'Carvajal'),
(952, 385, 'Campo Alegre'),
(953, 385, 'Antonio Nicolás Briceño'),
(954, 385, 'José Leonardo Suárez'),
(955, 386, 'Sabana de Mendoza'),
(956, 386, 'Junín'),
(957, 386, 'Valmore Rodríguez'),
(958, 386, 'El Paraíso'),
(959, 387, 'Andrés Linares'),
(960, 387, 'Chiquinquirá'),
(961, 387, 'Cristóbal Mendoza'),
(962, 387, 'Cruz Carrillo'),
(963, 387, 'Matriz'),
(964, 387, 'Monseñor Carrillo'),
(965, 387, 'Tres Esquinas'),
(966, 388, 'Cabimbú'),
(967, 388, 'Jajó'),
(968, 388, 'La Mesa de Esnujaque'),
(969, 388, 'Santiago'),
(970, 388, 'Tuñame'),
(971, 388, 'La Quebrada'),
(972, 389, 'Juan Ignacio Montilla'),
(973, 389, 'La Beatriz'),
(974, 389, 'La Puerta'),
(975, 389, 'Mendoza del Valle de Momboy'),
(976, 389, 'Mercedes Díaz'),
(977, 389, 'San Luis'),
(978, 390, 'Caraballeda'),
(979, 390, 'Carayaca'),
(980, 390, 'Carlos Soublette'),
(981, 390, 'Caruao Chuspa'),
(982, 390, 'Catia La Mar'),
(983, 390, 'El Junko'),
(984, 390, 'La Guaira'),
(985, 390, 'Macuto'),
(986, 390, 'Maiquetía'),
(987, 390, 'Naiguatá'),
(988, 390, 'Urimare'),
(989, 391, 'Arístides Bastidas'),
(990, 392, 'Bolívar'),
(991, 407, 'Chivacoa'),
(992, 407, 'Campo Elías'),
(993, 408, 'Cocorote'),
(994, 409, 'Independencia'),
(995, 410, 'José Antonio Páez'),
(996, 411, 'La Trinidad'),
(997, 412, 'Manuel Monge'),
(998, 413, 'Salóm'),
(999, 413, 'Temerla'),
(1000, 413, 'Nirgua'),
(1001, 414, 'San Andrés'),
(1002, 414, 'Yaritagua'),
(1003, 415, 'San Javier'),
(1004, 415, 'Albarico'),
(1005, 415, 'San Felipe'),
(1006, 416, 'Sucre'),
(1007, 417, 'Urachiche'),
(1008, 418, 'El Guayabo'),
(1009, 418, 'Farriar'),
(1010, 441, 'Isla de Toas'),
(1011, 441, 'Monagas'),
(1012, 442, 'San Timoteo'),
(1013, 442, 'General Urdaneta'),
(1014, 442, 'Libertador'),
(1015, 442, 'Marcelino Briceño'),
(1016, 442, 'Pueblo Nuevo'),
(1017, 442, 'Manuel Guanipa Matos'),
(1018, 443, 'Ambrosio'),
(1019, 443, 'Carmen Herrera'),
(1020, 443, 'La Rosa'),
(1021, 443, 'Germán Ríos Linares'),
(1022, 443, 'San Benito'),
(1023, 443, 'Rómulo Betancourt'),
(1024, 443, 'Jorge Hernández'),
(1025, 443, 'Punta Gorda'),
(1026, 443, 'Arístides Calvani'),
(1027, 444, 'Encontrados'),
(1028, 444, 'Udón Pérez'),
(1029, 445, 'Moralito'),
(1030, 445, 'San Carlos del Zulia'),
(1031, 445, 'Santa Cruz del Zulia'),
(1032, 445, 'Santa Bárbara'),
(1033, 445, 'Urribarrí'),
(1034, 446, 'Carlos Quevedo'),
(1035, 446, 'Francisco Javier Pulgar'),
(1036, 446, 'Simón Rodríguez'),
(1037, 446, 'Guamo-Gavilanes'),
(1038, 448, 'La Concepción'),
(1039, 448, 'San José'),
(1040, 448, 'Mariano Parra León'),
(1041, 448, 'José Ramón Yépez'),
(1042, 449, 'Jesús María Semprún'),
(1043, 449, 'Barí'),
(1044, 450, 'Concepción'),
(1045, 450, 'Andrés Bello'),
(1046, 450, 'Chiquinquirá'),
(1047, 450, 'El Carmelo'),
(1048, 450, 'Potreritos'),
(1049, 451, 'Libertad'),
(1050, 451, 'Alonso de Ojeda'),
(1051, 451, 'Venezuela'),
(1052, 451, 'Eleazar López Contreras'),
(1053, 451, 'Campo Lara'),
(1054, 452, 'Bartolomé de las Casas'),
(1055, 452, 'Libertad'),
(1056, 452, 'Río Negro'),
(1057, 452, 'San José de Perijá'),
(1058, 453, 'San Rafael'),
(1059, 453, 'La Sierrita'),
(1060, 453, 'Las Parcelas'),
(1061, 453, 'Luis de Vicente'),
(1062, 453, 'Monseñor Marcos Sergio Godoy'),
(1063, 453, 'Ricaurte'),
(1064, 453, 'Tamare'),
(1065, 454, 'Antonio Borjas Romero'),
(1066, 454, 'Bolívar'),
(1067, 454, 'Cacique Mara'),
(1068, 454, 'Carracciolo Parra Pérez'),
(1069, 454, 'Cecilio Acosta'),
(1070, 454, 'Cristo de Aranza'),
(1071, 454, 'Coquivacoa'),
(1072, 454, 'Chiquinquirá'),
(1073, 454, 'Francisco Eugenio Bustamante'),
(1074, 454, 'Idelfonzo Vásquez'),
(1075, 454, 'Juana de Ávila'),
(1076, 454, 'Luis Hurtado Higuera'),
(1077, 454, 'Manuel Dagnino'),
(1078, 454, 'Olegario Villalobos'),
(1079, 454, 'Raúl Leoni'),
(1080, 454, 'Santa Lucía'),
(1081, 454, 'Venancio Pulgar'),
(1082, 454, 'San Isidro'),
(1083, 455, 'Altagracia'),
(1084, 455, 'Faría'),
(1085, 455, 'Ana María Campos'),
(1086, 455, 'San Antonio'),
(1087, 455, 'San José'),
(1088, 456, 'Donaldo García'),
(1089, 456, 'El Rosario'),
(1090, 456, 'Sixto Zambrano'),
(1091, 457, 'San Francisco'),
(1092, 457, 'El Bajo'),
(1093, 457, 'Domitila Flores'),
(1094, 457, 'Francisco Ochoa'),
(1095, 457, 'Los Cortijos'),
(1096, 457, 'Marcial Hernández'),
(1097, 458, 'Santa Rita'),
(1098, 458, 'El Mene'),
(1099, 458, 'Pedro Lucas Urribarrí'),
(1100, 458, 'José Cenobio Urribarrí'),
(1101, 459, 'Rafael Maria Baralt'),
(1102, 459, 'Manuel Manrique'),
(1103, 459, 'Rafael Urdaneta'),
(1104, 460, 'Bobures'),
(1105, 460, 'Gibraltar'),
(1106, 460, 'Heras'),
(1107, 460, 'Monseñor Arturo Álvarez'),
(1108, 460, 'Rómulo Gallegos'),
(1109, 460, 'El Batey'),
(1110, 461, 'Rafael Urdaneta'),
(1111, 461, 'La Victoria'),
(1112, 461, 'Raúl Cuenca'),
(1113, 447, 'Sinamaica'),
(1114, 447, 'Alta Guajira'),
(1115, 447, 'Elías Sánchez Rubio'),
(1116, 447, 'Guajira'),
(1117, 462, 'Altagracia'),
(1118, 462, 'Antímano'),
(1119, 462, 'Caricuao'),
(1120, 462, 'Catedral'),
(1121, 462, 'Coche'),
(1122, 462, 'El Junquito'),
(1123, 462, 'El Paraíso'),
(1124, 462, 'El Recreo'),
(1125, 462, 'El Valle'),
(1126, 462, 'La Candelaria'),
(1127, 462, 'La Pastora'),
(1128, 462, 'La Vega'),
(1129, 462, 'Macarao'),
(1130, 462, 'San Agustín'),
(1131, 462, 'San Bernardino'),
(1132, 462, 'San José'),
(1133, 462, 'San Juan'),
(1134, 462, 'San Pedro'),
(1135, 462, 'Santa Rosalía'),
(1136, 462, 'Santa Teresa'),
(1137, 462, 'Sucre (Catia)'),
(1138, 462, '23 de enero');

INSERT INTO TRABAJADOR (
    tipo_documento,
    cedula,
    nombres,
    apellidos,
    fecha_nacimiento,
    fecha_ingreso,
    genero,
    estado_civil,
    nacionalidad,
    telefono,
    correo,
    status,
    id_dir,
    id_cargo
) VALUES (
    'Cédula',
    '12345678',
    'Admin',
    'Sistema',
    '1980-01-01',
    CURDATE(),
    'Masculino',
    'Soltero(a)',
    'Venezolano(a)',
    '0000000000',
    'admin@fundacite.local',
    'Activo',
    NULL,
    1
);

INSERT INTO USUARIO (
    id_trabajador,
    nombre,
    contrasena,
    tipo_usuario,
    status
) VALUES (
    LAST_INSERT_ID(),
    'admin',
    '$2y$12$3Ewh6G9SB22RSOiiUFHnMeAAlLXsPGUaz8C32PrPhcT256vgubIDu',
    'Administrador',
    'Activo'
);

-- ==========================================
-- VERIFICAR LOS MUNICIPIOS
-- ==========================================

SELECT
    estado.nombre AS estado,
    municipio.cod_muni,
    municipio.nombre AS municipio

FROM ESTADO AS estado

INNER JOIN MUNICIPIO AS municipio
    ON estado.cod_est = municipio.cod_est

ORDER BY estado.cod_est, municipio.cod_muni;

-- 6. BITÁCORA DE AUDITORÍA DEL SISTEMA
-- ================================================================================
-- Registra las acciones relevantes que realizan los usuarios dentro del
-- sistema (creación, edición, eliminación, inicio/cierre de sesión, consultas
-- de listados y generación de reportes/PDF), para fines de trazabilidad y
-- auditoría.
-- ================================================================================

CREATE TABLE BITACORA (
    id_bitacora INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NULL,
    usuario VARCHAR(100) NULL,
    modulo VARCHAR(100) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    descripcion VARCHAR(500) NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bitacora),
    INDEX idx_bitacora_usuario (id_usuario),
    INDEX idx_bitacora_modulo (modulo),
    INDEX idx_bitacora_fecha (fecha_hora),
    CONSTRAINT fk_bitacora_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;