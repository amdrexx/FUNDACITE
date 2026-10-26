-- ============================================================
-- Respaldo generado por el sistema FUNDACITE
-- Base de datos: bd_rrhh_fundacite_yaracuy
-- Generado por: admin
-- Fecha: 2026-10-26 04:09:57
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `BITACORA`;
CREATE TABLE `BITACORA` (
  `id_bitacora` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `modulo` varchar(100) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_bitacora`),
  KEY `idx_bitacora_usuario` (`id_usuario`),
  KEY `idx_bitacora_modulo` (`modulo`),
  KEY `idx_bitacora_fecha` (`fecha_hora`),
  CONSTRAINT `fk_bitacora_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('1', '1', 'admin', 'Autenticación', 'Login', 'Inicio de sesión exitoso (rol: Administrador).', '2026-10-25 23:04:02');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('2', '1', 'admin', 'Autenticación', 'Logout', 'Sesión cerrada por inactividad.', '2026-10-25 23:05:21');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('3', '1', 'admin', 'Autenticación', 'Login', 'Inicio de sesión exitoso (rol: Administrador).', '2026-10-25 23:05:25');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('4', '1', 'admin', 'Base de Datos', 'Importar BD', 'Importó la base de datos desde \"bd_rrhh_fundacite_yaracuy_2026-10-26_040528.sql\" (1539 sentencias ejecutadas, 2 con error).', '2026-10-25 23:07:40');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('5', '1', 'admin', 'Autenticación', 'Logout', 'Sesión cerrada por inactividad.', '2026-10-25 23:07:40');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('6', NULL, 'admin123', 'Autenticación', 'Login fallido', 'Intento de inicio de sesión fallido para el usuario \"admin123\".', '2026-10-25 23:07:47');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('7', '1', 'admin', 'Autenticación', 'Login', 'Inicio de sesión exitoso (rol: Administrador).', '2026-10-25 23:07:52');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('8', '1', 'admin', 'Autenticación', 'Logout', 'Sesión cerrada por inactividad.', '2026-10-25 23:09:06');
INSERT INTO `BITACORA` (`id_bitacora`, `id_usuario`, `usuario`, `modulo`, `accion`, `descripcion`, `fecha_hora`) VALUES ('9', '1', 'admin', 'Autenticación', 'Login', 'Inicio de sesión exitoso (rol: Administrador).', '2026-10-25 23:09:52');

DROP TABLE IF EXISTS `CARGO`;
CREATE TABLE `CARGO` (
  `id_cargo` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_cargo` varchar(100) NOT NULL,
  PRIMARY KEY (`id_cargo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `CARGO` (`id_cargo`, `nombre_cargo`) VALUES ('1', 'Administrador');
INSERT INTO `CARGO` (`id_cargo`, `nombre_cargo`) VALUES ('2', 'Analista');
INSERT INTO `CARGO` (`id_cargo`, `nombre_cargo`) VALUES ('3', 'Técnico');

DROP TABLE IF EXISTS `CONSTANCIA_DE_TRABAJO`;
CREATE TABLE `CONSTANCIA_DE_TRABAJO` (
  `id_constancia` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_solicitud` int(10) unsigned NOT NULL,
  `nombre_director_departamento` varchar(150) DEFAULT NULL,
  `tipo_personal` varchar(50) DEFAULT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`id_constancia`),
  KEY `idx_constancia_solicitud` (`id_solicitud`),
  CONSTRAINT `CONSTANCIA_DE_TRABAJO_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `SOLICITUD` (`id_solicitud`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_tipo_personal` CHECK (`tipo_personal` is null or `tipo_personal` in ('Fijo','Contratado','Obrero','Empleado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `CONTRATO`;
CREATE TABLE `CONTRATO` (
  `id_contrato` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_trabajador` int(10) unsigned NOT NULL,
  `id_cargo` int(10) unsigned NOT NULL,
  `tipo_contrato` varchar(50) NOT NULL,
  `fecha_contrato` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `lugar_trabajo` varchar(255) NOT NULL,
  `nombre_presidente` varchar(150) NOT NULL,
  `cedula_presidente` varchar(20) NOT NULL,
  `gaceta_designacion_presidente` varchar(100) NOT NULL,
  PRIMARY KEY (`id_contrato`),
  KEY `idx_contrato_trabajador` (`id_trabajador`),
  KEY `idx_contrato_cargo` (`id_cargo`),
  CONSTRAINT `fk_contrato_cargo` FOREIGN KEY (`id_cargo`) REFERENCES `CARGO` (`id_cargo`),
  CONSTRAINT `fk_contrato_trabajador` FOREIGN KEY (`id_trabajador`) REFERENCES `TRABAJADOR` (`id_trabajador`) ON DELETE CASCADE,
  CONSTRAINT `chk_periodo_contrato` CHECK (`fecha_fin` is null or `fecha_fin` >= `fecha_contrato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `DIRECCION`;
CREATE TABLE `DIRECCION` (
  `id_dir` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cod_par` int(10) unsigned NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_dir`),
  KEY `cod_par` (`cod_par`),
  CONSTRAINT `DIRECCION_ibfk_1` FOREIGN KEY (`cod_par`) REFERENCES `PARROQUIA` (`cod_par`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `DISFRUTE_DE_VACACIONES`;
CREATE TABLE `DISFRUTE_DE_VACACIONES` (
  `id_dis` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_solicitud` int(10) unsigned NOT NULL,
  `nombre_cargo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `desde` date NOT NULL,
  `hasta` date NOT NULL,
  PRIMARY KEY (`id_dis`),
  KEY `idx_dis_vacaciones_solicitud` (`id_solicitud`),
  CONSTRAINT `DISFRUTE_DE_VACACIONES_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `SOLICITUD` (`id_solicitud`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_periodo_vacaciones` CHECK (`hasta` >= `desde`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `ESTADO`;
CREATE TABLE `ESTADO` (
  `cod_est` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`cod_est`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('1', 'Amazonas');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('2', 'Anzoátegui');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('3', 'Apure');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('4', 'Aragua');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('5', 'Barinas');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('6', 'Bolívar');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('7', 'Carabobo');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('8', 'Cojedes');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('9', 'Delta Amacuro');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('10', 'Distrito Capital');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('11', 'Falcón');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('12', 'Guárico');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('13', 'La Guaira');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('14', 'Lara');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('15', 'Mérida');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('16', 'Miranda');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('17', 'Monagas');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('18', 'Nueva Esparta');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('19', 'Portuguesa');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('20', 'Sucre');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('21', 'Táchira');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('22', 'Trujillo');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('23', 'Yaracuy');
INSERT INTO `ESTADO` (`cod_est`, `nombre`) VALUES ('24', 'Zulia');

DROP TABLE IF EXISTS `MUNICIPIO`;
CREATE TABLE `MUNICIPIO` (
  `cod_muni` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cod_est` int(10) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`cod_muni`),
  KEY `cod_est` (`cod_est`),
  CONSTRAINT `MUNICIPIO_ibfk_1` FOREIGN KEY (`cod_est`) REFERENCES `ESTADO` (`cod_est`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=463 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('1', '1', 'Alto Orinoco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('2', '1', 'Atabapo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('3', '1', 'Atures');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('4', '1', 'Autana');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('5', '1', 'Manapiare');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('6', '1', 'Maroa');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('7', '1', 'Río Negro');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('8', '2', 'Anaco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('9', '2', 'Aragua');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('10', '2', 'Manuel Ezequiel Bruzual');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('11', '2', 'Diego Bautista Urbaneja');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('12', '2', 'Fernando Peñalver');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('13', '2', 'Francisco Del Carmen Carvajal');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('14', '2', 'General Sir Arthur McGregor');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('15', '2', 'Guanta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('16', '2', 'Independencia');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('17', '2', 'José Gregorio Monagas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('18', '2', 'Juan Antonio Sotillo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('19', '2', 'Juan Manuel Cajigal');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('20', '2', 'Libertad');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('21', '2', 'Francisco de Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('22', '2', 'Pedro María Freites');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('23', '2', 'Píritu');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('24', '2', 'San José de Guanipa');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('25', '2', 'San Juan de Capistrano');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('26', '2', 'Santa Ana');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('27', '2', 'Simón Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('28', '2', 'Simón Rodríguez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('29', '3', 'Achaguas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('30', '3', 'Biruaca');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('31', '3', 'Muñóz');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('32', '3', 'Páez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('33', '3', 'Pedro Camejo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('34', '3', 'Rómulo Gallegos');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('35', '3', 'San Fernando');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('36', '4', 'Atanasio Girardot');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('37', '4', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('38', '4', 'Camatagua');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('39', '4', 'Francisco Linares Alcántara');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('40', '4', 'José Ángel Lamas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('41', '4', 'José Félix Ribas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('42', '4', 'José Rafael Revenga');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('43', '4', 'Libertador');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('44', '4', 'Mario Briceño Iragorry');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('45', '4', 'Ocumare de la Costa de Oro');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('46', '4', 'San Casimiro');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('47', '4', 'San Sebastián');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('48', '4', 'Santiago Mariño');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('49', '4', 'Santos Michelena');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('50', '4', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('51', '4', 'Tovar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('52', '4', 'Urdaneta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('53', '4', 'Zamora');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('54', '5', 'Alberto Arvelo Torrealba');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('55', '5', 'Andrés Eloy Blanco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('56', '5', 'Antonio José de Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('57', '5', 'Arismendi');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('58', '5', 'Barinas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('59', '5', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('60', '5', 'Cruz Paredes');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('61', '5', 'Ezequiel Zamora');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('62', '5', 'Obispos');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('63', '5', 'Pedraza');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('64', '5', 'Rojas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('65', '5', 'Sosa');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('66', '6', 'Caroní');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('67', '6', 'Cedeño');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('68', '6', 'El Callao');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('69', '6', 'Gran Sabana');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('70', '6', 'Heres');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('71', '6', 'Piar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('72', '6', 'Angostura (Raúl Leoni)');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('73', '6', 'Roscio');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('74', '6', 'Sifontes');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('75', '6', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('76', '6', 'Padre Pedro Chien');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('77', '7', 'Bejuma');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('78', '7', 'Carlos Arvelo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('79', '7', 'Diego Ibarra');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('80', '7', 'Guacara');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('81', '7', 'Juan José Mora');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('82', '7', 'Libertador');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('83', '7', 'Los Guayos');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('84', '7', 'Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('85', '7', 'Montalbán');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('86', '7', 'Naguanagua');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('87', '7', 'Puerto Cabello');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('88', '7', 'San Diego');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('89', '7', 'San Joaquín');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('90', '7', 'Valencia');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('91', '8', 'Anzoátegui');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('92', '8', 'Tinaquillo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('93', '8', 'Girardot');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('94', '8', 'Lima Blanco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('95', '8', 'Pao de San Juan Bautista');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('96', '8', 'Ricaurte');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('97', '8', 'Rómulo Gallegos');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('98', '8', 'San Carlos');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('99', '8', 'Tinaco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('100', '9', 'Antonio Díaz');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('101', '9', 'Casacoima');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('102', '9', 'Pedernales');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('103', '9', 'Tucupita');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('104', '11', 'Acosta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('105', '11', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('106', '11', 'Buchivacoa');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('107', '11', 'Cacique Manaure');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('108', '11', 'Carirubana');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('109', '11', 'Colina');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('110', '11', 'Dabajuro');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('111', '11', 'Democracia');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('112', '11', 'Falcón');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('113', '11', 'Federación');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('114', '11', 'Jacura');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('115', '11', 'José Laurencio Silva');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('116', '11', 'Los Taques');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('117', '11', 'Mauroa');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('118', '11', 'Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('119', '11', 'Monseñor Iturriza');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('120', '11', 'Palmasola');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('121', '11', 'Petit');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('122', '11', 'Píritu');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('123', '11', 'San Francisco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('124', '11', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('125', '11', 'Tocópero');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('126', '11', 'Unión');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('127', '11', 'Urumaco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('128', '11', 'Zamora');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('129', '12', 'Camaguán');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('130', '12', 'Chaguaramas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('131', '12', 'El Socorro');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('132', '12', 'José Félix Ribas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('133', '12', 'José Tadeo Monagas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('134', '12', 'Juan Germán Roscio');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('135', '12', 'Julián Mellado');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('136', '12', 'Las Mercedes');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('137', '12', 'Leonardo Infante');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('138', '12', 'Pedro Zaraza');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('139', '12', 'Ortíz');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('140', '12', 'San Gerónimo de Guayabal');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('141', '12', 'San José de Guaribe');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('142', '12', 'Santa María de Ipire');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('143', '12', 'Sebastián Francisco de Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('144', '14', 'Andrés Eloy Blanco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('145', '14', 'Crespo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('146', '14', 'Iribarren');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('147', '14', 'Jiménez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('148', '14', 'Morán');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('149', '14', 'Palavecino');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('150', '14', 'Simón Planas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('151', '14', 'Torres');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('152', '14', 'Urdaneta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('179', '15', 'Alberto Adriani');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('180', '15', 'Andrés Bello');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('181', '15', 'Antonio Pinto Salinas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('182', '15', 'Aricagua');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('183', '15', 'Arzobispo Chacón');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('184', '15', 'Campo Elías');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('185', '15', 'Caracciolo Parra Olmedo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('186', '15', 'Cardenal Quintero');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('187', '15', 'Guaraque');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('188', '15', 'Julio César Salas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('189', '15', 'Justo Briceño');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('190', '15', 'Libertador');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('191', '15', 'Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('192', '15', 'Obispo Ramos de Lora');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('193', '15', 'Padre Noguera');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('194', '15', 'Pueblo Llano');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('195', '15', 'Rangel');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('196', '15', 'Rivas Dávila');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('197', '15', 'Santos Marquina');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('198', '15', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('199', '15', 'Tovar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('200', '15', 'Tulio Febres Cordero');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('201', '15', 'Zea');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('223', '16', 'Acevedo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('224', '16', 'Andrés Bello');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('225', '16', 'Baruta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('226', '16', 'Brión');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('227', '16', 'Buroz');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('228', '16', 'Carrizal');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('229', '16', 'Chacao');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('230', '16', 'Cristóbal Rojas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('231', '16', 'El Hatillo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('232', '16', 'Guaicaipuro');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('233', '16', 'Independencia');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('234', '16', 'Lander');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('235', '16', 'Los Salias');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('236', '16', 'Páez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('237', '16', 'Paz Castillo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('238', '16', 'Pedro Gual');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('239', '16', 'Plaza');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('240', '16', 'Simón Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('241', '16', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('242', '16', 'Urdaneta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('243', '16', 'Zamora');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('258', '17', 'Acosta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('259', '17', 'Aguasay');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('260', '17', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('261', '17', 'Caripe');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('262', '17', 'Cedeño');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('263', '17', 'Ezequiel Zamora');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('264', '17', 'Libertador');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('265', '17', 'Maturín');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('266', '17', 'Piar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('267', '17', 'Punceres');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('268', '17', 'Santa Bárbara');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('269', '17', 'Sotillo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('270', '17', 'Uracoa');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('271', '18', 'Antolín del Campo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('272', '18', 'Arismendi');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('273', '18', 'García');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('274', '18', 'Gómez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('275', '18', 'Maneiro');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('276', '18', 'Marcano');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('277', '18', 'Mariño');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('278', '18', 'Península de Macanao');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('279', '18', 'Tubores');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('280', '18', 'Villalba');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('281', '18', 'Díaz');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('282', '19', 'Agua Blanca');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('283', '19', 'Araure');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('284', '19', 'Esteller');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('285', '19', 'Guanare');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('286', '19', 'Guanarito');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('287', '19', 'Monseñor José Vicente de Unda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('288', '19', 'Ospino');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('289', '19', 'Páez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('290', '19', 'Papelón');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('291', '19', 'San Genaro de Boconoíto');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('292', '19', 'San Rafael de Onoto');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('293', '19', 'Santa Rosalía');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('294', '19', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('295', '19', 'Turén');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('296', '20', 'Andrés Eloy Blanco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('297', '20', 'Andrés Mata');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('298', '20', 'Arismendi');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('299', '20', 'Benítez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('300', '20', 'Bermúdez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('301', '20', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('302', '20', 'Cajigal');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('303', '20', 'Cruz Salmerón Acosta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('304', '20', 'Libertador');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('305', '20', 'Mariño');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('306', '20', 'Mejía');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('307', '20', 'Montes');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('308', '20', 'Ribero');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('309', '20', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('310', '20', 'Valdéz');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('341', '21', 'Andrés Bello');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('342', '21', 'Antonio Rómulo Costa');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('343', '21', 'Ayacucho');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('344', '21', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('345', '21', 'Cárdenas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('346', '21', 'Córdoba');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('347', '21', 'Fernández Feo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('348', '21', 'Francisco de Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('349', '21', 'García de Hevia');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('350', '21', 'Guásimos');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('351', '21', 'Independencia');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('352', '21', 'Jáuregui');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('353', '21', 'José María Vargas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('354', '21', 'Junín');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('355', '21', 'Libertad');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('356', '21', 'Libertador');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('357', '21', 'Lobatera');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('358', '21', 'Michelena');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('359', '21', 'Panamericano');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('360', '21', 'Pedro María Ureña');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('361', '21', 'Rafael Urdaneta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('362', '21', 'Samuel Darío Maldonado');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('363', '21', 'San Cristóbal');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('364', '21', 'Seboruco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('365', '21', 'Simón Rodríguez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('366', '21', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('367', '21', 'Torbes');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('368', '21', 'Uribante');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('369', '21', 'San Judas Tadeo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('370', '22', 'Andrés Bello');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('371', '22', 'Boconó');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('372', '22', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('373', '22', 'Candelaria');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('374', '22', 'Carache');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('375', '22', 'Escuque');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('376', '22', 'José Felipe Márquez Cañizalez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('377', '22', 'Juan Vicente Campos Elías');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('378', '22', 'La Ceiba');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('379', '22', 'Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('380', '22', 'Monte Carmelo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('381', '22', 'Motatán');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('382', '22', 'Pampán');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('383', '22', 'Pampanito');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('384', '22', 'Rafael Rangel');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('385', '22', 'San Rafael de Carvajal');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('386', '22', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('387', '22', 'Trujillo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('388', '22', 'Urdaneta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('389', '22', 'Valera');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('390', '13', 'Vargas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('391', '23', 'Arístides Bastidas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('392', '23', 'Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('407', '23', 'Bruzual');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('408', '23', 'Cocorote');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('409', '23', 'Independencia');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('410', '23', 'José Antonio Páez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('411', '23', 'La Trinidad');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('412', '23', 'Manuel Monge');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('413', '23', 'Nirgua');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('414', '23', 'Peña');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('415', '23', 'San Felipe');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('416', '23', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('417', '23', 'Urachiche');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('418', '23', 'José Joaquín Veroes');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('441', '24', 'Almirante Padilla');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('442', '24', 'Baralt');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('443', '24', 'Cabimas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('444', '24', 'Catatumbo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('445', '24', 'Colón');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('446', '24', 'Francisco Javier Pulgar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('447', '24', 'Páez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('448', '24', 'Jesús Enrique Losada');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('449', '24', 'Jesús María Semprún');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('450', '24', 'La Cañada de Urdaneta');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('451', '24', 'Lagunillas');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('452', '24', 'Machiques de Perijá');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('453', '24', 'Mara');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('454', '24', 'Maracaibo');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('455', '24', 'Miranda');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('456', '24', 'Rosario de Perijá');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('457', '24', 'San Francisco');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('458', '24', 'Santa Rita');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('459', '24', 'Simón Bolívar');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('460', '24', 'Sucre');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('461', '24', 'Valmore Rodríguez');
INSERT INTO `MUNICIPIO` (`cod_muni`, `cod_est`, `nombre`) VALUES ('462', '10', 'Libertador');

DROP TABLE IF EXISTS `PARROQUIA`;
CREATE TABLE `PARROQUIA` (
  `cod_par` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cod_muni` int(10) unsigned NOT NULL,
  `nombre` varchar(250) NOT NULL,
  PRIMARY KEY (`cod_par`),
  KEY `idx_parroquia_municipio` (`cod_muni`),
  CONSTRAINT `fk_parroquia_municipio` FOREIGN KEY (`cod_muni`) REFERENCES `MUNICIPIO` (`cod_muni`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1', '1', 'Alto Orinoco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('2', '1', 'Huachamacare Acanaña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('3', '1', 'Marawaka Toky Shamanaña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('4', '1', 'Mavaka Mavaka');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('5', '1', 'Sierra Parima Parimabé');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('6', '2', 'Ucata Laja Lisa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('7', '2', 'Yapacana Macuruco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('8', '2', 'Caname Guarinuma');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('9', '3', 'Fernando Girón Tovar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('10', '3', 'Luis Alberto Gómez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('11', '3', 'Pahueña Limón de Parhueña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('12', '3', 'Platanillal Platanillal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('13', '4', 'Samariapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('14', '4', 'Sipapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('15', '4', 'Munduapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('16', '4', 'Guayapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('17', '5', 'Alto Ventuari');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('18', '5', 'Medio Ventuari');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('19', '5', 'Bajo Ventuari');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('20', '6', 'Victorino');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('21', '6', 'Comunidad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('22', '7', 'Casiquiare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('23', '7', 'Cocuy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('24', '7', 'San Carlos de Río Negro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('25', '7', 'Solano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('26', '8', 'Anaco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('27', '8', 'San Joaquín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('28', '9', 'Cachipo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('29', '9', 'Aragua de Barcelona');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('30', '11', 'Lechería');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('31', '11', 'El Morro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('32', '12', 'Puerto Píritu');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('33', '12', 'San Miguel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('34', '12', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('35', '13', 'Valle de Guanape');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('36', '13', 'Santa Bárbara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('37', '14', 'El Chaparro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('38', '14', 'Tomás Alfaro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('39', '14', 'Calatrava');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('40', '15', 'Guanta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('41', '15', 'Chorrerón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('42', '16', 'Mamo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('43', '16', 'Soledad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('44', '17', 'Mapire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('45', '17', 'Piar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('46', '17', 'Santa Clara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('47', '17', 'San Diego de Cabrutica');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('48', '17', 'Uverito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('49', '17', 'Zuata');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('50', '18', 'Puerto La Cruz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('51', '18', 'Pozuelos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('52', '19', 'Onoto');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('53', '19', 'San Pablo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('54', '20', 'San Mateo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('55', '20', 'El Carito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('56', '20', 'Santa Inés');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('57', '20', 'La Romereña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('58', '21', 'Atapirire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('59', '21', 'Boca del Pao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('60', '21', 'El Pao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('61', '21', 'Pariaguán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('62', '22', 'Cantaura');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('63', '22', 'Libertador');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('64', '22', 'Santa Rosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('65', '22', 'Urica');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('66', '23', 'Píritu');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('67', '23', 'San Francisco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('68', '24', 'San José de Guanipa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('69', '25', 'Boca de Uchire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('70', '25', 'Boca de Chávez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('71', '26', 'Pueblo Nuevo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('72', '26', 'Santa Ana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('73', '27', 'Bergantín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('74', '27', 'Caigua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('75', '27', 'El Carmen');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('76', '27', 'El Pilar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('77', '27', 'Naricual');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('78', '27', 'San Crsitóbal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('79', '28', 'Edmundo Barrios');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('80', '28', 'Miguel Otero Silva');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('81', '29', 'Achaguas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('82', '29', 'Apurito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('83', '29', 'El Yagual');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('84', '29', 'Guachara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('85', '29', 'Mucuritas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('86', '29', 'Queseras del medio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('87', '30', 'Biruaca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('88', '31', 'Bruzual');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('89', '31', 'Mantecal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('90', '31', 'Quintero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('91', '31', 'Rincón Hondo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('92', '31', 'San Vicente');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('93', '32', 'Guasdualito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('94', '32', 'Aramendi');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('95', '32', 'El Amparo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('96', '32', 'San Camilo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('97', '32', 'Urdaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('98', '33', 'San Juan de Payara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('99', '33', 'Codazzi');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('100', '33', 'Cunaviche');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('101', '34', 'Elorza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('102', '34', 'La Trinidad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('103', '35', 'San Fernando');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('104', '35', 'El Recreo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('105', '35', 'Peñalver');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('106', '35', 'San Rafael de Atamaica');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('107', '36', 'Pedro José Ovalles');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('108', '36', 'Joaquín Crespo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('109', '36', 'José Casanova Godoy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('110', '36', 'Madre María de San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('111', '36', 'Andrés Eloy Blanco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('112', '36', 'Los Tacarigua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('113', '36', 'Las Delicias');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('114', '36', 'Choroní');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('115', '37', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('116', '38', 'Camatagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('117', '38', 'Carmen de Cura');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('118', '39', 'Santa Rita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('119', '39', 'Francisco de Miranda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('120', '39', 'Moseñor Feliciano González');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('121', '40', 'Santa Cruz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('122', '41', 'José Félix Ribas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('123', '41', 'Castor Nieves Ríos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('124', '41', 'Las Guacamayas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('125', '41', 'Pao de Zárate');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('126', '41', 'Zuata');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('127', '42', 'José Rafael Revenga');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('128', '43', 'Palo Negro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('129', '43', 'San Martín de Porres');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('130', '44', 'El Limón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('131', '44', 'Caña de Azúcar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('132', '45', 'Ocumare de la Costa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('133', '46', 'San Casimiro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('134', '46', 'Güiripa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('135', '46', 'Ollas de Caramacate');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('136', '46', 'Valle Morín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('137', '47', 'San Sebastían');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('138', '48', 'Turmero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('139', '48', 'Arevalo Aponte');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('140', '48', 'Chuao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('141', '48', 'Samán de Güere');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('142', '48', 'Alfredo Pacheco Miranda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('143', '49', 'Santos Michelena');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('144', '49', 'Tiara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('145', '50', 'Cagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('146', '50', 'Bella Vista');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('147', '51', 'Tovar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('148', '52', 'Urdaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('149', '52', 'Las Peñitas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('150', '52', 'San Francisco de Cara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('151', '52', 'Taguay');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('152', '53', 'Zamora');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('153', '53', 'Magdaleno');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('154', '53', 'San Francisco de Asís');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('155', '53', 'Valles de Tucutunemo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('156', '53', 'Augusto Mijares');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('157', '54', 'Sabaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('158', '54', 'Juan Antonio Rodríguez Domínguez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('159', '55', 'El Cantón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('160', '55', 'Santa Cruz de Guacas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('161', '55', 'Puerto Vivas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('162', '56', 'Ticoporo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('163', '56', 'Nicolás Pulido');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('164', '56', 'Andrés Bello');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('165', '57', 'Arismendi');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('166', '57', 'Guadarrama');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('167', '57', 'La Unión');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('168', '57', 'San Antonio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('169', '58', 'Barinas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('170', '58', 'Alberto Arvelo Larriva');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('171', '58', 'San Silvestre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('172', '58', 'Santa Inés');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('173', '58', 'Santa Lucía');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('174', '58', 'Torumos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('175', '58', 'El Carmen');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('176', '58', 'Rómulo Betancourt');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('177', '58', 'Corazón de Jesús');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('178', '58', 'Ramón Ignacio Méndez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('179', '58', 'Alto Barinas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('180', '58', 'Manuel Palacio Fajardo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('181', '58', 'Juan Antonio Rodríguez Domínguez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('182', '58', 'Dominga Ortiz de Páez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('183', '59', 'Barinitas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('184', '59', 'Altamira de Cáceres');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('185', '59', 'Calderas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('186', '60', 'Barrancas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('187', '60', 'El Socorro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('188', '60', 'Mazparrito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('189', '61', 'Santa Bárbara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('190', '61', 'Pedro Briceño Méndez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('191', '61', 'Ramón Ignacio Méndez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('192', '61', 'José Ignacio del Pumar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('193', '62', 'Obispos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('194', '62', 'Guasimitos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('195', '62', 'El Real');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('196', '62', 'La Luz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('197', '63', 'Ciudad Bolívia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('198', '63', 'José Ignacio Briceño');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('199', '63', 'José Félix Ribas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('200', '63', 'Páez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('201', '64', 'Libertad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('202', '64', 'Dolores');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('203', '64', 'Santa Rosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('204', '64', 'Palacio Fajardo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('205', '65', 'Ciudad de Nutrias');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('206', '65', 'El Regalo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('207', '65', 'Puerto Nutrias');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('208', '65', 'Santa Catalina');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('209', '66', 'Cachamay');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('210', '66', 'Chirica');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('211', '66', 'Dalla Costa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('212', '66', 'Once de Abril');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('213', '66', 'Simón Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('214', '66', 'Unare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('215', '66', 'Universidad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('216', '66', 'Vista al Sol');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('217', '66', 'Pozo Verde');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('218', '66', 'Yocoima');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('219', '66', '5 de Julio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('220', '67', 'Cedeño');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('221', '67', 'Altagracia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('222', '67', 'Ascensión Farreras');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('223', '67', 'Guaniamo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('224', '67', 'La Urbana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('225', '67', 'Pijiguaos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('226', '68', 'El Callao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('227', '69', 'Gran Sabana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('228', '69', 'Ikabarú');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('229', '70', 'Catedral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('230', '70', 'Zea');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('231', '70', 'Orinoco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('232', '70', 'José Antonio Páez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('233', '70', 'Marhuanta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('234', '70', 'Agua Salada');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('235', '70', 'Vista Hermosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('236', '70', 'La Sabanita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('237', '70', 'Panapana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('238', '71', 'Andrés Eloy Blanco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('239', '71', 'Pedro Cova');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('240', '72', 'Raúl Leoni');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('241', '72', 'Barceloneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('242', '72', 'Santa Bárbara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('243', '72', 'San Francisco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('244', '73', 'Roscio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('245', '73', 'Salóm');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('246', '74', 'Sifontes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('247', '74', 'Dalla Costa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('248', '74', 'San Isidro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('249', '75', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('250', '75', 'Aripao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('251', '75', 'Guarataro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('252', '75', 'Las Majadas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('253', '75', 'Moitaco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('254', '76', 'Padre Pedro Chien');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('255', '76', 'Río Grande');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('256', '77', 'Bejuma');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('257', '77', 'Canoabo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('258', '77', 'Simón Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('259', '78', 'Güigüe');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('260', '78', 'Carabobo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('261', '78', 'Tacarigua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('262', '79', 'Mariara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('263', '79', 'Aguas Calientes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('264', '80', 'Ciudad Alianza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('265', '80', 'Guacara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('266', '80', 'Yagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('267', '81', 'Morón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('268', '81', 'Yagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('269', '82', 'Tocuyito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('270', '82', 'Independencia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('271', '83', 'Los Guayos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('272', '84', 'Miranda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('273', '85', 'Montalbán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('274', '86', 'Naguanagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('275', '87', 'Bartolomé Salóm');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('276', '87', 'Democracia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('277', '87', 'Fraternidad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('278', '87', 'Goaigoaza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('279', '87', 'Juan José Flores');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('280', '87', 'Unión');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('281', '87', 'Borburata');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('282', '87', 'Patanemo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('283', '88', 'San Diego');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('284', '89', 'San Joaquín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('285', '90', 'Candelaria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('286', '90', 'Catedral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('287', '90', 'El Socorro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('288', '90', 'Miguel Peña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('289', '90', 'Rafael Urdaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('290', '90', 'San Blas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('291', '90', 'San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('292', '90', 'Santa Rosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('293', '90', 'Negro Primero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('294', '91', 'Cojedes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('295', '91', 'Juan de Mata Suárez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('296', '92', 'Tinaquillo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('297', '93', 'El Baúl');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('298', '93', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('299', '94', 'La Aguadita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('300', '94', 'Macapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('301', '95', 'El Pao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('302', '96', 'El Amparo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('303', '96', 'Libertad de Cojedes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('304', '97', 'Rómulo Gallegos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('305', '98', 'San Carlos de Austria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('306', '98', 'Juan Ángel Bravo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('307', '98', 'Manuel Manrique');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('308', '99', 'General en Jefe José Laurencio Silva');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('309', '100', 'Curiapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('310', '100', 'Almirante Luis Brión');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('311', '100', 'Francisco Aniceto Lugo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('312', '100', 'Manuel Renaud');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('313', '100', 'Padre Barral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('314', '100', 'Santos de Abelgas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('315', '101', 'Imataca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('316', '101', 'Cinco de Julio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('317', '101', 'Juan Bautista Arismendi');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('318', '101', 'Manuel Piar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('319', '101', 'Rómulo Gallegos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('320', '102', 'Pedernales');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('321', '102', 'Luis Beltrán Prieto Figueroa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('322', '103', 'San José (Delta Amacuro)');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('323', '103', 'José Vidal Marcano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('324', '103', 'Juan Millán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('325', '103', 'Leonardo Ruíz Pineda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('326', '103', 'Mariscal Antonio José de Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('327', '103', 'Monseñor Argimiro García');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('328', '103', 'San Rafael (Delta Amacuro)');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('329', '103', 'Virgen del Valle');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('330', '10', 'Clarines');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('331', '10', 'Guanape');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('332', '10', 'Sabana de Uchire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('333', '104', 'Capadare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('334', '104', 'La Pastora');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('335', '104', 'Libertador');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('336', '104', 'San Juan de los Cayos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('337', '105', 'Aracua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('338', '105', 'La Peña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('339', '105', 'San Luis');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('340', '106', 'Bariro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('341', '106', 'Borojó');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('342', '106', 'Capatárida');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('343', '106', 'Guajiro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('344', '106', 'Seque');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('345', '106', 'Zazárida');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('346', '106', 'Valle de Eroa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('347', '107', 'Cacique Manaure');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('348', '108', 'Norte');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('349', '108', 'Carirubana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('350', '108', 'Santa Ana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('351', '108', 'Urbana Punta Cardón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('352', '109', 'La Vela de Coro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('353', '109', 'Acurigua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('354', '109', 'Guaibacoa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('355', '109', 'Las Calderas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('356', '109', 'Macoruca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('357', '110', 'Dabajuro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('358', '111', 'Agua Clara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('359', '111', 'Avaria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('360', '111', 'Pedregal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('361', '111', 'Piedra Grande');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('362', '111', 'Purureche');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('363', '112', 'Adaure');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('364', '112', 'Adícora');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('365', '112', 'Baraived');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('366', '112', 'Buena Vista');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('367', '112', 'Jadacaquiva');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('368', '112', 'El Vínculo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('369', '112', 'El Hato');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('370', '112', 'Moruy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('371', '112', 'Pueblo Nuevo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('372', '113', 'Agua Larga');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('373', '113', 'El Paují');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('374', '113', 'Independencia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('375', '113', 'Mapararí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('376', '114', 'Agua Linda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('377', '114', 'Araurima');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('378', '114', 'Jacura');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('379', '115', 'Tucacas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('380', '115', 'Boca de Aroa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('381', '116', 'Los Taques');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('382', '116', 'Judibana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('383', '117', 'Mene de Mauroa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('384', '117', 'San Félix');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('385', '117', 'Casigua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('386', '118', 'Guzmán Guillermo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('387', '118', 'Mitare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('388', '118', 'Río Seco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('389', '118', 'Sabaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('390', '118', 'San Antonio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('391', '118', 'San Gabriel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('392', '118', 'Santa Ana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('393', '119', 'Boca del Tocuyo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('394', '119', 'Chichiriviche');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('395', '119', 'Tocuyo de la Costa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('396', '120', 'Palmasola');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('397', '121', 'Cabure');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('398', '121', 'Colina');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('399', '121', 'Curimagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('400', '122', 'San José de la Costa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('401', '122', 'Píritu');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('402', '123', 'San Francisco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('403', '124', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('404', '124', 'Pecaya');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('405', '125', 'Tocópero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('406', '126', 'El Charal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('407', '126', 'Las Vegas del Tuy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('408', '126', 'Santa Cruz de Bucaral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('409', '127', 'Bruzual');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('410', '127', 'Urumaco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('411', '128', 'Puerto Cumarebo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('412', '128', 'La Ciénaga');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('413', '128', 'La Soledad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('414', '128', 'Pueblo Cumarebo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('415', '128', 'Zazárida');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('416', '113', 'Churuguara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('417', '129', 'Camaguán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('418', '129', 'Puerto Miranda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('419', '129', 'Uverito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('420', '130', 'Chaguaramas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('421', '131', 'El Socorro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('422', '132', 'Tucupido');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('423', '132', 'San Rafael de Laya');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('424', '133', 'Altagracia de Orituco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('425', '133', 'San Rafael de Orituco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('426', '133', 'San Francisco Javier de Lezama');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('427', '133', 'Paso Real de Macaira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('428', '133', 'Carlos Soublette');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('429', '133', 'San Francisco de Macaira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('430', '133', 'Libertad de Orituco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('431', '134', 'Cantaclaro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('432', '134', 'San Juan de los Morros');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('433', '134', 'Parapara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('434', '135', 'El Sombrero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('435', '135', 'Sosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('436', '136', 'Las Mercedes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('437', '136', 'Cabruta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('438', '136', 'Santa Rita de Manapire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('439', '137', 'Valle de la Pascua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('440', '137', 'Espino');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('441', '138', 'San José de Unare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('442', '138', 'Zaraza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('443', '139', 'San José de Tiznados');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('444', '139', 'San Francisco de Tiznados');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('445', '139', 'San Lorenzo de Tiznados');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('446', '139', 'Ortiz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('447', '140', 'Guayabal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('448', '140', 'Cazorla');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('449', '141', 'San José de Guaribe');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('450', '141', 'Uveral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('451', '142', 'Santa María de Ipire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('452', '142', 'Altamira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('453', '143', 'El Calvario');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('454', '143', 'El Rastro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('455', '143', 'Guardatinajas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('456', '143', 'Capital Urbana Calabozo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('457', '144', 'Quebrada Honda de Guache');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('458', '144', 'Pío Tamayo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('459', '144', 'Yacambú');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('460', '145', 'Fréitez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('461', '145', 'José María Blanco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('462', '146', 'Catedral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('463', '146', 'Concepción');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('464', '146', 'El Cují');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('465', '146', 'Juan de Villegas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('466', '146', 'Santa Rosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('467', '146', 'Tamaca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('468', '146', 'Unión');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('469', '146', 'Aguedo Felipe Alvarado');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('470', '146', 'Buena Vista');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('471', '146', 'Juárez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('472', '147', 'Juan Bautista Rodríguez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('473', '147', 'Cuara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('474', '147', 'Diego de Lozada');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('475', '147', 'Paraíso de San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('476', '147', 'San Miguel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('477', '147', 'Tintorero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('478', '147', 'José Bernardo Dorante');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('479', '147', 'Coronel Mariano Peraza ');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('480', '148', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('481', '148', 'Anzoátegui');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('482', '148', 'Guarico');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('483', '148', 'Hilario Luna y Luna');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('484', '148', 'Humocaro Alto');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('485', '148', 'Humocaro Bajo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('486', '148', 'La Candelaria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('487', '148', 'Morán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('488', '149', 'Cabudare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('489', '149', 'José Gregorio Bastidas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('490', '149', 'Agua Viva');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('491', '150', 'Sarare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('492', '150', 'Buría');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('493', '150', 'Gustavo Vegas León');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('494', '151', 'Trinidad Samuel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('495', '151', 'Antonio Díaz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('496', '151', 'Camacaro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('497', '151', 'Castañeda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('498', '151', 'Cecilio Zubillaga');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('499', '151', 'Chiquinquirá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('500', '151', 'El Blanco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('501', '151', 'Espinoza de los Monteros');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('502', '151', 'Lara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('503', '151', 'Las Mercedes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('504', '151', 'Manuel Morillo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('505', '151', 'Montaña Verde');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('506', '151', 'Montes de Oca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('507', '151', 'Torres');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('508', '151', 'Heriberto Arroyo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('509', '151', 'Reyes Vargas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('510', '151', 'Altagracia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('511', '152', 'Siquisique');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('512', '152', 'Moroturo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('513', '152', 'San Miguel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('514', '152', 'Xaguas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('515', '179', 'Presidente Betancourt');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('516', '179', 'Presidente Páez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('517', '179', 'Presidente Rómulo Gallegos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('518', '179', 'Gabriel Picón González');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('519', '179', 'Héctor Amable Mora');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('520', '179', 'José Nucete Sardi');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('521', '179', 'Pulido Méndez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('522', '180', 'La Azulita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('523', '181', 'Santa Cruz de Mora');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('524', '181', 'Mesa Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('525', '181', 'Mesa de Las Palmas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('526', '182', 'Aricagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('527', '182', 'San Antonio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('528', '183', 'Canagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('529', '183', 'Capurí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('530', '183', 'Chacantá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('531', '183', 'El Molino');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('532', '183', 'Guaimaral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('533', '183', 'Mucutuy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('534', '183', 'Mucuchachí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('535', '184', 'Fernández Peña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('536', '184', 'Matriz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('537', '184', 'Montalbán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('538', '184', 'Acequias');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('539', '184', 'Jají');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('540', '184', 'La Mesa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('541', '184', 'San José del Sur');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('542', '185', 'Tucaní');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('543', '185', 'Florencio Ramírez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('544', '186', 'Santo Domingo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('545', '186', 'Las Piedras');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('546', '187', 'Guaraque');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('547', '187', 'Mesa de Quintero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('548', '187', 'Río Negro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('549', '188', 'Arapuey');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('550', '188', 'Palmira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('551', '189', 'San Cristóbal de Torondoy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('552', '189', 'Torondoy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('553', '190', 'Antonio Spinetti Dini');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('554', '190', 'Arias');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('555', '190', 'Caracciolo Parra Pérez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('556', '190', 'Domingo Peña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('557', '190', 'El Llano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('558', '190', 'Gonzalo Picón Febres');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('559', '190', 'Jacinto Plaza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('560', '190', 'Juan Rodríguez Suárez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('561', '190', 'Lasso de la Vega');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('562', '190', 'Mariano Picón Salas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('563', '190', 'Milla');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('564', '190', 'Osuna Rodríguez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('565', '190', 'Sagrario');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('566', '190', 'El Morro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('567', '190', 'Los Nevados');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('568', '191', 'Andrés Eloy Blanco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('569', '191', 'La Venta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('570', '191', 'Piñango');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('571', '191', 'Timotes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('572', '192', 'Eloy Paredes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('573', '192', 'San Rafael de Alcázar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('574', '192', 'Santa Elena de Arenales');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('575', '193', 'Santa María de Caparo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('576', '194', 'Pueblo Llano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('577', '195', 'Cacute');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('578', '195', 'La Toma');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('579', '195', 'Mucuchíes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('580', '195', 'Mucurubá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('581', '195', 'San Rafael');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('582', '196', 'Gerónimo Maldonado');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('583', '196', 'Bailadores');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('584', '197', 'Tabay');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('585', '198', 'Chiguará');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('586', '198', 'Estánquez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('587', '198', 'Lagunillas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('588', '198', 'La Trampa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('589', '198', 'Pueblo Nuevo del Sur');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('590', '198', 'San Juan');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('591', '199', 'El Amparo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('592', '199', 'El Llano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('593', '199', 'San Francisco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('594', '199', 'Tovar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('595', '200', 'Independencia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('596', '200', 'María de la Concepción Palacios Blanco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('597', '200', 'Nueva Bolivia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('598', '200', 'Santa Apolonia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('599', '201', 'Caño El Tigre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('600', '201', 'Zea');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('601', '223', 'Aragüita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('602', '223', 'Arévalo González');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('603', '223', 'Capaya');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('604', '223', 'Caucagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('605', '223', 'Panaquire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('606', '223', 'Ribas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('607', '223', 'El Café');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('608', '223', 'Marizapa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('609', '224', 'Cumbo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('610', '224', 'San José de Barlovento');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('611', '225', 'El Cafetal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('612', '225', 'Las Minas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('613', '225', 'Nuestra Señora del Rosario');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('614', '226', 'Higuerote');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('615', '226', 'Curiepe');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('616', '226', 'Tacarigua de Brión');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('617', '227', 'Mamporal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('618', '228', 'Carrizal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('619', '229', 'Chacao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('620', '230', 'Charallave');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('621', '230', 'Las Brisas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('622', '231', 'El Hatillo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('623', '232', 'Altagracia de la Montaña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('624', '232', 'Cecilio Acosta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('625', '232', 'Los Teques');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('626', '232', 'El Jarillo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('627', '232', 'San Pedro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('628', '232', 'Tácata');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('629', '232', 'Paracotos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('630', '233', 'Cartanal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('631', '233', 'Santa Teresa del Tuy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('632', '234', 'La Democracia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('633', '234', 'Ocumare del Tuy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('634', '234', 'Santa Bárbara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('635', '235', 'San Antonio de los Altos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('636', '236', 'Río Chico');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('637', '236', 'El Guapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('638', '236', 'Tacarigua de la Laguna');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('639', '236', 'Paparo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('640', '236', 'San Fernando del Guapo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('641', '237', 'Santa Lucía del Tuy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('642', '238', 'Cúpira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('643', '238', 'Machurucuto');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('644', '239', 'Guarenas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('645', '240', 'San Antonio de Yare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('646', '240', 'San Francisco de Yare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('647', '241', 'Leoncio Martínez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('648', '241', 'Petare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('649', '241', 'Caucagüita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('650', '241', 'Filas de Mariche');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('651', '241', 'La Dolorita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('652', '242', 'Cúa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('653', '242', 'Nueva Cúa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('654', '243', 'Guatire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('655', '243', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('656', '258', 'San Antonio de Maturín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('657', '258', 'San Francisco de Maturín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('658', '259', 'Aguasay');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('659', '260', 'Caripito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('660', '261', 'El Guácharo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('661', '261', 'La Guanota');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('662', '261', 'Sabana de Piedra');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('663', '261', 'San Agustín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('664', '261', 'Teresen');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('665', '261', 'Caripe');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('666', '262', 'Areo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('667', '262', 'Capital Cedeño');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('668', '262', 'San Félix de Cantalicio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('669', '262', 'Viento Fresco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('670', '263', 'El Tejero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('671', '263', 'Punta de Mata');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('672', '264', 'Chaguaramas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('673', '264', 'Las Alhuacas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('674', '264', 'Tabasca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('675', '264', 'Temblador');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('676', '265', 'Alto de los Godos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('677', '265', 'Boquerón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('678', '265', 'Las Cocuizas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('679', '265', 'La Cruz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('680', '265', 'San Simón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('681', '265', 'El Corozo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('682', '265', 'El Furrial');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('683', '265', 'Jusepín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('684', '265', 'La Pica');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('685', '265', 'San Vicente');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('686', '266', 'Aparicio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('687', '266', 'Aragua de Maturín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('688', '266', 'Chaguamal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('689', '266', 'El Pinto');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('690', '266', 'Guanaguana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('691', '266', 'La Toscana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('692', '266', 'Taguaya');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('693', '267', 'Cachipo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('694', '267', 'Quiriquire');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('695', '268', 'Santa Bárbara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('696', '269', 'Barrancas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('697', '269', 'Los Barrancos de Fajardo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('698', '270', 'Uracoa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('699', '271', 'Antolín del Campo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('700', '272', 'Arismendi');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('701', '273', 'García');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('702', '273', 'Francisco Fajardo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('703', '274', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('704', '274', 'Guevara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('705', '274', 'Matasiete');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('706', '274', 'Santa Ana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('707', '274', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('708', '275', 'Aguirre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('709', '275', 'Maneiro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('710', '276', 'Adrián');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('711', '276', 'Juan Griego');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('712', '276', 'Yaguaraparo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('713', '277', 'Porlamar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('714', '278', 'San Francisco de Macanao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('715', '278', 'Boca de Río');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('716', '279', 'Tubores');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('717', '279', 'Los Baleales');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('718', '280', 'Vicente Fuentes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('719', '280', 'Villalba');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('720', '281', 'San Juan Bautista');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('721', '281', 'Zabala');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('722', '283', 'Capital Araure');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('723', '283', 'Río Acarigua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('724', '284', 'Capital Esteller');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('725', '284', 'Uveral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('726', '285', 'Guanare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('727', '285', 'Córdoba');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('728', '285', 'San José de la Montaña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('729', '285', 'San Juan de Guanaguanare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('730', '285', 'Virgen de la Coromoto');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('731', '286', 'Guanarito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('732', '286', 'Trinidad de la Capilla');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('733', '286', 'Divina Pastora');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('734', '287', 'Monseñor José Vicente de Unda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('735', '287', 'Peña Blanca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('736', '288', 'Capital Ospino');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('737', '288', 'Aparición');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('738', '288', 'La Estación');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('739', '289', 'Páez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('740', '289', 'Payara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('741', '289', 'Pimpinela');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('742', '289', 'Ramón Peraza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('743', '290', 'Papelón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('744', '290', 'Caño Delgadito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('745', '291', 'San Genaro de Boconoito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('746', '291', 'Antolín Tovar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('747', '292', 'San Rafael de Onoto');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('748', '292', 'Santa Fe');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('749', '292', 'Thermo Morles');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('750', '293', 'Santa Rosalía');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('751', '293', 'Florida');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('752', '294', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('753', '294', 'Concepción');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('754', '294', 'San Rafael de Palo Alzado');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('755', '294', 'Uvencio Antonio Velásquez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('756', '294', 'San José de Saguaz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('757', '294', 'Villa Rosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('758', '295', 'Turén');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('759', '295', 'Canelones');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('760', '295', 'Santa Cruz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('761', '295', 'San Isidro Labrador');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('762', '296', 'Mariño');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('763', '296', 'Rómulo Gallegos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('764', '297', 'San José de Aerocuar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('765', '297', 'Tavera Acosta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('766', '298', 'Río Caribe');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('767', '298', 'Antonio José de Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('768', '298', 'El Morro de Puerto Santo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('769', '298', 'Puerto Santo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('770', '298', 'San Juan de las Galdonas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('771', '299', 'El Pilar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('772', '299', 'El Rincón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('773', '299', 'General Francisco Antonio Váquez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('774', '299', 'Guaraúnos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('775', '299', 'Tunapuicito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('776', '299', 'Unión');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('777', '300', 'Santa Catalina');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('778', '300', 'Santa Rosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('779', '300', 'Santa Teresa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('780', '300', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('781', '300', 'Maracapana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('782', '302', 'Libertad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('783', '302', 'El Paujil');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('784', '302', 'Yaguaraparo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('785', '303', 'Cruz Salmerón Acosta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('786', '303', 'Chacopata');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('787', '303', 'Manicuare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('788', '304', 'Tunapuy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('789', '304', 'Campo Elías');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('790', '305', 'Irapa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('791', '305', 'Campo Claro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('792', '305', 'Maraval');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('793', '305', 'San Antonio de Irapa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('794', '305', 'Soro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('795', '306', 'Mejía');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('796', '307', 'Cumanacoa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('797', '307', 'Arenas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('798', '307', 'Aricagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('799', '307', 'Cogollar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('800', '307', 'San Fernando');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('801', '307', 'San Lorenzo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('802', '308', 'Villa Frontado (Muelle de Cariaco)');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('803', '308', 'Catuaro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('804', '308', 'Rendón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('805', '308', 'San Cruz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('806', '308', 'Santa María');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('807', '309', 'Altagracia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('808', '309', 'Santa Inés');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('809', '309', 'Valentín Valiente');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('810', '309', 'Ayacucho');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('811', '309', 'San Juan');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('812', '309', 'Raúl Leoni');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('813', '309', 'Gran Mariscal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('814', '310', 'Cristóbal Colón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('815', '310', 'Bideau');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('816', '310', 'Punta de Piedras');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('817', '310', 'Güiria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('818', '341', 'Andrés Bello');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('819', '342', 'Antonio Rómulo Costa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('820', '343', 'Ayacucho');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('821', '343', 'Rivas Berti');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('822', '343', 'San Pedro del Río');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('823', '344', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('824', '344', 'Palotal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('825', '344', 'General Juan Vicente Gómez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('826', '344', 'Isaías Medina Angarita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('827', '345', 'Cárdenas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('828', '345', 'Amenodoro Ángel Lamus');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('829', '345', 'La Florida');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('830', '346', 'Córdoba');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('831', '347', 'Fernández Feo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('832', '347', 'Alberto Adriani');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('833', '347', 'Santo Domingo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('834', '348', 'Francisco de Miranda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('835', '349', 'García de Hevia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('836', '349', 'Boca de Grita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('837', '349', 'José Antonio Páez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('838', '350', 'Guásimos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('839', '351', 'Independencia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('840', '351', 'Juan Germán Roscio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('841', '351', 'Román Cárdenas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('842', '352', 'Jáuregui');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('843', '352', 'Emilio Constantino Guerrero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('844', '352', 'Monseñor Miguel Antonio Salas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('845', '353', 'José María Vargas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('846', '354', 'Junín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('847', '354', 'La Petrólea');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('848', '354', 'Quinimarí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('849', '354', 'Bramón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('850', '355', 'Libertad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('851', '355', 'Cipriano Castro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('852', '355', 'Manuel Felipe Rugeles');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('853', '356', 'Libertador');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('854', '356', 'Doradas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('855', '356', 'Emeterio Ochoa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('856', '356', 'San Joaquín de Navay');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('857', '357', 'Lobatera');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('858', '357', 'Constitución');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('859', '358', 'Michelena');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('860', '359', 'Panamericano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('861', '359', 'La Palmita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('862', '360', 'Pedro María Ureña');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('863', '360', 'Nueva Arcadia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('864', '361', 'Delicias');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('865', '361', 'Pecaya');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('866', '362', 'Samuel Darío Maldonado');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('867', '362', 'Boconó');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('868', '362', 'Hernández');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('869', '363', 'La Concordia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('870', '363', 'San Juan Bautista');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('871', '363', 'Pedro María Morantes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('872', '363', 'San Sebastián');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('873', '363', 'Dr. Francisco Romero Lobo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('874', '364', 'Seboruco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('875', '365', 'Simón Rodríguez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('876', '366', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('877', '366', 'Eleazar López Contreras');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('878', '366', 'San Pablo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('879', '367', 'Torbes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('880', '368', 'Uribante');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('881', '368', 'Cárdenas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('882', '368', 'Juan Pablo Peñalosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('883', '368', 'Potosí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('884', '369', 'San Judas Tadeo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('885', '370', 'Araguaney');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('886', '370', 'El Jaguito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('887', '370', 'La Esperanza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('888', '370', 'Santa Isabel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('889', '371', 'Boconó');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('890', '371', 'El Carmen');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('891', '371', 'Mosquey');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('892', '371', 'Ayacucho');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('893', '371', 'Burbusay');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('894', '371', 'General Ribas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('895', '371', 'Guaramacal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('896', '371', 'Vega de Guaramacal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('897', '371', 'Monseñor Jáuregui');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('898', '371', 'Rafael Rangel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('899', '371', 'San Miguel');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('900', '371', 'San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('901', '372', 'Sabana Grande');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('902', '372', 'Cheregüé');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('903', '372', 'Granados');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('904', '373', 'Arnoldo Gabaldón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('905', '373', 'Bolivia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('906', '373', 'Carrillo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('907', '373', 'Cegarra');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('908', '373', 'Chejendé');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('909', '373', 'Manuel Salvador Ulloa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('910', '373', 'San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('911', '374', 'Carache');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('912', '374', 'La Concepción');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('913', '374', 'Cuicas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('914', '374', 'Panamericana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('915', '374', 'Santa Cruz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('916', '375', 'Escuque');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('917', '375', 'La Unión');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('918', '375', 'Santa Rita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('919', '375', 'Sabana Libre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('920', '376', 'El Socorro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('921', '376', 'Los Caprichos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('922', '376', 'Antonio José de Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('923', '377', 'Campo Elías');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('924', '377', 'Arnoldo Gabaldón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('925', '378', 'Santa Apolonia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('926', '378', 'El Progreso');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('927', '378', 'La Ceiba');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('928', '378', 'Tres de Febrero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('929', '379', 'El Dividive');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('930', '379', 'Agua Santa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('931', '379', 'Agua Caliente');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('932', '379', 'El Cenizo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('933', '379', 'Valerita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('934', '380', 'Monte Carmelo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('935', '380', 'Buena Vista');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('936', '380', 'Santa María del Horcón');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('937', '381', 'Motatán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('938', '381', 'El Baño');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('939', '381', 'Jalisco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('940', '382', 'Pampán');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('941', '382', 'Flor de Patria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('942', '382', 'La Paz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('943', '382', 'Santa Ana');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('944', '383', 'Pampanito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('945', '383', 'La Concepción');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('946', '383', 'Pampanito II');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('947', '384', 'Betijoque');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('948', '384', 'José Gregorio Hernández');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('949', '384', 'La Pueblita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('950', '384', 'Los Cedros');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('951', '385', 'Carvajal');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('952', '385', 'Campo Alegre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('953', '385', 'Antonio Nicolás Briceño');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('954', '385', 'José Leonardo Suárez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('955', '386', 'Sabana de Mendoza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('956', '386', 'Junín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('957', '386', 'Valmore Rodríguez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('958', '386', 'El Paraíso');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('959', '387', 'Andrés Linares');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('960', '387', 'Chiquinquirá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('961', '387', 'Cristóbal Mendoza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('962', '387', 'Cruz Carrillo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('963', '387', 'Matriz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('964', '387', 'Monseñor Carrillo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('965', '387', 'Tres Esquinas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('966', '388', 'Cabimbú');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('967', '388', 'Jajó');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('968', '388', 'La Mesa de Esnujaque');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('969', '388', 'Santiago');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('970', '388', 'Tuñame');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('971', '388', 'La Quebrada');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('972', '389', 'Juan Ignacio Montilla');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('973', '389', 'La Beatriz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('974', '389', 'La Puerta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('975', '389', 'Mendoza del Valle de Momboy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('976', '389', 'Mercedes Díaz');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('977', '389', 'San Luis');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('978', '390', 'Caraballeda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('979', '390', 'Carayaca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('980', '390', 'Carlos Soublette');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('981', '390', 'Caruao Chuspa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('982', '390', 'Catia La Mar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('983', '390', 'El Junko');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('984', '390', 'La Guaira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('985', '390', 'Macuto');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('986', '390', 'Maiquetía');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('987', '390', 'Naiguatá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('988', '390', 'Urimare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('989', '391', 'Arístides Bastidas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('990', '392', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('991', '407', 'Chivacoa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('992', '407', 'Campo Elías');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('993', '408', 'Cocorote');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('994', '409', 'Independencia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('995', '410', 'José Antonio Páez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('996', '411', 'La Trinidad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('997', '412', 'Manuel Monge');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('998', '413', 'Salóm');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('999', '413', 'Temerla');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1000', '413', 'Nirgua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1001', '414', 'San Andrés');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1002', '414', 'Yaritagua');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1003', '415', 'San Javier');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1004', '415', 'Albarico');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1005', '415', 'San Felipe');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1006', '416', 'Sucre');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1007', '417', 'Urachiche');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1008', '418', 'El Guayabo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1009', '418', 'Farriar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1010', '441', 'Isla de Toas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1011', '441', 'Monagas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1012', '442', 'San Timoteo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1013', '442', 'General Urdaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1014', '442', 'Libertador');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1015', '442', 'Marcelino Briceño');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1016', '442', 'Pueblo Nuevo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1017', '442', 'Manuel Guanipa Matos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1018', '443', 'Ambrosio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1019', '443', 'Carmen Herrera');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1020', '443', 'La Rosa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1021', '443', 'Germán Ríos Linares');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1022', '443', 'San Benito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1023', '443', 'Rómulo Betancourt');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1024', '443', 'Jorge Hernández');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1025', '443', 'Punta Gorda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1026', '443', 'Arístides Calvani');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1027', '444', 'Encontrados');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1028', '444', 'Udón Pérez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1029', '445', 'Moralito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1030', '445', 'San Carlos del Zulia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1031', '445', 'Santa Cruz del Zulia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1032', '445', 'Santa Bárbara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1033', '445', 'Urribarrí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1034', '446', 'Carlos Quevedo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1035', '446', 'Francisco Javier Pulgar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1036', '446', 'Simón Rodríguez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1037', '446', 'Guamo-Gavilanes');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1038', '448', 'La Concepción');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1039', '448', 'San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1040', '448', 'Mariano Parra León');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1041', '448', 'José Ramón Yépez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1042', '449', 'Jesús María Semprún');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1043', '449', 'Barí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1044', '450', 'Concepción');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1045', '450', 'Andrés Bello');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1046', '450', 'Chiquinquirá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1047', '450', 'El Carmelo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1048', '450', 'Potreritos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1049', '451', 'Libertad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1050', '451', 'Alonso de Ojeda');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1051', '451', 'Venezuela');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1052', '451', 'Eleazar López Contreras');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1053', '451', 'Campo Lara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1054', '452', 'Bartolomé de las Casas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1055', '452', 'Libertad');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1056', '452', 'Río Negro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1057', '452', 'San José de Perijá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1058', '453', 'San Rafael');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1059', '453', 'La Sierrita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1060', '453', 'Las Parcelas');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1061', '453', 'Luis de Vicente');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1062', '453', 'Monseñor Marcos Sergio Godoy');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1063', '453', 'Ricaurte');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1064', '453', 'Tamare');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1065', '454', 'Antonio Borjas Romero');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1066', '454', 'Bolívar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1067', '454', 'Cacique Mara');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1068', '454', 'Carracciolo Parra Pérez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1069', '454', 'Cecilio Acosta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1070', '454', 'Cristo de Aranza');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1071', '454', 'Coquivacoa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1072', '454', 'Chiquinquirá');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1073', '454', 'Francisco Eugenio Bustamante');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1074', '454', 'Idelfonzo Vásquez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1075', '454', 'Juana de Ávila');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1076', '454', 'Luis Hurtado Higuera');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1077', '454', 'Manuel Dagnino');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1078', '454', 'Olegario Villalobos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1079', '454', 'Raúl Leoni');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1080', '454', 'Santa Lucía');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1081', '454', 'Venancio Pulgar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1082', '454', 'San Isidro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1083', '455', 'Altagracia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1084', '455', 'Faría');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1085', '455', 'Ana María Campos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1086', '455', 'San Antonio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1087', '455', 'San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1088', '456', 'Donaldo García');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1089', '456', 'El Rosario');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1090', '456', 'Sixto Zambrano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1091', '457', 'San Francisco');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1092', '457', 'El Bajo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1093', '457', 'Domitila Flores');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1094', '457', 'Francisco Ochoa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1095', '457', 'Los Cortijos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1096', '457', 'Marcial Hernández');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1097', '458', 'Santa Rita');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1098', '458', 'El Mene');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1099', '458', 'Pedro Lucas Urribarrí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1100', '458', 'José Cenobio Urribarrí');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1101', '459', 'Rafael Maria Baralt');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1102', '459', 'Manuel Manrique');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1103', '459', 'Rafael Urdaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1104', '460', 'Bobures');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1105', '460', 'Gibraltar');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1106', '460', 'Heras');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1107', '460', 'Monseñor Arturo Álvarez');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1108', '460', 'Rómulo Gallegos');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1109', '460', 'El Batey');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1110', '461', 'Rafael Urdaneta');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1111', '461', 'La Victoria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1112', '461', 'Raúl Cuenca');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1113', '447', 'Sinamaica');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1114', '447', 'Alta Guajira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1115', '447', 'Elías Sánchez Rubio');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1116', '447', 'Guajira');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1117', '462', 'Altagracia');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1118', '462', 'Antímano');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1119', '462', 'Caricuao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1120', '462', 'Catedral');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1121', '462', 'Coche');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1122', '462', 'El Junquito');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1123', '462', 'El Paraíso');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1124', '462', 'El Recreo');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1125', '462', 'El Valle');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1126', '462', 'La Candelaria');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1127', '462', 'La Pastora');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1128', '462', 'La Vega');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1129', '462', 'Macarao');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1130', '462', 'San Agustín');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1131', '462', 'San Bernardino');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1132', '462', 'San José');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1133', '462', 'San Juan');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1134', '462', 'San Pedro');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1135', '462', 'Santa Rosalía');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1136', '462', 'Santa Teresa');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1137', '462', 'Sucre (Catia)');
INSERT INTO `PARROQUIA` (`cod_par`, `cod_muni`, `nombre`) VALUES ('1138', '462', '23 de enero');

DROP TABLE IF EXISTS `PRIMA`;
CREATE TABLE `PRIMA` (
  `id_prima` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_prima` varchar(100) NOT NULL,
  `porcentaje` decimal(10,2) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Activo',
  `fecha` date NOT NULL,
  PRIMARY KEY (`id_prima`),
  UNIQUE KEY `uq_prima_tipo_prima` (`tipo_prima`),
  CONSTRAINT `chk_prima_estado` CHECK (`estado` in ('Activo','Inactivo')),
  CONSTRAINT `chk_prima_porcentaje` CHECK (`porcentaje` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `SALARIO`;
CREATE TABLE `SALARIO` (
  `id_salario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_trabajador` int(10) unsigned DEFAULT NULL,
  `id_cargo` int(10) unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Vigente',
  `tipo_salario` varchar(20) NOT NULL DEFAULT 'base',
  PRIMARY KEY (`id_salario`),
  KEY `idx_salario_trabajador` (`id_trabajador`),
  KEY `idx_salario_cargo` (`id_cargo`),
  CONSTRAINT `SALARIO_ibfk_1` FOREIGN KEY (`id_trabajador`) REFERENCES `TRABAJADOR` (`id_trabajador`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_salario_cargo` FOREIGN KEY (`id_cargo`) REFERENCES `CARGO` (`id_cargo`),
  CONSTRAINT `chk_salario_estado` CHECK (`estado` in ('Vigente','Deshabilitado')),
  CONSTRAINT `chk_salario_tipo` CHECK (`tipo_salario` in ('base','cargo')),
  CONSTRAINT `chk_salario_tipo_cargo_consistente` CHECK (`tipo_salario` <> 'cargo' or `id_cargo` is not null),
  CONSTRAINT `chk_salario_monto` CHECK (`monto` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `SOLICITUD`;
CREATE TABLE `SOLICITUD` (
  `id_solicitud` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_trabajador` int(10) unsigned NOT NULL,
  `codigo_solicitud` varchar(50) DEFAULT NULL,
  `tipo_solicitud` varchar(50) NOT NULL,
  `motivo_solicitud` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`),
  KEY `idx_solicitud_trabajador` (`id_trabajador`),
  KEY `idx_solicitud_tipo` (`tipo_solicitud`),
  CONSTRAINT `SOLICITUD_ibfk_1` FOREIGN KEY (`id_trabajador`) REFERENCES `TRABAJADOR` (`id_trabajador`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_tipo_solicitud` CHECK (`tipo_solicitud` in ('Vacaciones','Permiso','Constancia de trabajo','Reposo','Otro')),
  CONSTRAINT `chk_fechas_solicitud` CHECK (`fecha_finalizacion` is null or `fecha_finalizacion` >= `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `TRABAJADOR`;
CREATE TABLE `TRABAJADOR` (
  `id_trabajador` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_documento` varchar(50) NOT NULL,
  `cedula` varchar(10) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `estado_civil` varchar(30) DEFAULT NULL,
  `nacionalidad` varchar(50) DEFAULT 'Venezolano(a)',
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Activo',
  `id_dir` int(10) unsigned DEFAULT NULL,
  `id_cargo` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_trabajador`),
  UNIQUE KEY `uq_cedula` (`cedula`),
  KEY `idx_trabajador_status` (`status`),
  KEY `idx_trabajador_cargo` (`id_cargo`),
  KEY `idx_trabajador_dir` (`id_dir`),
  CONSTRAINT `TRABAJADOR_ibfk_1` FOREIGN KEY (`id_dir`) REFERENCES `DIRECCION` (`id_dir`) ON UPDATE CASCADE,
  CONSTRAINT `TRABAJADOR_ibfk_2` FOREIGN KEY (`id_cargo`) REFERENCES `CARGO` (`id_cargo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_status` CHECK (`status` in ('Activo','Inactivo','Jubilado')),
  CONSTRAINT `chk_tipo_documento` CHECK (`tipo_documento` in ('Cédula','Pasaporte','Cédula de Extranjería')),
  CONSTRAINT `chk_genero` CHECK (`genero` is null or `genero` in ('Masculino','Femenino')),
  CONSTRAINT `chk_estado_civil` CHECK (`estado_civil` is null or `estado_civil` in ('Soltero(a)','Casado(a)','Divorciado(a)','Viudo(a)'))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `TRABAJADOR` (`id_trabajador`, `tipo_documento`, `cedula`, `nombres`, `apellidos`, `fecha_nacimiento`, `fecha_ingreso`, `genero`, `estado_civil`, `nacionalidad`, `telefono`, `correo`, `status`, `id_dir`, `id_cargo`) VALUES ('1', 'Cédula', '12345678', 'Admin', 'Sistema', '1980-01-01', '2026-10-25', 'Masculino', 'Soltero(a)', 'Venezolano(a)', '0000000000', 'admin@fundacite.local', 'Activo', NULL, '1');

DROP TABLE IF EXISTS `USUARIO`;
CREATE TABLE `USUARIO` (
  `id_usuario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_trabajador` int(10) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `tipo_usuario` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuario_nombre` (`nombre`),
  UNIQUE KEY `uq_usuario_trabajador` (`id_trabajador`),
  KEY `idx_usuario_status` (`status`),
  CONSTRAINT `fk_usuario_trabajador` FOREIGN KEY (`id_trabajador`) REFERENCES `TRABAJADOR` (`id_trabajador`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_usuario_status` CHECK (`status` in ('Activo','Inactivo','Bloqueado')),
  CONSTRAINT `chk_tipo_usuario` CHECK (`tipo_usuario` in ('Administrador','Director','Analista'))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `USUARIO` (`id_usuario`, `id_trabajador`, `nombre`, `contrasena`, `tipo_usuario`, `status`) VALUES ('1', '1', 'admin', '$2y$12$3Ewh6G9SB22RSOiiUFHnMeAAlLXsPGUaz8C32PrPhcT256vgubIDu', 'Administrador', 'Activo');

DROP TABLE IF EXISTS `V_TRABAJADOR_COMPLETO`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `V_TRABAJADOR_COMPLETO` AS select `t`.`id_trabajador` AS `id_trabajador`,`t`.`cedula` AS `cedula`,`t`.`nombres` AS `nombres`,`t`.`apellidos` AS `apellidos`,`t`.`fecha_nacimiento` AS `fecha_nacimiento`,`t`.`fecha_ingreso` AS `fecha_ingreso`,timestampdiff(YEAR,`t`.`fecha_nacimiento`,curdate()) AS `edad`,`t`.`genero` AS `genero`,`t`.`estado_civil` AS `estado_civil`,`t`.`nacionalidad` AS `nacionalidad`,`t`.`telefono` AS `telefono`,`t`.`correo` AS `correo`,`t`.`status` AS `status`,`c`.`nombre_cargo` AS `nombre_cargo`,`d`.`nombre` AS `direccion`,`p`.`nombre` AS `parroquia`,`m`.`nombre` AS `municipio`,`e`.`nombre` AS `estado` from (((((`TRABAJADOR` `t` left join `CARGO` `c` on(`t`.`id_cargo` = `c`.`id_cargo`)) left join `DIRECCION` `d` on(`t`.`id_dir` = `d`.`id_dir`)) left join `PARROQUIA` `p` on(`d`.`cod_par` = `p`.`cod_par`)) left join `MUNICIPIO` `m` on(`p`.`cod_muni` = `m`.`cod_muni`)) left join `ESTADO` `e` on(`m`.`cod_est` = `e`.`cod_est`));

INSERT INTO `V_TRABAJADOR_COMPLETO` (`id_trabajador`, `cedula`, `nombres`, `apellidos`, `fecha_nacimiento`, `fecha_ingreso`, `edad`, `genero`, `estado_civil`, `nacionalidad`, `telefono`, `correo`, `status`, `nombre_cargo`, `direccion`, `parroquia`, `municipio`, `estado`) VALUES ('1', '12345678', 'Admin', 'Sistema', '1980-01-01', '2026-10-25', '46', 'Masculino', 'Soltero(a)', 'Venezolano(a)', '0000000000', 'admin@fundacite.local', 'Activo', 'Administrador', NULL, NULL, NULL, NULL);

DROP TABLE IF EXISTS `V_TRABAJADOR_DETALLE`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `V_TRABAJADOR_DETALLE` AS select `TRABAJADOR`.`id_trabajador` AS `id_trabajador`,`TRABAJADOR`.`cedula` AS `cedula`,`TRABAJADOR`.`nombres` AS `nombres`,`TRABAJADOR`.`apellidos` AS `apellidos`,`TRABAJADOR`.`fecha_nacimiento` AS `fecha_nacimiento`,`TRABAJADOR`.`fecha_ingreso` AS `fecha_ingreso`,timestampdiff(YEAR,`TRABAJADOR`.`fecha_nacimiento`,curdate()) AS `edad`,`TRABAJADOR`.`genero` AS `genero`,`TRABAJADOR`.`estado_civil` AS `estado_civil`,`TRABAJADOR`.`nacionalidad` AS `nacionalidad`,`TRABAJADOR`.`telefono` AS `telefono`,`TRABAJADOR`.`correo` AS `correo`,`TRABAJADOR`.`status` AS `status`,`TRABAJADOR`.`id_dir` AS `id_dir`,`TRABAJADOR`.`id_cargo` AS `id_cargo` from `TRABAJADOR`;

INSERT INTO `V_TRABAJADOR_DETALLE` (`id_trabajador`, `cedula`, `nombres`, `apellidos`, `fecha_nacimiento`, `fecha_ingreso`, `edad`, `genero`, `estado_civil`, `nacionalidad`, `telefono`, `correo`, `status`, `id_dir`, `id_cargo`) VALUES ('1', '12345678', 'Admin', 'Sistema', '1980-01-01', '2026-10-25', '46', 'Masculino', 'Soltero(a)', 'Venezolano(a)', '0000000000', 'admin@fundacite.local', 'Activo', NULL, '1');

SET FOREIGN_KEY_CHECKS = 1;
