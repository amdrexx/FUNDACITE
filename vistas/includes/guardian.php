<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["id_usuario"])) {

    header("Location: /FUNDACITE/index.php");
    exit;
}

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/sesion.php';

// Sesión inactiva: se cierra en el servidor (queda en bitácora y se muestra
// el motivo en la pantalla de acceso).
if (sesionInactiva()) {
    header('Location: /FUNDACITE/controladores/logout.php?motivo=inactividad');
    exit;
}

// Cada petición válida cuenta como actividad.
sesionRenovar();