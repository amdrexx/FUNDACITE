-- ============================================================================
-- DATOS DE PRUEBA COMPLEMENTARIOS
-- Base de datos: bd_rrhh_fundacite_yaracuy
--
-- IMPORTANTE:
-- 1. Ejecutar DESPUES del archivo principal que crea las tablas y carga los
--    datos geograficos, cargos, administrador y las tres primas principales.
-- 2. Este archivo NO vuelve a insertar los trabajadores 12345678 y
--    99900001..99900005, los contratos que ya existen ni las primas del sistema.
-- 3. Los registros se identifican por cedula, nombre/codigo o combinaciones
--    naturales para que el script pueda ejecutarse mas de una vez sin duplicar.
-- 4. Los salarios son montos ficticios de prueba; no representan una escala
--    salarial oficial ni valores institucionales.
-- 5. Usuarios nuevos: contrasena comun Prueba123* (hash compatible con
--    password_verify de PHP).
-- ============================================================================

USE bd_rrhh_fundacite_yaracuy;
SET NAMES utf8mb4;
START TRANSACTION;

-- ============================================================================
-- 1. GREMIOS DE PRUEBA (no se insertan si ya existe el mismo nombre)
-- ============================================================================
INSERT INTO GREMIO (nombre, estado)
SELECT datos.nombre, 'Activo'
FROM (
    SELECT 'Sindicato de Obreros FUNDACITE (PRUEBA)' AS nombre
    UNION ALL SELECT 'Asociacion de Profesionales FUNDACITE (PRUEBA)'
    UNION ALL SELECT 'Federacion de Trabajadores FUNDACITE (PRUEBA)'
) AS datos
WHERE NOT EXISTS (
    SELECT 1 FROM GREMIO g WHERE g.nombre = datos.nombre
);

INSERT INTO GREMIO (nombre, estado)
SELECT 'Asociacion de Jubilados FUNDACITE (PRUEBA)', 'Inactivo'
WHERE NOT EXISTS (
    SELECT 1 FROM GREMIO
    WHERE nombre = 'Asociacion de Jubilados FUNDACITE (PRUEBA)'
);

-- ============================================================================
-- 2. DIRECCIONES DE PRUEBA EN PARROQUIAS DE YARACUY
--    Se buscan las parroquias por estado, municipio y nombre; no se fijan IDs.
-- ============================================================================
INSERT INTO DIRECCION (cod_par, nombre)
SELECT p.cod_par, datos.direccion
FROM (
    SELECT 'San Felipe' AS municipio, 'San Felipe' AS parroquia,
           'Av. La Patria, Edificio Administrativo (PRUEBA)' AS direccion
    UNION ALL SELECT 'Independencia', 'Independencia',
           'Calle 30, Sector Centro (PRUEBA)'
    UNION ALL SELECT 'Cocorote', 'Cocorote',
           'Av. Bolivar, Sector Centro (PRUEBA)'
) AS datos
INNER JOIN ESTADO e ON e.nombre = 'Yaracuy'
INNER JOIN MUNICIPIO m ON m.cod_est = e.cod_est
                       AND m.nombre = datos.municipio
INNER JOIN PARROQUIA p ON p.cod_muni = m.cod_muni
                       AND p.nombre = datos.parroquia
WHERE NOT EXISTS (
    SELECT 1
    FROM DIRECCION d
    WHERE d.cod_par = p.cod_par
      AND d.nombre = datos.direccion
);

-- ============================================================================
-- 3. TRABAJADORES ADICIONALES
--    Incluye activo, inactivo, jubilado, empleado, obrero y fijo/contratado.
--    Los seis registros tienen cedulas nuevas respecto de los datos iniciales.
-- ============================================================================
INSERT INTO TRABAJADOR (
    tipo_documento, cedula, nombres, apellidos, genero, fecha_nacimiento,
    estado_civil, nacionalidad, telefono, correo,
    hijo_menor, hijo_mayor, anos_servicio_administracion_publica,
    nivel_educacion, tipo_personal, status, id_dir, id_cargo, id_gremio
)
SELECT
    'Cédula', datos.cedula, datos.nombres, datos.apellidos, datos.genero,
    datos.fecha_nacimiento, datos.estado_civil, 'Venezolano(a)',
    datos.telefono, datos.correo, datos.hijo_menor, datos.hijo_mayor,
    datos.anos_servicio, datos.nivel_educacion, datos.tipo_personal,
    datos.status, d.id_dir, c.id_cargo, g.id_gremio
