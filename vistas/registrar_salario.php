<?php

session_start();

include_once "includes/guardian.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../conexion.php");
require_once("../controladores/ctrl_salario.php");



$salarios = listarSalarios($conexion);



$editar = null;

if (isset($_GET['editar'])) {

    $editar = buscarSalario(
        $conexion,
        intval($_GET['editar'])
    );

}



$cargos = [];

$sqlCargos = "
    SELECT id_cargo, nombre_cargo
    FROM CARGO
    ORDER BY nombre_cargo ASC
";

$resultCargos = $conexion->query($sqlCargos);

if ($resultCargos) {

    while ($cargo = $resultCargos->fetch_assoc()) {

        $cargos[] = $cargo;

    }

}



$errores = $_SESSION['errores'] ?? [];

$exito = $_SESSION['exito'] ?? '';

$old = $_SESSION['old'] ?? [];


unset($_SESSION['errores']);
unset($_SESSION['exito']);
unset($_SESSION['old']);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>
        Registro de Salario
    </title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">

    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>


<body>

<div
    id="customAlert"
    class="custom-alert hidden"
>

    <div class="alert-box">

        <p id="alertMessage"></p>

        <button
            type="button"
            onclick="closeAlert()"
        >
            Aceptar
        </button>

    </div>

</div>




<?php include "includes/layout.php"; ?>



<div class="main">

    <div class="form-card">


        <form
            id="formsalario"
            method="POST"
            action="../controladores/ctrl_salario.php"
        >



            <center>

                <h2>

                    <?= $editar
                        ? "Editar Salario"
                        : "Registro de Salario";
                    ?>

                </h2>

            </center>



            <?php if ($editar): ?>

                <input
                    type="hidden"
                    name="id_salario"
                    value="<?= htmlspecialchars(
                        $editar['id_salario']
                    ); ?>"
                >

            <?php endif; ?>


            <div class="field full-width">

                <label for="tipo_salario">

                    Tipo de salario

                </label>


                <select
                    name="tipo_salario"
                    id="tipo_salario"
                    required
                    onchange="cambiarTipoSalario()"
                >

                    <option value="">

                        Seleccione un tipo

                    </option>


                    <option
                        value="base"
                        <?= (
                            ($editar['tipo_salario']
                            ?? $old['tipo_salario']
                            ?? '') === 'base'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        Sueldo base

                    </option>


                    <option
                        value="cargo"
                        <?= (
                            ($editar['tipo_salario']
                            ?? $old['tipo_salario']
                            ?? '') === 'cargo'
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        Sueldo por cargo

                    </option>

                </select>

            </div>


            <!-- =========================================
                 CARGO
                 ========================================= -->

            <div
                class="field full-width"
                id="campo-cargo"
            >

                <label for="id_cargo">

                    Cargo

                </label>


                <select
                    name="id_cargo"
                    id="id_cargo"
                >

                    <option value="">

                        Seleccione un cargo

                    </option>


                    <?php foreach ($cargos as $cargo): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $cargo['id_cargo']
                            ); ?>"
                            <?= (
                                ($editar['id_cargo']
                                ?? $old['id_cargo']
                                ?? '')
                                == $cargo['id_cargo']
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >

                            <?= htmlspecialchars(
                                $cargo['nombre_cargo']
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="field">

                <label for="fecha">

                    Fecha de ingreso

                </label>


                <input
                    type="date"
                    name="fecha"
                    id="fecha"
                    required
                    value="<?= htmlspecialchars(
                        $editar['fecha']
                        ?? $old['fecha']
                        ?? ''
                    ); ?>"
                >

            </div>



            <div class="field campo-monto">

                <label for="monto">

                    Monto

                </label>


                <input
                    type="number"
                    name="monto"
                    id="monto"
                    step="0.01"
                    min="0"
                    required
                    placeholder="Ej. 5000.00"
                    value="<?= htmlspecialchars(
                        $editar['monto']
                        ?? $old['monto']
                        ?? ''
                    ); ?>"
                >


                <span class="sufijo-bs">

                    Bs

                </span>

            </div>


            <div class="full-width">

                <button
                    type="submit"
                    name="accion"
                    value="<?= $editar
                        ? 'actualizar'
                        : 'guardar';
                    ?>"
                    class="btn-guardar"
                >

                    <?= $editar
                        ? "Actualizar"
                        : "Guardar";
                    ?>

                </button>


                <button
                    type="reset"
                    class="btn-guardar"
                    id="btnLimpiar"
                >

                    Limpiar

                </button>

            </div>


        </form>

    </div>


    <!-- =================================================
         CATÁLOGO DE SALARIOS
         ================================================= -->

    <div class="form-card">


        <center>

            <h2>
                Lista de Salarios
            </h2>

        </center>


        <table class="tabla">


            <thead>

                <tr>

                    <th>
                        Tipo
                    </th>

                    <th>
                        Cargo
                    </th>

                    <th>
                        Fecha
                    </th>

                    <th>
                        Monto
                    </th>

                    <th>
                        Estado
                    </th>

                    <th>
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (!empty($salarios)): ?>


                <?php foreach ($salarios as $fila): ?>

                    <tr>


                        <!-- =============================
                             TIPO
                             ============================= -->

                        <td>

                            <?php

                            if (
                                isset(
                                    $fila['tipo_salario']
                                )
                                &&
                                $fila['tipo_salario'] === 'cargo'
                            ) {

                                echo "Sueldo por cargo";

                            } else {

                                echo "Sueldo base";

                            }

                            ?>

                        </td>


                        <!-- =============================
                             CARGO
                             ============================= -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $fila['nombre_cargo']
                                )
                            ) {

                                echo htmlspecialchars(
                                    $fila['nombre_cargo']
                                );

                            } else {

                                echo "—";

                            }

                            ?>

                        </td>


                        <!-- =============================
                             FECHA
                             ============================= -->

                        <td>

                            <?= htmlspecialchars(
                                $fila['fecha']
                            ); ?>

                        </td>


                        <!-- =============================
                             MONTO
                             ============================= -->

                        <td>

                            Bs
                            <?= number_format(
                                $fila['monto'],
                                2,
                                ',',
                                '.'
                            ); ?>

                        </td>


                        <!-- =============================
                             ESTADO
                             ============================= -->

                        <td>

                            <?= htmlspecialchars(
                                $fila['estado']
                            ); ?>

                        </td>


                        <!-- =============================
                             ACCIONES
                             ============================= -->

                        <td class="acciones">


                            <a
                                href="registrar_salario.php?editar=<?= $fila['id_salario']; ?>"
                                class="btn-editar"
                            >

                                <i class="bi bi-pencil-square"></i>

                                Editar

                            </a>


                            <a
                                href="../controladores/ctrl_salario.php?eliminar=<?= $fila['id_salario']; ?>"
                                class="btn-eliminar"
                                onclick="return confirm('¿Desea eliminar este salario?');"
                            >

                                <i class="bi bi-trash"></i>

                                Eliminar

                            </a>


                        </td>


                    </tr>

                <?php endforeach; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="6"
                        style="text-align:center;"
                    >

                        No hay salarios registrados.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>


    </div>


