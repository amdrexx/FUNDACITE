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

    public function registrarTrabajador(
        $tipoDocumento,
        $cedula,
        $nombres,
        $apellidos,
        $fechaNacimiento,
        $genero,
        $estadoCivil,
        $telefono,
        $correo,
        $status,
        $idCargo
    )
    {
        $sql = "INSERT INTO TRABAJADOR
        (
            tipo_documento,
            cedula,
            nombres,
            apellidos,
            fecha_nacimiento,
            genero,
            estado_civil,
            telefono,
            correo,
            status,
            id_cargo
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?,?
        )";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "ssssssssssi",
            $tipoDocumento,
            $cedula,
            $nombres,
            $apellidos,
            $fechaNacimiento,
            $genero,
            $estadoCivil,
            $telefono,
            $correo,
            $status,
            $idCargo
        );

        if ($stmt->execute()) {
            return $this->conexion->insert_id;
        }

        return false;
    }

    public function registrarContrato(
        $idTrabajador,
        $fechaIngreso
    )
    {
        $tipoContrato = "Indefinido";
        $salario = 1.00;

        $sql = "INSERT INTO CONTRATO
        (
            id_trabajador,
            tipo_contrato,
            fecha_ingreso,
            salario
        )
        VALUES
        (
            ?,?,?,?
        )";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "issd",
            $idTrabajador,
            $tipoContrato,
            $fechaIngreso,
            $salario
        );

        return $stmt->execute();
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
                co.fecha_ingreso,
                t.status,
                c.nombre_cargo
            FROM TRABAJADOR t
            INNER JOIN CARGO c ON t.id_cargo = c.id_cargo
            LEFT JOIN CONTRATO co ON t.id_trabajador = co.id_trabajador";

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
                t.genero,
                t.estado_civil AS estadoCivil,
                t.telefono AS numeroTelefono,
                t.correo AS correoElectronico,
                t.status AS estatus_laboral,
                t.id_cargo,
                c.nombre_cargo,
                co.fecha_ingreso
            FROM TRABAJADOR t
            LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
            LEFT JOIN CONTRATO co ON t.id_trabajador = co.id_trabajador
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
    $idCargo
) {
    $sql = "UPDATE TRABAJADOR
            SET estado_civil = ?,
                telefono = ?,
                correo = ?,
                status = ?,
                id_cargo = ?
            WHERE id_trabajador = ?";

    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param(
        "ssssii",
        $estadoCivil,
        $telefono,
        $correo,
        $status,
        $idCargo,
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
}