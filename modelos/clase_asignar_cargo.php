<?php
class clase_asignar_cargo {
    private $conexion;
    public $ultimoError = '';

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerCargos() {
        $sql = "SELECT id_cargo, nombre_cargo FROM CARGO ORDER BY nombre_cargo ASC";
        $result = $this->conexion->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Trabajadores activos que actualmente no tienen cargo.
    public function obtenerTodosLosTrabajadores() {
        $sql = "SELECT t.id_trabajador, t.cedula, t.nombres, t.apellidos,
                       CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo, t.id_cargo
                FROM TRABAJADOR t
                WHERE t.status = 'Activo' AND t.id_cargo IS NULL
                ORDER BY t.nombres ASC, t.apellidos ASC";
        $result = $this->conexion->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Listado actual, separado del historial histórico.
    public function listarTrabajadoresConCargo() {
        $sql = "SELECT t.id_trabajador, t.cedula, t.nombres, t.apellidos,
                       CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo,
                       c.nombre_cargo, t.id_cargo, t.status,
                       COALESCE(a.gaceta, '') AS gaceta
                FROM TRABAJADOR t
                INNER JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN ASIGNACION_CARGO a
                    ON a.id_asignacion = (
                        SELECT MAX(a2.id_asignacion)
                        FROM ASIGNACION_CARGO a2
                        WHERE a2.id_trabajador = t.id_trabajador
                          AND a2.id_cargo = t.id_cargo
                          AND a2.estado = 'ACTIVA'
                    )
                ORDER BY c.nombre_cargo ASC, t.nombres ASC";
        $result = $this->conexion->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function listarHistorialAsignaciones() {
        $sql = "SELECT a.id_asignacion, a.id_trabajador, a.id_cargo,
                       a.fecha_asignacion, a.fecha_fin, a.estado, a.gaceta,
                       t.cedula,
                       CONCAT(t.nombres, ' ', t.apellidos) AS funcionario,
                       c.nombre_cargo,
                       COALESCE(u.nombre, 'No disponible / Registro inicial') AS asignado_por,
                       COALESCE(ud.nombre, '—') AS desvinculado_por
                FROM ASIGNACION_CARGO a
                INNER JOIN TRABAJADOR t ON t.id_trabajador = a.id_trabajador
                INNER JOIN CARGO c ON c.id_cargo = a.id_cargo
                LEFT JOIN USUARIO u ON u.id_usuario = a.id_usuario_asignador
                LEFT JOIN USUARIO ud ON ud.id_usuario = a.id_usuario_desvinculador
                ORDER BY a.fecha_asignacion DESC, a.id_asignacion DESC";
        $result = $this->conexion->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function buscarTrabajadorPorCedula($cedula) {
        $stmt = $this->conexion->prepare(
            "SELECT t.id_trabajador, t.cedula, t.nombres, t.apellidos, t.id_cargo, c.nombre_cargo
             FROM TRABAJADOR t
             LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
             WHERE t.cedula = ? AND t.status = 'Activo'
             LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('s', $cedula);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    /**
     * Cambia el cargo de un trabajador desde el módulo de asignación y conserva
     * el historial. Presidente y Director no se sustituyen desde este módulo:
     * si ya tienen titular, la operación se rechaza. El reemplazo automático
     * está reservado al registro de un nuevo trabajador.
     */
    public function asignarCargo($id_trabajador, $id_cargo, $gaceta, $id_usuario_asignador = null) {
        $id_trabajador = (int) $id_trabajador;
        $id_cargo = (int) $id_cargo;
        $gaceta = trim((string) $gaceta);
        $id_usuario_asignador = ($id_usuario_asignador === null || (int) $id_usuario_asignador <= 0)
            ? null : (int) $id_usuario_asignador;

        if ($id_trabajador <= 0 || $id_cargo <= 0) {
            $this->ultimoError = 'Debe seleccionar un trabajador y un cargo válidos.';
            return false;
        }
        if ($gaceta === '' || (preg_match_all('/./us', $gaceta, $coincidenciasGaceta) ?: 0) > 150) {
            $this->ultimoError = 'Debe indicar la Gaceta utilizada para asignar el cargo (máximo 150 caracteres).';
            return false;
        }

        try {
            if (!$this->conexion->begin_transaction()) {
                throw new RuntimeException('No se pudo iniciar la transacción de asignación.');
            }

            // Bloquear al trabajador para impedir asignaciones simultáneas.
            $stmtTrabajador = $this->conexion->prepare(
                "SELECT id_trabajador, id_cargo, status
                 FROM TRABAJADOR
                 WHERE id_trabajador = ?
                 LIMIT 1 FOR UPDATE"
            );
            if (!$stmtTrabajador) throw new RuntimeException($this->conexion->error);
            $stmtTrabajador->bind_param('i', $id_trabajador);
            if (!$stmtTrabajador->execute()) throw new RuntimeException($stmtTrabajador->error);
            $trabajador = $stmtTrabajador->get_result()->fetch_assoc();
            $stmtTrabajador->close();

            if (!$trabajador || strcasecmp(trim((string) $trabajador['status']), 'Activo') !== 0) {
                throw new RuntimeException('Solo se puede asignar un cargo a un trabajador activo.');
            }
            if (!empty($trabajador['id_cargo'])) {
                throw new RuntimeException('El trabajador ya tiene un cargo asignado. Primero debe realizar la desvinculación desde este módulo, cuando corresponda.');
            }

            // Evitar que exista una asignación histórica todavía abierta para el trabajador.
            $stmtActiva = $this->conexion->prepare(
                "SELECT id_asignacion
                 FROM ASIGNACION_CARGO
                 WHERE id_trabajador = ? AND estado = 'ACTIVA'
                 LIMIT 1 FOR UPDATE"
            );
            if (!$stmtActiva) throw new RuntimeException($this->conexion->error);
            $stmtActiva->bind_param('i', $id_trabajador);
            if (!$stmtActiva->execute()) throw new RuntimeException($stmtActiva->error);
            $asignacionActiva = $stmtActiva->get_result()->fetch_assoc();
            $stmtActiva->close();
            if ($asignacionActiva) {
                throw new RuntimeException('El trabajador posee una asignación activa en el historial. Revise el historial antes de realizar otra asignación.');
            }

            // Verificar que el cargo exista.
            $stmtCargo = $this->conexion->prepare(
                "SELECT nombre_cargo FROM CARGO WHERE id_cargo = ? LIMIT 1 FOR UPDATE"
            );
            if (!$stmtCargo) throw new RuntimeException($this->conexion->error);
            $stmtCargo->bind_param('i', $id_cargo);
            if (!$stmtCargo->execute()) throw new RuntimeException($stmtCargo->error);
            $cargo = $stmtCargo->get_result()->fetch_assoc();
            $stmtCargo->close();
            if (!$cargo) throw new RuntimeException('El cargo seleccionado no existe.');

            // Presidente y Director son cargos únicos. Desde este módulo no se
            // desplaza al titular actual; la sustitución ocurre al registrar
            // un trabajador nuevo con uno de esos cargos.
            $nombreCargo = strtolower(trim($cargo['nombre_cargo']));
            if (in_array($nombreCargo, ['presidente', 'director'], true)) {
                $stmtOcupante = $this->conexion->prepare(
                    "SELECT id_trabajador
                     FROM TRABAJADOR
                     WHERE id_cargo = ?
                     LIMIT 1 FOR UPDATE"
                );
                if (!$stmtOcupante) throw new RuntimeException($this->conexion->error);
                $stmtOcupante->bind_param('i', $id_cargo);
                if (!$stmtOcupante->execute()) throw new RuntimeException($stmtOcupante->error);
                $ocupante = $stmtOcupante->get_result()->fetch_assoc();
                $stmtOcupante->close();
                if ($ocupante) {
                    throw new RuntimeException(
                        'El cargo de ' . ucfirst($nombreCargo) . ' ya tiene un titular. '
                        . 'La sustitución se realiza al registrar un nuevo trabajador con ese cargo.'
                    );
                }
            }

            // Actualizar el cargo actual del trabajador.
            $stmtActualizar = $this->conexion->prepare(
                "UPDATE TRABAJADOR SET id_cargo = ? WHERE id_trabajador = ? AND id_cargo IS NULL"
            );
            if (!$stmtActualizar) throw new RuntimeException($this->conexion->error);
            $stmtActualizar->bind_param('ii', $id_cargo, $id_trabajador);
            if (!$stmtActualizar->execute()) {
                $err = $stmtActualizar->error;
                $stmtActualizar->close();
                throw new RuntimeException($err);
            }
            if ($stmtActualizar->affected_rows !== 1) {
                $stmtActualizar->close();
                throw new RuntimeException('No se pudo asignar el cargo. Actualice el listado e inténtelo nuevamente.');
            }
            $stmtActualizar->close();

            // Crear el movimiento con la Gaceta usada para esta asignación.
            $idUsuarioSql = $id_usuario_asignador ?? 0;
            $stmtInsertar = $this->conexion->prepare(
                "INSERT INTO ASIGNACION_CARGO
                    (id_trabajador, id_cargo, fecha_asignacion, gaceta, id_usuario_asignador, estado)
                 VALUES (?, ?, NOW(), ?, NULLIF(?, 0), 'ACTIVA')"
            );
            if (!$stmtInsertar) throw new RuntimeException($this->conexion->error);
            $stmtInsertar->bind_param('iisi', $id_trabajador, $id_cargo, $gaceta, $idUsuarioSql);
            if (!$stmtInsertar->execute()) {
                $err = $stmtInsertar->error;
                $stmtInsertar->close();
                throw new RuntimeException($err);
            }
            $stmtInsertar->close();

            $this->conexion->commit();
            return true;
        } catch (Throwable $e) {
            $this->conexion->rollback();
            $this->ultimoError = $e->getMessage();
            error_log('Error al asignar cargo y registrar historial: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Desvinculación manual de cargos ordinarios desde el módulo Asignación de cargo.
     * Cierra el registro histórico, guarda quién realizó la acción y deja
     * TRABAJADOR.id_cargo en NULL sin tocar el estatus laboral.
     */
    public function desvincularCargoManualmente($id_trabajador, $id_usuario_desvinculador = null) {
        $id_trabajador = (int) $id_trabajador;
        $id_usuario_desvinculador = ($id_usuario_desvinculador === null || (int) $id_usuario_desvinculador <= 0)
            ? null : (int) $id_usuario_desvinculador;

        if ($id_trabajador <= 0) {
            $this->ultimoError = 'El trabajador seleccionado no es válido.';
            return false;
        }

        try {
            if (!$this->conexion->begin_transaction()) {
                throw new RuntimeException('No se pudo iniciar la transacción de desvinculación.');
            }

            $stmtTrabajador = $this->conexion->prepare(
                "SELECT id_cargo FROM TRABAJADOR WHERE id_trabajador = ? LIMIT 1 FOR UPDATE"
            );
            if (!$stmtTrabajador) throw new RuntimeException($this->conexion->error);
            $stmtTrabajador->bind_param('i', $id_trabajador);
            $stmtTrabajador->execute();
            $trabajador = $stmtTrabajador->get_result()->fetch_assoc();
            $stmtTrabajador->close();

            if (!$trabajador) {
                throw new RuntimeException('No se encontró el trabajador seleccionado.');
            }
            $id_cargo = (int) ($trabajador['id_cargo'] ?? 0);
            if ($id_cargo <= 0) {
                throw new RuntimeException('El trabajador ya se encuentra sin cargo asignado.');
            }

            $stmtNombreCargo = $this->conexion->prepare(
                "SELECT nombre_cargo FROM CARGO WHERE id_cargo = ? LIMIT 1"
            );
            if (!$stmtNombreCargo) throw new RuntimeException($this->conexion->error);
            $stmtNombreCargo->bind_param('i', $id_cargo);
            if (!$stmtNombreCargo->execute()) throw new RuntimeException($stmtNombreCargo->error);
            $cargoActual = $stmtNombreCargo->get_result()->fetch_assoc();
            $stmtNombreCargo->close();
            $nombreCargoActual = strtolower(trim((string) ($cargoActual['nombre_cargo'] ?? '')));
            if (in_array($nombreCargoActual, ['presidente', 'director'], true)) {
                throw new RuntimeException(
                    'Presidente y Director no se desvinculan manualmente desde este módulo. '
                    . 'La sustitución se realiza al registrar un nuevo trabajador con ese cargo.'
                );
            }

            $stmtHistorial = $this->conexion->prepare(
                "UPDATE ASIGNACION_CARGO
                 SET fecha_fin = NOW(),
                     estado = 'FINALIZADA',
                     id_usuario_desvinculador = ?
                 WHERE id_trabajador = ? AND id_cargo = ? AND estado = 'ACTIVA'"
            );
            if (!$stmtHistorial) throw new RuntimeException($this->conexion->error);
            $stmtHistorial->bind_param('iii', $id_usuario_desvinculador, $id_trabajador, $id_cargo);
            if (!$stmtHistorial->execute()) {
                $error = $stmtHistorial->error;
                $stmtHistorial->close();
                throw new RuntimeException($error);
            }
            $cerradas = $stmtHistorial->affected_rows;
            $stmtHistorial->close();

            if ($cerradas < 1) {
                throw new RuntimeException(
                    'No existe una asignación ACTIVA en el historial para este cargo. Ejecute la migración del historial antes de desvincular.'
                );
            }

            $stmtLiberar = $this->conexion->prepare(
                "UPDATE TRABAJADOR SET id_cargo = NULL WHERE id_trabajador = ? AND id_cargo = ?"
            );
            if (!$stmtLiberar) throw new RuntimeException($this->conexion->error);
            $stmtLiberar->bind_param('ii', $id_trabajador, $id_cargo);
            if (!$stmtLiberar->execute()) {
                $error = $stmtLiberar->error;
                $stmtLiberar->close();
                throw new RuntimeException($error);
            }
            $stmtLiberar->close();

            $this->conexion->commit();
            return true;
        } catch (Throwable $e) {
            $this->conexion->rollback();
            $this->ultimoError = $e->getMessage();
            error_log('Error al desvincular manualmente el cargo: ' . $e->getMessage());
            return false;
        }
    }
}
?>
