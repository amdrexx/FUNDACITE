<?php
session_start();
include_once "includes/guardian.php";
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
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

  
  <?php include "includes/layout.php"; ?>
   
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