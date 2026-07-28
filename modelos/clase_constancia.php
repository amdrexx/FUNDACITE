<?php
// ARCHIVO: /FUNDACITE/modelos/clase_constancia.php

class clase_constancia {
    private $db;

    public function __construct($conexion = null) {
        if ($conexion !== null) {
            $this->db = $conexion;
        } else {
            global $conexion;
            $this->db = $conexion;
        }
    }

    // =========================================================================
    // LISTAR TRABAJADORES ACTIVOS (para el SELECT)
    // =========================================================================
    public function listarTrabajadoresActivos() {
        $sql = "SELECT 
                    t.id_trabajador,
                    t.cedula,
                    CONCAT(t.apellidos, ' ', t.nombres) AS nombre_completo,
                    c.nombre_cargo AS cargo,
                    t.fecha_ingreso
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                WHERE t.status = 'Activo'
                ORDER BY t.apellidos ASC";

        $resultado = $this->db->query($sql);
        return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // OBTENER TRABAJADOR COMPLETO POR ID (para auto-relleno AJAX)
    // =========================================================================
    public function obtenerTrabajadorCompleto($id_trabajador) {
        $sql = "SELECT 
                    t.id_trabajador,
                    t.cedula,
                    CONCAT(t.apellidos, ' ', t.nombres) AS nombre_completo,
                    c.nombre_cargo AS cargo,
                    t.fecha_ingreso,
                    sa.monto AS salario_monto
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN SALARIO sa ON sa.id_trabajador = t.id_trabajador AND sa.estado = 'Vigente'
                WHERE t.id_trabajador = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_trabajador);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // REGISTRAR CONSTANCIA (SOLICITUD + CONSTANCIA_DE_TRABAJO)
    // =========================================================================
    public function registrar($id_trabajador, $codigo_solicitud, $motivo, $fecha_inicio,
                              $nombre_director, $tipo_personal, $fecha) {

        $this->db->begin_transaction();

        try {
            $sqlSol = "INSERT INTO SOLICITUD
                       (id_trabajador, codigo_solicitud, tipo_solicitud, motivo_solicitud, fecha_inicio)
                       VALUES (?, ?, 'Constancia de trabajo', ?, ?)";
            $stmtSol = $this->db->prepare($sqlSol);
            if (!$stmtSol) throw new Exception("Error al preparar SOLICITUD");
            $stmtSol->bind_param("isss", $id_trabajador, $codigo_solicitud, $motivo, $fecha_inicio);
            $stmtSol->execute();
            $id_solicitud = $this->db->insert_id;

            $sqlConst = "INSERT INTO CONSTANCIA_DE_TRABAJO
                         (id_solicitud, nombre_director_departamento, tipo_personal, fecha)
                         VALUES (?, ?, ?, ?)";
            $stmtConst = $this->db->prepare($sqlConst);
            if (!$stmtConst) throw new Exception("Error al preparar CONSTANCIA_DE_TRABAJO");
            $stmtConst->bind_param("isss", $id_solicitud, $nombre_director, $tipo_personal, $fecha);
            $stmtConst->execute();

            $this->db->commit();
            return $this->db->insert_id;

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Error registrar constancia: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // OBTENER CONSTANCIA POR ID
    // =========================================================================
    public function obtenerPorId($id_constancia) {
        $sql = "SELECT 
                    ct.id_constancia,
                    ct.nombre_director_departamento,
                    ct.tipo_personal,
                    ct.fecha AS fecha_constancia,
                    s.codigo_solicitud,
                    s.motivo_solicitud,
                    s.fecha_inicio,
                    t.id_trabajador,
                    t.nombres,
                    t.apellidos,
                    t.cedula,
                    t.fecha_ingreso,
                    c.nombre_cargo,
                    sa.monto AS salario_monto
                FROM CONSTANCIA_DE_TRABAJO ct
                INNER JOIN SOLICITUD s ON ct.id_solicitud = s.id_solicitud
                INNER JOIN TRABAJADOR t ON s.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN SALARIO sa ON sa.id_trabajador = t.id_trabajador AND sa.estado = 'Vigente'
                WHERE ct.id_constancia = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_constancia);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // LISTAR CONSTANCIAS
    // =========================================================================
    public function listarConstancias($buscar = '') {
        $sql = "SELECT 
                    ct.id_constancia,
                    ct.tipo_personal,
                    ct.fecha AS fecha_constancia,
                    ct.nombre_director_departamento,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    c.nombre_cargo
                FROM CONSTANCIA_DE_TRABAJO ct
                INNER JOIN SOLICITUD s ON ct.id_solicitud = s.id_solicitud
                INNER JOIN TRABAJADOR t ON s.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo";

        if ($buscar !== '') {
            $param = "%{$buscar}%";
            $sql .= " WHERE t.cedula LIKE ? OR t.nombres LIKE ? OR t.apellidos LIKE ?";
            $sql .= " ORDER BY ct.id_constancia DESC";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param("sss", $param, $param, $param);
        } else {
            $sql .= " ORDER BY ct.id_constancia DESC";
            $stmt = $this->db->prepare($sql);
        }

        if (!$stmt) return [];
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // ELIMINAR CONSTANCIA
    // =========================================================================
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
?>
