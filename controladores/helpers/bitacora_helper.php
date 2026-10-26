<?php
// ============================================================================
// ARCHIVO: controladores/helpers/bitacora_helper.php
// DESCRIPCIÓN: Funciones auxiliares para registrar en la BITÁCORA las acciones
//              de los usuarios y los intentos de acceso no permitidos.
//
// USO BÁSICO:
//   require_once __DIR__ . '/helpers/bitacora_helper.php';
//   registrarBitacora($conexion, 'Trabajadores', 'Crear', "Registró al trabajador Ana Pérez (CI 123).");
//
// USO PARA EDITAR / ELIMINAR (con nombres en lugar de ID):
//   $antes = bitacoraSnapshot($conexion, 'cargo', $id);   // ANTES de modificar
//   ... se ejecuta el UPDATE ...
//   $despues = bitacoraSnapshot($conexion, 'cargo', $id); // DESPUÉS de modificar
//   registrarBitacora($conexion, 'Cargos', 'Editar',
//       'Actualizó el cargo "' . bitacoraNombre($antes) . '": ' . bitacoraCambios($antes, $despues));
//
//   $antes = bitacoraSnapshot($conexion, 'cargo', $id);   // ANTES de borrar
//   ... se ejecuta el DELETE ...
//   registrarBitacora($conexion, 'Cargos', 'Eliminar', 'Eliminó el cargo "' . bitacoraNombre($antes, $id) . '".');
// ============================================================================

require_once __DIR__ . '/../../modelos/clase_bitacora.php';

if (!function_exists('registrarBitacora')) {

    /**
     * Registra una acción del usuario autenticado (o del sistema) en la bitácora.
     * Nunca interrumpe el flujo normal de la aplicación: si algo falla al
     * escribir el registro (p. ej. la tabla aún no existe), solo se deja
     * constancia en el log de errores de PHP.
     *
     * @param mysqli $conexion    Conexión activa a la base de datos.
     * @param string $modulo      Módulo/entidad afectada (ej: "Trabajadores").
     * @param string $accion      "Crear" | "Editar" | "Eliminar" | "Login" | "Logout" | "Listar" |
     *                            "Consultar" | "Generar PDF" | "Acceso denegado" | "Acceso sin sesión"
     * @param string $descripcion Detalle legible de la acción realizada.
     */
    function registrarBitacora($conexion, string $modulo, string $accion, string $descripcion = ''): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        try {
            if (!($conexion instanceof mysqli)) {
                return;
            }

            $bitacora = new Bitacora($conexion);

            $id_usuario = $_SESSION['id_usuario'] ?? null;
            $usuario    = $_SESSION['usuario'] ?? 'Sistema';

            $bitacora->registrar($id_usuario, $usuario, $modulo, $accion, $descripcion);
        } catch (\Throwable $e) {
            error_log('Error al registrar bitácora: ' . $e->getMessage());
        }
    }
}

// ============================================================================
// NOMBRES EN LUGAR DE ID
// ============================================================================

