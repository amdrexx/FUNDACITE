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

// PROCESAR ASIGNACIÓN DE CARGO (ACTUALIZA TRABAJADOR.id_cargo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_cargo'])) {
    $id_trabajador = filter_input(INPUT_POST, 'id_trabajador', FILTER_VALIDATE_INT);
    $id_cargo = filter_input(INPUT_POST, 'id_cargo', FILTER_VALIDATE_INT);

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

    $antes = bitacoraSnapshot($conexion, 'trabajador', $id_trabajador);

    $resultado = $controladorAsignarCargo->asignarCargo($id_trabajador, $id_cargo);

    if ($resultado) {
        $cargoAnterior = !empty($antes['Cargo']) ? '"' . $antes['Cargo'] . '"' : 'sin cargo';
        registrarBitacora($conexion, 'Asignación de Cargos', 'Editar', 'Asignó el cargo "' . bitacoraNombreDe($conexion, 'cargo', $id_cargo) . '" al trabajador "' . bitacoraNombre($antes, $id_trabajador) . '" (cargo anterior: ' . $cargoAnterior . ').');
        $_SESSION['exito_asignacion'] = "¡Cargo asignado correctamente!";
        header("Location: ../vistas/asignar_cargo.php?status=success");
    } else {
        $_SESSION['error_asignacion'] = ["Error al intentar asignar el cargo."];
        header("Location: ../vistas/asignar_cargo.php?status=error");
    }
    exit;
}

// DESVINCULAR CARGO (PONE TRABAJADOR.id_cargo EN NULL)
if (isset($_GET['action']) && $_GET['action'] === 'desvincular' && isset($_GET['id_trabajador'])) {
    $id_trabajador = filter_input(INPUT_GET, 'id_trabajador', FILTER_VALIDATE_INT);

    if ($id_trabajador) {
        $antes = bitacoraSnapshot($conexion, 'trabajador', $id_trabajador);
        $resultado = $controladorAsignarCargo->asignarCargo($id_trabajador, null);

        if ($resultado) {
            registrarBitacora($conexion, 'Asignación de Cargos', 'Eliminar', 'Desvinculó el cargo "' . ($antes['Cargo'] ?? 'desconocido') . '" del trabajador "' . bitacoraNombre($antes, $id_trabajador) . '".');
            header("Location: ../vistas/asignar_cargo.php?status=unlinked");
        } else {
            header("Location: ../vistas/asignar_cargo.php?status=error");
        }
    } else {
        header("Location: ../vistas/asignar_cargo.php?status=error");
    }
    exit;
}
?>