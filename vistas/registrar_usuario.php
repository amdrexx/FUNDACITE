<?php
session_start();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_usuario.php';

$usuarioModel = new Usuario($conexion);

$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

$old = $_SESSION['old_usuario_form'] ?? [];
unset($_SESSION['old_usuario_form']);

$usuario_editar = null;
if (isset($_GET['id']) && $_GET['id'] !== '') {
    $usuario_editar = $usuarioModel->obtenerUsuarioPorId((int)$_GET['id']);
}

$trabajadores_db = $usuarioModel->obtenerTrabajadores();

$id_usuario_form    = $old['id_usuario'] ?? ($usuario_editar['id_usuario'] ?? '');
$id_trabajador_form = $old['trabajadorAsociado'] ?? ($usuario_editar['id_trabajador'] ?? '');
$nombre_form        = $old['usuario'] ?? ($usuario_editar['nombre'] ?? '');
$tipo_form          = $old['tipoUsuario'] ?? ($usuario_editar['tipo_usuario'] ?? '');
$status_form        = $old['status'] ?? ($usuario_editar['status'] ?? 'Activo');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuarios</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
   
</head>
<body>

<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<div class="topbar">
    <img src="logo.png" alt="Logo">
</div>

<button class="menu-btn" id="menuBtn" onclick="toggleMenu()">
    <div></div><div></div><div></div>
</button>

<div class="sidebar" id="sidebar" style="display: flex; flex-direction: column; justify-content: space-between; height: calc(102vh - 70px);">
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li><a href="dashboard.html" class="submenu-link"><i class="bi bi-house-door-fill"></i> <b>INICIO</b></a></li>
        <li><a href="lista_personas.html" class="submenu-link"><i class="bi bi-people-fill"></i> <b>PERSONAS</b></a></li>
        <li><a href="lista_trabajadores.html" class="submenu-link"><i class="bi bi-person-workspace"></i> <b>TRABAJADORES</b></a></li>
        <li><a href="lista_cargos.html" class="submenu-link"><i class="bi bi-briefcase-fill"></i> <b>CARGO</b></a></li>
        <li><a href="lista_direcciones.html" class="submenu-link"><i class="bi bi-geo-alt-fill"></i> <b>DIRECCION</b></a></li>
        <li><a href="lista_contratos.html" class="submenu-link"><i class="bi bi-file-earmark-text-fill"></i> <b>CONTRATOS</b></a></li>
        <li><a href="lista_usuarios.html" class="submenu-link"><i class="bi bi-person-circle"></i> <b>USUARIO</b></a></li>
        <li><a href="lista_solicitudes.html" class="submenu-link"><i class="bi bi-calendar2-check-fill"></i> <b>SOLICITUDES DE DIAS DE DISFRUTE</b></a></li>
    </ul>
    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
        <li><a href="" class="submenu-link"><i class="bi bi-box-arrow-right"></i> <b>CERRAR SESIÓN</b></a></li>
    </ul>
</div>

<div class="main">
    <form class="form-card" id="formUsuarios" method="POST" action="../controladores/ctrl_usuario.php">
        <div class="form-grid">

            <h2 class="title-form"><?php echo $usuario_editar ? 'Modificar Usuario' : 'Registro de Usuarios'; ?></h2>

            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($id_usuario_form, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="field">
                <label>Trabajador Asociado</label>
                <select id="trabajadorAsociado" name="trabajadorAsociado" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($trabajadores_db as $t): ?>
                        <option value="<?php echo $t['id_trabajador']; ?>"
                            <?php echo ((string)$id_trabajador_form === (string)$t['id_trabajador']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ingrese nombre de usuario" required
                       value="<?php echo htmlspecialchars($nombre_form, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="field">
                <label>Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" placeholder="Ingrese contraseña"
                       <?php echo $usuario_editar ? '' : 'required'; ?>>
                <?php if ($usuario_editar): ?>
                    <small style="color: gray; margin-top: 4px;">* Dejar en blanco para mantener la contraseña actual</small>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>Tipo de Usuario</label>
                <select id="tipoUsuario" name="tipoUsuario" required>
                    <option value="">Seleccione...</option>
                    <option value="Administrador" <?php echo ($tipo_form === 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="Supervisor" <?php echo ($tipo_form === 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                    <option value="Usuario" <?php echo ($tipo_form === 'Usuario') ? 'selected' : ''; ?>>Usuario</option>
                    <option value="Invitado" <?php echo ($tipo_form === 'Invitado') ? 'selected' : ''; ?>>Invitado</option>
                </select>
            </div>

            <div class="field">
                <label>Estado</label>
                <select id="status" name="status" required>
                    <option value="Activo" <?php echo ($status_form === 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Inactivo" <?php echo ($status_form === 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    <option value="Bloqueado" <?php echo ($status_form === 'Bloqueado') ? 'selected' : ''; ?>>Bloqueado</option>
                </select>
            </div>

            
                <button type="submit" class="btn-guardar full-width-btn">
                    <?php echo $usuario_editar ? 'Actualizar' : 'Registrar'; ?>
                </button>

                <?php if ($usuario_editar): ?>
                    <a href="registro_usuario.php" class="btn-guardar full-width-btn" style="background:#6c757d; text-decoration:none;">
                        Cancelar Edición
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </form>
</div>

<script src="/FUNDACITE/vistas/js/valid_usuarios.js"></script>

<?php if (!empty($mensaje_error)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($mensaje_error); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

<?php if (!empty($mensaje_exito)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($mensaje_exito); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

</body>
</html>