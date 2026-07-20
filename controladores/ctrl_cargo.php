<?php
// ARCHIVO: /FUNDACITE/controladores/CargoControlador.php

// Incluimos la conexión y el modelo obligatoriamente
require_once __DIR__ . '/../conexion.php'; 
require_once __DIR__ . '/../modelos/clase_cargo.php';

class CargoControlador {
    private $modelo;

    public function __construct($conexion) {
        $this->modelo = new clase_cargo($conexion);
    }

    // Controlador para listar
    public function mostrarCargos() {
        return $this->modelo->listarCargos();
    }

    // Controlador para registrar
    public function guardar() {
        if (isset($_POST['registrar_cargo']) && !empty(trim($_POST['nombre_cargo']))) {
            $nombre = trim($_POST['nombre_cargo']);

            // 1. Validamos duplicado antes de procesar
            if ($this->modelo->existeCargo($nombre)) {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=duplicate");
                exit();
            }

            // 2. Intentamos registrar
            if ($this->modelo->registrarCargo($nombre)) {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=success");
                exit();
            } else {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=error");
                exit();
            }
        }
    }

    // Controlador para editar
    public function modificar() {
        if (isset($_POST['actualizar_cargo']) && !empty($_POST['id_cargo']) && !empty(trim($_POST['nombre_cargo']))) {
            $id = intval($_POST['id_cargo']);
            $nombre = trim($_POST['nombre_cargo']);
            
            // 1. Validamos si el nombre existe en OTRO registro distinto al actual
            if ($this->modelo->existeCargo($nombre, $id)) {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=duplicate");
                exit();
            }

            // 2. Intentamos actualizar
            if ($this->modelo->actualizarCargo($id, $nombre)) {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=updated");
                exit();
            } else {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=error");
                exit();
            }
        }
    }

    // Controlador para eliminar
    public function borrar() {
        if (isset($_GET['action']) && $_GET['action'] == 'eliminar' && !empty($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($this->modelo->eliminarCargo($id)) {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=deleted");
                exit();
            } else {
                header("Location: /FUNDACITE/vistas/registrar_cargo.php?status=error");
                exit();
            }
        }
    }
}

// Inicializamos el controlador pasando la conexión global del archivo conexion.php
$controladorCargo = new CargoControlador($conexion);

// Ejecutamos los disparadores automáticos para POST y GET si corresponden
$controladorCargo->guardar();
$controladorCargo->modificar();
$controladorCargo->borrar();
?>