<?php
// ============================================================================
// ARCHIVO: pdf/reporte_primas.php
// DESCRIPCIÓN: Reporte PDF del listado de primas (solo Administrador y
//              Director). Respeta la búsqueda aplicada en la lista (?buscar=).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_primas.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$buscar = trim($_GET['buscar'] ?? '');

$prima = new Prima($conexion);
$primas = $prima->mostrarPrimas($buscar);

registrarBitacora(
    $conexion,
    'Primas',
    'Generar PDF',
    'Generó el reporte PDF del listado de primas' . ($buscar !== '' ? " (búsqueda: \"$buscar\")" : '') . '.'
);

$filas = [];

foreach ($primas as $fila) {
    $filas[] = [
        (string) ($fila['tipo_prima'] ?? ''),
        ((string) ($fila['porcentaje'] ?? '')) . '%',
        reporteFecha($fila['fecha'] ?? ''),
        (string) ($fila['estado'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE PRIMAS',
    ['Tipo de Prima', 'Porcentaje', 'Fecha de Creación', 'Estado'],
    $filas,
    'Reporte_Primas.pdf'
);
