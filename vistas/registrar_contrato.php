<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cargamos el controlador oficial de contratos
require_once("../controladores/ctrl_contrato.php");

// Traemos los contratos reales de la BD
$contratos = $controladorContrato->mostrarContratos();

// Determinamos el mensaje y estado para el Modal
$status = $_GET['status'] ?? null;
$mensajeExito = $_SESSION['exito_contrato'] ?? null;
$mensajeError = isset($_SESSION['error_contrato']) && is_array($_SESSION['error_contrato']) ? implode("<br>", $_SESSION['error_contrato']) : null;

// Limpiamos las variables de sesión para que no se repitan
unset($_SESSION['exito_contrato'], $_SESSION['error_contrato']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Contratos</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <!-- Librería SweetAlert2 para la ventana modal -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">

    <div style="max-width: 600px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <!-- FORMULARIO DE REGISTRO -->
        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formContratos" action="../controladores/ctrl_contrato.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <center><h2>Registrar Nuevo Contrato</h2></center>

                <div class="field">
                    <label>Cédula del Trabajador</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="cedula_trabajador" placeholder="Ej: V-25785010" required style="flex-grow: 1;">
                        <button type="button" id="btn_buscar_trabajador" style="padding: 10px 15px; border-radius: 5px; background-color: #007bff; color: white; border: none; cursor: pointer; font-weight: bold;">Buscar</button>
                    </div>
                </div>

                <input type="hidden" name="id_trabajador" id="id_trabajador" required>

                <div class="field">
                    <label>Trabajador Seleccionado</label>
                    <input type="text" id="nombre_trabajador" placeholder="Busca un trabajador primero..." readonly style="background-color: rgba(255, 255, 255, 0.1); cursor: not-allowed;">
                </div>

                <div class="field">
                    <label>Tipo de Contrato</label>
                    <select name="tipo_contrato" required>
                        <option value="Indefinido" style="color: black;">Indefinido</option>
                        <option value="Tiempo determinado" style="color: black;" selected>Tiempo determinado</option>
                        <option value="Obra determinada" style="color: black;">Obra determinada</option>
                        <option value="Pasantía" style="color: black;">Pasantía</option>
                        <option value="Suplencia" style="color: black;">Suplencia</option>
                    </select>
                </div>

                <div class="field">
                    <label>Fecha del Contrato</label>
                    <input type="date" name="fecha_contrato" required>
                </div>

                <div class="field">
                    <label>Lugar de Trabajo</label>
                    <input type="text" name="lugar_trabajo" placeholder="Ej: Zona Industrial, Edificio FUNDACITE, San Felipe" required>
                </div>

                <!-- DATOS DEL PRESIDENTE AUTO-COMPLETADOS -->
                <div class="field">
                    <label>Nombre del Presidente</label>
                    <input type="text" name="nombre_presidente" value="Miguel Ángel Solórzano Belizario" placeholder="Ej: Miguel Ángel Solórzano Belizario" required>
                </div>

                <div class="field">
                    <label>Cédula del Presidente</label>
                    <input type="text" name="cedula_presidente" value="V-19.817.987" placeholder="Ej: V-19.817.987" required>
                </div>

                <div class="field">
                    <label>Gaceta Oficial de Designación</label>
                    <input type="text" name="gaceta_designacion_presidente" value="N° 41.823" placeholder="Ej: N° 41.823" required>
                </div>

                <button type="submit" name="registrar_contrato" class="btn-guardar">Registrar Contrato</button>
            </form>
        </div>

        <!-- TABLA DE CONTRATOS -->
        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white;">Lista de Contratos</h2>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contratos)): ?>
                            <?php foreach ($contratos as $con): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($con['nombre_trabajador'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($con['tipo_contrato'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($con['fecha_contrato'] ?? ''); ?></td>
                                    <td class="acciones">
                                        <a class="btn-editar" href="ver_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>"><i class="bi bi-eye"></i>  Ver</a>
                                        <a class="btn-editar" href="editar_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>">
                                            <i class="bi bi-pencil-square"></i>  Editar
                                        </a>
                                        <a href="../controladores/ctrl_contrato.php?action=eliminar&id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>" 
                                           class="btn-eliminar" 
                                           style="text-decoration: none; display: inline-block;" 
                                           onclick="return confirm('¿Seguro que deseas eliminar este contrato?');">
                                            <i class="bi bi-trash"></i>  Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">No se encontraron contratos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

    <script src="/FUNDACITE/vistas/js/validar_contrato.js"></script>
    <script src="/FUNDACITE/vistas/js/ajax_contrato.js"></script>

   <!-- SCRIPT DE MODAL CON SWEETALERT2 AJUSTADO -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    let title = '';

    <?php if ($mensajeExito): ?>
        title = '<?php echo addslashes($mensajeExito); ?>';
    <?php elseif ($mensajeError): ?>
        title = '<?php echo addslashes($mensajeError); ?>';
    <?php elseif ($status === 'success'): ?>
        title = '¡Contrato registrado correctamente!';
    <?php elseif ($status === 'updated'): ?>
        title = 'Contrato actualizado correctamente.';
    <?php elseif ($status === 'deleted'): ?>
        title = '¡Contrato eliminado correctamente!';
    <?php elseif ($status === 'error'): ?>
        title = 'Hubo un error al procesar la solicitud.';
    <?php endif; ?>

    if (title !== '') {
        Swal.fire({
            title: title,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#007bff',
            width: '380px', /* Ancho compacto igual al de cargos */
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
/* Estilos para igualar el tamaño compacto de la vista de Cargos */
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