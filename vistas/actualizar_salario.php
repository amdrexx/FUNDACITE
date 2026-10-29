<?php
session_start();
include_once "includes/guardian.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once '../controladores/ctrl_salario.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

/* =====================================================
   SOLO ADMINISTRADOR O DIRECTOR
   ===================================================== */
if (function_exists('esAdministradorODirector') && !esAdministradorODirector()) {
    $_SESSION['errores'] = ['No tiene permisos para actualizar salarios.'];
    header('Location: lista_salario.php');
    exit();
}

/* =====================================================
   ACTUALIZAR SUELDOS POR CARGO POR PORCENTAJE
   ===================================================== */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'aumento_porcentaje'
) {

    $porcentaje = (float) str_replace(',', '.', $_POST['porcentaje'] ?? '0');

    if ($porcentaje <= 0 || $porcentaje > 1000) {

        $_SESSION['errores'] = ['Ingrese un porcentaje válido (mayor que 0 y hasta 1000).'];
        header('Location: actualizar_salario.php');
        exit();

    }

    $conexion->begin_transaction();

    try {

        // Solo salarios VIGENTES de tipo "cargo" (el sueldo base no se toca)
        $stmt = $conexion->prepare("
            UPDATE SALARIO
            SET monto = ROUND(monto * (1 + ? / 100), 2)
            WHERE estado = 'Vigente'
              AND tipo_salario = 'cargo'
        ");

        $stmt->bind_param('d', $porcentaje);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $filas = $stmt->affected_rows;
        $stmt->close();

        $conexion->commit();

        $textoPct = rtrim(rtrim(number_format($porcentaje, 2, ',', '.'), '0'), ',');

        registrarBitacora(
            $conexion,
            'Salarios',
            'Editar',
            "Aplicó un aumento del $textoPct% a $filas sueldos por cargo vigentes."
        );

        $_SESSION['exito'] = "Se actualizaron $filas sueldos por cargo con un aumento del $textoPct%.";

        header('Location: lista_salario.php');
        exit();

    } catch (Throwable $e) {

        $conexion->rollback();

        $_SESSION['errores'] = ['Error al actualizar los sueldos por cargo.'];
        header('Location: actualizar_salario.php');
        exit();

    }

}

/* =====================================================
   SUELDOS POR CARGO VIGENTES (los que se van a actualizar)
   ===================================================== */
$todos = listarSalarios($conexion) ?? [];

$vigentes = array_filter(
    $todos,
    fn($s) => ($s['estado'] ?? '') === 'Vigente'
           && ($s['tipo_salario'] ?? '') === 'cargo'
);

$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
unset($_SESSION['errores'], $_SESSION['exito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Sueldos por Cargo</title>
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
    <div class="form-card">

        <form id="formAumento" method="POST" action="actualizar_salario.php">

            <input type="hidden" name="accion" value="aumento_porcentaje">

            <center><h2>Actualizar Sueldos por Cargo</h2></center>

            <div class="field full-width">
                <label for="porcentaje">Porcentaje de aumento</label>
                <div style="position:relative; width:73%;">
                    <input
                        type="number"
                        name="porcentaje"
                        id="porcentaje"
                        step="0.01"
                        min="0.01"
                        max="1000"
                        required
                        placeholder="Ej. 10"
                        style="width:100%; box-sizing:border-box; padding-right:45px;"
                    >
                    <span
                        style="position:absolute; right:12px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:800; pointer-events:none;"
                    >%</span>
                </div>
            </div>

            <table class="tabla" style="width:200%; margin-top:20px; table-layout:fixed;">
                <thead>
                    <tr>
                        <th style="width:25%;">Cargo</th>
                        <th style="width:25%;">Fecha</th>
                        <th style="width:25%;">Monto actual</th>
                        <th style="width:25%;">Nuevo monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vigentes)): ?>
                        <?php foreach ($vigentes as $fila): ?>
                            <tr>
                                <td><?= htmlspecialchars($fila['nombre_cargo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($fila['fecha']) ?></td>
                                <td class="monto-actual" data-monto="<?= htmlspecialchars($fila['monto']) ?>">
                                    Bs <?= number_format($fila['monto'], 2, ',', '.') ?>
                                </td>
                                <td class="monto-nuevo">—</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">
                                No hay sueldos por cargo vigentes para actualizar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="form-actions full-width" style="margin-top:20px;">
                <a
                    href="lista_salario.php"
                    class="btn-secundario"
                    style="display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box; text-decoration:none; text-align:center;"
                >
                    Cancelar
                </a>
                <button type="submit" class="btn-guardar" <?= empty($vigentes) ? 'disabled' : '' ?>>
                    Actualizar
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function closeAlert() {
    const alerta = document.getElementById("customAlert");
    if (alerta) alerta.classList.add("hidden");
}

document.addEventListener("DOMContentLoaded", function () {

    const inputPct = document.getElementById("porcentaje");
    const formAum  = document.getElementById("formAumento");

    const formatear = function (n) {
        return "Bs " + n.toLocaleString("es-VE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    // Calcula el nuevo monto de cada cargo mientras se escribe el porcentaje
    inputPct.addEventListener("input", function () {

        const p = parseFloat(inputPct.value) || 0;

        document.querySelectorAll("tbody tr").forEach(function (tr) {

            const celdaActual = tr.querySelector(".monto-actual");
            const celdaNuevo  = tr.querySelector(".monto-nuevo");

            if (!celdaActual || !celdaNuevo) return;

            const actual = parseFloat(celdaActual.dataset.monto);

            celdaNuevo.textContent = p > 0
                ? formatear(Math.round(actual * (1 + p / 100) * 100) / 100)
                : "—";

        });

    });

    formAum.addEventListener("submit", function (e) {
        const mensaje = "¿Aplicar un aumento del " + inputPct.value
            + "% a todos los sueldos por cargo vigentes?";
        if (!confirm(mensaje)) {
            e.preventDefault();
        }
    });

});
</script>

<?php if (!empty($errores)): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const mensaje = document.getElementById("alertMessage");
    const alerta  = document.getElementById("customAlert");
    if (mensaje && alerta) {
        mensaje.textContent = <?= json_encode(implode("\n", $errores)) ?>;
        alerta.classList.remove("hidden");
    }
});
</script>
<?php endif; ?>

<?php if (!empty($exito)): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const mensaje = document.getElementById("alertMessage");
    const alerta  = document.getElementById("customAlert");
    if (mensaje && alerta) {
        mensaje.textContent = <?= json_encode($exito) ?>;
        alerta.classList.remove("hidden");
    }
});
</script>
<?php endif; ?>

<script src="/FUNDACITE/vistas/js/valid_actualizar_salario.js"></script>
</body>
</html>