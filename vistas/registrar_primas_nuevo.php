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

/** 12.5 -> "12.5" | 56 -> "56" (para dejarlo editable en el input) */
function numInput($v): string { return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); }

$valNombre = $old['nombre'] ?? ($registro['nombre'] ?? '');
$valFecha  = $old['fecha']  ?? ($registro['fecha'] ?? '');
$valTipo   = $old['tipo_valor'] ?? ($registro['tipo_valor'] ?? 'porcentaje');
if (!in_array($valTipo, NuevaPrima::TIPOS_VALOR, true)) { $valTipo = 'porcentaje'; }

// Lista de valores a mostrar (el primero va en el campo principal, el resto en campos extra)
if (isset($old['valores']) && is_array($old['valores'])) {
    $valValores = array_values(array_map('strval', $old['valores']));
} else {
    $valValores = array_map(static fn($v) => numInput($v['valor']), $registro['valores'] ?? []);
}
if (!$valValores) { $valValores = ['']; }

$valVarios = isset($old['varios']) ? (bool) $old['varios'] : count($valValores) > 1;
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
    <style>
        .check-varios { display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:700;
                        color:#a9c7ff; font-size:.95rem; }
        .check-varios input { width:18px; height:18px; margin:0; cursor:pointer; accent-color:#0a84ff; }
        .valor-extra { display:flex; align-items:flex-end; gap:10px; }
        .valor-extra .caja { position:relative; flex:1; }
        .btn-quitar { border:none; cursor:pointer; background:#c62828; color:#fff; border-radius:8px;
                      height:38px; padding:0 12px; font-weight:700; }
        .btn-agregar-valor { border:none; cursor:pointer; border-radius:8px; padding:9px 14px; font-weight:700; }
    </style>
</head>
<body>

<?php include "includes/alerta.php"; ?>
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

                <!-- 1) ¿Porcentaje o monto fijo? -->
                <div class="field">
                    <label for="tipo_valor">¿Cómo se calcula la prima?</label>
                    <select name="tipo_valor" id="tipo_valor" required>
                        <option value="porcentaje" <?= $valTipo === 'porcentaje' ? 'selected' : '' ?>>Por porcentaje (%)</option>
                        <option value="monto" <?= $valTipo === 'monto' ? 'selected' : '' ?>>Monto fijo (Bs.)</option>
                    </select>
                </div>

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

                <!-- 2) Valor principal -->
                <div class="field campo-monto">
                    <label for="valor1" id="lblValor1">% prima</label>
                    <div style="position:relative; width:70%;">
                        <input
                            type="number"
                            name="valores[]"
                            id="valor1"
                            step="0.01"
                            min="0.01"
                            required
                            style="width:100%; box-sizing:border-box; padding-right:34px;"
                            value="<?= h($valValores[0]) ?>"
                        >
                        <span
                            class="sufijo-valor"
                            style="position:absolute; right:12px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:700; pointer-events:none;"
                        ></span>
                    </div>
                </div>

                <!-- 3) ¿Tiene más de un valor? -->
                <div class="field full-width">
                    <label class="check-varios">
                        <input type="checkbox" name="varios" id="varios" value="1" <?= $valVarios ? 'checked' : '' ?>>
                        <span>Esta prima tiene más de un valor <small id="notaVarios" style="font-weight:400; color:#ddd;"></small></span>
                    </label>
                </div>

                <div class="full-width" id="bloqueExtras" style="display:none; grid-column:1 / -1;">
                    <div id="listaExtras" style="display:grid; grid-template-columns:repeat(2, 1fr); gap:15px 25px;"></div>
                    <button type="button" id="btnAgregarValor" class="btn-secundario btn-agregar-valor" style="margin-top:12px;">
                        + Agregar otro valor
                    </button>
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
    const MAX_VALORES = <?= (int) NuevaPrima::MAX_VALORES ?>;
    const form      = document.getElementById('formnuevaprima');
    const tipo      = document.getElementById('tipo_valor');
    const valor1    = document.getElementById('valor1');
    const lblValor1 = document.getElementById('lblValor1');
    const varios    = document.getElementById('varios');
    const bloque    = document.getElementById('bloqueExtras');
    const lista     = document.getElementById('listaExtras');
    const btnAgregar = document.getElementById('btnAgregarValor');
    const nota      = document.getElementById('notaVarios');
    const valoresIniciales = <?= json_encode(array_slice($valValores, 1), JSON_UNESCAPED_UNICODE) ?>;
    let enviando = false;

    const esPct = () => tipo.value === 'porcentaje';

    function sufijo() { return esPct() ? '%' : 'Bs.'; }

    // Ajusta etiquetas, sufijo y límites según sea porcentaje o monto fijo
    function aplicarTipo() {
        const pct = esPct();
        const inputs = [valor1].concat(Array.from(lista.querySelectorAll('input')));

        lblValor1.textContent = pct ? '% prima' : 'Monto (Bs.)';
        nota.textContent = pct ? '(por ejemplo 12 % y 56 %)' : '(por ejemplo 56,00 Bs. y 12,50 Bs.)';

        inputs.forEach(function (inp, i) {
            inp.placeholder = pct ? 'Ej. 10' : 'Ej. 56.00';
            if (pct) { inp.max = '100'; } else { inp.removeAttribute('max'); }
            const suf = inp.parentElement.querySelector('.sufijo-valor');
            if (suf) suf.textContent = sufijo();
        });

        lista.querySelectorAll('label').forEach(function (l, i) {
            l.textContent = (pct ? 'Porcentaje ' : 'Monto ') + (i + 2);
        });
    }

    function renumerar() {
        lista.querySelectorAll('label').forEach(function (l, i) {
            l.textContent = (esPct() ? 'Porcentaje ' : 'Monto ') + (i + 2);
        });
        btnAgregar.style.display = (lista.children.length + 1 >= MAX_VALORES) ? 'none' : '';
    }

    function agregarExtra(valor) {
        if (lista.children.length + 1 >= MAX_VALORES) return;

        const n = lista.children.length;
        const campo = document.createElement('div');
        campo.className = 'field valor-extra-campo';

        const etiqueta = document.createElement('label');
        etiqueta.htmlFor = 'valor_extra_' + Date.now() + '_' + n;

        const fila = document.createElement('div');
        fila.className = 'valor-extra';

        const caja = document.createElement('div');
        caja.className = 'caja';

        const input = document.createElement('input');
        input.type = 'number';
        input.name = 'valores[]';
        input.id = etiqueta.htmlFor;
        input.step = '0.01';
        input.min = '0.01';
        input.required = true;
        input.style.cssText = 'width:100%; box-sizing:border-box; padding-right:34px;';
        input.value = valor || '';

        const suf = document.createElement('span');
        suf.className = 'sufijo-valor';
        suf.style.cssText = 'position:absolute; right:12px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:700; pointer-events:none;';

        const quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.className = 'btn-quitar';
        quitar.title = 'Quitar este valor';
        quitar.textContent = '✕';
        quitar.addEventListener('click', function () {
            campo.remove();
            renumerar();
            aplicarTipo();
        });

        caja.appendChild(input);
        caja.appendChild(suf);
        fila.appendChild(caja);
        fila.appendChild(quitar);
        campo.appendChild(etiqueta);
        campo.appendChild(fila);
        lista.appendChild(campo);

        renumerar();
        aplicarTipo();
        return input;
    }

    function actualizarVarios() {
        if (varios.checked) {
            bloque.style.display = '';
            if (lista.children.length === 0) {
                const inp = agregarExtra('');
                if (inp && document.activeElement === varios) inp.focus();
            }
        } else {
            bloque.style.display = 'none';
            lista.innerHTML = '';   // sin campos extra no se envían
        }
        aplicarTipo();
    }

    tipo.addEventListener('change', aplicarTipo);
    varios.addEventListener('change', actualizarVarios);
    btnAgregar.addEventListener('click', function () {
        const inp = agregarExtra('');
        if (inp) inp.focus();
    });

    form.addEventListener('reset', function () {
        setTimeout(function () { lista.innerHTML = ''; actualizarVarios(); }, 0);
    });

    form.addEventListener('submit', function (e) {
        if (enviando) { e.preventDefault(); return; }

        const nombre = form.nombre.value.replace(/\s+/g, ' ').trim();
        const fecha  = form.fecha.value;
        const errores = [];

        if (nombre.length < 3 || nombre.length > 100) errores.push('El nombre debe tener entre 3 y 100 caracteres.');
        if (!fecha) errores.push('Debe seleccionar una fecha.');

        const inputs = [valor1].concat(Array.from(lista.querySelectorAll('input')));
        const vistos = {};
        inputs.forEach(function (inp, i) {
            const v = parseFloat(String(inp.value).replace(',', '.'));
            const n = i + 1;
            if (!Number.isFinite(v) || v <= 0 || (esPct() && v > 100)) {
                errores.push('Valor ' + n + ': ' + (esPct()
                    ? 'el porcentaje debe ser mayor que 0 y hasta 100.'
                    : 'el monto debe ser mayor que 0.'));
            } else {
                const clave = v.toFixed(2);
                if (vistos[clave]) errores.push('El valor ' + n + ' está repetido.');
                vistos[clave] = true;
            }
        });

        if (varios.checked && inputs.length < 2) {
            errores.push('Marcó que la prima tiene más de un valor: agregue al menos otro.');
        }

        if (errores.length) {
            e.preventDefault();
            const alerta = document.getElementById('customAlert');
            const msg = document.getElementById('alertMessage');
            if (alerta && msg) {
                msg.textContent = errores.join('\n');
                alerta.classList.remove('hidden');
            } else {
                alert(errores.join('\n'));
            }
            return;
        }

        form.nombre.value = nombre;
        enviando = true;
    });

    window.addEventListener('pageshow', function () { enviando = false; });

    // Estado inicial (también restaura lo digitado tras un error del servidor)
    if (varios.checked) {
        bloque.style.display = '';
        valoresIniciales.forEach(function (v) { agregarExtra(v); });
        if (lista.children.length === 0) agregarExtra('');
    }
    aplicarTipo();
})();
</script>

<?php if (!empty($errores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        if (alertDiv && msg) {
            msg.textContent = <?= json_encode(implode("\n", $errores)) ?>;
            alertDiv.classList.remove('hidden');
        }
    });
</script>
<?php endif; ?>

</body>
</html>