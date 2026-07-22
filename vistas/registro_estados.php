<?php
session_start();
include_once "includes/guardian.php";
require_once("../controladores/ctrl_estado.php");

$controlador = new EstadoController();

if (isset($_POST['guardar'])) {
    $controlador->guardar();
}

if (isset($_POST['actualizar'])) {
    $controlador->actualizar();
}

if (isset($_GET['eliminar'])) {
    $controlador->eliminar($_GET['eliminar']);
}

$editar = null;

if (isset($_GET['editar'])) {
    $editar = $controlador->buscar($_GET['editar']);
}

$estados = $controlador->listar();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Estado</title>

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

            <!-- FORMULARIO -->
<!-- FORMULARIO -->
<div class="form-card">

    <form method="POST">

        <input
            type="hidden"
            name="cod_est"
            value="<?= $editar['cod_est'] ?? ''; ?>"
        >

        <div class="full-width">
            <h2 style="text-align:center;">
                <?= $editar ? "Editar Estado" : "Registro de Estado"; ?>
            </h2>
        </div>

        <div class="field full-width">
            <label><strong>Nombre del Estado</strong></label>

            <input
                type="text"
                name="nombre"
                placeholder="Ingrese el estado"
                value="<?= $editar['nombre'] ?? ''; ?>"
                required
            >
        </div>

        <div class="full-width">

            <?php if ($editar) { ?>

                <button
                    type="submit"
                    name="actualizar"
                    class="btn-guardar"
                >
                    Actualizar
                </button>

            <?php } else { ?>

                <button
                    type="submit"
                    name="guardar"
                    class="btn-guardar"
                >
                    Guardar
                </button>

            <?php } ?>

        </div>

    </form>

</div>
        <br>
            <!-- TABLA -->
            <div class="form-card">
                <center>
                 <h2>Estados Registrados</h2>
                </center>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($estados)) { ?>

                            <?php foreach ($estados as $estado) { ?>

                                <tr>
                                    <td>
                                        <?php echo $estado['nombre']; ?>
                                    </td>

                                    <td class="acciones">

                                        <a
                                            href="?editar=<?php echo $estado['cod_est']; ?>"
                                            class="btn-editar"
                                            style="text-decoration: none;"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                            Editar
                                        </a>

                                        <a
                                            href="?eliminar=<?php echo $estado['cod_est']; ?>"
                                            class="btn-eliminar"
                                            style="text-decoration: none;"
                                            onclick="return confirm('¿Desea eliminar este estado?');"
                                        >
                                            <i class="bi bi-trash"></i>
                                            Eliminar
                                        </a>

                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="2" style="text-align: center;">
                                    No hay estados registrados.
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