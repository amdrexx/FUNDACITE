<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../vistas/includes/guardian.php');
requireAdminDirectorOEstandar();

// Aprobar/desaprobar y eliminar siguen siendo exclusivos de Administrador y
// Director. El Usuario Estándar solo puede registrar SU propia solicitud.
if (isset($_POST['eliminar_disfrute']) || isset($_POST['cambiar_estado_disfrute'])) {
    requireAdministradorODirector();
}

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

    // El botón pulsado ('Aprobar' o 'Desaprobar') manda sobre el estado
    // destino. Antes el código solo invertía el estado ACTUAL, así que un
    // clic en "Desaprobar" sobre una solicitud Pendiente terminaba
    // aprobándola igual, y nunca se leía la justificación que pedía el
    // formulario: por eso desaprobar siempre fallaba.
    $accionEstado = $_POST['accion_estado'] ?? '';
    $mapaAccion = ['Aprobar' => 'Aprobado', 'Desaprobar' => 'Desaprobado'];

    if (!isset($mapaAccion[$accionEstado])) {
        $_SESSION['error_edicion'] = ['Acción no reconocida.'];
        header('Location: ' . $redirigirA);
        exit;
    }

    $nuevoEstado = $mapaAccion[$accionEstado];
    $justificacion = trim((string) ($_POST['justificacion_desaprobacion'] ?? ''));

    $estadoActual = $diasDisfruteModelo->obtenerEstadoDiasDisfrute($id_dis);

    if ($estadoActual === null) {
        $_SESSION['error_edicion'] = ['No se encontró el registro de días de disfrute.'];
        header('Location: ' . $redirigirA);
        exit;
    }

    if ($estadoActual === 'Vencida') {
        $_SESSION['error_edicion'] = ['La solicitud ya venció al cumplir 15 días calendario sin decisión y no puede aprobarse ni desaprobarse.'];
        header('Location: ' . $redirigirA);
        exit;
    }

    if ($estadoActual === $nuevoEstado) {
        // Ya está en ese estado (doble clic, botón debió estar deshabilitado, etc.).
        $_SESSION['exito_edicion'] = $nuevoEstado === 'Aprobado'
            ? 'El registro ya estaba aprobado.'
            : 'El registro ya estaba desaprobado.';
        header('Location: ' . $redirigirA);
        exit;
    }

    // Si por error se aprobó una solicitud, el administrador puede
    // desaprobarla después (y viceversa) siempre que indique la
    // justificación de la desaprobación.
    $resultado = $diasDisfruteModelo->cambiarEstadoDiasDisfrute($id_dis, $nuevoEstado, $justificacion);

    if ($resultado) {
        $nombreRegistro = bitacoraNombreDe($conexion, 'dias_disfrute', $id_dis);
        $accionBitacora = $nuevoEstado === 'Aprobado' ? 'Aprobar' : 'Desaprobar';
        $verboBitacora = $nuevoEstado === 'Aprobado' ? 'Aprobó' : 'Desaprobó';
        $detalleBitacora = $verboBitacora . ' el registro de ' . $nombreRegistro . '.';
        if ($nuevoEstado === 'Desaprobado' && $justificacion !== '') {
            $detalleBitacora .= ' Justificación: ' . $justificacion;
        }
        registrarBitacora($conexion, 'Días de Disfrute', $accionBitacora, $detalleBitacora);
        $_SESSION['exito_edicion'] = $nuevoEstado === 'Aprobado'
            ? 'Registro aprobado correctamente.'
            : 'Registro desaprobado correctamente.';
    } else {
        $_SESSION['error_edicion'] = [
            $diasDisfruteModelo->ultimoError !== ''
                ? $diasDisfruteModelo->ultimoError
                : 'No se pudo actualizar el estado del registro.'
        ];
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
            // Usuario Estándar: siempre su propio trabajador, sin importar lo que llegue por POST.
            if (esUsuarioEstandar()) {
                $id_trabajador = idTrabajadorSesion();
            }
            $nombre_completo  = trim($_POST['nombre_completo'] ?? '');
            $cargo_display    = trim($_POST['cargo_display'] ?? '');
            // La fecha de inicio se fija en el servidor con la fecha actual.
            // La fecha de finalización se calcula únicamente al aprobar.
            $zonaHoraria = new DateTimeZone('America/Caracas');
            $fecha_inicio = (new DateTimeImmutable('today', $zonaHoraria))->format('Y-m-d');
            $fecha_final = '';
            $descripcion      = trim($_POST['descripcion'] ?? '');
            $observaciones    = trim($_POST['observaciones'] ?? '');
            $tipo_remuneracion = (string) ($_POST['tipo_remuneracion'] ?? '');
            $requiere_suplente = (string) ($_POST['requiere_suplente'] ?? '');

            // Datos que se conservan al regresar al formulario si ocurre un error.
            $datosFormulario = [
                'id_trabajador' => $id_trabajador,
                'nombre_completo' => $nombre_completo,
                'cargo' => $cargo_display,
                'cedula' => '',
                'descripcion' => $descripcion,
                'observaciones' => $observaciones,
                'tipo_remuneracion' => $tipo_remuneracion,
                'requiere_suplente' => $requiere_suplente,
            ];

            $errores = [];

            if ($id_trabajador <= 0) {
                $errores[] = 'Debe seleccionar un trabajador.';
            }
            if ($descripcion === '') {
                $errores[] = 'La descripcion es obligatoria.';
            }
            if ($observaciones === '') {
                $errores[] = 'Las observaciones son obligatorias.';
            }
            if (!in_array($tipo_remuneracion, ['remunerado', 'no_remunerado'], true)) {
                $errores[] = 'Debe indicar si los días de disfrute son remunerados o no remunerados.';
            }
            if (!in_array($requiere_suplente, ['si', 'no'], true)) {
                $errores[] = 'Debe indicar si se requiere suplente.';
            }
            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $_SESSION['old'] = $datosFormulario;
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // Confirmar que el trabajador exista; el módulo no exige antigüedad mínima.
            $trab = $diasDisfruteModelo->obtenerContratoTrabajador($id_trabajador);

            if (!$trab) {
                $_SESSION['errores'] = ['No se encontro el trabajador.'];
                $_SESSION['old'] = $datosFormulario;
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // Cada trabajador solo puede tener UNA solicitud de días de
            // disfrute activa (Pendiente) a la vez. Si la anterior venció
            // (15 días calendario sin decisión) o ya fue aprobada/desaprobada,
            // puede registrar una nueva.
            if ($diasDisfruteModelo->tieneSolicitudActiva($id_trabajador)) {
                $_SESSION['errores'] = [
                    'Este trabajador ya tiene una solicitud de días de disfrute pendiente por decidir. '
                    . 'Debe esperar a que el Director/Administrador la apruebe o desapruebe, o a que venza '
                    . 'el plazo de 15 días, para poder registrar una nueva solicitud.'
                ];
                $datosFormulario['cedula'] = $trab['cedula'] ?? '';
                $_SESSION['old'] = $datosFormulario;
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // La cantidad de días corresponde a la que indique el convenio
            // colectivo vigente; si no hay ninguno, no se puede registrar.
            $dias_correspondientes = $diasDisfruteModelo->calcularDiasDisfrute();
            if ($dias_correspondientes === null) {
                $_SESSION['errores'] = [
                    'No hay un convenio vigente registrado que indique los días de disfrute correspondientes. '
                    . 'Registre o actualice el convenio colectivo antes de continuar.'
                ];
                $datosFormulario['cedula'] = $trab['cedula'] ?? '';
                $_SESSION['old'] = $datosFormulario;
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            // Código individual del registro de días de disfrute.
            $codigo_disfrute = str_replace('-', '', $fecha_inicio) . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $resultado = $diasDisfruteModelo->registrarDiasDisfrute(
                $id_trabajador,
                $codigo_disfrute,
                $descripcion,
                $fecha_inicio,
                $fecha_final,
                $observaciones,
                $tipo_remuneracion,
                $requiere_suplente,
                $dias_correspondientes
            );

            if ($resultado) {
                registrarBitacora($conexion, 'Días de Disfrute', 'Crear', "Registró días de disfrute para el trabajador \"" . bitacoraNombreDe($conexion, 'trabajador', $id_trabajador) . "\" (código $codigo_disfrute).");
                $_SESSION['exito'] = 'Dias de disfrute registrados correctamente.';
                unset($_SESSION['old']);
            } else {
                $_SESSION['errores'] = [
                    $diasDisfruteModelo->ultimoError !== ''
                        ? $diasDisfruteModelo->ultimoError
                        : 'No se pudo guardar la solicitud de días de disfrute.'
                ];
                $datosFormulario['cedula'] = $trab['cedula'] ?? '';
                $_SESSION['old'] = $datosFormulario;
            }

            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        break;
    }
}
