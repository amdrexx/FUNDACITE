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
     * Calcula la remuneración según el tipo de personal:
     * - Obrero: usa únicamente salario base individual o salario mínimo general.
     * - Cualquier otro tipo: usa únicamente el salario vigente del cargo.
     * Las primas activas se suman por separado y no sustituyen el salario.
     */
    private function agregarRemuneracion(array $trabajador): array {
        $idTrabajador = (int) ($trabajador['id_trabajador'] ?? 0);
        $idCargo = (int) ($trabajador['id_cargo'] ?? 0);
        $tipoPersonal = mb_strtolower(trim((string) ($trabajador['tipo_personal'] ?? '')), 'UTF-8');
        $esObrero = ($tipoPersonal === 'obrero');

        if ($idTrabajador <= 0) {
            $trabajador['salario_monto'] = 0.0;
            $trabajador['total_primas'] = 0.0;
            $trabajador['total_devengado'] = 0.0;
            $trabajador['origen_salario'] = 'Sin salario registrado';
            $trabajador['regla_salario'] = 'No determinada';
            return $trabajador;
        }

        if ($esObrero) {
            // El personal obrero no debe tomar el salario configurado por cargo.
            $sql = "SELECT
                        COALESCE(
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
                            WHEN EXISTS (SELECT 1 FROM SALARIO s WHERE s.tipo_salario = 'base'
                                         AND s.id_trabajador = ? AND s.estado = 'Vigente' AND s.monto > 0)
                                THEN 'Salario base del trabajador'
                            WHEN EXISTS (SELECT 1 FROM SALARIO s WHERE s.tipo_salario = 'base'
                                         AND s.id_trabajador IS NULL AND s.estado = 'Vigente' AND s.monto > 0)
                                THEN 'Salario base'
                            ELSE 'Sin salario base registrado'
                        END AS origen_salario";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->ultimoError = $this->db->error;
                return $this->remuneracionSinRegistro($trabajador, 'Salario base');
            }
            $stmt->bind_param('iii', $idTrabajador, $idTrabajador, $idTrabajador);
            $reglaSalario = 'Salario base/mínimo para personal obrero';
        } else {
            // El personal no obrero debe tener salario vigente asociado a su cargo.
            $sql = "SELECT
                        COALESCE(
                            (SELECT s.monto FROM SALARIO s
                             WHERE s.tipo_salario = 'cargo' AND s.id_cargo = NULLIF(?, 0)
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
                            ELSE 'Sin salario por cargo registrado'
                        END AS origen_salario";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->ultimoError = $this->db->error;
                return $this->remuneracionSinRegistro($trabajador, 'Salario por cargo');
            }
            $stmt->bind_param('iii', $idCargo, $idTrabajador, $idCargo);
            $reglaSalario = 'Salario por cargo para este tipo de personal';
        }

        if (!$stmt->execute()) {
            $this->ultimoError = $stmt->error;
            $stmt->close();
            return $this->remuneracionSinRegistro($trabajador, $esObrero ? 'Salario base' : 'Salario por cargo');
        }

        $remuneracion = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $salario = (float) ($remuneracion['salario_monto'] ?? 0);
        $primas = (float) ($remuneracion['total_primas'] ?? 0);
        $trabajador['salario_monto'] = round($salario, 2);
        $trabajador['total_primas'] = round($primas, 2);
        $trabajador['total_devengado'] = round($salario + $primas, 2);
        $trabajador['origen_salario'] = $remuneracion['origen_salario'] ?? 'Sin salario registrado';
        $trabajador['regla_salario'] = $reglaSalario;
        return $trabajador;
    }

    /** Asigna valores seguros cuando no se pudo obtener el salario exigido. */
    private function remuneracionSinRegistro(array $trabajador, string $fuente): array {
        $trabajador['salario_monto'] = 0.0;
        $trabajador['total_primas'] = 0.0;
        $trabajador['total_devengado'] = 0.0;
        $trabajador['origen_salario'] = 'Sin ' . mb_strtolower($fuente, 'UTF-8') . ' registrado';
        $trabajador['regla_salario'] = $fuente;
        return $trabajador;
    }

    public function listarTrabajadoresActivos($idTrabajador = null) {
        $sql = "SELECT 
                    t.id_trabajador,
                    t.id_cargo,
                    t.cedula,
                    CONCAT(t.apellidos, ' ', t.nombres) AS nombre_completo,
                    c.nombre_cargo AS cargo
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                WHERE t.status = 'Activo'" . ($idTrabajador !== null ? ' AND t.id_trabajador = ' . (int) $idTrabajador : '') . "
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
                    t.tipo_personal,
                    ingreso.fecha_primer_contrato
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
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

    /** Devuelve la cantidad de primas activas que pueden incluirse en una constancia de egreso. */
    public function contarPrimasActivas(int $id_trabajador): int {
        if ($id_trabajador <= 0) return 0;
        $sql = "SELECT COUNT(*) AS cantidad
                FROM PRIMA
                WHERE id_trabajador = ? AND estatus = 'Activo'";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->ultimoError = $this->db->error;
            return 0;
        }
        $stmt->bind_param('i', $id_trabajador);
        if (!$stmt->execute()) {
            $this->ultimoError = $stmt->error;
            $stmt->close();
            return 0;
        }
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int) ($fila['cantidad'] ?? 0);
    }

    public function tienePrimasActivas(int $id_trabajador): bool {
        return $this->contarPrimasActivas($id_trabajador) > 0;
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
     * Verifica que exista el salario vigente que corresponde al tipo de personal:
     * Obrero = salario base/mínimo; los demás = salario por cargo.
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
            // La categoría laboral se toma de TRABAJADOR; no se confía en POST.
            $datosTrabajador = $this->obtenerTrabajadorCompleto((int) $id_trabajador);
            if ($datosTrabajador) {
                $tipo_personal = (string) ($datosTrabajador['tipo_personal'] ?? '');
            }

            if (!$this->tieneSalarioRegistrado((int) $id_trabajador)) {
                $tipo = mb_strtolower(trim((string) ($datosTrabajador['tipo_personal'] ?? '')), 'UTF-8');
                $fuente = ($tipo === 'obrero') ? 'salario base/mínimo' : 'salario vigente por cargo';
                $this->ultimoError = 'No se puede registrar la constancia: para este tipo de personal se requiere un ' . $fuente . ' mayor que cero.';
                return false;
            }

            if ($tipo_constancia === 'primas_detalladas') {
                $tipo_constancia = 'egreso';
            }
            if (!in_array($tipo_constancia, ['normal', 'egreso'], true)) {
                $tipo_constancia = 'normal';
            }
            if ($tipo_constancia === 'egreso' && !$this->tienePrimasActivas((int) $id_trabajador)) {
                $nombre = trim((string) ($datosTrabajador['nombre_completo'] ?? 'El trabajador seleccionado'));
                $this->ultimoError = 'No se puede emitir la Constancia de egreso para ' . $nombre . ' porque no tiene primas activas registradas. Registre al menos una prima activa o seleccione Constancia normal.';
                return false;
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
            // La categoría laboral se toma de TRABAJADOR; no se confía en POST.
            $datosTrabajador = $this->obtenerTrabajadorCompleto((int) $id_trabajador);
            if ($datosTrabajador) {
                $tipo_personal = (string) ($datosTrabajador['tipo_personal'] ?? '');
            }

            if (!$this->tieneSalarioRegistrado((int) $id_trabajador)) {
                $tipo = mb_strtolower(trim((string) ($datosTrabajador['tipo_personal'] ?? '')), 'UTF-8');
                $fuente = ($tipo === 'obrero') ? 'salario base/mínimo' : 'salario vigente por cargo';
                $this->ultimoError = 'No se puede actualizar la constancia: para este tipo de personal se requiere un ' . $fuente . ' mayor que cero.';
                return false;
            }

            if ($tipo_constancia === 'primas_detalladas') {
                $tipo_constancia = 'egreso';
            }
            if (!in_array($tipo_constancia, ['normal', 'egreso'], true)) {
                $tipo_constancia = 'normal';
            }
            if ($tipo_constancia === 'egreso' && !$this->tienePrimasActivas((int) $id_trabajador)) {
                $nombre = trim((string) ($datosTrabajador['nombre_completo'] ?? 'El trabajador seleccionado'));
                $this->ultimoError = 'No se puede cambiar a Constancia de egreso para ' . $nombre . ' porque no tiene primas activas registradas. Registre al menos una prima activa o seleccione Constancia normal.';
                return false;
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

    public function listarConstancias($buscar = '', $idTrabajador = null) {
        $sql = "SELECT 
                    ct.id_constancia,
                    ct.id_trabajador,
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

        $condiciones = [];
        $tipos = '';
        $parametros = [];

        if ($buscar !== '') {
            $param = "%{$buscar}%";
            $condiciones[] = "(t.cedula LIKE ? OR t.nombres LIKE ? OR t.apellidos LIKE ? OR ct.codigo_constancia LIKE ?)";
            $tipos .= 'ssss';
            array_push($parametros, $param, $param, $param, $param);
        }

        if ($idTrabajador !== null) {
            $condiciones[] = "ct.id_trabajador = ?";
            $tipos .= 'i';
            $parametros[] = (int) $idTrabajador;
        }

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }
        $sql .= " ORDER BY ct.id_constancia DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        if ($tipos !== '') {
            $stmt->bind_param($tipos, ...$parametros);
        }

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
