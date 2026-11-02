<?php
// ============================================================================
// ARCHIVO: ajax/ajax_keepalive.php
// DESCRIPCIÓN: "Latido" que envía inactividad.js mientras el usuario está
//              activo (clics, teclado, scroll…) o pulsa "Seguir conectado".
//              Renueva la actividad de la sesión. Si la sesión ya expiró o no
//              existe responde 401 y el navegador vuelve al login.
// ============================================================================
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../vistas/includes/sesion.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['id_usuario']) || sesionInactiva()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'expirada' => true]);
    exit;
}

sesionRenovar();
session_write_close();

echo json_encode(['ok' => true, 'limite' => SESION_INACTIVIDAD_SEG]);
