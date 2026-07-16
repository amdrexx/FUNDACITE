<?php
require_once("../controladores/ctrl_direccion.php");

$controller = new DireccionController();

/*=========================================
CARGAR ESTADOS
=========================================*/
if (isset($_POST["accion"]) && $_POST["accion"] == "listarEstados") {

    $estados = $controller->listarEstados();

    echo '<option value="">Seleccione un Estado</option>';

    foreach ($estados as $estado) {

        echo '<option value="'.$estado["cod_est"].'">'.$estado["nombre"].'</option>';

    }

    exit;
}

/*=========================================
CARGAR MUNICIPIOS
=========================================*/
if (isset($_POST["accion"]) && $_POST["accion"] == "listarMunicipios") {

    $municipios = $controller->listarMunicipios($_POST["cod_est"]);

    echo '<option value="">Seleccione un Municipio</option>';

    foreach ($municipios as $municipio) {

        echo '<option value="'.$municipio["cod_muni"].'">'.$municipio["nombre"].'</option>';

    }

    exit;
}

/*=========================================
CARGAR PARROQUIAS
=========================================*/
if (isset($_POST["accion"]) && $_POST["accion"] == "listarParroquias") {

    $parroquias = $controller->listarParroquias($_POST["cod_muni"]);

    echo '<option value="">Seleccione una Parroquia</option>';

    foreach ($parroquias as $parroquia) {

        echo '<option value="'.$parroquia["cod_par"].'">'.$parroquia["nombre"].'</option>';

    }

    exit;
}
?>