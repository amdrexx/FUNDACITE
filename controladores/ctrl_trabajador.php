<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';

$_SESSION['old_input'] = $_POST;

$errores = [];

$tipoDoc          = trim($_POST['tipoDoc'] ?? '');
$cedula           = trim($_POST['cedula'] ?? '');
$nombres          = trim($_POST['nombres'] ?? '');
$apellidos        = trim($_POST['apellidos'] ?? '');
$fechaNacimiento  = trim($_POST['fecha'] ?? '');
$genero           = trim($_POST['genero'] ?? '');
$estadoCivil      = trim($_POST['estadoCivil'] ?? '');
$correo           = trim($_POST['correoElectronico'] ?? '');
$telefono         = trim($_POST['numeroTelefono'] ?? '');
$fechaIngreso     = trim($_POST['fecha_ingreso'] ?? '');
$idCargo          = trim($_POST['cargo_id'] ?? '');
$status           = trim($_POST['estatus_laboral'] ?? '');

if ($tipoDoc == 'V') {
    $tipoDocumento = 'Cédula';
} elseif ($tipoDoc == 'E') {
    $tipoDocumento = 'Cédula de Extranjería';
} else {
    $tipoDocumento = '';
}

if ($status == 'Retirado') {
    $status = 'Inactivo';
}

if (empty($tipoDocumento))
    $errores[] = "Seleccione el tipo de documento.";

if (empty($cedula))
    $errores[] = "Ingrese la cédula.";

if (empty($nombres))
    $errores[] = "Ingrese los nombres.";

if (empty($apellidos))
    $errores[] = "Ingrese los apellidos.";

if (empty($fechaNacimiento))
    $errores[] = "Ingrese la fecha de nacimiento.";

if (empty($genero))
    $errores[] = "Seleccione el género.";

if (empty($estadoCivil))
    $errores[] = "Seleccione el estado civil.";

if (empty($telefono))
    $errores[] = "Ingrese el teléfono.";

if (empty($correo))
    $errores[] = "Ingrese el correo.";

if (empty($fechaIngreso))
    $errores[] = "Ingrese la fecha de ingreso.";

if (empty($idCargo))
    $errores[] = "Seleccione un cargo.";

if (empty($status))
    $errores[] = "Seleccione el estatus laboral.";

$trabajador = new Trabajador($conexion);

if ($trabajador->existeCedula($cedula)) {
    $errores[] = "La cédula ya se encuentra registrada.";
}

if (!empty($errores)) {

    $_SESSION['errores'] = $errores;

    header("Location: vistas/registro_trabajador.php");
    exit;
}

$conexion->begin_transaction();

try {

    $idTrabajador = $trabajador->registrarTrabajador(
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

    if (!$idTrabajador) {
        throw new Exception("Error al registrar trabajador.");
    }

    if (
        !$trabajador->registrarContrato(
            $idTrabajador,
            $fechaIngreso
        )
    ) {
        throw new Exception("Error al registrar contrato.");
    }

    $conexion->commit();

    unset($_SESSION['old_input']);

    $_SESSION['exito'] =
        "Trabajador registrado correctamente.";

} catch (Exception $e) {

    $conexion->rollback();

    $_SESSION['errores'][] = $e->getMessage();
}

header("Location: vistas/registro_trabajador.php");
exit;