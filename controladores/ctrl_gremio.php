<?php
// ARCHIVO: /FUNDACITE/controladores/ctrl_gremio.php

if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../vistas/includes/guardian.php';
requireAdministradorODirector();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_gremio.php';
require_once __DIR__ . '/helpers/bitacora_helper.php';

$modelo = new Gremio($conexion);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/lista_gremio.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'cambiar_estado') {
    $id = (int) ($_POST['id_gremio'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');

    if ($id <= 0 || !in_array($estado, ['Activo', 'Inactivo'], true)) {
        $_SESSION['flash_alerta'] = ['tipo' => 'warning', 'mensaje' => 'Los datos enviados para cambiar el estado no son válidos.'];
        header('Location: ../vistas/lista_gremio.php');
        exit;
    }

    if (!$modelo->obtenerPorId($id)) {
        $_SESSION['flash_alerta'] = ['tipo' => 'warning', 'mensaje' => 'El gremio indicado no existe.'];
        header('Location: ../vistas/lista_gremio.php');
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'gremio', $id);
    if ($modelo->actualizarEstado($id, $estado)) {
        registrarBitacora($conexion, 'Gremios', 'Cambiar estado', 'Cambió el estado del gremio "' . bitacoraNombre($antes, $id) . '" a ' . $estado . '.');
        $_SESSION['flash_alerta'] = ['tipo' => 'success', 'mensaje' => 'Estado del gremio actualizado correctamente.'];
    } else {
        $_SESSION['flash_alerta'] = ['tipo' => 'error', 'mensaje' => 'No se pudo actualizar el estado del gremio.'];
    }
    header('Location: ../vistas/lista_gremio.php');
    exit;
}

$idGremio = (int) ($_POST['id_gremio'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$estado = trim($_POST['estado'] ?? 'Activo');
$errores = [];

if ($nombre === '') {
    $errores[] = 'Ingrese el nombre del gremio.';
} elseif (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 150) {
    $errores[] = 'El nombre del gremio debe tener entre 3 y 150 caracteres.';
}
if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
    $errores[] = 'Seleccione un estado válido para el gremio.';
}
if ($nombre !== '' && $modelo->existeNombre($nombre, $idGremio)) {
    $errores[] = 'Ya existe un gremio registrado con ese nombre.';
}

if (!empty($errores)) {
    $_SESSION['old_input'] = $_POST;
    $_SESSION[$idGremio > 0 ? 'error_edicion' : 'error_registro'] = $errores;
    header('Location: ../vistas/registrar_gremio.php' . ($idGremio > 0 ? '?id=' . $idGremio : ''));
    exit;
}

if ($idGremio > 0) {
    if (!$modelo->obtenerPorId($idGremio)) {
        $_SESSION['flash_alerta'] = ['tipo' => 'warning', 'mensaje' => 'El gremio indicado no existe.'];
        header('Location: ../vistas/lista_gremio.php');
        exit;
    }
    $antes = bitacoraSnapshot($conexion, 'gremio', $idGremio);
    if ($modelo->actualizar($idGremio, $nombre, $estado)) {
        $despues = bitacoraSnapshot($conexion, 'gremio', $idGremio);
        registrarBitacora($conexion, 'Gremios', 'Editar', 'Actualizó el gremio "' . bitacoraNombre($antes, $idGremio) . '": ' . bitacoraCambios($antes, $despues));
        $_SESSION['flash_alerta'] = ['tipo' => 'success', 'mensaje' => 'Gremio actualizado correctamente.'];
        unset($_SESSION['old_input']);
        header('Location: ../vistas/lista_gremio.php');
    } else {
        $_SESSION['error_edicion'] = ['No se pudo actualizar el gremio.'];
        header('Location: ../vistas/registrar_gremio.php?id=' . $idGremio);
    }
    exit;
}

$nuevoId = $modelo->registrar($nombre, $estado);
if ($nuevoId) {
    unset($_SESSION['old_input']);
    registrarBitacora($conexion, 'Gremios', 'Crear', "Registró el gremio \"$nombre\" con estado $estado.");
    $_SESSION['flash_alerta'] = ['tipo' => 'success', 'mensaje' => 'Gremio registrado correctamente.'];
    header('Location: ../vistas/lista_gremio.php');
} else {
    $_SESSION['error_registro'] = ['No se pudo registrar el gremio.'];
    header('Location: ../vistas/registrar_gremio.php');
}
exit;