if (!function_exists('bitacoraDefinicion')) {

    /**
     * Consulta de cada entidad: devuelve UNA fila con
     *   _nombre  → el nombre legible con el que se identifica el registro
     *   resto    → los campos que interesa comparar (clave = etiqueta en español),
     *              con las llaves foráneas ya resueltas a su nombre.
     * El único parámetro es el ID del registro (entero).
     */
    function bitacoraDefinicion(string $entidad): ?string
    {
        static $sql = null;

        if ($sql === null) {
            $trabajador = "CONCAT(t.nombres, ' ', t.apellidos, ' (CI ', t.cedula, ')')";
            $ubicacion  = "CONCAT_WS(', ', d.nombre, p.nombre, m.nombre, e.nombre)";

            $sql = [
                'estado' => "SELECT e.nombre AS _nombre, e.nombre AS `Nombre`
                             FROM ESTADO e WHERE e.cod_est = ?",

                'municipio' => "SELECT m.nombre AS _nombre, m.nombre AS `Nombre`, e.nombre AS `Estado`
                                FROM MUNICIPIO m
                                LEFT JOIN ESTADO e ON e.cod_est = m.cod_est
                                WHERE m.cod_muni = ?",

                'parroquia' => "SELECT p.nombre AS _nombre, p.nombre AS `Nombre`,
                                       m.nombre AS `Municipio`, e.nombre AS `Estado`
                                FROM PARROQUIA p
                                LEFT JOIN MUNICIPIO m ON m.cod_muni = p.cod_muni
                                LEFT JOIN ESTADO e ON e.cod_est = m.cod_est
                                WHERE p.cod_par = ?",

                'direccion' => "SELECT $ubicacion AS _nombre, d.nombre AS `Dirección`,
                                       p.nombre AS `Parroquia`, m.nombre AS `Municipio`, e.nombre AS `Estado`
                                FROM DIRECCION d
                                LEFT JOIN PARROQUIA p ON p.cod_par = d.cod_par
                                LEFT JOIN MUNICIPIO m ON m.cod_muni = p.cod_muni
                                LEFT JOIN ESTADO e ON e.cod_est = m.cod_est
                                WHERE d.id_dir = ?",

                'cargo' => "SELECT c.nombre_cargo AS _nombre, c.nombre_cargo AS `Nombre`
                            FROM CARGO c WHERE c.id_cargo = ?",

                'prima' => "SELECT pr.tipo_prima AS _nombre, pr.tipo_prima AS `Tipo de prima`,
                                   pr.porcentaje AS `Porcentaje`, pr.estado AS `Estado`, pr.fecha AS `Fecha`
                            FROM PRIMA pr WHERE pr.id_prima = ?",

                'usuario' => "SELECT u.nombre AS _nombre, u.nombre AS `Nombre de usuario`,
                                     u.tipo_usuario AS `Rol`, u.status AS `Estatus`,
                                     $trabajador AS `Trabajador`
                              FROM USUARIO u
                              LEFT JOIN TRABAJADOR t ON t.id_trabajador = u.id_trabajador
                              WHERE u.id_usuario = ?",

                'trabajador' => "SELECT $trabajador AS _nombre,
                                        t.estado_civil AS `Estado civil`, t.telefono AS `Teléfono`,
                                        t.correo AS `Correo`, t.status AS `Estatus laboral`,
                                        cg.nombre_cargo AS `Cargo`,
                                        $ubicacion AS `Dirección`
                                 FROM TRABAJADOR t
                                 LEFT JOIN CARGO cg ON cg.id_cargo = t.id_cargo
                                 LEFT JOIN DIRECCION d ON d.id_dir = t.id_dir
                                 LEFT JOIN PARROQUIA p ON p.cod_par = d.cod_par
                                 LEFT JOIN MUNICIPIO m ON m.cod_muni = p.cod_muni
                                 LEFT JOIN ESTADO e ON e.cod_est = m.cod_est
                                 WHERE t.id_trabajador = ?",

                'salario' => "SELECT CONCAT(IF(s.tipo_salario = 'cargo', CONCAT('cargo ', IFNULL(c.nombre_cargo, '?')), 'base'),
                                            ' de ', s.monto, ' (', s.fecha, ')') AS _nombre,
                                     s.tipo_salario AS `Tipo`, c.nombre_cargo AS `Cargo`,
                                     s.monto AS `Monto`, s.fecha AS `Fecha`, s.estado AS `Estado`
                              FROM SALARIO s
                              LEFT JOIN CARGO c ON c.id_cargo = s.id_cargo
                              WHERE s.id_salario = ?",

                'contrato' => "SELECT CONCAT(k.tipo_contrato, ' de ', t.nombres, ' ', t.apellidos) AS _nombre,
                                      $trabajador AS `Trabajador`, c.nombre_cargo AS `Cargo`,
                                      k.tipo_contrato AS `Tipo de contrato`,
                                      k.fecha_contrato AS `Fecha de inicio`, k.fecha_fin AS `Fecha de fin`,
                                      k.lugar_trabajo AS `Lugar de trabajo`,
                                      k.nombre_presidente AS `Presidente`,
                                      k.cedula_presidente AS `Cédula del presidente`,
                                      k.gaceta_designacion_presidente AS `Gaceta`
                               FROM CONTRATO k
                               LEFT JOIN TRABAJADOR t ON t.id_trabajador = k.id_trabajador
                               LEFT JOIN CARGO c ON c.id_cargo = k.id_cargo
                               WHERE k.id_contrato = ?",

                // Solicitud (también cubre "días de disfrute", que cuelgan de una solicitud)
                'solicitud' => "SELECT CONCAT(s.tipo_solicitud, ' ', IFNULL(s.codigo_solicitud, ''), ' de ',
                                              t.nombres, ' ', t.apellidos) AS _nombre,
                                       $trabajador AS `Trabajador`, s.tipo_solicitud AS `Tipo`,
                                       s.motivo_solicitud AS `Motivo`,
                                       s.fecha_inicio AS `Fecha de inicio`, s.fecha_finalizacion AS `Fecha de fin`
                                FROM SOLICITUD s
                                LEFT JOIN TRABAJADOR t ON t.id_trabajador = s.id_trabajador
                                WHERE s.id_solicitud = ?",

                'constancia' => "SELECT CONCAT('constancia ', IFNULL(s.codigo_solicitud, ''), ' de ',
                                               t.nombres, ' ', t.apellidos) AS _nombre,
                                        $trabajador AS `Trabajador`,
                                        k.nombre_director_departamento AS `Director`,
                                        k.tipo_personal AS `Tipo de personal`,
                                        k.fecha AS `Fecha`, s.motivo_solicitud AS `Motivo`
                                 FROM CONSTANCIA_DE_TRABAJO k
                                 LEFT JOIN SOLICITUD s ON s.id_solicitud = k.id_solicitud
                                 LEFT JOIN TRABAJADOR t ON t.id_trabajador = s.id_trabajador
                                 WHERE k.id_constancia = ?",
            ];
        }

        return $sql[$entidad] ?? null;
    }

    /**
     * Foto del registro (con nombres en vez de ID). Llamar ANTES de modificar
     * o eliminar, y otra vez DESPUÉS de modificar para comparar.
     *
     * @return array Campos etiquetados + '_nombre'; array vacío si no existe.
     */
    function bitacoraSnapshot($conexion, string $entidad, $id): array
    {
        try {
            $consulta = bitacoraDefinicion($entidad);
            $id       = (int) $id;

            if ($consulta === null || $id <= 0 || !($conexion instanceof mysqli)) {
                return [];
            }

            $stmt = $conexion->prepare($consulta);

            if (!$stmt) {
                return [];
            }

            $stmt->bind_param('i', $id);
            $stmt->execute();

            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $fila ?: [];
        } catch (\Throwable $e) {
            error_log('Bitácora (snapshot ' . $entidad . '): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Nombre legible del registro a partir de su foto. Si el registro ya no
     * existe (o no se pudo leer) se muestra el ID de respaldo para no perder
     * trazabilidad.
     */
    function bitacoraNombre(array $snapshot, $idRespaldo = null): string
    {
        $nombre = trim((string) ($snapshot['_nombre'] ?? ''));

        if ($nombre !== '') {
            return $nombre;
        }

        return $idRespaldo !== null ? 'ID ' . (int) $idRespaldo : 'registro desconocido';
    }

    /** Atajo: nombre legible directo desde el ID (para Crear, donde solo hay IDs de referencia). */
    function bitacoraNombreDe($conexion, string $entidad, $id): string
    {
        return bitacoraNombre(bitacoraSnapshot($conexion, $entidad, $id), $id);
    }

    /**
     * Compara dos fotos y describe lo que cambió: campo: "antes" → "después".
     * Los campos sin cambios no se muestran.
     */
    function bitacoraCambios(array $antes, array $despues): string
    {
        $partes = [];

        foreach ($despues as $campo => $nuevo) {
            if ($campo === '_nombre') {
                continue;
            }

            $anterior = $antes[$campo] ?? null;

            if ((string) $anterior === (string) $nuevo) {
                continue;
            }

            $partes[] = $campo . ': ' . bitacoraValor($anterior) . ' → ' . bitacoraValor($nuevo);
        }

        return empty($partes) ? 'sin cambios en los datos.' : implode('; ', $partes) . '.';
    }

    function bitacoraValor($valor): string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? '(vacío)' : '"' . $valor . '"';
    }
}

// ============================================================================
// INTENTOS DE ACCESO NO PERMITIDOS (sin sesión / rol sin permiso)
// ============================================================================

if (!function_exists('bitacoraModuloDeUrl')) {

    /** Nombre del módulo al que pertenece un archivo, según su nombre. */
    function bitacoraModuloDeUrl(string $ruta): string
    {
        $archivo = strtolower(basename(parse_url($ruta, PHP_URL_PATH) ?: $ruta));

        $mapa = [
            'usuario'      => 'Usuarios',
            'trabajador'   => 'Trabajadores',
            'contrato'     => 'Contratos',
            'constancia'   => 'Constancias',
            'dias_disfrute'=> 'Días de Disfrute',
            'solicitud'    => 'Solicitudes',
            'salario'      => 'Salarios',
            'prima'        => 'Primas',
            'asignar_cargo'=> 'Asignación de Cargos',
            'cargo'        => 'Cargos',
            'parroquia'    => 'Parroquias',
            'municipio'    => 'Municipios',
            'estado'       => 'Estados',
            'direccion'    => 'Direcciones',
            'bitacora'     => 'Bitácora',
            'dashboard'    => 'Dashboard',
        ];

        foreach ($mapa as $clave => $modulo) {
            if (strpos($archivo, $clave) !== false) {
                return $modulo;
            }
        }

        return 'Seguridad';
    }

    /** Conexión reutilizable aunque el archivo que llama no haya incluido conexion.php. */
    function bitacoraConexion()
    {
        static $propia = null;

        if (isset($GLOBALS['conexion']) && $GLOBALS['conexion'] instanceof mysqli) {
            return $GLOBALS['conexion'];
        }

        if ($propia === null) {
            // include dentro de una función: las variables de conexion.php quedan locales.
            $propia = (static function () {
                include __DIR__ . '/../../conexion.php';
                return $conexion ?? false;
            })();
        }

        return $propia ?: null;
    }

    /** Ruta pedida, sin parámetros (los parámetros pueden traer datos sensibles) y sin caracteres de control. */
    function bitacoraRutaPedida(): string
    {
        $ruta = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $ruta = preg_replace('/[\x00-\x1F\x7F]/', '', $ruta);

        return mb_substr($ruta !== '' ? $ruta : 'desconocida', 0, 200);
    }

    /**
     * Registra un intento de acceso que no debe ocurrir.
     *   · sin sesión  → accion "Acceso sin sesión"  (usuario = "Visitante sin sesión")
     *   · rol sin permiso → accion "Acceso denegado" (usuario = el de la sesión)
     * Si el mismo evento se repite en menos de 60 s (F5, redirecciones) no se
     * duplica, para que un solo intento no inunde la bitácora ni la campana.
     *
     * @param string[] $rolesRequeridos Roles que sí tienen acceso (solo para "Acceso denegado").
     */
    function registrarAccesoRestringido(bool $haySesion, array $rolesRequeridos = []): void
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $conexion = bitacoraConexion();

            if (!($conexion instanceof mysqli)) {
                return;
            }

            $ruta   = bitacoraRutaPedida();
            $modulo = bitacoraModuloDeUrl($ruta);

            if ($haySesion) {
                $accion     = 'Acceso denegado';
                $idUsuario  = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null;
                $usuario    = (string) ($_SESSION['usuario'] ?? 'Desconocido');
                $rol        = (string) ($_SESSION['tipo_usuario'] ?? 'sin rol');
                $descripcion = "El usuario \"$usuario\" (rol $rol) intentó entrar por URL a \"$ruta\" [$metodo], "
                             . "módulo $modulo, sin tener permiso"
                             . (!empty($rolesRequeridos) ? ' (requiere: ' . implode(' o ', $rolesRequeridos) . ')' : '')
                             . ". IP $ip.";
            } else {
                $accion      = 'Acceso sin sesión';
                $idUsuario   = null;
                $usuario     = 'Visitante sin sesión';
                $descripcion = "Intentó entrar por URL a \"$ruta\" [$metodo], módulo $modulo, sin haber iniciado sesión.";
            }

            $descripcion = mb_substr($descripcion, 0, 500);

            // Antirrebote: mismo evento en los últimos 60 s → no se vuelve a registrar.
            $stmt = $conexion->prepare(
                "SELECT 1 FROM BITACORA
                 WHERE accion = ? AND usuario = ? AND descripcion = ?
                   AND fecha_hora > (NOW() - INTERVAL 60 SECOND)
                 LIMIT 1"
            );

            if ($stmt) {
                $stmt->bind_param('sss', $accion, $usuario, $descripcion);
                $stmt->execute();

                if ($stmt->get_result()->num_rows > 0) {
                    return;
                }
            }

            (new Bitacora($conexion))->registrar($idUsuario, $usuario, $modulo, $accion, $descripcion);
        } catch (\Throwable $e) {
            error_log('Error al registrar acceso restringido: ' . $e->getMessage());
        }
    }
}
