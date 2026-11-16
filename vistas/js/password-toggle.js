document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const wrapper = button.closest('.password-input-wrapper');
        const input = wrapper ? wrapper.querySelector('input') : null;
        const icon = button.querySelector('i');

        if (!input) return;

        button.addEventListener('click', () => {
            const mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';

            const accion = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
            button.setAttribute('aria-label', accion);
            button.setAttribute('title', accion);
            button.setAttribute('aria-pressed', String(mostrar));

            if (icon) {
                icon.classList.toggle('bi-eye', !mostrar);
                icon.classList.toggle('bi-eye-slash', mostrar);
            }

            input.focus({ preventScroll: true });
        });
    });
});
