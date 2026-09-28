<?php
session_start();
include_once "includes/guardian.php";
require_once("../controladores/ctrl_parroquia.php");
$controller = new ParroquiaController();

// Estado que llega por URL: sirve para enfocar el filtro de la tabla
$estadoSeleccionado = isset($_GET['estado']) ? (int) $_GET['estado'] : 0;

// Estados (a arreglo para reutilizarlos en el formulario y en el filtro)
$resultadoEstados = $controller->listarEstados();
$estados = mysqli_fetch_all($resultadoEstados, MYSQLI_ASSOC);

// Parroquias (todas; el filtrado y la paginación se hacen en el cliente)
$resultado = $controller->listar();
$parroquias = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
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

    <!-- ================= CABECERA DEL MÓDULO ================= -->

    <div class="cabecera-modulo">

        <i class="bi bi-geo-alt-fill"></i>

        <h1>REGISTRO DE PARROQUIAS</h1>

    </div>

    <!-- ================= FORMULARIO ================= -->

    <div class="modulo-card">

        <form
            action="../controladores/ctrl_parroquia.php"
            method="POST"
        >

            <input
                type="hidden"
                name="accion"
                value="registrar"
            >

            <!-- ESTADO, MUNICIPIO Y PARROQUIA -->

            <div class="form-registro">

                <div class="field">

                    <label>Estado</label>

                    <select
                        id="selectEstado"
                        name="cod_est"
                        required
                    >

                        <option value="">
                            Seleccione un Estado
                        </option>

                        <?php foreach ($estados as $estado) { ?>

                            <option
                                value="<?php echo $estado['cod_est']; ?>"
                                <?php if ((int) $estado['cod_est'] === $estadoSeleccionado) { echo 'selected'; } ?>
                            >
                                <?php echo htmlspecialchars($estado['nombre']); ?>
                            </option>

                        <?php } ?>

                    </select>

                </div>

                <div class="field">

                    <label>Municipio</label>

                    <select
                        id="selectMunicipio"
                        name="cod_muni"
                        required
                    >

                        <option value="">
                            Seleccione un Municipio
                        </option>

                    </select>

                </div>

                <div class="field">

                    <label>Parroquia</label>

                    <div id="contenedorParroquias">

                        <div class="campo-parroquia">

                            <input
                                type="text"
                                name="parroquia[]"
                                placeholder="Ingrese la parroquia"
                                required
                            >

                        </div>

                    </div>

                </div>

            </div>

            <!-- BOTONES DEL FORMULARIO -->

            <div class="form-acciones">

                <div class="grupo-campos-multi">

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

                <button
                    type="submit"
                    class="btn-primario"
                >
                    <i class="bi bi-plus-lg"></i>

                    Registrar
                </button>

            </div>

        </form>

    </div>

    <!-- ================= TABLA ================= -->

    <div class="modulo-card" id="moduloUbicacion" data-etiqueta="parroquias">

        <!-- BUSCADOR Y FILTRO POR ESTADO -->
        <div class="toolbar-tabla">

            <div class="input-icon">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    class="input-tabla"
                    id="buscadorRegistros"
                    placeholder="Buscar parroquia..."
                >

            </div>

            <label class="filtro-label" for="filtroEstado">Estado</label>

            <select class="filtro-select" id="filtroEstado">

                <option value="">Todos</option>

                <?php foreach ($estados as $estado) { ?>

                    <option
                        value="<?php echo $estado['cod_est']; ?>"
                        <?php if ((int) $estado['cod_est'] === $estadoSeleccionado) { echo 'selected'; } ?>
                    >
                        <?php echo htmlspecialchars($estado['nombre']); ?>
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

                    <th>Parroquia</th>

                    <th style="text-align:center;">Acciones</th>

                </tr>

            </thead>

            <tbody id="listaRegistros">

                <?php foreach ($parroquias as $fila) { ?>

                    <tr
                        data-estado="<?php echo $fila['cod_est']; ?>"
                        data-busqueda="<?php echo htmlspecialchars($fila['parroquia'] . ' ' . $fila['municipio'] . ' ' . $fila['estado']); ?>"
                    >

                        <td>
                            <?php echo htmlspecialchars($fila['estado']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($fila['municipio']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($fila['parroquia']); ?>
                        </td>

                        <td class="acciones">

                            <a
                                href="editar_parroquia.php?editar=<?php echo $fila['cod_par']; ?>&estado=<?php echo $fila['cod_est']; ?>"
                                class="btn-editar"
                            >
                                <i class="bi bi-pencil-square"></i>
                                Editar
                            </a>

                            <a
                                href="../controladores/ctrl_parroquia.php?eliminar=<?php echo $fila['cod_par']; ?>&estado=<?php echo $fila['cod_est']; ?>"
                                class="btn-eliminar"
                                onclick="return confirm('¿Desea eliminar esta parroquia?');"
                            >
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </a>

                        </td>

                    </tr>

                <?php } ?>

                <tr id="filaVacia" style="display:none;">

                    <td colspan="4" class="sin-registros">

                        No se encontraron parroquias.

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

<!-- AJAX PARA CARGAR MUNICIPIOS -->
<script src="/FUNDACITE/vistas/js/ajax_parroquia.js"></script>

<!-- AGREGAR Y ELIMINAR CAMPOS -->
<script src="/FUNDACITE/vistas/js/eliminar_campo.js"></script>

<!-- BUSCADOR, FILTRO POR ESTADO Y PAGINACIÓN DE LA TABLA -->
<script src="/FUNDACITE/vistas/js/filtrar_tabla_ubicacion.js"></script>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const mensajes = {
        restrict: "No se puede eliminar: esta parroquia tiene direcciones registradas.",
        notfound: "El registro que intentaste eliminar ya no existe.",
        error: "Ocurrió un error al eliminar el registro.",
        ok: "Parroquia eliminada correctamente."
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
