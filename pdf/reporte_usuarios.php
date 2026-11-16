<?php
// ============================================================================
// ARCHIVO: pdf/reporte_usuarios.php
// DESCRIPCIÓN: Reporte PDF del listado de usuarios del sistema. Igual que la
//              pantalla, este reporte es exclusivo del Administrador.
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// La lista de usuarios es exclusiva del rol Administrador; el reporte igual.
requireAdministrador();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_usuario.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$buscar = trim($_GET['buscar'] ?? '');

$usuarioObj = new Usuario($conexion);
$usuarios = $usuarioObj->listar($buscar);

registrarBitacora(
    $conexion,
    'Usuarios',
    'Generar PDF',
    'Generó el reporte PDF del listado de usuarios' . ($buscar !== '' ? " (búsqueda: \"$buscar\")" : '') . '.'
);

$filas = [];

foreach ($usuarios as $u) {
    $filas[] = [
        trim((string) ($u['cedula'] ?? '') . ' - ' . ($u['apellidos'] ?? '') . ' ' . ($u['nombres'] ?? '')),
        (string) ($u['nombre'] ?? ''),
        (string) ($u['tipo_usuario'] ?? ''),
        (string) ($u['status'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE USUARIOS DEL SISTEMA',
    ['Trabajador', 'Usuario', 'Tipo de Usuario', 'Estatus'],
    $filas,
    'Reporte_Usuarios.pdf'
);
