<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vistas/includes/guardian.php';

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';
require_once __DIR__ . '/helpers/bitacora_helper.php';

$trabajador = new Trabajador($conexion);

// =====================================
// ELIMINAR LOGICAMENTE TRABAJADOR
// =====================================
if (isset($_POST['eliminar_trabajador'])) {

    $idTrabajador = intval($_POST['id_trabajador'] ?? 0);

    if ($idTrabajador <= 0) {
        $_SESSION['error_eliminacion'] = ["ID de trabajador no válido."];
        header("Location: ../vistas/lista_trabajadores.php");
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'trabajador', $idTrabajador);

    if ($trabajador->eliminarLogicamenteTrabajador($idTrabajador)) {
        registrarBitacora($conexion, 'Trabajadores', 'Eliminar', 'Eliminó lógicamente al trabajador "' . bitacoraNombre($antes, $idTrabajador) . '".');
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

    registrarBitacora($conexion, 'Trabajadores', 'Listar', $buscar !== '' ? "Consultó el listado de trabajadores (búsqueda: \"$buscar\")." : 'Consultó el listado de trabajadores.');

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
    $status       = trim($_POST['estatus_laboral'] ?? '');
    $idCargo      = ($_POST['id_cargo'] ?? '') === '' ? null : intval($_POST['id_cargo']);
    $hijos = filter_var($_POST['hijos'] ?? '', FILTER_VALIDATE_INT);
    $anosServicio = filter_var($_POST['anos_servicio_administracion_publica'] ?? '', FILTER_VALIDATE_INT);
    $nivelEducacion = trim($_POST['nivel_educacion'] ?? '');

    if ($status === 'Retirado' || $status === 'Suspendido') {
        $status = 'Inactivo';
    }

    $idDirActual    = intval($_POST['id_dir'] ?? 0);
    $codPar         = intval($_POST['cod_par'] ?? 0);
    $direccionTexto = trim($_POST['direccion_texto'] ?? '');

    $errores = [];

    if (empty($estadoCivil)) {
        $errores[] = "Seleccione el estado civil.";
    }

    if (empty($correo)) {
        $errores[] = "Ingrese el correo electrónico.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no tiene un formato válido.";
    }

    if (empty($telefono)) {
        $errores[] = "Ingrese el número de teléfono.";
    } elseif (!preg_match('/^\d{11}$/', $telefono)) {
        $errores[] = "El teléfono debe tener 11 dígitos.";
    }

    if ($hijos === false || $hijos < 0 || $hijos > 30) {
        $errores[] = "Ingrese una cantidad de hijos entre 0 y 30.";
    }

    if ($anosServicio === false || $anosServicio < 0 || $anosServicio > 70) {
        $errores[] = "Ingrese años de servicio entre 0 y 70.";
    }

    $trabajadorEditado = $trabajador->obtenerTrabajadorPorId($idTrabajador);
    if ($trabajadorEditado && $anosServicio !== false) {
        try {
            $nacimiento = new DateTime($trabajadorEditado['fecha']);
            $ingreso = new DateTime($trabajadorEditado['fecha_ingreso']);
            $edadAlIngreso = $nacimiento->diff($ingreso)->y;
            if ($ingreso < $nacimiento || $edadAlIngreso < 18 || $anosServicio > ($edadAlIngreso - 18)) {
                $errores[] = "Los años de servicio no pueden superar el tiempo transcurrido desde que el trabajador cumplió 18 años hasta su ingreso.";
            }
        } catch (Exception $e) {
            $errores[] = "Las fechas de nacimiento e ingreso registradas no son válidas.";
        }
    }

    if ($nivelEducacion === '') {
        $errores[] = "Seleccione el nivel de educación.";
    }

    if (empty($status)) {
        $errores[] = "Seleccione el estatus laboral.";
    }

    if (empty($codPar)) {
        $errores[] = "Seleccione la parroquia correspondiente a la dirección.";
    }

    if (empty($direccionTexto)) {
        $errores[] = "Ingrese la dirección exacta.";
    }

    if (!empty($errores)) {
        $_SESSION['error_edicion'] = $errores;
        header("Location: ../vistas/editar_trabajador.php?id=" . $idTrabajador);
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'trabajador', $idTrabajador);

    if ($idDirActual > 0) {
        $direccionOk = $trabajador->actualizarDireccion($idDirActual, $codPar, $direccionTexto);
        $idDir = $idDirActual;
    } else {
        $idDir = $trabajador->crearDireccion($codPar, $direccionTexto);
        $direccionOk = ($idDir !== false);
    }

    if (!$direccionOk) {
        $_SESSION['error_edicion'] = ["Error al actualizar la dirección."];
        header("Location: ../vistas/editar_trabajador.php?id=" . $idTrabajador);
        exit;
    }

    if ($trabajador->actualizarTrabajador(
        $idTrabajador,
        $estadoCivil,
        $telefono,
        $correo,
        $status,
        $hijos,
        $anosServicio,
        $nivelEducacion,
        $idDir,
        $idCargo
    )) {
        $despues = bitacoraSnapshot($conexion, 'trabajador', $idTrabajador);
        registrarBitacora($conexion, 'Trabajadores', 'Editar', 'Actualizó los datos del trabajador "' . bitacoraNombre($antes, $idTrabajador) . '": ' . bitacoraCambios($antes, $despues));
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

$tipoDoc         = trim($_POST['tipoDoc'] ?? '');
$cedula          = trim($_POST['cedula'] ?? '');
$nombres         = trim($_POST['nombres'] ?? '');
$apellidos       = trim($_POST['apellidos'] ?? '');
$fechaNacimiento = trim($_POST['fecha'] ?? '');
$genero          = trim($_POST['genero'] ?? '');
$estadoCivil     = trim($_POST['estadoCivil'] ?? '');
$correo          = trim($_POST['correoElectronico'] ?? '');
$telefono        = trim($_POST['numeroTelefono'] ?? '');
$fechaIngreso    = trim($_POST['fecha_ingreso'] ?? '');
$hijos = filter_var($_POST['hijos'] ?? '', FILTER_VALIDATE_INT);
$anosServicio = filter_var($_POST['anos_servicio_administracion_publica'] ?? '', FILTER_VALIDATE_INT);
$nivelEducacion = trim($_POST['nivel_educacion'] ?? '');
$status          = trim($_POST['estatus_laboral'] ?? '');
$idCargo         = intval($_POST['id_cargo'] ?? 0);

if ($status === 'Retirado' || $status === 'Suspendido') {
    $status = 'Inactivo';
}

$codEst          = intval($_POST['cod_est'] ?? 0);
$codMuni         = intval($_POST['cod_muni'] ?? 0);
$codPar          = intval($_POST['cod_par'] ?? 0);
$direccionTexto  = trim($_POST['direccion_texto'] ?? '');

if ($tipoDoc == 'V') {
    $tipoDocumento = 'Cédula';
} elseif ($tipoDoc == 'E') {
    $tipoDocumento = 'Cédula de Extranjería';
} else {
    $tipoDocumento = '';
}

if (empty($tipoDocumento))
    $errores[] = "Seleccione el tipo de documento.";

if (empty($cedula))
    $errores[] = "Ingrese la cédula.";
elseif (!preg_match('/^\d{6,8}$/', $cedula))
    $errores[] = "La cédula debe contener entre 6 y 8 dígitos.";

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
elseif (!preg_match('/^0\d{10}$/', $telefono))
    $errores[] = "El teléfono debe tener 11 dígitos y comenzar por 0.";

if (empty($correo))
    $errores[] = "Ingrese el correo.";
elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL))
    $errores[] = "El correo electrónico no tiene un formato válido.";

if (empty($fechaIngreso))
    $errores[] = "Ingrese la fecha de ingreso.";

if ($hijos === false || $hijos < 0 || $hijos > 30)
    $errores[] = "Ingrese una cantidad de hijos entre 0 y 30.";

if ($anosServicio === false || $anosServicio < 0 || $anosServicio > 70)
    $errores[] = "Ingrese años de servicio entre 0 y 70.";

if ($anosServicio !== false && $fechaNacimiento !== '' && $fechaIngreso !== '') {
    try {
        $nacimiento = new DateTime($fechaNacimiento);
        $ingreso = new DateTime($fechaIngreso);
        $edadAlIngreso = $nacimiento->diff($ingreso)->y;
        if ($ingreso < $nacimiento || $edadAlIngreso < 18 || $anosServicio > ($edadAlIngreso - 18)) {
            $errores[] = "Los años de servicio no pueden superar el tiempo transcurrido desde que el trabajador cumplió 18 años hasta su ingreso.";
        }
    } catch (Exception $e) {
        $errores[] = "Las fechas de nacimiento e ingreso no son válidas.";
    }
}

if ($fechaNacimiento !== '' && $fechaIngreso !== '') {
    try {
        $nacimiento = new DateTime($fechaNacimiento);
        $ingreso = new DateTime($fechaIngreso);
        $edadAlIngreso = $nacimiento->diff($ingreso)->y;
        if ($ingreso < $nacimiento || $edadAlIngreso < 18) {
            $errores[] = "El trabajador debe tener al menos 18 años en la fecha de ingreso.";
        }
    } catch (Exception $e) {
        $errores[] = "Las fechas de nacimiento e ingreso no son válidas.";
    }
}

if (empty($nivelEducacion))
    $errores[] = "Seleccione el nivel de educación.";

if (empty($status))
    $errores[] = "Seleccione el estatus laboral.";

if ($idCargo <= 0)
    $errores[] = "Seleccione el cargo a asignar.";

if (empty($codEst))
    $errores[] = "Seleccione el estado.";

if (empty($codMuni))
    $errores[] = "Seleccione el municipio.";

if (empty($codPar))
    $errores[] = "Seleccione la parroquia.";

if (empty($direccionTexto))
    $errores[] = "Ingrese la dirección exacta.";

if ($trabajador->existeCedula($cedula)) {
    $errores[] = "La cédula ya se encuentra registrada.";
}

if (!empty($errores)) {
    $_SESSION['error_registro'] = $errores;
    header("Location: ../vistas/registrar_trabajadores.php");
    exit;
}

try {

    $idDir = $trabajador->crearDireccion($codPar, $direccionTexto);

    if (!$idDir) {
        throw new Exception("Error al registrar la dirección.");
    }

    $idTrabajador = $trabajador->registrarTrabajador(
        $tipoDocumento,
        $cedula,
        $nombres,
        $apellidos,
        $fechaNacimiento,
        $fechaIngreso,
        $hijos,
        $anosServicio,
        $nivelEducacion,
        $genero,
        $estadoCivil,
        $telefono,
        $correo,
        $status,
        $idDir,
        $idCargo
    );

    if (!$idTrabajador) {
        throw new Exception("Error al registrar trabajador.");
    }

    unset($_SESSION['old_input']);

    registrarBitacora($conexion, 'Trabajadores', 'Crear', "Registró al trabajador $nombres $apellidos (CI $cedula).");

    $_SESSION['exito_registro'] = "Trabajador registrado correctamente.";
    header("Location: ../vistas/registrar_trabajadores.php");
    exit;

} catch (Exception $e) {

    $_SESSION['error_registro'] = [$e->getMessage()];
    header("Location: ../vistas/registrar_trabajadores.php");
    exit;
}
