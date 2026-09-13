<?php
// ARCHIVO: /FUNDACITE/controladores/ctrl_contrato.php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // opcional, si usas mysqli
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../modelos/clase_contrato.php";

class ContratoControlador {
    private $modelo;

    public function __construct() {
        global $conexion;
        $this->modelo = new clase_contrato($conexion);
    }

    // =========================================================================
    // GUARDAR NUEVO CONTRATO
    // =========================================================================
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
            $id_trabajador = intval($_POST['id_trabajador'] ?? 0);
            $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
            $fecha_contrato = trim($_POST['fecha_contrato'] ?? '');
            $fecha_fin = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
            $lugar_trabajo = trim($_POST['lugar_trabajo'] ?? '');
            $nombre_presidente = trim($_POST['nombre_presidente'] ?? '');
            $cedula_presidente = trim($_POST['cedula_presidente'] ?? '');
            $gaceta = trim($_POST['gaceta_designacion_presidente'] ?? '');

            // Si el contrato es Indeterminado o Indefinido, la fecha fin debe ser NULL
            if (stripos($tipo_contrato, 'indefinido') !== false || stripos($tipo_contrato, 'indeterminado') !== false) {
                $fecha_fin = null;
            }

            // Validaciones básicas obligatorias en backend
            if (
                $id_trabajador <= 0 || 
                empty($tipo_contrato) || 
                empty($fecha_contrato) || 
                empty($lugar_trabajo) || 
                empty($nombre_presidente) || 
                empty($cedula_presidente) || 
                empty($gaceta)
            ) {
                header("Location: ../vistas/registrar_contrato.php?status=error&msg=faltan_campos");
                exit();
            }

            $resultado = $this->modelo->registrarContrato(
                $id_trabajador, 
                $tipo_contrato, 
                $fecha_contrato, 
                $fecha_fin, 
                $lugar_trabajo, 
                $nombre_presidente, 
                $cedula_presidente, 
                $gaceta
            );

            if ($resultado) {
                header("Location: ../vistas/registrar_contrato.php?status=success");
            } else {
                header("Location: ../vistas/registrar_contrato.php?status=error");
            }
            exit();
        }
    }

    // =========================================================================
    // ACTUALIZAR CONTRATO EXISTENTE
    // =========================================================================
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
            $id_contrato = intval($_POST['id_contrato'] ?? 0);
            $id_trabajador = intval($_POST['id_trabajador'] ?? 0);
            $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
            $fecha_contrato = trim($_POST['fecha_contrato'] ?? '');
            $fecha_fin = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
            $lugar_trabajo = trim($_POST['lugar_trabajo'] ?? '');
            $nombre_presidente = trim($_POST['nombre_presidente'] ?? '');
            $cedula_presidente = trim($_POST['cedula_presidente'] ?? '');
            $gaceta = trim($_POST['gaceta_designacion_presidente'] ?? '');

            if (stripos($tipo_contrato, 'indefinido') !== false || stripos($tipo_contrato, 'indeterminado') !== false) {
                $fecha_fin = null;
            }

            if ($id_contrato <= 0 || $id_trabajador <= 0) {
                header("Location: ../vistas/registrar_contrato.php?status=error");
                exit();
            }

            $resultado = $this->modelo->actualizarContrato(
                $id_contrato, 
                $id_trabajador, 
                $tipo_contrato, 
                $fecha_contrato, 
                $fecha_fin, 
                $lugar_trabajo, 
                $nombre_presidente, 
                $cedula_presidente, 
                $gaceta
            );

            if ($resultado) {
                header("Location: ../vistas/registrar_contrato.php?status=updated");
            } else {
                header("Location: ../vistas/editar_contrato.php?id={$id_contrato}&status=error");
            }
            exit();
        }
    }

    // =========================================================================
    // ELIMINAR CONTRATO
    // =========================================================================
    public function borrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
            $id_contrato = intval($_GET['id'] ?? 0);
            if ($id_contrato > 0) {
                $this->modelo->eliminarContrato($id_contrato);
            }
            header("Location: ../vistas/registrar_contrato.php?status=deleted");
            exit();
        }
    }

    // =========================================================================
    // MÉTODOS DE CONSULTA PARA LAS VISTAS
    // =========================================================================
    public function mostrarContratos() {
        if (method_exists($this->modelo, 'obtenerContratos')) {
            return $this->modelo->obtenerContratos();
        } elseif (method_exists($this->modelo, 'listarContratos')) {
            return $this->modelo->listarContratos();
        }
        return [];
    }

    public function buscarPorId($id) {
        if (method_exists($this->modelo, 'obtenerPorId')) {
            return $this->modelo->obtenerPorId($id);
        } elseif (method_exists($this->modelo, 'buscarPorId')) {
            return $this->modelo->buscarPorId($id);
        }
        return false;
    }
}

// INSTANCIACIÓN DE VARIABLES GLOBALES PARA LAS VISTAS
$controlador = new ContratoControlador();
$controladorContrato = $controlador; // Soporte para $controladorContrato usado en registrar_contrato.php

// ENRUTADOR DE ACCIONES
if (isset($_POST['accion'])) {
    if ($_POST['accion'] === 'guardar') {
        $controlador->guardar();
    } elseif ($_POST['accion'] === 'actualizar') {
        $controlador->actualizar();
    }
} elseif (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $controlador->borrar();
}
?>