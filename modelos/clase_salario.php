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

    public function listarSalarios($buscar = '') {
        return $this->mostrarSalarios($buscar);
    }

    public function mostrarSalarios($buscar = '') {
        $tieneTipo  = $this->columnaExiste('SALARIO', 'tipo_salario');
        $tieneCargo = $this->columnaExiste('SALARIO', 'id_cargo');

        if ($tieneTipo && $tieneCargo) {
            $sql = "SELECT 
                        s.id_salario,
                        s.fecha,
                        s.monto,
                        s.estado,
                        s.tipo_salario,
                        s.id_cargo,
                        c.nombre_cargo
                    FROM SALARIO s
                    LEFT JOIN CARGO c ON s.id_cargo = c.id_cargo";

            if ($buscar !== '') {
                $sql .= " WHERE c.nombre_cargo LIKE ? OR s.estado LIKE ? OR s.tipo_salario LIKE ?
                          ORDER BY s.fecha DESC, s.id_salario DESC";
                $param = "%{$buscar}%";
                $stmt = $this->conexion->prepare($sql);
                if ($stmt) $stmt->bind_param("sss", $param, $param, $param);
            } else {
                $sql .= " ORDER BY s.fecha DESC, s.id_salario DESC";
                $stmt = $this->conexion->prepare($sql);
            }
        } else {
            // Compatibilidad si aún no se ha ejecutado el ALTER TABLE
            $sql = "SELECT id_salario, fecha, monto, estado,
                           'base' AS tipo_salario, NULL AS id_cargo, NULL AS nombre_cargo
                    FROM SALARIO
                    ORDER BY fecha DESC, id_salario DESC";
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

    // =========================================================
    // REGISTRAR — deja "Vigente" solo al último registrado,
    // dentro de su propio grupo:
    //   - tipo_salario = 'base'  -> un único vigente global
    //   - tipo_salario = 'cargo' -> un único vigente por cada id_cargo
    // =========================================================
    public function registrarSalario($fecha, $monto, $tipo_salario = 'base', $id_cargo = null) {

        $tipo_salario = ($tipo_salario === 'cargo') ? 'cargo' : 'base';
        if ($tipo_salario === 'base') {
            $id_cargo = null;
        }

        $tieneTipo  = $this->columnaExiste('SALARIO', 'tipo_salario');
        $tieneCargo = $this->columnaExiste('SALARIO', 'id_cargo');

        if ($tipo_salario === 'cargo' && $id_cargo === null) {
            error_log("registrarSalario: se requiere id_cargo cuando tipo_salario = 'cargo'");
            return false;
        }

        $this->conexion->begin_transaction();

        try {

            // 1) Deshabilitar el/los salario(s) Vigente(s) del mismo grupo
            if ($tieneTipo && $tieneCargo) {
                if ($tipo_salario === 'cargo') {
                    $sqlUpd = "UPDATE SALARIO 
                               SET estado = 'Deshabilitado' 
                               WHERE estado = 'Vigente' AND tipo_salario = 'cargo' AND id_cargo = ?";
                    $stmtUpd = $this->conexion->prepare($sqlUpd);
                    if (!$stmtUpd) throw new Exception("Error al preparar el UPDATE de deshabilitación");
                    $stmtUpd->bind_param("i", $id_cargo);
                } else {
                    $sqlUpd = "UPDATE SALARIO 
                               SET estado = 'Deshabilitado' 
                               WHERE estado = 'Vigente' AND tipo_salario = 'base'";
                    $stmtUpd = $this->conexion->prepare($sqlUpd);
                    if (!$stmtUpd) throw new Exception("Error al preparar el UPDATE de deshabilitación");
                }
                $stmtUpd->execute();
            } else {
                $this->conexion->query(
                    "UPDATE SALARIO SET estado = 'Deshabilitado' WHERE estado = 'Vigente'"
                );
            }

            // 2) Insertar el nuevo como Vigente
            if ($tieneTipo && $tieneCargo) {
                $sql = "INSERT INTO SALARIO (fecha, monto, estado, tipo_salario, id_cargo) 
                        VALUES (?, ?, 'Vigente', ?, ?)";
                $stmt = $this->conexion->prepare($sql);
                if (!$stmt) throw new Exception("Error al preparar el INSERT");
                $stmt->bind_param("sdsi", $fecha, $monto, $tipo_salario, $id_cargo);
            } else {
                $sql = "INSERT INTO SALARIO (fecha, monto, estado) VALUES (?, ?, 'Vigente')";
                $stmt = $this->conexion->prepare($sql);
                if (!$stmt) throw new Exception("Error al preparar el INSERT");
                $stmt->bind_param("sd", $fecha, $monto);
            }

            $stmt->execute();

            $this->conexion->commit();
            return true;

        } catch (\Throwable $e) {
            $this->conexion->rollback();
            error_log("Error registrarSalario: " . $e->getMessage());
            return false;
        }
    }

    // Al editar, se respeta el estado actual del registro (no se toca Vigente/Deshabilitado)
    public function actualizarSalario($id, $fecha, $monto, $estado, $tipo_salario = 'base', $id_cargo = null) {
        $tipo_salario = ($tipo_salario === 'cargo') ? 'cargo' : 'base';
        if ($tipo_salario === 'base') {
            $id_cargo = null;
        }

        if ($this->columnaExiste('SALARIO', 'tipo_salario') && $this->columnaExiste('SALARIO', 'id_cargo')) {
            $sql = "UPDATE SALARIO 
                    SET fecha = ?, monto = ?, estado = ?, tipo_salario = ?, id_cargo = ? 
                    WHERE id_salario = ?";
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("sdssii", $fecha, $monto, $estado, $tipo_salario, $id_cargo, $id);
        } else {
            $sql = "UPDATE SALARIO SET fecha = ?, monto = ?, estado = ? WHERE id_salario = ?";
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("sdsi", $fecha, $monto, $estado, $id);
        }
        return $stmt->execute();
    }

    public function eliminarSalario($id) {
        $sql = "DELETE FROM SALARIO WHERE id_salario = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);

        try {
            $stmt->execute();
            return true;
        } catch (mysqli_sql_exception $e) {
            error_log("Eliminar SALARIO id={$id} excepcion: " . $e->getMessage());
            if ($e->getCode() == 1451) {
                return "RESTRICT";
            }
            return false;
        }
    }
}
