<?php
// ============================================================================
// ARCHIVO: pdf/reporte_convenios.php
// DESCRIPCIÓN: Reporte PDF del listado de convenios (solo Administrador y
//              Director). Respeta la búsqueda aplicada en la lista (?buscar=).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_convenio.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$buscar = trim($_GET['buscar'] ?? '');

$convenio = new Convenio($conexion);
$convenios = $convenio->listarConvenios($buscar);

registrarBitacora(
    $conexion,
    'Convenios',
    'Generar PDF',
    'Generó el reporte PDF del listado de convenios' . ($buscar !== '' ? " (búsqueda: \"$buscar\")" : '') . '.'
);

$filas = [];

foreach ($convenios as $fila) {
    $filas[] = [
        (string) ($fila['nombre_gremio'] ?? ''),
        reporteFecha($fila['fecha_inicio'] ?? ''),
        reporteFecha($fila['fecha_vencimiento'] ?? ''),
        (string) ($fila['dias_disfrute'] ?? ''),
        Convenio::estadoVigencia($fila['fecha_inicio'], $fila['fecha_vencimiento']),
    ];
}

generarReportePdf(
    'REPORTE DE CONVENIOS',
    ['Gremio', 'Fecha de Inicio', 'Fecha de Vencimiento', 'Días de Disfrute', 'Estado'],
    $filas,
    'Reporte_Convenios.pdf'
);
