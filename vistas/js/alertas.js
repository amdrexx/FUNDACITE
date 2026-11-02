/**
 * alertas.js
 * Helper único para mostrar la alerta del sistema con el color/ícono
 * correcto según el tipo de mensaje. Usa los IDs históricos del proyecto
 * (customAlert / alertMessage / closeAlert) para no romper el código que
 * cada módulo ya tenía, y añade mostrarAlerta(mensaje, tipo) como forma
 * recomendada de abrirla desde nuevo código.
 *
 * Uso:
 *   mostrarAlerta('Trabajador registrado correctamente.', 'success');
 *   mostrarAlerta('La cédula ya existe.', 'error');
 *   mostrarAlerta('Revise los campos marcados.', 'warning');
 */
var SYS_ALERT_ICONS = {
    success: '\u2713', // check
    error: '!',
    warning: '!',
    info: 'i'
};

function mostrarAlerta(mensaje, tipo) {
    tipo = (tipo && SYS_ALERT_ICONS[tipo]) ? tipo : 'info';

    var cont = document.getElementById('customAlert');
    if (!cont) return;

    cont.className = 'custom-alert sys-alert sys-alert--' + tipo;

    var icon = document.getElementById('sysAlertIcon');
    if (icon) icon.textContent = SYS_ALERT_ICONS[tipo];

    var msg = document.getElementById('alertMessage');
    if (msg) msg.textContent = mensaje;

    cont.classList.remove('hidden');
}

function closeAlert() {
    var cont = document.getElementById('customAlert');
    if (cont) cont.classList.add('hidden');
}

// Alias moderno, por si el código nuevo prefiere este nombre.
function cerrarAlerta() {
    closeAlert();
}
