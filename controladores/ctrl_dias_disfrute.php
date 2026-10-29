<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../vistas/includes/guardian.php');
requireAdministradorODirector();

require_once(__DIR__ . '/../conexion.php');
require_once(__DIR__ . '/../modelos/clase_dias_disfrute.php');
require_once(__DIR__ . '/helpers/bitacora_helper.php');

$diasDisfruteModelo = new DiasDisfrute($conexion);


// =====================================================
// ELIMINAR DÍAS DE DISFRUTE (viene de lista_dias_disfrute.php)
// =====================================================
if (isset($_POST['eliminar_disfrute'])) {

    $id_dis = (int) ($_POST['id_dis'] ?? 0);

    if ($id_dis <= 0) {
        $_SESSION['error_edicion'] = ['No se especificó un registro válido para eliminar.'];
        header('Location: /FUNDACITE/vistas/lista_dias_disfrute.php');
        exit;
    }

    try {
        $antes = bitacoraSnapshot($conexion, 'dias_disfrute', $id_dis);
        $ok = $diasDisfruteModelo->eliminarDiasDisfrute($id_dis);

        if ($ok) {
            registrarBitacora($conexion, 'Días de Disfrute', 'Eliminar', 'Eliminó el registro de días de disfrute "' . bitacoraNombre($antes, $id_dis) . '".');
            $_SESSION['exito_edicion'] = 'Registro de días de disfrute eliminado correctamente.';
        } else {
            $_SESSION['error_edicion'] = ['No se pudo eliminar el registro de días de disfrute.'];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_edicion'] = ['Error de base de datos al eliminar: ' . $e->getMessage()];
    }

    header('Location: /FUNDACITE/vistas/lista_dias_disfrute.php');
    exit;
}

// =====================================================
// APROBAR / DESAPROBAR (viene de aprobacion_dias_disfrute.php)
// =====================================================
if (isset($_POST['cambiar_estado_disfrute'])) {

    $id_dis = (int) ($_POST['id_dis'] ?? 0);

    // Esta acción se usa desde dos pantallas (lista completa y catálogo de
    // aprobaciones); 'origen' indica a cuál volver. Se valida contra una
    // lista blanca para evitar un redirect abierto.
    $vistasPermitidas = ['lista_dias_disfrute.php', 'aprobacion_dias_disfrute.php'];
    $origen = $_POST['origen'] ?? 'lista_dias_disfrute.php';
    if (!in_array($origen, $vistasPermitidas, true)) {
        $origen = 'lista_dias_disfrute.php';
    }
    $redirigirA = '/FUNDACITE/vistas/' . $origen . (isset($_POST['query']) ? '?' . $_POST['query'] : '');

    if ($id_dis <= 0) {
        $_SESSION['error_edicion'] = ['No se especificó un registro válido.'];
        header('Location: ' . $redirigirA);
        exit;
    }

    $estadoActual = $diasDisfruteModelo->obtenerEstadoDiasDisfrute($id_dis);

    if ($estadoActual === null) {
        $_SESSION['error_edicion'] = ['No se encontró el registro de días de disfrute.'];
        header('Location: ' . $redirigirA);
        exit;
    }

    $nuevoEstado = $estadoActual === 'Aprobado' ? 'Desaprobado' : 'Aprobado';
    $resultado = $diasDisfruteModelo->cambiarEstadoDiasDisfrute($id_dis, $nuevoEstado);

    if ($resultado) {
        $nombreRegistro = bitacoraNombreDe($conexion, 'dias_disfrute', $id_dis);
        $accionBitacora = $nuevoEstado === 'Aprobado' ? 'Aprobar' : 'Desaprobar';
        $verboBitacora = $nuevoEstado === 'Aprobado' ? 'Aprobó' : 'Desaprobó';
        registrarBitacora($conexion, 'Días de Disfrute', $accionBitacora, $verboBitacora . ' el registro de ' . $nombreRegistro . '.');
        $_SESSION['exito_edicion'] = $nuevoEstado === 'Aprobado'
            ? 'Registro aprobado correctamente.'
            : 'Registro desaprobado correctamente.';
    } else {
        $_SESSION['error_edicion'] = ['No se pudo actualizar el estado del registro.'];
    }

    header('Location: ' . $redirigirA);
    exit;
}

/**
 * CONTROL DE ACCIONES
 */
if (isset($_POST['accion'])) {

    switch ($_POST['accion']) {

        case 'limpiar':
            unset($_SESSION['old']);
            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        case 'guardar':

            $id_trabajador    = (int) ($_POST['id_trabajador'] ?? 0);
            $nombre_completo  = trim($_POST['nombre_completo'] ?? '');
            $cargo_display    = trim($_POST['cargo_display'] ?? '');
            $fecha_inicio     = trim($_POST['fecha_inicio'] ?? '');
            $fecha_final      = trim($_POST['fecha_finalizacion'] ?? '');
            $descripcion      = trim($_POST['descripcion'] ?? '');
            $observaciones    = trim($_POST['observaciones'] ?? '');

            $errores = [];

            if ($id_trabajador <= 0) {
                $errores[] = 'Debe seleccionar un trabajador.';
            }
            if ($fecha_inicio === '') {
                $errores[] = 'La fecha de inicio es obligatoria.';
            }
            if ($descripcion === '') {
                $errores[] = 'La descripcion es obligatoria.';
            }
            if ($observaciones === '') {
                $errores[] = 'Las observaciones son obligatorias.';
            }
            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $_SESSION['old'] = [
                    'id_trabajador'   => $id_trabajador,
                    'nombre_completo' => $nombre_completo,
                    'cargo'           => $cargo_display,
                    'cedula'          => '',
                    'descripcion'     => $descripcion,
                    'observaciones'   => $observaciones,
                ];
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // Obtener datos del trabajador para validar tipo de contrato
            $trab = $diasDisfruteModelo->obtenerContratoTrabajador($id_trabajador);

            if (!$trab) {
                $_SESSION['errores'] = ['No se encontro el trabajador.'];
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            if (!$diasDisfruteModelo->esElegibleDiasDisfrute(
                $trab['tipo_contrato'] ?? '',
                $trab['fecha_primer_contrato'] ?? ''
            )) {
                $motivo = $diasDisfruteModelo->motivoNoElegibilidadDiasDisfrute(
                    $trab['tipo_contrato'] ?? '',
                    $trab['fecha_primer_contrato'] ?? ''
                );

                $_SESSION['errores'] = [
                    'El trabajador seleccionado no cumple los requisitos para registrar días de disfrute: ' . $motivo . '.'
                ];
                $_SESSION['old'] = [
                    'id_trabajador'   => $id_trabajador,
                    'nombre_completo' => $nombre_completo,
                    'cargo'           => $cargo_display,
                    'cedula'          => $trab['cedula'] ?? '',
                    'descripcion'     => $descripcion,
                    'observaciones'   => $observaciones,
                ];
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // Código individual del registro de días de disfrute.
            $codigo_disfrute = date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $resultado = $diasDisfruteModelo->registrarDiasDisfrute(
                $id_trabajador,
                $codigo_disfrute,
                $descripcion,
                $fecha_inicio,
                $fecha_final,
                $observaciones
            );

            if ($resultado) {
                registrarBitacora($conexion, 'Días de Disfrute', 'Crear', "Registró días de disfrute para el trabajador \"" . bitacoraNombreDe($conexion, 'trabajador', $id_trabajador) . "\" (código $codigo_disfrute).");
                $_SESSION['exito'] = 'Dias de disfrute registrados correctamente.';
                unset($_SESSION['old']);
            } else {
                $_SESSION['errores'] = [
                    'Error al guardar.' . ($diasDisfruteModelo->ultimoError ? ' Detalle: ' . $diasDisfruteModelo->ultimoError : '')
                ];
            }

            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        break;
    }
}
