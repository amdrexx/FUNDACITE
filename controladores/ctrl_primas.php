<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../conexion.php';
require_once '../modelos/clase_primas.php';
require_once __DIR__ . '/helpers/bitacora_helper.php';

$prima = new Prima($conexion);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion == '' || $accion == 'listar') {
    $buscar = $_GET['buscar'] ?? '';
    $primas = $prima->mostrarPrimas($buscar);
    registrarBitacora($conexion, 'Primas', 'Listar', 'Consultó el listado de primas.');
    require_once '../vistas/registrar_prima.php';
    exit;
}

switch ($accion) {

    case 'guardar':
        $tipoprima  = trim($_POST['tipoprima'] ?? '');
        $porcentaje = trim($_POST['porcentaje'] ?? '');
        $fecha      = trim($_POST['fecha'] ?? '');
        $estado     = $_POST['estado'] ?? 'Activo';

        $errores = [];

        if (empty($tipoprima)) {
            $errores[] = "Seleccione tipo de prima";
        }
        if (empty($porcentaje)) {
            $errores[] = "Ingrese porcentaje";
        }
        if (empty($fecha)) {
            $errores[] = "Ingrese fecha";
        }

        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            header("Location: ../vistas/registrar_prima.php");
            exit;
        }

        if ($prima->existeTipoPrima($tipoprima)) {
            $_SESSION['errores'][] = "El tipo de prima ya está registrado.";
            header("Location: ../vistas/registrar_prima.php");
            exit;
        }

        if (!$prima->guardar($tipoprima, $porcentaje, $estado, $fecha)) {
            die("Error al guardar la prima: " . $conexion->error);
        }

        registrarBitacora($conexion, 'Primas', 'Crear', "Registró la prima \"$tipoprima\" ($porcentaje%).");
        $_SESSION['exito'] = "Prima registrada correctamente";
        header("Location: ../vistas/registrar_prima.php");
        exit;
    break;

    case 'actualizar':
        $id = $_POST['id_prima'] ?? 0;
        $porcentaje = $_POST['porcentaje'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if (!$prima->actualizar($id, $porcentaje, $estado)) {
            die("Error al actualizar la prima: " . $conexion->error);
        }

        registrarBitacora($conexion, 'Primas', 'Editar', "Actualizó la prima ID $id (porcentaje: $porcentaje%, estado: $estado).");
        $_SESSION['exito'] = "Prima actualizada correctamente";
        header("Location: ../vistas/registrar_prima.php");
        exit;
    break;

    case 'editar':
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            $_SESSION['errores'][] = "ID inválido";
            header("Location: ../vistas/registrar_prima.php");
            exit;
        }

        $registro = $prima->obtenerPorId($id);

        if (!$registro) {
            $_SESSION['errores'][] = "Registro no encontrado";
            header("Location: ../vistas/registrar_prima.php");
            exit;
        }

        registrarBitacora($conexion, 'Primas', 'Consultar', "Consultó la prima ID $id.");
        $_SESSION['prima_editar'] = $registro;
        header("Location: ../vistas/registrar_prima.php");
        exit;
    break;

    case 'eliminar':
        $id = $_POST['id_prima'] ?? 0;

        if (!$id) {
            $_SESSION['errores'][] = "ID inválido";
            header("Location: ../vistas/registrar_prima.php");
            exit;
        }

        if (!$prima->eliminar($id)) {
            die("Error al eliminar la prima: " . $conexion->error);
        }

        registrarBitacora($conexion, 'Primas', 'Eliminar', "Eliminó la prima ID $id.");
        $_SESSION['exito'] = "Eliminado correctamente";
        header("Location: ../vistas/registrar_prima.php");
        exit;
    break;
}