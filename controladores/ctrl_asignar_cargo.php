<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../modelos/clase_asignar_cargo.php";

$controladorAsignarCargo = new clase_asignar_cargo($conexion);

// BÚSQUEDA VÍA AJAX POR CÉDULA
if (isset($_GET['action']) && $_GET['action'] === 'buscar_trabajador') {
    header('Content-Type: application/json');
    $cedula = $_GET['cedula'] ?? '';
    
    if (empty($cedula)) {
        echo json_encode(['success' => false, 'message' => 'Por favor ingrese una cédula.']);
        exit;
    }

    $trabajador = $controladorAsignarCargo->buscarTrabajadorPorCedula($cedula);

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

// PROCESAR ASIGNACIÓN DE CARGO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_cargo'])) {
    $id_trabajador = $_POST['id_trabajador'] ?? 0;
    $id_cargo = $_POST['id_cargo'] ?? null;

    if (empty($id_trabajador)) {
        $_SESSION['error_asignacion'] = ["Debe seleccionar un trabajador válido."];
        header("Location: ../vistas/asignar_cargo.php?status=error");
        exit;
    }

    $resultado = $controladorAsignarCargo->asignarCargo($id_trabajador, $id_cargo);

    if ($resultado) {
        $_SESSION['exito_asignacion'] = "¡Cargo asignado correctamente!";
        header("Location: ../vistas/asignar_cargo.php?status=success");
    } else {
        $_SESSION['error_asignacion'] = ["Error al intentar asignar el cargo."];
        header("Location: ../vistas/asignar_cargo.php?status=error");
    }
    exit;
}

// DESVINCULAR CARGO (DESDE LA TABLA)
if (isset($_GET['action']) && $_GET['action'] === 'desvincular' && isset($_GET['id_trabajador'])) {
    $id_trabajador = intval($_GET['id_trabajador']);
    $resultado = $controladorAsignarCargo->asignarCargo($id_trabajador, null);

    if ($resultado) {
        header("Location: ../vistas/asignar_cargo.php?status=unlinked");
    } else {
        header("Location: ../vistas/asignar_cargo.php?status=error");
    }
    exit;
}
?>