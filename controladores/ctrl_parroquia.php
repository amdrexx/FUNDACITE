<?php

require_once __DIR__ . '/../vistas/includes/guardian.php';
require_once("../modelos/clase_parroquia.php");
require_once __DIR__ . "/helpers/bitacora_helper.php";

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
        $resultado = $this->modelo->listar();
        global $conexion;
        registrarBitacora($conexion, 'Parroquias', 'Consultar', 'Consultó el listado de parroquias.');
        return $resultado;
    }

    /*=========================
      BUSCAR
    =========================*/
    public function buscar($cod_par)
    {
        $resultado = $this->modelo->buscar($cod_par);
        global $conexion;
        registrarBitacora($conexion, 'Parroquias', 'Consultar', "Consultó la parroquia ID $cod_par.");
        return $resultado;
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

            global $conexion;
            registrarBitacora($conexion, 'Parroquias', 'Crear', 'Registró parroquia(s) en el municipio "' . bitacoraNombreDe($conexion, 'municipio', $cod_muni) . '": ' . implode(', ', (array) $parroquias) . '.');

            $cod_est = $_POST["cod_est"] ?? "";
            $destino = "../vistas/registro_parroquia.php";

            if (ctype_digit((string) $cod_est) && (int) $cod_est > 0) {
                $destino .= "?estado=" . (int) $cod_est;
            }

            header("Location: " . $destino);
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

            global $conexion;
            $antes = bitacoraSnapshot($conexion, 'parroquia', $cod_par);
            $this->modelo->editar($cod_par, $cod_muni, $nombre);
            $despues = bitacoraSnapshot($conexion, 'parroquia', $cod_par);

            registrarBitacora($conexion, 'Parroquias', 'Editar', 'Actualizó la parroquia "' . bitacoraNombre($antes, $cod_par) . '": ' . bitacoraCambios($antes, $despues));

            $cod_est = $_POST["cod_est"] ?? "";
            $destino = "../vistas/registro_parroquia.php";

            if (ctype_digit((string) $cod_est) && (int) $cod_est > 0) {
                $destino .= "?estado=" . (int) $cod_est;
            }

            header("Location: " . $destino);
            exit();
        }
    }

    /*=========================
      ELIMINAR
    =========================*/
public function eliminar()
{
    if (isset($_GET["eliminar"])) {

        $codPar = $_GET["eliminar"];
        global $conexion;
        $antes = bitacoraSnapshot($conexion, 'parroquia', $codPar);
        $resultado = $this->modelo->eliminar($codPar);

        $mensaje = "ok";

        if ($resultado === "RESTRICT") {
            $mensaje = "restrict";
        } elseif ($resultado === "NOT_FOUND") {
            $mensaje = "notfound";
        } elseif ($resultado === false) {
            $mensaje = "error";
        } else {
            registrarBitacora($conexion, 'Parroquias', 'Eliminar', 'Eliminó la parroquia "' . bitacoraNombre($antes, $codPar) . '" (municipio "' . ($antes['Municipio'] ?? 'desconocido') . '").');
        }

        $destino = "../vistas/registro_parroquia.php?msg=" . $mensaje;

        if (isset($_GET["estado"]) && (int) $_GET["estado"] > 0) {
            $destino .= "&estado=" . (int) $_GET["estado"];
        } elseif (isset($_GET["ver"]) && $_GET["ver"] === "todos") {
            $destino .= "&ver=todos";
        }

        header("Location: " . $destino);
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