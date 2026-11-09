<?php
session_start();
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_nueva_prima.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

/* =====================================================
   SOLO ADMINISTRADOR O DIRECTOR
   ===================================================== */
if (function_exists('esAdministradorODirector') && !esAdministradorODirector()) {
    $_SESSION['errores'] = ['No tiene permisos para actualizar los porcentajes de las primas.'];
    header('Location: lista_nuevas_primas.php');
    exit();
}

$modelo = new NuevaPrima($conexion);

/* =====================================================
   PRIMAS ACTIVAS (las que se pueden actualizar)
   ===================================================== */
// Cada fila es un VALOR de porcentaje (una prima con varios valores aparece varias veces).
// Las primas de monto fijo no se actualizan por porcentaje.
$vigentes = $modelo->listarValoresPorcentaje();

registrarBitacora($conexion, 'Nuevas primas', 'Consultar', 'Abrió la pantalla de actualización de porcentajes de primas.');

$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
unset($_SESSION['errores'], $_SESSION['exito']);
if (!is_array($errores)) { $errores = [$errores]; }

function fmtFecha(string $f): string {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d ? $d->format('d/m/Y') : $f;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Porcentaje de Primas</title>
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

        <form id="formPorcentajes" method="POST" action="../controladores/ctrl_nueva_prima.php">

            <input type="hidden" name="accion" value="actualizar_porcentajes">

            <center><h2>Actualizar Porcentaje de Primas</h2></center>

            <div class="field full-width">
                <label for="modo">Modo de actualización</label>
                <select name="modo" id="modo">
                    <option value="individual">Una por una</option>
                    <option value="todas">Todas a la vez</option>
                </select>
            </div>

            <div class="field" id="campoOperacion" style="display:none;">
                <label for="operacion">¿Qué desea hacer?</label>
                <select name="operacion" id="operacion">
                    <option value="subir">Subir el porcentaje en</option>
                    <option value="bajar">Bajar el porcentaje en</option>
                    <option value="establecer">Establecer el porcentaje en</option>
                </select>
            </div>

            <div class="field" id="campoValor" style="display:none;">
                <label for="valor">Porcentaje</label>
                <div style="position:relative; width:73%;">
                    <input
                        type="number"
                        name="valor"
                        id="valor"
                        step="0.01"
                        min="0.01"
                        max="100"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="Ej. 2"
                        style="width:100%; box-sizing:border-box; padding-right:45px;"
                    >
                    <span
                        style="position:absolute; right:12px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:800; pointer-events:none;"
                    >%</span>
                </div>
            </div>

            <table class="tabla" style="width:100%; grid-column:1 / -1; margin-top:20px; table-layout:fixed;">
                <thead>
                    <tr>
                        <th class="col-sel" style="width:10%; text-align:center; display:none;">
                            <input type="checkbox" id="marcarTodas" checked title="Marcar / desmarcar todas">
                        </th>
                        <th style="width:30%;">Prima</th>
                        <th style="width:20%;">Fecha</th>
                        <th style="width:20%;">Porcentaje actual</th>
                        <th style="width:20%;">Nuevo porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vigentes)): ?>
                        <?php foreach ($vigentes as $fila): ?>
                            <tr data-id="<?= (int) $fila['id_prima_valor'] ?>"
                                data-actual="<?= htmlspecialchars(number_format((float) $fila['valor'], 2, '.', '')) ?>">
                                <td class="col-sel" style="text-align:center; display:none;">
                                    <input type="checkbox" class="chk-fila" name="seleccion[]" value="<?= (int) $fila['id_prima_valor'] ?>" checked>
                                </td>
                                <td class="nombre-prima"><?= htmlspecialchars($fila['nombre']) ?><?= (int) $fila['total_valores'] > 1 ? ' (valor ' . (int) $fila['posicion'] . ')' : '' ?></td>
                                <td><?= htmlspecialchars(fmtFecha($fila['fecha'])) ?></td>
                                <td><?= number_format((float) $fila['valor'], 2, ',', '.') ?>%</td>
                                <td>
                                    <div style="position:relative;">
                                        <input
                                            type="number"
                                            class="nuevo-pct"
                                            name="nuevo[<?= (int) $fila['id_prima_valor'] ?>]"
                                            step="0.01"
                                            min="0.01"
                                            max="100"
                                            inputmode="decimal"
                                            autocomplete="off"
                                            value="<?= htmlspecialchars(number_format((float) $fila['valor'], 2, '.', '')) ?>"
                                            style="width:100%; box-sizing:border-box; padding-right:34px;"
                                        >
                                        <span
                                            style="position:absolute; right:10px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:800; pointer-events:none;"
                                        >%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">
                                No hay primas activas por porcentaje para actualizar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="form-actions full-width" style="margin-top:20px;">
                <a
                    href="lista_primas.php"
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

function mostrarAlerta(mensaje) {
    const alerta = document.getElementById("customAlert");
    const caja   = document.getElementById("alertMessage");
    if (alerta && caja) {
        caja.textContent = mensaje;
        alerta.classList.remove("hidden");
    } else {
        alert(mensaje);
    }
}

