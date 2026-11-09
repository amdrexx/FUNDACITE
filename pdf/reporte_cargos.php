<?php
// ============================================================================
// ARCHIVO: pdf/reporte_cargos.php
// DESCRIPCIÓN: Reporte PDF del listado de cargos (solo Administrador y
//              Director).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_cargo.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$modeloCargo = new clase_cargo($conexion);
$cargos = $modeloCargo->listarCargos();

registrarBitacora(
    $conexion,
    'Cargos',
    'Generar PDF',
    'Generó el reporte PDF del listado de cargos.'
);

$filas = [];

foreach ($cargos as $fila) {
    $filas[] = [
        (string) ($fila['nombre_cargo'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE CARGOS',
    ['Nombre del Cargo'],
    $filas,
    'Reporte_Cargos.pdf'
);