FROM (
    SELECT '99900006' AS cedula, 'Gabriela Elena' AS nombres,
           'Ruiz Mendoza' AS apellidos, 'Femenino' AS genero,
           '1990-05-09' AS fecha_nacimiento, 'Casado(a)' AS estado_civil,
           '0412-5551006' AS telefono, 'gabriela.ruiz@example.com' AS correo,
           2 AS hijo_menor, 0 AS hijo_mayor, 5 AS anos_servicio,
           'Licenciatura' AS nivel_educacion, 'Contratado' AS tipo_personal,
           'Inactivo' AS status, 'Av. La Patria, Edificio Administrativo (PRUEBA)' AS direccion,
           NULL AS cargo, 'Sindicato de Obreros FUNDACITE (PRUEBA)' AS gremio
    UNION ALL SELECT '99900007', 'Pedro Antonio', 'Salas Perez', 'Masculino',
           '1960-11-20', 'Casado(a)', '0414-5551007', 'pedro.salas@example.com',
           0, 0, 30, 'Postgrado', 'Jubilado', 'Jubilado',
           'Calle 30, Sector Centro (PRUEBA)', NULL,
           'Asociacion de Jubilados FUNDACITE (PRUEBA)'
    UNION ALL SELECT '99900008', 'Diana Carolina', 'Blanco Romero', 'Femenino',
           '1994-04-12', 'Soltero(a)', '0416-5551008', 'diana.blanco@example.com',
           1, 0, 3, 'Técnico Superior Universitario', 'Empleado', 'Activo',
           'Av. Bolivar, Sector Centro (PRUEBA)', 'Analista',
           'Asociacion de Profesionales FUNDACITE (PRUEBA)'
    UNION ALL SELECT '99900009', 'Rafael Enrique', 'Vargas Leon', 'Masculino',
           '1989-08-06', 'Casado(a)', '0424-5551009', 'rafael.vargas@example.com',
           1, 0, 6, 'Bachiller', 'Obrero', 'Activo',
           'Av. La Patria, Edificio Administrativo (PRUEBA)', 'Técnico',
           'Sindicato de Obreros FUNDACITE (PRUEBA)'
    UNION ALL SELECT '99900010', 'Beatriz del Carmen', 'Mora Gil', 'Femenino',
           '1982-12-01', 'Divorciado(a)', '0412-5551010', 'beatriz.mora@example.com',
           0, 1, 14, 'Universitario', 'Fijo', 'Activo',
           'Calle 30, Sector Centro (PRUEBA)', 'Director',
           'Federacion de Trabajadores FUNDACITE (PRUEBA)'
    UNION ALL SELECT '99900011', 'Miguel Angel', 'Hernandez Soto', 'Masculino',
           '1978-06-15', 'Casado(a)', '0414-5551011', 'miguel.hernandez@example.com',
           0, 0, 20, 'Postgrado', 'Empleado', 'Activo',
           'Av. Bolivar, Sector Centro (PRUEBA)', 'Analista',
           'Asociacion de Profesionales FUNDACITE (PRUEBA)'
) AS datos
LEFT JOIN (
    SELECT nombre, MIN(id_dir) AS id_dir
    FROM DIRECCION GROUP BY nombre
) d ON d.nombre = datos.direccion
LEFT JOIN (
    SELECT nombre_cargo, MIN(id_cargo) AS id_cargo
    FROM CARGO GROUP BY nombre_cargo
) c ON c.nombre_cargo = datos.cargo
LEFT JOIN GREMIO g ON g.nombre = datos.gremio
WHERE NOT EXISTS (
    SELECT 1 FROM TRABAJADOR t WHERE t.cedula = datos.cedula
);

-- ============================================================================
-- 4. CONTRATOS HISTORICOS Y VIGENTES PARA LOS NUEVOS TRABAJADORES
--    Se usa un cargo historico aunque el trabajador ya no tenga cargo actual.
-- ============================================================================
INSERT INTO CONTRATO (
    id_trabajador, id_cargo, tipo_contrato, posee_convenio,
    fecha_contrato, fecha_fin, lugar_trabajo,
    nombre_presidente, cedula_presidente
)
SELECT
    t.id_trabajador, c.id_cargo, datos.tipo_contrato,
    CASE WHEN t.id_gremio IS NULL THEN 0 ELSE 1 END,
    datos.fecha_contrato, datos.fecha_fin,
    'Zona Industrial, Edificio FUNDACITE, San Felipe, Yaracuy',
    'Jose Rafael Castillo Vera', '99900005'
