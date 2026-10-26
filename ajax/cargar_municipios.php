<?php
require_once("../modelos/Parroquia.php");

if(isset($_POST["cod_est"]))
{
    $modelo = new Parroquia();

    $municipios = $modelo->listarMunicipios($_POST["cod_est"]);

    echo '<option value="">Seleccione un Municipio</option>';

    while($fila = mysqli_fetch_assoc($municipios))
    {
        echo '<option value="'.$fila["cod_muni"].'">'.$fila["nombre"].'</option>';
    }
}
?>