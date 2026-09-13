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
    <title>Contratos</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>

<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">

    <div style="max-width: 700px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <!-- FORMULARIO DE REGISTRO -->
        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formContratos" action="../controladores/ctrl_contrato.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                
                <!-- Campo oculto para indicar la acción al controlador -->
                <input type="hidden" name="accion" value="guardar">

                <center><h2>Nuevo Contrato</h2></center>

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

                <!-- CAMPO CARGO DEL TRABAJADOR -->
                <div class="field">
                    <label>Cargo Registrado</label>
                    <input type="text" id="cargo_trabajador" name="cargo_trabajador" placeholder="Cargo asignado al trabajador..." readonly style="background-color: rgba(255, 255, 255, 0.1); cursor: not-allowed;">
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
                    <label>Fecha de Inicio del Contrato</label>
                    <input type="date" name="fecha_contrato" required>
                </div>

                <!-- CAMPO: FECHA FIN -->
                <div class="field">
                    <label>Fecha de Finalización</label>
                    <input type="date" name="fecha_fin" id="fecha_fin">
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

        <!-- TABLA DE CONTRATOS CON COLUMNA DE CARGO -->
        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white;">Lista de Contratos</h2>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Cargo</th>
                            <th>Tipo</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contratos)): ?>
                            <?php foreach ($contratos as $con): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($con['nombre_trabajador'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($con['nombre_cargo'] ?? $con['cargo'] ?? 'Sin asignar'); ?></td>
                                    <td><?php echo htmlspecialchars($con['tipo_contrato'] ?? ''); ?></td>
                                    <td><?php echo !empty($con['fecha_contrato']) ? date("d/m/Y", strtotime($con['fecha_contrato'])) : ''; ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($con['fecha_fin']) && $con['fecha_fin'] !== '0000-00-00') {
                                            echo date("d/m/Y", strtotime($con['fecha_fin']));
                                        } else {
                                            echo '<span style="opacity: 0.8; font-style: italic;">Indefinido</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="acciones">
                                        <a class="btn-editar" href="ver_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>"><i class="bi bi-eye"></i> Ver</a>
                                        <a class="btn-editar" href="editar_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                        <a href="../controladores/ctrl_contrato.php?accion=eliminar&id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>" 
                                           class="btn-eliminar" 
                                           style="text-decoration: none; display: inline-block;" 
                                           onclick="return confirm('¿Seguro que deseas eliminar este contrato?');">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No se encontraron contratos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- MODAL DE ALERTA PERSONALIZADO -->
<div id="customAlert" class="custom-alert-overlay" style="display: none;">
    <div class="custom-alert-box">
        <p id="customAlertText"></p>
        <button type="button" class="btn-modal-compacto" onclick="cerrarCustomAlert()">Aceptar</button>
    </div>
</div>

<!-- Carga de los scripts JS -->
<script src="/FUNDACITE/vistas/js/valid_contrato.js"></script>
<script src="/FUNDACITE/vistas/js/ajax_contrato.js"></script>

<!-- SCRIPT DE MANEJO DE ALERTA -->
<script>
function mostrarCustomAlert(mensaje) {
    document.getElementById('customAlertText').innerHTML = mensaje;
    document.getElementById('customAlert').style.display = 'flex';
}

function cerrarCustomAlert() {
    document.getElementById('customAlert').style.display = 'none';
    window.history.replaceState({}, document.title, window.location.pathname);
}

document.addEventListener("DOMContentLoaded", function() {
    let mensaje = '';

    <?php if ($mensajeExito): ?>
        mensaje = '<?php echo addslashes($mensajeExito); ?>';
    <?php elseif ($mensajeError): ?>
        mensaje = '<?php echo addslashes($mensajeError); ?>';
    <?php elseif ($status === 'success'): ?>
        mensaje = '¡Contrato registrado correctamente!';
    <?php elseif ($status === 'updated'): ?>
        mensaje = 'Contrato actualizado correctamente.';
    <?php elseif ($status === 'deleted'): ?>
        mensaje = '¡Contrato eliminado correctamente!';
    <?php elseif ($status === 'error'): ?>
        mensaje = 'Hubo un error al procesar la solicitud.';
    <?php endif; ?>

    if (mensaje !== '') {
        mostrarCustomAlert(mensaje);
    }
});
</script>

<style>
/* Estilos para el Modal de Alerta Personalizado (#customAlert) */
.custom-alert-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.custom-alert-box {
    background: #ffffff;
    padding: 20px 25px;
    border-radius: 12px;
    max-width: 380px;
    width: 90%;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    font-family: inherit;
}

.custom-alert-box p {
    font-size: 15px;
    font-weight: normal;
    color: #444;
    margin: 10px 0 20px 0;
    line-height: 1.4;
}

.btn-modal-compacto {
    font-size: 13px;
    padding: 6px 18px;
    border-radius: 6px;
    background-color: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn-modal-compacto:hover {
    background-color: #0056b3;
}
</style>

</body>
</html>