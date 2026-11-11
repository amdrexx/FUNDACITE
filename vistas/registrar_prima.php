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
// Valor elegido en cada prima con varios valores: [id_prima del catálogo => id_prima_valor]
$valoresPrevios = [];
foreach ($primasTrabajador as $pt) {
    if (!empty($pt['id_prima_valor'])) { $valoresPrevios[(int) $pt['id_prima_catalogo']] = (int) $pt['id_prima_valor']; }
}
if (isset($old['valor_prima']) && is_array($old['valor_prima'])) {
    foreach ($old['valor_prima'] as $k => $v) { $valoresPrevios[(int) $k] = (int) $v; }
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
        data-modo="<?= h($accion) ?>"
        data-montos-previos='<?= h(json_encode($montosPrevios)) ?>'
        data-valores-previos='<?= h(json_encode((object) $valoresPrevios)) ?>'
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
                data-hijos-menores="<?= (int) $trabajador['hijos_menores'] ?>"
                data-hijos-mayores="<?= (int) $trabajador['hijos_mayores'] ?>"
                data-hijos-fuera="<?= (int) $trabajador['hijos_fuera_rango'] ?>"
                data-edades-hijos="<?= htmlspecialchars((string) $trabajador['edades_hijos'], ENT_QUOTES, 'UTF-8') ?>"
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
            // Primas fijas del sistema: si están en el catálogo se toman su prima y sus valores
            // (en "Prima por hijos" los valores del catálogo son los montos permitidos por hijo)
            $filasPrimas = [];
            foreach (['Prima por antigüedad', 'Prima por hijos', 'Prima por profesionalización'] as $nombreFija) {
                $c = $catalogoNuevas[$nombreFija] ?? null;
                $filasPrimas[$nombreFija] = [
                    'nueva'      => false,
                    'id_prima'   => (int) ($c['id_prima'] ?? 0),
                    'tipo_valor' => (string) ($c['tipo_valor'] ?? 'porcentaje'),
                    'valores'    => $c['valores'] ?? [],
                ];
            }

            // Primas nuevas registradas en "Registrar nueva prima" (catálogo)
            foreach ($catalogoNuevas as $nombreNueva => $infoNueva) {
                $nombreNueva = (string) $nombreNueva;
                if (!isset($filasPrimas[$nombreNueva])) {
                    $filasPrimas[$nombreNueva] = [
                        'nueva'      => true,
                        'id_prima'   => (int) $infoNueva['id_prima'],
                        'tipo_valor' => (string) $infoNueva['tipo_valor'],
                        'valores'    => $infoNueva['valores'],
                    ];
                }
            }

            // Editar/consultar: si el trabajador tiene una prima nueva que ya no está activa
            // en el catálogo, igual se muestra con el valor que tenía guardado
            if ($accion !== 'registrar') {
                $guardadas = $accion === 'editar' ? $primasTrabajador : [$prima_data];
                foreach ($guardadas as $pg) {
                    $tp = (string) ($pg['tipo_prima'] ?? '');
                    $pctG = (float) ($pg['porcentaje'] ?? 0);
                    $valG = [[
                        'id'    => (int) ($pg['id_prima_valor'] ?? 0),
                        'valor' => $pctG > 0 ? $pctG : (float) ($pg['monto'] ?? 0),
                    ]];

                    if ($tp !== '' && !isset($filasPrimas[$tp])) {
                        $filasPrimas[$tp] = [
                            'nueva'      => true,
                            'id_prima'   => (int) ($pg['id_prima_catalogo'] ?? 0),
                            'tipo_valor' => $pctG > 0 ? 'porcentaje' : 'monto',
                            'valores'    => $valG,
                        ];
                    }

                    // En consulta se muestra el valor con el que se guardó
                    if ($accion === 'consultar' && $tp !== '' && !empty($filasPrimas[$tp]['nueva'])) {
                        $filasPrimas[$tp]['tipo_valor'] = $pctG > 0 ? 'porcentaje' : 'monto';
                        $filasPrimas[$tp]['valores'] = $valG;
                    }
                }
            }

            foreach ($filasPrimas as $valorFila => $infoFila):
                $valorFila = (string) $valorFila;
                if ($accion !== 'registrar' && !in_array($valorFila, $tiposMostrar, true)) continue;
            ?>
            <div class="prima-row" data-tipo="<?= h($valorFila) ?>"
                 data-nueva="<?= $infoFila['nueva'] ? '1' : '0' ?>"
                 data-id-prima="<?= (int) $infoFila['id_prima'] ?>"
                 data-tipo-valor="<?= h($infoFila['tipo_valor']) ?>"
                 data-valores='<?= h(json_encode($infoFila['valores'])) ?>'>
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
        
<script src="/FUNDACITE/vistas/js/registrar_prima.js"></script>

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
<script src="/FUNDACITE/vistas/js/registrar_prima.js"></script>
</body>
</html>