FROM (
    SELECT '99900006' AS cedula, 'Analista' AS cargo,
           'Tiempo determinado' AS tipo_contrato,
           '2018-01-15' AS fecha_contrato, '2025-12-31' AS fecha_fin
    UNION ALL SELECT '99900007', 'Técnico', 'Tiempo indeterminado',
           '1990-01-15', '2020-11-20'
    UNION ALL SELECT '99900008', 'Analista', 'Tiempo indeterminado',
           '2021-02-01', NULL
    UNION ALL SELECT '99900009', 'Técnico', 'Tiempo indeterminado',
           '2019-06-01', NULL
    UNION ALL SELECT '99900010', 'Director', 'Tiempo determinado',
           '2022-03-01', '2027-02-28'
    UNION ALL SELECT '99900011', 'Analista', 'Tiempo indeterminado',
           '2017-09-15', NULL
) AS datos
INNER JOIN TRABAJADOR t ON t.cedula = datos.cedula
INNER JOIN (
    SELECT nombre_cargo, MIN(id_cargo) AS id_cargo
    FROM CARGO GROUP BY nombre_cargo
) c ON c.nombre_cargo = datos.cargo
WHERE NOT EXISTS (
    SELECT 1
    FROM CONTRATO ct
    WHERE ct.id_trabajador = t.id_trabajador
      AND ct.fecha_contrato = datos.fecha_contrato
);

-- ============================================================================
-- 5. USUARIOS PARA PROBAR LOS ROLES
--    El usuario admin existente no se vuelve a crear.
--    Clave de los tres usuarios nuevos: Prueba123*
-- ============================================================================
INSERT INTO USUARIO (id_trabajador, nombre, contrasena, tipo_usuario, status)
SELECT t.id_trabajador, datos.usuario,
       '$2y$12$2bCWJBtgeTmFDj0U.412D./lSPuOVLuAusU.Z8KmMi5W0GD/3cVdW',
       datos.tipo_usuario, 'Activo'
FROM (
    SELECT '99900010' AS cedula, 'director.prueba' AS usuario, 'Director' AS tipo_usuario
    UNION ALL SELECT '99900002', 'analista.prueba', 'Analista'
    UNION ALL SELECT '99900008', 'estandar.prueba', 'Usuario Estándar'
) AS datos
INNER JOIN TRABAJADOR t ON t.cedula = datos.cedula
WHERE NOT EXISTS (
    SELECT 1 FROM USUARIO u
    WHERE u.id_trabajador = t.id_trabajador
       OR u.nombre = datos.usuario
);

-- ============================================================================
-- 6. CONVENIOS DE PRUEBA
--    Tres convenios vigentes y uno historico no vigente.
-- ============================================================================
INSERT INTO CONVENIO (id_gremio, fecha_inicio, fecha_vencimiento, dias_disfrute, estado)
SELECT g.id_gremio, datos.fecha_inicio, datos.fecha_vencimiento,
       30, datos.estado
FROM (
    SELECT 'Sindicato de Obreros FUNDACITE (PRUEBA)' AS gremio,
           '2026-01-01' AS fecha_inicio, NULL AS fecha_vencimiento,
           'Vigente' AS estado
    UNION ALL SELECT 'Asociacion de Profesionales FUNDACITE (PRUEBA)',
           '2026-01-01', NULL, 'Vigente'
    UNION ALL SELECT 'Federacion de Trabajadores FUNDACITE (PRUEBA)',
           '2026-01-01', NULL, 'Vigente'
    UNION ALL SELECT 'Sindicato de Obreros FUNDACITE (PRUEBA)',
           '2023-01-01', '2024-12-31', 'No vigente'
) AS datos
INNER JOIN GREMIO g ON g.nombre = datos.gremio
WHERE NOT EXISTS (
    SELECT 1 FROM CONVENIO cv
    WHERE cv.id_gremio = g.id_gremio
      AND cv.fecha_inicio = datos.fecha_inicio
);

