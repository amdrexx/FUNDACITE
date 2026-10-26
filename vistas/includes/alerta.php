<?php
/**
 * includes/alerta.php
 * Alerta única del sistema. Antes cada vista repetía su propio bloque
 * <div id="customAlert">...</div> (gris, sin distinguir éxito/error, y en
 * algunos módulos -lista_trabajadores, lista_primas, lista_usuarios- ni
 * siquiera estaba declarado, así que el mensaje nunca se mostraba).
 *
 * Mantiene los IDs históricos (customAlert / alertMessage / closeAlert())
 * para que el código existente de cada módulo siga funcionando sin
 * cambios, y además detecta automáticamente los nombres de variable que
 * ya usa cada vista para sus mensajes de sesión ($exito, $exito_edicion,
 * $exito_registro, $mensajeExito / $mensajeError) para pintar la alerta
 * del color correcto (success/error) apenas carga la página.
 */
$__sysExito = $exito ?? $exito_edicion ?? $exito_registro ?? $mensajeExito ?? '';
$__sysErrores = $errores ?? [];
if (empty($__sysErrores) && !empty($mensajeError)) {
    $__sysErrores = is_array($mensajeError) ? $mensajeError : [$mensajeError];
}
?>
<div id="customAlert" class="custom-alert sys-alert sys-alert--info hidden" role="alertdialog" aria-live="assertive" aria-modal="true">
    <div class="alert-box sys-alert-box">
        <div class="sys-alert-icon" id="sysAlertIcon">i</div>
        <div class="sys-alert-content">
            <p id="alertMessage" class="sys-alert-message"></p>
            <button type="button" onclick="closeAlert()">Aceptar</button>
        </div>
    </div>
</div>
<script src="/FUNDACITE/vistas/js/alertas.js"></script>
<?php if (!empty($__sysErrores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        mostrarAlerta(<?php echo json_encode(implode("\n", (array) $__sysErrores)); ?>, 'error');
    });
</script>
<?php elseif (!empty($__sysExito)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        mostrarAlerta(<?php echo json_encode($__sysExito); ?>, 'success');
    });
</script>
<?php endif; ?>
