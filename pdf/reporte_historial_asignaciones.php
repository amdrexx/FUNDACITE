<?php
// Reporte histórico de las asignaciones de cargo.
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';
requireAdministradorODirector();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_asignar_cargo.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';
require_once __DIR__ . '/plantilla_reporte.php';

$modelo = new clase_asignar_cargo($conexion);
$historial = $modelo->listarHistorialAsignaciones();
registrarBitacora($conexion, 'Asignación de Cargos', 'Generar PDF', 'Generó el reporte histórico de asignaciones de cargos.');

$filas = [];
foreach ($historial as $registro) {
    $filas[] = [
        (string) ($registro['cedula'] ?? ''),
        (string) ($registro['funcionario'] ?? ''),
        (string) ($registro['nombre_cargo'] ?? ''),
        (string) (($registro['gaceta'] ?? '') !== '' ? $registro['gaceta'] : 'No registrada'),
        !empty($registro['fecha_asignacion']) ? date('d/m/Y H:i', strtotime($registro['fecha_asignacion'])) : '',
        !empty($registro['fecha_fin']) ? date('d/m/Y H:i', strtotime($registro['fecha_fin'])) : 'Actualmente asignado',
        (string) ($registro['estado'] ?? ''),
        (string) ($registro['asignado_por'] ?? 'No disponible'),
        (string) ($registro['desvinculado_por'] ?? '—'),
    ];
}

generarReportePdf(
    'HISTORIAL DE ASIGNACIONES DE CARGOS',
    ['Cédula', 'Funcionario(a)', 'Cargo', 'Gaceta', 'Fecha asignación', 'Fecha finalización', 'Estado', 'Asignado por', 'Desvinculado por'],
    $filas,
    'Historial_Asignaciones_Cargos.pdf',
    'landscape'
);