document.addEventListener("DOMContentLoaded", function () {

    const form       = document.getElementById("formPorcentajes");
    const selModo    = document.getElementById("modo");
    const operacion  = document.getElementById("operacion");
    const valor      = document.getElementById("valor");
    const campoOp    = document.getElementById("campoOperacion");
    const campoValor = document.getElementById("campoValor");
    const marcarTodas = document.getElementById("marcarTodas");
    const filas      = Array.from(form.querySelectorAll("tbody tr[data-id]"));
    const colsSel    = Array.from(form.querySelectorAll(".col-sel"));
    let enviando = false;

    const esTodas = function () { return selModo.value === "todas"; };

    // Bloquea letras (incluidas e, E), + y -. Mantiene las flechas de subir/bajar.
    const soloDecimal = function (input) {
        input.addEventListener("keydown", function (e) {
            // Permitir flechas, borrar, tab, atajos (Ctrl+C, Ctrl+V, etc.)
            if (e.ctrlKey || e.metaKey || e.key.length > 1) return;
            if (!/[0-9.,]/.test(e.key)) e.preventDefault();
        });

        // Al pegar, solo se acepta un número con hasta 2 decimales
        input.addEventListener("paste", function (e) {
            const texto = (e.clipboardData || window.clipboardData).getData("text").trim();
            if (!/^\d+([.,]\d{0,2})?$/.test(texto)) e.preventDefault();
        });

        // Máximo 2 decimales
        input.addEventListener("input", function () {
            if (/\.\d{3,}/.test(this.value)) {
                this.value = this.value.replace(/(\.\d{2})\d+/, "$1");
            }
        });
    };

    soloDecimal(valor);
    filas.forEach(function (tr) { soloDecimal(tr.querySelector(".nuevo-pct")); });

    // Porcentaje resultante en modo "todas a la vez"
    const calcular = function (actual) {
        const v = parseFloat(String(valor.value).replace(",", "."));
        if (!Number.isFinite(v) || v <= 0) return null;
        const r = operacion.value === "establecer"
            ? v
            : (operacion.value === "subir" ? actual + v : actual - v);
        return Math.round(r * 100) / 100;
    };

    const refrescar = function () {
        const todas = esTodas();

        campoOp.style.display    = todas ? "" : "none";
        campoValor.style.display = todas ? "" : "none";
        colsSel.forEach(function (c) { c.style.display = todas ? "table-cell" : "none"; });

        filas.forEach(function (tr) {
            const input  = tr.querySelector(".nuevo-pct");
            const actual = parseFloat(tr.dataset.actual);
            const marcada = tr.querySelector(".chk-fila").checked;

            input.readOnly = todas;
            input.style.color = "";

            if (!todas) return;

            const r = marcada ? calcular(actual) : null;
            input.value = (r === null ? actual : r).toFixed(2);

            if (r !== null && (r <= 0 || r > 100)) {
                input.style.color = "#c62828";   // fuera de rango
            }
        });
    };

    selModo.addEventListener("change", function () {
        // Al volver a "una por una" se restauran los porcentajes actuales
        if (!esTodas()) {
            filas.forEach(function (tr) { tr.querySelector(".nuevo-pct").value = tr.dataset.actual; });
        }
        refrescar();
    });
    operacion.addEventListener("change", refrescar);
    valor.addEventListener("input", refrescar);
    form.querySelectorAll(".chk-fila").forEach(function (c) { c.addEventListener("change", refrescar); });

    if (marcarTodas) {
        marcarTodas.addEventListener("change", function () {
            form.querySelectorAll(".chk-fila").forEach(function (c) { c.checked = marcarTodas.checked; });
            refrescar();
        });
    }

    form.addEventListener("submit", function (e) {
        if (enviando) { e.preventDefault(); return; }

        const errores = [];
        let cambios = 0;

        if (esTodas()) {
            const v = parseFloat(String(valor.value).replace(",", "."));
            if (!Number.isFinite(v) || v <= 0 || v > 100) {
                errores.push("Ingrese un porcentaje válido (mayor que 0 y hasta 100).");
            }

            const marcadas = filas.filter(function (tr) { return tr.querySelector(".chk-fila").checked; });
            if (!marcadas.length) errores.push("Seleccione al menos una prima.");

            if (!errores.length) {
                marcadas.forEach(function (tr) {
                    const actual = parseFloat(tr.dataset.actual);
                    const r = calcular(actual);
                    const nombre = tr.querySelector(".nombre-prima").textContent.trim();
                    if (r === null || r <= 0 || r > 100) {
                        errores.push('La prima "' + nombre + '" quedaría fuera del rango permitido (0,01 a 100).');
                    } else if (Math.abs(r - actual) >= 0.005) {
                        cambios++;
                    }
                });
            }
        } else {
            filas.forEach(function (tr) {
                const n = parseFloat(String(tr.querySelector(".nuevo-pct").value).replace(",", "."));
                const nombre = tr.querySelector(".nombre-prima").textContent.trim();
                if (!Number.isFinite(n) || n <= 0 || n > 100) {
                    errores.push('"' + nombre + '": el porcentaje debe ser mayor que 0 y hasta 100.');
                } else if (Math.abs(n - parseFloat(tr.dataset.actual)) >= 0.005) {
                    cambios++;
                }
            });
        }

        if (!errores.length && cambios === 0) {
            errores.push("No hay cambios que guardar: los porcentajes son iguales a los actuales.");
        }

        if (errores.length) {
            e.preventDefault();
            mostrarAlerta(errores.join("\n"));
            return;
        }

        const mensaje = "¿Actualizar el porcentaje de " + cambios + (cambios === 1 ? " prima?" : " primas?");
        if (!confirm(mensaje)) {
            e.preventDefault();
            return;
        }

        enviando = true;
    });

    window.addEventListener("pageshow", function () { enviando = false; });

    refrescar();
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

</body>
</html>