-- ============================================================================
-- 7. HIJOS DE TRABAJADORES
--    Incluye edades en los rangos 0-5, 6-21 y mayores de 18 para pruebas.
-- ============================================================================
INSERT INTO HIJO_TRABAJADOR (id_trabajador, fecha_nacimiento)
SELECT t.id_trabajador, datos.fecha_nacimiento
FROM (
    SELECT '99900006' AS cedula, '2022-10-10' AS fecha_nacimiento
    UNION ALL SELECT '99900006', '2014-08-16'
    UNION ALL SELECT '99900008', '2013-05-12'
    UNION ALL SELECT '99900009', '2021-08-01'
    UNION ALL SELECT '99900010', '2007-11-09'
) AS datos
INNER JOIN TRABAJADOR t ON t.cedula = datos.cedula
WHERE NOT EXISTS (
    SELECT 1 FROM HIJO_TRABAJADOR h
    WHERE h.id_trabajador = t.id_trabajador
      AND h.fecha_nacimiento = datos.fecha_nacimiento
);

-- Sincroniza los contadores del trabajador con las fechas registradas.
-- Menores: menos de 18 años; mayores: 18 años o mas.
UPDATE TRABAJADOR t
SET t.hijo_menor = (
        SELECT COUNT(*) FROM HIJO_TRABAJADOR h
        WHERE h.id_trabajador = t.id_trabajador
          AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) < 18
    ),
    t.hijo_mayor = (
        SELECT COUNT(*) FROM HIJO_TRABAJADOR h
        WHERE h.id_trabajador = t.id_trabajador
          AND TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) >= 18
    )
WHERE t.cedula IN ('99900006','99900008','99900009','99900010');

-- ============================================================================
-- 8. ASIGNACIONES ACTIVAS PARA LOS TRABAJADORES NUEVOS QUE TIENEN CARGO
-- ============================================================================
INSERT INTO ASIGNACION_CARGO (
    id_trabajador, id_cargo, fecha_asignacion,
    id_usuario_asignador, estado, gaceta
)
SELECT t.id_trabajador, t.id_cargo,
       COALESCE((
           SELECT MIN(ct.fecha_contrato)
           FROM CONTRATO ct
           WHERE ct.id_trabajador = t.id_trabajador
             AND ct.id_cargo = t.id_cargo
       ), NOW()),
       NULL, 'ACTIVA', 'Gaceta de prueba N. 0001'
FROM TRABAJADOR t
WHERE t.cedula IN ('99900008','99900009','99900010','99900011')
  AND t.id_cargo IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM ASIGNACION_CARGO a
      WHERE a.id_trabajador = t.id_trabajador
        AND a.estado = 'ACTIVA'
  );

-- ============================================================================
-- 9. SALARIOS FICTICIOS PARA PROBAR CONSTANCIAS Y PRIMAS
--    No se insertan si ya hay un salario vigente del mismo tipo y referencia.
-- ============================================================================
INSERT INTO SALARIO (id_trabajador, id_cargo, fecha, monto, estado, tipo_salario)
SELECT NULL, c.id_cargo, CURDATE(),
       CASE c.nombre_cargo
           WHEN 'Administrador' THEN 42000.00
           WHEN 'Analista' THEN 35000.00
           WHEN 'Técnico' THEN 30000.00
           WHEN 'Director' THEN 55000.00
           WHEN 'Presidente' THEN 65000.00
           ELSE 28000.00
       END,
       'Vigente', 'cargo'
FROM (
    SELECT nombre_cargo, MIN(id_cargo) AS id_cargo
    FROM CARGO GROUP BY nombre_cargo
) c
WHERE NOT EXISTS (
    SELECT 1 FROM SALARIO s
    WHERE s.id_cargo = c.id_cargo
      AND s.id_trabajador IS NULL
      AND s.tipo_salario = 'cargo'
      AND s.estado = 'Vigente'
);

INSERT INTO SALARIO (id_trabajador, id_cargo, fecha, monto, estado, tipo_salario)
SELECT t.id_trabajador, NULL, CURDATE(),
       CASE t.tipo_personal
           WHEN 'Obrero' THEN 18000.00
           WHEN 'Jubilado' THEN 18000.00
           ELSE 20000.00
       END,
       'Vigente', 'base'
FROM TRABAJADOR t
WHERE t.cedula IN (
    '12345678','99900001','99900002','99900003','99900004','99900005',
    '99900006','99900007','99900008','99900009','99900010','99900011'
)
AND NOT EXISTS (
    SELECT 1 FROM SALARIO s
    WHERE s.id_trabajador = t.id_trabajador
      AND s.tipo_salario = 'base'
      AND s.estado = 'Vigente'
);

