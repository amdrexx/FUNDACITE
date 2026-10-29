<?php
// Acceso a datos del módulo independiente de constancias de trabajo.
class ConstanciaTrabajo {
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

    /**
     * Calcula la remuneración actual sin duplicar el salario en cada prima.
     * Se toma el salario vigente del cargo; si no existe, el salario base
     * individual y, como último recurso, el salario base general. A ese importe
     * se le suman los montos de las primas activas (columna PRIMA.monto).
     * PRIMA.monto_total no se suma porque cada registro puede contener salario + prima.
     */
    private function agregarRemuneracion(array $trabajador): array {
        $idTrabajador = (int) ($trabajador['id_trabajador'] ?? 0);
        $idCargo = (int) ($trabajador['id_cargo'] ?? 0);

        if ($idTrabajador <= 0) {
            $trabajador['salario_monto'] = 0.0;
            $trabajador['total_primas'] = 0.0;
            $trabajador['total_devengado'] = 0.0;
            $trabajador['origen_salario'] = 'Sin salario registrado';
            return $trabajador;
        }

        $sql = "SELECT
                    COALESCE(
                        (SELECT s.monto FROM SALARIO s
                         WHERE s.tipo_salario = 'cargo' AND s.id_cargo = NULLIF(?, 0)
                           AND s.estado = 'Vigente' AND s.monto > 0
                         ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1),
                        (SELECT s.monto FROM SALARIO s
                         WHERE s.tipo_salario = 'base' AND s.id_trabajador = ?
                           AND s.estado = 'Vigente' AND s.monto > 0
                         ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1),
                        (SELECT s.monto FROM SALARIO s
                         WHERE s.tipo_salario = 'base' AND s.id_trabajador IS NULL
                           AND s.estado = 'Vigente' AND s.monto > 0
                         ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1),
                        0
                    ) AS salario_monto,
                    COALESCE((SELECT SUM(p.monto) FROM PRIMA p
                              WHERE p.id_trabajador = ? AND p.estatus = 'Activo'), 0) AS total_primas,
                    CASE
                        WHEN EXISTS (SELECT 1 FROM SALARIO s WHERE s.tipo_salario = 'cargo'
                                     AND s.id_cargo = NULLIF(?, 0) AND s.estado = 'Vigente' AND s.monto > 0)
                            THEN 'Salario por cargo'
                        WHEN EXISTS (SELECT 1 FROM SALARIO s WHERE s.tipo_salario = 'base'
                                     AND s.id_trabajador = ? AND s.estado = 'Vigente' AND s.monto > 0)
                            THEN 'Salario base del trabajador'
                        WHEN EXISTS (SELECT 1 FROM SALARIO s WHERE s.tipo_salario = 'base'
                                     AND s.id_trabajador IS NULL AND s.estado = 'Vigente' AND s.monto > 0)
                            THEN 'Salario base'
                        ELSE 'Sin salario registrado'
                    END AS origen_salario";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $trabajador['salario_monto'] = 0.0;
            $trabajador['total_primas'] = 0.0;
            $trabajador['total_devengado'] = 0.0;
            $trabajador['origen_salario'] = 'Sin salario registrado';
            $this->ultimoError = $this->db->error;
            return $trabajador;
        }

        $stmt->bind_param('iiiii', $idCargo, $idTrabajador, $idTrabajador, $idCargo, $idTrabajador);
        if (!$stmt->execute()) {
            $stmt->close();
            $trabajador['salario_monto'] = 0.0;
            $trabajador['total_primas'] = 0.0;
            $trabajador['total_devengado'] = 0.0;
            $trabajador['origen_salario'] = 'Sin salario registrado';
            return $trabajador;
        }

