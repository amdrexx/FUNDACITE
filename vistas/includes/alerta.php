<?php
/**
 * Componente único de alertas FUNDACITE.
 * Se incluye desde includes/layout.php para que todos los módulos usen
 * exactamente la misma alerta personalizada.
 */
$__sysFlash = $_SESSION['flash_alerta'] ?? null;
if ($__sysFlash !== null) unset($_SESSION['flash_alerta']);

$__sysErrores = $errores ?? [];
if (empty($__sysErrores) && !empty($errorValidacion)) $__sysErrores = $errorValidacion;
if (empty($__sysErrores) && !empty($_SESSION['errores'])) $__sysErrores = $_SESSION['errores'];
if (empty($__sysErrores) && !empty($_SESSION['error_edicion'])) $__sysErrores = $_SESSION['error_edicion'];
if (empty($__sysErrores) && !empty($mensajeError)) $__sysErrores = $mensajeError;
if (!is_array($__sysErrores) && $__sysErrores !== '') $__sysErrores = [$__sysErrores];

$__sysExito = '';
foreach ([$exito ?? '', $exito_edicion ?? '', $exito_registro ?? '', $mensajeExito ?? ''] as $__candidate) {
    if (!empty($__candidate)) { $__sysExito = $__candidate; break; }
}
$__sysAviso = $mensaje_alerta ?? '';
$__sysTipo = 'info';
$__sysMensaje = '';

if (is_array($__sysFlash) && !empty($__sysFlash['mensaje'])) {
    $__sysMensaje = (string) $__sysFlash['mensaje'];
    $__sysTipo = in_array($__sysFlash['tipo'] ?? 'info', ['success','error','warning','info'], true)
        ? $__sysFlash['tipo'] : 'info';
} elseif (!empty($__sysErrores)) {
    $__sysMensaje = implode("\n", array_map('strval', (array) $__sysErrores));
    $__sysTipo = 'error';
} elseif (!empty($__sysExito)) {
    $__sysMensaje = is_array($__sysExito) ? implode("\n", array_map('strval', $__sysExito)) : (string) $__sysExito;
    $__sysTipo = 'success';
} elseif (!empty($__sysAviso)) {
    $__sysMensaje = (string) $__sysAviso;
    $__sysStatusActual = (string) ($status ?? $_GET['status'] ?? '');
    $__sysTipo = in_array($__sysStatusActual, ['duplicate','error','restrict'], true) ? 'warning' : 'success';
} elseif (($status ?? ($_GET['status'] ?? '')) !== '') {
    $__sysStatus = (string) ($status ?? $_GET['status']);
    $__sysMensajes = [
        'success' => 'La operación se realizó correctamente.',
        'updated' => 'Los cambios se guardaron correctamente.',
        'deleted' => 'El registro se eliminó correctamente.',
        'error' => 'No se pudo completar la operación.',
        'restrict' => 'No se puede eliminar el registro porque tiene información relacionada.'
    ];
    if (isset($__sysMensajes[$__sysStatus])) {
        $__sysMensaje = $__sysMensajes[$__sysStatus];
        $__sysTipo = in_array($__sysStatus, ['error','restrict'], true) ? 'warning' : 'success';
    }
}
?>
<div id="customAlert" class="custom-alert sys-alert sys-alert--info hidden" role="dialog" aria-modal="true" aria-labelledby="sysAlertTitle" aria-describedby="alertMessage">
    <div class="alert-box sys-alert-box">
        <div class="sys-alert-icon" id="sysAlertIcon" aria-hidden="true">i</div>
        <div class="sys-alert-content">
            <h3 class="sys-alert-title" id="sysAlertTitle">Información</h3>
            <p id="alertMessage" class="sys-alert-message"></p>
            <div class="sys-alert-input-wrap hidden" id="sysAlertInputWrap">
                <textarea id="sysAlertInput" rows="4" maxlength="1000" aria-label="Justificación"></textarea>
            </div>
            <div class="sys-alert-actions">
                <button type="button" id="sysAlertCancel" class="sys-alert-cancel hidden">Cancelar</button>
                <button type="button" id="sysAlertPrimary" class="sys-alert-ok">Aceptar</button>
            </div>
        </div>
    </div>
</div>
<script src="/FUNDACITE/vistas/js/alertas.js"></script>
<?php if ($__sysMensaje !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.FundaciteAlerta) {
        FundaciteAlerta.mostrar(<?php echo json_encode($__sysMensaje, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>, <?php echo json_encode($__sysTipo); ?>);
    }
});
</script>
<?php endif; ?>
