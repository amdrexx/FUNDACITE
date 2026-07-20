<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Manejo de mensajes (si decides usarlos luego como en trabajadores)
$errores = $_SESSION['error_edicion'] ?? [];
$exito_edicion = $_SESSION['exito_edicion'] ?? '';

unset(
    $_SESSION['error_edicion'],
    $_SESSION['exito_edicion']
);

// Incluimos el controlador para obtener los cargos reales de la base de datos
require_once("../controladores/ctrl_cargo.php");

// Llamamos al método del controlador para traer los registros reales
$cargos = $controladorCargo->mostrarCargos();

// Definir mensaje según el estado recibido por URL si aplica
$mensaje_alerta = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success':
            $mensaje_alerta = "✅ ¡Cargo registrado exitosamente!";
            break;
        case 'updated':
            $mensaje_alerta = "🔄 ¡Cargo actualizado correctamente!";
            break;
        case 'deleted':
            $mensaje_alerta = "🗑️ ¡Cargo eliminado correctamente!";
            break;
        case 'duplicate':
            $mensaje_alerta = "⚠️ El cargo ingresado ya se encuentra registrado.";
            break;
        case 'error':
            $mensaje_alerta = "⚠️ Hubo un error al procesar la solicitud. Intente nuevamente.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Cargos</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <script>
        function closeAlert() {
            var alertDiv = document.getElementById('customAlert');
            if (alertDiv) {
                alertDiv.classList.add('hidden');
            }
        }
    </script>
</head>

<body>

<!-- Alerta personalizada idéntica a registrar_trabajador -->
<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">

    <div style="max-width: 600px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formCargos" action="../controladores/ctrl_cargo.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <center><h2>Registrar Nuevo Cargo</h2></center>

                <div class="field">
                    <label>Nombre del Cargo</label>
                    <input type="text" name="nombre_cargo" id="nombreCargo" placeholder="Ingrese el nombre del cargo" required>
                </div>

                <button type="submit" name="registrar_cargo" class="btn-guardar">Registrar Cargo</button>
            </form>
        </div>

        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white;">Lista de Cargos Registrados</h2>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Nombre del Cargo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cargos)): ?>
                            <?php foreach ($cargos as $cargo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cargo['nombre_cargo'] ?? ''); ?></td>
                                    <td class="acciones">
                                        <a class="btn-editar" href="editar_cargo.php?id=<?php echo urlencode($cargo['id_cargo'] ?? ''); ?>">
                                            <i class="bi bi-pencil-square"></i>
                                            Editar
                                        </a>

                                        <a href="../controladores/ctrl_cargo.php?action=eliminar&id=<?php echo urlencode($cargo['id_cargo'] ?? ''); ?>" 
                                           class="btn-eliminar" 
                                           style="text-decoration: none; display: inline-block;" 
                                           onclick="return confirm('¿Seguro que deseas eliminar este cargo?');">
                                            <i class="bi bi-trash"></i>
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align:center;">No se encontraron cargos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> 
</div>

<!-- Disparador de la alerta si existe mensaje -->
<?php if (!empty($mensaje_alerta)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($mensaje_alerta); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

</body>
</html>