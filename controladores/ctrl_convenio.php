<?php
// ARCHIVO: /FUNDACITE/controladores/ctrl_convenio.php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vistas/includes/guardian.php';
requireAdministradorODirector();

require_once '../conexion.php';
require_once '../modelos/clase_convenio.php';
require_once __DIR__ . '/helpers/bitacora_helper.php';

$convenio = new Convenio($conexion);

// =====================================
// VALIDACIÓN COMÚN (registro y edición)
// =====================================
function validarDatosConvenio(string $gremio, string $inicio, string $vencimiento, string $dias): array
{
    $errores = [];

    if ($gremio === '') {
        $errores[] = "Ingrese el nombre del gremio.";
    } elseif (mb_strlen($gremio) < 3 || mb_strlen($gremio) > 150) {
        $errores[] = "El nombre del gremio debe tener entre 3 y 150 caracteres.";
    }

    $fechaInicio = DateTime::createFromFormat('Y-m-d', $inicio);
    $fechaVenc   = DateTime::createFromFormat('Y-m-d', $vencimiento);
    $inicioOk = $fechaInicio && $fechaInicio->format('Y-m-d') === $inicio;
    $vencOk   = $fechaVenc && $fechaVenc->format('Y-m-d') === $vencimiento;

    if ($inicio === '')        $errores[] = "Ingrese la fecha de inicio.";
    elseif (!$inicioOk)        $errores[] = "La fecha de inicio no es válida.";

    if ($vencimiento === '')   $errores[] = "Ingrese la fecha de vencimiento.";
    elseif (!$vencOk)          $errores[] = "La fecha de vencimiento no es válida.";

    if ($inicioOk && $vencOk && $vencimiento < $inicio) {
        $errores[] = "La fecha de vencimiento no puede ser anterior a la fecha de inicio.";
    }

    if ($dias === '') {
        $errores[] = "Ingrese los días de disfrute.";
    } elseif (!ctype_digit($dias) || (int) $dias < 1 || (int) $dias > 255) {
        $errores[] = "Los días de disfrute deben ser un número entero entre 1 y 255.";
    }

    return $errores;
}

// =====================================
// ELIMINAR CONVENIO
// =====================================
if (isset($_POST['eliminar_convenio'])) {

    $idConvenio = intval($_POST['id_convenio'] ?? 0);

    if ($idConvenio <= 0) {
        $_SESSION['flash_alerta'] = ['tipo' => 'warning', 'mensaje' => 'ID de convenio no válido.'];
        header("Location: ../vistas/lista_convenio.php");
        exit;
    }

    if ($convenio->tieneSolicitudes($idConvenio)) {
        $_SESSION['flash_alerta'] = ['tipo' => 'warning', 'mensaje' => 'No se puede eliminar: el convenio está asociado a solicitudes de días de disfrute.'];
        header("Location: ../vistas/lista_convenio.php");
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'convenio', $idConvenio);

    if ($convenio->eliminarConvenio($idConvenio)) {
        registrarBitacora($conexion, 'Convenios', 'Eliminar', 'Eliminó el convenio "' . bitacoraNombre($antes, $idConvenio) . '".');
        $_SESSION['flash_alerta'] = ['tipo' => 'success', 'mensaje' => 'Convenio eliminado correctamente.'];
    } else {
        $_SESSION['flash_alerta'] = ['tipo' => 'warning', 'mensaje' => 'Error al eliminar el convenio.'];
    }

    header("Location: ../vistas/lista_convenio.php");
    exit;
}

// =====================================
// DETECTAR ACCIÓN (GUARDAR O ACTUALIZAR)
// =====================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/lista_convenio.php");
    exit;
}

$accion = $_POST['accion'] ?? 'guardar';

$idConvenio  = intval($_POST['id_convenio'] ?? 0);
$gremio      = trim($_POST['nombre_gremio'] ?? '');
$inicio      = trim($_POST['fecha_inicio'] ?? '');
$vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
$dias        = trim($_POST['dias_disfrute'] ?? '');

if ($accion === 'actualizar') {
    // =====================================
    // ACTUALIZAR CONVENIO
    // =====================================
    $errores = validarDatosConvenio($gremio, $inicio, $vencimiento, $dias);

    if ($idConvenio <= 0) {
        $errores[] = "ID de convenio no válido para actualizar.";
    }

    if (empty($errores) && $convenio->existeSolape($gremio, $inicio, $vencimiento, $idConvenio)) {
        $errores[] = "Ya existe un convenio de ese gremio cuyo periodo coincide con las fechas indicadas.";
    }

    if (!empty($errores)) {
        $_SESSION['error_edicion'] = $errores;
        header("Location: ../vistas/editar_convenio.php?id=" . $idConvenio);
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'convenio', $idConvenio);

    if ($convenio->actualizarConvenio($idConvenio, $gremio, $inicio, $vencimiento, $dias)) {
        $despues = bitacoraSnapshot($conexion, 'convenio', $idConvenio);
        registrarBitacora($conexion, 'Convenios', 'Editar', 'Actualizó el convenio "' . bitacoraNombre($antes, $idConvenio) . '": ' . bitacoraCambios($antes, $despues));
        $_SESSION['exito_edicion'] = "Convenio actualizado correctamente.";
        header("Location: ../vistas/lista_convenio.php");
    } else {
        $_SESSION['error_edicion'] = ["Error al actualizar el convenio."];
        header("Location: ../vistas/editar_convenio.php?id=" . $idConvenio);
    }
    exit;
}

// =====================================
// REGISTRAR CONVENIO
// =====================================
$_SESSION['old_input'] = $_POST;

$errores = validarDatosConvenio($gremio, $inicio, $vencimiento, $dias);

if (empty($errores) && $convenio->existeSolape($gremio, $inicio, $vencimiento)) {
    $errores[] = "Ya existe un convenio de ese gremio cuyo periodo coincide con las fechas indicadas.";
}

if (!empty($errores)) {
    $_SESSION['error_registro'] = $errores;
    header("Location: ../vistas/registrar_convenio.php");
    exit;
}

$nuevoId = $convenio->registrarConvenio($gremio, $inicio, $vencimiento, $dias);

if ($nuevoId) {
    unset($_SESSION['old_input']);
    registrarBitacora($conexion, 'Convenios', 'Crear', "Registró el convenio del gremio \"$gremio\" ($inicio al $vencimiento, $dias días de disfrute).");
    $_SESSION['exito_registro'] = "Convenio registrado correctamente.";
    header("Location: ../vistas/lista_convenio.php");
} else {
    $_SESSION['error_registro'] = ["Error al registrar el convenio."];
    header("Location: ../vistas/registrar_convenio.php");
}
exit;