INSERT INTO SALARIO (id_trabajador, id_cargo, fecha, monto, estado, tipo_salario)
SELECT t.id_trabajador, NULL, '2020-11-20', 12000.00, 'Deshabilitado', 'base'
FROM TRABAJADOR t
WHERE t.cedula = '99900007'
  AND NOT EXISTS (
      SELECT 1 FROM SALARIO s
      WHERE s.id_trabajador = t.id_trabajador
        AND s.fecha = '2020-11-20'
        AND s.tipo_salario = 'base'
        AND s.estado = 'Deshabilitado'
  );

-- ============================================================================
-- 10. ASOCIACION DE TRABAJADORES CON LAS PRIMAS YA EXISTENTES
--     No se crean nuevas filas en PRIMA ni PRIMA_VALOR.
-- ============================================================================

-- 10.1 Prima por antiguedad. Se utiliza el tramo de años correspondiente.
INSERT INTO TRABAJADOR_PRIMA (
    id_trabajador, id_prima, id_prima_valor, condicion,
    porcentaje, monto, monto_total, fecha, estatus
)
SELECT
    t.id_trabajador, p.id_prima, pv.id_prima_valor,
    CONCAT(t.anos_servicio_administracion_publica, ' anos de servicio'),
    pv.valor,
    ROUND(COALESCE((
        SELECT s.monto FROM SALARIO s
        WHERE s.id_trabajador = t.id_trabajador
          AND s.tipo_salario = 'base' AND s.estado = 'Vigente'
        ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1
    ), 20000.00) * pv.valor / 100, 2),
    ROUND(COALESCE((
        SELECT s.monto FROM SALARIO s
        WHERE s.id_trabajador = t.id_trabajador
          AND s.tipo_salario = 'base' AND s.estado = 'Vigente'
        ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1
    ), 20000.00) * pv.valor / 100, 2),
    CURDATE(), 'Activo'
FROM TRABAJADOR t
INNER JOIN (SELECT nombre, MIN(id_prima) AS id_prima FROM PRIMA GROUP BY nombre) p
    ON p.nombre = 'Prima por antigüedad'
INNER JOIN (
    SELECT id_prima, orden, MIN(id_prima_valor) AS id_prima_valor, MAX(valor) AS valor
    FROM PRIMA_VALOR GROUP BY id_prima, orden
) pv ON pv.id_prima = p.id_prima
    AND pv.orden = LEAST(GREATEST(t.anos_servicio_administracion_publica, 1), 23)
WHERE t.cedula IN (
    '12345678','99900001','99900002','99900003','99900004','99900005',
    '99900006','99900007','99900008','99900009','99900010','99900011'
)
AND NOT EXISTS (
    SELECT 1 FROM TRABAJADOR_PRIMA tp
    WHERE tp.id_trabajador = t.id_trabajador
      AND tp.id_prima = p.id_prima
);

-- 10.2 Prima por profesionalizacion, segun el nivel educativo.
INSERT INTO TRABAJADOR_PRIMA (
    id_trabajador, id_prima, id_prima_valor, condicion,
    porcentaje, monto, monto_total, fecha, estatus
)
SELECT
    t.id_trabajador, p.id_prima, pv.id_prima_valor,
    t.nivel_educacion, pv.valor,
    ROUND(COALESCE((
        SELECT s.monto FROM SALARIO s
        WHERE s.id_trabajador = t.id_trabajador
          AND s.tipo_salario = 'base' AND s.estado = 'Vigente'
        ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1
    ), 20000.00) * pv.valor / 100, 2),
    ROUND(COALESCE((
        SELECT s.monto FROM SALARIO s
        WHERE s.id_trabajador = t.id_trabajador
          AND s.tipo_salario = 'base' AND s.estado = 'Vigente'
        ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1
    ), 20000.00) * pv.valor / 100, 2),
    CURDATE(), 'Activo'
FROM TRABAJADOR t
INNER JOIN (SELECT nombre, MIN(id_prima) AS id_prima FROM PRIMA GROUP BY nombre) p
    ON p.nombre = 'Prima por profesionalización'
