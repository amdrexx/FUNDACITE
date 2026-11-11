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

    /**
     * Todos los trabajadores activos pueden registrar días de disfrute.
     * La antigüedad y el tipo de contrato no condicionan este módulo.
     */
    public function esElegibleDiasDisfrute($tipoContrato = '', $fechaIngreso = '') {
        return true;
    }

    /**
     * Días hábiles de disfrute definidos por el CONVENIO colectivo vigente
     * hoy (dias_disfrute del convenio cuyo periodo cubre la fecha actual).
     * Devuelve null si no hay ningún convenio vigente registrado.
     */
    public function calcularDiasDisfrute($fechaIngreso = null) {
        return $this->diasSegunConvenioVigente();
    }

    /** id y días del CONVENIO vigente hoy (o null si no hay). */
    private function convenioVigente(): ?array {
        $sql = "SELECT id_convenio, dias_disfrute FROM CONVENIO
                WHERE fecha_inicio <= CURDATE() AND fecha_vencimiento >= CURDATE()
                ORDER BY fecha_inicio DESC, id_convenio DESC
                LIMIT 1";
        $resultado = $this->db->query($sql);
        if (!$resultado) {
            $this->ultimoError = $this->db->error;
            return null;
        }
        $fila = $resultado->fetch_assoc();
        return $fila ?: null;
    }

    /** Consulta directa a CONVENIO para obtener los días del convenio vigente hoy. */
    private function diasSegunConvenioVigente(): ?int {
        $sql = "SELECT dias_disfrute FROM CONVENIO
                WHERE fecha_inicio <= CURDATE() AND fecha_vencimiento >= CURDATE()
                ORDER BY fecha_inicio DESC, id_convenio DESC
                LIMIT 1";
        $resultado = $this->db->query($sql);
        if (!$resultado) {
            $this->ultimoError = $this->db->error;
            return null;
        }

        $fila = $resultado->fetch_assoc();
        return $fila ? (int) $fila['dias_disfrute'] : null;
    }

    public function motivoNoElegibilidadDiasDisfrute($tipoContrato = '', $fechaIngreso = '') {
        return '';
    }

    /**
     * Determina si una fecha es feriado aplicable a Yaracuy, municipio
     * Independencia. Incluye feriados nacionales, estatales recurrentes y
     * el día no laborable municipal de San Rafael Arcángel (24 de octubre).
     */
    private function esFeriadoLocal(DateTimeImmutable $fecha): bool {
        $anio = (int) $fecha->format('Y');
        $fechaISO = $fecha->format('Y-m-d');

        // Feriados nacionales fijos y feriados recurrentes del estado Yaracuy
        // (02 de febrero y 28 de marzo), además del 24 de octubre municipal.
        $feriados = [
            sprintf('%04d-01-01', $anio), // Año Nuevo
            sprintf('%04d-02-02', $anio), // Nuestra Señora de la Presentación (Yaracuy)
            sprintf('%04d-03-28', $anio), // Día del estado Yaracuy
            sprintf('%04d-04-19', $anio), // Declaración de la Independencia
            sprintf('%04d-05-01', $anio), // Día del Trabajador
            sprintf('%04d-06-24', $anio), // Batalla de Carabobo
            sprintf('%04d-07-05', $anio), // Día de la Independencia
            sprintf('%04d-07-24', $anio), // Natalicio del Libertador
            sprintf('%04d-10-12', $anio), // Resistencia Indígena
            sprintf('%04d-10-24', $anio), // San Rafael Arcángel (Municipio Independencia)
            sprintf('%04d-12-24', $anio), // Nochebuena
            sprintf('%04d-12-25', $anio), // Navidad
            sprintf('%04d-12-31', $anio), // Fin de año
        ];

        // Carnaval y Semana Santa dependen de la fecha de Pascua.
        $pascua = new DateTimeImmutable($anio . '-03-21', new DateTimeZone('America/Caracas'));
        $pascua = $pascua->modify('+' . easter_days($anio) . ' days');
        $feriados[] = $pascua->modify('-48 days')->format('Y-m-d'); // Lunes de Carnaval
        $feriados[] = $pascua->modify('-47 days')->format('Y-m-d'); // Martes de Carnaval
        $feriados[] = $pascua->modify('-3 days')->format('Y-m-d');  // Jueves Santo
        $feriados[] = $pascua->modify('-2 days')->format('Y-m-d');  // Viernes Santo

        return in_array($fechaISO, $feriados, true);
    }

    /**
     * Calcula la fecha de finalización contando los días hábiles desde la
     * fecha de aprobación. El día de aprobación cuenta como día 1 cuando
     * es laborable. Se excluyen sábados, domingos y feriados aplicables.
     */
    public function calcularFechaFinalizacion($fechaAprobacion, $cantidadDias = 30): ?string {
        try {
            $zonaHoraria = new DateTimeZone('America/Caracas');
            if ($fechaAprobacion instanceof DateTimeInterface) {
                $fecha = DateTimeImmutable::createFromInterface($fechaAprobacion)
                    ->setTimezone($zonaHoraria)->setTime(0, 0, 0);
            } else {
                $fecha = new DateTimeImmutable((string) $fechaAprobacion, $zonaHoraria);
                $fecha = $fecha->setTime(0, 0, 0);
            }

            $cantidadDias = max(1, (int) $cantidadDias);
            $contados = 0;
            $limite = 0;

            while ($contados < $cantidadDias && $limite < 1000) {
                $diaSemana = (int) $fecha->format('N');
                if ($diaSemana <= 5 && !$this->esFeriadoLocal($fecha)) {
                    $contados++;
                }

                if ($contados < $cantidadDias) {
                    $fecha = $fecha->modify('+1 day');
                }
                $limite++;
            }

            if ($contados !== $cantidadDias) {
                throw new RuntimeException('No se pudo calcular la fecha de finalización.');
            }

            return $fecha->format('Y-m-d');
        } catch (Throwable $e) {
            $this->ultimoError = 'Error al calcular la fecha de finalización: ' . $e->getMessage();
            error_log($this->ultimoError);
            return null;
        }
    }

    public function listarTrabajadoresDiasDisfrute($idTrabajador = null) {
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
                WHERE t.status = 'Activo'" . ($idTrabajador !== null ? ' AND t.id_trabajador = ' . (int) $idTrabajador : '') . "
                ORDER BY t.apellidos ASC, t.nombres ASC";

        $resultado = $this->db->query($sql);
        if (!$resultado) {
            $this->ultimoError = $this->db->error;
            return [];
        }

        $trabajadores = $resultado->fetch_all(MYSQLI_ASSOC);
        $diasConvenioVigente = $this->diasSegunConvenioVigente();
        foreach ($trabajadores as &$trabajador) {
            $trabajador['elegible_disfrute'] = true;
            $trabajador['motivo_inelegibilidad'] = '';
            $trabajador['dias_correspondientes'] = $diasConvenioVigente;
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
                                           $observaciones, $tipo_remuneracion,
                                           $requiere_suplente, $dias_correspondientes = null) {

        try {
            if (!in_array($tipo_remuneracion, ['remunerado', 'no_remunerado'], true)) {
                throw new InvalidArgumentException('Tipo de remuneración no válido.');
            }
            if (!in_array($requiere_suplente, ['si', 'no'], true)) {
                throw new InvalidArgumentException('La indicación de suplente no es válida.');
            }

            // La cantidad de días siempre se toma del convenio colectivo vigente,
            // nunca de un valor fijo ni del que haya enviado el formulario.
            $convenioVigente = $this->convenioVigente();
            $dias_correspondientes = $convenioVigente ? (int) $convenioVigente['dias_disfrute'] : null;
            $id_convenio = $convenioVigente ? (int) $convenioVigente['id_convenio'] : null;
            if ($dias_correspondientes === null) {
                throw new RuntimeException('No hay un convenio vigente registrado que indique los días de disfrute correspondientes. Registre o actualice el convenio colectivo antes de continuar.');
            }

            $zonaHoraria = new DateTimeZone('America/Caracas');
            $fecha_inicio = (new DateTimeImmutable('today', $zonaHoraria))->format('Y-m-d');
            $fecha_final_val = null; // Se calcula al aprobar, no al solicitar.
            // La solicitud se crea pendiente. Los días hábiles del convenio vigente
            // comienzan a contarse desde la fecha/hora en que el Director o Administrador apruebe.
            $sqlDis = "INSERT INTO DISFRUTE_DE_VACACIONES
                       (id_trabajador, id_convenio, codigo_disfrute, motivo, fecha_inicio,
                        fecha_finalizacion, observaciones, tipo_remuneracion,
                        requiere_suplente, dias_correspondientes, estado, fecha_solicitud)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', NOW())";
            $stmtDis = $this->db->prepare($sqlDis);
            if (!$stmtDis) throw new Exception("Error al preparar DISFRUTE_DE_VACACIONES: " . $this->db->error);
            $stmtDis->bind_param("iisssssssi", $id_trabajador, $id_convenio, $codigo_disfrute, $motivo, $fecha_inicio, $fecha_final_val, $observaciones, $tipo_remuneracion, $requiere_suplente, $dias_correspondientes);
            if (!$stmtDis->execute()) {
                throw new Exception("Error al ejecutar DISFRUTE_DE_VACACIONES: " . $stmtDis->error);
            }

            // Verificar que ambos valores realmente quedaron persistidos.
            // Esto permite detectar columnas ausentes, triggers que alteren
            // los datos o cualquier discrepancia en la estructura de la BD.
            $idInsertado = (int) $this->db->insert_id;
            if ($idInsertado <= 0) {
                throw new Exception("La solicitud se ejecutó, pero no se obtuvo un ID de registro válido.");
            }

            $sqlVerificar = "SELECT tipo_remuneracion, requiere_suplente
                             FROM DISFRUTE_DE_VACACIONES
                             WHERE id_dis = ? LIMIT 1";
            $stmtVerificar = $this->db->prepare($sqlVerificar);
            if (!$stmtVerificar) {
                throw new Exception("No se pudo verificar el registro de días de disfrute: " . $this->db->error);
            }
            $stmtVerificar->bind_param("i", $idInsertado);
            if (!$stmtVerificar->execute()) {
                $errorVerificacion = $stmtVerificar->error;
                $stmtVerificar->close();
                throw new Exception("No se pudieron verificar los campos de la solicitud: " . $errorVerificacion);
            }

            $stmtVerificar->bind_result($remuneracionGuardada, $suplenteGuardado);
            $registroVerificado = $stmtVerificar->fetch();
            $stmtVerificar->close();
            $stmtDis->close();

            if (!$registroVerificado
                || $remuneracionGuardada !== $tipo_remuneracion
                || $suplenteGuardado !== $requiere_suplente) {
                throw new Exception("La solicitud fue insertada, pero los valores de remuneración o suplente no coinciden con lo seleccionado.");
            }

            return $idInsertado;

        } catch (\Throwable $e) {
            error_log("Error registrar dias de disfrute: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /**
     * Marca como 'Vencida' toda solicitud 'Pendiente' cuya fecha_solicitud
     * tenga más de 15 días calendario sin decisión. Se ejecuta antes de
     * cualquier lectura para que el estado mostrado siempre esté al día.
     */
    private function venceSolicitudesPendientes() {
        $sql = "UPDATE DISFRUTE_DE_VACACIONES
                SET estado = 'Vencida'
                WHERE estado = 'Pendiente'
                  AND fecha_solicitud IS NOT NULL
                  AND fecha_solicitud <= (NOW() - INTERVAL 15 DAY)";
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
            error_log("Error al vencer solicitudes de dias de disfrute: " . $e->getMessage());
        }
    }

    /**
     * Un trabajador solo puede tener UNA solicitud de días de disfrute
     * activa (en estado 'Pendiente', dentro del plazo de 15 días) a la
     * vez. Si la anterior venció o ya fue decidida, puede registrar otra.
     */
    public function tieneSolicitudActiva($id_trabajador) {
        $this->venceSolicitudesPendientes();

        $sql = "SELECT id_dis FROM DISFRUTE_DE_VACACIONES
                WHERE id_trabajador = ? AND estado = 'Pendiente'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id_trabajador);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_assoc();
    }

    /**
     * @param string $buscar Texto libre (trabajador o código del registro).
     * @param string $estado 'Pendiente' | 'Aprobado' | 'Desaprobado' | '' (todos).
     * @param int|null $idTrabajador Si se indica, solo devuelve las solicitudes de ese trabajador.
     */
    public function mostrarDiasDisfrute($buscar = '', $estado = '', $idTrabajador = null) {
        $this->venceSolicitudesPendientes();

        $sql = "SELECT
                    d.id_dis,
                    d.id_trabajador,
                    d.codigo_disfrute,
                    d.motivo,
                    d.motivo AS descripcion,
                    d.fecha_inicio,
                    d.fecha_finalizacion,
                    d.observaciones,
                    d.justificacion_desaprobacion,
                    d.dias_correspondientes,
                    d.tipo_remuneracion,
                    d.requiere_suplente,
                    d.fecha_solicitud,
                    d.estado,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    c.nombre_cargo AS cargo,
                    d.id_convenio,
                    cv.nombre_gremio AS convenio_gremio
                FROM DISFRUTE_DE_VACACIONES d
                INNER JOIN TRABAJADOR t ON d.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN CONVENIO cv ON cv.id_convenio = d.id_convenio";

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

        if ($idTrabajador !== null) {
            $condiciones[] = "d.id_trabajador = ?";
            $tipos .= 'i';
            $parametros[] = (int) $idTrabajador;
        }

        if (in_array($estado, ['Pendiente', 'Aprobado', 'Desaprobado', 'Vencida'], true)) {
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
        $this->venceSolicitudesPendientes();

        $sql = "SELECT
                    d.id_dis,
                    d.id_trabajador,
                    d.codigo_disfrute,
                    d.motivo,
                    d.motivo AS descripcion,
                    d.fecha_inicio,
                    d.fecha_finalizacion,
                    d.observaciones,
                    d.justificacion_desaprobacion,
                    d.dias_correspondientes,
                    d.tipo_remuneracion,
                    d.requiere_suplente,
                    d.fecha_solicitud,
                    d.fecha_aprobacion,
                    d.fecha_desaprobacion,
                    d.estado,
                    CONCAT(t.apellidos, ' ', t.nombres) AS trabajador,
                    t.cedula,
                    c.nombre_cargo AS cargo,
                    d.id_convenio,
                    cv.nombre_gremio AS convenio_gremio
                FROM DISFRUTE_DE_VACACIONES d
                INNER JOIN TRABAJADOR t ON d.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN CONVENIO cv ON cv.id_convenio = d.id_convenio
                WHERE d.id_dis = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_dis);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Actualiza el estado y registra la fecha de aprobación/desaprobación.
     * Al aprobar, fecha_inicio pasa a ser la fecha efectiva de aprobación y
     * fecha_finalizacion se calcula a 30 días hábiles desde ese día.
     */
    public function cambiarEstadoDiasDisfrute($id_dis, $nuevoEstado, $justificacion = '') {
        if (!in_array($nuevoEstado, ['Aprobado', 'Desaprobado'], true)) {
            $this->ultimoError = 'Estado de días de disfrute no válido.';
            return false;
        }

        $justificacion = trim((string) $justificacion);
        if ($nuevoEstado === 'Desaprobado' && $justificacion === '') {
            $this->ultimoError = 'La justificación de desaprobación es obligatoria.';
            return false;
        }

        try {
            $zonaHoraria = new DateTimeZone('America/Caracas');
            $momentoDecision = new DateTimeImmutable('now', $zonaHoraria);
            $fechaDecision = $momentoDecision->format('Y-m-d H:i:s');

            if ($nuevoEstado === 'Aprobado') {
                // Se respeta la cantidad de días que ya quedó guardada en la
                // solicitud (la del convenio vigente al momento de solicitarla).
                $sqlActual = "SELECT dias_correspondientes FROM DISFRUTE_DE_VACACIONES WHERE id_dis = ? LIMIT 1";
                $stmtActual = $this->db->prepare($sqlActual);
                if (!$stmtActual) {
                    $this->ultimoError = 'No se pudo consultar la solicitud: ' . $this->db->error;
                    return false;
                }
                $stmtActual->bind_param('i', $id_dis);
                $stmtActual->execute();
                $filaActual = $stmtActual->get_result()->fetch_assoc();
                $stmtActual->close();

                $diasCorrespondientes = ($filaActual && $filaActual['dias_correspondientes'] !== null)
                    ? (int) $filaActual['dias_correspondientes']
                    : $this->diasSegunConvenioVigente();

                if ($diasCorrespondientes === null) {
                    $this->ultimoError = 'No se pudo determinar la cantidad de días correspondientes para esta solicitud. Registre o actualice el convenio colectivo vigente.';
                    return false;
                }

                $fechaInicioReal = $momentoDecision->format('Y-m-d');
                $fechaFinalizacion = $this->calcularFechaFinalizacion($momentoDecision, $diasCorrespondientes);
                if ($fechaFinalizacion === null) {
                    return false;
                }

                $sql = "UPDATE DISFRUTE_DE_VACACIONES
                        SET estado = ?,
                            justificacion_desaprobacion = NULL,
                            fecha_aprobacion = ?,
                            fecha_inicio = ?,
                            fecha_finalizacion = ?
                        WHERE id_dis = ?";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    $this->ultimoError = 'No se pudo preparar la aprobación: ' . $this->db->error;
                    return false;
                }
                $stmt->bind_param('ssssi', $nuevoEstado, $fechaDecision, $fechaInicioReal, $fechaFinalizacion, $id_dis);
            } else {
                $sql = "UPDATE DISFRUTE_DE_VACACIONES
                        SET estado = ?,
                            justificacion_desaprobacion = ?,
                            fecha_desaprobacion = ?
                        WHERE id_dis = ?";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    $this->ultimoError = 'No se pudo preparar la desaprobación: ' . $this->db->error;
                    return false;
                }
                $stmt->bind_param('sssi', $nuevoEstado, $justificacion, $fechaDecision, $id_dis);
            }

            if (!$stmt->execute()) {
                $this->ultimoError = $stmt->error ?: 'No se pudo actualizar el registro.';
                return false;
            }

            return $nuevoEstado;
        } catch (Throwable $e) {
            error_log("Cambiar estado dias de disfrute id_dis={$id_dis}: " . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /**
     * Estado actual de un registro ('Aprobado' | 'Desaprobado' | null si no existe).
     */
    public function obtenerEstadoDiasDisfrute($id_dis) {
        $this->venceSolicitudesPendientes();

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
