<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

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

        case 'consultar':

            $cedula = trim($_POST['cedula'] ?? '');

            if ($cedula === '') {
                $_SESSION['consulta_exitosa'] = false;
                $_SESSION['errores'][] = "Debe indicar la cédula del trabajador.";
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            $sql = "SELECT t.id_trabajador,
                           t.cedula,
                           t.nombres,
                           t.apellidos,
                           c.nombre_cargo,
                           co.tipo_contrato
                    FROM TRABAJADOR t
                    LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                    LEFT JOIN CONTRATO co ON co.id_trabajador = t.id_trabajador
                    WHERE t.cedula = ?
                    ORDER BY co.fecha_ingreso DESC
                    LIMIT 1";

            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                die('Error en prepare: ' . $conexion->error);
            }

            $stmt->bind_param("s", $cedula);

            if (!$stmt->execute()) {
                die('Error en execute: ' . $stmt->error);
            }

            $resultado = $stmt->get_result();

            if ($resultado && $resultado->num_rows > 0) {

                $trabajador = $resultado->fetch_assoc();

                $_SESSION['consulta_exitosa'] = true;
                $_SESSION['old']['cedula'] = $trabajador['cedula'];
                $_SESSION['old']['nombre_cargo'] = $trabajador['nombre_cargo'];
                $_SESSION['old']['tipo_contrato'] = $trabajador['tipo_contrato'] ?? '';

                if (!puedeGozarVacaciones($trabajador['tipo_contrato'] ?? '')) {
                    if (!isset($_SESSION['errores'])) {
                        $_SESSION['errores'] = [];
                    }
                    $_SESSION['errores'][] = "No puede gozar de días de disfrute debido a su tipo de contrato o porque no tiene un contrato registrado.";
                    $_SESSION['consulta_exitosa'] = false;
                }

            } else {

                $_SESSION['consulta_exitosa'] = false;

                if (!isset($_SESSION['errores'])) {
                    $_SESSION['errores'] = [];
                }

                $_SESSION['errores'][] = "No existe un trabajador con esa cédula.";
            }

            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        break;

        case 'calcular':

            $tipoContrato = $_SESSION['old']['tipo_contrato'] ?? '';

            if (!puedeGozarVacaciones($tipoContrato)) {
                if (!isset($_SESSION['errores'])) {
                    $_SESSION['errores'] = [];
                }
                $_SESSION['errores'][] = "Este trabajador no tiene derecho a días de disfrute por su tipo de contrato.";
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        break;
    }
}