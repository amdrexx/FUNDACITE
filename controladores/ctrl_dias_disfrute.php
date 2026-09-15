<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../vistas/includes/guardian.php');
requireAdministradorODirector();

require_once(__DIR__ . '/../conexion.php');
require_once(__DIR__ . '/../modelos/clase_solicitud.php');
require_once(__DIR__ . '/helpers/bitacora_helper.php');

$solicitudModelo = new Solicitud($conexion);

function puedeGozarVacaciones(string $tipoContrato): bool
{
    $tipoContrato = strtolower(trim($tipoContrato));

    if ($tipoContrato === '') {
        return false;
    }

    $noGozan = ['honorario', 'eventual'];
    return !in_array($tipoContrato, $noGozan, true);
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
            $desde            = trim($_POST['desde'] ?? '');
            $hasta            = trim($_POST['hasta'] ?? '');

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
            if ($desde === '') {
                $errores[] = 'El campo Desde es obligatorio.';
            }
            if ($hasta === '') {
                $errores[] = 'El campo Hasta es obligatorio.';
            }
            if ($desde !== '' && $hasta !== '' && $hasta < $desde) {
                $errores[] = 'La fecha Hasta no puede ser anterior a Desde.';
            }

            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $_SESSION['old'] = [
                    'id_trabajador'   => $id_trabajador,
                    'nombre_completo' => $nombre_completo,
                    'cargo'           => $cargo_display,
                    'cedula'          => '',
                ];
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // Obtener datos del trabajador para validar tipo de contrato
            $trab = $solicitudModelo->obtenerContratoTrabajador($id_trabajador);

            if (!$trab) {
                $_SESSION['errores'] = ['No se encontro el trabajador.'];
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            if (!puedeGozarVacaciones($trab['tipo_contrato'] ?? '')) {
                $_SESSION['errores'] = ['Este trabajador no tiene derecho a dias de disfrute por su tipo de contrato.'];
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // Generar codigo de solicitud (solo números: fecha + 4 dígitos aleatorios)
            $codigo_solicitud = date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $resultado = $solicitudModelo->registrarDiasDisfrute(
                $id_trabajador,
                $codigo_solicitud,
                $descripcion,
                $fecha_inicio,
                $fecha_final,
                $cargo_display,
                $desde,
                $hasta
            );

            if ($resultado) {
                registrarBitacora($conexion, 'Días de Disfrute', 'Crear', "Registró días de disfrute para el trabajador ID $id_trabajador (código $codigo_solicitud).");
                $_SESSION['exito'] = 'Dias de disfrute registrados correctamente.';
                unset($_SESSION['old']);
            } else {
                $_SESSION['errores'] = [
                    'Error al guardar.' . ($solicitudModelo->ultimoError ? ' Detalle: ' . $solicitudModelo->ultimoError : '')
                ];
            }

            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        break;
    }
}