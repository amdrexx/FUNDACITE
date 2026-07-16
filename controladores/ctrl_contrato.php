<?php
// ARCHIVO: /FUNDACITE/controladores/ctrl_contrato.php

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

    public function mostrarContratos() {
        return $this->modelo->listarContratos();
    }

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
                    header("Location: /FUNDACITE/vistas/registrar_contrato.php?status=success");
                    exit();
                } else {
                    header("Location: /FUNDACITE/vistas/registrar_contrato.php?status=error");
                    exit();
                }
            } else {
                header("Location: /FUNDACITE/vistas/registrar_contrato.php?status=error");
                exit();
            }
        }
    }

    public function borrar() {
        if (isset($_GET['action']) && $_GET['action'] == 'eliminar' && !empty($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($this->modelo->eliminarContrato($id)) {
                header("Location: /FUNDACITE/vistas/registrar_contrato.php?status=deleted");
                exit();
            } else {
                header("Location: /FUNDACITE/vistas/registrar_contrato.php?status=error");
                exit();
            }
        }
    }
}

$controladorContrato = new ContratoControlador();
$controladorContrato->guardar();
$controladorContrato->borrar();
?>