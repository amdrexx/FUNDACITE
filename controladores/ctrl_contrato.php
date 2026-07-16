<?php
// ======================================================
// ARCHIVO: controladores/ContratoControlador.php
// DESCRIPCIÓN: Controlador del módulo de Contratos
// ======================================================

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_contrato.php';

class ContratoControlador
{
    private $modelo;

    public function __construct($conexion)
    {
        $this->modelo = new clase_contrato($conexion);
    }

    // ======================================================
    // OBTENER TRABAJADORES ACTIVOS
    // ======================================================
    public function mostrarTrabajadoresActivos()
    {
        return $this->modelo->obtenerTrabajadoresActivos();
    }

    // ======================================================
    // LISTAR CONTRATOS
    // ======================================================
    public function mostrarContratos()
    {
        return $this->modelo->listarContratos();
    }

    // ======================================================
    // OBTENER UN CONTRATO
    // ======================================================
    public function obtenerContrato($id)
    {
        return $this->modelo->obtenerContrato($id);
    }

    // ======================================================
    // REGISTRAR CONTRATO
    // ======================================================
    public function guardar()
    {

        if (!isset($_POST['registrar_contrato'])) {
            return;
        }

        $id_trabajador = intval($_POST['id_trabajador']);
        $tipo_contrato = trim($_POST['tipo_contrato']);
        $fecha_ingreso = trim($_POST['fecha_ingreso']);
        $lugar_trabajo = trim($_POST['lugar_trabajo']);
        $salario       = floatval($_POST['salario']);
        $notas_empresa = trim($_POST['notas_empresa']);

        if (
            empty($id_trabajador) ||
            empty($tipo_contrato) ||
            empty($fecha_ingreso) ||
            empty($lugar_trabajo) ||
            $salario <= 0
        ) {

            header("Location: ../vistas/registrar_contrato.php?status=error");
            exit;
        }

        // Evitar que un trabajador tenga dos contratos
        if ($this->modelo->trabajadorTieneContrato($id_trabajador)) {

            header("Location: ../vistas/registrar_contrato.php?status=existe");
            exit;
        }

        $guardar = $this->modelo->registrarContrato(
            $id_trabajador,
            $notas_empresa,
            $tipo_contrato,
            $fecha_ingreso,
            $lugar_trabajo,
            $salario
        );

        if ($guardar) {

            header("Location: ../vistas/registrar_contrato.php?status=success");
            exit;

        } else {

            header("Location: ../vistas/registrar_contrato.php?status=error");
            exit;

        }
    }

    // ======================================================
    // ACTUALIZAR CONTRATO
    // ======================================================
    public function actualizar()
    {

        if (!isset($_POST['editar_contrato'])) {
            return;
        }

        $id_contrato   = intval($_POST['id_contrato']);
        $id_trabajador = intval($_POST['id_trabajador']);
        $tipo_contrato = trim($_POST['tipo_contrato']);
        $fecha_ingreso = trim($_POST['fecha_ingreso']);
        $lugar_trabajo = trim($_POST['lugar_trabajo']);
        $salario       = floatval($_POST['salario']);
        $notas_empresa = trim($_POST['notas_empresa']);

        $actualizar = $this->modelo->actualizarContrato(
            $id_contrato,
            $id_trabajador,
            $notas_empresa,
            $tipo_contrato,
            $fecha_ingreso,
            $lugar_trabajo,
            $salario
        );

        if ($actualizar) {

            header("Location: ../vistas/registrar_contrato.php?status=updated");
            exit;

        } else {

            header("Location: ../vistas/editar_contrato.php?id=".$id_contrato."&status=error");
            exit;

        }
    }

    // ======================================================
    // ELIMINAR CONTRATO
    // ======================================================
    public function eliminar()
    {

        if (
            isset($_GET['action']) &&
            $_GET['action'] == 'eliminar' &&
            isset($_GET['id'])
        ) {

            $id = intval($_GET['id']);

            if ($this->modelo->eliminarContrato($id)) {

                header("Location: ../vistas/registrar_contrato.php?status=deleted");
                exit;

            } else {

                header("Location: ../vistas/registrar_contrato.php?status=error");
                exit;

            }
        }
    }
}

// ======================================================
// INSTANCIA
// ======================================================

$controladorContrato = new ContratoControlador($conexion);

// ======================================================
// EJECUTAR ACCIONES
// ======================================================

$controladorContrato->guardar();
$controladorContrato->actualizar();
$controladorContrato->eliminar();

?>