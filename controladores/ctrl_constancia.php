<?php
session_start();

require_once '../conexion.php';
require_once '../modelos/clase_constancia.php';

$constanciaModelo = new clase_constancia($conexion);

// =====================================================
// 1. ELIMINAR CONSTANCIA
// =====================================================
if (isset($_POST['eliminar_constancia'])) {

    $id_constancia = (int) ($_POST['id_constancia'] ?? 0);

    if ($id_constancia <= 0) {
        $_SESSION['error_edicion'] = ['No se especificó una constancia válida.'];
        header('Location: ../vistas/lista_constancias.php');
        exit;
    }

    try {
        $ok = $constanciaModelo->eliminar($id_constancia);

        if ($ok) {
            $_SESSION['exito_edicion'] = 'Constancia eliminada correctamente.';
        } else {
            $_SESSION['error_edicion'] = ['No se pudo eliminar la constancia.'];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_edicion'] = ['Error de BD al eliminar: ' . $e->getMessage()];
    }

    header('Location: ../vistas/lista_constancias.php');
    exit;
}


// =====================================================
// 2. ACCIONES DEL FORMULARIO
// =====================================================
$accion = $_POST['accion'] ?? '';


// ---------- LIMPIAR ----------
if ($accion === 'limpiar') {
    unset($_SESSION['old_constancia']);
    header('Location: ../vistas/registrar_constancia.php');
    exit;
}


// ---------- GUARDAR ----------
if ($accion === 'guardar') {

    $id_trabajador     = (int) ($_POST['id_trabajador'] ?? 0);
    $nombre_director   = trim($_POST['nombre_director'] ?? '');
    $tipo_personal     = trim($_POST['tipo_personal'] ?? '');
    $fecha             = trim($_POST['fecha'] ?? '');
    $motivo            = trim($_POST['motivo'] ?? '');

    $TIPOS_PERSONAL_VALIDOS = ['Fijo', 'Contratado', 'Obrero', 'Empleado'];
    $errores = [];

    if ($id_trabajador <= 0) {
        $errores[] = 'Debes seleccionar un trabajador.';
    }
    if ($nombre_director === '') {
        $errores[] = 'El nombre del director es obligatorio.';
    }
    if (!in_array($tipo_personal, $TIPOS_PERSONAL_VALIDOS, true)) {
        $errores[] = 'Debes seleccionar un tipo de personal válido.';
    }
    if ($fecha === '') {
        $errores[] = 'La fecha de emisión es obligatoria.';
    }
    if ($motivo === '') {
        $errores[] = 'El motivo de la solicitud es obligatorio.';
    }

    if (!empty($errores)) {
        $_SESSION['errores_constancia'] = $errores;
        header('Location: ../vistas/registrar_constancia.php');
        exit;
    }

    $codigo_solicitud = 'SOL-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

    try {
        $resultado = $constanciaModelo->registrar(
            $id_trabajador,
            $codigo_solicitud,
            $motivo,
            $fecha,
            $nombre_director,
            $tipo_personal,
            $fecha
        );

        if ($resultado) {
            $_SESSION['exito_constancia'] = 'Constancia registrada correctamente.';
            unset($_SESSION['old_constancia']);
        } else {
            $_SESSION['errores_constancia'] = ['No se pudo registrar la constancia.'];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['errores_constancia'] = ['Error de BD al guardar: ' . $e->getMessage()];
    }

    header('Location: ../vistas/registrar_constancia.php');
    exit;
}


// ---------- ACCIÓN NO RECONOCIDA ----------
$_SESSION['errores_constancia'] = ['Acción no válida.'];
header('Location: ../vistas/registrar_constancia.php');
exit;
