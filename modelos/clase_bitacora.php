<?php
// ============================================================================
// ARCHIVO: modelos/clase_bitacora.php
// DESCRIPCIÓN: Modelo para el registro y consulta de la bitácora de
//              auditoría del sistema (quién hizo qué, cuándo y desde dónde).
// ============================================================================

class Bitacora
{
    private $conexion;

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
}
