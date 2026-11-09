// Habilita la fecha final solo para contratos a tiempo determinado.
document.addEventListener('DOMContentLoaded', function () {
    const formulario = document.getElementById('formContratos');
    if (!formulario) return;

    const tipoContrato = formulario.querySelector('[name="tipo_contrato"]');
    const fechaInicio = formulario.querySelector('[name="fecha_contrato"]');
    const fechaFin = formulario.querySelector('[name="fecha_fin"]');
    if (!tipoContrato || !fechaFin) return;

    function actualizarFechaFin() {
        const tipo = tipoContrato.value.toLowerCase();
        const esIndeterminado = tipo.includes('indefinido') || tipo.includes('indeterminado');

        fechaFin.disabled = esIndeterminado;
        fechaFin.required = !esIndeterminado;
        fechaFin.setAttribute('aria-disabled', esIndeterminado ? 'true' : 'false');

        if (esIndeterminado) {
            fechaFin.value = '';
            fechaFin.setCustomValidity('');
        }
    }

    tipoContrato.addEventListener('change', actualizarFechaFin);
    actualizarFechaFin();

    formulario.addEventListener('submit', function (evento) {
        if (fechaFin.disabled || !fechaFin.value || !fechaInicio?.value) return;

        if (fechaFin.value < fechaInicio.value) {
            evento.preventDefault();
            fechaFin.setCustomValidity('La fecha final no puede ser anterior a la fecha de inicio.');
            fechaFin.reportValidity();
        } else {
            fechaFin.setCustomValidity('');
        }
    });

    fechaFin.addEventListener('change', function () {
        fechaFin.setCustomValidity('');
    });
});
