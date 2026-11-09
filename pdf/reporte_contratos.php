<?php
// ============================================================================
// ARCHIVO: pdf/reporte_contratos.php
// DESCRIPCIÓN: Reporte PDF del listado de contratos (solo Administrador y
//              Director).
// ============================================================================
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden descargar el reporte.
requireAdministradorODirector();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';
require_once __DIR__ . '/../controladores/ctrl_contrato.php';

// ctrl_contrato.php ya instancia $controladorContrato y su propio
// mostrarContratos() registra en bitácora la consulta del listado.
$contratos = $controladorContrato->mostrarContratos();

registrarBitacora(
    $conexion,
    'Contratos',
    'Generar PDF',
    'Generó el reporte PDF del listado de contratos.'
);

$filas = [];

foreach ($contratos as $fila) {
    $filas[] = [
        (string) ($fila['nombre_trabajador'] ?? ''),
        (string) ($fila['nombre_cargo'] ?? $fila['cargo'] ?? 'Sin asignar'),
        (string) ($fila['tipo_contrato'] ?? ''),
        reporteFecha($fila['fecha_contrato'] ?? ''),
        (!empty($fila['fecha_fin']) && $fila['fecha_fin'] !== '0000-00-00')
            ? reporteFecha($fila['fecha_fin'])
            : 'Indefinido',
    ];
}

generarReportePdf(
    'REPORTE DE CONTRATOS',
    ['Trabajador', 'Cargo', 'Tipo', 'Fecha Inicio', 'Fecha Fin'],
    $filas,
    'Reporte_Contratos.pdf'
);
