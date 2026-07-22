<?php
include_once "includes/guardian.php";
session_start();
requireAdministrador();
require_once '../conexion.php';
require_once '../modelos/clase_usuario.php';

$usuarioObj = new Usuario($conexion);
$buscar = trim($_GET['buscar'] ?? '');
$usuarios = $usuarioObj->listar($buscar);

$errores = $_SESSION['error_registro'] ?? [];
$exito = $_SESSION['exito_registro'] ?? '';
unset($_SESSION['error_registro'], $_SESSION['exito_registro']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Lista de Usuarios</title>
<link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
<link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
<div class="glass tabla-container">
<h2 style="text-align:center;color:white;">Lista de Usuarios</h2>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;">
<input type="text" name="buscar" class="input-busqueda"
placeholder="Buscar usuario, cédula o trabajador"
value="<?php echo htmlspecialchars($buscar); ?>">
<button class="btn-buscar">Buscar</button>
<a href="registrar_usuario.php" class="btn-persona" style="text-decoration:none;">+ Agregar Usuario</a>
</form>



<table class="tabla">
<thead>
<tr>

<th>Trabajador</th>
<th>Usuario</th>
<th>Tipo</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if(!empty($usuarios)): ?>
<?php foreach($usuarios as $u): ?>
<tr>
<td><?php echo htmlspecialchars($u['cedula']." - ".$u['nombres']." ".$u['apellidos']); ?></td>
<td><?php echo htmlspecialchars($u['nombre']); ?></td>
<td><?php echo htmlspecialchars($u['tipo_usuario']); ?></td>
<td><?php echo htmlspecialchars($u['status']); ?></td>
<td>
<a class="btn-editar" href="editar_usuario.php?id=<?php echo $u['id_usuario']; ?>">Editar
    <i class="bi bi-pencil-square"></i>
</a>


<?php if($u['status']=="Activo"): ?>
<a class="btn-eliminar"
onclick="return confirm('¿Desea inactivar este usuario?')"
href="../controladores/ctrl_usuario.php?eliminar=<?php echo $u['id_usuario']; ?>">
Inactivar
</a>
<?php else: ?>
<a class="btn-ver"
onclick="return confirm('¿Desea activar este usuario?')"
href="../controladores/ctrl_usuario.php?activar=<?php echo $u['id_usuario']; ?>">
    <i class="bi bi-person-check"></i>
Activar
</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6" style="text-align:center;">No existen usuarios registrados.</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>
</div>
</body>
</html>
