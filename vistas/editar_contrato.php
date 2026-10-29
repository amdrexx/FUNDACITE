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
    header("Location: lista_contrato.php");
    exit;
}

// Consultar datos actuales del contrato usando el método del controlador
$contrato = $controladorContrato->buscarPorId($id_contrato);

if (!$contrato) {
    header("Location: lista_contrato.php?status=not_found");
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
    <div style="max-width: 900px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formContratos" action="../controladores/ctrl_contrato.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                
                <!-- Campo oculto para enrutar la acción en el controlador -->
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_contrato" value="<?php echo htmlspecialchars($contrato['id_contrato']); ?>">
                <input type="hidden" name="id_trabajador" id="id_trabajador" value="<?php echo htmlspecialchars($contrato['id_trabajador']); ?>">

                <div class="form-grid">
                    <h2 class="full-width" style="text-align: center; margin-bottom: 20px;">Modificar Contrato</h2>

                    <h3 class="form-section-title full-width">Trabajador Asociado</h3>

                    <!-- Cédula del Trabajador (Informativo) -->
                    <div class="field">
                        <label>Cédula</label>
                        <input type="text" 
                               value="<?php echo htmlspecialchars($contrato['cedula_trabajador'] ?? $contrato['cedula'] ?? 'N/A'); ?>" 
                               readonly 
                               style="background-color: rgba(255, 255, 255, 0.05); cursor: not-allowed;">
                    </div>

                    <!-- Nombre del Trabajador (Informativo) -->
                    <div class="field">
                        <label>Nombres y Apellidos</label>
                        <input type="text" 
                               value="<?php echo htmlspecialchars($contrato['nombre_trabajador'] ?? (($contrato['nombres'] ?? '') . ' ' . ($contrato['apellidos'] ?? 'No especificado'))); ?>" 
                               readonly 
                               style="background-color: rgba(255, 255, 255, 0.05); cursor: not-allowed;">
                    </div>

                    <!-- Cargo del Trabajador (Informativo) -->
                    <div class="field full-width">
                        <label>Cargo del Trabajador</label>
                        <input type="text" 
                               value="<?php echo htmlspecialchars($contrato['nombre_cargo'] ?? $contrato['cargo'] ?? 'Sin cargo asignado'); ?>" 
                               readonly 
                               style="background-color: rgba(255, 255, 255, 0.05); cursor: not-allowed;">
                    </div>

                    <h3 class="form-section-title full-width">Detalles del Contrato</h3>

                    <!-- Tipo de Contrato -->
                    <div class="field">
                        <label>Tipo de Contrato</label>
                        <select name="tipo_contrato" id="tipo_contrato" required>
                            <?php 
                            $tipos = ['Tiempo indeterminado', 'Tiempo determinado'];
                            foreach ($tipos as $tipo): 
                            ?>
                                <option value="<?php echo $tipo; ?>" style="color: black;" <?php echo (($contrato['tipo_contrato'] ?? '') === $tipo) ? 'selected' : ''; ?>>
                                    <?php echo $tipo; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tipo de Personal -->
                    <div class="field">
                        <label>Tipo de Personal</label>
                        <select name="tipo_personal" required>
                            <option value="" disabled>Seleccione el tipo de personal</option>
                            <?php 
                            $tiposPersonal = ['Fijo', 'Contratado', 'Obrero', 'Jubilado'];
                            foreach ($tiposPersonal as $tp): 
                            ?>
                                <option value="<?php echo $tp; ?>" style="color: black;" <?php echo (($contrato['tipo_personal'] ?? '') === $tp) ? 'selected' : ''; ?>>
                                    <?php echo $tp; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Lugar de Trabajo -->
                    <div class="field">
                        <label>Lugar de Trabajo</label>
                        <input type="text" name="lugar_trabajo" value="<?php echo htmlspecialchars($contrato['lugar_trabajo'] ?? ''); ?>" required>
                    </div>

                    <!-- Fecha de Inicio -->
                    <div class="field">
                        <label>Fecha de Inicio del Contrato</label>
                        <input type="date" name="fecha_contrato" value="<?php echo htmlspecialchars($contrato['fecha_contrato'] ?? ''); ?>" required>
                    </div>

                    <!-- Fecha de Finalización -->
                    <div class="field">
                        <label>Fecha de Finalización</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($contrato['fecha_fin'] ?? ''); ?>">
                    </div>

                    <h3 class="form-section-title full-width">Datos del Representante (Presidente)</h3>

                    <!-- Nombre del Presidente -->
                    <div class="field">
                        <label>Nombre del Presidente</label>
                        <input type="text" 
                               name="nombre_presidente" 
                               value="<?php echo htmlspecialchars($contrato['nombre_presidente'] ?? ''); ?>" 
                               readonly 
                               style="background-color: rgba(255, 255, 255, 0.05); cursor: not-allowed;">
                    </div>

                    <!-- Cédula del Presidente -->
                    <div class="field">
                        <label>Cédula del Presidente</label>
                        <input type="text" 
                               name="cedula_presidente" 
                               value="<?php echo htmlspecialchars($contrato['cedula_presidente'] ?? ''); ?>" 
                               readonly 
                               style="background-color: rgba(255, 255, 255, 0.05); cursor: not-allowed;">
                    </div>

                    <!-- Gaceta Oficial (Editable manualmente) -->
                    <div class="field full-width">
                        <label>Gaceta Oficial de Designación</label>
                        <input type="text" 
                               name="gaceta_designacion_presidente" 
                               value="<?php echo htmlspecialchars($contrato['gaceta_designacion_presidente'] ?? ''); ?>" 
                               required>
                    </div>

                    <!-- Botones de Acción / Navegación -->
                    <div class="form-actions full-width" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <a href="lista_contrato.php" style="color: #bbb; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                            <i class="bi bi-arrow-left"></i> Volver a la Lista
                        </a>
                        <button type="submit" name="editar_contrato" class="btn-guardar">
                            Guardar Cambios
                        </button>
                    </div>

                </div>
            </form>
        </div>

    </div>
</div>

<script src="/FUNDACITE/vistas/js/valid_contrato.js"></script>
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
