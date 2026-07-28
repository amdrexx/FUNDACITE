<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../vistas/includes/guardian.php');
requireAdministradorODirector();

require_once(__DIR__ . '/../conexion.php');

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
            $sql = "SELECT t.cedula, co.tipo_contrato
                    FROM TRABAJADOR t
                    LEFT JOIN CONTRATO co ON co.id_trabajador = t.id_trabajador
                    WHERE t.id_trabajador = ?
                    ORDER BY co.id_contrato DESC
                    LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_trabajador);
            $stmt->execute();
            $trab = $stmt->get_result()->fetch_assoc();

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

            // Generar codigo de solicitud
            $codigo_solicitud = 'SOL-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            $conexion->begin_transaction();

            try {
                // 1) Insertar en SOLICITUD
                $sqlSol = "INSERT INTO SOLICITUD
                           (id_trabajador, codigo_solicitud, tipo_solicitud, motivo_solicitud, fecha_inicio, fecha_finalizacion)
                           VALUES (?, ?, 'Vacaciones', ?, ?, ?)";
                $stmtSol = $conexion->prepare($sqlSol);
                $fecha_final_val = $fecha_final !== '' ? $fecha_final : null;
                $stmtSol->bind_param("issss", $id_trabajador, $codigo_solicitud, $descripcion, $fecha_inicio, $fecha_final_val);
                $stmtSol->execute();
                $id_solicitud = $conexion->insert_id;

                // 2) Insertar en DISFRUTE_DE_VACACIONES
                $sqlDis = "INSERT INTO DISFRUTE_DE_VACACIONES
                           (id_solicitud, nombre_cargo, descripcion, desde, hasta)
                           VALUES (?, ?, ?, ?, ?)";
                $stmtDis = $conexion->prepare($sqlDis);
                $stmtDis->bind_param("issss", $id_solicitud, $cargo_display, $descripcion, $desde, $hasta);
                $stmtDis->execute();

                $conexion->commit();

                $_SESSION['exito'] = 'Dias de disfrute registrados correctamente.';
                unset($_SESSION['old']);

            } catch (\Throwable $e) {
                $conexion->rollback();
                $_SESSION['errores'] = ['Error al guardar: ' . $e->getMessage()];
            }

            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        break;
    }
}
