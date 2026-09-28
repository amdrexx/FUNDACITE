<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once "includes/roles.php";
include_once "includes/guardian.php";
require_once("../controladores/ctrl_municipio.php");

$controlador = new MunicipioController();

// Estado que llega por URL: sirve para enfocar el filtro de la tabla
$estadoSeleccionado = isset($_GET['estado']) ? (int) $_GET['estado'] : 0;

// Guardar
if (isset($_POST['guardar'])) {

    $cod_est = $_POST['cod_est'];
    $nombre  = trim($_POST['nombre']);

    if (!empty($cod_est) && !empty($nombre)) {

        $controlador->guardar($cod_est, $nombre);

        header("Location: registro_municipios.php?estado=" . (int) $cod_est);
        exit();
    }
}

// Actualizar
if (isset($_POST['actualizar'])) {

    $id      = $_POST['cod_muni'];
    $cod_est = $_POST['cod_est'];
    $nombre  = trim($_POST['nombre']);

    $controlador->actualizar($id, $cod_est, $nombre);

    if (ctype_digit((string) $cod_est) && (int) $cod_est > 0) {
        header("Location: registro_municipios.php?estado=" . (int) $cod_est);
    } else {
        header("Location: registro_municipios.php");
    }
    exit();
}

// Eliminar
if (isset($_GET['eliminar'])) {

    $resultado = $controlador->eliminar($_GET['eliminar']);

    $mensaje = "ok";

    if ($resultado === "RESTRICT") {
        $mensaje = "restrict";
    } elseif ($resultado === "NOT_FOUND") {
        $mensaje = "notfound";
    } elseif ($resultado === false) {
        $mensaje = "error";
    }

    $destino = "registro_municipios.php?msg=" . $mensaje;

    if ($estadoSeleccionado > 0) {
        $destino .= "&estado=" . $estadoSeleccionado;
    }

    header("Location: " . $destino);
    exit();
}

// Editar
$municipioEditar = null;

if (isset($_GET['editar'])) {

    $municipioEditar = $controlador->buscar($_GET['editar']);
}

// Listar Estados (formulario de registro y filtro de la tabla)
$estados = $controlador->obtenerEstados();

