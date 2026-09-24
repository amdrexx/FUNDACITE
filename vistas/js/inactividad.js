// ============================================================================
// ARCHIVO: vistas/js/inactividad.js
// DESCRIPCIÓN: Cierra la sesión tras 1 minuto sin actividad.
//
//   · Actividad = clic, tecla, scroll, toque o mover el mouse.
//   · A los 45 s sin actividad aparece un aviso con cuenta regresiva; al
//     llegar a 60 s se cierra la sesión (controladores/logout.php).
//   · Mientras hay actividad envía un "latido" al servidor (máx. 1 cada 10 s)
//     para que la sesión de PHP no expire mientras se llena un formulario largo.
//   · Se sincroniza entre pestañas con localStorage: actividad en una pestaña
//     cuenta para todas.
//   · Se calcula con la hora real (Date.now), no acumulando temporizadores,
//     porque el navegador frena los timers de pestañas en segundo plano.
// ============================================================================
(function () {
    'use strict';

    const script = document.currentScript;
    const LIMITE_MS = (parseInt(script && script.dataset.limite, 10) || 60) * 1000;
    const AVISO_MS = Math.min(15000, LIMITE_MS / 2);   // duración del aviso
    const LATIDO_MS = Math.min(10000, LIMITE_MS / 6);  // máx. 1 latido por ventana
    const MOVER_MS = 1000;                             // filtro del mousemove
    const CLAVE = 'fundacite:actividad';
    const URL_LATIDO = '/FUNDACITE/ajax/ajax_keepalive.php';
    const URL_SALIR = '/FUNDACITE/controladores/logout.php?motivo=inactividad';

    let ultima = Date.now();
    let ultimoLatido = ultima;
    let latidoEnCurso = false;
    let expirada = false;
    let avisoVisible = false;
    let foco = null;

    // ------------------------------------------------------------------ aviso
    const overlay = document.createElement('div');
    overlay.className = 'ses-overlay';
    overlay.innerHTML =
        '<div class="ses-dialog" role="alertdialog" aria-modal="true"' +
        ' aria-labelledby="ses-titulo" aria-describedby="ses-texto">' +
        '<h2 id="ses-titulo">¿Sigues ahí?</h2>' +
        '<p id="ses-texto">Por seguridad, tu sesión se cerrará en ' +
        '<span class="ses-count" aria-hidden="true"></span> segundos por inactividad.</p>' +
        '<div class="ses-track" aria-hidden="true"><div class="ses-bar"></div></div>' +
        '<div class="ses-actions">' +
        '<button type="button" class="ses-btn ses-btn-secondary" data-ses="salir">Cerrar sesión</button>' +
        '<button type="button" class="ses-btn ses-btn-primary" data-ses="seguir">Seguir conectado</button>' +
        '</div>' +
        '<span class="ses-sr" role="status" aria-live="polite"></span>' +
        '</div>';
    document.body.appendChild(overlay);

    const cuenta = overlay.querySelector('.ses-count');
    const barra = overlay.querySelector('.ses-bar');
    const estado = overlay.querySelector('.ses-sr');
    const btnSeguir = overlay.querySelector('[data-ses="seguir"]');
    const btnSalir = overlay.querySelector('[data-ses="salir"]');

    function mostrarAviso(restanteMs) {
        avisoVisible = true;
        foco = document.activeElement;
        overlay.classList.add('ses-visible');

        // Barra: parte de lo que queda y se vacía linealmente hasta 0
        barra.style.transitionDuration = '0ms';
        barra.style.transform = 'scaleX(' + Math.max(restanteMs / AVISO_MS, 0) + ')';
        void barra.offsetWidth; // reflow: fija el punto de partida
        barra.style.transitionDuration = restanteMs + 'ms';
        barra.style.transform = 'scaleX(0)';

        btnSeguir.focus({ preventScroll: true });
    }

    function ocultarAviso() {
        if (!avisoVisible) return;
        avisoVisible = false;
        overlay.classList.remove('ses-visible');
        if (foco && typeof foco.focus === 'function') foco.focus({ preventScroll: true });
        foco = null;
    }

    // ---------------------------------------------------------------- latido
    function latido() {
        if (latidoEnCurso || expirada) return Promise.resolve();
        latidoEnCurso = true;
        ultimoLatido = Date.now();

        return fetch(URL_LATIDO, { cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) { if (r.status === 401) expirar(); })
            .catch(function () { /* sin red: el servidor decidirá en la próxima petición */ })
            .then(function () { latidoEnCurso = false; });
    }

    // ------------------------------------------------------------- actividad
    function registrarActividad(ahora) {
        ultima = ahora;
        try { localStorage.setItem(CLAVE, String(ahora)); } catch (e) { /* modo privado */ }
        if (ahora - ultimoLatido >= LATIDO_MS) latido();
    }

    function alActividad(e) {
        if (expirada || avisoVisible) return; // con el aviso abierto solo cuentan sus botones
        const ahora = Date.now();
        if (e.type === 'mousemove' && ahora - ultima < MOVER_MS) return;
        registrarActividad(ahora);
    }

    ['mousedown', 'mousemove', 'keydown', 'wheel', 'touchstart', 'scroll'].forEach(function (tipo) {
        window.addEventListener(tipo, alActividad, { passive: true, capture: true });
    });

    // Actividad en OTRA pestaña: también cuenta aquí (y cierra el aviso).
    window.addEventListener('storage', function (e) {
        if (e.key !== CLAVE || !e.newValue) return;
        const t = parseInt(e.newValue, 10);
        if (t > ultima) {
            ultima = t;
            ocultarAviso();
        }
    });

    // --------------------------------------------------------------- botones
    btnSeguir.addEventListener('click', function () {
        registrarActividad(Date.now());
        latido().then(function () { if (!expirada) ocultarAviso(); });
    });

    btnSalir.addEventListener('click', function () {
        window.location.replace('/FUNDACITE/controladores/logout.php');
    });

    // Foco atrapado dentro del aviso (Tab / Shift+Tab)
    overlay.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') return;
        const primero = btnSalir, ultimoBtn = btnSeguir;
        if (e.shiftKey && document.activeElement === primero) {
            e.preventDefault(); ultimoBtn.focus();
        } else if (!e.shiftKey && document.activeElement === ultimoBtn) {
            e.preventDefault(); primero.focus();
        }
    });

    // ------------------------------------------------------------- expiración
    function expirar() {
        if (expirada) return;
        expirada = true;
        window.location.replace(URL_SALIR);
    }

    function revisar() {
        if (expirada) return;

        // Puede haber actividad en otra pestaña que aún no llegó por 'storage'
        try {
            const guardada = parseInt(localStorage.getItem(CLAVE), 10);
            if (guardada > ultima) { ultima = guardada; ocultarAviso(); }
        } catch (e) { /* ignorar */ }

        const inactivo = Date.now() - ultima;

        if (inactivo >= LIMITE_MS) {
            expirar();
            return;
        }

        const restante = LIMITE_MS - inactivo;

        if (restante <= AVISO_MS) {
            if (!avisoVisible) mostrarAviso(restante);
            const seg = Math.ceil(restante / 1000);
            cuenta.textContent = seg;
            // Lector de pantalla: avisos espaciados, no uno por segundo
            if (seg % 5 === 0) estado.textContent = 'La sesión se cerrará en ' + seg + ' segundos.';
        }
    }

    setInterval(revisar, 1000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) revisar(); // al volver a la pestaña, revisar de inmediato
    });

    registrarActividad(Date.now());
})();
