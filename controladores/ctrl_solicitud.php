<?php

session_start();

require_once '../conexion.php';
require_once '../modelos/clase_solicitud.php';

$solicitudModelo = new Solicitud($conexion);

// Tipos permitidos (deben coincidir EXACTO con el CHECK de la tabla SOLICITUD)
const TIPOS_SOLICITUD_VALIDOS = [
    'Vacaciones',
    'Permiso',
    'Constancia de trabajo',
    'Reposo',
    'Otro',
];


// =====================================================
// 1. ELIMINAR SOLICITUD (viene de lista_solicitudes.php)
// =====================================================
if (isset($_POST['eliminar_solicitud'])) {

    $id_solicitud = (int) ($_POST['id_solicitud'] ?? 0);

    if ($id_solicitud <= 0) {
        $_SESSION['error_edicion'] = ['No se especificó una solicitud válida para eliminar.'];
        header('Location: ../vistas/lista_solicitud.php');
        exit;
    }

    try {
        $ok = $solicitudModelo->eliminar($id_solicitud);

        if ($ok) {
            $_SESSION['exito_edicion'] = 'Solicitud eliminada correctamente.';
        } else {
            $_SESSION['error_edicion'] = ['No se pudo eliminar la solicitud.'];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_edicion'] = ['Error de base de datos al eliminar: ' . $e->getMessage()];
    }

    header('Location: ../vistas/lista_solicitud.php');
    exit;
}


// =====================================================
// 2. ACCIONES DEL FORMULARIO (registrar_solicitud.php)
//    accion = consultar | guardar | limpiar
// =====================================================
$accion = $_POST['accion'] ?? '';


// ---------- LIMPIAR ----------
if ($accion === 'limpiar') {

    unset($_SESSION['old']);
    header('Location: ../vistas/registrar_solicitud.php');
    exit;
}


// ---------- CONSULTAR (buscar trabajador por cédula) ----------
if ($accion === 'consultar') {

    $cedula = trim($_POST['cedula'] ?? '');

    if ($cedula === '') {
        $_SESSION['errores'] = ['Debes indicar una cédula para consultar.'];
        header('Location: ../vistas/registrar_solicitud.php');
        exit;
    }

    try {
        $trabajador = $solicitudModelo->obtenerTrabajadorPorCedula($cedula);
    } catch (mysqli_sql_exception $e) {
        $_SESSION['errores'] = ['Error de base de datos al consultar: ' . $e->getMessage()];
        header('Location: ../vistas/registrar_solicitud.php');
        exit;
    }

    if (!$trabajador) {
        $_SESSION['errores'] = ['No se encontró ningún trabajador con esa cédula.'];
        unset($_SESSION['old']);
        header('Location: ../vistas/registrar_solicitud.php');
        exit;
    }

    // Se guarda en sesión para precargar el formulario
    $_SESSION['old'] = $trabajador;
    header('Location: ../vistas/registrar_solicitud.php');
    exit;
}


// ---------- GUARDAR ----------
if ($accion === 'guardar') {

    $cedula             = trim($_POST['cedula'] ?? '');
    $motivo_solicitud   = trim($_POST['motivo_solicitud'] ?? '');
    $tipo_solicitud     = trim($_POST['tipo_solicitud'] ?? '');
    $fecha_inicio       = trim($_POST['fecha_inicio'] ?? '');
    $fecha_finalizacion = trim($_POST['fecha_finalizacion'] ?? '');

    $errores = [];

    if ($cedula === '') {
        $errores[] = 'La cédula es obligatoria.';
    }

    if ($motivo_solicitud === '') {
        $errores[] = 'El motivo de la solicitud es obligatorio.';
    }

    if ($fecha_inicio === '') {
        $errores[] = 'La fecha de inicio es obligatoria.';
    }

    if (!in_array($tipo_solicitud, TIPOS_SOLICITUD_VALIDOS, true)) {
        $errores[] = 'Debes seleccionar un tipo de solicitud válido.';
    }

    if (
        $fecha_inicio !== ''
        && $fecha_finalizacion !== ''
        && $fecha_finalizacion < $fecha_inicio
    ) {
        $errores[] = 'La fecha de finalización no puede ser anterior a la fecha de inicio.';
    }

    // Buscar al trabajador para obtener su id_trabajador (la tabla SOLICITUD
    // no guarda la cédula directamente, solo la FK id_trabajador)
    $trabajador = null;

    if ($cedula !== '') {
        try {
            $trabajador = $solicitudModelo->obtenerTrabajadorPorCedula($cedula);
        } catch (mysqli_sql_exception $e) {
            $errores[] = 'Error de base de datos al validar la cédula: ' . $e->getMessage();
        }

        if (!$trabajador) {
            $errores[] = 'No se encontró ningún trabajador con esa cédula.';
        }
    }

    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        // Reintenta mantener los datos ya escritos, si el trabajador existe
        if ($trabajador) {
            $_SESSION['old'] = $trabajador;
        }
        header('Location: ../vistas/registrar_solicitud.php');
        exit;
    }

    // Código de solicitud autogenerado: SOL-YYYYMMDD-XXXX
    $codigo_solicitud = 'SOL-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

    // fecha_finalizacion es nullable en la tabla
    $fecha_finalizacion_valor = $fecha_finalizacion !== '' ? $fecha_finalizacion : null;

    try {
        $id_solicitud = $solicitudModelo->registrar(
            $trabajador['id_trabajador'],
            $codigo_solicitud,
            $tipo_solicitud,
            $motivo_solicitud,
            $fecha_inicio,
            $fecha_finalizacion_valor
        );

        if ($id_solicitud) {
            $_SESSION['exito'] = 'Solicitud registrada correctamente con el código ' . $codigo_solicitud . '.';
            unset($_SESSION['old']);
        } else {
            $_SESSION['errores'] = ['No se pudo registrar la solicitud.'];
            $_SESSION['old'] = $trabajador;
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['errores'] = ['Error de base de datos al guardar: ' . $e->getMessage()];
        $_SESSION['old'] = $trabajador;
    }

    header('Location: ../vistas/registrar_solicitud.php');
    exit;
}


// ---------- ACCIÓN NO RECONOCIDA ----------
$_SESSION['errores'] = ['Acción no válida.'];
header('Location: ../vistas/registrar_solicitud.php');
exit;