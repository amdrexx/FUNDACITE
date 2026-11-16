/* FUNDACITE responsive behaviour: mobile navigation and accessible table labels. */
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
            sidebar.setAttribute('aria-hidden', mobileQuery.matches && !shouldOpen ? 'true' : 'false');
        }

        toggle.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('active'));
        });

        backdrop.addEventListener('click', function () {
            setOpen(false);
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
            if (!mobileQuery.matches) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                toggle.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
                sidebar.setAttribute('aria-hidden', 'false');
            } else {
                setOpen(false);
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
            const headCells = Array.from(table.querySelectorAll('thead tr:first-child th'));
            if (!headCells.length) return;

            const labels = headCells.map(function (cell) {
                return cell.textContent.replace(/\s+/g, ' ').trim();
            });
            const columnCount = labels.length;
            const isCompact = columnCount > 0 && columnCount <= 6;

            if (isCompact) {
                table.dataset.responsiveCards = 'true';
            } else {
                table.dataset.wide = 'true';
            }

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                const cells = Array.from(row.children).filter(function (cell) {
                    return cell.matches('td, th');
                });
                cells.forEach(function (cell, index) {
                    if (!cell.matches('td')) return;
                    if (cell.hasAttribute('colspan')) return;
                    const label = labels[index] || '';
                    if (label && !cell.hasAttribute('data-label')) {
                        cell.setAttribute('data-label', label);
                    }
                });
            });

            let viewport = table.parentElement;
            if (!viewport || !viewport.classList.contains('table-scroll')) {
                viewport = document.createElement('div');
                viewport.className = 'table-scroll';
                viewport.tabIndex = 0;
                viewport.setAttribute('role', 'region');
                viewport.setAttribute('aria-label', 'Tabla de datos. En pantallas pequeñas puede desplazarse horizontalmente.');
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
