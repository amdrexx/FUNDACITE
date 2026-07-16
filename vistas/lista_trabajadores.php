<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errores = $_SESSION['error_edicion'] ?? [];
$exito_edicion = $_SESSION['exito_edicion'] ?? '';

unset(
    $_SESSION['error_edicion'],
    $_SESSION['exito_edicion']
);

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';

$buscar = trim($_GET['buscar'] ?? '');

$trabajador = new Trabajador($conexion);
$trabajadores = $trabajador->listarTrabajadores($buscar);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Trabajadores</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>

<body>
    
<?php include "includes/layout.php"; ?>

<!-- ================= CONTENIDO ================= -->
<div class="main">
    <div class="glass tabla-container">
        <h2 style="text-align:center; color:white;">Lista de Trabajadores</h2>

      <form method="GET" action="lista_trabajadores.php" class="buscador-container" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <input
        type="text"
        name="buscar"
        class="input-busqueda"
        placeholder="Buscar por cédula, nombre, apellido o cargo..."
        value="<?php echo htmlspecialchars($buscar); ?>"
    >
    <button type="submit" class="btn-buscar">Buscar</button>

    <a href="registrar_trabajadores.php" class="btn-persona" style="text-decoration:none;">
        + Agregar Trabajador
    </a>
</form>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Persona (Cédula)</th>
                    <th>Cargo</th>
                    <th>Fecha de Ingreso</th>
                    <th>Tipo de Contratación</th>
                    <th>Estatus Laboral</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($trabajadores)): ?>
                    <?php foreach ($trabajadores as $fila): ?>
                        <tr>
                            <td>
                                <?php
                                echo htmlspecialchars($fila['cedula'] ?? '') . ' - ' .
                                     htmlspecialchars(trim(($fila['nombres'] ?? '') . ' ' . ($fila['apellidos'] ?? '')));
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($fila['nombre_cargo'] ?? 'Sin cargo'); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_ingreso'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['tipo_contrato'] ?? 'Sin contrato'); ?></td>
                            <td><?php echo htmlspecialchars($fila['status'] ?? ''); ?></td>
                           <td class="acciones">
                        <a class="btn-ver" href="ver_trabajador.php?id=<?php echo urlencode($fila['id_trabajador'] ?? ''); ?>">
                            Ver
                        </a>
                           <a class="btn-editar" href="editar_trabajador.php?id=<?php echo urlencode($fila['id_trabajador'] ?? ''); ?>">
                             <i class="bi bi-pencil-square"></i>
                            Editar
                        </a>

                        <form action="../controladores/ctrl_trabajador.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este trabajador?');">
                            <input type="hidden" name="id_trabajador" value="<?php echo htmlspecialchars($fila['id_trabajador'] ?? ''); ?>">
                            <button type="submit" name="eliminar_trabajador" class="btn-eliminar" style="border:none; cursor:pointer;">
                                <i class="bi bi-trash"></i>
                                Inactivar
                            </button>
                        </form>
                         
                    </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No se encontraron trabajadores registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="boton_desplegable.js"></script>
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

<?php if (!empty($exito_edicion)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($exito_edicion); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>
</body>
</html>