        $remuneracion = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        $salario = (float) ($remuneracion['salario_monto'] ?? 0);
        $primas = (float) ($remuneracion['total_primas'] ?? 0);
        $trabajador['salario_monto'] = round($salario, 2);
        $trabajador['total_primas'] = round($primas, 2);
        $trabajador['total_devengado'] = round($salario + $primas, 2);
        $trabajador['origen_salario'] = $remuneracion['origen_salario'] ?? 'Sin salario registrado';
        return $trabajador;
    }

    public function listarTrabajadoresActivos() {
        $sql = "SELECT 
                    t.id_trabajador,
                    t.id_cargo,
                    t.cedula,
                    CONCAT(t.apellidos, ' ', t.nombres) AS nombre_completo,
                    c.nombre_cargo AS cargo
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                WHERE t.status = 'Activo'
                ORDER BY t.apellidos ASC";

        $resultado = $this->db->query($sql);
        return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerTrabajadorCompleto($id_trabajador) {
        $sql = "SELECT 
                    t.id_trabajador,
                    t.id_cargo,
                    t.cedula,
                    CONCAT(t.apellidos, ' ', t.nombres) AS nombre_completo,
                    c.nombre_cargo AS cargo,
                    contrato_actual.tipo_personal,
                    ingreso.fecha_primer_contrato
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN CONTRATO contrato_actual ON contrato_actual.id_contrato = (
                    SELECT co.id_contrato
                    FROM CONTRATO co
                    WHERE co.id_trabajador = t.id_trabajador
                    ORDER BY co.id_contrato DESC
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
        $trabajador = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $trabajador ? $this->agregarRemuneracion($trabajador) : null;
    }


    public function listarPrimasActivas(int $id_trabajador): array {
        $sql = "SELECT tipo_prima, condicion, porcentaje, monto FROM PRIMA
                WHERE id_trabajador = ? AND estatus = 'Activo'
                ORDER BY tipo_prima ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $id_trabajador);
        if (!$stmt->execute()) { $stmt->close(); return []; }
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $resultado;
    }

    public function obtenerDirectorActivo() {
        $sql = "SELECT t.id_trabajador, t.nombres, t.apellidos, t.cedula, t.tipo_documento, t.genero, c.nombre_cargo
                FROM TRABAJADOR t
                INNER JOIN CARGO c ON c.id_cargo = t.id_cargo
                WHERE LOWER(TRIM(t.status)) = 'activo'
                  AND LOWER(c.nombre_cargo) LIKE '%director%'
                ORDER BY (LOWER(TRIM(c.nombre_cargo)) = 'director') DESC, t.id_trabajador DESC
                LIMIT 1";

        $resultado = $this->db->query($sql);
        return $resultado ? $resultado->fetch_assoc() : null;
    }

    /**
     * Una constancia solo puede registrarse si existe un salario vigente y mayor
     * que cero para el cargo del trabajador, su salario base individual o el
     * salario base general.
     */
    public function tieneSalarioRegistrado($id_trabajador): bool {
        $trabajador = $this->obtenerTrabajadorCompleto((int) $id_trabajador);
        return $trabajador !== null
            && (float) ($trabajador['salario_monto'] ?? 0) > 0
            && ($trabajador['origen_salario'] ?? 'Sin salario registrado') !== 'Sin salario registrado';
    }

    public function registrarConstancia($id_trabajador, $codigo_constancia, $motivo,
                                        $tipo_personal, $fecha, $tipo_constancia = 'normal') {

        try {
            if (!$this->tieneSalarioRegistrado((int) $id_trabajador)) {
                $this->ultimoError = 'No se puede registrar la constancia: el trabajador no tiene un salario vigente registrado, ni por cargo ni como salario base.';
                return false;
            }

            if (!in_array($tipo_constancia, ['normal', 'primas_detalladas'], true)) {
                $tipo_constancia = 'normal';
            }
            $sqlConst = "INSERT INTO CONSTANCIA_DE_TRABAJO
                         (id_trabajador, codigo_constancia, motivo, tipo_personal, fecha, tipo_constancia)
                         VALUES (?, ?, ?, ?, ?, ?)";
            $stmtConst = $this->db->prepare($sqlConst);
            if (!$stmtConst) throw new Exception("Error al preparar CONSTANCIA_DE_TRABAJO: " . $this->db->error);
            $stmtConst->bind_param("isssss", $id_trabajador, $codigo_constancia, $motivo, $tipo_personal, $fecha, $tipo_constancia);
            if (!$stmtConst->execute()) throw new Exception("Error al ejecutar CONSTANCIA_DE_TRABAJO: " . $stmtConst->error);

            $id_constancia = $this->db->insert_id;
            return $id_constancia;

        } catch (\Throwable $e) {
            error_log("Error registrar constancia: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function obtenerPorId($id_constancia) {
        $sql = "SELECT 
                    ct.id_constancia,
                    ct.tipo_personal,
                    ct.fecha AS fecha_constancia,
                    ct.codigo_constancia,
                    ct.motivo,
                    ct.tipo_constancia,
                    ct.fecha AS fecha_inicio,
                    t.id_trabajador,
                    t.id_cargo,
                    t.nombres,
                    t.apellidos,
                    t.cedula,
                    t.genero,
                    ingreso.fecha_primer_contrato,
                    c.nombre_cargo
                FROM CONSTANCIA_DE_TRABAJO ct
                INNER JOIN TRABAJADOR t ON ct.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN (
                    SELECT id_trabajador, MIN(fecha_contrato) AS fecha_primer_contrato
                    FROM CONTRATO
                    GROUP BY id_trabajador
                ) ingreso ON ingreso.id_trabajador = t.id_trabajador
                WHERE ct.id_constancia = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_constancia);
        $stmt->execute();
        $constancia = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $constancia ? $this->agregarRemuneracion($constancia) : null;
    }

    public function buscarPorId($id_constancia) {
        $fila = $this->obtenerPorId($id_constancia);
        if (!$fila) return null;

        $fila['fecha']           = $fila['fecha_constancia'] ?? null;
        return $fila;
    }

    public function actualizar($id_constancia, $id_trabajador, $motivo,
                                $tipo_personal, $fecha, $tipo_constancia = 'normal') {

        try {
            if (!$this->tieneSalarioRegistrado((int) $id_trabajador)) {
                $this->ultimoError = 'No se puede actualizar la constancia: el trabajador no tiene un salario vigente registrado, ni por cargo ni como salario base.';
                return false;
            }

            if (!in_array($tipo_constancia, ['normal', 'primas_detalladas'], true)) {
                $tipo_constancia = 'normal';
            }
            $sqlConst = "UPDATE CONSTANCIA_DE_TRABAJO
                         SET id_trabajador = ?, motivo = ?, tipo_personal = ?, fecha = ?, tipo_constancia = ?
                         WHERE id_constancia = ?";
            $stmtConst = $this->db->prepare($sqlConst);
            if (!$stmtConst) throw new Exception("Error al preparar CONSTANCIA_DE_TRABAJO: " . $this->db->error);
            $stmtConst->bind_param("issssi", $id_trabajador, $motivo, $tipo_personal, $fecha, $tipo_constancia, $id_constancia);
            if (!$stmtConst->execute()) throw new Exception("Error al ejecutar CONSTANCIA_DE_TRABAJO: " . $stmtConst->error);

            return true;

        } catch (\Throwable $e) {
            error_log("Error actualizar constancia: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function listarConstancias($buscar = '') {
        $sql = "SELECT 
                    ct.id_constancia,
                    ct.tipo_personal,
                    ct.fecha AS fecha_constancia,
                    ct.codigo_constancia,
                    ct.motivo,
                    ct.tipo_constancia,
                    ct.fecha AS fecha_inicio,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    c.nombre_cargo
                FROM CONSTANCIA_DE_TRABAJO ct
                INNER JOIN TRABAJADOR t ON ct.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo";

        if ($buscar !== '') {
            $param = "%{$buscar}%";
            $sql .= " WHERE t.cedula LIKE ? OR t.nombres LIKE ? OR t.apellidos LIKE ? OR ct.codigo_constancia LIKE ?";
            $sql .= " ORDER BY ct.id_constancia DESC";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param("ssss", $param, $param, $param, $param);
        } else {
            $sql .= " ORDER BY ct.id_constancia DESC";
            $stmt = $this->db->prepare($sql);
        }

        if (!$stmt) return [];
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function eliminar($id_constancia) {
        $sql = "DELETE FROM CONSTANCIA_DE_TRABAJO WHERE id_constancia = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id_constancia);

        try {
            $stmt->execute();
            return true;
        } catch (mysqli_sql_exception $e) {
            error_log("Eliminar constancia id={$id_constancia}: " . $e->getMessage());
            return false;
        }
    }
}
