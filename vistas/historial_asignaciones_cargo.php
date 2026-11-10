<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../controladores/ctrl_asignar_cargo.php";

$historialAsignaciones = $controladorAsignarCargo->listarHistorialAsignaciones();

registrarBitacora($conexion, 'Asignación de Cargos', 'Consultar', 'Consultó el historial de asignaciones de cargos.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Asignaciones de Cargos</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 1100px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important; overflow-x: auto;">
                <h2 style="text-align:center; color:white; margin-bottom: 20px;">Historial de Asignaciones de Cargos</h2>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                    <a href="asignar_cargo.php" class="btn-pdf" style="text-decoration:none;">
                        <i class="bi bi-arrow-left"></i> Volver a Asignación de Cargo
                    </a>
                    <a href="/FUNDACITE/pdf/reporte_historial_asignaciones.php" class="btn-pdf" target="_blank" style="text-decoration:none;">
                        <i class="bi bi-file-earmark-pdf"></i> Exportar historial PDF
                    </a>
                </div>

                <div class="modulo-card" style="margin: 20px auto 0; padding: 10px;">
                    <table class="tabla">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Funcionario(a)</th>
                            <th>Cargo asignado</th>
                            <th>Gaceta</th>
                            <th>Fecha de asignación</th>
                            <th>Fecha de finalización</th>
                            <th>Estado</th>
                            <th>Asignado por</th>
                            <th>Desvinculado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($historialAsignaciones)): ?>
                            <?php foreach ($historialAsignaciones as $asignacion): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($asignacion['cedula'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($asignacion['funcionario'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($asignacion['nombre_cargo'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(($asignacion['gaceta'] ?? '') !== '' ? $asignacion['gaceta'] : 'No registrada'); ?></td>
                                    <td><?php echo !empty($asignacion['fecha_asignacion']) ? date('d/m/Y H:i', strtotime($asignacion['fecha_asignacion'])) : '—'; ?></td>
                                    <td><?php echo !empty($asignacion['fecha_fin']) ? date('d/m/Y H:i', strtotime($asignacion['fecha_fin'])) : 'Actualmente asignado'; ?></td>
                                    <td><?php echo htmlspecialchars($asignacion['estado'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($asignacion['asignado_por'] ?? 'No disponible'); ?></td>
                                    <td><?php echo htmlspecialchars($asignacion['desvinculado_por'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;">Todavía no hay registros en el historial de asignaciones.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>
