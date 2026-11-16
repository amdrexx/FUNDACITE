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

// Esta vista solo muestra el listado de trabajadores con sus cargos actuales;
// el formulario de asignación vive en registrar_asignacion_cargo.php.
$trabajadoresConCargo = $controladorAsignarCargo->listarTrabajadoresConCargo();
if (empty($_SESSION['csrf_asignacion_cargo'])) {
    $_SESSION['csrf_asignacion_cargo'] = bin2hex(random_bytes(32));
}
$csrfAsignacionCargo = $_SESSION['csrf_asignacion_cargo'];

// Los mensajes de este módulo también se muestran con la alerta global.
if (!isset($_SESSION['flash_alerta'])) {
    if (!empty($_SESSION['error_asignacion'])) {
        $_SESSION['flash_alerta'] = [
            'tipo' => 'error',
            'mensaje' => implode("\n", array_map('strval', (array) $_SESSION['error_asignacion']))
        ];
        unset($_SESSION['error_asignacion']);
    } elseif (!empty($_SESSION['exito_asignacion'])) {
        $_SESSION['flash_alerta'] = [
            'tipo' => 'success',
            'mensaje' => (string) $_SESSION['exito_asignacion']
        ];
        unset($_SESSION['exito_asignacion']);
    }
}

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
    <style>
        .btn-desvincular-cargo{background:#b42318;color:#fff;border:0;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:600}
        .btn-desvincular-cargo:hover{background:#8f1c13}
        .btn-asignar-cargo{background:#16794b;color:#fff;border:0;border-radius:8px;padding:11px 18px;cursor:pointer;font-weight:700}
        .btn-asignar-cargo:hover{background:#11623c}
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 900px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <!-- TABLA DE TRABAJADORES Y SUS CARGOS ACTUALES -->
        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white; margin-bottom: 20px;">Listado de Cargos Asignados</h2>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                    <a href="registrar_asignacion_cargo.php" class="btn-asignar-cargo" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <i class="bi bi-person-plus"></i>
                        Asignar cargo
                    </a>
                    <a href="historial_asignaciones_cargo.php" class="btn-pdf" style="text-decoration:none;">
                        <i class="bi bi-clock-history"></i>
                        Ver historial de asignaciones
                    </a>
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
                
                <div class="modulo-card" style="margin: 20px auto 0; padding: 10px;">
                    <table class="tabla" style="width: 100% !important;">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Trabajador</th>
                            <th>Cargo Actual</th>
                            <th>Gaceta de asignación</th>
                            <th>Estatus laboral</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($trabajadoresConCargo)): ?>
                            <?php foreach ($trabajadoresConCargo as $t): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombres'] . ' ' . $t['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_cargo']); ?></td>
                                    <td><?php echo htmlspecialchars(($t['gaceta'] ?? '') !== '' ? $t['gaceta'] : 'No registrada'); ?></td>
                                    <td><?php echo htmlspecialchars($t['status'] ?? ''); ?></td>
                                    <td>
                                        <?php $cargoActualNormalizado = strtolower(trim($t['nombre_cargo'] ?? '')); ?>
                                        <?php if (in_array($cargoActualNormalizado, ['presidente', 'director'], true)): ?>
                                            <span style="color:rgba(255,255,255,.82);font-size:12px;">Se sustituye al registrar un nuevo titular</span>
                                        <?php else: ?>
                                            <form method="POST" action="../controladores/ctrl_asignar_cargo.php"
                                                  data-confirm="¿Está seguro de desvincular este cargo? Se conservará el estatus laboral del trabajador y el movimiento quedará registrado en el historial.">
                                                <input type="hidden" name="accion" value="desvincular_cargo">
                                                <input type="hidden" name="id_trabajador" value="<?php echo (int) $t['id_trabajador']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfAsignacionCargo, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="btn-desvincular-cargo">
                                                    <i class="bi bi-person-dash"></i> Desvincular
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No hay trabajadores con cargos asignados aún.</td>
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