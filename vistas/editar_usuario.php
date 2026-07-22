<?php
include "includes/guardian.php";
session_start();
require_once __DIR__ . '/includes/roles.php';
requireAdministrador();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once '../modelos/clase_usuario.php';
require_once '../modelos/clase_trabajador.php';

$id_usuario = intval($_GET['id'] ?? 0);

if ($id_usuario <= 0) {
    header("Location: lista_usuarios.php");
    exit;
}

$usuarioObj = new Usuario($conexion);
$trabajadorObj = new Trabajador($conexion);

$usuarioActual = $usuarioObj->buscarPorId($id_usuario);

if (!$usuarioActual) {
    $_SESSION['error_registro'] = ["El usuario solicitado no existe."];
    header("Location: lista_usuarios.php");
    exit;
}

$stmtTrabajador = $conexion->prepare("SELECT * FROM TRABAJADOR WHERE id_trabajador = ?");
$stmtTrabajador->bind_param("i", $usuarioActual['id_trabajador']);
$stmtTrabajador->execute();
$trabajadorAsignado = $stmtTrabajador->get_result()->fetch_assoc();

$errores = $_SESSION['error_registro'] ?? [];
$exito = $_SESSION['exito_registro'] ?? '';

unset($_SESSION['error_registro'], $_SESSION['exito_registro']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<?php include "includes/layout.php"; ?>

<div class="main">
    <form class="form-card" id="formEditarUsuario" method="POST" action="../controladores/ctrl_usuario.php">
        
        <input type="hidden" name="id_usuario" value="<?php echo $usuarioActual['id_usuario']; ?>">

        <div class="form-grid">
            <h2 style="text-align: center; margin-bottom: 10px;">Modificar Usuario</h2>

            <!-- Trabajador Asignado (no editable) -->
            <div class="field">
                <label>Trabajador Asignado</label>
                <input type="text" readonly disabled
                       value="<?php echo $trabajadorAsignado ? htmlspecialchars($trabajadorAsignado['cedula'] . " - " . $trabajadorAsignado['nombres'] . " " . $trabajadorAsignado['apellidos']) : 'No disponible'; ?>">
                <input type="hidden" name="id_trabajador" id="id_trabajador" value="<?php echo $usuarioActual['id_trabajador']; ?>">
            </div>

            <!-- Nombre de Usuario -->
            <div class="field">
                <label>Nombre de Usuario</label>
                <input type="text" name="nombre" id="nombre" 
                       value="<?php echo htmlspecialchars($usuarioActual['nombre']); ?>" required>
            </div>

            <!-- Nueva Contraseña -->
            <div class="field">
                <label>Nueva Contraseña <small style="color: #bbb;">(Dejar en blanco para conservar)</small></label>
                <input type="password" name="contrasena" id="contrasena" placeholder="Escriba para cambiar la clave">
            </div>

            <!-- Confirmar Nueva Contraseña -->
            <div class="field">
                <label>Confirmar Nueva Contraseña</label>
                <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" placeholder="Repita la nueva clave">
            </div>

            <!-- Tipo de Usuario -->
            <div class="field">
                <label>Tipo de Usuario</label>
                <select name="tipo_usuario" id="tipo_usuario">
                    <option value="Administrador" <?php echo ($usuarioActual['tipo_usuario'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="Director" <?php echo ($usuarioActual['tipo_usuario'] == 'Director') ? 'selected' : ''; ?>>Director</option>
                    <option value="Analista" <?php echo ($usuarioActual['tipo_usuario'] == 'Analista') ? 'selected' : ''; ?>>Analista</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" name="editar_usuario" class="btn-guardar full-width">Guardar Cambios</button>
                <a href="lista_usuarios.php" class="btn-persona full-width" style="text-align:center; text-decoration:none;">Cancelar</a>
            </div>
        </div>
    </form>
</div>

<script>
    function closeAlert() {
        document.getElementById('customAlert').classList.add('hidden');
    }
</script>

<?php if (!empty($errores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode(implode("\n", $errores)); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

</body>
</html>