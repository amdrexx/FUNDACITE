<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// La búsqueda por cédula responde JSON: sin sesión debe devolver 401, no una redirección.
if (($_GET['action'] ?? '') === 'buscar_trabajador' && !defined('GUARDIAN_JSON')) {
    define('GUARDIAN_JSON', true);
}
require_once __DIR__ . '/../vistas/includes/guardian.php';
requireAdministradorODirector(); // misma restricción que vistas/asignar_cargo.php

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../modelos/clase_asignar_cargo.php";
require_once __DIR__ . "/helpers/bitacora_helper.php";

$controladorAsignarCargo = new clase_asignar_cargo($conexion);

// BÚSQUEDA VÍA AJAX POR CÉDULA (SI AÚN SE USA EN OTRAS VISTAS)
if (isset($_GET['action']) && $_GET['action'] === 'buscar_trabajador') {
    header('Content-Type: application/json');
    $cedula = filter_input(INPUT_GET, 'cedula', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    
    if (empty($cedula)) {
        echo json_encode(['success' => false, 'message' => 'Por favor ingrese una cédula.']);
        exit;
    }

    $trabajador = $controladorAsignarCargo->buscarTrabajadorPorCedula($cedula);

    registrarBitacora($conexion, 'Asignación de Cargos', 'Consultar', "Buscó al trabajador con cédula \"$cedula\".");

    if ($trabajador) {
        echo json_encode([
            'success' => true,
            'id_trabajador' => $trabajador['id_trabajador'],
            'nombre' => $trabajador['nombres'] . ' ' . $trabajador['apellidos'],
            'id_cargo_actual' => $trabajador['id_cargo'],
            'cargo_actual' => $trabajador['nombre_cargo'] ?? 'Sin cargo asignado'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Trabajador no encontrado o inactivo.']);
    }
    exit;
}

// PROCESAR ASIGNACIÓN DE CARGO DESDE EL FORMULARIO DE ESTE MISMO MÓDULO.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_cargo'])) {
    $tokenSesion = $_SESSION['csrf_asignacion_cargo'] ?? '';
    $tokenFormulario = $_POST['csrf_token'] ?? '';

    if ($tokenSesion === '' || $tokenFormulario === '' || !hash_equals($tokenSesion, $tokenFormulario)) {
        $_SESSION['error_asignacion'] = ['La solicitud de asignación no es válida o ha expirado. Actualice la página e inténtelo nuevamente.'];
        header('Location: ../vistas/asignar_cargo.php?status=error');
        exit;
    }

    $id_trabajador = filter_input(INPUT_POST, 'id_trabajador', FILTER_VALIDATE_INT);
    $id_cargo = filter_input(INPUT_POST, 'id_cargo', FILTER_VALIDATE_INT);
    $gaceta = trim((string) ($_POST['gaceta'] ?? ''));
    $gaceta = preg_replace('/[\x00-\x1F\x7F]/u', '', $gaceta) ?? '';

    if (empty($id_trabajador)) {
        $_SESSION['error_asignacion'] = ["Debe seleccionar un trabajador válido."];
        header("Location: ../vistas/asignar_cargo.php?status=error");
        exit;
    }

    if (empty($id_cargo)) {
        $_SESSION['error_asignacion'] = ["Debe seleccionar un cargo válido a asignar."];
        header("Location: ../vistas/asignar_cargo.php?status=error");
        exit;
    }

    if ($gaceta === '' || (preg_match_all('/./us', $gaceta, $coincidenciasGaceta) ?: 0) > 150) {
        $_SESSION['error_asignacion'] = ['Debe indicar la Gaceta utilizada para asignar el cargo (máximo 150 caracteres).'];
        header('Location: ../vistas/asignar_cargo.php?status=error');
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'trabajador', $id_trabajador);

    $resultado = $controladorAsignarCargo->asignarCargo(
        $id_trabajador,
        $id_cargo,
        $gaceta,
        isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null
    );

    if ($resultado) {
        $cargoAnterior = !empty($antes['Cargo']) ? '"' . $antes['Cargo'] . '"' : 'sin cargo';
        registrarBitacora($conexion, 'Asignación de Cargos', 'Editar', 'Asignó el cargo "' . bitacoraNombreDe($conexion, 'cargo', $id_cargo) . '" al trabajador "' . bitacoraNombre($antes, $id_trabajador) . '" con Gaceta "' . $gaceta . '" (cargo anterior: ' . $cargoAnterior . ').');
        $_SESSION['exito_asignacion'] = "¡Cargo asignado correctamente!";
        header("Location: ../vistas/asignar_cargo.php?status=success");
    } else {
        $_SESSION['error_asignacion'] = [$controladorAsignarCargo->ultimoError ?: 'Error al intentar asignar el cargo.'];
        header("Location: ../vistas/asignar_cargo.php?status=error");
    }
    exit;
}

// DESVINCULACIÓN MANUAL DE CARGOS ORDINARIOS DESDE ESTE MÓDULO.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'desvincular_cargo') {
    $tokenSesion = $_SESSION['csrf_asignacion_cargo'] ?? '';
    $tokenFormulario = $_POST['csrf_token'] ?? '';
    $id_trabajador = filter_input(INPUT_POST, 'id_trabajador', FILTER_VALIDATE_INT);

    if ($tokenSesion === '' || $tokenFormulario === '' || !hash_equals($tokenSesion, $tokenFormulario)) {
        $_SESSION['error_asignacion'] = ['La solicitud de desvinculación no es válida o ha expirado. Actualice la página e inténtelo nuevamente.'];
        header('Location: ../vistas/asignar_cargo.php?status=error');
        exit;
    }

    if (!$id_trabajador) {
        $_SESSION['error_asignacion'] = ['Debe seleccionar un trabajador válido.'];
        header('Location: ../vistas/asignar_cargo.php?status=error');
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'trabajador', $id_trabajador);
    $resultado = $controladorAsignarCargo->desvincularCargoManualmente(
        $id_trabajador,
        isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null
    );

    if ($resultado) {
        registrarBitacora(
            $conexion,
            'Asignación de Cargos',
            'Desvincular cargo',
            'Desvinculó manualmente el cargo "' . ($antes['Cargo'] ?? 'desconocido')
            . '" del trabajador "' . bitacoraNombre($antes, $id_trabajador)
            . '". Se conservó su estatus laboral.'
        );
        $_SESSION['exito_asignacion'] = 'El cargo fue desvinculado correctamente y quedó guardado en el historial.';
        header('Location: ../vistas/asignar_cargo.php?status=unlinked');
    } else {
        $_SESSION['error_asignacion'] = [$controladorAsignarCargo->ultimoError ?: 'No se pudo desvincular el cargo.'];
        header('Location: ../vistas/asignar_cargo.php?status=error');
    }
    exit;
}
?>