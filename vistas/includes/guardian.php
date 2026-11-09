<?php
// ============================================================================
// ARCHIVO: vistas/includes/guardian.php
// DESCRIPCIÓN: Exige sesión iniciada. Si no la hay, deja constancia en la
//              bitácora ("Acceso sin sesión": quién/qué URL/IP) y redirige al
//              login. Los endpoints que responden JSON pueden declarar
//              define('GUARDIAN_JSON', true) antes de incluir este archivo
//              para recibir un 401 en JSON en lugar de la redirección.
// ============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controladores/helpers/bitacora_helper.php';

if (!isset($_SESSION["id_usuario"])) {

    registrarAccesoRestringido(false);

    if (defined('GUARDIAN_JSON')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'ok' => false, 'message' => 'Sesión no válida.']);
        exit;
    }

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