// Listar Municipios (todos; el filtrado y la paginación se hacen en el cliente)
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

        <!-- ================= CABECERA DEL MÓDULO ================= -->

        <div class="cabecera-modulo">

            <i class="bi bi-map"></i>

            <h1>REGISTRO DE MUNICIPIOS</h1>

        </div>

        <!-- ================= FORMULARIO ================= -->

        <div class="modulo-card">

            <?php if ($municipioEditar) { ?>

                <h3 class="titulo-card">Editar Municipio</h3>

            <?php } ?>

            <form method="POST">

                <?php if ($municipioEditar) { ?>

                    <input
                        type="hidden"
                        name="cod_muni"
                        value="<?= $municipioEditar['cod_muni']; ?>"
                    >

                <?php } ?>

                <div class="form-registro">

                    <!-- ESTADO -->
                    <div class="field">

                        <label>Estado</label>

                        <select name="cod_est" required>

                            <option value="">Seleccione un Estado</option>

                            <?php foreach ($estados as $estado) { ?>

                                <option
                                    value="<?= $estado['cod_est']; ?>"
                                    <?php if ($municipioEditar) { ?>
                                        <?= ($municipioEditar['cod_est'] == $estado['cod_est']) ? 'selected' : ''; ?>
                                    <?php } else { ?>
                                        <?= ($estadoSeleccionado == $estado['cod_est']) ? 'selected' : ''; ?>
                                    <?php } ?>
                                >
                                    <?= $estado['nombre']; ?>
                                </option>

                            <?php } ?>

                        </select>

                    </div>

                    <!-- MUNICIPIO -->
                    <div class="field">

                        <label>Municipio</label>

                        <input
                            type="text"
                            name="nombre"
                            placeholder="Ingrese el municipio"
                            value="<?= $municipioEditar['nombre'] ?? ''; ?>"
                            required
                        >

                    </div>

                </div>

                <!-- BOTONES DEL FORMULARIO -->
                <?php if ($municipioEditar) { ?>

                    <div class="form-acciones">

                        <a
                            href="registro_municipios.php?estado=<?= (int) $municipioEditar['cod_est']; ?>"
                            class="btn-eliminar"
                        >
                            <i class="bi bi-x-circle"></i>

                            Cancelar
                        </a>

                        <button
                            type="submit"
                            name="actualizar"
                            class="btn-primario"
                        >
                            <i class="bi bi-check-lg"></i>

                            Actualizar
                        </button>

                    </div>

                <?php } else { ?>

                    <div class="form-acciones" style="justify-content:center;">

                        <button
                            type="submit"
                            name="guardar"
                            class="btn-primario"
                        >
                            <i class="bi bi-plus-lg"></i>

                            Registrar
                        </button>

                    </div>

                <?php } ?>

            </form>

        </div>

        <!-- ================= TABLA ================= -->

        <div class="modulo-card" id="moduloUbicacion" data-etiqueta="municipios">

            <!-- BUSCADOR Y FILTRO POR ESTADO -->
            <div class="toolbar-tabla">

                <div class="input-icon">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        class="input-tabla"
                        id="buscadorRegistros"
                        placeholder="Buscar municipio..."
                    >

                </div>

                <label class="filtro-label" for="filtroEstado">Estado</label>

                <select class="filtro-select" id="filtroEstado">

                    <option value="">Todos</option>

                    <?php foreach ($estados as $estado) { ?>

                        <option
                            value="<?= $estado['cod_est']; ?>"
                            <?= ($estadoSeleccionado == $estado['cod_est']) ? 'selected' : ''; ?>
                        >
                            <?= htmlspecialchars($estado['nombre']); ?>
                        </option>

                    <?php } ?>

                </select>

            </div>

            <!-- LISTADO -->
            <table class="tabla tabla-modulo">

                <thead>

                    <tr>

                        <th>Estado</th>

                        <th>Municipio</th>

                        <th style="text-align:center;">Acciones</th>

                    </tr>

                </thead>

                <tbody id="listaRegistros">

                    <?php foreach ($municipios as $municipio) { ?>

                        <tr
                            data-estado="<?= $municipio['cod_est']; ?>"
                            data-busqueda="<?= htmlspecialchars($municipio['municipio'] . ' ' . $municipio['estado']); ?>"
                        >

                            <td>

                                <?= htmlspecialchars($municipio['estado']); ?>

                            </td>

                            <td>

                                <?= htmlspecialchars($municipio['municipio']); ?>

                            </td>

                            <td class="acciones">

                                <!-- BOTÓN EDITAR -->
                                <button
                                    type="button"
                                    class="btn-editar"
                                    onclick="window.location.href='registro_municipios.php?editar=<?= $municipio['cod_muni']; ?>&estado=<?= $municipio['cod_est']; ?>';"
                                >

                                    <i class="bi bi-pencil-square"></i>

                                    Editar

                                </button>

                                <!-- BOTÓN ELIMINAR -->
                                <button
                                    type="button"
                                    class="btn-eliminar"
                                    onclick="if(confirm('¿Desea eliminar este municipio?')){window.location.href='registro_municipios.php?eliminar=<?= $municipio['cod_muni']; ?>&estado=<?= $municipio['cod_est']; ?>';}"
                                >

                                    <i class="bi bi-trash"></i>

                                    Eliminar

                                </button>

                            </td>

                        </tr>

                    <?php } ?>

                    <tr id="filaVacia" style="display:none;">

                        <td colspan="3" class="sin-registros">

                            No se encontraron municipios.

                        </td>

                    </tr>

                </tbody>

            </table>

            <!-- PIE: CONTADOR Y PAGINACIÓN -->
            <div class="paginacion-fila">

                <span id="infoRegistros"></span>

                <div class="paginacion" id="paginacion"></div>

            </div>

        </div>

    </div>

    <script src="/FUNDACITE/vistas/js/jquery.min.js"></script>
    <script src="/FUNDACITE/vistas/js/select2.min.js"></script>
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>
    <script src="/FUNDACITE/vistas/js/valid_trabajadores.js"></script>

    <!-- BUSCADOR, FILTRO POR ESTADO Y PAGINACIÓN DE LA TABLA -->
    <script src="/FUNDACITE/vistas/js/filtrar_tabla_ubicacion.js"></script>

<?php if (isset($_GET['msg'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const mensajes = {
            restrict: "No se puede eliminar: este municipio tiene parroquias registradas.",
            notfound: "El registro que intentaste eliminar ya no existe.",
            error: "Ocurrió un error al eliminar el registro.",
            ok: "Municipio eliminado correctamente."
        };

        const tipo = "<?= htmlspecialchars($_GET['msg']) ?>";

        if (mensajes[tipo]) {
            document.getElementById("alertMessage").innerText = mensajes[tipo];
            document.getElementById("customAlert").classList.remove("hidden");
        }
    });
</script>
<?php endif; ?>

</body>

</html>
