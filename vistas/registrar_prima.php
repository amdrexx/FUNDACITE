<?php
// Formulario integrado desde Prima.zip, adaptado al modelo/controlador de FUNDACITE.
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
include_once "includes/guardian.php";
require_once '../conexion.php';
require_once '../modelos/clase_primas.php';
$objprima = new Prima($conexion);
$accion = (($_GET['accion'] ?? '') === 'editar') ? 'editar' : ((($_GET['accion'] ?? '') === 'consultar') ? 'consultar' : 'registrar');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$prima_data = [];
$primasTrabajador = [];
if ($accion === 'editar' && $id > 0) {
    // En modo editar, $id es el id del TRABAJADOR: se cargan todas sus primas
    $primasTrabajador = $objprima->listarPorTrabajador($id);
    $prima_data = $primasTrabajador[0] ?? [];
} elseif ($accion === 'consultar' && $id > 0) {
    $prima_data = $objprima->obtenerPorId($id) ?? [];
}
if (in_array($accion, ['editar', 'consultar'], true) && $id > 0) {
    if (!$prima_data) { $_SESSION['errores'] = ['No se encontró la prima solicitada.']; header('Location: lista_primas.php'); exit; }
}
$errores = $_SESSION['errores'] ?? [];
$exito = $_SESSION['exito'] ?? '';
$old = $_SESSION['old'] ?? [];
if (!is_array($errores)) $errores = [$errores];
if (!is_array($old)) $old = [];
$trabajadores = $objprima->listarTrabajadoresActivos((int)($prima_data['id_trabajador'] ?? 0));
$primasRegistradas = $objprima->tiposRegistradosPorTrabajador();
$sueldoBase = $objprima->obtenerSueldoBase() ?? 0.0;
// Primas nuevas activas registradas en el catálogo: [nombre => porcentaje]
$catalogoNuevas = $objprima->listarCatalogoNuevas();
$trabajadorSeleccionado = (string)($old['id_trabajador'] ?? $prima_data['id_trabajador'] ?? '');
$txt_tipo = (string)($old['tipo_prima'] ?? $prima_data['tipo_prima'] ?? '');
$estatusSeleccionado = (string)($old['estatus'] ?? $prima_data['estatus'] ?? 'Activo');
if ($accion === 'editar' && !isset($old['estatus'])) {
    // Si el trabajador tiene al menos una prima activa se muestra "Activo"
    $estatusSeleccionado = 'Inactivo';
    foreach ($primasTrabajador as $pt) { if (($pt['estatus'] ?? '') === 'Activo') { $estatusSeleccionado = 'Activo'; break; } }
}
// Tipos de prima que se muestran al editar/consultar
$tiposMostrar = $accion === 'editar' ? array_column($primasTrabajador, 'tipo_prima') : [$txt_tipo];
$tiposPrevios = (isset($old['tipos_prima']) && is_array($old['tipos_prima'])) ? array_values($old['tipos_prima']) : [];
$montosPrevios = [];
$condHijos = (string)($prima_data['condicion'] ?? '');
if ($accion === 'editar') {
    $condHijos = '';
    foreach ($primasTrabajador as $pt) { if ($pt['tipo_prima'] === 'Prima por hijos') { $condHijos = (string)$pt['condicion']; } }
}
if ($condHijos !== '' && strpos($condHijos, ':') !== false) {
    $parte = substr(strrchr($condHijos, ':'), 1);
    $montosPrevios = array_map('trim', explode(',', $parte));
}
$deshabilitado = $accion === 'consultar' ? 'disabled' : '';
$bloquear = ($accion === 'editar' || $accion === 'consultar') ? 'disabled' : '';
$titulo = $accion === 'editar' ? 'Editar prima' : ($accion === 'consultar' ? 'Consulta de prima' : 'Registro de primas');
$btnTexto = $accion === 'editar' ? 'Actualizar' : 'Guardar';
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
unset($_SESSION['errores'], $_SESSION['exito'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= h($titulo) ?></title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

</div>


<?php include "includes/layout.php"; ?>

<div class="main">

<div style="display:flex; flex-direction:column; gap:30px;">

<!-- FORMULARIO -->
<div class="form-card">

    <form
        id="formprimas"
        data-modo="<?= h($accion) ?>"
        data-montos-previos='<?= h(json_encode($montosPrevios)) ?>'
        method="POST"
        action="../controladores/ctrl_primas.php"
        novalidate
    >

        <h2 class="full-width" style="text-align:center;">
            <?= h($titulo) ?>
        </h2>
        <?php if ($accion === 'editar'): ?>
            <input type="hidden" name="id_trabajador" value="<?= (int)$prima_data['id_trabajador'] ?>">
        <?php endif; ?>

        <div class="field">
    <label for="id_trabajador">Trabajador</label>

    <select
        name="id_trabajador"
        id="id_trabajador"
        required
        <?= $bloquear ?>
    >
        <option value="">Seleccione un trabajador</option>

        <?php foreach ($trabajadores as $trabajador): ?>
            <option
                value="<?= (int) $trabajador['id_trabajador'] ?>"
                data-sueldo-cargo="<?= htmlspecialchars(
                    (string) $trabajador['sueldo_cargo'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-salario-mensual="<?= htmlspecialchars((string) $trabajador['salario_mensual'], ENT_QUOTES, 'UTF-8') ?>"
                data-base-prima="<?= htmlspecialchars((string) $trabajador['base_prima'], ENT_QUOTES, 'UTF-8') ?>"
                data-origen-salario="<?= htmlspecialchars((string) $trabajador['origen_salario'], ENT_QUOTES, 'UTF-8') ?>"
                data-tipo-personal="<?= htmlspecialchars(
                    (string) $trabajador['tipo_personal'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-hijos="<?= (int) $trabajador['hijos'] ?>"
                data-anos-servicio="<?= (int) $trabajador['anos_servicio'] ?>"
                data-nivel-educacion="<?= htmlspecialchars(
                    (string) $trabajador['nivel_educacion'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-estatus="<?= htmlspecialchars(
                    (string) $trabajador['status'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-primas-registradas='<?= h(json_encode($primasRegistradas[(int) $trabajador['id_trabajador']] ?? [])) ?>'
                <?= (string) $trabajadorSeleccionado ===
                    (string) $trabajador['id_trabajador']
                    ? 'selected'
                    : '' ?>
            >
                <?= htmlspecialchars(
                    $trabajador['cedula'] . ' - ' .
                    $trabajador['nombre_completo'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
        <style>
            .prima-row { grid-column: 1 / -1; border-bottom: 1px solid rgba(255,255,255,0.22); padding: 10px 0; }
            .prima-check { display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700;
                           text-transform: uppercase; letter-spacing: .04em; color: #a9c7ff; font-size: .95rem; }
            .prima-check input { width: 18px; height: 18px; margin: 0; cursor: pointer; accent-color: #0a84ff; }
            .prima-check input:disabled { cursor: not-allowed; }
            .prima-nota { font-weight: 400; text-transform: none; letter-spacing: 0; font-size: .8rem; color: #ddd; }
            .prima-panel { display: none; margin-top: 12px; grid-template-columns: repeat(2, 1fr); gap: 15px 25px; }
        </style>

        <?php if ($accion === 'registrar'): ?>
        <input type="hidden" name="estatus" value="Activo">
        <?php endif; ?>

        <div class="field">
            <label for="tipo_personal">Tipo de personal</label>
            <input type="text" id="tipo_personal" placeholder="Seleccione un trabajador" readonly>
        </div>

        <div class="field">
            <label for="estatus_laboral">Estatus laboral</label>
            <input type="text" id="estatus_laboral" placeholder="Seleccione un trabajador" readonly>
        </div>

        <div class="field">
            <label for="origen_salario">Modalidad salarial aplicada</label>
            <input type="text" id="origen_salario" placeholder="Seleccione un trabajador" readonly aria-live="polite">
        </div>

        <div class="field" id="campoSueldoBase">
            <label for="sueldo_base">Sueldo base (Bs.)</label>
            <input type="number" id="sueldo_base" name="sueldo_base" min="0" step="0.01"
                   value="<?= h($sueldoBase) ?>" readonly required>
        </div>

        <div class="field" id="campoSueldoCargo">
            <label for="sueldo_cargo">Sueldo por cargo (Bs.)</label>
            <input type="text" id="sueldo_cargo" placeholder="Seleccione un trabajador" readonly>
        </div>

        <?php if ($accion !== 'registrar'): ?>
        <!-- ESTATUS DE LA PRIMA (solo al editar/consultar) -->
        <div class="field">
            <label for="estatus">Estatus de la prima</label>
            <select name="estatus" id="estatus" required <?= $deshabilitado ?>>
                <option value="Activo" <?= $estatusSeleccionado === 'Activo' ? 'selected' : '' ?>>Activo</option>
                <option value="Inactivo" <?= $estatusSeleccionado === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <?php endif; ?>

        <!-- PRIMAS: al registrar se marcan las que se quieran; al editar/consultar solo aparece la prima del registro -->
        <div id="grupoPrimas" style="grid-column: 1 / -1;" data-previas='<?= h(json_encode($tiposPrevios)) ?>'>
            <?php
            $filasPrimas = [
                'Prima por antigüedad'         => ['nueva' => false, 'pct' => 0],
                'Prima por hijos'              => ['nueva' => false, 'pct' => 0],
                'Prima por profesionalización' => ['nueva' => false, 'pct' => 0],
            ];

            // Primas nuevas registradas en "Registrar nueva prima" (catálogo)
            foreach ($catalogoNuevas as $nombreNueva => $pctNueva) {
                $nombreNueva = (string) $nombreNueva;
                if (!isset($filasPrimas[$nombreNueva])) {
                    $filasPrimas[$nombreNueva] = ['nueva' => true, 'pct' => (float) $pctNueva];
                }
            }

            // Editar/consultar: si el trabajador tiene una prima nueva que ya no está activa
            // en el catálogo, igual se muestra con el porcentaje que tenía guardado
            if ($accion !== 'registrar') {
                $guardadas = $accion === 'editar' ? $primasTrabajador : [$prima_data];
                foreach ($guardadas as $pg) {
                    $tp = (string) ($pg['tipo_prima'] ?? '');
                    if ($tp !== '' && !isset($filasPrimas[$tp])) {
                        $filasPrimas[$tp] = ['nueva' => true, 'pct' => (float) ($pg['porcentaje'] ?? 0)];
                    }
                }
                // En consulta se muestra el porcentaje con el que se guardó
                if ($accion === 'consultar' && isset($filasPrimas[$txt_tipo]) && $filasPrimas[$txt_tipo]['nueva']) {
                    $filasPrimas[$txt_tipo]['pct'] = (float) ($prima_data['porcentaje'] ?? 0);
                }
            }

            foreach ($filasPrimas as $valorFila => $infoFila):
                $valorFila = (string) $valorFila;
                if ($accion !== 'registrar' && !in_array($valorFila, $tiposMostrar, true)) continue;
            ?>
            <div class="prima-row" data-tipo="<?= h($valorFila) ?>"
                 data-nueva="<?= $infoFila['nueva'] ? '1' : '0' ?>"
                 data-pct="<?= h($infoFila['pct']) ?>">
                <label class="prima-check">
                    <input type="checkbox" class="chk-prima"
                        <?= $accion === 'registrar'
                            ? 'name="tipos_prima[]"'
                            : ($accion === 'editar'
                                ? 'name="tipos_prima[]" checked onclick="return false" style="cursor:default;"'
                                : 'checked onclick="return false" style="cursor:default;"') ?>
                        value="<?= h($valorFila) ?>">
                    <span><?= h($valorFila) ?><span class="prima-nota"></span></span>
                </label>
                <div class="prima-panel"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="field full-width">
            <label for="monto_total">Monto total (Bs.)</label>
            <input type="number" id="monto_total" name="monto_total" value="<?= h($sueldoBase) ?>" min="0" step="0.01" readonly>
        </div>

        <!-- BOTONES -->
        <div class="form-actions full-width">
            <?php if ($accion === 'editar' || $accion === 'consultar'): ?>
                <a href="lista_primas.php" class="btn-secundario" style="text-decoration:none;text-align:center;">Volver</a>
            <?php else: ?>
                <button type="reset" class="btn-secundario" id="btnLimpiar">Limpiar</button>
            <?php endif; ?>
            <?php if ($accion !== 'consultar'): ?>
                <button type="submit" name="accion" value="<?= $accion === 'editar' ? 'actualizar' : 'guardar' ?>" class="btn-guardar"><?= h($btnTexto) ?></button>
            <?php endif; ?>
        </div>

    </form>

</div>
        
<script>
/* =============================================================================
   Registro de varias primas a la vez (solo modo "registrar").
   - Los selects en cascada (#grupoPrimas) deciden qué primas se registran.
   - Por cada prima elegida se muestra un bloque de campos que se llena solo
     con los datos del trabajador (años de servicio, nivel de educación, hijos).
   - El monto total = salario mensual + suma de las primas elegidas.
   Para editar/consultar se sigue usando valid_primas.js.
============================================================================= */
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formprimas");
    const trabajador = document.getElementById("id_trabajador");
    const grupo = document.getElementById("grupoPrimas");

    if (!formulario || !trabajador || !grupo) return;

    // registrar | editar | consultar
    const modo = formulario.dataset.modo || "registrar";

    // Cada fila: checkbox + panel de campos que se muestra al marcarla
    const filas = Array.from(grupo.querySelectorAll(".prima-row")).map(function (fila) {
        return {
            tipo: fila.dataset.tipo,
            fila: fila,
            check: fila.querySelector(".chk-prima"),
            panel: fila.querySelector(".prima-panel"),
            nota: fila.querySelector(".prima-nota"),
            nueva: fila.dataset.nueva === "1",
            pct: parseFloat(fila.dataset.pct) || 0
        };
    });

    const sueldoBase = document.getElementById("sueldo_base");
    const sueldoCargo = document.getElementById("sueldo_cargo");
    const campoSueldoBase = document.getElementById("campoSueldoBase");
    const campoSueldoCargo = document.getElementById("campoSueldoCargo");
    const origenSalario = document.getElementById("origen_salario");
    const tipoPersonal = document.getElementById("tipo_personal");
    const estatusLaboral = document.getElementById("estatus_laboral");
    const montoTotal = document.getElementById("monto_total");

    let consultaSalarioCorrecta = true;
    let enviando = false;
    let tokenConsultaSalario = 0;

    const T_ANT = "Prima por antigüedad";
    const T_PROF = "Prima por profesionalización";
    const T_HIJ = "Prima por hijos";

    const NIVEL_A_CONDICION = {
        "Técnico Superior Universitario": "TSU",
        "Profesional": "Lic. / Ing.",
        "Especialista": "Doc. / Post.",
        "Maestría": "Doc. / Post.",
        "Doctor": "Doc. / Post."
    };

    const NIVELES = [
        { valor: "TSU", texto: "TSU (20%)", pct: 20 },
        { valor: "Lic. / Ing.", texto: "Lic. / Ing. (25%)", pct: 25 },
        { valor: "Doc. / Post.", texto: "Doc. / Post. (35%)", pct: 35 }
    ];

    const MONTOS_HIJO = [
        { valor: "56.00", texto: "56,00 Bs." },
        { valor: "12.50", texto: "12,50 Bs." }
    ];

    // Tabla 1 - Prima de antigüedad: años de servicio -> porcentaje (23 años en adelante = 30%)
    const PORCENTAJE_ANTIGUEDAD = {
        1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6.2, 7: 7.4, 8: 8.6, 9: 9.8, 10: 11,
        11: 12.4, 12: 13.8, 13: 15.2, 14: 16.6, 15: 18, 16: 19.6, 17: 21.2,
        18: 22.8, 19: 24.4, 20: 26, 21: 27.8, 22: 29.6
    };
    function porcentajeAntiguedad(anios) {
        if (!Number.isInteger(anios) || anios < 1) return 0;
        return anios >= 23 ? 30 : (PORCENTAJE_ANTIGUEDAD[anios] || 0);
    }

    // ---------------------------------------------------------------------
    // Utilidades
    // ---------------------------------------------------------------------
    function numero(valorTexto) {
        if (valorTexto === null || valorTexto === undefined) return 0;
        const texto = String(valorTexto).trim().replace(/\s/g, "").replace(",", ".");
        if (texto === "") return 0;
        const resultado = Number(texto);
        return Number.isFinite(resultado) ? resultado : 0;
    }

    function moneda(cantidad) {
        return numero(cantidad).toFixed(2);
    }

    function mostrarAlerta(mensaje) {
        window.FundaciteAlerta.mostrar(mensaje, 'warning');
    }

    function opcionTrabajador() {
        const opcion = trabajador.options[trabajador.selectedIndex];
        return opcion && opcion.value ? opcion : null;
    }

    function crearCampo(textoEtiqueta, control) {
        const campo = document.createElement("div");
        campo.className = "field";
        campo.style.width = "100%";

        const etiqueta = document.createElement("label");
        etiqueta.htmlFor = control.id;
        etiqueta.textContent = textoEtiqueta;

        campo.appendChild(etiqueta);
        campo.appendChild(control);
        return campo;
    }

    function crearSelect(id, nombre, opciones, textoVacio) {
        const select = document.createElement("select");
        select.id = id;
        select.name = nombre;
        select.required = true;
        if (textoVacio) {
            const vacio = document.createElement("option");
            vacio.value = "";
            vacio.textContent = textoVacio;
            select.appendChild(vacio);
        }
        opciones.forEach(function (o) {
            const op = document.createElement("option");
            op.value = o.valor;
            op.textContent = o.texto;
            select.appendChild(op);
        });
        return select;
    }

    function crearInputLectura(id, tipo) {
        const input = document.createElement("input");
        input.type = tipo || "text";
        input.id = id;
        input.readOnly = true;
        if (tipo === "number") input.step = "0.01";
        return input;
    }

    function crearRaiz(titulo) {
        const raiz = document.createElement("div");
        raiz.style.display = "grid";
        raiz.style.gridTemplateColumns = "repeat(2, 1fr)";
        raiz.style.gap = "15px 25px";

        const encabezado = document.createElement("div");
        encabezado.textContent = titulo;
        encabezado.style.gridColumn = "1 / -1";
        encabezado.style.fontWeight = "600";
        encabezado.style.paddingBottom = "4px";
        encabezado.style.borderBottom = "1px solid rgba(255,255,255,0.25)";
        raiz.appendChild(encabezado);
        return raiz;
    }

    // ---------------------------------------------------------------------
    // Panel: Antigüedad (solo muestra los años ya registrados del trabajador)
    // ---------------------------------------------------------------------
    function crearPanelAntiguedad(host) {
        const raiz = host;
        let anios = 0;

        const vista = crearInputLectura("antiguedad_anios_vista", "text");
        const oculto = document.createElement("input");
        oculto.type = "hidden";
        oculto.name = "antiguedad_anios";

        const pct = crearInputLectura("antiguedad_pct", "number");
        const monto = crearInputLectura("antiguedad_monto", "number");

        raiz.appendChild(crearCampo("Años de antigüedad", vista));
        raiz.appendChild(oculto);
        raiz.appendChild(crearCampo("Porcentaje (%)", pct));
        raiz.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        return {
            raiz: raiz,
            autollenar: function (opcion, avisos) {
                anios = Math.min(30, parseInt(opcion.dataset.anosServicio, 10) || 0);
                oculto.value = anios >= 1 ? String(anios) : "";
                vista.value = anios >= 1 ? anios + (anios === 1 ? " año" : " años") : "";
                if (anios < 1) avisos.push("El trabajador no tiene años de servicio registrados.");
            },
            calcular: function (base) {
                const p = porcentajeAntiguedad(anios);
                const valido = p > 0;
                const m = valido ? (base * p) / 100 : 0;
                pct.value = valido ? p.toFixed(2) : "";
                monto.value = moneda(m);
                return m;
            },
            validar: function () {
                return anios >= 1 ? "" : "El trabajador no tiene años de servicio registrados.";
            }
        };
    }

    // ---------------------------------------------------------------------
    // Panel: Profesionalización (solo muestra el nivel académico registrado)
    // ---------------------------------------------------------------------
    function crearPanelProfesionalizacion(host) {
        const raiz = host;
        let condicion = "";

        const vista = crearInputLectura("profesionalizacion_nivel_vista", "text");
        const oculto = document.createElement("input");
        oculto.type = "hidden";
        oculto.name = "profesionalizacion_nivel";

        const pct = crearInputLectura("profesionalizacion_pct", "number");
        const monto = crearInputLectura("profesionalizacion_monto", "number");

        raiz.appendChild(crearCampo("Nivel académico", vista));
        raiz.appendChild(oculto);
        raiz.appendChild(crearCampo("Porcentaje (%)", pct));
        raiz.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        return {
            raiz: raiz,
            autollenar: function (opcion, avisos) {
                const registrado = (opcion.dataset.nivelEducacion || "").trim();
                condicion = NIVEL_A_CONDICION[registrado] || "";
                oculto.value = condicion;
                vista.value = registrado;
                if (!condicion) {
                    avisos.push("El nivel de educación del trabajador (" +
                        (registrado || "sin registrar") +
                        ") no genera prima por profesionalización.");
                }
            },
            calcular: function (base) {
                const n = NIVELES.find(function (x) { return x.valor === condicion; });
                const p = n ? n.pct : 0;
                const m = p > 0 ? (base * p) / 100 : 0;
                pct.value = p > 0 ? String(p) : "";
                monto.value = moneda(m);
                return m;
            },
            validar: function () {
                return condicion ? "" : "El nivel de educación del trabajador no genera prima por profesionalización.";
            }
        };
    }

    // ---------------------------------------------------------------------
    // Panel: Hijos (solo muestra la cantidad registrada y el monto por hijo)
    // ---------------------------------------------------------------------
    // Monto que se aplica a cada hijo (cambiar aquí si la regla es otra: "56.00" o "12.50")
    const MONTO_HIJO_DEFECTO = "56.00";

    function crearPanelHijos(host) {
        const raiz = host;
        let cantidadHijos = 0;

        const vista = crearInputLectura("cantidad_hijos_vista", "text");
        const oculto = document.createElement("input");
        oculto.type = "hidden";
        oculto.name = "cantidad_hijos";

        const listaHijos = document.createElement("div");
        listaHijos.style.gridColumn = "1 / -1";
        listaHijos.style.display = "none";
        listaHijos.style.gridTemplateColumns = "repeat(2, 1fr)";
        listaHijos.style.gap = "15px 25px";

        const monto = crearInputLectura("hijos_monto", "number");

        raiz.appendChild(crearCampo("Cantidad de hijos", vista));
        raiz.appendChild(oculto);
        raiz.appendChild(listaHijos);
        raiz.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        // Al editar: recupera los montos por hijo que se guardaron antes
        function montosPrevios() {
            try { return JSON.parse(formulario.dataset.montosPrevios || "[]"); } catch (e) { return []; }
        }

        function generarHijos(n) {
            const previos = montosPrevios();
            listaHijos.innerHTML = "";

            if (!Number.isInteger(n) || n < 1 || n > 20) {
                listaHijos.style.display = "none";
                return;
            }

            listaHijos.style.display = "grid";
            for (let i = 0; i < n; i++) {
                const valor = MONTOS_HIJO.some(function (m) { return m.valor === previos[i]; })
                    ? previos[i]
                    : MONTO_HIJO_DEFECTO;

                // Select con los dos montos posibles por hijo: 56,00 Bs. o 12,50 Bs.
                const select = crearSelect("monto_hijo_" + i, "monto_hijo[]", MONTOS_HIJO, null);
                select.value = valor;
                select.addEventListener("change", recalcular);

                listaHijos.appendChild(crearCampo("Monto del hijo " + (i + 1), select));
            }
        }

        return {
            raiz: raiz,
            autollenar: function (opcion, avisos) {
                const h = parseInt(opcion.dataset.hijos, 10) || 0;
                cantidadHijos = h >= 1 ? Math.min(h, 20) : 0;
                oculto.value = cantidadHijos >= 1 ? String(cantidadHijos) : "";
                vista.value = cantidadHijos >= 1 ? cantidadHijos + (cantidadHijos === 1 ? " hijo" : " hijos") : "";
                generarHijos(cantidadHijos);
                if (cantidadHijos < 1) avisos.push("El trabajador no tiene hijos registrados.");
            },
            calcular: function () {
                const total = Array.from(listaHijos.querySelectorAll("select")).reduce(function (s, el) {
                    return s + numero(el.value);
                }, 0);
                monto.value = moneda(total);
                return total;
            },
            validar: function () {
                return cantidadHijos >= 1 ? "" : "El trabajador no tiene hijos registrados.";
            }
        };
    }

    // ---------------------------------------------------------------------
    // Panel: Prima nueva del catálogo (monto = base salarial x porcentaje)
    // ---------------------------------------------------------------------
    let contadorNuevas = 0;

    function crearPanelNueva(host, porcentaje) {
        const n = ++contadorNuevas;
        const pct = crearInputLectura("nueva_pct_" + n, "number");
        const monto = crearInputLectura("nueva_monto_" + n, "number");

        pct.value = porcentaje.toFixed(2);

        host.appendChild(crearCampo("Porcentaje (%)", pct));
        host.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        return {
            raiz: host,
            autollenar: function () { /* no depende de datos del trabajador */ },
            calcular: function (base) {
                const m = porcentaje > 0 ? (base * porcentaje) / 100 : 0;
                monto.value = moneda(m);
                return m;
            },
            validar: function () {
                return porcentaje > 0 ? "" : "La prima no tiene un porcentaje válido.";
            }
        };
    }

    const FABRICA = {};
    FABRICA[T_ANT] = crearPanelAntiguedad;
    FABRICA[T_PROF] = crearPanelProfesionalizacion;
    FABRICA[T_HIJ] = crearPanelHijos;

    const activos = {}; // tipo -> panel

    // ---------------------------------------------------------------------
    // Cálculo general
    // ---------------------------------------------------------------------
    function recalcular() {
        const opcion = opcionTrabajador();
        const global = numero(sueldoBase ? sueldoBase.value : 0);
        const base = opcion ? numero(opcion.dataset.basePrima) : global;
        const salario = opcion ? numero(opcion.dataset.salarioMensual) : global;

        let suma = 0;
        Object.keys(activos).forEach(function (tipo) {
            suma += activos[tipo].calcular(base);
        });

        if (montoTotal) montoTotal.value = moneda(salario + suma);
    }

    // ---------------------------------------------------------------------
    // Disponibilidad de cada prima según el trabajador
    //  - Obrero: no tiene prima por profesionalización
    //  - Las primas que el trabajador ya tiene registradas se bloquean
    // ---------------------------------------------------------------------
    function actualizarDisponibilidad() {
        if (modo !== "registrar") return; // al editar/consultar la prima ya existe
        const opcion = opcionTrabajador();
        const tipo = ((opcion && opcion.dataset.tipoPersonal) || "").toLowerCase();
        const obrero = tipo.includes("obrero");

        let registradas = [];
        try { registradas = JSON.parse((opcion && opcion.dataset.primasRegistradas) || "[]"); } catch (e) {}

        filas.forEach(function (f) {
            const noAplica = obrero && f.tipo === T_PROF;
            const yaRegistrada = registradas.includes(f.tipo);
            const bloquear = noAplica || yaRegistrada;

            f.fila.style.display = noAplica ? "none" : "";
            if (bloquear) f.check.checked = false;
            f.check.disabled = bloquear;
            f.fila.style.opacity = yaRegistrada ? "0.55" : "";
            if (f.nota) f.nota.textContent = yaRegistrada ? " (ya registrada para este trabajador)" : "";
        });
    }

    // ---------------------------------------------------------------------
    // Mostrar el panel de cada prima marcada
    // ---------------------------------------------------------------------
    function renderPaneles(autollenarTodos) {
        const opcion = opcionTrabajador();
        const avisos = [];

        filas.forEach(function (f) {
            const marcada = f.check.checked && !f.check.disabled;
            let panel = activos[f.tipo];

            if (!marcada) {
                if (panel) {
                    f.panel.innerHTML = "";
                    delete activos[f.tipo];
                }
                f.panel.style.display = "none";
                return;
            }

            const esNuevo = !panel;
            if (esNuevo) {
                f.panel.innerHTML = "";
                panel = f.nueva ? crearPanelNueva(f.panel, f.pct) : FABRICA[f.tipo](f.panel);
                activos[f.tipo] = panel;
            }
            f.panel.style.display = "grid";

            if (opcion && (esNuevo || autollenarTodos)) {
                panel.autollenar(opcion, avisos);
            }

            if (modo === "consultar") {
                f.panel.querySelectorAll("input:not([type=hidden]), select").forEach(function (e) { e.disabled = true; });
            }
        });

        recalcular();
        if (avisos.length) mostrarAlerta(avisos.join("\n"));
    }

    // ---------------------------------------------------------------------
    // Salario vigente del cargo (misma consulta que usaba valid_primas.js)
    // ---------------------------------------------------------------------
    // Obrero: solo se muestra el sueldo base. Resto del personal: solo el sueldo por cargo.
    function actualizarVisibilidadSueldos(tipo) {
        if (!campoSueldoBase || !campoSueldoCargo) return;
        const t = String(tipo || "").toLowerCase();
        if (!t) {
            campoSueldoBase.style.display = "";
            campoSueldoCargo.style.display = "";
            return;
        }
        const obrero = t.includes("obrero");
        campoSueldoBase.style.display = obrero ? "" : "none";
        campoSueldoCargo.style.display = obrero ? "none" : "";
    }

    async function actualizarSueldoCargo() {
        const opcion = opcionTrabajador();

        actualizarVisibilidadSueldos(opcion ? opcion.dataset.tipoPersonal : "");

        if (!opcion) {
            consultaSalarioCorrecta = true;
            if (sueldoCargo) sueldoCargo.value = "0.00";
            if (origenSalario) origenSalario.value = "";
            if (tipoPersonal) tipoPersonal.value = "";
            if (estatusLaboral) estatusLaboral.value = "";
            recalcular();
            return;
        }

        const token = ++tokenConsultaSalario;
        consultaSalarioCorrecta = false;

        try {
            const respuesta = await fetch(
                "/FUNDACITE/controladores/consultar_salario_cargo.php?id_trabajador=" + encodeURIComponent(opcion.value),
                { method: "GET", headers: { "Accept": "application/json" }, cache: "no-store", credentials: "same-origin" }
            );
            const datos = await respuesta.json();
            if (token !== tokenConsultaSalario) return;
            if (!respuesta.ok || !datos.success) throw new Error(datos.message || "No se pudo consultar el salario.");

            if (String(datos.tipo_personal || "").toLowerCase().includes("obrero")) {
                // Obrero: solo sueldo base (sueldo mínimo)
                datos.sueldo_cargo = 0;
                datos.origen_salario = "base";
                datos.salario_mensual = datos.sueldo_base;
                datos.base_prima = datos.sueldo_base;
                datos.salario_cargo_registrado = false;
            } else {
                // Resto del personal: solo el sueldo de su cargo; las primas se calculan sobre ese sueldo
                const cargo = Number(datos.sueldo_cargo) || 0;
                datos.origen_salario = "cargo";
                datos.salario_mensual = cargo;
                datos.base_prima = cargo;
            }

            opcion.dataset.sueldoCargo = String(datos.sueldo_cargo ?? 0);
            opcion.dataset.salarioMensual = String(datos.salario_mensual ?? 0);
            opcion.dataset.basePrima = String(datos.base_prima ?? datos.sueldo_base ?? 0);
            opcion.dataset.origenSalario = String(datos.origen_salario ?? "base");
            opcion.dataset.tipoPersonal = String(datos.tipo_personal ?? "");
            opcion.dataset.estatus = String(datos.status ?? "");
            consultaSalarioCorrecta = true;

            const origen = String(datos.origen_salario || "base");
            const etiquetasOrigen = {
                "cargo": "Sueldo por cargo",
                "base": "Sueldo base",
                "base+cargo": "Sueldo base + sueldo por cargo"
            };

            if (sueldoCargo) sueldoCargo.value = moneda(datos.sueldo_cargo);
            if (origenSalario) origenSalario.value = etiquetasOrigen[origen] || "No determinado";
            if (tipoPersonal) {
                let etiqueta = datos.tipo_personal || "";
                if (etiqueta === "Obrero") {
                    etiqueta += " (aplica sueldo base)";
                }
                tipoPersonal.value = etiqueta;
            }
            if (estatusLaboral) estatusLaboral.value = datos.status || "";
            actualizarVisibilidadSueldos(datos.tipo_personal);

            recalcular();
        } catch (error) {
            if (token !== tokenConsultaSalario) return;
            consultaSalarioCorrecta = false;
            if (origenSalario) origenSalario.value = "No se pudo consultar";
            console.error("Consulta de salario por cargo fallida:", error);
        }
    }

    // ---------------------------------------------------------------------
    // Eventos
    // ---------------------------------------------------------------------
    // (el script de cascada, cargado antes, ya actualizó los selects)
    filas.forEach(function (f) {
        f.check.addEventListener("change", function () {
            if (f.check.checked && !opcionTrabajador()) {
                f.check.checked = false;
                mostrarAlerta("Seleccione primero un trabajador.");
                trabajador.focus();
                return;
            }
            renderPaneles(false);
        });
    });

    trabajador.addEventListener("change", function () {
        actualizarDisponibilidad();
        renderPaneles(true); // vuelve a llenar todas las primas marcadas con los datos del nuevo trabajador
        actualizarSueldoCargo();
    });

    formulario.addEventListener("submit", function (evento) {
        if (enviando) { evento.preventDefault(); evento.stopImmediatePropagation(); return; }
        let mensaje = "";

        if (!trabajador.value) {
            mensaje = "Debe seleccionar un trabajador.";
            trabajador.focus();
        } else if (!consultaSalarioCorrecta) {
            mensaje = "No se ha podido verificar el salario vigente del cargo en la base de datos. Espere la consulta o vuelva a seleccionar el trabajador.";
            trabajador.focus();
        } else if (Object.keys(activos).length === 0) {
            mensaje = "Seleccione al menos una prima.";
            filas[0].check.focus();
        } else {
            const tipos = Object.keys(activos);
            for (let i = 0; i < tipos.length && !mensaje; i++) {
                mensaje = activos[tipos[i]].validar();
            }
        }

        if (mensaje) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            mostrarAlerta(mensaje);
            return;
        }

        recalcular();
        enviando = true; // evita un segundo envío por doble clic
    });

    window.addEventListener("pageshow", function () { enviando = false; });

    formulario.addEventListener("reset", function () {
        setTimeout(function () {
            actualizarDisponibilidad();
            renderPaneles(false);
            actualizarSueldoCargo();
        }, 0);
    });

    // Inicializar (también restaura el estado tras un error al guardar)
    let previas = [];
    try { previas = JSON.parse(grupo.dataset.previas || "[]"); } catch (e) {}
    actualizarDisponibilidad();
    filas.forEach(function (f) {
        if (previas.includes(f.tipo) && !f.check.disabled) f.check.checked = true;
    });
    renderPaneles(false);
    actualizarSueldoCargo();
});

// =====================================================
// ALERTA PERSONALIZADA
// =====================================================
function showAlert(message, success = false) { FundaciteAlerta.mostrar(message, success ? 'success' : 'error'); }

function closeAlert() { FundaciteAlerta.cerrar(false); }

</script>



<script src="/FUNDACITE/vistas/js/valid_primas_extra.js"></script>
</body>
</html>