<?php
// ============================================================================
// ARCHIVO: modelos/clase_bitacora.php
// DESCRIPCIÓN: Modelo para el registro y consulta de la bitácora de
//              auditoría del sistema (quién hizo qué, cuándo y desde dónde).
// ============================================================================

class Bitacora
{
    private $conexion;

    // Acciones que hacen "sonar" la campana del administrador. El resto
    // (Listar, Consultar, Login, Logout, Generar PDF) se ve en el panel pero
    // no notifica, para no saturar con ruido.
    const ACCIONES_ALERTA = ['Crear', 'Editar', 'Eliminar', 'Login fallido'];

    // Agrupaciones de acciones para los filtros rápidos del panel.
    const GRUPOS = [
        'cambios'   => ['Crear', 'Editar', 'Eliminar'],
        'accesos'   => ['Login', 'Logout', 'Login fallido'],
        'consultas' => ['Listar', 'Consultar', 'Generar PDF'],
    ];

    // Columnas listas para mostrar en la interfaz: fecha ya formateada y
    // "hace_seg" calculado con el reloj de MySQL (evita problemas de zona horaria).
    const COLUMNAS_VISTA = "id_bitacora AS id,
                id_usuario,
                COALESCE(usuario, 'Sistema') AS usuario,
                modulo,
                accion,
                COALESCE(descripcion, '') AS descripcion,
                DATE_FORMAT(fecha_hora, '%d/%m/%Y %H:%i') AS fecha,
                GREATEST(TIMESTAMPDIFF(SECOND, fecha_hora, NOW()), 0) AS hace_seg";

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // =========================================================================
    // REGISTRAR UNA ACCIÓN EN LA BITÁCORA
    // =========================================================================
    public function registrar($id_usuario, $usuario, $modulo, $accion, $descripcion = '')
    {
        $sql = "INSERT INTO BITACORA
                (id_usuario, usuario, modulo, accion, descripcion)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "issss",
            $id_usuario,
            $usuario,
            $modulo,
            $accion,
            $descripcion
        );

