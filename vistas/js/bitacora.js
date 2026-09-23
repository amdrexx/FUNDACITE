// ============================================================================
// ARCHIVO: vistas/js/bitacora.js
// DESCRIPCIÓN: Comportamiento de la campana flotante de la bitácora
//              (solo se carga para el Administrador).
//
//   · Sondea ajax/ajax_bitacora.php cada 20 s (pausado si la pestaña no está
//     visible) para saber cuántos movimientos "sin leer" hay.
//   · Cuando llega uno nuevo: repica la campana y muestra un aviso emergente.
//   · Al abrir el panel se marca todo como leído y se listan los registros
//     con búsqueda, filtros rápidos y paginación.
//
// Lo "leído" se recuerda por administrador en localStorage (clave con su id),
// así el contador sobrevive a recargas sin tocar la base de datos.
// ============================================================================

(function () {
    'use strict';

    const root = document.getElementById('btRoot');
    if (!root) return;

    const ENDPOINT = root.dataset.endpoint;
    const CLAVE_VISTO = 'fundacite:bitacora:visto:' + (root.dataset.usuario || '0');

    const SONDEO_MS = 20000;      // frecuencia normal de consulta
    const SONDEO_MAX_MS = 120000; // tope del retroceso si el servidor falla
    const AVISO_MS = 7000;        // cuánto dura el aviso emergente
    const BUSQUEDA_MS = 300;      // espera al escribir antes de buscar

    // Ícono y color de cada tipo de movimiento (los colores viven en el CSS)
    const ACCIONES = {
        'Crear':         { icono: 'bi-plus-circle-fill',      tono: 'green' },
        'Editar':        { icono: 'bi-pencil-fill',           tono: 'blue'  },
        'Eliminar':      { icono: 'bi-trash3-fill',           tono: 'red'   },
        'Login fallido': { icono: 'bi-shield-exclamation',    tono: 'amber' },
        'Login':         { icono: 'bi-box-arrow-in-right',    tono: 'slate' },
        'Logout':        { icono: 'bi-box-arrow-left',        tono: 'gray'  },
        'Listar':        { icono: 'bi-list-ul',               tono: 'gray'  },
        'Consultar':     { icono: 'bi-eye-fill',              tono: 'gray'  },
        'Generar PDF':   { icono: 'bi-file-earmark-pdf-fill', tono: 'slate' }
    };
    const ACCION_DEFECTO = { icono: 'bi-journal-text', tono: 'gray' };

    const reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

    // --- Referencias al DOM ---------------------------------------------------
    const $ = (id) => document.getElementById(id);

    const fab = $('btFab');
    const badge = $('btBadge');
    const panel = $('btPanel');
    const cerrarBtn = $('btClose');
    const buscar = $('btBuscar');
    const chips = $('btChips');
    const scroll = $('btScroll');
    const pildora = $('btNuevos');
    const lista = $('btLista');
    const estado = $('btEstado');
    const mas = $('btMas');
    const aviso = $('btToast');
    const avisoPrincipal = $('btToastMain');
    const avisoCerrar = $('btToastX');
    const avisoIco = $('btToastIco');
    const avisoUsuario = $('btToastUser');
    const avisoMas = $('btToastMore');
    const avisoMeta = $('btToastMeta');
    const avisoTexto = $('btToastText');

    // --- Estado ---------------------------------------------------------------
    const state = {
        abierto: false,
        visto: leerVisto(),     // último id que el administrador ya vio
        desde: null,            // último id que este navegador ya recibió
        maxId: 0,               // mayor id conocido en el servidor
        sinLeer: 0,
        resaltarDesde: 0,       // en el panel, marca como "nuevo" lo posterior a este id
        grupo: '',
        q: '',
        cursor: 0,              // id del último registro cargado (paginación)
        hayMas: false,
        cargado: false,         // ¿ya se cargó la lista alguna vez?
        seqLista: 0,            // descarta respuestas de listas que llegan tarde
        sondeando: false,
        retardo: SONDEO_MS,
        timerSondeo: null,
        timerAviso: null,
        timerBusqueda: null,
        muerto: false
    };

    // --- Utilidades -----------------------------------------------------------
    function el(etiqueta, clase, texto) {
        const nodo = document.createElement(etiqueta);
        if (clase) nodo.className = clase;
        if (texto !== undefined) nodo.textContent = texto;
        return nodo;
    }

    function leerVisto() {
        try {
            const v = localStorage.getItem(CLAVE_VISTO);
            const n = v === null ? NaN : parseInt(v, 10);
            return Number.isNaN(n) ? null : n;
        } catch (e) {
            return null;
        }
    }

    function guardarVisto() {
        try {
            localStorage.setItem(CLAVE_VISTO, String(state.visto));
        } catch (e) { /* modo privado o almacenamiento lleno: se sigue en memoria */ }
    }

    async function pedir(params) {
        const url = ENDPOINT + '?' + new URLSearchParams(params).toString();
        const res = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json' }
        });

        // Sesión vencida o el usuario ya no es administrador: se apaga todo
        if (res.status === 401 || res.status === 403) {
            const err = new Error('sin acceso');
            err.fatal = true;
            throw err;
        }
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const datos = await res.json();
        if (!datos || datos.ok !== true) throw new Error('respuesta inválida');
        return datos;
    }

    function relativo(ms) {
        const s = Math.floor(ms / 1000);
        if (s < 45) return 'ahora';

        const m = Math.round(s / 60);
        if (m < 60) return 'hace ' + m + ' min';

        const h = Math.round(m / 60);
        if (h < 24) return 'hace ' + h + ' h';

        const d = Math.round(h / 24);
        if (d < 7) return 'hace ' + d + (d === 1 ? ' día' : ' días');

        return null; // más viejo: se muestra la fecha
    }

    function pintarTiempo(nodo) {
        const texto = relativo(Date.now() - Number(nodo.dataset.ts));
        nodo.textContent = texto !== null ? texto : nodo.dataset.fecha.slice(0, 10);
    }

    function refrescarTiempos() {
        lista.querySelectorAll('.bt-time').forEach(pintarTiempo);
    }

    // --- Contador y campana -----------------------------------------------------
    function pintarBadge(n) {
        state.sinLeer = n;
        badge.textContent = n > 99 ? '99+' : String(n);
        badge.classList.toggle('is-on', n > 0);
        fab.classList.toggle('has-unread', n > 0);
        fab.setAttribute(
            'aria-label',
            n > 0
                ? 'Abrir bitácora, ' + n + (n === 1 ? ' movimiento sin leer' : ' movimientos sin leer')
                : 'Abrir bitácora'
        );
    }

    function repicar() {
        if (reducirMovimiento.matches) return;
        // Se reinicia la animación aunque la anterior no haya terminado
        fab.classList.remove('is-ringing');
        void fab.offsetWidth;
        fab.classList.add('is-ringing');
    }

    fab.addEventListener('animationend', () => fab.classList.remove('is-ringing'));
    fab.addEventListener('animationcancel', () => fab.classList.remove('is-ringing'));

    function marcarLeido() {
        if (state.maxId > (state.visto === null ? -1 : state.visto)) {
            state.visto = state.maxId;
            guardarVisto();
        }
        pintarBadge(0);
    }

    // --- Aviso emergente ---------------------------------------------------------
    function programarCierreAviso(ms) {
        clearTimeout(state.timerAviso);
        state.timerAviso = setTimeout(ocultarAviso, ms);
    }

    function mostrarAviso(datos) {
        const it = datos.nuevos[0]; // el más reciente
        if (!it) return;

        const meta = ACCIONES[it.accion] || ACCION_DEFECTO;
        const extra = datos.nuevos_total - 1;

        aviso.className = 'bt-toast tone-' + meta.tono + ' is-on';
        avisoIco.replaceChildren(el('i', 'bi ' + meta.icono));
        avisoUsuario.textContent = it.usuario;
        avisoMas.textContent = extra > 0 ? '+' + extra + ' más' : '';
        avisoMeta.textContent = it.accion + ' · ' + it.modulo;
        avisoTexto.textContent = it.descripcion || 'Sin detalle.';

        programarCierreAviso(AVISO_MS);
    }

    function ocultarAviso() {
        clearTimeout(state.timerAviso);
        aviso.classList.remove('is-on');
    }

    // Mientras se lee (mouse encima o foco dentro) el aviso no desaparece
    aviso.addEventListener('mouseenter', () => clearTimeout(state.timerAviso));
    aviso.addEventListener('mouseleave', () => {
        if (aviso.classList.contains('is-on')) programarCierreAviso(2500);
    });
    aviso.addEventListener('focusin', () => clearTimeout(state.timerAviso));
    aviso.addEventListener('focusout', () => {
        if (aviso.classList.contains('is-on')) programarCierreAviso(2500);
    });

    avisoPrincipal.addEventListener('click', abrir);
    avisoCerrar.addEventListener('click', ocultarAviso);

    // --- Lista de registros --------------------------------------------------------
    function crearTiempo(it) {
        const t = el('time', 'bt-time');
        t.dataset.ts = String(Date.now() - it.hace_seg * 1000);
        t.dataset.fecha = it.fecha;
        t.title = it.fecha;
        pintarTiempo(t);
        return t;
    }

    function crearItem(it) {
        const meta = ACCIONES[it.accion] || ACCION_DEFECTO;
        // "Nuevo" = movimiento alertable, de otro usuario, posterior a lo último visto
        const nuevo = it.alerta && !it.propio && it.id > state.resaltarDesde;

        const li = el('li', 'bt-item tone-' + meta.tono + (nuevo ? ' is-nuevo' : ''));

        const ico = el('span', 'bt-ico');
        ico.setAttribute('aria-hidden', 'true');
        ico.appendChild(el('i', 'bi ' + meta.icono));

        const cuerpo = el('div', 'bt-item-body');

        const arriba = el('div', 'bt-item-top');
        arriba.appendChild(el('strong', 'bt-user', it.usuario));
        arriba.appendChild(crearTiempo(it));

        const etiquetas = el('div', 'bt-item-meta');
        etiquetas.appendChild(el('span', 'bt-tag', it.accion));
        etiquetas.appendChild(el('span', 'bt-mod', it.modulo));

        const texto = el('p', 'bt-item-text', it.descripcion || 'Sin detalle.');
        texto.title = it.descripcion;

        cuerpo.append(arriba, etiquetas, texto);
        li.append(ico, cuerpo);
        return li;
    }

    function pintarLista(items, anexar) {
        if (!anexar) lista.replaceChildren();

        const fragmento = document.createDocumentFragment();
        items.forEach((it) => fragmento.appendChild(crearItem(it)));
        lista.appendChild(fragmento);
    }

    function mostrarSkeleton() {
        lista.replaceChildren();
        estado.hidden = true;
        mas.hidden = true;

        for (let i = 0; i < 5; i++) {
            const fila = el('li', 'bt-sk');
            fila.setAttribute('aria-hidden', 'true');
            fila.appendChild(el('span', 'bt-sk-c'));

            const lineas = el('div', 'bt-sk-l');
            lineas.append(el('span'), el('span'));
            fila.appendChild(lineas);

            lista.appendChild(fila);
        }
    }

    function mostrarEstado(icono, titulo, texto, conReintento) {
        estado.replaceChildren(el('i', 'bi ' + icono), el('strong', '', titulo), el('span', '', texto));

        if (conReintento) {
            const boton = el('button', 'bt-reintentar', 'Reintentar');
            boton.type = 'button';
            boton.addEventListener('click', () => cargarLista({ reset: true }));
            estado.appendChild(boton);
        }
        estado.hidden = false;
    }

    async function cargarLista(opciones) {
        const reset = !opciones || opciones.reset !== false;
        const silencioso = !!(opciones && opciones.silencioso);
        const seq = ++state.seqLista;

        if (reset) {
            state.cursor = 0;
            if (!silencioso) mostrarSkeleton();
        } else {
            mas.disabled = true;
            mas.textContent = 'Cargando…';
        }

        try {
            const datos = await pedir({
                accion: 'listar',
                grupo: state.grupo,
                q: state.q,
                antes_id: reset ? 0 : state.cursor
            });

            // Llegó tarde: mientras tanto se pidió otra cosa (otro filtro, otra búsqueda)
            if (seq !== state.seqLista) return;

            state.cargado = true;
            estado.hidden = true;

            if (datos.max_id > state.maxId) state.maxId = datos.max_id;
            if (state.abierto) marcarLeido();

            if (reset && datos.items.length === 0) {
                lista.replaceChildren();
                state.hayMas = false;
                mas.hidden = true;

                if (state.q || state.grupo) {
                    mostrarEstado('bi-search', 'Sin resultados', 'Prueba con otro filtro o cambia lo que buscas.');
                } else {
                    mostrarEstado('bi-inbox', 'Todavía no hay movimientos', 'Cuando alguien use el sistema, aparecerá aquí.');
                }
                return;
            }

            pintarLista(datos.items, !reset);
            if (datos.items.length) state.cursor = datos.items[datos.items.length - 1].id;

            state.hayMas = datos.hay_mas;
            mas.hidden = !state.hayMas;
            mas.disabled = false;
            mas.textContent = 'Cargar más';

            if (reset) {
                ocultarPildora();
                if (!silencioso) scroll.scrollTop = 0;
            }
        } catch (err) {
            if (seq !== state.seqLista) return;
            if (err.fatal) { apagar(); return; }

            if (!reset) {
                mas.disabled = false;
                mas.hidden = false;
                mas.textContent = 'No se pudo cargar. Reintentar';
            } else if (!silencioso) {
                lista.replaceChildren();
                mas.hidden = true;
                mostrarEstado('bi-wifi-off', 'No se pudo cargar la bitácora', 'Revisa tu conexión e inténtalo de nuevo.', true);
            }
            // Recarga silenciosa fallida: se conserva lo que ya estaba a la vista
        }
    }

    // --- Píldora "Movimientos nuevos" (llegan datos mientras se lee más abajo) ------
    function mostrarPildora() {
        pildora.classList.add('is-on');
        pildora.tabIndex = 0;
    }

    function ocultarPildora() {
        pildora.classList.remove('is-on');
        pildora.tabIndex = -1;
    }

    pildora.addEventListener('click', () => {
        ocultarPildora();
        scroll.scrollTop = 0;
        panel.focus({ preventScroll: true }); // el botón se oculta: el foco no debe perderse
        cargarLista({ reset: true, silencioso: true });
    });

    // --- Panel ----------------------------------------------------------------------
    function abrir() {
        if (state.abierto) return;
        state.abierto = true;

        ocultarAviso();
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        fab.setAttribute('aria-expanded', 'true');

        // Lo posterior a lo último visto se resalta; luego todo cuenta como leído
        state.resaltarDesde = state.visto === null ? state.maxId : state.visto;
        marcarLeido();

        // Si ya había datos se muestran de inmediato y se refrescan por detrás
        cargarLista({ reset: true, silencioso: state.cargado });

        panel.focus({ preventScroll: true });
    }

    // devolverFoco: al cerrar con teclado (Esc / botón X) el foco regresa a la
    // campana; al cerrar haciendo clic fuera se respeta hacia dónde fue el usuario.
    function cerrar(devolverFoco) {
        if (!state.abierto) return;
        state.abierto = false;

        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        fab.setAttribute('aria-expanded', 'false');
        ocultarPildora();

        if (devolverFoco) fab.focus({ preventScroll: true });
    }

    fab.addEventListener('click', () => (state.abierto ? cerrar(false) : abrir()));
    cerrarBtn.addEventListener('click', () => cerrar(true));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && state.abierto) cerrar(true);
    });

    document.addEventListener('pointerdown', (e) => {
        if (state.abierto && !root.contains(e.target)) cerrar(false);
    });

    // --- Búsqueda, filtros y "cargar más" ----------------------------------------------
    buscar.addEventListener('input', () => {
        clearTimeout(state.timerBusqueda);
        state.timerBusqueda = setTimeout(() => {
            const q = buscar.value.trim();
            if (q === state.q) return;
            state.q = q;
            cargarLista({ reset: true });
        }, BUSQUEDA_MS);
    });

    chips.addEventListener('click', (e) => {
        const chip = e.target.closest('.bt-chip');
        if (!chip || chip.getAttribute('aria-pressed') === 'true') return;

        chips.querySelectorAll('.bt-chip').forEach((c) => {
            c.setAttribute('aria-pressed', c === chip ? 'true' : 'false');
        });

        state.grupo = chip.dataset.grupo || '';
        cargarLista({ reset: true });
    });

    mas.addEventListener('click', () => cargarLista({ reset: false }));

    // --- Sondeo -------------------------------------------------------------------------
    function programarSondeo() {
        clearTimeout(state.timerSondeo);
        // Con la pestaña oculta no se consulta: se retoma al volver
        if (state.muerto || document.hidden) return;
        state.timerSondeo = setTimeout(sondear, state.retardo);
    }

    async function sondear() {
        if (state.muerto || state.sondeando) return;
        state.sondeando = true;

        try {
            const params = { accion: 'poll' };
            if (state.visto !== null) params.visto_id = state.visto;
            if (state.desde !== null) params.desde_id = state.desde;

            const datos = await pedir(params);

            const primera = state.desde === null;
            const llegoAlgo = !primera && datos.max_id > state.desde;

            // Primera vez (sin referencia) o tabla reiniciada: se arranca "al día"
            if (state.visto === null || datos.max_id < state.visto) {
                state.visto = datos.max_id;
                guardarVisto();
            }
            state.desde = datos.max_id;
            state.maxId = datos.max_id;

            if (state.abierto) {
                // Está mirando la bitácora: lo que llegue ya cuenta como visto
                marcarLeido();
                if (llegoAlgo) {
                    if (scroll.scrollTop < 40) cargarLista({ reset: true, silencioso: true });
                    else mostrarPildora();
                }
            } else {
                pintarBadge(datos.sin_leer);
                if (!primera && datos.nuevos_total > 0) {
                    repicar();
                    mostrarAviso(datos);
                }
            }

            state.retardo = SONDEO_MS;
        } catch (err) {
            if (err.fatal) { apagar(); return; }
            // Retroceso exponencial: no martillar un servidor que está fallando
            state.retardo = Math.min(state.retardo * 2, SONDEO_MAX_MS);
        } finally {
            state.sondeando = false;
            programarSondeo();
        }
    }

    document.addEventListener('visibilitychange', () => {
        clearTimeout(state.timerSondeo);
        if (!document.hidden) sondear();
    });

    // Si el administrador lee la bitácora en otra pestaña, esta se pone al día
    window.addEventListener('storage', (e) => {
        if (e.key !== CLAVE_VISTO || e.newValue === null) return;
        const n = parseInt(e.newValue, 10);
        if (!Number.isNaN(n) && (state.visto === null || n > state.visto)) {
            state.visto = n;
            sondear();
        }
    });

    // Las etiquetas "hace 5 min" se mantienen frescas mientras el panel está abierto
    setInterval(() => {
        if (state.abierto && !document.hidden) refrescarTiempos();
    }, 30000);

    // Sesión vencida o rol distinto: se retira la campana por completo
    function apagar() {
        state.muerto = true;
        clearTimeout(state.timerSondeo);
        clearTimeout(state.timerAviso);
        root.remove();
    }

    sondear();
})();
