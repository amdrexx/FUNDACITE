<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_nueva_prima.php';

$modelo = new NuevaPrima($conexion);

// Modo: registrar (por defecto) o editar (?editar=ID)
$idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
$editando = $idEditar > 0;
$registro = [];

if ($editando) {
    $registro = $modelo->obtenerPorId($idEditar) ?? [];

    if (!$registro) {
        $_SESSION['errores'] = ['No se encontró la prima solicitada.'];
        header('Location: lista_nuevas_primas.php');
        exit;
    }
}

// Mensajes y datos digitados si el controlador devolvió un error de validación
$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
$old     = $_SESSION['old'] ?? [];
if (!is_array($errores)) { $errores = [$errores]; }
if (!is_array($old)) { $old = []; }
unset($_SESSION['errores'], $_SESSION['exito'], $_SESSION['old']);

function h($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$valNombre     = $old['nombre']     ?? ($registro['nombre'] ?? '');
$valFecha      = $old['fecha']      ?? ($registro['fecha'] ?? '');
$valPorcentaje = $old['porcentaje'] ?? (isset($registro['porcentaje']) ? rtrim(rtrim(number_format((float) $registro['porcentaje'], 2, '.', ''), '0'), '.') : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editando ? 'Modificar prima' : 'Registro de nueva prima' ?></title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_nuevas_primas.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver
        </a>

        <div class="form-card">

            <form id="formnuevaprima" method="POST" action="../controladores/ctrl_nueva_prima.php" novalidate>

                <center><h2><?= $editando ? 'Modificar prima' : 'Registro de nueva prima' ?></h2></center>

                <?php if ($editando): ?>
                    <input type="hidden" name="id_nueva_prima" value="<?= (int) $registro['id_nueva_prima'] ?>">
                <?php endif; ?>

                <div class="field">
                    <label for="nombre">Nombre de la prima</label>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        maxlength="100"
                        placeholder="Ingrese el nombre de la prima"
                        required
                        value="<?= h($valNombre) ?>"
                    >
                </div>

                <div class="field">
                    <label for="fecha">Fecha</label>
                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        required
                        value="<?= h($valFecha) ?>"
                    >
                </div>

                <div class="field campo-monto">
                    <label for="porcentaje">% prima</label>
                    <div style="position:relative; width:70%;">
                        <input
                            type="number"
                            name="porcentaje"
                            id="porcentaje"
                            step="0.01"
                            min="0.01"
                            max="100"
                            required
                            placeholder="Ej. 10"
                            style="width:100%; box-sizing:border-box; padding-right:3px;"
                            value="<?= h($valPorcentaje) ?>"
                        >
                        <span
                            class="sufijo-bs"
                            style="position:absolute; right:20px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:700; pointer-events:none;"
                        >%</span>
                    </div>
                </div>

                <div class="form-actions full-width">
                    <?php if ($editando): ?>
                        <a href="lista_nuevas_primas.php" class="btn-secundario" style="text-decoration:none;text-align:center;">Cancelar</a>
                    <?php else: ?>
                        <button type="reset" class="btn-secundario" id="btnLimpiar">Limpiar</button>
                    <?php endif; ?>
                    <button type="submit" name="accion" value="<?= $editando ? 'actualizar' : 'guardar' ?>" class="btn-guardar">
                        <?= $editando ? 'Actualizar' : 'Guardar' ?>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// Validación en pantalla (el servidor vuelve a validar todo) y protección contra doble envío
(function () {
    const form = document.getElementById('formnuevaprima');
    let enviando = false;

    form.addEventListener('submit', function (e) {
        if (enviando) { e.preventDefault(); return; }

        const nombre = form.nombre.value.replace(/\s+/g, ' ').trim();
        const fecha  = form.fecha.value;
        const pct    = parseFloat(String(form.porcentaje.value).replace(',', '.'));
        const errores = [];

        if (nombre.length < 3 || nombre.length > 100) errores.push('El nombre debe tener entre 3 y 100 caracteres.');
        if (!fecha) errores.push('Debe seleccionar una fecha.');
        if (!Number.isFinite(pct) || pct <= 0 || pct > 100) errores.push('El porcentaje debe ser mayor que 0 y hasta 100.');

        if (errores.length) {
            e.preventDefault();
            FundaciteAlerta.mostrar(errores.join('\n'), 'warning');
            return;
        }

        form.nombre.value = nombre;
        enviando = true;
    });

    window.addEventListener('pageshow', function () { enviando = false; });
})();
</script>



</body>
</html>