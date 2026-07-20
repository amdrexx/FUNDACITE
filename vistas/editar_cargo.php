<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../conexion.php");
require_once("../modelos/clase_cargo.php");

// Validamos que venga el ID por la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: registrar_cargo.php?status=error");
    exit();
}

$id_cargo = intval($_GET['id']);
$cargoModelo = new clase_cargo($conexion);
$cargoActual = $cargoModelo->obtenerCargoPorId($id_cargo);

// Si el ID no existe en la BD, redirigimos
if (!$cargoActual) {
    header("Location: registrar_cargo.php?status=error");
    exit();
}

// Mensaje de alerta si el controlador redirige con algún estatus de error
$mensaje_alerta = '';
if (isset($_GET['status']) && $_GET['status'] == 'duplicate') {
    $mensaje_alerta = "⚠️ El nombre del cargo ingresado ya pertenece a otro registro.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cargo</title>
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

<!-- Alerta personalizada idéntica a trabajadores y registrar cargo -->
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
            <form class="form-card" id="formEditarCargo" action="../controladores/ctrl_cargo.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <center><h2>Editar Cargo</h2></center>

                <!-- Campo oculto para pasar el ID al controlador -->
                <input type="hidden" name="id_cargo" value="<?php echo htmlspecialchars($cargoActual['id_cargo']); ?>">

                <div class="field">
                    <label>Nombre del Cargo</label>
                    <input type="text" name="nombre_cargo" id="nombreCargo" 
                           value="<?php echo htmlspecialchars($cargoActual['nombre_cargo']); ?>" 
                           placeholder="Ingrese el nuevo nombre del cargo" required>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="actualizar_cargo" class="btn-guardar" style="flex: 1;">Actualizar Cargo</button>
                    <a href="registrar_cargo.php" class="btn-eliminar" style="text-decoration: none; text-align: center; padding: 10px; border-radius: 5px; flex: 1;">Cancelar</a>
                </div>
            </form>
        </div>

    </div> 
</div>

<!-- Disparador de la alerta en caso de duplicados -->
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