INNER JOIN (
    SELECT id_prima, orden, MIN(id_prima_valor) AS id_prima_valor, MAX(valor) AS valor
    FROM PRIMA_VALOR GROUP BY id_prima, orden
) pv ON pv.id_prima = p.id_prima
    AND pv.orden = CASE
        WHEN LOWER(t.nivel_educacion) LIKE '%postgrado%'
          OR LOWER(t.nivel_educacion) LIKE '%doctorado%' THEN 3
        WHEN (LOWER(t.nivel_educacion) LIKE '%técnico superior%' OR LOWER(t.nivel_educacion) LIKE '%tecnico superior%')
          OR LOWER(t.nivel_educacion) = 'tsu' THEN 1
        WHEN LOWER(t.nivel_educacion) LIKE '%universitario%'
          OR LOWER(t.nivel_educacion) LIKE '%licenciatura%'
          OR LOWER(t.nivel_educacion) LIKE '%ingenier%' THEN 2
        ELSE 0
    END
WHERE t.cedula IN (
    '12345678','99900001','99900002','99900003','99900004','99900005',
    '99900006','99900007','99900008','99900009','99900010','99900011'
)
AND NOT EXISTS (
    SELECT 1 FROM TRABAJADOR_PRIMA tp
    WHERE tp.id_trabajador = t.id_trabajador
      AND tp.id_prima = p.id_prima
);

-- 10.3 Prima por hijos. Suma los valores de los tramos 0-5 y 6-21 anos.
-- Se deja id_prima_valor en NULL porque puede haber hijos en ambos tramos.
INSERT INTO TRABAJADOR_PRIMA (
    id_trabajador, id_prima, id_prima_valor, condicion,
    porcentaje, monto, monto_total, fecha, estatus
)
SELECT
    resumen.id_trabajador,
    p.id_prima,
    NULL,
    CONCAT('Hijos de 0 a 5 anos: ', resumen.hijos_0_5,
           '; hijos de 6 a 21 anos: ', resumen.hijos_6_21),
    0.00,
    resumen.total_prima,
    resumen.total_prima,
    CURDATE(), 'Activo'
FROM (
    SELECT
        h.id_trabajador,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) BETWEEN 0 AND 5
                 THEN 1 ELSE 0 END) AS hijos_0_5,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) BETWEEN 6 AND 21
                 THEN 1 ELSE 0 END) AS hijos_6_21,
        SUM(CASE
            WHEN TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) BETWEEN 0 AND 5
                THEN pv_cero.valor
            WHEN TIMESTAMPDIFF(YEAR, h.fecha_nacimiento, CURDATE()) BETWEEN 6 AND 21
                THEN pv_mayor.valor
            ELSE 0 END) AS total_prima
    FROM HIJO_TRABAJADOR h
    INNER JOIN TRABAJADOR t ON t.id_trabajador = h.id_trabajador
    INNER JOIN (SELECT nombre, MIN(id_prima) AS id_prima FROM PRIMA GROUP BY nombre) p_hijos
        ON p_hijos.nombre = 'Prima por hijos'
    INNER JOIN (
        SELECT id_prima, orden, MAX(valor) AS valor
        FROM PRIMA_VALOR GROUP BY id_prima, orden
    ) pv_cero ON pv_cero.id_prima = p_hijos.id_prima AND pv_cero.orden = 1
    INNER JOIN (
        SELECT id_prima, orden, MAX(valor) AS valor
        FROM PRIMA_VALOR GROUP BY id_prima, orden
    ) pv_mayor ON pv_mayor.id_prima = p_hijos.id_prima AND pv_mayor.orden = 2
    WHERE t.cedula IN ('99900006','99900008','99900009','99900010')
    GROUP BY h.id_trabajador
) AS resumen
INNER JOIN (SELECT nombre, MIN(id_prima) AS id_prima FROM PRIMA GROUP BY nombre) p
    ON p.nombre = 'Prima por hijos'
WHERE resumen.total_prima > 0
  AND NOT EXISTS (
      SELECT 1 FROM TRABAJADOR_PRIMA tp
      WHERE tp.id_trabajador = resumen.id_trabajador
        AND tp.id_prima = p.id_prima
  );

-- ============================================================================
-- 11. CONSTANCIAS DE TRABAJO: NORMALES Y DE EGRESO
-- ============================================================================
INSERT INTO CONSTANCIA_DE_TRABAJO (
    id_trabajador, codigo_constancia, motivo, tipo_personal,
    tipo_constancia, fecha
)
SELECT t.id_trabajador, datos.codigo, datos.motivo,
       t.tipo_personal, datos.tipo_constancia, CURDATE()
