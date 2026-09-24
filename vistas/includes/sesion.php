<?php
// ============================================================================
// ARCHIVO: vistas/includes/sesion.php
// DESCRIPCIÓN: Control de inactividad de la sesión.
//
//   · El navegador (vistas/js/inactividad.js) avisa a los 45 s y cierra la
//     sesión a los 60 s sin actividad.
//   · El servidor es la autoridad: aunque el JS se desactive o se manipule,
//     una sesión sin peticiones durante LÍMITE + GRACIA segundos se rechaza.
//     La gracia (10 s) absorbe el desfase del "latido" que envía el navegador
//     cada 10 s, para que SIEMPRE dispare primero el aviso del navegador.
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SESION_INACTIVIDAD_SEG')) {
    define('SESION_INACTIVIDAD_SEG', 60); // tiempo máximo sin actividad
}
if (!defined('SESION_GRACIA_SEG')) {
    define('SESION_GRACIA_SEG', 10);      // tolerancia del servidor
}

/** ¿Pasó más del tiempo permitido desde la última actividad registrada? */
function sesionInactiva(): bool
{
    if (!isset($_SESSION['ultima_actividad'])) {
        return false; // primera petición de la sesión: se marcará al renovar
    }

    return (time() - (int) $_SESSION['ultima_actividad'])
        > (SESION_INACTIVIDAD_SEG + SESION_GRACIA_SEG);
}

/** Marca "ahora" como la última actividad del usuario. */
function sesionRenovar(): void
{
    $_SESSION['ultima_actividad'] = time();
}
