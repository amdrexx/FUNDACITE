<?php

require_once("../modelos/clase_parroquia.php");

class ParroquiaController
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new Parroquia();
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
    public function listar()
    {
        return $this->modelo->listar();
    }

    /*=========================
      BUSCAR
    =========================*/
    public function buscar($cod_par)
    {
        return $this->modelo->buscar($cod_par);
    }

    /*=========================
      REGISTRAR
    =========================*/
    public function registrar()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $cod_muni   = $_POST["cod_muni"] ?? "";
            $parroquias = $_POST["parroquia"] ?? [];

            if (empty($cod_muni)) {

                echo "<script>
                        alert('Debe seleccionar un municipio.');
                        window.history.back();
                      </script>";
                exit;
            }

            $this->modelo->registrar($cod_muni, $parroquias);

            header("Location: ../vistas/registro_parroquia.php");
            exit();
        }
    }

    /*=========================
      EDITAR
    =========================*/
    public function editar()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $cod_par  = $_POST["cod_par"];
            $cod_muni = $_POST["cod_muni"];
            $nombre   = trim($_POST["parroquia"][0]);

            $this->modelo->editar($cod_par, $cod_muni, $nombre);

            header("Location: ../vistas/registro_parroquia.php");
            exit();
        }
    }

    /*=========================
      ELIMINAR
    =========================*/
    public function eliminar()
    {
        if (isset($_GET["eliminar"])) {

            $this->modelo->eliminar($_GET["eliminar"]);

            header("Location: ../vistas/registro_parroquia.php");
            exit();
        }
    }

    /*=========================
      CARGAR MUNICIPIOS AJAX
    =========================*/
    public function cargarMunicipios()
    {
        if (isset($_POST["cod_est"])) {

            $municipios = $this->modelo->listarMunicipios($_POST["cod_est"]);

            echo '<option value="">Seleccione un Municipio</option>';

            while ($fila = mysqli_fetch_assoc($municipios)) {

                echo '<option value="' . $fila["cod_muni"] . '">'
                    . $fila["nombre"] .
                    '</option>';
            }

            exit();
        }
    }
}

/*==================================
  EJECUTAR ACCIONES
===================================*/

$controller = new ParroquiaController();

if (isset($_POST["accion"])) {

    switch ($_POST["accion"]) {

        case "registrar":
            $controller->registrar();
            break;

        case "editar":
            $controller->editar();
            break;

        case "cargarMunicipios":
            $controller->cargarMunicipios();
            break;
    }
}

if (isset($_GET["eliminar"])) {
    $controller->eliminar();
}

?>