/* Validación individual del módulo Municipio. */
document.addEventListener('DOMContentLoaded', () => {
    const selector = 'input[name="nombre"], input[data-validar-ubicacion]';
    const campos = document.querySelectorAll(selector);
    campos.forEach(campo => {
        campo.minLength = 3;
        campo.maxLength = 50;
        campo.addEventListener('input', () => {
            campo.value = campo.value
                .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ .\'-]/g, '')
                .replace(/(.)\1{2,}/gi, '$1$1')
                .slice(0, 50);
            campo.setCustomValidity('');
        });
    });
    document.addEventListener('submit', evento => {
        const formulario = evento.target;
        if (!(formulario instanceof HTMLFormElement)) return;
        for (const campo of formulario.querySelectorAll(selector)) {
            const valor = campo.value.trim();
            if (campo.required && !valor) {
                campo.setCustomValidity('Este campo es obligatorio.');
                campo.reportValidity();
                evento.preventDefault();
                return;
            }
            if (valor && (valor.length < 3 || valor.length > 50 || !/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(valor) || /(.)\1{2,}/i.test(valor))) {
                campo.setCustomValidity('Use entre 3 y 50 caracteres y evite repetir el mismo carácter más de 2 veces seguidas.');
                campo.reportValidity();
                evento.preventDefault();
                return;
            }
            campo.setCustomValidity('');
        }
    });
});
