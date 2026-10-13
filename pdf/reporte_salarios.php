<?php
// ============================================================================
// ARCHIVO: pdf/reporte_salarios.php
// DESCRIPCIÓN: Reporte PDF del catálogo de salarios (solo Administrador y
//              Director).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_salario.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$modeloSalario = new clase_salario($conexion);
$salarios = $modeloSalario->listarSalarios();

registrarBitacora(
    $conexion,
    'Salarios',
    'Generar PDF',
    'Generó el reporte PDF del listado de salarios.'
);

$filas = [];

foreach ($salarios as $fila) {
    $filas[] = [
        (($fila['tipo_salario'] ?? '') === 'cargo') ? 'Sueldo por cargo' : 'Sueldo base',
        !empty($fila['nombre_cargo']) ? (string) $fila['nombre_cargo'] : '—',
        reporteFecha($fila['fecha'] ?? ''),
        'Bs ' . number_format((float) ($fila['monto'] ?? 0), 2, ',', '.'),
        (string) ($fila['estado'] ?? ''),
    ];
}

generarReportePdf(
    'REPORTE DE SALARIOS',
    ['Tipo', 'Cargo', 'Fecha', 'Monto', 'Estado'],
    $filas,
    'Reporte_Salarios.pdf'
);
