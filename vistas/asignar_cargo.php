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

$cargos = $controladorAsignarCargo->obtenerCargos();
$todosTrabajadores = $controladorAsignarCargo->obtenerTodosLosTrabajadores(); // Para el selector
$trabajadoresConCargo = $controladorAsignarCargo->listarTrabajadoresConCargo(); // Para la tabla inferior

$cedulaPreseleccionada = $_GET['cedula'] ?? '';

$status = $_GET['status'] ?? null;
$mensajeExito = $_SESSION['exito_asignacion'] ?? null;
$mensajeError = isset($_SESSION['error_asignacion']) && is_array($_SESSION['error_asignacion']) ? implode("<br>", $_SESSION['error_asignacion']) : null;

unset($_SESSION['exito_asignacion'], $_SESSION['error_asignacion']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignación de Cargos</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 600px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <!-- FORMULARIO DE ASIGNACIÓN CON SELECT -->
        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formAsignarCargo" action="../controladores/ctrl_asignar_cargo.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <center><h2>Asignar Cargo a Trabajador</h2></center>

                <!-- SELECTOR DE TRABAJADOR (TODOS LOS ACTIVOS) -->
                <div class="field">
                    <label>Seleccionar Trabajador</label>
                    <select name="id_trabajador" id="id_trabajador" required onchange="cargarCargoActual(this)">
                        <option value="" style="color: black;">-- Seleccione un Trabajador --</option>
                        <?php foreach ($todosTrabajadores as $t): ?>
                            <option value="<?php echo $t['id_trabajador']; ?>" 
                                    data-idcargo="<?php echo $t['id_cargo'] ?? ''; ?>" 
                                    style="color: black;"
                                    <?php echo ($cedulaPreseleccionada === $t['cedula']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['cedula'] . ' - ' . $t['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- SELECTOR DE CARGO -->
                <div class="field">
                    <label>Cargo a Asignar</label>
                    <select name="id_cargo" id="id_cargo" required>
                        <option value="" style="color: black;">-- Seleccione un Cargo --</option>
                        <?php foreach ($cargos as $cargo): ?>
                            <option value="<?php echo $cargo['id_cargo']; ?>" style="color: black;">
                                <?php echo htmlspecialchars($cargo['nombre_cargo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="asignar_cargo" class="btn-guardar">Guardar Asignación</button>
            </form>
        </div>

        <!-- TABLA DE TRABAJADORES Y SUS CARGOS (SOLO LOS QUE TIENEN CARGO) -->
        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white;">Cargos Asignados</h2>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Trabajador</th>
                            <th>Cargo Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($trabajadoresConCargo)): ?>
                            <?php foreach ($trabajadoresConCargo as $t): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_cargo']); ?></td>
                                    <td class="acciones">
                                        <a href="../controladores/ctrl_asignar_cargo.php?action=desvincular&id_trabajador=<?php echo $t['id_trabajador']; ?>" 
                                           class="btn-eliminar" 
                                           style="text-decoration: none; display: inline-block;" 
                                           onclick="return confirm('¿Deseas desvincular el cargo de este trabajador?');">
                                            Quitar Cargo
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">No hay trabajadores con cargos asignados aún.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
function cargarCargoActual(selectTrabajador) {
    const selectedOption = selectTrabajador.options[selectTrabajador.selectedIndex];
    const idCargo = selectedOption.getAttribute('data-idcargo');
    const selectCargo = document.getElementById('id_cargo');
    
    if (idCargo) {
        selectCargo.value = idCargo;
    } else {
        selectCargo.value = '';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const selectTrab = document.getElementById('id_trabajador');
    if (selectTrab.value !== '') {
        cargarCargoActual(selectTrab);
    }

    let title = '';

    <?php if ($mensajeExito): ?>
        title = '<?php echo addslashes($mensajeExito); ?>';
    <?php elseif ($mensajeError): ?>
        title = '<?php echo addslashes($mensajeError); ?>';
    <?php elseif ($status === 'success'): ?>
        title = '¡Cargo asignado correctamente!';
    <?php elseif ($status === 'unlinked'): ?>
        title = 'Cargo desvinculado correctamente.';
    <?php elseif ($status === 'error'): ?>
        title = 'Hubo un error al procesar la solicitud.';
    <?php endif; ?>

    if (title !== '') {
        Swal.fire({
            title: title,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#007bff',
            width: '380px',
            padding: '1.25rem',
            customClass: {
                popup: 'modal-compacto',
                title: 'titulo-modal-compacto',
                confirmButton: 'btn-modal-compacto'
            }
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
});
</script>

<style>
.modal-compacto {
    font-family: inherit !important;
    border-radius: 12px !important;
}
.titulo-modal-compacto {
    font-size: 15px !important;
    font-weight: normal !important;
    color: #444 !important;
    margin: 10px 0 15px 0 !important;
}
.btn-modal-compacto {
    font-size: 13px !important;
    padding: 6px 18px !important;
    border-radius: 6px !important;
}
</style>

</body>
</html>