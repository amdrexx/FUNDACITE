<?php

require_once '../conexion.php';

class Trabajador
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function existeCedula($cedula)
    {
        $sql = "SELECT id_trabajador
                FROM TRABAJADOR
                WHERE cedula = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Lista todos los cargos disponibles en la tabla CARGO
     */
    public function listarCargos()
    {
        $cargos = [];
        $sql = "SELECT id_cargo, nombre_cargo FROM CARGO ORDER BY nombre_cargo ASC";
        $resultado = $this->conexion->query($sql);

        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $cargos[] = $fila;
            }
        }

        return $cargos;
    }

    /**
     * Lista las direcciones ya registradas en el maestro de direcciones
     */
    public function listarDirecciones()
    {
        $direcciones = [];

        $sql = "SELECT
                    d.id_dir,
                    d.nombre AS direccion,
                    p.nombre AS parroquia,
                    m.nombre AS municipio,
                    e.nombre AS estado
                FROM DIRECCION d
                INNER JOIN PARROQUIA p ON d.cod_par = p.cod_par
                INNER JOIN MUNICIPIO m ON p.cod_muni = m.cod_muni
                INNER JOIN ESTADO e ON m.cod_est = e.cod_est
                ORDER BY e.nombre, m.nombre, p.nombre, d.nombre";

        $resultado = $this->conexion->query($sql);

        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $direcciones[] = $fila;
            }
        }

        return $direcciones;
    }

    /**
     * Crea o reutiliza un registro en DIRECCION
     */
    public function crearDireccion($codPar, $nombreDireccion)
    {
        $sqlBuscar = "SELECT id_dir
                     FROM DIRECCION
                     WHERE cod_par = ? AND nombre = ?
                     LIMIT 1";

        $stmtBuscar = $this->conexion->prepare($sqlBuscar);
        $stmtBuscar->bind_param("is", $codPar, $nombreDireccion);
        $stmtBuscar->execute();

        $resultadoBuscar = $stmtBuscar->get_result();

        if ($resultadoBuscar && $resultadoBuscar->num_rows > 0) {
            $fila = $resultadoBuscar->fetch_assoc();
            return $fila['id_dir'];
        }

        $sqlInsert = "INSERT INTO DIRECCION (cod_par, nombre)
                     VALUES (?, ?)";

        $stmtInsert = $this->conexion->prepare($sqlInsert);
        $stmtInsert->bind_param("is", $codPar, $nombreDireccion);

        if ($stmtInsert->execute()) {
            return $this->conexion->insert_id;
        }

        return false;
    }

    public function actualizarDireccion($idDir, $codPar, $nombreDireccion)
    {
        $sql = "UPDATE DIRECCION
                SET cod_par = ?, nombre = ?
                WHERE id_dir = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("isi", $codPar, $nombreDireccion, $idDir);

        return $stmt->execute();
    }

    public function registrarTrabajador(
        $tipoDocumento,
        $cedula,
        $nombres,
        $apellidos,
        $fechaNacimiento,
        $hijosMenores,
        $hijosMayores,
        $anosServicioAdministracionPublica,
        $nivelEducacion,
        $genero,
        $estadoCivil,
        $telefono,
        $correo,
        $tipoPersonal,
        $status,
        $idDir = null,
        $idCargo = null
    )
    {
        $sql = "INSERT INTO TRABAJADOR
        (
            tipo_documento,
            cedula,
            nombres,
            apellidos,
            fecha_nacimiento,
            hijo_menor,
            hijo_mayor,
            anos_servicio_administracion_publica,
            nivel_educacion,
            genero,
            estado_civil,
            telefono,
            correo,
            tipo_personal,
            status,
            id_dir,
            id_cargo
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
        )";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            error_log("Error al preparar el registro de trabajador: " . $this->conexion->error);
            return false;
        }

        $stmt->bind_param(
            "sssssiiisssssssii",
            $tipoDocumento,
            $cedula,
            $nombres,
            $apellidos,
            $fechaNacimiento,
            $hijosMenores,
            $hijosMayores,
            $anosServicioAdministracionPublica,
            $nivelEducacion,
            $genero,
            $estadoCivil,
            $telefono,
            $correo,
            $tipoPersonal,
            $status,
            $idDir,
            $idCargo
        );

        if ($stmt->execute()) {
            return $this->conexion->insert_id;
        }

        return false;
    }

    /** Devuelve los hijos y calcula su edad directamente desde la fecha de nacimiento. */
    public function listarHijosTrabajador(int $idTrabajador): array
    {
        $stmt = $this->conexion->prepare("SELECT id_hijo, fecha_nacimiento, TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) AS edad FROM HIJO_TRABAJADOR WHERE id_trabajador = ? ORDER BY id_hijo");
        if (!$stmt) return [];
        $stmt->bind_param("i", $idTrabajador);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Actualiza los contadores persistidos usando la edad real de los hijos hoy. */
    public function actualizarContadoresHijos(?int $idTrabajador = null): bool
    {
        $sql = "UPDATE TRABAJADOR t
                LEFT JOIN (
                    SELECT id_trabajador,
                           SUM(fecha_nacimiento > DATE_SUB(CURDATE(), INTERVAL 18 YEAR)) AS menores,
                           SUM(fecha_nacimiento <= DATE_SUB(CURDATE(), INTERVAL 18 YEAR)) AS mayores
                    FROM HIJO_TRABAJADOR GROUP BY id_trabajador
                ) h ON h.id_trabajador = t.id_trabajador
                SET t.hijo_menor = COALESCE(h.menores, 0),
                    t.hijo_mayor = COALESCE(h.mayores, 0)";
        if ($idTrabajador !== null) $sql .= " WHERE t.id_trabajador = " . (int)$idTrabajador;
        return (bool)$this->conexion->query($sql);
    }

    /** Reemplaza la lista de hijos usando fechas de nacimiento, no edades estáticas. */
    public function reemplazarHijosTrabajador(int $idTrabajador, array $fechasNacimiento): bool
    {
        $stmtDel = $this->conexion->prepare("DELETE FROM HIJO_TRABAJADOR WHERE id_trabajador = ?");
        if (!$stmtDel) return false;
        $stmtDel->bind_param("i", $idTrabajador);
        if (!$stmtDel->execute()) return false;

        $stmtIns = $this->conexion->prepare("INSERT INTO HIJO_TRABAJADOR (id_trabajador, fecha_nacimiento) VALUES (?, ?)");
        if (!$stmtIns) return false;
        foreach ($fechasNacimiento as $fecha) {
            $fecha = trim((string)$fecha);
            $stmtIns->bind_param("is", $idTrabajador, $fecha);
            if (!$stmtIns->execute()) return false;
        }
        return $this->actualizarContadoresHijos($idTrabajador);
    }

    public function listarTrabajadores($buscar = '')
    {
        $this->actualizarContadoresHijos();
        $trabajadores = [];

        $sql = "SELECT 
                    t.id_trabajador,
                    t.cedula,
                    t.nombres,
                    t.apellidos,
                    t.hijo_menor,
                    t.hijo_mayor,
                    t.anos_servicio_administracion_publica,
                    t.nivel_educacion,
                    t.tipo_personal,
                    t.status,
                    c.nombre_cargo,
                    co.tipo_contrato,
                    primer.fecha_primer_contrato
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN (
                    SELECT id_trabajador, MAX(id_contrato) AS id_contrato
                    FROM CONTRATO
                    GROUP BY id_trabajador
                ) ultimo ON ultimo.id_trabajador = t.id_trabajador
                LEFT JOIN (
                    SELECT id_trabajador, MIN(fecha_contrato) AS fecha_primer_contrato
                    FROM CONTRATO
                    GROUP BY id_trabajador
                ) primer ON primer.id_trabajador = t.id_trabajador
                LEFT JOIN CONTRATO co ON co.id_contrato = ultimo.id_contrato";

        if (!empty($buscar)) {
            $buscar = $this->conexion->real_escape_string($buscar);

            $sql .= " WHERE 
                        t.cedula LIKE '%$buscar%' OR
                        t.nombres LIKE '%$buscar%' OR
                        t.apellidos LIKE '%$buscar%'";
        }

        $sql .= " ORDER BY t.id_trabajador DESC";

        $resultado = $this->conexion->query($sql);

        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $trabajadores[] = $fila;
            }
        }

        return $trabajadores;
    }

    public function obtenerTrabajadorPorId($idTrabajador)
    {
        $this->actualizarContadoresHijos((int)$idTrabajador);
        $sql = "SELECT 
                    t.id_trabajador,
                    t.tipo_documento AS tipoDoc,
                    t.cedula,
                    t.nombres,
                    t.apellidos,
                    t.fecha_nacimiento AS fecha,

                    TIMESTAMPDIFF(
                        YEAR,
                        t.fecha_nacimiento,
                        CURDATE()
                    ) AS edad,

                    t.hijo_menor,
                    t.hijo_mayor,
                    t.anos_servicio_administracion_publica,
                    t.nivel_educacion,
                    t.genero,
                    t.tipo_personal,
                    t.estado_civil AS estadoCivil,
                    t.telefono AS numeroTelefono,
                    t.correo AS correoElectronico,
                    t.status AS estatus_laboral,
                    t.id_dir,
                    t.id_cargo,
                    c.nombre_cargo,
                    d.nombre AS direccion,
                    p.cod_par,
                    p.nombre AS parroquia,
                    m.cod_muni,
                    m.nombre AS municipio,
                    e.cod_est,
                    e.nombre AS estado
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN DIRECCION d ON t.id_dir = d.id_dir
                LEFT JOIN PARROQUIA p ON d.cod_par = p.cod_par
                LEFT JOIN MUNICIPIO m ON p.cod_muni = m.cod_muni
                LEFT JOIN ESTADO e ON m.cod_est = e.cod_est
                WHERE t.id_trabajador = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idTrabajador);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public function actualizarTrabajador(
        $idTrabajador,
        $estadoCivil,
        $telefono,
        $correo,
        $status,
        $hijosMenores,
        $hijosMayores,
        $anosServicioAdministracionPublica,
        $nivelEducacion,
        $tipoPersonal,
        $idDir = null
    ) {
        $sql = "UPDATE TRABAJADOR
                SET estado_civil = ?,
                    telefono = ?,
                    correo = ?,
                    status = ?,
                    hijo_menor = ?,
                    hijo_mayor = ?,
                    anos_servicio_administracion_publica = ?,
                    nivel_educacion = ?,
                    tipo_personal = ?,
                    id_dir = ?
                WHERE id_trabajador = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param(
"ssssiiissii",
            $estadoCivil,
            $telefono,
            $correo,
            $status,
            $hijosMenores,
            $hijosMayores,
            $anosServicioAdministracionPublica,
            $nivelEducacion,
            $tipoPersonal,
            $idDir,
            $idTrabajador
        );

        return $stmt->execute();
    }

    /**
     * Exclusividad aplicada durante el registro de un trabajador nuevo:
     * Presidente y Director admiten un solo titular. Se finaliza la asignación
     * del titular anterior y se libera únicamente TRABAJADOR.id_cargo.
     * No se modifica el estatus laboral ni se elimina ningún registro.
     * Debe ejecutarse dentro de la transacción de registro.
     */
    public function desasignarOcupantesPreviosCargoUnico(int $idCargo, int $idTrabajadorActual, ?int $idUsuarioDesvinculador = null): array
    {
        if ($idCargo <= 0 || $idTrabajadorActual <= 0) {
            return [];
        }

        $stmtCargo = $this->conexion->prepare(
            "SELECT LOWER(TRIM(nombre_cargo)) AS nombre_cargo
             FROM CARGO WHERE id_cargo = ? LIMIT 1 FOR UPDATE"
        );
        if (!$stmtCargo) {
            throw new RuntimeException('No se pudo verificar el cargo seleccionado: ' . $this->conexion->error);
        }
        $stmtCargo->bind_param('i', $idCargo);
        $stmtCargo->execute();
        $cargo = $stmtCargo->get_result()->fetch_assoc();
        $stmtCargo->close();

        $nombreCargo = (string) ($cargo['nombre_cargo'] ?? '');
        if (!in_array($nombreCargo, ['presidente', 'director'], true)) {
            return [];
        }

        $stmtAnteriores = $this->conexion->prepare(
            "SELECT id_trabajador, nombres, apellidos, cedula
             FROM TRABAJADOR
             WHERE id_cargo = ? AND id_trabajador <> ?"
        );
        if (!$stmtAnteriores) {
            throw new RuntimeException('No se pudieron consultar los titulares anteriores: ' . $this->conexion->error);
        }
        $stmtAnteriores->bind_param('ii', $idCargo, $idTrabajadorActual);
        $stmtAnteriores->execute();
        $anteriores = $stmtAnteriores->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtAnteriores->close();

        if (!$anteriores) {
            return [];
        }

        // Cerrar el historial de los titulares desplazados antes de liberar el cargo.
        $stmtHistorial = $this->conexion->prepare(
            "UPDATE ASIGNACION_CARGO
             SET fecha_fin = NOW(),
                 estado = 'FINALIZADA',
                 id_usuario_desvinculador = ?
             WHERE id_cargo = ? AND id_trabajador <> ? AND estado = 'ACTIVA'"
        );
        if (!$stmtHistorial) {
            throw new RuntimeException('No se pudo cerrar el historial de los titulares anteriores: ' . $this->conexion->error);
        }
        $stmtHistorial->bind_param('iii', $idUsuarioDesvinculador, $idCargo, $idTrabajadorActual);
        if (!$stmtHistorial->execute()) {
            $error = $stmtHistorial->error;
            $stmtHistorial->close();
            throw new RuntimeException('Error al cerrar el historial de asignación anterior: ' . $error);
        }
        $stmtHistorial->close();

        $stmtLiberar = $this->conexion->prepare(
            "UPDATE TRABAJADOR
             SET id_cargo = NULL
             WHERE id_cargo = ? AND id_trabajador <> ?"
        );
        if (!$stmtLiberar) {
            throw new RuntimeException('No se pudo liberar el cargo anterior: ' . $this->conexion->error);
        }
        $stmtLiberar->bind_param('ii', $idCargo, $idTrabajadorActual);
        if (!$stmtLiberar->execute()) {
            $error = $stmtLiberar->error;
            $stmtLiberar->close();
            throw new RuntimeException('Error al desasignar el cargo anterior: ' . $error);
        }
        $stmtLiberar->close();

        return $anteriores;
    }

    /**
     * En edición no se sustituye automáticamente al titular de Presidente/Director.
     * La sustitución automática está reservada al registro de un nuevo trabajador.
     */
    public function validarDisponibilidadCargoUnico(int $idCargo, int $idTrabajadorActual): bool
    {
        if ($idCargo <= 0 || $idTrabajadorActual <= 0) {
            return true;
        }

        $stmtCargo = $this->conexion->prepare(
            "SELECT LOWER(TRIM(nombre_cargo)) AS nombre_cargo
             FROM CARGO WHERE id_cargo = ? LIMIT 1"
        );
        if (!$stmtCargo) {
            throw new RuntimeException('No se pudo verificar el cargo: ' . $this->conexion->error);
        }
        $stmtCargo->bind_param('i', $idCargo);
        $stmtCargo->execute();
        $cargo = $stmtCargo->get_result()->fetch_assoc();
        $stmtCargo->close();

        $nombreCargo = (string) ($cargo['nombre_cargo'] ?? '');
        if (!in_array($nombreCargo, ['presidente', 'director'], true)) {
            return true;
        }

        $stmtOcupante = $this->conexion->prepare(
            "SELECT id_trabajador
             FROM TRABAJADOR
             WHERE id_cargo = ? AND id_trabajador <> ?
             LIMIT 1 FOR UPDATE"
        );
        if (!$stmtOcupante) {
            throw new RuntimeException('No se pudo verificar la titularidad del cargo: ' . $this->conexion->error);
        }
        $stmtOcupante->bind_param('ii', $idCargo, $idTrabajadorActual);
        $stmtOcupante->execute();
        $ocupante = $stmtOcupante->get_result()->fetch_assoc();
        $stmtOcupante->close();

        return !$ocupante;
    }

    /**
     * Sincroniza el historial con el cargo que acaba de quedar en TRABAJADOR.id_cargo.
     * Debe ejecutarse dentro de la misma transacción que modifica al trabajador.
     * Cada cambio cierra la asignación anterior y crea una nueva fila histórica.
     */
    public function sincronizarHistorialAsignacionCargo($idTrabajador, $idCargo, $idUsuario = null): bool
    {
        $idTrabajador = (int) $idTrabajador;
        $idCargo = ($idCargo === null || $idCargo === '' || (int) $idCargo <= 0) ? null : (int) $idCargo;
        $idUsuario = ($idUsuario === null || (int) $idUsuario <= 0) ? null : (int) $idUsuario;

        if ($idTrabajador <= 0) {
            return false;
        }

        $stmtActivas = $this->conexion->prepare(
            "SELECT id_cargo
             FROM ASIGNACION_CARGO
             WHERE id_trabajador = ? AND estado = 'ACTIVA'
             FOR UPDATE"
        );
        if (!$stmtActivas) {
            throw new RuntimeException('No se pudo consultar el historial de asignaciones: ' . $this->conexion->error);
        }
        $stmtActivas->bind_param('i', $idTrabajador);
        $stmtActivas->execute();
        $filasActivas = $stmtActivas->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtActivas->close();

        $yaTieneCargoActivo = false;
        if ($idCargo !== null) {
            foreach ($filasActivas as $filaActiva) {
                if ((int) $filaActiva['id_cargo'] === $idCargo) {
                    $yaTieneCargoActivo = true;
                    break;
                }
            }
        }

        if ($yaTieneCargoActivo) {
            $stmtCerrarInconsistentes = $this->conexion->prepare(
                "UPDATE ASIGNACION_CARGO
                 SET fecha_fin = NOW(),
                     estado = 'FINALIZADA',
                     id_usuario_desvinculador = ?
                 WHERE id_trabajador = ? AND estado = 'ACTIVA' AND id_cargo <> ?"
            );
            if (!$stmtCerrarInconsistentes) {
                throw new RuntimeException('No se pudieron cerrar asignaciones anteriores: ' . $this->conexion->error);
            }
            $stmtCerrarInconsistentes->bind_param('iii', $idUsuario, $idTrabajador, $idCargo);
            $ok = $stmtCerrarInconsistentes->execute();
            $stmtCerrarInconsistentes->close();
            return $ok;
        }

        $stmtCerrar = $this->conexion->prepare(
            "UPDATE ASIGNACION_CARGO
             SET fecha_fin = NOW(),
                 estado = 'FINALIZADA',
                 id_usuario_desvinculador = ?
             WHERE id_trabajador = ? AND estado = 'ACTIVA'"
        );
        if (!$stmtCerrar) {
            throw new RuntimeException('No se pudo cerrar la asignación anterior: ' . $this->conexion->error);
        }
        $stmtCerrar->bind_param('ii', $idUsuario, $idTrabajador);
        if (!$stmtCerrar->execute()) {
            $error = $stmtCerrar->error;
            $stmtCerrar->close();
            throw new RuntimeException('Error al cerrar la asignación anterior: ' . $error);
        }
        $stmtCerrar->close();

        if ($idCargo === null) {
            return true;
        }

        if ($idUsuario !== null) {
            $stmtInsertar = $this->conexion->prepare(
                "INSERT INTO ASIGNACION_CARGO
                    (id_trabajador, id_cargo, fecha_asignacion, id_usuario_asignador, estado)
                 VALUES (?, ?, NOW(), ?, 'ACTIVA')"
            );
            if (!$stmtInsertar) {
                throw new RuntimeException('No se pudo preparar el registro de asignación: ' . $this->conexion->error);
            }
            $stmtInsertar->bind_param('iii', $idTrabajador, $idCargo, $idUsuario);
        } else {
            $stmtInsertar = $this->conexion->prepare(
                "INSERT INTO ASIGNACION_CARGO
                    (id_trabajador, id_cargo, fecha_asignacion, id_usuario_asignador, estado)
                 VALUES (?, ?, NOW(), NULL, 'ACTIVA')"
            );
            if (!$stmtInsertar) {
                throw new RuntimeException('No se pudo preparar el registro de asignación: ' . $this->conexion->error);
            }
            $stmtInsertar->bind_param('ii', $idTrabajador, $idCargo);
        }

        $ok = $stmtInsertar->execute();
        if (!$ok) {
            $error = $stmtInsertar->error;
            $stmtInsertar->close();
            throw new RuntimeException('No se pudo registrar la nueva asignación: ' . $error);
        }
        $stmtInsertar->close();
        return true;
    }

    public function eliminarLogicamenteTrabajador($idTrabajador)
    {
        $nuevoStatus = "Inactivo";

        $sql = "UPDATE TRABAJADOR
                SET status = ?
                WHERE id_trabajador = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("si", $nuevoStatus, $idTrabajador);

        return $stmt->execute();
    }

    public function listarTrabajadoresSinUsuario()
    {
        $sql = "SELECT
                    t.id_trabajador,
                    t.cedula,
                    t.nombres,
                    t.apellidos
                FROM TRABAJADOR t
                LEFT JOIN USUARIO u
                    ON t.id_trabajador = u.id_trabajador
                WHERE u.id_trabajador IS NULL
                AND t.status = 'Activo'
                ORDER BY t.nombres ASC";

        $resultado = $this->conexion->query($sql);

        if (!$resultado) {
            return [];
        }

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }
}
