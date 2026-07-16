<?php
class clase_salario {
    private $conexion;
    private $db;

    public function __construct($conexion) {
        $this->conexion = $conexion;
        $res = $this->conexion->query("SELECT DATABASE() AS db");
        $this->db = $res ? $res->fetch_assoc()['db'] : null;
    }

    private function columnaExiste($tabla, $columna) {
        $sql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("sss", $this->db, $tabla, $columna);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return ($r && $r['cnt'] > 0);
    }

    // Compatibilidad con nombres usados en otros archivos
    public function listarSalarios($buscar = '') {
        return $this->mostrarSalarios($buscar);
    }

    public function mostrarSalarios($buscar = '') {
        if ($this->columnaExiste('SALARIO', 'id_trabajador')) {
            $sql = "SELECT 
                        s.id_salario,
                        s.fecha,
                        s.monto,
                        s.estado,
                        t.cedula,
                        c.nombre_cargo,
                        ctr.tipo_contrato AS contrato
                    FROM SALARIO s
                    LEFT JOIN TRABAJADOR t ON s.id_trabajador = t.id_trabajador
                    LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                    LEFT JOIN CONTRATO ctr ON ctr.id_trabajador = t.id_trabajador";
            if ($buscar !== '') {
                $sql .= " WHERE t.cedula LIKE ? OR c.nombre_cargo LIKE ? OR ctr.tipo_contrato LIKE ?";
                $param = "%{$buscar}%";
                $stmt = $this->conexion->prepare($sql);
                $stmt->bind_param("sss", $param, $param, $param);
            } else {
                $stmt = $this->conexion->prepare($sql);
            }
        } else {
            $sql = "SELECT id_salario, fecha, monto, estado, NULL AS cedula, NULL AS nombre_cargo, NULL AS contrato
                    FROM SALARIO";
            $stmt = $this->conexion->prepare($sql);
        }

        if (!$stmt) return [];
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM SALARIO WHERE id_salario = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }

    // Nombre usado por el controlador: registrarSalario
    public function registrarSalario($fecha, $monto, $estado = 'Activo', $id_trabajador = null) {
        if ($this->columnaExiste('SALARIO', 'id_trabajador') && $id_trabajador !== null) {
            $sql = "INSERT INTO SALARIO (fecha, monto, estado, id_trabajador) VALUES (?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("sdsi", $fecha, $monto, $estado, $id_trabajador);
        } else {
            $sql = "INSERT INTO SALARIO (fecha, monto, estado) VALUES (?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("sds", $fecha, $monto, $estado);
        }
        return $stmt->execute();
    }

    // Nombre usado por el controlador: actualizarSalario
    public function actualizarSalario($id, $fecha, $monto, $estado = 'Activo') {
        $sql = "UPDATE SALARIO SET fecha = ?, monto = ?, estado = ? WHERE id_salario = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("sdsi", $fecha, $monto, $estado, $id);
        return $stmt->execute();
    }

    // Nombre usado por el controlador: eliminarSalario
    public function eliminarSalario($id) {
        $sql = "DELETE FROM SALARIO WHERE id_salario = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}