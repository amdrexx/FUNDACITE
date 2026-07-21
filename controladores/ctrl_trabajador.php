<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vistas/includes/permissions.php';
require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';

$trabajador = new Trabajador($conexion);

// =====================================
// ELIMINAR LOGICAMENTE TRABAJADOR
// =====================================
if (isset($_POST['eliminar_trabajador'])) {

    if (!esAdministradorODirector()) {
        $_SESSION['error_eliminacion'] = ["No tienes permisos para eliminar trabajadores."];
        header("Location: ../vistas/lista_trabajadores.php");
        exit;
    }

    $idTrabajador = intval($_POST['id_trabajador'] ?? 0);

    if ($idTrabajador <= 0) {
        $_SESSION['error_eliminacion'] = ["ID de trabajador no válido."];
        header("Location: ../vistas/lista_trabajadores.php");
        exit;
    }

    if ($trabajador->eliminarLogicamenteTrabajador($idTrabajador)) {
        $_SESSION['exito_eliminacion'] = "Trabajador eliminado lógicamente correctamente.";
        header("Location: ../vistas/lista_trabajadores.php");
        exit;
    }

    $_SESSION['error_eliminacion'] = ["Error al eliminar lógicamente el trabajador."];
    header("Location: ../vistas/lista_trabajadores.php");
    exit;
}

// =====================================
// LISTAR TRABAJADORES
// =====================================
if (isset($_GET['accion']) && $_GET['accion'] === 'listar') {
    $buscar = trim($_GET['buscar'] ?? '');
    $trabajadores = $trabajador->listarTrabajadores($buscar);

    require_once '../vistas/lista_trabajadores.php';
    exit;
}

// =====================================
// ACTUALIZAR TRABAJADOR
// =====================================
if (isset($_POST['editar_trabajador'])) {

    $idTrabajador = intval($_POST['id_trabajador'] ?? 0);
    $estadoCivil  = trim($_POST['estadoCivil'] ?? '');
    $correo       = trim($_POST['correoElectronico'] ?? '');
    $telefono     = trim($_POST['numeroTelefono'] ?? '');
    $idCargo      = intval($_POST['cargo_id'] ?? 0);
    $status       = trim($_POST['estatus_laboral'] ?? '');

    $errores = [];

    if (empty($estadoCivil)) {
        $errores[] = "Seleccione el estado civil.";
    }

    if (empty($correo)) {
        $errores[] = "Ingrese el correo electrónico.";
    }

    if (empty($telefono)) {
        $errores[] = "Ingrese el número de teléfono.";
    }

    if (empty($idCargo)) {
        $errores[] = "Seleccione un cargo.";
    }

    if (empty($status)) {
        $errores[] = "Seleccione el estatus laboral.";
    }

    if (!empty($errores)) {
        $_SESSION['error_edicion'] = $errores;
        header("Location: ../vistas/editar_trabajador.php?id=" . $idTrabajador);
        exit;
    }

    if ($trabajador->actualizarTrabajador(
        $idTrabajador,
        $estadoCivil,
        $telefono,
        $correo,
        $status,
        $idCargo
    )) {
        $_SESSION['exito_edicion'] = "Trabajador actualizado correctamente.";
        header("Location: ../vistas/lista_trabajadores.php");
        exit;
    }

    $_SESSION['error_edicion'] = ["Error al actualizar el trabajador."];
    header("Location: ../vistas/editar_trabajador.php?id=" . $idTrabajador);
    exit;
}

// =====================================
// REGISTRAR TRABAJADOR
// =====================================
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

if ($trabajador->existeCedula($cedula)) {
    $errores[] = "La cédula ya se encuentra registrada.";
}

if (!empty($errores)) {
    $_SESSION['error_registro'] = $errores;
    header("Location: ../vistas/registrar_trabajadores.php");
    exit;
}

try {

    $idTrabajador = $trabajador->registrarTrabajador(
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
        $idCargo
    );

    if (!$idTrabajador) {
        throw new Exception("Error al registrar trabajador.");
    }

    unset($_SESSION['old_input']);

    $_SESSION['exito_registro'] = "Trabajador registrado correctamente.";
    header("Location: ../vistas/registrar_trabajadores.php");
    exit;

} catch (Exception $e) {

    $_SESSION['error_registro'] = [$e->getMessage()];
    header("Location: ../vistas/registrar_trabajadores.php");
    exit;
}