</div>


<script>


/* =====================================================
   MOSTRAR / OCULTAR CARGO
   ===================================================== */

function cambiarTipoSalario() {

    const tipo =
        document.getElementById("tipo_salario");

    const campoCargo =
        document.getElementById("campo-cargo");

    const cargo =
        document.getElementById("id_cargo");


    if (!tipo || !campoCargo || !cargo) {

        return;

    }


    /* =================================================
       SUELDO BASE
       ================================================= */

    if (tipo.value === "base") {

        campoCargo.style.display = "none";

        cargo.value = "";

        cargo.disabled = true;

        cargo.required = false;

    }


    /* =================================================
       SUELDO POR CARGO
       ================================================= */

    else if (tipo.value === "cargo") {

        campoCargo.style.display = "flex";

        cargo.disabled = false;

        cargo.required = true;

    }


    /* =================================================
       SIN SELECCIÓN
       ================================================= */

    else {

        campoCargo.style.display = "none";

        cargo.value = "";

        cargo.disabled = true;

        cargo.required = false;

    }

}


/* =====================================================
   ALERTA
   ===================================================== */

function closeAlert() {

    const alerta =
        document.getElementById("customAlert");

    if (alerta) {

        alerta.classList.add("hidden");

    }

}


/* =====================================================
   AL CARGAR LA PÁGINA
   ===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function() {


        /* ---------------------------------------------
           Revisar tipo de salario
           --------------------------------------------- */

        cambiarTipoSalario();


        /* ---------------------------------------------
           Botón limpiar
           --------------------------------------------- */

        const formulario =
            document.getElementById("formsalario");


        if (formulario) {

            formulario.addEventListener(
                "reset",
                function() {

                    setTimeout(
                        function() {

                            cambiarTipoSalario();

                        },
                        0
                    );

                }
            );

        }

    }
);

</script>


<!-- =====================================================
     MOSTRAR ERRORES
     ===================================================== -->

<?php if (!empty($errores)): ?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const mensaje =
            document.getElementById("alertMessage");

        const alerta =
            document.getElementById("customAlert");


        if (mensaje && alerta) {

            mensaje.textContent =
                <?= json_encode(
                    implode("\n", $errores)
                ); ?>;

            alerta.classList.remove("hidden");

        }

    }
);

</script>

<?php endif; ?>


<!-- =====================================================
     MOSTRAR ÉXITO
     ===================================================== -->

<?php if (!empty($exito)): ?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const mensaje =
            document.getElementById("alertMessage");

        const alerta =
            document.getElementById("customAlert");


        if (mensaje && alerta) {

            mensaje.textContent =
                <?= json_encode($exito); ?>;

            alerta.classList.remove("hidden");

        }

    }
);

</script>

<?php endif; ?>


</body>

</html>