        return $stmt->execute();
    }

    // =========================================================================
    // LISTAR REGISTROS DE LA BITÁCORA (más recientes primero)
    // Admite filtros opcionales por módulo, acción, usuario y rango de fechas.
    // =========================================================================
    public function listar(array $filtros = [], int $limite = 200)
    {
        $condiciones = [];
        $parametros  = [];
        $tipos       = "";

        if (!empty($filtros['modulo'])) {
            $condiciones[] = "modulo = ?";
            $parametros[]  = $filtros['modulo'];
            $tipos        .= "s";
        }

        if (!empty($filtros['accion'])) {
            $condiciones[] = "accion = ?";
            $parametros[]  = $filtros['accion'];
            $tipos        .= "s";
        }

        if (!empty($filtros['usuario'])) {
            $condiciones[] = "usuario LIKE ?";
            $parametros[]  = "%" . $filtros['usuario'] . "%";
            $tipos        .= "s";
        }

        if (!empty($filtros['desde'])) {
            $condiciones[] = "fecha_hora >= ?";
            $parametros[]  = $filtros['desde'] . " 00:00:00";
            $tipos        .= "s";
        }

        if (!empty($filtros['hasta'])) {
            $condiciones[] = "fecha_hora <= ?";
            $parametros[]  = $filtros['hasta'] . " 23:59:59";
            $tipos        .= "s";
        }

        $sql = "SELECT id_bitacora, id_usuario, usuario, modulo, accion, descripcion, fecha_hora
                FROM BITACORA";

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }

        $sql .= " ORDER BY fecha_hora DESC LIMIT ?";
        $parametros[] = $limite;
        $tipos       .= "i";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($tipos, ...$parametros);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // CAMPANA DE NOTIFICACIONES (solo Administrador)
    // =========================================================================

    /** Id del último registro de la bitácora (0 si está vacía). */
    public function ultimoId(): int
    {
        $r = $this->conexion->query("SELECT COALESCE(MAX(id_bitacora), 0) AS ultimo FROM BITACORA");

        if (!$r) {
            return 0;
        }

        $fila = $r->fetch_assoc();
        return (int)($fila['ultimo'] ?? 0);
    }

    /**
     * Condición y parámetros comunes de "movimiento que merece alerta":
     * posterior a $desdeId, de una acción alertable y hecho por OTRO usuario
     * (o por el sistema / un intento de login fallido, que no tiene usuario).
     *
     * @return array [sql_where, tipos, parametros]
     */
    private function condicionAlertas(int $desdeId, int $idUsuario): array
    {
        $marcas = implode(',', array_fill(0, count(self::ACCIONES_ALERTA), '?'));

        return [
            "id_bitacora > ? AND accion IN ($marcas) AND (id_usuario IS NULL OR id_usuario <> ?)",
            'i' . str_repeat('s', count(self::ACCIONES_ALERTA)) . 'i',
            array_merge([$desdeId], self::ACCIONES_ALERTA, [$idUsuario]),
        ];
    }

    /** Cuántos movimientos alertables ocurrieron después de $desdeId. */
    public function contarAlertas(int $desdeId, int $idUsuario): int
    {
        [$where, $tipos, $params] = $this->condicionAlertas($desdeId, $idUsuario);

        $stmt = $this->conexion->prepare("SELECT COUNT(*) AS total FROM BITACORA WHERE $where");

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param($tipos, ...$params);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();
        return (int)($fila['total'] ?? 0);
    }

    /** Los movimientos alertables más recientes posteriores a $desdeId. */
    public function alertasDesde(int $desdeId, int $idUsuario, int $limite = 5): array
    {
        [$where, $tipos, $params] = $this->condicionAlertas($desdeId, $idUsuario);

        $sql = "SELECT " . self::COLUMNAS_VISTA . "
                FROM BITACORA
                WHERE $where
                ORDER BY id_bitacora DESC
                LIMIT ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $params[] = $limite;
        $tipos   .= 'i';

        $stmt->bind_param($tipos, ...$params);
        $stmt->execute();

        return $this->normalizar($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    /**
     * Página de registros para el panel (paginación por cursor: pide los
     * anteriores a $antesId, así no se repiten ni se saltan filas aunque
     * entren registros nuevos mientras se navega).
     *
     * Filtros: 'grupo' (cambios | accesos | consultas) y 'q' (texto libre
     * sobre usuario, módulo y descripción).
     *
     * @return array ['items' => array, 'hay_mas' => bool]
     */
    public function listarPaginado(array $filtros = [], int $antesId = 0, int $limite = 30): array
    {
        $condiciones = [];
        $parametros  = [];
        $tipos       = '';

        if ($antesId > 0) {
            $condiciones[] = 'id_bitacora < ?';
            $parametros[]  = $antesId;
            $tipos        .= 'i';
        }

        $grupo = (string)($filtros['grupo'] ?? '');

        if (isset(self::GRUPOS[$grupo])) {
            $acciones      = self::GRUPOS[$grupo];
            $condiciones[] = 'accion IN (' . implode(',', array_fill(0, count($acciones), '?')) . ')';
            $parametros    = array_merge($parametros, $acciones);
            $tipos        .= str_repeat('s', count($acciones));
        }

        $texto = trim((string)($filtros['q'] ?? ''));

        if ($texto !== '') {
            $like          = '%' . addcslashes($texto, '\\%_') . '%';
            $condiciones[] = '(usuario LIKE ? OR modulo LIKE ? OR descripcion LIKE ?)';
            $parametros    = array_merge($parametros, [$like, $like, $like]);
            $tipos        .= 'sss';
        }

        $sql = "SELECT " . self::COLUMNAS_VISTA . " FROM BITACORA";

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(' AND ', $condiciones);
        }

        // Se pide una fila de más para saber si existe una página siguiente.
        $sql         .= " ORDER BY id_bitacora DESC LIMIT ?";
        $parametros[] = $limite + 1;
        $tipos       .= 'i';

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return ['items' => [], 'hay_mas' => false];
        }

        $stmt->bind_param($tipos, ...$parametros);
        $stmt->execute();

        $filas  = $this->normalizar($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        $hayMas = count($filas) > $limite;

        return [
            'items'   => array_slice($filas, 0, $limite),
            'hay_mas' => $hayMas,
        ];
    }

    /** Convierte a enteros los campos numéricos para que el JSON salga limpio. */
    private function normalizar(array $filas): array
    {
        foreach ($filas as &$fila) {
            $fila['id']         = (int)$fila['id'];
            $fila['id_usuario'] = $fila['id_usuario'] === null ? null : (int)$fila['id_usuario'];
            $fila['hace_seg']   = (int)$fila['hace_seg'];
        }
        unset($fila);

        return $filas;
    }
}
