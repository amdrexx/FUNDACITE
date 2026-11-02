/* Validaciones del módulo Contrato.
 * La fecha de ingreso puede ser histórica; únicamente se impiden fechas futuras.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formContratos');
    if (!form) return;

    const inicio = form.querySelector('[name="fecha_contrato"]');
    const fin = form.querySelector('[name="fecha_fin"]');

    // Fecha local (evita diferencias por zona horaria).
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const isoHoy = [
        hoy.getFullYear(),
        String(hoy.getMonth() + 1).padStart(2, '0'),
        String(hoy.getDate()).padStart(2, '0')
    ].join('-');

    // IMPORTANTE: no establecer min en fecha_contrato. Se permiten fechas pasadas.
    // El máximo evita registrar un ingreso situado en el futuro.
    if (inicio) {
        inicio.removeAttribute('min');
        inicio.max = isoHoy;
    }

    const textos = ['lugar_trabajo', 'gaceta_designacion_presidente'];
    textos.forEach(nombre => {
        const el = form.querySelector(`[name="${nombre}"]`);
        if (!el) return;
        el.maxLength = nombre === 'lugar_trabajo' ? 150 : 60;
        el.addEventListener('input', () => {
            el.value = el.value.slice(0, el.maxLength).replace(/(.)\1{3,}/gi, '$1$1$1');
        });
    });

    form.addEventListener('submit', evento => {
        const errores = [];
        if (inicio?.value && inicio.value > isoHoy) {
            errores.push('La fecha de ingreso/contrato no puede ser futura. Se permiten fechas anteriores.');
        }
        if (inicio?.value && fin?.value && fin.value < inicio.value) {
            errores.push('La fecha de finalización no puede ser anterior a la fecha de inicio.');
        }

        textos.forEach(nombre => {
            const el = form.querySelector(`[name="${nombre}"]`);
            const maximo = nombre === 'lugar_trabajo' ? 150 : 60;
            if (el && el.value.trim().length > maximo) {
                errores.push(`${nombre === 'lugar_trabajo' ? 'Lugar de trabajo' : 'Gaceta'} supera la longitud permitida.`);
            }
        });

        if (errores.length) {
            evento.preventDefault();
            alert(errores.join('\n'));
        }
    });
});
