<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once "includes/roles.php";
include_once "includes/guardian.php";
require_once("../controladores/ctrl_municipio.php");

$controlador = new MunicipioController();

// Guardar
if (isset($_POST['guardar'])) {

    $cod_est = $_POST['cod_est'];
    $nombre  = trim($_POST['nombre']);

    if (!empty($cod_est) && !empty($nombre)) {

        $controlador->guardar($cod_est, $nombre);

        header("Location: registro_municipios.php");
        exit();
    }
}

// Actualizar
if (isset($_POST['actualizar'])) {

    $id      = $_POST['cod_muni'];
    $cod_est = $_POST['cod_est'];
    $nombre  = trim($_POST['nombre']);

    $controlador->actualizar($id, $cod_est, $nombre);

    header("Location: registro_municipios.php");
    exit();
}

// Eliminar
if (isset($_GET['eliminar'])) {

    $controlador->eliminar($_GET['eliminar']);

    header("Location: registro_municipios.php");
    exit();
}

// Editar
$municipioEditar = null;

if (isset($_GET['editar'])) {

    $municipioEditar = $controlador->buscar($_GET['editar']);
}

// Listar Estados
$estados = $controlador->obtenerEstados();

// Listar Municipios
$municipios = $controlador->listar();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Registro de Municipios</title>

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

            <button onclick="closeAlert()">Aceptar</button>

        </div>

    </div>


<?php include "includes/layout.php"; ?>

    <div class="main">

        <div class="contenedor-estados">

<!-- ================= FORMULARIO ================= -->

<div class="form-card">

    <form method="POST">

        <?php if ($municipioEditar) { ?>

            <input
                type="hidden"
                name="cod_muni"
                value="<?= $municipioEditar['cod_muni']; ?>"
            >

        <?php } ?>

        <!-- TÍTULO -->
        <div class="full-width">
            <h2 style="text-align:center;">
                <?= $municipioEditar ? "Editar Municipio" : "Registro de Municipio"; ?>
            </h2>
        </div>

        <!-- ESTADO -->
        <div class="field">

            <label>Seleccione el Estado</label>

            <select name="cod_est" required>

                <option value="">Seleccione un Estado</option>

                <?php foreach ($estados as $estado) { ?>

                    <option
                        value="<?= $estado['cod_est']; ?>"
                        <?= ($municipioEditar && $municipioEditar['cod_est'] == $estado['cod_est']) ? 'selected' : ''; ?>
                    >
                        <?= $estado['nombre']; ?>
                    </option>

                <?php } ?>

            </select>

        </div>

        <!-- MUNICIPIO -->
        <div class="field">

            <label>Nombre del Municipio</label>

            <input
                type="text"
                name="nombre"
                placeholder="Ingrese el municipio"
                value="<?= $municipioEditar['nombre'] ?? ''; ?>"
                required
            >

        </div>

        <!-- BOTÓN -->
        <div class="full-width" style="text-align:center;">

            <?php if ($municipioEditar) { ?>

                <button
                    type="submit"
                    name="actualizar"
                    class="btn-guardar"
                    style="max-width:350px;"
                >
                    Actualizar Municipio
                </button>

            <?php } else { ?>

                <button
                    type="submit"
                    name="guardar"
                    class="btn-guardar"
                    style="max-width:350px;"
                >
                    Guardar Municipio
                </button>

            <?php } ?>

        </div>

    </form>

</div>

            <br>

            <div class="form-card">

                <center>

                    <h2>Lista de Municipios</h2>

                </center>
                <table class="tabla">

                    <thead>

                        <tr>

                            <th>Estado</th>
                            <th>Municipio</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($municipios as $municipio) { ?>

                            <tr>

                                <td>

                                    <?php echo $municipio['estado']; ?>

                                </td>

                                <td>

                                    <?php echo $municipio['municipio']; ?>

                                </td>

                                <td class="acciones">

                                    <!-- BOTÓN EDITAR -->

                                    <button
                                        type="button"
                                        class="btn-editar"
                                        onclick="window.location.href='registro_municipios.php?editar=<?php echo $municipio['cod_muni']; ?>';"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                        Editar

                                    </button>

                                    <!-- BOTÓN ELIMINAR -->

                                    <button
                                        type="button"
                                        class="btn-eliminar"
                                        onclick="if(confirm('¿Desea eliminar este municipio?')){window.location.href='registro_municipios.php?eliminar=<?php echo $municipio['cod_muni']; ?>';}"
                                    >

                                        <i class="bi bi-trash"></i>

                                        Eliminar

                                    </button>

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
    <script src="/FUNDACITE/vistas/js/parroquia.js"></script>

</body>

</html>