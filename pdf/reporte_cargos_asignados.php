<?php
// ============================================================================
// ARCHIVO: pdf/reporte_cargos_asignados.php
// DESCRIPCIÓN: Reporte PDF del listado de trabajadores con cargo asignado
//              (solo Administrador y Director).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_asignar_cargo.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$modeloAsignarCargo = new clase_asignar_cargo($conexion);
$trabajadoresConCargo = $modeloAsignarCargo->listarTrabajadoresConCargo();

registrarBitacora(
    $conexion,
    'Asignación de Cargos',
    'Generar PDF',
    'Generó el reporte PDF del listado de trabajadores con cargo asignado.'
);

$filas = [];

foreach ($trabajadoresConCargo as $fila) {
    $filas[] = [
        (string) ($fila['cedula'] ?? ''),
        trim(($fila['nombres'] ?? '') . ' ' . ($fila['apellidos'] ?? '')),
        (string) ($fila['nombre_cargo'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE CARGOS ASIGNADOS',
    ['Cédula', 'Trabajador', 'Cargo'],
    $filas,
    'Reporte_Cargos_Asignados.pdf'
);
