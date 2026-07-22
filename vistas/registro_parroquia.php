<?php
session_start();
include_once "includes/guardian.php";
require_once("../controladores/ctrl_parroquia.php");
$controller = new ParroquiaController();
$estados = $controller->listarEstados();

$editar = null;

if (isset($_GET["editar"])) {
    $editar = mysqli_fetch_assoc(
        $controller->buscar($_GET["editar"])
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <title>Registro de Parroquia</title>

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

        <!-- ================= FORMULARIO ================= -->

        <div class="form-card">

            <center>

                <h2>

                    <?php
                    echo $editar
                        ? "Editar Parroquia"
                        : "Registro de Parroquia";
                    ?>

                </h2>

            </center>

            <form
                action="../controladores/ctrl_parroquia.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="<?php echo $editar ? 'editar' : 'registrar'; ?>"
                >

                <?php if ($editar) { ?>

                    <input
                        type="hidden"
                        name="cod_par"
                        value="<?php echo $editar['cod_par']; ?>"
                    >

                <?php } ?>

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
                                    if (
                                        $editar &&
                                        isset($editar['cod_est']) &&
                                        $editar['cod_est'] == $estado['cod_est']
                                    ) {
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
                            data-selected="<?php echo $editar ? $editar['cod_muni'] : ''; ?>"
                            required
                        >

                            <option value="">
                                Seleccione un Municipio
                            </option>

                        </select>

                    </div>

                </div>
<!-- ================= CAMPOS DE PARROQUIAS ================= -->

                <div class="zona-parroquias">

                    <div id="contenedorParroquias">

                        <div class="field campo-parroquia">

                            <label>
                                Nombre de la parroquia
                            </label>

                            <input
                                type="text"
                                name="parroquia[]"
                                placeholder="Ingrese la parroquia"
                                required
                                value="<?php echo $editar ? $editar['nombre'] : ''; ?>"
                            >

                        </div>

                    </div>

                    <?php if (!$editar) { ?>
                    <div
                        class="botones-parroquia"
                        style="display:flex; gap:8px; margin-top:10px;"
                    >

                        <button
                            type="button"
                            id="btnAgregar"
                            class="btn-editar"
                        >
                            <i class="bi bi-plus-circle"></i>
                            Añadir parroquia
                        </button>

                        <button
                            type="button"
                            id="btnEliminarParroquia"
                            class="btn-eliminar"
                        >
                            <i class="bi bi-dash-circle"></i>
                            Eliminar parroquia
                        </button>

                    </div>
                    <?php } ?>

                    <button
                        type="submit"
                        class="btn-guardar"
                    >

                        <?php
                        echo $editar
                            ? "Actualizar"
                            : "Guardar";
                        ?>

                    </button>

                </div>

            </form>

        </div>

        <!-- ================= Lista================= -->

        <div class="form-card">

            <center>

                <h2>
                    Lista de Parroquias
                </h2>

            </center>

            <table class="tabla">

                <thead>

                    <tr>

                        <th>Estado</th>
                        <th>Municipio</th>
                        <th>Parroquia</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php

                $parroquias = $controller->listar();

                while ($fila = mysqli_fetch_assoc($parroquias)) {

                ?>

                    <tr>

                        <td>
                            <?php echo $fila["estado"]; ?>
                        </td>

                        <td>
                            <?php echo $fila["municipio"]; ?>
                        </td>

                        <td>
                            <?php echo $fila["parroquia"]; ?>
                        </td>

                        <td class="acciones">

                            <a
                                href="registro_parroquia.php?editar=<?php echo $fila['cod_par']; ?>"
                                class="btn-editar"
                            >
                                <i class="bi bi-pencil-square"></i>
                                Editar
                            </a>

                            <a
                                href="../controladores/ctrl_parroquia.php?eliminar=<?php echo $fila['cod_par']; ?>"
                                class="btn-eliminar"
                                onclick="return confirm('¿Desea eliminar esta parroquia?');"
                            >
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </a>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

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

<!-- AGREGAR Y ELIMINAR CAMPOS -->
<script src="/FUNDACITE/vistas/js/eliminar_campo.js"></script>

</body>

</html>