<?php
session_start();
include_once "includes/guardian.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("../conexion.php");
require_once("../controladores/ctrl_salario.php");

$salarios = listarSalarios($conexion);

$errores = $_SESSION['errores'] ?? [];
$exito = $_SESSION['exito'] ?? '';

unset($_SESSION['errores']);
unset($_SESSION['exito']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Salario</title>

    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">

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

<!-- ================= CONTENIDO ================= -->

<div class="main">

    <div style="display:flex; flex-direction:column; gap:30px;">

        <!-- ================= FORMULARIO ================= -->

        <form class="form-card"
              id="formsalario"
              method="POST"
              action="../controladores/ctrl_salario.php"
              novalidate>

            <div class="form-grid">

                <h2 style="text-align:center;">
                    Registro de Salario
                </h2>

                <div class="field">
                    <label>Fecha de ingreso</label>

                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        value="<?= $_SESSION['old']['fecha'] ?? '' ?>">
                </div>

                <div class="field">

                    <label>Monto</label>

                    <input
                        type="number"
                        name="monto"
                        id="monto"
                        step="0.01"
                        value="<?= $_SESSION['old']['monto'] ?? '' ?>">

                </div>

                <button
                    type="submit"
                    name="accion"
                    value="guardar"
                    class="btn-guardar">

                    Guardar

                </button>

                <button
                    type="reset"
                    class="btn-limpiar">

                    Limpiar

                </button>

            </div>

        </form>
        <!-- ================= CATÁLOGO ================= -->

        <div class="form-card">

            <h2 style="text-align:center; color:white;">
                Lista de Salarios
            </h2>

            <table class="tabla">

                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($salarios)): ?>

                    <?php foreach ($salarios as $fila): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($fila['fecha']) ?>
                            </td>

                            <td>
                                <?= number_format($fila['monto'], 2, ',', '.') ?>
                            </td>

                            <td>
                               <?= htmlspecialchars($fila['estado']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="3" style="text-align:center;">
                            No hay salarios registrados.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>

function closeAlert() {

    document.getElementById("customAlert").classList.add("hidden");

}

</script>

<!-- ================= MENSAJES DE ERROR ================= -->

<?php if (!empty($errores)): ?>

<script>

document.addEventListener("DOMContentLoaded", function(){

    document.getElementById("alertMessage").textContent =
        <?= json_encode(implode("\n", $errores)); ?>;

    document.getElementById("customAlert").classList.remove("hidden");

});

</script>

<?php endif; ?>

<!-- ================= MENSAJE DE ÉXITO ================= -->

<?php if (!empty($exito)): ?>

<script>

document.addEventListener("DOMContentLoaded", function(){

    document.getElementById("alertMessage").textContent =
        <?= json_encode($exito); ?>;

    document.getElementById("customAlert").classList.remove("hidden");

});

</script>

<?php endif; ?>

</body>
</html>        