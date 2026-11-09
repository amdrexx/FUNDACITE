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
$primas = $prima->listar($buscar);

registrarBitacora(
    $conexion,
    'Primas',
    'Generar PDF',
    'Generó el reporte PDF del listado de primas' . ($buscar !== '' ? " (búsqueda: \"$buscar\")" : '') . '.'
);

$filas = [];

foreach ($primas as $fila) {
    $filas[] = [
        (string) ($fila['cedula'] ?? ''),
        (string) ($fila['nombre_completo'] ?? ''),
        (string) ($fila['tipo_prima'] ?? ''),
        (string) ($fila['condicion'] ?? ''),
        ((string) ($fila['porcentaje'] ?? '')) . '%',
        number_format((float) ($fila['monto'] ?? 0), 2, ',', '.'),
        reporteFecha($fila['fecha'] ?? ''),
        (string) ($fila['estatus'] ?? 'Activo'),
    ];
}

generarReportePdf(
    'REPORTE DE PRIMAS',
    ['Cédula', 'Trabajador', 'Tipo de Prima', 'Condición', 'Porcentaje', 'Monto (Bs.)', 'Fecha', 'Estatus'],
    $filas,
    'Reporte_Primas.pdf'
);
