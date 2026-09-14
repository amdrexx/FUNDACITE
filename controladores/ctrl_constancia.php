<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../conexion.php';
require_once '../modelos/clase_solicitud.php';

$constanciaModelo = new Solicitud($conexion);

// Alias esperado por otras vistas (p.ej. ver_constancia.php) que llaman
// a $controladorConstancia->buscarPorId(...)
$controladorConstancia = $constanciaModelo;

// =====================================================
// Las acciones de abajo (eliminar / guardar / limpiar) son parte del
// flujo de FORMULARIO y solo deben ejecutarse en peticiones POST.
// Si este archivo se incluye desde una vista en GET (como ver_constancia.php),
// no debe disparar ninguna redirección ni el mensaje "Acción no válida".
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

            // Mensaje de éxito para mostrar en la lista de constancias
            $_SESSION['exito_edicion'] = 'Constancia registrada correctamente.';

            unset($_SESSION['old_constancia']);

            // Redirige a la lista de constancias
            header('Location: ../vistas/lista_constancias.php');
            exit;

        } else {
            $_SESSION['errores_constancia'] = [
                'No se pudo registrar la constancia.' . ($constanciaModelo->ultimoError ? ' Detalle: ' . $constanciaModelo->ultimoError : '')
            ];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['errores_constancia'] = ['Error de BD al guardar: ' . $e->getMessage()];
    }

    header('Location: ../vistas/registrar_constancia.php');
    exit;
}


// ---------- ACTUALIZAR ----------
if ($accion === 'actualizar') {

    $id_constancia     = (int) ($_POST['id_constancia'] ?? 0);
    $id_trabajador     = (int) ($_POST['id_trabajador'] ?? 0);
    $nombre_director   = trim($_POST['nombre_director'] ?? '');
    $tipo_personal     = trim($_POST['tipo_personal'] ?? '');
    $fecha             = trim($_POST['fecha'] ?? '');
    $motivo            = trim($_POST['motivo'] ?? '');

    $TIPOS_PERSONAL_VALIDOS = ['Fijo', 'Contratado', 'Obrero', 'Empleado'];
    $errores = [];

    if ($id_constancia <= 0) {
        $errores[] = 'No se especificó una constancia válida.';
    }
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
        header('Location: ../vistas/editar_constancia.php?id=' . $id_constancia);
        exit;
    }

    try {
        $ok = $constanciaModelo->actualizar(
            $id_constancia,
            $id_trabajador,
            $motivo,
            $fecha,
            $nombre_director,
            $tipo_personal,
            $fecha
        );

        if ($ok) {
            $_SESSION['exito_edicion'] = 'Constancia actualizada correctamente.';
            header('Location: ../vistas/lista_constancias.php');
            exit;
        } else {
            $_SESSION['errores_constancia'] = [
                'No se pudo actualizar la constancia.' . ($constanciaModelo->ultimoError ? ' Detalle: ' . $constanciaModelo->ultimoError : '')
            ];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['errores_constancia'] = ['Error de BD al actualizar: ' . $e->getMessage()];
    }

    header('Location: ../vistas/editar_constancia.php?id=' . $id_constancia);
    exit;
}


// ---------- ACCIÓN NO RECONOCIDA ----------
$_SESSION['errores_constancia'] = ['Acción no válida.'];
header('Location: ../vistas/registrar_constancia.php');
exit;

} // fin if ($_SERVER['REQUEST_METHOD'] === 'POST')