FROM (
    SELECT '99900001' AS cedula, 'CT-PRUEBA-0001' AS codigo,
           'Constancia de trabajo para validacion del modulo.' AS motivo,
           'normal' AS tipo_constancia
    UNION ALL SELECT '99900009', 'CT-PRUEBA-0002',
           'Constancia de trabajo para trabajador obrero.', 'normal'
    UNION ALL SELECT '99900007', 'CE-PRUEBA-0001',
           'Constancia de egreso para trabajador jubilado.', 'egreso'
    UNION ALL SELECT '99900006', 'CE-PRUEBA-0002',
           'Constancia de egreso para trabajador inactivo.', 'egreso'
) AS datos
INNER JOIN TRABAJADOR t ON t.cedula = datos.cedula
WHERE NOT EXISTS (
    SELECT 1 FROM CONSTANCIA_DE_TRABAJO c
    WHERE c.codigo_constancia = datos.codigo
);

-- ============================================================================
-- 12. SOLICITUDES DE DISFRUTE DE VACACIONES EN DIFERENTES ESTADOS
--     Una solicitud por trabajador para no crear solicitudes activas duplicadas.
-- ============================================================================
INSERT INTO DISFRUTE_DE_VACACIONES (
    id_trabajador, id_convenio, codigo_disfrute, motivo,
    fecha_inicio, fecha_finalizacion, observaciones,
    justificacion_desaprobacion, dias_correspondientes,
    tipo_remuneracion, requiere_suplente, fecha_solicitud,
    fecha_aprobacion, fecha_desaprobacion, estado
)
SELECT
    t.id_trabajador,
    (SELECT cv.id_convenio
     FROM CONVENIO cv
     WHERE cv.id_gremio = t.id_gremio
       AND cv.estado = 'Vigente'
       AND (cv.fecha_vencimiento IS NULL OR cv.fecha_vencimiento >= CURDATE())
     ORDER BY cv.fecha_inicio DESC, cv.id_convenio DESC
     LIMIT 1),
    datos.codigo, datos.motivo,
    datos.fecha_inicio, datos.fecha_finalizacion, datos.observaciones,
    datos.justificacion, 30,
    datos.tipo_remuneracion, datos.requiere_suplente,
    datos.fecha_solicitud, datos.fecha_aprobacion,
    datos.fecha_desaprobacion, datos.estado
FROM (
    SELECT '99900008' AS cedula, 'VAC-PRUEBA-PEND-0001' AS codigo,
           'Solicitud de disfrute de vacaciones pendiente.' AS motivo,
           CURDATE() AS fecha_inicio, NULL AS fecha_finalizacion,
           'Registro de prueba pendiente de revision.' AS observaciones,
           NULL AS justificacion,
           'remunerado' AS tipo_remuneracion, 'no' AS requiere_suplente,
           NOW() AS fecha_solicitud, NULL AS fecha_aprobacion,
           NULL AS fecha_desaprobacion, 'Pendiente' AS estado
    UNION ALL SELECT '99900009', 'VAC-PRUEBA-APR-0001',
           'Solicitud de disfrute de vacaciones aprobada.',
           DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY),
           'Registro de prueba aprobado.', NULL,
           'remunerado', 'si',
           DATE_SUB(NOW(), INTERVAL 40 DAY),
           DATE_SUB(NOW(), INTERVAL 35 DAY), NULL, 'Aprobado'
    UNION ALL SELECT '99900010', 'VAC-PRUEBA-DES-0001',
           'Solicitud de disfrute de vacaciones desaprobada.',
           CURDATE(), NULL,
           'Registro de prueba desaprobado.',
           'Debe ajustarse la fecha solicitada por necesidades del servicio.',
           'no_remunerado', 'no',
           DATE_SUB(NOW(), INTERVAL 4 DAY), NULL,
           DATE_SUB(NOW(), INTERVAL 2 DAY), 'Desaprobado'
    UNION ALL SELECT '99900006', 'VAC-PRUEBA-VEN-0001',
           'Solicitud de disfrute de vacaciones vencida.',
           DATE_SUB(CURDATE(), INTERVAL 20 DAY), NULL,
           'Registro de prueba vencido por falta de procesamiento.', NULL,
           'remunerado', 'no',
           DATE_SUB(NOW(), INTERVAL 20 DAY), NULL, NULL, 'Vencida'
) AS datos
INNER JOIN TRABAJADOR t ON t.cedula = datos.cedula
WHERE NOT EXISTS (
    SELECT 1 FROM DISFRUTE_DE_VACACIONES v
    WHERE v.codigo_disfrute = datos.codigo
);

