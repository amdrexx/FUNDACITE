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

    public function listarTrabajadoresActivos() {
        $sql = "SELECT 
                    t.id_trabajador,
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
                    t.cedula,
                    CONCAT(t.apellidos, ' ', t.nombres) AS nombre_completo,
                    c.nombre_cargo AS cargo,
                    contrato_actual.tipo_personal,
                    ingreso.fecha_primer_contrato,
                    sa.monto AS salario_monto
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
                LEFT JOIN SALARIO sa ON sa.id_cargo = t.id_cargo AND sa.estado = 'Vigente'
                WHERE t.id_trabajador = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_trabajador);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
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

    public function registrarConstancia($id_trabajador, $codigo_constancia, $motivo,
                                        $tipo_personal, $fecha) {

        try {
            $sqlConst = "INSERT INTO CONSTANCIA_DE_TRABAJO
                         (id_trabajador, codigo_constancia, motivo, tipo_personal, fecha)
                         VALUES (?, ?, ?, ?, ?)";
            $stmtConst = $this->db->prepare($sqlConst);
            if (!$stmtConst) throw new Exception("Error al preparar CONSTANCIA_DE_TRABAJO: " . $this->db->error);
            $stmtConst->bind_param("issss", $id_trabajador, $codigo_constancia, $motivo, $tipo_personal, $fecha);
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
                    ct.fecha AS fecha_inicio,
                    t.id_trabajador,
                    t.nombres,
                    t.apellidos,
                    t.cedula,
                    t.genero,
                    ingreso.fecha_primer_contrato,
                    c.nombre_cargo,
                    sa.monto AS salario_monto
                FROM CONSTANCIA_DE_TRABAJO ct
                INNER JOIN TRABAJADOR t ON ct.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN (
                    SELECT id_trabajador, MIN(fecha_contrato) AS fecha_primer_contrato
                    FROM CONTRATO
                    GROUP BY id_trabajador
                ) ingreso ON ingreso.id_trabajador = t.id_trabajador
                LEFT JOIN SALARIO sa ON sa.id_cargo = t.id_cargo AND sa.estado = 'Vigente'
                WHERE ct.id_constancia = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_constancia);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function buscarPorId($id_constancia) {
        $fila = $this->obtenerPorId($id_constancia);
        if (!$fila) return null;

        $fila['fecha']           = $fila['fecha_constancia'] ?? null;
        return $fila;
    }

    public function actualizar($id_constancia, $id_trabajador, $motivo,
                                $tipo_personal, $fecha) {

        try {
            $sqlConst = "UPDATE CONSTANCIA_DE_TRABAJO
                         SET id_trabajador = ?, motivo = ?, tipo_personal = ?, fecha = ?
                         WHERE id_constancia = ?";
            $stmtConst = $this->db->prepare($sqlConst);
            if (!$stmtConst) throw new Exception("Error al preparar CONSTANCIA_DE_TRABAJO: " . $this->db->error);
            $stmtConst->bind_param("isssi", $id_trabajador, $motivo, $tipo_personal, $fecha, $id_constancia);
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
