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

<!-- ================= TOPBAR ================= -->
<div class="topbar">
    <img src="/FUNDACITE/vistas/img/logo.png" alt="Logo">
</div>

<!-- ================= SIDEBAR ================= -->
<div class="sidebar" id="sidebar" style="display: flex; flex-direction: column; justify-content: space-between; height: calc(102vh - 70px);">
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li>
            <a href="dashboard.html" class="submenu-link">
                <i class="bi bi-house-door-fill"></i> <b>INICIO</b>
            </a>
        </li>
        <li>
            <a href="lista_personas.html" class="submenu-link">
                <i class="bi bi-people-fill"></i> <b>PERSONAS</b>
            </a>
        </li>
        <li>
            <a href="lista_trabajadores.php" class="submenu-link">
                <i class="bi bi-person-workspace"></i> <b>TRABAJADORES</b>
            </a>
        </li>
        <li>
            <a href="lista_cargos.html" class="submenu-link">
                <i class="bi bi-briefcase-fill"></i> <b>CARGO</b>
            </a>
        </li>
        <li>
            <a href="lista_direcciones.html" class="submenu-link">
                <i class="bi bi-geo-alt-fill"></i> <b>DIRECCION</b>
            </a>
        </li>
        <li>
            <a href="lista_contratos.html" class="submenu-link">
                <i class="bi bi-file-earmark-text-fill"></i> <b>CONTRATOS</b>
            </a>
        </li>
        <li>
            <a href="lista_usuarios.html" class="submenu-link">
                <i class="bi bi-person-circle"></i> <b>USUARIO</b>
            </a>
        </li>
        <li>
            <a href="lista_solicitudes.html" class="submenu-link">
                <i class="bi bi-calendar2-check-fill"></i> <b>SOLICITUDES DE DIAS DE DISFRUTE</b>
            </a>
        </li>
    </ul>

    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
        <li>
            <a href="" class="submenu-link">
                <i class="bi bi-box-arrow-right"></i> <b>CERRAR SESIÓN</b>
            </a>
        </li>
    </ul>
</div>

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
                            <td><?php echo htmlspecialchars($fila['tipo_contrato'] ?? 'Indefinido'); ?></td>
                            <td><?php echo htmlspecialchars($fila['status'] ?? ''); ?></td>
                           <td class="acciones">
                        <a class="btn-editar" href="editar_trabajador.php?id=<?php echo urlencode($fila['id_trabajador'] ?? ''); ?>">
                            Editar
                        </a>

                        <form action="../controladores/ctrl_trabajador.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este trabajador?');">
                            <input type="hidden" name="id_trabajador" value="<?php echo htmlspecialchars($fila['id_trabajador'] ?? ''); ?>">
                            <button type="submit" name="eliminar_trabajador" class="btn-eliminar" style="border:none; cursor:pointer;">
                                Eliminar
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