-- ============================================================================
-- 13. REGISTROS DE BITACORA PARA PROBAR EL HISTORIAL DE ACCIONES
-- ============================================================================
INSERT INTO BITACORA (
    id_usuario, usuario, modulo, accion, descripcion, fecha_hora
)
SELECT u.id_usuario, u.nombre, datos.modulo, datos.accion,
       datos.descripcion, NOW()
FROM (
    SELECT 'director.prueba' AS usuario, 'Trabajadores' AS modulo,
           'CONSULTAR' AS accion,
           'Consulta de prueba del listado de trabajadores.' AS descripcion
    UNION ALL SELECT 'analista.prueba', 'Constancias', 'REGISTRAR',
           'Registro de prueba de una constancia de trabajo.'
    UNION ALL SELECT 'estandar.prueba', 'Vacaciones', 'CONSULTAR',
           'Consulta de prueba de solicitudes propias.'
) AS datos
INNER JOIN USUARIO u ON u.nombre = datos.usuario
WHERE NOT EXISTS (
    SELECT 1 FROM BITACORA b
    WHERE b.id_usuario = u.id_usuario
      AND b.modulo = datos.modulo
      AND b.accion = datos.accion
      AND b.descripcion = datos.descripcion
);

COMMIT;

-- ============================================================================
-- 14. CONSULTAS DE VERIFICACION
-- ============================================================================

-- Trabajadores de prueba y sus relaciones principales.
SELECT
    t.cedula,
    CONCAT(t.nombres, ' ', t.apellidos) AS trabajador,
    t.tipo_personal,
    t.status,
    c.nombre_cargo AS cargo_actual,
    g.nombre AS gremio,
    d.nombre AS direccion
FROM TRABAJADOR t
LEFT JOIN CARGO c ON c.id_cargo = t.id_cargo
LEFT JOIN GREMIO g ON g.id_gremio = t.id_gremio
LEFT JOIN DIRECCION d ON d.id_dir = t.id_dir
WHERE t.cedula IN ('99900006','99900007','99900008','99900009','99900010','99900011')
ORDER BY t.cedula;

-- Usuarios y roles disponibles para iniciar sesion.
SELECT
    u.nombre AS usuario,
    u.tipo_usuario AS rol,
    CONCAT(t.nombres, ' ', t.apellidos) AS trabajador,
    u.status
FROM USUARIO u
INNER JOIN TRABAJADOR t ON t.id_trabajador = u.id_trabajador
WHERE u.nombre IN ('admin','director.prueba','analista.prueba','estandar.prueba')
ORDER BY u.id_usuario;

-- Cantidad de registros de prueba agregados por modulo.
SELECT 'GREMIO' AS modulo, COUNT(*) AS registros
FROM GREMIO WHERE nombre LIKE '%(PRUEBA)%'
UNION ALL SELECT 'DIRECCION', COUNT(*)
FROM DIRECCION WHERE nombre LIKE '%(PRUEBA)%'
UNION ALL SELECT 'TRABAJADOR', COUNT(*)
FROM TRABAJADOR WHERE cedula BETWEEN '99900006' AND '99900011'
UNION ALL SELECT 'CONTRATO', COUNT(*)
FROM CONTRATO ct INNER JOIN TRABAJADOR t ON t.id_trabajador = ct.id_trabajador
WHERE t.cedula BETWEEN '99900006' AND '99900011'
UNION ALL SELECT 'CONVENIO', COUNT(*)
FROM CONVENIO cv INNER JOIN GREMIO g ON g.id_gremio = cv.id_gremio
WHERE g.nombre LIKE '%(PRUEBA)%'
UNION ALL SELECT 'HIJO_TRABAJADOR', COUNT(*)
FROM HIJO_TRABAJADOR h INNER JOIN TRABAJADOR t ON t.id_trabajador = h.id_trabajador
WHERE t.cedula BETWEEN '99900006' AND '99900010'
UNION ALL SELECT 'CONSTANCIA_DE_TRABAJO', COUNT(*)
FROM CONSTANCIA_DE_TRABAJO WHERE codigo_constancia LIKE '%PRUEBA%'
UNION ALL SELECT 'DISFRUTE_DE_VACACIONES', COUNT(*)
FROM DISFRUTE_DE_VACACIONES WHERE codigo_disfrute LIKE '%PRUEBA%';
