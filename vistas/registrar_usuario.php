<?php
include "includes/guardian.php";
session_start();
require_once __DIR__ . '/includes/roles.php';
requireAdministrador();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errores = $_SESSION['error_registro'] ?? [];
$old = $_SESSION['old_input'] ?? [];
$exito_registro = $_SESSION['exito_registro'] ?? '';

unset(
    $_SESSION['error_registro'],
    $_SESSION['old_input'],
    $_SESSION['exito_registro']
);

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';

$trabajadorObj = new Trabajador($conexion);
$trabajadores = $trabajadorObj->listarTrabajadoresSinUsuario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuarios</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>


<?php include "includes/layout.php"; ?>

<!-- FORMULARIO -->
<div class="main">
    <form class="form-card" id="formUsuarios" method="POST" action="../controladores/ctrl_usuario.php">
        <div class="form-grid">
            <h2 style="text-align: center; margin-bottom: 10px;">Registro de Usuario</h2>

            <!-- Asignar Trabajador -->
            <div class="field">
                <label>Asignar a Trabajador</label>
                <select name="id_trabajador" id="id_trabajador">
                    <option value="">Seleccione un trabajador...</option>
                    <?php foreach ($trabajadores as $t): ?>
                        <option value="<?php echo $t['id_trabajador']; ?>"
                            <?php echo (isset($old['id_trabajador']) && $old['id_trabajador'] == $t['id_trabajador']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['cedula'] . " - " . $t['nombres'] . " " . $t['apellidos']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Nombre de Usuario -->
            <div class="field">
                <label>Nombre de Usuario</label>
                <input type="text" name="nombre" id="nombre" placeholder="Ej: pedro.perez" 
                       value="<?php echo htmlspecialchars($old['nombre'] ?? ''); ?>">
            </div>

            <!-- Contraseña -->
            <div class="field">
                <label>Contraseña</label>
                <input type="password" name="contrasena" id="contrasena" placeholder="Ingrese la contraseña de acceso">
            </div>

            <!-- Confirmar Contraseña -->
            <div class="field">
                <label>Confirmar Contraseña</label>
                <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" placeholder="Repita la contraseña">
            </div>

            <!-- Tipo de Usuario -->
            <div class="field">
                <label>Tipo de Usuario</label>
                <select name="tipo_usuario" id="tipo_usuario">
                    <option value="">Seleccione un rol...</option>
                    <option value="Administrador" <?php echo (isset($old['tipo_usuario']) && $old['tipo_usuario'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="Director" <?php echo (isset($old['tipo_usuario']) && $old['tipo_usuario'] == 'Director') ? 'selected' : ''; ?>>Director</option>
                    <option value="Analista" <?php echo (isset($old['tipo_usuario']) && $old['tipo_usuario'] == 'Analista') ? 'selected' : ''; ?>>Analista</option>
                    <option value="Usuario Estándar" <?php echo (isset($old['tipo_usuario']) && $old['tipo_usuario'] == 'Usuario Estándar') ? 'selected' : ''; ?>>Usuario Estándar</option>
                </select>
            </div>

            <div class="form-actions full-width">
                <button type="submit" name="registrar_usuario" class="btn-guardar">Registrar Usuario</button>
            </div>
        </div>
    </form>
</div>

<script>
</script>




<script src="/FUNDACITE/vistas/js/valid_usuarios.js"></script>
</body>
</html>