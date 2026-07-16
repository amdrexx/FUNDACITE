<?php
// ======================================================
// ARCHIVO: controladores/ctrl_salario.php
// ======================================================

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
    }

    if (!is_numeric($monto) || $monto <= 0) {
        $errores[] = "El monto debe ser mayor que cero.";
    }

    if (!empty($errores)) {

        $_SESSION['errores'] = $errores;

        $_SESSION['old'] = [
            'fecha' => $fecha,
            'monto' => $monto
        ];

        header("Location: ../vistas/registrar_salario.php");
        exit();
    }

    if ($modelo->registrarSalario($fecha, $monto)) {

        $_SESSION['exito'] = "Salario registrado correctamente.";

        unset($_SESSION['old']);

    } else {

        $_SESSION['errores'] = ["Ocurrió un error al registrar el salario."];

        $_SESSION['old'] = [
            'fecha' => $fecha,
            'monto' => $monto
        ];
    }

    header("Location: ../vistas/registrar_salario.php");
    exit();
}

// =====================================
// ELIMINAR
// =====================================
if (isset($_GET['eliminar'])) {

    $id = intval($_GET['eliminar']);

    $modelo->eliminarSalario($id);

    header("Location: ../vistas/lista_salario.php");
    exit();
}

// =====================================
// ACTUALIZAR
// =====================================
if (isset($_POST['accion']) && $_POST['accion'] == "actualizar") {

    $id = intval($_POST['id_salario']);
    $fecha = trim($_POST['fecha']);
    $monto = trim($_POST['monto']);

    if ($modelo->actualizarSalario($id, $fecha, $monto)) {

        $_SESSION['exito'] = "Salario actualizado correctamente.";

    } else {

        $_SESSION['errores'] = ["No fue posible actualizar el salario."];
    }

    header("Location: ../vistas/lista_salario.php");
    exit();
}
// LISTAR
// =====================================
function listarSalarios($conexion)
{
    $modelo = new clase_salario($conexion);
    return $modelo->listarSalarios();
}
?>