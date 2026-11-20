<?php
// ============================================================================
// ARCHIVO: vistas/includes/manual_flotante.php
// DESCRIPCIÓN: Botón flotante (libro) con el Manual de Usuario del sistema.
//              Visible para TODOS los roles (sin restricción por rol, a
//              diferencia de bitacora_flotante.php / exportar_importar_
//              flotante.php, que son exclusivos del Administrador).
//
//              Es un simple enlace: al pulsarlo abre el PDF en una pestaña
//              nueva (target="_blank"); no despliega ningún panel ni
//              necesita JavaScript.
//
//              Se ubica en la esquina inferior IZQUIERDA. En escritorio
//              queda por encima del sidebar; en responsive reduce su z-index
//              para que el menú lateral desplegado lo cubra. Tiene suficiente
//              separación del borde inferior para no superponerse al enlace
//              "CERRAR SESIÓN", que el sidebar fija siempre al fondo del
//              menú (ver sidebar.php, <ul style="margin-top:auto">).
//
// USO (dentro de includes/layout.php, para que aparezca en toda la app):
//   include "includes/manual_flotante.php";
// ============================================================================

if (!isset($_SESSION['id_usuario'])) {
    return;
}

$muRuta = '/FUNDACITE/vistas/docs/manual_usuario.pdf';
?>
<link rel="stylesheet" href="/FUNDACITE/vistas/css/manual.css">

<a class="mn-fab" id="mnFab"
   href="<?= htmlspecialchars($muRuta, ENT_QUOTES) ?>"
   target="_blank" rel="noopener"
   aria-label="Abrir el Manual de Usuario en una pestaña nueva"
   title="Manual de Usuario">
    <i class="bi bi-book-half" aria-hidden="true"></i>
</a>

<script>
/**
 * Integra el acceso al manual dentro del contenedor principal.
 * El botón permanece fijo en pantalla, pero toma como referencia el borde
 * izquierdo real de main para quedar en el lado opuesto a las acciones
 * flotantes de la derecha y no encima del sidebar.
 */
(() => {
    const manual = document.getElementById('mnFab');
    if (!manual) return;

    let mainObservado = null;
    let observador = null;

    const obtenerMain = () => document.querySelector(
        'main, .main, #main, .main-content, #main-content, .contenido-principal'
    );

    const ubicarManual = () => {
        const main = obtenerMain();
        if (!main) return;

        // El enlace pasa a ser hijo del área principal, no del layout global.
        if (manual.parentElement !== main) {
            main.appendChild(manual);
        }

        const rect = main.getBoundingClientRect();
        const estilos = window.getComputedStyle(main);
        const paddingIzquierdo = parseFloat(estilos.paddingLeft) || 0;
        const separacion = window.matchMedia('(max-width: 576px)').matches ? 16 : 28;

        // Considera tanto el desplazamiento del main como su padding interno.
        const izquierda = Math.max(0, rect.left) + paddingIzquierdo + separacion;
        manual.style.left = `${Math.round(izquierda)}px`;
        manual.style.right = 'auto';

        // Recalcula también cuando cambia el tamaño del contenedor principal.
        if ('ResizeObserver' in window && mainObservado !== main) {
            if (observador) observador.disconnect();
            observador = new ResizeObserver(ubicarManual);
            observador.observe(main);
            mainObservado = main;
        }
    };

    const iniciar = () => ubicarManual();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }

    window.addEventListener('resize', ubicarManual, { passive: true });
    window.addEventListener('orientationchange', ubicarManual, { passive: true });
})();
</script>
