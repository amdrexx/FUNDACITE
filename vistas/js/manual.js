// ============================================================================
// ARCHIVO: vistas/js/manual.js
// DESCRIPCIÓN: Comportamiento del botón flotante del Manual de Usuario.
//              Visible para todos los roles. Solo abre/cierra el panel y
//              carga el PDF en el iframe la primera vez que se abre (para no
//              descargar 46 páginas de PDF en cada carga de página).
// ============================================================================

(function () {
    'use strict';

    const root = document.getElementById('mnRoot');
    if (!root) return;

    const pdf = root.dataset.pdf;

    const fab = document.getElementById('mnFab');
    const panel = document.getElementById('mnPanel');
    const cerrarBtn = document.getElementById('mnClose');
    const frame = document.getElementById('mnFrame');

    let abierto = false;
    let cargado = false;

    function abrir() {
        if (abierto) return;
        abierto = true;

        if (!cargado) {
            frame.src = pdf + '#view=FitH';
            cargado = true;
        }

        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        fab.setAttribute('aria-expanded', 'true');
        panel.focus({ preventScroll: true });
    }

    function cerrar(devolverFoco) {
        if (!abierto) return;
        abierto = false;

        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        fab.setAttribute('aria-expanded', 'false');

        if (devolverFoco) fab.focus({ preventScroll: true });
    }

    fab.addEventListener('click', () => (abierto ? cerrar(false) : abrir()));
    cerrarBtn.addEventListener('click', () => cerrar(true));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && abierto) cerrar(true);
    });

    document.addEventListener('pointerdown', (e) => {
        if (abierto && !root.contains(e.target)) cerrar(false);
    });
})();
