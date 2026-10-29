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
if (in_array($accion, ['editar', 'consultar'], true) && $id > 0) {
    $prima_data = $objprima->obtenerPorId($id) ?? [];
    if (!$prima_data) { $_SESSION['errores'] = ['No se encontró la prima solicitada.']; header('Location: lista_primas.php'); exit; }
}
$errores = $_SESSION['errores'] ?? [];
$exito = $_SESSION['exito'] ?? '';
$old = $_SESSION['old'] ?? [];
if (!is_array($errores)) $errores = [$errores];
if (!is_array($old)) $old = [];
$trabajadores = $objprima->listarTrabajadoresActivos((int)($prima_data['id_trabajador'] ?? 0));
$sueldoBase = $objprima->obtenerSueldoBase() ?? 0.0;
$trabajadorSeleccionado = (string)($old['id_trabajador'] ?? $prima_data['id_trabajador'] ?? '');
$txt_tipo = (string)($old['tipo_prima'] ?? $prima_data['tipo_prima'] ?? '');
$estatusSeleccionado = (string)($old['estatus'] ?? $prima_data['estatus'] ?? 'Activo');
$montosPrevios = [];
if (!empty($prima_data['condicion']) && strpos((string)$prima_data['condicion'], ':') !== false) {
    $parte = substr(strrchr((string)$prima_data['condicion'], ':'), 1);
    $montosPrevios = array_map('trim', explode(',', $parte));
}
$deshabilitado = $accion === 'consultar' ? 'disabled' : '';
$bloquear = ($accion === 'editar' || $accion === 'consultar') ? 'disabled' : '';
$titulo = $accion === 'editar' ? 'Modificar prima' : ($accion === 'consultar' ? 'Consulta de prima' : 'Registro de primas');
$btnTexto = $accion === 'editar' ? 'Actualizar' : 'Guardar';
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
unset($_SESSION['errores'], $_SESSION['exito'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar primas</title>
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

<div style="display:flex; flex-direction:column; gap:30px;">

<!-- FORMULARIO -->
<div class="form-card">

    <form
        id="formprimas"
        data-montos-previos='<?= h(json_encode($montosPrevios)) ?>'
        method="POST"
        action="../controladores/ctrl_primas.php"
        novalidate
    >

        <h2 class="full-width" style="text-align:center;">
            <?= h($titulo) ?>
        </h2>
        <?php if ($accion === 'editar'): ?>
            <input type="hidden" name="id_prima" value="<?= (int)$prima_data['id_prima'] ?>">
            <input type="hidden" name="id_trabajador" value="<?= (int)$prima_data['id_trabajador'] ?>">
            <input type="hidden" name="tipo_prima" value="<?= h($prima_data['tipo_prima']) ?>">
        <?php endif; ?>

        <div class="field">
    <label for="id_trabajador">Trabajador</label>

    <select
        name="id_trabajador"
        id="id_trabajador"
        required
        <?= $deshabilitado ?>
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
        <!-- TIPO DE PRIMA -->
        <div class="field">
            <label for="tipo_prima">Prima</label>

            <select name="tipo_prima" id="tipo_prima" required>
                <option value="">Seleccione una prima</option>
                <option value="Prima por antigüedad" <?= $txt_tipo === "Prima por antigüedad" ? "selected" : "" ?>>Antigüedad</option>
                <option value="Prima por profesionalización" <?= $txt_tipo === "Prima por profesionalización" ? "selected" : "" ?>>Profesionalización</option>
                <option value="Prima por hijos" <?= $txt_tipo === "Prima por hijos" ? "selected" : "" ?>>Hijos</option>
            </select>
        </div>

        <!-- ESTATUS DE LA PRIMA -->
        <div class="field">
            <label for="estatus">Estatus de la prima</label>
            <select name="estatus" id="estatus" required <?= $deshabilitado ?>>
                <option value="Activo" <?= $estatusSeleccionado === 'Activo' ? 'selected' : '' ?>>Activo</option>
                <option value="Inactivo" <?= $estatusSeleccionado === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <!-- CANTIDAD DE HIJOS -->
<div class="field" id="campoCantidadHijos" style="display:none;">
    <label for="cantidad_hijos">Cantidad de hijos</label>
    <input type="number" id="cantidad_hijos" name="cantidad_hijos" min="1" max="20">
</div>

<!-- MONTO DEL HIJO 1 -->
<div class="field" id="campoMontoHijo1" style="display:none;">
    <label for="monto_hijo_0">Monto del hijo 1</label>
    <select id="monto_hijo_0" name="monto_hijo[]" class="monto-hijo-select" required>
        <option value="56.00">56,00 Bs.</option>
        <option value="12.50">12,50 Bs.</option>
    </select>
</div>

<!-- CONTENEDOR PARA LOS HIJOS RESTANTES -->
<div id="contenedorHijos" style="display:none; grid-column: 1 / -1; grid-template-columns: repeat(2, 1fr); gap: 15px 25px;">
</div>

        <!-- CONDICIÓN -->
<div class="field" id="campoCondicion" style="display:none;">
    <label for="condicion" id="labelCondicion">Condición</label>

    <select
        id="condicion"
        name="condicion"
        readonly
        required
    >
        <option value="">Seleccione alguna prima</option>
    </select>
</div>

        <!-- VALOR -->
        <div class="field" id="campoValor">
            <label for="valor" id="labelValor">
                Valor
            </label>

            <input
                type="number"
                name="valor"
                id="valor"
                step="0.01"
                min="0"
                readonly
                required
            >
        </div>
<div class="field">
    <label for="sueldo_base">Sueldo base (Bs.)</label>

    <input
        type="number"
        id="sueldo_base"
        name="sueldo_base"
        min="0"
        step="0.01"
        value="<?= htmlspecialchars((string)$sueldoBase, ENT_QUOTES, 'UTF-8') ?>"
        readonly
        required
    >
</div>

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
    <input
        type="text"
        id="origen_salario"
        placeholder="Seleccione un trabajador"
        readonly
        aria-live="polite"
    >
</div>

<div class="field">
    <label for="sueldo_cargo">Sueldo por cargo (Bs.)</label>
    <input
        type="text"
        id="sueldo_cargo"
        placeholder="Seleccione un trabajador"
        readonly
    >
</div>

<div class="field full-width">
    <label for="monto_total">Monto total (Bs.)</label>

    <input
        type="number"
        id="monto_total"
        name="monto_total"
        value="<?= htmlspecialchars(
            (string) $sueldoBase,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        min="0"
        step="0.01"
        readonly
    >
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
        
<script src="/FUNDACITE/vistas/js/valid_primas.js"></script>

<!-- Script para restringir tipos de prima según el trabajador (Obrero -> Solo Antigüedad e Hijos) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectTrabajador = document.getElementById('id_trabajador');
    const selectTipoPrima = document.getElementById('tipo_prima');

    function actualizarOpcionesPrimas() {
        const optionSeleccionada = selectTrabajador.options[selectTrabajador.selectedIndex];
        const tipoPersonal = optionSeleccionada ? optionSeleccionada.getAttribute('data-tipo-personal') : '';
        const opciones = selectTipoPrima.querySelectorAll('option');

        const esObrero = tipoPersonal && tipoPersonal.toLowerCase().includes('obrero');

        opciones.forEach(opcion => {
            if (opcion.value === "") return; // Mantener la opción por defecto

            if (esObrero && opcion.value === 'Prima por profesionalización') {
                opcion.style.display = 'none';
                opcion.disabled = true;
                
                if (selectTipoPrima.value === 'Prima por profesionalización') {
                    selectTipoPrima.value = '';
                }
            } else {
                opcion.style.display = 'block';
                opcion.disabled = false;
            }
        });
    }

    if (selectTrabajador) {
        selectTrabajador.addEventListener('change', actualizarOpcionesPrimas);
        actualizarOpcionesPrimas();
    }
});
</script>

<!-- Alertas de errores/éxito -->
<?php if (!empty($errores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode(implode("\n", $errores)); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

<?php if (!empty($exito)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($exito); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

<script src="/FUNDACITE/vistas/js/valid_primas_extra.js"></script>
</body>
</html>