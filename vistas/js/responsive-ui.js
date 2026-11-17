/* FUNDACITE: navegación adaptable y desplazamiento accesible de tablas.
 * No modifica las columnas ni el contenido de las tablas: en pantallas
 * reducidas solo habilita el desplazamiento horizontal dentro de su marco.
 */
(function () {
    'use strict';

    function initNavigation() {
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!toggle || !sidebar || !backdrop) return;

        const mobileQuery = window.matchMedia('(max-width: 992px)');

        function setOpen(open) {
            const shouldOpen = Boolean(open) && mobileQuery.matches;
            sidebar.classList.toggle('active', shouldOpen);
            document.body.classList.toggle('sidebar-open', shouldOpen);
            toggle.classList.toggle('active', shouldOpen);
            toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', shouldOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
            sidebar.setAttribute('aria-hidden', mobileQuery.matches && !shouldOpen ? 'true' : 'false');
            backdrop.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
            backdrop.tabIndex = shouldOpen ? 0 : -1;
        }

        toggle.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('active'));
        });

        backdrop.addEventListener('click', function () {
            setOpen(false);
            toggle.focus();
        });

        sidebar.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileQuery.matches) setOpen(false);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('active')) {
                setOpen(false);
                toggle.focus();
            }
        });

        function syncBreakpoint() {
            setOpen(false);
            if (!mobileQuery.matches) {
                sidebar.setAttribute('aria-hidden', 'false');
            }
        }

        if (typeof mobileQuery.addEventListener === 'function') {
            mobileQuery.addEventListener('change', syncBreakpoint);
        } else if (typeof mobileQuery.addListener === 'function') {
            mobileQuery.addListener(syncBreakpoint);
        }

        syncBreakpoint();
    }

    function enhanceTables() {
        document.querySelectorAll('table.tabla').forEach(function (table) {
            const firstRow = table.querySelector('thead tr:first-child') || table.querySelector('tr');
            const columnCount = firstRow
                ? Array.from(firstRow.children).filter(function (cell) {
                    return cell.matches('th, td');
                }).length
                : 0;

            if (columnCount > 0) {
                table.dataset.columnCount = String(columnCount);
            }

            let viewport = table.parentElement;
            if (!viewport || !viewport.classList.contains('table-scroll')) {
                viewport = document.createElement('div');
                viewport.className = 'table-scroll';
                viewport.tabIndex = 0;
                viewport.setAttribute('role', 'region');
                viewport.setAttribute('aria-label', 'Tabla de datos. En pantallas pequeñas puede desplazarse horizontalmente.');
                const gridColumn = window.getComputedStyle(table).gridColumn;
                if (gridColumn && gridColumn !== 'auto') {
                    viewport.style.gridColumn = gridColumn;
                }
                table.parentNode.insertBefore(viewport, table);
                viewport.appendChild(table);
            }
        });
    }

    function init() {
        initNavigation();
        enhanceTables();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
