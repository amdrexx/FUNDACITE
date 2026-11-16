<?php
// ============================================================================
// ARCHIVO: pdf/reporte_constancias.php
// DESCRIPCIÓN: Reporte PDF de constancias de trabajo
//              (solo Administrador y Director). Respeta la búsqueda (?buscar=).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_constancia.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$buscar = trim($_GET['buscar'] ?? '');

$constanciaModelo = new ConstanciaTrabajo($conexion);
$constancias = $constanciaModelo->listarConstancias($buscar);

registrarBitacora(
    $conexion,
    'Constancias',
    'Generar PDF',
    'Generó el reporte PDF del listado de constancias de trabajo' . ($buscar !== '' ? " (búsqueda: \"$buscar\")" : '') . '.'
);

$filas = [];

foreach ($constancias as $c) {
    $filas[] = [
        (string) ($c['codigo_constancia'] ?? ''),
        (string) ($c['trabajador'] ?? ''),
        (string) ($c['cedula'] ?? ''),
        (string) ($c['nombre_cargo'] ?? ''),
        (string) ($c['tipo_personal'] ?? ''),
        reporteFecha($c['fecha_constancia'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE CONSTANCIAS DE TRABAJO',
    ['Código', 'Trabajador', 'Cédula', 'Cargo', 'Tipo de Personal', 'Fecha'],
    $filas,
    'Reporte_Constancias.pdf',
    'landscape'
);
