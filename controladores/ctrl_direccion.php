<?php
require_once("../modelos/clase_direccion.php");

class DireccionController
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new Direccion();
    }

    /*=========================
      LISTAR ESTADOS
    =========================*/
    public function listarEstados()
    {
        return $this->modelo->listarEstados();
    }

    /*=========================
      LISTAR MUNICIPIOS
    =========================*/
    public function listarMunicipios($cod_est)
    {
        return $this->modelo->listarMunicipios($cod_est);
    }

    /*=========================
      LISTAR PARROQUIAS
    =========================*/
    public function listarParroquias($cod_muni)
    {
        return $this->modelo->listarParroquias($cod_muni);
    }

    /*=========================
      LISTAR DIRECCIONES
    =========================*/
    public function listar()
    {
        return $this->modelo->listar();
    }

    /*=========================
      BUSCAR DIRECCION
    =========================*/
    public function buscar($id_dir)
    {
        return $this->modelo->buscar($id_dir);
    }

    /*=========================
      REGISTRAR
    =========================*/
    public function registrar()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $cod_par = $_POST["cod_par"];
            $nombre  = trim($_POST["nombre"]);

            if (empty($cod_par) || empty($nombre)) {

                echo "<script>
                        alert('Debe completar todos los campos.');
                        window.history.back();
                      </script>";
                exit;
            }

            $this->modelo->registrar($cod_par, $nombre);

            header("Location: ../vistas/registro_direccion.php");
            exit;
        }
    }

    /*=========================
      EDITAR
    =========================*/
    public function editar()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $id_dir  = $_POST["id_dir"];
            $cod_par = $_POST["cod_par"];
            $nombre  = trim($_POST["nombre"]);

            if (empty($cod_par) || empty($nombre)) {

                echo "<script>
                        alert('Debe completar todos los campos.');
                        window.history.back();
                      </script>";
                exit;
            }

            $this->modelo->editar($id_dir, $cod_par, $nombre);

            header("Location: ../vistas/registro_direccion.php");
            exit;
        }
    }

    /*=========================
      ELIMINAR
    =========================*/
    public function eliminar()
    {
        if (isset($_GET["eliminar"])) {

            $this->modelo->eliminar($_GET["eliminar"]);

            header("Location: ../vistas/registro_direccion.php");
            exit;
        }
    }
}

/*=========================
  EJECUTAR ACCIONES
=========================*/

$controller = new DireccionController();

if (isset($_POST["accion"])) {

    switch ($_POST["accion"]) {

        case "registrar":
            $controller->registrar();
            break;

        case "editar":
            $controller->editar();
            break;
    }
}

if (isset($_GET["eliminar"])) {
    $controller->eliminar();
}

?>