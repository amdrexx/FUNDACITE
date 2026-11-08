document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formdisfrute');
    if (!form) return;

    const inicio = form.querySelector('[name="fecha_inicio"]');
    // El servidor entrega la fecha actual usando America/Caracas. Se conserva
    // ese valor para no depender de la zona horaria del navegador del usuario.
    const iso = inicio ? inicio.value : '';
    if (inicio) {
        inicio.min = iso;
        inicio.max = iso;
        inicio.readOnly = true;
    }

    const textos = [
        ['descripcion', 1000, 10],
        ['observaciones', 1000, 0]
    ];

    textos.forEach(([name, max]) => {
        const campo = form.querySelector(`[name="${name}"]`);
        if (!campo) return;
        campo.maxLength = max;
        campo.addEventListener('input', function () {
            this.value = this.value.slice(0, max).replace(/(.)\1{4,}/gi, '$1$1$1');
        });
    });

    form.addEventListener('submit', function (event) {
        if (event.submitter && event.submitter.value === 'limpiar') return;

        const errores = [];
        if (inicio && inicio.value !== iso) {
            errores.push('La fecha de inicio debe ser la fecha actual.');
        }

        textos.forEach(([name, max, min]) => {
            const campo = form.querySelector(`[name="${name}"]`);
            if (!campo) return;
            const valor = campo.value.trim();
            if (valor.length > max) errores.push(`${name} no puede superar ${max} caracteres.`);
            if (min > 0 && valor.length < min) errores.push(`${name} debe tener al menos ${min} caracteres.`);
        });

        if (!form.querySelector('input[name="tipo_remuneracion"]:checked')) {
            errores.push('Debe seleccionar Remunerado o No remunerado.');
        }
        if (!form.querySelector('input[name="requiere_suplente"]:checked')) {
            errores.push('Debe indicar si se requiere suplente.');
        }

        if (errores.length) {
            event.preventDefault();
            FundaciteAlerta.mostrar(errores.join('\n'), 'warning');
        }
    });
});
