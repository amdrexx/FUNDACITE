<?php
session_start();
include_once "includes/guardian.php";
require_once("../controladores/ctrl_estado.php");
require_once("includes/validar_ubicacion.php");

$controlador = new EstadoController();

// Validación del nombre antes de guardar o actualizar
$errorValidacion = '';

if (isset($_POST['guardar']) || isset($_POST['actualizar'])) {
    $_POST['nombre'] = normalizarNombreUbicacion($_POST['nombre'] ?? '');
    $errorValidacion = validarNombreUbicacion($_POST['nombre'], 'del estado');
}

if (isset($_POST['guardar']) && $errorValidacion === '') {
    $controlador->guardar();
}

if (isset($_POST['actualizar']) && $errorValidacion === '') {
    $controlador->actualizar();
}

if (isset($_GET['eliminar'])) {
    $controlador->eliminar($_GET['eliminar']);
}

$editar = null;

if (isset($_GET['editar'])) {
    $editar = $controlador->buscar($_GET['editar']);
}

// Valor que se muestra en el campo: lo digitado (si hubo error) o lo guardado
$nombreForm = $_POST['nombre'] ?? ($editar['nombre'] ?? '');

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

    <?php include "includes/alerta.php"; ?>
    
  <?php include "includes/layout.php"; ?>

    <div class="main">
        <div class="contenedor-estados">


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
                value="<?= htmlspecialchars($nombreForm); ?>"
                maxlength="50"
                autocomplete="off"
                data-validar-ubicacion
                data-etiqueta="del estado"
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
    
    <script src="/FUNDACITE/vistas/js/parroquia.js"></script>
    <script src="/FUNDACITE/vistas/js/validar_ubicacion.js"></script>
    
<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const mensajes = {
        restrict: "No se puede eliminar: este estado tiene municipios registrados.",
        notfound: "El registro que intentaste eliminar ya no existe.",
        error: "Ocurrió un error al eliminar el registro.",
        ok: "Estado eliminado correctamente."
    };

    const tipo = "<?= htmlspecialchars($_GET['msg']) ?>";
    if (mensajes[tipo]) {
        document.getElementById("alertMessage").innerText = mensajes[tipo];
        document.getElementById("customAlert").classList.remove("hidden");
    }
});
</script>
<?php endif; ?>
<?php if (!empty($errorValidacion)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const mensaje = document.getElementById("alertMessage");
    const alerta  = document.getElementById("customAlert");
    if (mensaje && alerta) {
        mensaje.innerText = <?= json_encode($errorValidacion) ?>;
        alerta.classList.remove("hidden");
    }
});
</script>
<?php endif; ?>
<script src="/FUNDACITE/vistas/js/valid_estados.js?v=20261029"></script>
</body>

</html>