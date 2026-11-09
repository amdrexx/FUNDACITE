/*
 * Sistema único de alertas FUNDACITE.
 * Todos los avisos, validaciones, confirmaciones y solicitudes de texto
 * utilizan el mismo modal personalizado del proyecto.
 */
(function (window, document) {
    'use strict';

    const iconos = { success: '✓', error: '×', warning: '!', info: 'i' };
    const titulos = {
        success: 'Operación exitosa',
        error: 'No se pudo completar',
        warning: 'Atención',
        info: 'Información'
    };

    let resolverActual = null;
    let modoActual = 'alert';
    let elementoEnfocado = null;

    function elementos() {
        return {
            modal: document.getElementById('customAlert'),
            titulo: document.getElementById('sysAlertTitle'),
            icono: document.getElementById('sysAlertIcon'),
            mensaje: document.getElementById('alertMessage'),
            aceptar: document.getElementById('sysAlertPrimary'),
            cancelar: document.getElementById('sysAlertCancel'),
            inputWrap: document.getElementById('sysAlertInputWrap'),
            input: document.getElementById('sysAlertInput')
        };
    }

    function abrir(mensaje, tipo, modo, opciones) {
        const el = elementos();
        if (!el.modal || !el.mensaje || !el.aceptar) return Promise.resolve(modo === 'alert' ? undefined : null);

        if (resolverActual) cerrar(false);
        elementoEnfocado = document.activeElement;
        modoActual = modo;
        const color = iconos[tipo] ? tipo : 'info';
        el.modal.className = 'custom-alert sys-alert sys-alert--' + color;
        el.modal.setAttribute('aria-hidden', 'false');
        el.titulo.textContent = opciones.titulo || titulos[color];
        el.icono.textContent = iconos[color];
        el.mensaje.textContent = String(mensaje ?? '');
        el.aceptar.textContent = opciones.aceptar || (modo === 'alert' ? 'Aceptar' : 'Confirmar');
        el.cancelar.textContent = opciones.cancelar || 'Cancelar';
        el.cancelar.classList.toggle('hidden', modo === 'alert');
        el.inputWrap.classList.toggle('hidden', modo !== 'prompt');
        if (modo === 'prompt') {
            el.input.value = opciones.valorInicial || '';
            el.input.placeholder = opciones.placeholder || 'Escriba aquí...';
            el.input.required = Boolean(opciones.obligatorio);
        }
        el.modal.classList.remove('hidden');
        document.body.classList.add('sys-alert-open');

        return new Promise(resolve => {
            resolverActual = resolve;
            window.setTimeout(() => {
                if (modo === 'prompt') el.input.focus();
                else el.aceptar.focus();
            }, 0);
        });
    }

    function cerrar(resultado) {
        const el = elementos();
        if (el.modal) {
            el.modal.classList.add('hidden');
            el.modal.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('sys-alert-open');
        const resolver = resolverActual;
        resolverActual = null;
        if (resolver) resolver(resultado);
        if (elementoEnfocado && typeof elementoEnfocado.focus === 'function') {
            try { elementoEnfocado.focus({ preventScroll: true }); } catch (_) { elementoEnfocado.focus(); }
        }
        elementoEnfocado = null;
    }

    const api = {
        mostrar(mensaje, tipo = 'info', opciones = {}) {
            abrir(mensaje, tipo, 'alert', opciones);
        },
        confirmar(mensaje, opciones = {}) {
            return abrir(mensaje, opciones.tipo || 'warning', 'confirm', opciones);
        },
        solicitarTexto(mensaje, opciones = {}) {
            return abrir(mensaje, opciones.tipo || 'warning', 'prompt', opciones).then(valor => valor);
        },
        cerrar(resultado = false) {
            const el = elementos();
            if (modoActual === 'prompt' && resultado === true) {
                const valor = el.input ? el.input.value.trim() : '';
                if (el.input && el.input.required && !valor) {
                    if (el.mensaje) el.mensaje.textContent = 'La justificación es obligatoria. Escriba el motivo antes de continuar.';
                    el.input.focus();
                    return;
                }
                cerrar(valor);
            } else {
                cerrar(modoActual === 'alert' ? undefined : (modoActual === 'prompt' ? null : Boolean(resultado)));
            }
        }
    };

    window.FundaciteAlerta = api;
    window.mostrarAlerta = (mensaje, tipo = 'info') => api.mostrar(mensaje, tipo);
    window.cerrarAlerta = () => api.cerrar(false);
    window.closeAlert = () => api.cerrar(false);
    window.confirmarAccion = (mensaje, opciones) => api.confirmar(mensaje, opciones);
    window.solicitarTextoAlerta = (mensaje, opciones) => api.solicitarTexto(mensaje, opciones);

    // Compatibilidad: las llamadas antiguas a alert() ahora abren la alerta del sistema.
    window.alert = function (mensaje) { api.mostrar(mensaje, 'warning'); };

    document.addEventListener('click', function (event) {
        const el = elementos();
        if (event.target === el.aceptar) {
            event.preventDefault();
            api.cerrar(true);
        } else if (event.target === el.cancelar) {
            event.preventDefault();
            api.cerrar(false);
        } else if (event.target === el.modal && modoActual !== 'alert') {
            api.cerrar(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        const el = elementos();
        if (!el.modal || el.modal.classList.contains('hidden')) return;
        if (event.key === 'Escape' && modoActual !== 'alert') {
            event.preventDefault();
            api.cerrar(false);
        } else if (event.key === 'Enter' && modoActual === 'prompt' && event.target === el.input && !event.shiftKey) {
            event.preventDefault();
            api.cerrar(true);
        }
    });

    // Confirmación estándar para formularios y enlaces marcados con data-confirm.
    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.confirmApproved === '1') {
            delete form.dataset.confirmApproved;
            return;
        }
        if (event.defaultPrevented) return;
        event.preventDefault();
        const aceptado = await api.confirmar(form.getAttribute('data-confirm') || '¿Desea continuar?');
        if (aceptado) {
            form.dataset.confirmApproved = '1';
            if (typeof form.requestSubmit === 'function') form.requestSubmit(event.submitter || undefined);
            else form.submit();
        }
    });

    document.addEventListener('click', async function (event) {
        const disparador = event.target.closest ? event.target.closest('[data-confirm][data-confirm-href], a[data-confirm]') : null;
        if (!disparador || event.defaultPrevented) return;
        event.preventDefault();
        const aceptado = await api.confirmar(disparador.getAttribute('data-confirm') || '¿Desea continuar?');
        if (aceptado) {
            const destino = disparador.getAttribute('data-confirm-href') || disparador.href;
            if (destino) window.location.href = destino;
        }
    });
})(window, document);
