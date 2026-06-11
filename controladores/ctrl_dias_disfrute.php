<?php

session_start();

require_once(__DIR__ . '/../conexion.php');

function puedeGozarVacaciones($tipoContrato)
{
    $tipoContrato = strtolower(trim($tipoContrato));
    $noGozan = ['honorario', 'eventual']; // Puedes añadir más tipos sin derecho
    return !in_array($tipoContrato, $noGozan);
}

/**
 * CONTROL DE ACCIONES
 */
if (isset($_POST['accion'])) {

    switch ($_POST['accion']) {

        case 'consultar':

            $cedula = trim($_POST['cedula']);

            $sql = "SELECT t.*, c.nombre_cargo
                    FROM trabajador t
                    INNER JOIN cargo c
                    ON t.id_cargo = c.id_cargo
                    WHERE t.cedula = ?";

            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("s", $cedula);
            $stmt->execute();

            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {

                $trabajador = $resultado->fetch_assoc();

                // Guardar datos en sesión
                $_SESSION['consulta_exitosa'] = true;
                $_SESSION['old']['cedula'] = $trabajador['cedula'];
                $_SESSION['old']['nombre_cargo'] = $trabajador['nombre_cargo'];
                $_SESSION['old']['tipo_contrato'] = $trabajador['tipo_contrato'] ?? '';

                // Validación según tipo de contrato
                if (!puedeGozarVacaciones($trabajador['tipo_contrato'] ?? '')) {
                    if (!isset($_SESSION['errores'])) $_SESSION['errores'] = [];
                    $_SESSION['errores'][] = "No puede gozar de días de disfrute debido a su tipo de contrato.";
                    $_SESSION['consulta_exitosa'] = false; // Bloquea inputs
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
            
            // Bloquear cálculo si no tiene derecho
            if (!puedeGozarVacaciones($tipoContrato)) {
                if (!isset($_SESSION['errores'])) $_SESSION['errores'] = [];
                $_SESSION['errores'][] = "Este trabajador no tiene derecho a días de disfrute por su tipo de contrato.";
                header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
                exit;
            }

            header("Location: /FUNDACITE/vistas/registrar_dias_disfrute.php");
            exit;

        break;
    }
}