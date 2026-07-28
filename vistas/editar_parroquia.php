<?php
session_start();
include_once "includes/guardian.php";
require_once("../controladores/ctrl_parroquia.php");
 
$controller = new ParroquiaController();
 
// Esta pantalla solo sirve para editar: si no llega un id válido, no hay nada que mostrar
if (!isset($_GET["editar"]) || empty($_GET["editar"])) {
    header("Location: registro_parroquia.php");
    exit();
}
 
$editar = mysqli_fetch_assoc(
    $controller->buscar($_GET["editar"])
);
 
// Si el id no corresponde a ninguna parroquia real, regresamos al listado
if (!$editar) {
    header("Location: registro_parroquia.php");
    exit();
}
 
$estados = $controller->listarEstados();
?>
 
<!DOCTYPE html>
<html lang="es">
 
<head>
 
    <meta charset="UTF-8">
    <title>Editar Parroquia</title>
 
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
 
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
 
</head>
 
<body>
 
<div id="customAlert" class="custom-alert hidden">
 
    <div class="alert-box">
 
        <p id="alertMessage"></p>
 
        <button onclick="closeAlert()">
            Aceptar
        </button>
 
    </div>
 
</div>
 
 
<?php include "includes/layout.php"; ?>
 
<!-- ================= CONTENIDO ================= -->
 
<div class="main">
 
    <div class="contenedor-estados">
 
        <!-- ================= FORMULARIO DE EDICIÓN ================= -->
 
        <div class="form-card">
 
            <center>
 
                <h2>
                    Editar Parroquia
                </h2>
 
            </center>
 
            <form
                action="../controladores/ctrl_parroquia.php"
                method="POST"
            >
 
                <input
                    type="hidden"
                    name="accion"
                    value="editar"
                >
 
                <input
                    type="hidden"
                    name="cod_par"
                    value="<?php echo $editar['cod_par']; ?>"
                >
 
                <!-- ESTADO Y MUNICIPIO -->
 
                <div class="fila-ubicacion">
 
                    <div class="field">
 
                        <label>Seleccione el Estado</label>
 
                        <select
                            id="selectEstado"
                            name="cod_est"
                            required
                        >
 
                            <option value="">
                                Seleccione un Estado
                            </option>
 
                            <?php while ($estado = mysqli_fetch_assoc($estados)) { ?>
 
                                <option
                                    value="<?php echo $estado['cod_est']; ?>"
                                    <?php
                                    if (isset($editar['cod_est']) && $editar['cod_est'] == $estado['cod_est']) {
                                        echo "selected";
                                    }
                                    ?>
                                >
 
                                    <?php echo $estado['nombre']; ?>
 
                                </option>
 
                            <?php } ?>
 
                        </select>
 
                    </div>
 
                    <div class="field">
 
                        <label>Seleccione el Municipio</label>
 
                        <select
                            id="selectMunicipio"
                            name="cod_muni"
                            data-selected="<?php echo $editar['cod_muni']; ?>"
                            required
                        >
 
                            <option value="">
                                Seleccione un Municipio
                            </option>
 
                        </select>
 
                    </div>
 
                </div>
 
                <!-- ================= NOMBRE DE LA PARROQUIA ================= -->
 
                <div class="zona-parroquias">
 
                    <div class="field campo-parroquia">
 
                        <label>
                            Nombre de la parroquia
                        </label>
 
                        <input
                            type="text"
                            name="parroquia[]"
                            placeholder="Ingrese la parroquia"
                            required
                            value="<?php echo $editar['nombre']; ?>"
                        >
 
                    </div>
 
                    <div style="display:flex; gap:8px; margin-top:15px;">
 
                        <button
                            type="submit"
                            class="btn-guardar"
                        >
                            Actualizar
                        </button>
 
                        <a
                            href="registro_parroquia.php"
                            class="btn-eliminar"
                            style="text-decoration:none; display:flex; align-items:center; justify-content:center;"
                        >
                            Cancelar
                        </a>
 
                    </div>
 
                </div>
 
            </form>
 
        </div>
 
    </div>
 
</div>
 
<script src="/FUNDACITE/vistas/js/jquery.min.js"></script>
<script src="/FUNDACITE/vistas/js/select2.min.js"></script>
<script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
<script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>
<script src="/FUNDACITE/vistas/js/valid_trabajadores.js"></script>
 
<!-- AJAX PARA CARGAR MUNICIPIOS -->
<script src="/FUNDACITE/vistas/js/ajax_parroquia.js"></script>
 
</body>
 
</html>