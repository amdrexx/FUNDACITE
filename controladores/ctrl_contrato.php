<?php
// ARCHIVO: controladores/ctrl_contrato.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vistas/includes/guardian.php';

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

require_once __DIR__ . '/../conexion.php'; 
require_once __DIR__ . '/../modelos/clase_contrato.php';

class ContratoControlador {
    private $modelo;

    public function __construct($conexion = null) {
        if ($conexion === null) {
            global $conexion; 
        }
        $this->modelo = new clase_contrato($conexion);
    }

    public function mostrarContratos($buscar = "") {
        return $this->modelo->listarContratos($buscar);
    }

    public function buscarPorId($id_contrato) {
        return $this->modelo->buscarPorId($id_contrato);
    }

    // =========================================================================
    // REGISTRAR CONTRATO
    // =========================================================================
    public function guardar() {
        if (isset($_POST['registrar_contrato'])) {
            $id_trabajador                 = intval($_POST['id_trabajador']);
            $tipo_contrato                 = trim($_POST['tipo_contrato']);
            $fecha_contrato                = $_POST['fecha_contrato'];
            $lugar_trabajo                 = trim($_POST['lugar_trabajo']);
            $nombre_presidente             = trim($_POST['nombre_presidente']);
            $cedula_presidente             = trim($_POST['cedula_presidente']);
            $gaceta_designacion_presidente = trim($_POST['gaceta_designacion_presidente']);

            $tipos_validos = ['Indefinido', 'Tiempo determinado', 'Obra determinada', 'Pasantía', 'Suplencia'];

            if ($id_trabajador > 0 && in_array($tipo_contrato, $tipos_validos) && !empty($fecha_contrato)) {
                if ($this->modelo->registrarContrato($id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente)) {
                    $_SESSION['exito_contrato'] = "Contrato registrado correctamente.";
                    header("Location: ../vistas/registrar_contrato.php?status=success");
                    exit();
                } else {
                    $_SESSION['error_contrato'] = ["No fue posible registrar el contrato."];
                    header("Location: ../vistas/registrar_contrato.php?status=error");
                    exit();
                }
            } else {
                $_SESSION['error_contrato'] = ["Por favor complete todos los campos obligatorios."];
                header("Location: ../vistas/registrar_contrato.php?status=error");
                exit();
            }
        }
    }

    // =========================================================================
    // ACTUALIZAR CONTRATO
    // =========================================================================
    public function actualizar() {
        if (isset($_POST['editar_contrato'])) {
            $id_contrato                   = intval($_POST['id_contrato']);
            $id_trabajador                 = intval($_POST['id_trabajador']);
            $tipo_contrato                 = trim($_POST['tipo_contrato']);
            $fecha_contrato                = $_POST['fecha_contrato'];
            $lugar_trabajo                 = trim($_POST['lugar_trabajo']);
            $nombre_presidente             = trim($_POST['nombre_presidente']);
            $cedula_presidente             = trim($_POST['cedula_presidente']);
            $gaceta_designacion_presidente = trim($_POST['gaceta_designacion_presidente']);

            $tipos_validos = ['Indefinido', 'Tiempo determinado', 'Obra determinada', 'Pasantía', 'Suplencia'];

            if ($id_contrato > 0 && $id_trabajador > 0 && in_array($tipo_contrato, $tipos_validos) && !empty($fecha_contrato)) {
                if ($this->modelo->actualizarContrato($id_contrato, $id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente)) {
                    $_SESSION['exito_contrato'] = "Contrato actualizado correctamente.";
                    // Redirección de regreso a la vista combinada con el mensaje de status
                    header("Location: ../vistas/registrar_contrato.php?status=updated");
                    exit();
                } else {
                    $_SESSION['error_contrato'] = ["No se pudieron guardar los cambios en el contrato."];
                    header("Location: ../vistas/editar_contrato.php?id=" . $id_contrato);
                    exit();
                }
            } else {
                $_SESSION['error_contrato'] = ["Por favor complete todos los datos requeridos."];
                header("Location: ../vistas/editar_contrato.php?id=" . $id_contrato);
                exit();
            }
        }
    }

    // =========================================================================
    // ELIMINAR CONTRATO
    // =========================================================================
    public function borrar() {
        if (isset($_GET['action']) && $_GET['action'] == 'eliminar' && !empty($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($this->modelo->eliminarContrato($id)) {
                $_SESSION['exito_contrato'] = "Contrato eliminado correctamente.";
                header("Location: ../vistas/registrar_contrato.php?status=deleted");
                exit();
            } else {
                $_SESSION['error_contrato'] = ["No se pudo eliminar el contrato."];
                header("Location: ../vistas/registrar_contrato.php?status=error");
                exit();
            }
        }
    }
}

$controladorContrato = new ContratoControlador();
$controladorContrato->guardar();
$controladorContrato->actualizar();
$controladorContrato->borrar();
?>