<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../vistas/includes/guardian.php';
require_once("../conexion.php");
require_once("../modelos/clase_salario.php");
require_once __DIR__ . "/helpers/bitacora_helper.php";

$modelo = new clase_salario($conexion);

// =====================================
// AUXILIARES
// =====================================

/**
 * Indica si ya existe un salario registrado con la misma fecha
 * (sin importar el tipo de salario ni el cargo).
 * $excluirId sirve al editar, para no compararse contra el propio registro.
 */
function existeSalarioConFecha($conexion, $fecha, $excluirId = 0) {
    $stmt = $conexion->prepare(
        "SELECT COUNT(*) FROM SALARIO WHERE fecha = ? AND id_salario <> ?"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('si', $fecha, $excluirId);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    return $total > 0;
}

/** Acepta coma o punto como separador y devuelve el monto con punto. */
function normalizarMonto($monto) {
    return str_replace(',', '.', trim($monto));
}

// =====================================
// GUARDAR
// =====================================
if (isset($_POST['accion']) && $_POST['accion'] == "guardar") {

    $tipo_salario       = trim($_POST['tipo_salario'] ?? '');
    $id_cargo           = trim($_POST['id_cargo'] ?? '');
    $fecha              = trim($_POST['fecha'] ?? '');
    $monto              = normalizarMonto($_POST['monto'] ?? '');

    $errores = [];

    if (!in_array($tipo_salario, ['base', 'cargo'], true)) {
        $errores[] = "Debe seleccionar un tipo de salario válido.";
    }

    if ($tipo_salario === 'cargo' && ($id_cargo === '' || !ctype_digit($id_cargo))) {
        $errores[] = "Debe seleccionar un cargo.";
    }

    if (empty($fecha)) {
        $errores[] = "Debe seleccionar una fecha.";
    }

    if ($monto === '') {
        $errores[] = "Debe ingresar un monto.";
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $monto) || (float) $monto <= 0) {
        $errores[] = "El monto debe ser un número válido mayor que cero (máximo 2 decimales).";
    }

    $id_cargo_final = ($tipo_salario === 'cargo' && ctype_digit($id_cargo)) ? intval($id_cargo) : null;

    // No permitir dos salarios con la misma fecha
    if (empty($errores) && existeSalarioConFecha($conexion, $fecha)) {
        $errores[] = "Ya existe un salario registrado con esa fecha. No se permiten dos salarios con la misma fecha.";
    }

    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        $_SESSION['old'] = [
            'fecha'              => $fecha,
            'monto'              => $monto,
            'tipo_salario'       => $tipo_salario,
            'id_cargo'           => $id_cargo,
        ];
        header("Location: ../vistas/registrar_salario.php");
        exit();
    }

    if ($modelo->registrarSalario($fecha, $monto, $tipo_salario, $id_cargo_final)) {
        $detalleCargo = $id_cargo_final !== null ? ' del cargo "' . bitacoraNombreDe($conexion, 'cargo', $id_cargo_final) . '"' : '';
        registrarBitacora($conexion, 'Salarios', 'Crear', "Registró un salario de tipo \"$tipo_salario\"$detalleCargo por $monto (fecha $fecha).");
        $_SESSION['exito'] = "Salario registrado correctamente y marcado como Vigente.";
        unset($_SESSION['old']);
    } else {
        $_SESSION['errores'] = ["Ocurrió un error al registrar el salario."];
        $_SESSION['old'] = [
            'fecha'              => $fecha,
            'monto'              => $monto,
            'tipo_salario'       => $tipo_salario,
            'id_cargo'           => $id_cargo,
        ];
    }

    header("Location: ../vistas/registrar_salario.php");
    exit();
}

