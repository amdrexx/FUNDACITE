<?php
// ============================================================================
// ARCHIVO: pdf/reporte_trabajadores.php
// DESCRIPCIÓN: Reporte PDF del listado de trabajadores (solo Administrador y
//              Director). Respeta la búsqueda aplicada en la lista (?buscar=).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_trabajador.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$buscar = trim($_GET['buscar'] ?? '');

$trabajador = new Trabajador($conexion);
$trabajadores = $trabajador->listarTrabajadores($buscar);

registrarBitacora(
    $conexion,
    'Trabajadores',
    'Generar PDF',
    'Generó el reporte PDF del listado de trabajadores' . ($buscar !== '' ? " (búsqueda: \"$buscar\")" : '') . '.'
);

$filas = [];

foreach ($trabajadores as $fila) {
    $filas[] = [
        (string) ($fila['cedula'] ?? ''),
        trim(($fila['apellidos'] ?? '') . ' ' . ($fila['nombres'] ?? '')),
        reporteFecha($fila['fecha_ingreso'] ?? ''),
        trim((string) ($fila['tipo_contrato'] ?? '')) !== '' ? (string) $fila['tipo_contrato'] : 'Sin contrato',
        (string) ($fila['status'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE TRABAJADORES',
    ['Cédula', 'Apellidos y Nombres', 'Fecha de Ingreso', 'Tipo de Contratación', 'Estatus Laboral'],
    $filas,
    'Reporte_Trabajadores.pdf'
);
