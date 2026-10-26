// Interacciones del panel. Importar/exportar se procesan mediante POST normal
// por los modelos PHP y devuelven el resultado con una redirección al dashboard.
(function () {
    'use strict';

    const root = document.getElementById('eiRoot');
    if (!root) return;
    const $ = (id) => document.getElementById(id);
    const fab = $('eiFab');
    const panel = $('eiPanel');
    const close = $('eiClose');
    const tabExport = $('eiTabExportar');
    const tabImport = $('eiTabImportar');
    const secExport = $('eiPanelExportar');
    const secImport = $('eiPanelImportar');
    const file = $('eiArchivo');
    const fileText = $('eiFileTexto');
    const fileLabel = $('eiFileLabel');
    const btnFile = $('eiBtnImportarArchivo');
    const selectBackup = $('eiSelectRespaldo');
    const btnBackup = $('eiBtnImportarRespaldo');

    let open = false;
    function togglePanel(next) {
        open = next;
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', String(!open));
        fab.setAttribute('aria-expanded', String(open));
        if (open) panel.focus({ preventScroll: true });
    }
    fab.addEventListener('click', () => togglePanel(!open));
    close.addEventListener('click', () => { togglePanel(false); fab.focus(); });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && open) { togglePanel(false); fab.focus(); }
    });
    document.addEventListener('pointerdown', (event) => {
        if (open && !root.contains(event.target)) togglePanel(false);
    });

    function showTab(exporting) {
        tabExport.setAttribute('aria-selected', String(exporting));
        tabImport.setAttribute('aria-selected', String(!exporting));
        secExport.hidden = !exporting;
        secImport.hidden = exporting;
    }
    tabExport.addEventListener('click', () => showTab(true));
    tabImport.addEventListener('click', () => showTab(false));

    const resultado = new URLSearchParams(window.location.search).get('respaldo_estado');
    if (['exportado', 'importado', 'error'].includes(resultado)) {
        showTab(resultado === 'importado' || (resultado === 'error' && $('eiEstado')) ? false : true);
        togglePanel(true);
    }

    file.addEventListener('change', () => {
        const selected = file.files[0];
        fileText.textContent = selected ? selected.name : 'Elegir archivo .sql…';
        fileLabel.classList.toggle('has-file', Boolean(selected));
        btnFile.disabled = !selected;
    });
    selectBackup.addEventListener('change', () => { btnBackup.disabled = !selectBackup.value; });

    [$('eiFormExportar'), $('eiFormArchivo'), $('eiFormRespaldo')].forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form === $('eiFormArchivo') && !file.files[0]) {
                event.preventDefault();
                return;
            }
            if (form === $('eiFormRespaldo') && !selectBackup.value) {
                event.preventDefault();
                return;
            }
            if ((form === $('eiFormArchivo') || form === $('eiFormRespaldo')) &&
                !window.confirm('La importación reemplaza los datos actuales. ¿Deseas continuar?')) {
                event.preventDefault();
                return;
            }
            if (form === $('eiFormArchivo') || form === $('eiFormRespaldo')) {
                // El documento queda visible mientras el servidor ejecuta el SQL.
                // Evita que el contador local redirija al logout antes del resultado.
                window.__fundaciteRespaldoEnCurso = true;
            }
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.dataset.originalText = button.textContent.trim();
                button.textContent = form === $('eiFormExportar') ? 'Generando respaldo…' : 'Importando respaldo…';
            }
        });
    });
})();
