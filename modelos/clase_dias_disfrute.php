<?php
// Acceso a datos del módulo independiente de días de disfrute.
class DiasDisfrute {
    private $db;
    public $ultimoError = '';

    public function __construct($conexion = null) {
        if ($conexion !== null) {
            $this->db = $conexion;
        } else {
            global $conexion;
            $this->db = $conexion;
        }
    }

    private function esContratoIndefinido($tipoContrato) {
        $tipoContrato = strtolower(trim((string) $tipoContrato));
        return strpos($tipoContrato, 'indefinido') !== false
            || strpos($tipoContrato, 'indeterminado') !== false;
    }

    public function esElegibleDiasDisfrute($tipoContrato, $fechaIngreso) {
        $tipoContrato = trim((string) $tipoContrato);

        if ($tipoContrato === '') {
            return false;
        }

        if ($this->esContratoIndefinido($tipoContrato)) {
            return true;
        }

        $fechaIngreso = trim((string) $fechaIngreso);
        if ($fechaIngreso === '') {
            return false;
        }

        try {
            $zonaHoraria = new DateTimeZone('America/Caracas');
            $ingreso = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaIngreso, $zonaHoraria);
            $erroresFecha = DateTimeImmutable::getLastErrors();

            if (
                !$ingreso ||
                $ingreso->format('Y-m-d') !== $fechaIngreso ||
                ($erroresFecha !== false && ($erroresFecha['warning_count'] > 0 || $erroresFecha['error_count'] > 0))
            ) {
                return false;
            }

            $hoy = new DateTimeImmutable('today', $zonaHoraria);
            $aniversarioUnAnio = $ingreso->modify('+1 year');

            // Debe haber transcurrido más de un año, no solo cumplirse hoy el aniversario.
            return $hoy > $aniversarioUnAnio;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function motivoNoElegibilidadDiasDisfrute($tipoContrato, $fechaIngreso) {
        $tipoContrato = trim((string) $tipoContrato);

        if ($tipoContrato === '') {
            return 'no tiene un contrato registrado';
        }

        if ($this->esContratoIndefinido($tipoContrato)) {
            return '';
        }

        if (trim((string) $fechaIngreso) === '') {
            return 'no tiene una fecha de ingreso válida';
        }

        return $this->esElegibleDiasDisfrute($tipoContrato, $fechaIngreso)
            ? ''
            : 'requiere más de un año desde la fecha de ingreso';
    }

    public function listarTrabajadoresDiasDisfrute() {
        $sql = "SELECT
                    t.id_trabajador,
                    t.cedula,
                    CONCAT(t.apellidos, ' ', t.nombres) AS nombre_completo,
                    c.nombre_cargo AS cargo,
                    ingreso.fecha_primer_contrato,
                    co.tipo_contrato
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN (
                    SELECT id_trabajador, MIN(fecha_contrato) AS fecha_primer_contrato
                    FROM CONTRATO
                    GROUP BY id_trabajador
                ) ingreso ON ingreso.id_trabajador = t.id_trabajador
                LEFT JOIN CONTRATO co ON co.id_contrato = (
                    SELECT co2.id_contrato
                    FROM CONTRATO co2
                    WHERE co2.id_trabajador = t.id_trabajador
                    ORDER BY co2.id_contrato DESC
                    LIMIT 1
                )
                WHERE t.status = 'Activo'
                ORDER BY t.apellidos ASC, t.nombres ASC";

        $resultado = $this->db->query($sql);
        if (!$resultado) {
            $this->ultimoError = $this->db->error;
            return [];
        }

        $trabajadores = $resultado->fetch_all(MYSQLI_ASSOC);
        foreach ($trabajadores as &$trabajador) {
            $tipoContrato = $trabajador['tipo_contrato'] ?? '';
            $fechaIngreso = $trabajador['fecha_primer_contrato'] ?? '';
            $trabajador['elegible_disfrute'] = $this->esElegibleDiasDisfrute($tipoContrato, $fechaIngreso);
            $trabajador['motivo_inelegibilidad'] = $trabajador['elegible_disfrute']
                ? ''
                : $this->motivoNoElegibilidadDiasDisfrute($tipoContrato, $fechaIngreso);
        }
        unset($trabajador);

        return $trabajadores;
    }

    public function obtenerContratoTrabajador($id_trabajador) {
        $sql = "SELECT t.cedula, ingreso.fecha_primer_contrato, co.tipo_contrato
                FROM TRABAJADOR t
                LEFT JOIN CONTRATO co ON co.id_contrato = (
                    SELECT co2.id_contrato
                    FROM CONTRATO co2
                    WHERE co2.id_trabajador = t.id_trabajador
                    ORDER BY co2.id_contrato DESC
                    LIMIT 1
                )
                LEFT JOIN (
                    SELECT id_trabajador, MIN(fecha_contrato) AS fecha_primer_contrato
                    FROM CONTRATO
                    GROUP BY id_trabajador
                ) ingreso ON ingreso.id_trabajador = t.id_trabajador
                WHERE t.id_trabajador = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_trabajador);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function registrarDiasDisfrute($id_trabajador, $codigo_disfrute, $motivo,
                                           $fecha_inicio, $fecha_finalizacion,
                                           $observaciones) {

        try {
            $fecha_final_val = $fecha_finalizacion !== '' ? $fecha_finalizacion : null;
            // Todo registro nuevo entra como "Pendiente": el Director o Administrador
            // lo aprueba/desaprueba desde el catálogo de aprobaciones.
            $sqlDis = "INSERT INTO DISFRUTE_DE_VACACIONES
                       (id_trabajador, codigo_disfrute, motivo, fecha_inicio,
                        fecha_finalizacion, observaciones, estado)
                       VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";
            $stmtDis = $this->db->prepare($sqlDis);
            if (!$stmtDis) throw new Exception("Error al preparar DISFRUTE_DE_VACACIONES: " . $this->db->error);
            $stmtDis->bind_param("isssss", $id_trabajador, $codigo_disfrute, $motivo, $fecha_inicio, $fecha_final_val, $observaciones);
            if (!$stmtDis->execute()) throw new Exception("Error al ejecutar DISFRUTE_DE_VACACIONES: " . $stmtDis->error);

            return $this->db->insert_id;

        } catch (\Throwable $e) {
            error_log("Error registrar dias de disfrute: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string $buscar Texto libre (trabajador o código del registro).
     * @param string $estado 'Pendiente' | 'Aprobado' | 'Desaprobado' | '' (todos).
     */
    public function mostrarDiasDisfrute($buscar = '', $estado = '') {
        $sql = "SELECT
                    d.id_dis,
                    d.codigo_disfrute,
                    d.motivo,
                    d.motivo AS descripcion,
                    d.fecha_inicio,
                    d.fecha_finalizacion,
                    d.observaciones,
                    d.justificacion_desaprobacion,
                    d.estado,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    c.nombre_cargo AS cargo
                FROM DISFRUTE_DE_VACACIONES d
                INNER JOIN TRABAJADOR t ON d.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo";

        $condiciones = [];
        $tipos = '';
        $parametros = [];

        if ($buscar !== '') {
            $condiciones[] = "(t.nombres LIKE ? OR t.apellidos LIKE ? OR d.codigo_disfrute LIKE ?)";
            $param = "%{$buscar}%";
            $tipos .= 'sss';
            $parametros[] = $param;
            $parametros[] = $param;
            $parametros[] = $param;
        }

        if (in_array($estado, ['Pendiente', 'Aprobado', 'Desaprobado'], true)) {
            $condiciones[] = "d.estado = ?";
            $tipos .= 's';
            $parametros[] = $estado;
        }

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }

        $sql .= " ORDER BY d.id_dis DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        if ($tipos !== '') {
            $stmt->bind_param($tipos, ...$parametros);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtiene todos los datos de un único registro de días de disfrute
     * (para la vista de detalle "Ver").
     */
    public function obtenerDiasDisfrutePorId($id_dis) {
        $sql = "SELECT
                    d.id_dis,
                    d.codigo_disfrute,
                    d.motivo,
                    d.motivo AS descripcion,
                    d.fecha_inicio,
                    d.fecha_finalizacion,
                    d.observaciones,
                    d.justificacion_desaprobacion,
                    d.estado,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    c.nombre_cargo AS cargo
                FROM DISFRUTE_DE_VACACIONES d
                INNER JOIN TRABAJADOR t ON d.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                WHERE d.id_dis = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_dis);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Actualiza estado y justificación de desaprobación. */
    public function cambiarEstadoDiasDisfrute($id_dis, $nuevoEstado, $justificacion = '') {
        if (!in_array($nuevoEstado, ['Aprobado', 'Desaprobado'], true)) {
            return false;
        }
        $justificacion = trim((string) $justificacion);
        if ($nuevoEstado === 'Desaprobado' && $justificacion === '') {
            $this->ultimoError = 'La justificación de desaprobación es obligatoria.';
            return false;
        }
        if ($nuevoEstado === 'Aprobado') {
            $justificacion = '';
        }
        $sql = "UPDATE DISFRUTE_DE_VACACIONES SET estado = ?, justificacion_desaprobacion = ? WHERE id_dis = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("ssi", $nuevoEstado, $justificacion, $id_dis);
        try {
            if (!$stmt->execute() || $stmt->affected_rows < 0) return false;
            return $nuevoEstado;
        } catch (mysqli_sql_exception $e) {
            error_log("Cambiar estado dias de disfrute id_dis={$id_dis}: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /**
     * Estado actual de un registro ('Aprobado' | 'Desaprobado' | null si no existe).
     */
    public function obtenerEstadoDiasDisfrute($id_dis) {
        $sql = "SELECT estado FROM DISFRUTE_DE_VACACIONES WHERE id_dis = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $id_dis);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        return $fila['estado'] ?? null;
    }

    public function eliminarDiasDisfrute($id_dis) {
        $sql = "DELETE FROM DISFRUTE_DE_VACACIONES WHERE id_dis = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id_dis);

        try {
            $stmt->execute();
            return true;
        } catch (mysqli_sql_exception $e) {
            error_log("Eliminar dias de disfrute id_dis={$id_dis}: " . $e->getMessage());
            return false;
        }
    }
}
