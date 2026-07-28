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
    $editar = buscarSalario($conexion, intval($_GET['editar']));
}

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

    <style>
/* Sufijo "Bs" dentro del campo Monto */
.campo-monto {
    position: relative;
}
.campo-monto input {
    padding-right: 45px;
}

/* Ocultamos las flechitas nativas del input number para que no choquen con "Bs" */
.campo-monto input::-webkit-outer-spin-button,
.campo-monto input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.campo-monto input[type="number"] {
    -moz-appearance: textfield;
}

.campo-monto .sufijo-bs {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(calc(-50% + 12px)); /* +12px para compensar el label de arriba */
    font-weight: 600;
    color: #444;
    pointer-events: none;
}
    </style>
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
                    <?= $editar ? "Editar Salario" : "Registro de Salario"; ?>
                </h2>

                <?php if ($editar) { ?>
                    <input type="hidden" name="id_salario" value="<?= $editar['id_salario']; ?>">
                <?php } ?>

                <div class="field">
                    <label>Fecha de ingreso</label>

                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        value="<?= $editar['fecha'] ?? ($_SESSION['old']['fecha'] ?? '') ?>">
                </div>

                <div class="field campo-monto">

                    <label>Monto</label>

                    <input
                        type="number"
                        name="monto"
                        id="monto"
                        step="0.01"
                        value="<?= $editar['monto'] ?? ($_SESSION['old']['monto'] ?? '') ?>">

                    <span class="sufijo-bs">Bs</span>

                </div>

                <button
                    type="submit"
                    name="accion"
                    value="<?= $editar ? 'actualizar' : 'guardar'; ?>"
                    class="btn-guardar">

                    <?= $editar ? "Actualizar" : "Guardar"; ?>

                </button>

                <button
                    type="reset"
                    class="btn-guardar">

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
                        <th>Acciones</th>
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
                                Bs <?= number_format($fila['monto'], 2, ',', '.') ?>
                            </td>

                            <td>
                                <?php if ($fila['estado'] === 'Vigente') { ?>
                                    <span class="badge-vigente">Vigente</span>
                                <?php } else { ?>
                                    <span class="badge-deshabilitado"><?= htmlspecialchars($fila['estado']) ?></span>
                                <?php } ?>
                            </td>

                               <td class="acciones">

                                <a
                                    href="registrar_salario.php?editar=<?= $fila['id_salario']; ?>"
                                    class="btn-editar"
                                    style="text-decoration:none;">
                                    <i class="bi bi-pencil-square"></i>
                                    Editar
                                </a>

                                <a
                                    href="../controladores/ctrl_salario.php?eliminar=<?= $fila['id_salario']; ?>"
                                    class="btn-eliminar"
                                    style="text-decoration:none;"
                                    onclick="return confirm('¿Desea eliminar este salario?');">
                                    <i class="bi bi-trash"></i>
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" style="text-align:center;">
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

<?php if (!empty($errores)): ?>
<script>
document.addEventListener("DOMContentLoaded", function(){
    document.getElementById("alertMessage").textContent =
        <?= json_encode(implode("\n", $errores)); ?>;
    document.getElementById("customAlert").classList.remove("hidden");
});
</script>
<?php endif; ?>

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