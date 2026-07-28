<?php
require_once("../modelos/clase_direccion.php");
require_once("../conexion.php");
class DireccionController
{
    private 
    $modelo;

    public function __construct()
    {
        $this->modelo = new Direccion();
    }

    public function listarEstados() { return $this->modelo->listarEstados(); }
    public function listarMunicipios($cod_est) { return $this->modelo->listarMunicipios($cod_est); }
    public function listarParroquias($cod_muni) { return $this->modelo->listarParroquias($cod_muni); }
    public function listar() { return $this->modelo->listar(); }
    public function buscar($id_dir) { return $this->modelo->buscar($id_dir); }

    public function registrar()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $cod_par = $_POST["cod_par"] ?? "";

            if (empty($cod_par)) {
                echo "<script>
                        alert('Debe seleccionar Estado, Municipio y Parroquia.');
                        window.history.back();
                      </script>";
                exit;
            }

            $this->modelo->registrar($cod_par);

            header("Location: ../vistas/registro_direccion.php");
            exit;
        }
    }

    public function editar()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $id_dir  = $_POST["id_dir"];
            $cod_par = $_POST["cod_par"] ?? "";

            if (empty($cod_par)) {
                echo "<script>
                        alert('Debe seleccionar Estado, Municipio y Parroquia.');
                        window.history.back();
                      </script>";
                exit;
            }

            $this->modelo->editar($id_dir, $cod_par);

            header("Location: ../vistas/registro_direccion.php");
            exit;
        }
    }

    public function eliminar()
    {
        if (isset($_GET["eliminar"])) {

            $resultado = $this->modelo->eliminar($_GET["eliminar"]);

            $mensaje = "ok";
            if ($resultado === "RESTRICT") {
                $mensaje = "restrict";
            } elseif ($resultado === false) {
                $mensaje = "error";
            }

            header("Location: ../vistas/registro_direccion.php?msg=" . $mensaje);
            exit;
        }
    }
}

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