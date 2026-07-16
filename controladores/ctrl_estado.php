<?php

require_once("../modelos/clase_estado.php");

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
        return $data;
    }

    public function buscar($id) {
        return $this->modelo->buscar($id);
    }

    public function guardar() {
        if (!empty($_POST['nombre'])) {
            $this->modelo->insertar($_POST['nombre']);
            header("Location: ../vistas/registro_estados.php");
        }
    }

    public function actualizar() {
        if (!empty($_POST['cod_est']) && !empty($_POST['nombre'])) {
            $this->modelo->actualizar($_POST['cod_est'], $_POST['nombre']);
            header("Location: ../vistas/registro_estados.php");
        }
    }

    public function eliminar($id) {
        $this->modelo->eliminar($id);
        header("Location: ../vistas/registro_estados.php");
    }
}