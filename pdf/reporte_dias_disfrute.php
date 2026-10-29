<?php
// ============================================================================
// ARCHIVO: pdf/reporte_dias_disfrute.php
// DESCRIPCIÓN: Reporte PDF de registros de días de disfrute
//              (solo Administrador y Director). Respeta la búsqueda (?buscar=).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_dias_disfrute.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$buscar = trim($_GET['buscar'] ?? '');

$diasDisfruteModelo = new DiasDisfrute($conexion);
$registros = $diasDisfruteModelo->mostrarDiasDisfrute($buscar);

registrarBitacora(
    $conexion,
    'Días de Disfrute',
    'Generar PDF',
    'Generó el reporte PDF del listado de días de disfrute' . ($buscar !== '' ? " (búsqueda: \"$buscar\")" : '') . '.'
);

$filas = [];

foreach ($registros as $s) {
    $filas[] = [
        (string) ($s['codigo_disfrute'] ?? ''),
        (string) ($s['trabajador'] ?? ''),
        (string) ($s['cedula'] ?? ''),
        (string) ($s['motivo'] ?? ''),
        reporteFecha($s['fecha_inicio'] ?? ''),
        reporteFecha($s['fecha_finalizacion'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE DÍAS DE DISFRUTE',
    ['Código', 'Trabajador', 'Cédula', 'Motivo', 'Inicio', 'Finalización'],
    $filas,
    'Reporte_Dias_Disfrute.pdf',
    'landscape'
);
