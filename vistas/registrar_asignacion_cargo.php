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

$trabajadoresSinCargo = $controladorAsignarCargo->obtenerTodosLosTrabajadores();
$cargosDisponibles = $controladorAsignarCargo->obtenerCargos();

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

registrarBitacora($conexion, 'Asignación de Cargos', 'Consultar', 'Consultó el formulario para asignar un cargo.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Cargo a Trabajador</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <style>
        .btn-asignar-cargo{background:#16794b;color:#fff;border:0;border-radius:8px;padding:11px 18px;cursor:pointer;font-weight:700}
        .btn-asignar-cargo:hover{background:#11623c}
        .btn-asignar-cargo:disabled{opacity:.55;cursor:not-allowed}
        .form-asignacion-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:18px}
        .campo-asignacion{display:flex;flex-direction:column;gap:7px;min-width:0}
        .campo-asignacion label{color:#fff;font-weight:600}
        .campo-asignacion select,.campo-asignacion input{width:100%;min-height:42px;padding:8px 10px;border:1px solid #b8c3cf;border-radius:7px;background:#f4f6f8;color:#202833;box-sizing:border-box}
        .nota-asignacion{color:rgba(255,255,255,.82);font-size:13px;line-height:1.5;margin-top:12px}
        @media(max-width:650px){.form-asignacion-grid{grid-template-columns:1fr}.btn-asignar-cargo{width:100%}}
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 900px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <div style="width:100%;display:block;box-sizing:border-box;">
            <div class="glass tabla-container" style="width:100%!important;max-width:100%!important;box-sizing:border-box;margin:0!important;padding:24px;">
                <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
                    <a href="asignar_cargo.php" class="btn-pdf" style="text-decoration:none;">
                        <i class="bi bi-arrow-left"></i> Volver al Listado de Cargos Asignados
                    </a>
                </div>

                <h2 style="text-align:center;color:white;margin:0 0 8px;">Asignar cargo a un trabajador</h2>

                <?php if (!empty($trabajadoresSinCargo) && !empty($cargosDisponibles)): ?>
                    <form class="form-card" method="POST" action="../controladores/ctrl_asignar_cargo.php" style="margin-top:18px;">
                        <input type="hidden" name="asignar_cargo" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfAsignacionCargo, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-asignacion-grid">
                            <div class="campo-asignacion">
                                <label for="id_trabajador_asignacion">Trabajador(a)</label>
                                <select name="id_trabajador" id="id_trabajador_asignacion" required>
                                    <option value="">Seleccione un trabajador</option>
                                    <?php foreach ($trabajadoresSinCargo as $trabajador): ?>
                                        <option value="<?php echo (int) $trabajador['id_trabajador']; ?>">
                                            <?php echo htmlspecialchars($trabajador['cedula'] . ' - ' . $trabajador['nombres'] . ' ' . $trabajador['apellidos']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo-asignacion">
                                <label for="id_cargo_asignacion">Cargo que se asignará</label>
                                <select name="id_cargo" id="id_cargo_asignacion" required>
                                    <option value="">Seleccione un cargo</option>
                                    <?php foreach ($cargosDisponibles as $cargo): ?>
                                        <option value="<?php echo (int) $cargo['id_cargo']; ?>"><?php echo htmlspecialchars($cargo['nombre_cargo']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo-asignacion" style="grid-column:1 / -1;">
                                <label for="gaceta_asignacion">Gaceta utilizada para la asignación</label>
                                <input type="text" name="gaceta" id="gaceta_asignacion" maxlength="150" required
                                       placeholder="Ej.: Gaceta Oficial N.º 6.123, de fecha 15/03/2026" autocomplete="off">
                            </div>
                            <div class="full-width" style="display:flex;justify-content:center;margin-top:2px;">
                                <button type="submit" class="btn-asignar-cargo">
                                    <i class="bi bi-person-plus"></i> Asignar cargo
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="nota-asignacion" style="text-align:center;margin-top:18px;">
                        <?php if (empty($trabajadoresSinCargo)): ?>
                            No hay trabajadores activos sin cargo disponibles para asignar. Primero debe desvincular manualmente el cargo correspondiente, cuando aplique.
                        <?php else: ?>
                            No hay cargos registrados para realizar una asignación.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>
