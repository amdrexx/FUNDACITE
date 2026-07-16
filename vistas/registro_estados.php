<?php
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
            <div class="form-card">
    <center>
        <h2>
            <?php echo $editar ? "Editar Estado" : "Registro de Estado"; ?>
        </h2>
    </center>

    <form method="POST" class="form-estado">

        <input
            type="hidden"
            name="cod_est"
            value="<?= $editar['cod_est'] ?? ''; ?>"
        >

        <div class="fila-estado">

            <div class="field">
                
                <label>Nombre del Estado</label>

                <input
                    type="text"
                    name="nombre"
                    placeholder="Ingrese el estado"
                    required
                    value="<?= $editar['nombre'] ?? ''; ?>"
                >

            </div>

            <?php if ($editar) { ?>

                <button
                    type="submit"
                    class="btn-guardar full-width"
                    name="actualizar"
                >
                    Actualizar
                </button>

            <?php } else { ?>

                <button
                    type="submit"
                    class="btn-guardar full-width"
                    name="guardar"
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