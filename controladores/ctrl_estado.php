<?php

require_once("../modelos/clase_estado.php");
require_once __DIR__ . "/helpers/bitacora_helper.php";

class EstadoController {

    private $modelo;

    public function __construct() {
        $this->modelo = new Estado();
    }

    public function listar() {
        $resultado = $this->modelo->listar();
        $data = [];
        while ($row = $resultado->fetch_assoc()) {
            $data[] = $row;
        }
        global $conexion;
        registrarBitacora($conexion, 'Estados', 'Consultar', 'Consultó el listado de estados.');
        return $data;
    }

    public function buscar($id) {
        $resultado = $this->modelo->buscar($id);
        global $conexion;
        registrarBitacora($conexion, 'Estados', 'Consultar', "Consultó el estado ID $id.");
        return $resultado;
    }

    public function guardar() {
        if (!empty($_POST['nombre'])) {
            $this->modelo->insertar($_POST['nombre']);
            global $conexion;
            registrarBitacora($conexion, 'Estados', 'Crear', "Registró el estado \"{$_POST['nombre']}\".");
            header("Location: ../vistas/registro_estados.php");
        }
    }

    public function actualizar() {
        if (!empty($_POST['cod_est']) && !empty($_POST['nombre'])) {
            $this->modelo->actualizar($_POST['cod_est'], $_POST['nombre']);
            global $conexion;
            registrarBitacora($conexion, 'Estados', 'Editar', "Actualizó el estado ID {$_POST['cod_est']} a \"{$_POST['nombre']}\".");
            header("Location: ../vistas/registro_estados.php");
        }
    }

    public function eliminar($id) {
        $this->modelo->eliminar($id);
        global $conexion;
        registrarBitacora($conexion, 'Estados', 'Eliminar', "Eliminó el estado ID $id.");
        header("Location: ../vistas/registro_estados.php");
    }
}