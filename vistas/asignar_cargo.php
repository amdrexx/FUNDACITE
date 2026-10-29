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

// Obtenemos únicamente el listado de trabajadores con sus cargos actuales
$trabajadoresConCargo = $controladorAsignarCargo->listarTrabajadoresConCargo(); 

registrarBitacora($conexion, 'Asignación de Cargos', 'Consultar', 'Consultó el listado de cargos asignados a los trabajadores.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargos Asignados a Trabajadores</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 900px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <!-- TABLA DE TRABAJADORES Y SUS CARGOS (SOLO LISTADO) -->
        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white; margin-bottom: 20px;">Listado de Cargos Asignados</h2>

                <div style="text-align:right; margin-bottom:15px;">
                    <a
                        href="/FUNDACITE/pdf/reporte_cargos_asignados.php"
                        class="btn-pdf"
                        target="_blank"
                        style="text-decoration:none;"
                    >
                        <i class="bi bi-file-earmark-pdf"></i>
                        Exportar PDF
                    </a>
                </div>

                <table class="tabla" style="width: 100% !important;">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Trabajador</th>
                            <th>Cargo Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($trabajadoresConCargo)): ?>
                            <?php foreach ($trabajadoresConCargo as $t): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombres'] . ' ' . $t['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_cargo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center;">No hay trabajadores con cargos asignados aún.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>