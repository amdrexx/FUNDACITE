<?php
// ============================================================
// AJAX para cascada Estado → Municipio → Parroquia
// ============================================================
// Suprimimos errores para no contaminar la respuesta HTML
error_reporting(0);
ini_set('display_errors', 0);

require_once("../controladores/ctrl_direccion.php");

$controller = new DireccionController();

/*=========================================
CARGAR ESTADOS
=========================================*/
if (isset($_POST["accion"]) && $_POST["accion"] == "listarEstados") {

    $estados = $controller->listarEstados();

    if (!empty($estados) && is_array($estados)) {
        echo '<option value="">Seleccione un Estado</option>';
        foreach ($estados as $estado) {
            $cod = htmlspecialchars($estado["cod_est"] ?? '', ENT_QUOTES, 'UTF-8');
            $nom = htmlspecialchars($estado["nombre"] ?? '', ENT_QUOTES, 'UTF-8');
            echo '<option value="'.$cod.'">'.$nom.'</option>';
        }
    } else {
        echo '<option value="">No hay estados disponibles</option>';
    }

    exit;
}

/*=========================================
CARGAR MUNICIPIOS
=========================================*/
if (isset($_POST["accion"]) && $_POST["accion"] == "listarMunicipios") {

    $cod_est = isset($_POST["cod_est"]) ? intval($_POST["cod_est"]) : 0;
    $municipios = $controller->listarMunicipios($cod_est);

    if (!empty($municipios) && is_array($municipios)) {
        echo '<option value="">Seleccione un Municipio</option>';
        foreach ($municipios as $municipio) {
            $cod = htmlspecialchars($municipio["cod_muni"] ?? '', ENT_QUOTES, 'UTF-8');
            $nom = htmlspecialchars($municipio["nombre"] ?? '', ENT_QUOTES, 'UTF-8');
            echo '<option value="'.$cod.'">'.$nom.'</option>';
        }
    } else {
        echo '<option value="">No hay municipios disponibles</option>';
    }

    exit;
}

/*=========================================
CARGAR PARROQUIAS
=========================================*/
if (isset($_POST["accion"]) && $_POST["accion"] == "listarParroquias") {

    $cod_muni = isset($_POST["cod_muni"]) ? intval($_POST["cod_muni"]) : 0;
    $parroquias = $controller->listarParroquias($cod_muni);

    if (!empty($parroquias) && is_array($parroquias)) {
        echo '<option value="">Seleccione una Parroquia</option>';
        foreach ($parroquias as $parroquia) {
            $cod = htmlspecialchars($parroquia["cod_par"] ?? '', ENT_QUOTES, 'UTF-8');
            $nom = htmlspecialchars($parroquia["nombre"] ?? '', ENT_QUOTES, 'UTF-8');
            echo '<option value="'.$cod.'">'.$nom.'</option>';
        }
    } else {
        echo '<option value="">No hay parroquias disponibles</option>';
    }

    exit;
}

// Si no se reconoció la acción, responder algo
header("HTTP/1.1 400 Bad Request");
echo '<option value="">Acción no válida</option>';
?>