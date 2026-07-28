<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../controladores/ctrl_contrato.php";

// Obtener el ID del contrato a editar desde la URL
$id_contrato = $_GET['id'] ?? null;

if (!$id_contrato) {
    header("Location: registrar_contrato.php");
    exit;
}

// Consultar datos actuales del contrato usando el método del controlador
$contrato = $controladorContrato->buscarPorId($id_contrato);

if (!$contrato) {
    header("Location: registrar_contrato.php?status=not_found");
    exit;
}

$status = $_GET['status'] ?? null;
$mensajeExito = $_SESSION['exito_contrato'] ?? null;
$mensajeError = isset($_SESSION['error_contrato']) && is_array($_SESSION['error_contrato']) ? implode("<br>", $_SESSION['error_contrato']) : null;

unset($_SESSION['exito_contrato'], $_SESSION['error_contrato']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Contrato</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 600px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" action="../controladores/ctrl_contrato.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <center><h2>Modificar Contrato</h2></center>

                <input type="hidden" name="id_contrato" value="<?php echo htmlspecialchars($contrato['id_contrato']); ?>">
                <input type="hidden" name="id_trabajador" value="<?php echo htmlspecialchars($contrato['id_trabajador']); ?>">

                <!-- TRABAJADOR (INFORMATIVO) -->
                <div class="field">
                    <label>Trabajador Asignado</label>
                    <input type="text" 
                           value="<?php echo htmlspecialchars(($contrato['cedula_trabajador'] ?? $contrato['cedula'] ?? '') . ' - ' . ($contrato['nombre_trabajador'] ?? ($contrato['nombres'] ?? '') . ' ' . ($contrato['apellidos'] ?? ''))); ?>" 
                           readonly 
                           style="background-color: rgba(255, 255, 255, 0.15); cursor: not-allowed; color: #e0e0e0; font-weight: bold;">
                </div>

                <!-- CAMPOS MODIFICABLES DEL CONTRATO -->
                <div class="field">
                    <label>Tipo de Contrato</label>
                    <select name="tipo_contrato" required>
                        <?php 
                        $tipos = ['Indefinido', 'Tiempo determinado', 'Obra determinada', 'Pasantía', 'Suplencia'];
                        foreach ($tipos as $tipo): 
                        ?>
                            <option value="<?php echo $tipo; ?>" style="color: black;" <?php echo ($contrato['tipo_contrato'] === $tipo) ? 'selected' : ''; ?>>
                                <?php echo $tipo; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Fecha del Contrato</label>
                    <input type="date" name="fecha_contrato" value="<?php echo htmlspecialchars($contrato['fecha_contrato'] ?? ''); ?>" required>
                </div>

                <div class="field">
                    <label>Lugar de Trabajo</label>
                    <input type="text" name="lugar_trabajo" value="<?php echo htmlspecialchars($contrato['lugar_trabajo'] ?? ''); ?>" required>
                </div>

                <!-- DATOS DEL PRESIDENTE (BLOQUEADOS CON READONLY) -->
                <div class="field">
                    <label>Nombre del Presidente</label>
                    <input type="text" 
                           name="nombre_presidente" 
                           value="<?php echo htmlspecialchars($contrato['nombre_presidente'] ?? ''); ?>" 
                           readonly 
                           style="background-color: rgba(255, 255, 255, 0.15); cursor: not-allowed; color: #e0e0e0;">
                </div>

                <div class="field">
                    <label>Cédula del Presidente</label>
                    <input type="text" 
                           name="cedula_presidente" 
                           value="<?php echo htmlspecialchars($contrato['cedula_presidente'] ?? ''); ?>" 
                           readonly 
                           style="background-color: rgba(255, 255, 255, 0.15); cursor: not-allowed; color: #e0e0e0;">
                </div>

                <div class="field">
                    <label>Gaceta Oficial de Designación</label>
                    <input type="text" 
                           name="gaceta_designacion_presidente" 
                           value="<?php echo htmlspecialchars($contrato['gaceta_designacion_presidente'] ?? ''); ?>" 
                           readonly 
                           style="background-color: rgba(255, 255, 255, 0.15); cursor: not-allowed; color: #e0e0e0;">
                </div>

                <!-- BOTONES DE ACCIÓN (ESTILO EXACTO A TU IMAGEN) -->
                <div style="display: flex; gap: 10px; width: 100%; margin-top: 25px;">
                    <button type="submit" 
                            name="editar_contrato" 
                            class="btn-guardar" 
                            style="flex: 1; margin: 0 !important;">
                        Guardar Cambios
                    </button>
                    
                    <a href="registrar_contrato.php" 
                       style="background-color: #007bff; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; box-sizing: border-box;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let title = '';

    <?php if ($mensajeExito): ?>
        title = '<?php echo addslashes($mensajeExito); ?>';
    <?php elseif ($mensajeError): ?>
        title = '<?php echo addslashes($mensajeError); ?>';
    <?php elseif ($status === 'success'): ?>
        title = '¡Contrato actualizado correctamente!';
    <?php elseif ($status === 'error'): ?>
        title = 'Hubo un error al actualizar el contrato.';
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