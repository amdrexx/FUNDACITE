<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once("../conexion.php");
require_once("../modelos/clase_salario.php");

$modelo = new clase_salario($conexion);

// =====================================
// GUARDAR
// =====================================
if (isset($_POST['accion']) && $_POST['accion'] == "guardar") {

    $fecha = trim($_POST['fecha'] ?? '');
    $monto = trim($_POST['monto'] ?? '');

    $errores = [];

    if (empty($fecha)) {
        $errores[] = "Debe seleccionar una fecha.";
    }

    if (empty($monto)) {
        $errores[] = "Debe ingresar un monto.";
    } elseif (!is_numeric($monto) || $monto <= 0) {
        $errores[] = "El monto debe ser mayor que cero.";
    }

    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        $_SESSION['old'] = ['fecha' => $fecha, 'monto' => $monto];
        header("Location: ../vistas/registrar_salario.php");
        exit();
    }

    if ($modelo->registrarSalario($fecha, $monto)) {
        $_SESSION['exito'] = "Salario registrado correctamente y marcado como Vigente.";
        unset($_SESSION['old']);
    } else {
        $_SESSION['errores'] = ["Ocurrió un error al registrar el salario."];
        $_SESSION['old'] = ['fecha' => $fecha, 'monto' => $monto];
    }

    header("Location: ../vistas/registrar_salario.php");
    exit();
}

// =====================================
// ACTUALIZAR
// =====================================
if (isset($_POST['accion']) && $_POST['accion'] == "actualizar") {

    $id    = intval($_POST['id_salario']);
    $fecha = trim($_POST['fecha']);
    $monto = trim($_POST['monto']);

    $errores = [];

    if (empty($fecha)) {
        $errores[] = "Debe seleccionar una fecha.";
    }
    if (empty($monto) || !is_numeric($monto) || $monto <= 0) {
        $errores[] = "El monto debe ser mayor que cero.";
    }

    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        header("Location: ../vistas/registrar_salario.php?editar=" . $id);
        exit();
    }

    // Se respeta el estado actual (Vigente/Deshabilitado) del registro que se edita
    $actual = $modelo->obtenerPorId($id);
    $estado = $actual ? $actual['estado'] : 'Deshabilitado';

    if ($modelo->actualizarSalario($id, $fecha, $monto, $estado)) {
        $_SESSION['exito'] = "Salario actualizado correctamente.";
    } else {
        $_SESSION['errores'] = ["No fue posible actualizar el salario."];
    }

    header("Location: ../vistas/registrar_salario.php");
    exit();
}

// =====================================
// ELIMINAR
// =====================================
if (isset($_GET['eliminar'])) {

    $id = intval($_GET['eliminar']);
    $resultado = $modelo->eliminarSalario($id);

    if ($resultado === "RESTRICT") {
        $_SESSION['errores'] = ["No se puede eliminar: este salario está referenciado en otro registro."];
    } elseif ($resultado === false) {
        $_SESSION['errores'] = ["Ocurrió un error al eliminar el salario."];
    } else {
        $_SESSION['exito'] = "Salario eliminado correctamente.";
    }

    header("Location: ../vistas/registrar_salario.php");
    exit();
}

// =====================================
// LISTAR / BUSCAR (funciones auxiliares usadas por la vista)
// =====================================
function listarSalarios($conexion) {
    $modelo = new clase_salario($conexion);
    return $modelo->listarSalarios();
}

function buscarSalario($conexion, $id) {
    $modelo = new clase_salario($conexion);
    return $modelo->obtenerPorId($id);
}
?>