// =====================================
// ACTUALIZAR
// =====================================
if (isset($_POST['accion']) && $_POST['accion'] == "actualizar") {

    $id                 = intval($_POST['id_salario'] ?? 0);
    $tipo_salario       = trim($_POST['tipo_salario'] ?? '');
    $id_cargo           = trim($_POST['id_cargo'] ?? '');
    $fecha              = trim($_POST['fecha'] ?? '');
    $monto              = normalizarMonto($_POST['monto'] ?? '');

    $errores = [];

    if ($id <= 0) {
        $errores[] = "Registro de salario no válido.";
    }

    if (!in_array($tipo_salario, ['base', 'cargo'], true)) {
        $errores[] = "Debe seleccionar un tipo de salario válido.";
    }

    if ($tipo_salario === 'cargo' && ($id_cargo === '' || !ctype_digit($id_cargo))) {
        $errores[] = "Debe seleccionar un cargo.";
    }

    if (empty($fecha)) {
        $errores[] = "Debe seleccionar una fecha.";
    }

    if ($monto === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $monto) || (float) $monto <= 0) {
        $errores[] = "El monto debe ser un número válido mayor que cero (máximo 2 decimales).";
    }

    $id_cargo_final = ($tipo_salario === 'cargo' && ctype_digit($id_cargo)) ? intval($id_cargo) : null;

    // No permitir dos salarios con la misma fecha (excluyendo este mismo registro)
    if (empty($errores) && existeSalarioConFecha($conexion, $fecha, $id)) {
        $errores[] = "Ya existe un salario registrado con esa fecha. No se permiten dos salarios con la misma fecha.";
    }

    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        $_SESSION['old'] = [
            'fecha'              => $fecha,
            'monto'              => $monto,
            'tipo_salario'       => $tipo_salario,
            'id_cargo'           => $id_cargo,
        ];
        header("Location: ../vistas/editar_salario.php?editar=" . $id);
        exit();
    }

    // Se respeta el estado actual (Vigente/Deshabilitado) del registro que se edita
    $actual = $modelo->obtenerPorId($id);
    $estado = $actual ? $actual['estado'] : 'Deshabilitado';

    $antes = bitacoraSnapshot($conexion, 'salario', $id);

    if ($modelo->actualizarSalario($id, $fecha, $monto, $estado, $tipo_salario, $id_cargo_final)) {
        $despues = bitacoraSnapshot($conexion, 'salario', $id);
        registrarBitacora($conexion, 'Salarios', 'Editar', 'Actualizó el salario ' . bitacoraNombre($antes, $id) . ': ' . bitacoraCambios($antes, $despues));
        $_SESSION['exito'] = "Salario actualizado correctamente.";
    } else {
        $_SESSION['errores'] = ["No fue posible actualizar el salario."];
    }

    header("Location: ../vistas/lista_salario.php");
    exit();
}

// =====================================
// ELIMINAR
// =====================================
if (isset($_GET['eliminar'])) {

    $id = intval($_GET['eliminar']);
    $antes = bitacoraSnapshot($conexion, 'salario', $id);
    $resultado = $modelo->eliminarSalario($id);

    if ($resultado === "RESTRICT") {
        $_SESSION['errores'] = ["No se puede eliminar: este salario está referenciado en otro registro."];
    } elseif ($resultado === false) {
        $_SESSION['errores'] = ["Ocurrió un error al eliminar el salario."];
    } else {
        registrarBitacora($conexion, 'Salarios', 'Eliminar', 'Eliminó el salario ' . bitacoraNombre($antes, $id) . '.');
        $_SESSION['exito'] = "Salario eliminado correctamente.";
    }

    header("Location: ../vistas/lista_salario.php");
    exit();
}

// =====================================
// LISTAR / BUSCAR (funciones auxiliares usadas por la vista)
// =====================================
function listarSalarios($conexion) {
    $modelo = new clase_salario($conexion);
    registrarBitacora($conexion, 'Salarios', 'Listar', 'Consultó el listado de salarios.');
    return $modelo->listarSalarios();
}

function buscarSalario($conexion, $id) {
    $modelo = new clase_salario($conexion);
    $resultado = $modelo->obtenerPorId($id);
    registrarBitacora($conexion, 'Salarios', 'Consultar', "Consultó el salario ID $id.");
    return $resultado;
}
?>