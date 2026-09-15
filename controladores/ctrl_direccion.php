<?php
require_once("../modelos/clase_direccion.php");
require_once("../conexion.php");
require_once __DIR__ . "/helpers/bitacora_helper.php";
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
    public function listar() {
        $resultado = $this->modelo->listar();
        global $conexion;
        registrarBitacora($conexion, 'Direcciones', 'Consultar', 'Consultó el listado de direcciones.');
        return $resultado;
    }
    public function buscar($id_dir) {
        $resultado = $this->modelo->buscar($id_dir);
        global $conexion;
        registrarBitacora($conexion, 'Direcciones', 'Consultar', "Consultó la dirección ID $id_dir.");
        return $resultado;
    }

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

            global $conexion;
            registrarBitacora($conexion, 'Direcciones', 'Crear', "Registró una dirección (parroquia ID $cod_par).");

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

            global $conexion;
            registrarBitacora($conexion, 'Direcciones', 'Editar', "Actualizó la dirección ID $id_dir (parroquia ID $cod_par).");

            header("Location: ../vistas/registro_direccion.php");
            exit;
        }
    }

    public function eliminar()
    {
        if (isset($_GET["eliminar"])) {

            $idDir = $_GET["eliminar"];
            $resultado = $this->modelo->eliminar($idDir);

            $mensaje = "ok";
            if ($resultado === "RESTRICT") {
                $mensaje = "restrict";
            } elseif ($resultado === false) {
                $mensaje = "error";
            } else {
                global $conexion;
                registrarBitacora($conexion, 'Direcciones', 'Eliminar', "Eliminó la dirección ID $idDir.");
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