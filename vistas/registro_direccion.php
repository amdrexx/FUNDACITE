<?php
session_start();
include_once "includes/guardian.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("../controladores/ctrl_direccion.php");

$controller = new DireccionController();

// Listar Estados
$estados = $controller->listarEstados();

// Si se va a editar
$direccion = null;

if (isset($_GET["editar"])) {
    $direccion = $controller->buscar($_GET["editar"]);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <title>Registro de Dirección</title>

    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">

    <script src="/FUNDACITE/vistas/js/jquery.min.js"></script>

</head>

<body>

    <div id="customAlert" class="custom-alert hidden">
        <div class="alert-box">
            <p id="alertMessage"></p>
            <button onclick="closeAlert()">Aceptar</button>
        </div>
    </div>

  <?php include "includes/layout.php"; ?>

<!-- ================= CONTENIDO ================= -->

    <div class="main">

        <div class="contenedor-estados">

            <div class="form-card">

                <center>

                    <h2>

                        <?php
                        echo ($direccion)
                            ? "Editar Dirección"
                            : "Registro de Dirección";
                        ?>

                    </h2>

                </center>

                <form
                    action="../controladores/ctrl_direccion.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="accion"
                        value="<?php echo ($direccion) ? "editar" : "registrar"; ?>"
                    >

                    <input
                        type="hidden"
                        name="id_dir"
                        value="<?php echo ($direccion) ? $direccion["id_dir"] : ""; ?>"
                    >

                    <div class="fila-ubicacion">

                        <div class="field">

                            <label>Estado</label>

                            <select id="selectEstado">

                                <option value="">Seleccione un Estado</option>

                                <?php foreach ($estados as $estado) { ?>

                                    <option value="<?php echo $estado["cod_est"]; ?>">

                                        <?php echo $estado["nombre"]; ?>

                                    </option>

                                <?php } ?>

                            </select>

                        </div>

                        <div class="field">

                            <label>Municipio</label>

                            <select id="selectMunicipio">

                                <option value="">
                                    Seleccione un Municipio
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="fila-ubicacion">

                        <div class="field">

                            <label>Parroquia</label>

                            <select
                                id="selectParroquia"
                                name="cod_par"
                                required
                            >

                                <option value="">
                                    Seleccione una Parroquia
                                </option>

                            </select>

                        </div>

                        <div class="field">

                            <label>Dirección</label>

                            <textarea
                                name="nombre"
                                id="nombre"
                                class="textarea-direccion"
                                required
                            ><?php echo ($direccion) ? $direccion["nombre"] : ""; ?></textarea>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn-guardar full-width"
                    >

                        <?php
                        echo ($direccion)
                            ? "Actualizar"
                            : "Guardar";
                        ?>

                    </button>

                </form>

            </div>
            <?php
$direcciones = $controller->listar();
?>

<br>

<div class="form-card">

    <center>

        <h2>
            Direcciones Registradas
        </h2>

    </center>

    <table class="tabla">

        <thead>

            <tr>

                <th>Estado</th>
                <th>Municipio</th>
                <th>Parroquia</th>
                <th>Dirección</th>
                <th>Acciones</th>

            </tr>

        </thead>

        <tbody>

            <?php if (count($direcciones) > 0) { ?>

                <?php foreach ($direcciones as $fila) { ?>

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

                        <td>
                            <?php echo $fila["direccion"]; ?>
                        </td>

                        <td class="acciones">

                            <a
                                href="Registro_Direccion.php?editar=<?php echo $fila["id_dir"]; ?>"
                                class="btn-editar"
                            >
                                <i class="bi bi-pencil-square"></i>
                                Editar
                            </a>

                            <a
                                href="../controladores/ctrl_direccion.php?eliminar=<?php echo $fila["id_dir"]; ?>"
                                class="btn-eliminar"
                                onclick="return confirm('¿Está seguro de eliminar esta dirección?');"
                            >
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </a>

                        </td>

                    </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>

                    <td colspan="5" style="text-align:center;">

                        No hay direcciones registradas.

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
    <script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>
    <script src="/FUNDACITE/vistas/js/ajax_direccion.js"></script>


</body>

</html>