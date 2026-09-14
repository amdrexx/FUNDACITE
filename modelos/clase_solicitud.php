<?php
// ARCHIVO: /FUNDACITE/modelos/clase_solicitud.php
// Clase única para todos los subtipos de SOLICITUD (Constancia de trabajo, Vacaciones/Días de disfrute, etc.)

class Solicitud {
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
                LEFT JOIN SALARIO sa ON sa.id_cargo = t.id_cargo AND sa.estado = 'Vigente'
                WHERE t.id_trabajador = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_trabajador);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // OBTENER CONTRATO ACTUAL DE UN TRABAJADOR (cédula + tipo_contrato)
    // Usado por días de disfrute para validar si tiene derecho a vacaciones.
    // =========================================================================
    public function obtenerContratoTrabajador($id_trabajador) {
        $sql = "SELECT t.cedula, co.tipo_contrato
                FROM TRABAJADOR t
                LEFT JOIN CONTRATO co ON co.id_trabajador = t.id_trabajador
                WHERE t.id_trabajador = ?
                ORDER BY co.id_contrato DESC
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
            if (!$stmtSol) throw new Exception("Error al preparar SOLICITUD: " . $this->db->error);
            $stmtSol->bind_param("isss", $id_trabajador, $codigo_solicitud, $motivo, $fecha_inicio);
            if (!$stmtSol->execute()) throw new Exception("Error al ejecutar SOLICITUD: " . $stmtSol->error);
            $id_solicitud = $this->db->insert_id;

            $sqlConst = "INSERT INTO CONSTANCIA_DE_TRABAJO
                         (id_solicitud, nombre_director_departamento, tipo_personal, fecha)
                         VALUES (?, ?, ?, ?)";
            $stmtConst = $this->db->prepare($sqlConst);
            if (!$stmtConst) throw new Exception("Error al preparar CONSTANCIA_DE_TRABAJO: " . $this->db->error);
            $stmtConst->bind_param("isss", $id_solicitud, $nombre_director, $tipo_personal, $fecha);
            if (!$stmtConst->execute()) throw new Exception("Error al ejecutar CONSTANCIA_DE_TRABAJO: " . $stmtConst->error);

            $id_constancia = $this->db->insert_id;

            $this->db->commit();
            return $id_constancia;

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Error registrar constancia: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
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
                LEFT JOIN SALARIO sa ON sa.id_cargo = t.id_cargo AND sa.estado = 'Vigente'
                WHERE ct.id_constancia = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_constancia);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // BUSCAR CONSTANCIA POR ID (alias de obtenerPorId con nombres de columna
    // compatibles con vistas.ver_constancia.php)
    // =========================================================================
    public function buscarPorId($id_constancia) {
        $fila = $this->obtenerPorId($id_constancia);
        if (!$fila) return null;

        $fila['fecha']           = $fila['fecha_constancia'] ?? null;
        $fila['nombre_director'] = $fila['nombre_director_departamento'] ?? null;
        $fila['motivo']          = $fila['motivo_solicitud'] ?? null;

        return $fila;
    }

    // =========================================================================
    // ACTUALIZAR CONSTANCIA (SOLICITUD + CONSTANCIA_DE_TRABAJO)
    // =========================================================================
    public function actualizar($id_constancia, $id_trabajador, $motivo, $fecha_inicio,
                                $nombre_director, $tipo_personal, $fecha) {

        // Ubicar la SOLICITUD asociada a esta constancia
        $sqlBuscar = "SELECT id_solicitud FROM CONSTANCIA_DE_TRABAJO WHERE id_constancia = ?";
        $stmtBuscar = $this->db->prepare($sqlBuscar);
        if (!$stmtBuscar) {
            $this->ultimoError = $this->db->error;
            return false;
        }
        $stmtBuscar->bind_param("i", $id_constancia);
        $stmtBuscar->execute();
        $fila = $stmtBuscar->get_result()->fetch_assoc();

        if (!$fila) {
            $this->ultimoError = 'La constancia no existe.';
            return false;
        }
        $id_solicitud = $fila['id_solicitud'];

        $this->db->begin_transaction();

        try {
            $sqlSol = "UPDATE SOLICITUD
                       SET id_trabajador = ?, motivo_solicitud = ?, fecha_inicio = ?
                       WHERE id_solicitud = ?";
            $stmtSol = $this->db->prepare($sqlSol);
            if (!$stmtSol) throw new Exception("Error al preparar SOLICITUD: " . $this->db->error);
            $stmtSol->bind_param("issi", $id_trabajador, $motivo, $fecha_inicio, $id_solicitud);
            if (!$stmtSol->execute()) throw new Exception("Error al ejecutar SOLICITUD: " . $stmtSol->error);

            $sqlConst = "UPDATE CONSTANCIA_DE_TRABAJO
                         SET nombre_director_departamento = ?, tipo_personal = ?, fecha = ?
                         WHERE id_constancia = ?";
            $stmtConst = $this->db->prepare($sqlConst);
            if (!$stmtConst) throw new Exception("Error al preparar CONSTANCIA_DE_TRABAJO: " . $this->db->error);
            $stmtConst->bind_param("sssi", $nombre_director, $tipo_personal, $fecha, $id_constancia);
            if (!$stmtConst->execute()) throw new Exception("Error al ejecutar CONSTANCIA_DE_TRABAJO: " . $stmtConst->error);

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Error actualizar constancia: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
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
                    s.id_solicitud,
                    s.codigo_solicitud,
                    s.motivo_solicitud,
                    s.fecha_inicio,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    c.nombre_cargo
                FROM CONSTANCIA_DE_TRABAJO ct
                INNER JOIN SOLICITUD s ON ct.id_solicitud = s.id_solicitud
                INNER JOIN TRABAJADOR t ON s.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo";

        if ($buscar !== '') {
            $param = "%{$buscar}%";
            $sql .= " WHERE t.cedula LIKE ? OR t.nombres LIKE ? OR t.apellidos LIKE ? OR s.codigo_solicitud LIKE ?";
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

    // =========================================================================
    // REGISTRAR DÍAS DE DISFRUTE (SOLICITUD + DISFRUTE_DE_VACACIONES)
    // =========================================================================
    public function registrarDiasDisfrute($id_trabajador, $codigo_solicitud, $descripcion,
                                           $fecha_inicio, $fecha_finalizacion,
                                           $nombre_cargo, $desde, $hasta) {

        $this->db->begin_transaction();

        try {
            $sqlSol = "INSERT INTO SOLICITUD
                       (id_trabajador, codigo_solicitud, tipo_solicitud, motivo_solicitud, fecha_inicio, fecha_finalizacion)
                       VALUES (?, ?, 'Vacaciones', ?, ?, ?)";
            $stmtSol = $this->db->prepare($sqlSol);
            if (!$stmtSol) throw new Exception("Error al preparar SOLICITUD: " . $this->db->error);
            $fecha_final_val = $fecha_finalizacion !== '' ? $fecha_finalizacion : null;
            $stmtSol->bind_param("issss", $id_trabajador, $codigo_solicitud, $descripcion, $fecha_inicio, $fecha_final_val);
            if (!$stmtSol->execute()) throw new Exception("Error al ejecutar SOLICITUD: " . $stmtSol->error);
            $id_solicitud = $this->db->insert_id;

            $sqlDis = "INSERT INTO DISFRUTE_DE_VACACIONES
                       (id_solicitud, nombre_cargo, descripcion, desde, hasta)
                       VALUES (?, ?, ?, ?, ?)";
            $stmtDis = $this->db->prepare($sqlDis);
            if (!$stmtDis) throw new Exception("Error al preparar DISFRUTE_DE_VACACIONES: " . $this->db->error);
            $stmtDis->bind_param("issss", $id_solicitud, $nombre_cargo, $descripcion, $desde, $hasta);
            if (!$stmtDis->execute()) throw new Exception("Error al ejecutar DISFRUTE_DE_VACACIONES: " . $stmtDis->error);

            $this->db->commit();
            return $id_solicitud;

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Error registrar dias de disfrute: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    // =========================================================================
    // LISTAR DÍAS DE DISFRUTE (para lista_dias_disfrute.php)
    // =========================================================================
    public function mostrarDiasDisfrute($buscar = '') {
        $sql = "SELECT
                    s.id_solicitud,
                    s.codigo_solicitud,
                    s.tipo_solicitud,
                    s.motivo_solicitud,
                    s.fecha_inicio,
                    s.fecha_finalizacion,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    d.nombre_cargo,
                    d.descripcion,
                    d.desde,
                    d.hasta
                FROM DISFRUTE_DE_VACACIONES d
                INNER JOIN SOLICITUD s ON d.id_solicitud = s.id_solicitud
                INNER JOIN TRABAJADOR t ON s.id_trabajador = t.id_trabajador";

        if ($buscar !== '') {
            $param = "%{$buscar}%";
            $sql .= " WHERE t.nombres LIKE ? OR t.apellidos LIKE ? OR s.codigo_solicitud LIKE ?";
            $sql .= " ORDER BY s.id_solicitud DESC";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param("sss", $param, $param, $param);
        } else {
            $sql .= " ORDER BY s.id_solicitud DESC";
            $stmt = $this->db->prepare($sql);
        }

        if (!$stmt) return [];
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // ELIMINAR DÍAS DE DISFRUTE
    // =========================================================================
    public function eliminarDiasDisfrute($id_solicitud) {
        $sql = "DELETE FROM DISFRUTE_DE_VACACIONES WHERE id_solicitud = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id_solicitud);

        try {
            $stmt->execute();
            return true;
        } catch (mysqli_sql_exception $e) {
            error_log("Eliminar dias de disfrute id_solicitud={$id_solicitud}: " . $e->getMessage());
            return false;
        }
    }
}
?>