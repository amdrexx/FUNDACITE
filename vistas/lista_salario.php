<?php
require_once '../conexion.php';
require_once '../modelos/clase_salario.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$salario = null;
$salarios = [];

// Intentar instanciar la clase del modelo de salario con varios nombres posibles
$possibleClasses = ['Salario', 'Clase_salario', 'ClaseSalario', 'clase_salario', 'salario'];
foreach ($possibleClasses as $className) {
    if (class_exists($className)) {
        $salario = new $className($conexion);
        break;
    }
}

if ($salario !== null) {
    if (method_exists($salario, 'mostrarSalarios')) {
        $salarios = $salario->mostrarSalarios();
    } else {
        $salarios = [];
        $errores[] = 'El modelo de salario no tiene el método mostrarSalarios.';
    }
} else {
    $errores[] = 'Clase de modelo de salario no encontrada. Verifique ../modelos/clase_salario.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista Salario</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
</head>
<body>

  
    <button class="menu-btn" id="menuBtn" onclick="toggleMenu()">
        <div></div>
        <div></div>
        <div></div>
    </button>

    <!-- ================= TOPBAR ================= -->
<div class="topbar">
    <img src="/FUNDACITE/vistas/img/logo.png" alt="Logo">
</div>

   <!-- ================= SIDEBAR ================= -->
<div class="sidebar" id="sidebar" style="display: flex; flex-direction: column; justify-content: space-between; height: calc(102vh - 70px);">
     <ul style="list-style: none; padding: 0; margin: 0;">
         <li>
            <a href="/FUNDACITE/vistas/dashboard.php" class="submenu-link">
                <i class="bi bi-house-door-fill"></i> <b>INICIO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_trabajadores.php" class="submenu-link">
                <i class="bi bi-person-workspace"></i> <b>TRABAJADORES</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_cargos.php" class="submenu-link">
                <i class="bi bi-briefcase-fill"></i> <b>CARGO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_direcciones.php" class="submenu-link">
                <i class="bi bi-geo-alt-fill"></i> <b>DIRECCION</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_contratos.php" class="submenu-link">
                <i class="bi bi-file-earmark-text-fill"></i> <b>CONTRATOS</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_usuarios.php" class="submenu-link">
                <i class="bi bi-person-circle"></i> <b>USUARIO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_salario.php" class="submenu-link">
                <i class="bi bi-coin"></i> <b>SALARIO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_primas.php" class="submenu-link">
                <i class="bi bi-wallet"></i> <b>PRIMA</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_dias_disfrute.php" class="submenu-link">
                <i class="bi bi-calendar2-check-fill"></i> <b>SOLICITUD DE DIAS DE DISFRUTE</b>
            </a>
        </li>
    </ul>
    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
        <li><a href="" class="submenu-link"><i class="bi bi-box-arrow-right"></i> <b>CERRAR SESIÓN</b></a></li>
    </ul>
</div>

    <div class="overlay"></div>

   
    <div class="main">
        <div class="glass tabla-container">
            <h2 style="text-align:center; color:white;">Lista salario</h2>

            <div class="buscador-container">
                <input 
                    type="text" 
                    class="input-busqueda"
                   
                >
                <button class="btn-buscar">Buscar</button>
                <a href="/FUNDACITE/vistas/registrar_salario.php" class="btn-persona" style="text-decoration:none;">
                    + Agregar salario
                </a>
            </div>


            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cedula</th>
                        <th>Fecha de ingreso</th>
                        <th>cargo</th>
                        <th>contrato</th>
                        <th>monto</th>
                        <th>Prima</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
              <tbody>
    <?php if (!empty($salarios)): ?>

    <?php foreach ($salarios as $s): ?>

        <tr>
            <td><?= htmlspecialchars($s['cedula']) ?></td>

            <td><?= htmlspecialchars($s['fecha']) ?></td>

            <td><?= htmlspecialchars($s['nombre_cargo']) ?></td>

             <td><?= htmlspecialchars($s['contrato'] ?? '') ?></td>

            <td><?= htmlspecialchars($s['monto']) ?></td>

           <td><?= htmlspecialchars($s['primas'] ?? 'SIN PRIMAS') ?></td>

            <td class="acciones">

                <a class="btn-editar"
                href="../controladores/ctrl_salario.php?accion=editar&id=<?= $s['id_salario'] ?>">
                Editar
             </a>

                <form action="../controladores/ctrl_salario.php" method="POST"
                 style="display:inline;"
                 onsubmit="return confirm('¿Seguro que deseas eliminar este salario?');">

                <input type="hidden" name="accion" value="eliminar">
             <input type="hidden" name="id_salario" value="<?= $s['id_salario'] ?>">

             <button type="submit" class="btn-eliminar">
             Eliminar
             </button>
             </form>

            </td>
        </tr>

    <?php endforeach; ?>

<?php else: ?>

    <tr>
        <td colspan="12" style="text-align:center;">
            No hay salarios registrados.
        </td>
    </tr>

<?php endif; ?>
</tbody>
            </table>
        </div>
    </div>

    
    <script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>
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