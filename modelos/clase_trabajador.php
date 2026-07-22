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
     * Lista las direcciones ya registradas en el maestro de direcciones
     * (tabla DIRECCION), con su ruta completa Estado/Municipio/Parroquia,
     * para mostrarlas como opciones de selección en el registro de trabajador.
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

    public function registrarTrabajador(
        $tipoDocumento,
        $cedula,
        $nombres,
        $apellidos,
        $fechaNacimiento,
        $fechaIngreso,
        $genero,
        $estadoCivil,
        $telefono,
        $correo,
        $status,
        $idCargo,
        $idDir = null
    )
    {
        $sql = "INSERT INTO TRABAJADOR
        (
            tipo_documento,
            cedula,
            nombres,
            apellidos,
            fecha_nacimiento,
            fecha_ingreso,
            genero,
            estado_civil,
            telefono,
            correo,
            status,
            id_cargo,
            id_dir
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?,?,?,?
        )";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "sssssssssssii",
            $tipoDocumento,
            $cedula,
            $nombres,
            $apellidos,
            $fechaNacimiento,
            $fechaIngreso,
            $genero,
            $estadoCivil,
            $telefono,
            $correo,
            $status,
            $idCargo,
            $idDir
        );

        if ($stmt->execute()) {
            return $this->conexion->insert_id;
        }

        return false;
    }

    public function listarCargos()
    {
        $cargos = [];

        $sql = "SELECT id_cargo, nombre_cargo
                FROM CARGO
                ORDER BY nombre_cargo";

        $resultado = $this->conexion->query($sql);

        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $cargos[] = $fila;
            }
        }

        return $cargos;
    }

    public function listarTrabajadores($buscar = '')
    {
        $trabajadores = [];

        $sql = "SELECT 
                    t.id_trabajador,
                    t.cedula,
                    t.nombres,
                    t.apellidos,
                    t.fecha_ingreso,
                    t.status,
                    c.nombre_cargo,
                    co.tipo_contrato
                FROM TRABAJADOR t
                INNER JOIN CARGO c ON t.id_cargo = c.id_cargo
                LEFT JOIN CONTRATO co ON co.id_trabajador = t.id_trabajador";

        if (!empty($buscar)) {
            $buscar = $this->conexion->real_escape_string($buscar);

            $sql .= " WHERE 
                        t.cedula LIKE '%$buscar%' OR
                        t.nombres LIKE '%$buscar%' OR
                        t.apellidos LIKE '%$buscar%' OR
                        c.nombre_cargo LIKE '%$buscar%'";
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

                    t.fecha_ingreso,
                    t.genero,
                    t.estado_civil AS estadoCivil,
                    t.telefono AS numeroTelefono,
                    t.correo AS correoElectronico,
                    t.status AS estatus_laboral,
                    t.id_cargo,
                    c.nombre_cargo,
                    t.id_dir,
                    d.nombre AS direccion,
                    p.nombre AS parroquia,
                    m.nombre AS municipio,
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
        $idCargo,
        $idDir = null
    ) {
        $sql = "UPDATE TRABAJADOR
                SET estado_civil = ?,
                    telefono = ?,
                    correo = ?,
                    status = ?,
                    id_cargo = ?,
                    id_dir = ?
                WHERE id_trabajador = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param(
            "ssssiii",
            $estadoCivil,
            $telefono,
            $correo,
            $status,
            $idCargo,
            $idDir,
            $idTrabajador
        );

        return $stmt->execute();
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