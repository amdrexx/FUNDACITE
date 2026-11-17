<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SESION_INACTIVIDAD_SEG')) {
    define('SESION_INACTIVIDAD_SEG', 300); // cinco minutos sin actividad
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
