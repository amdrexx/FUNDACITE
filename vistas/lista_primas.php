<?php
require_once '../conexion.php';
require_once '../modelos/clase_primas.php';

$prima = new Prima($conexion);

$prima = new Prima($conexion);
$primas = $prima->mostrarPrimas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listas de primas</title>
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
            <h2 style="text-align:center; color:white;">Lista Primas</h2>

            <div class="buscador-container">
                <input 
                    type="text" 
                    class="input-busqueda"
                   
                >
                <button class="btn-buscar">Buscar</button>
                <a href="/FUNDACITE/vistas/registrar_prima.php" class="btn-persona" style="text-decoration:none;">
                    + Agregar prima
                </a>
            </div>


            <table class="tabla">
                <thead>
                    <tr>
                        <th>Tipo prima</th>
                        <th>porcentaje</th>
                        <th>Fecha creaciòn</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
             <tbody>
<?php if (!empty($primas)): ?>

    <?php foreach ($primas as $p): ?>
        <tr>
             <td><?= htmlspecialchars($p['tipo_prima'] ?? '') ?></td>

<td><?= htmlspecialchars($p['porcentaje'] ?? '') ?>%</td>

<td><?= htmlspecialchars($p['fecha'] ?? '') ?></td>

<td><?= htmlspecialchars($p['estado'] ?? '') ?></td>

            <td class="acciones">

                <a class="btn-editar"
                   href="../controladores/ctrl_primas.php?accion=editar&id=<?= $p['id_prima'] ?>">
                    Editar
                </a>

                <form action="../controladores/ctrl_primas.php"
                      method="POST"
                      style="display:inline;"
                      onsubmit="return confirm('¿Seguro que deseas eliminar esta prima?');">

                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_prima" value="<?= $p['id_prima'] ?>">

                    <button type="submit" class="btn-eliminar">
                        Eliminar
                    </button>

                </form>

            </td>
        </tr>
    <?php endforeach; ?>

<?php else: ?>

    <tr>
        <td colspan="5" style="text-align:center;">
            No hay primas registradas.
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