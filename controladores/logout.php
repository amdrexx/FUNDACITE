<?php

session_start();

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/helpers/bitacora_helper.php";

$porInactividad = ($_GET['motivo'] ?? '') === 'inactividad';

registrarBitacora(
    $conexion,
    'Autenticación',
    'Logout',
    $porInactividad ? 'Sesión cerrada por inactividad.' : 'Cierre de sesión.'
);

session_unset();
session_destroy();

if ($porInactividad) {
    // Sesión nueva y limpia, solo para mostrar el motivo en el login.
    session_start();
    session_regenerate_id(true);
    $_SESSION['error_login'] = 'Su sesión se cerró por inactividad. Inicie sesión nuevamente.';
}

header("Location: /FUNDACITE/index.php");
exit;