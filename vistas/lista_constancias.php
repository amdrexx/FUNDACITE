<?php
session_start();
include_once "includes/guardian.php";

$errores = $_SESSION['error_edicion'] ?? [];
$exito_edicion = $_SESSION['exito_edicion'] ?? '';
unset($_SESSION['error_edicion'], $_SESSION['exito_edicion']);

require_once '../conexion.php';
require_once '../modelos/clase_constancia.php';

$buscar = trim($_GET['buscar'] ?? '');
$constanciaObj = new clase_constancia($conexion);
$constancias = $constanciaObj->listarConstancias($buscar);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Constancias</title>
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
        <h2 style="text-align:center; color:white;">Lista de Constancias de Trabajo</h2>

        <form method="GET" action="lista_constancias.php" class="buscador-container" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="text" name="buscar" class="input-busqueda"
                   placeholder="Buscar por trabajador o cedula..."
                   value="<?= htmlspecialchars($buscar) ?>">
            <button type="submit" class="btn-buscar">Buscar</button>
            <a href="registrar_constancia.php" class="btn-persona" style="text-decoration:none;">
                + Nueva Constancia
            </a>
        </form>

        <table class="tabla">
            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Cedula</th>
                    <th>Cargo</th>
                    <th>Tipo Personal</th>
                    <th>Director</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($constancias)): ?>
                    <?php foreach ($constancias as $fila): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['trabajador'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['cedula'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['nombre_cargo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['tipo_personal'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['nombre_director_departamento'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['fecha_constancia'] ?? '') ?></td>
                            <td class="acciones">
                                <a class="btn-ver" href="ver_constancia.php?id=<?= urlencode($fila['id_constancia']) ?>">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a class="btn-editar" href="ver_constancia.php?id=<?= urlencode($fila['id_constancia']) ?>&print=1">
                                    <i class="bi bi-printer"></i> Imprimir
                                </a>
                                <?php if (esAdministradorODirector()): ?>
                                <form action="../controladores/ctrl_constancia.php" method="POST" style="display:inline;"
                                      onsubmit="return confirm('Seguro que deseas eliminar esta constancia?');">
                                    <input type="hidden" name="id_constancia" value="<?= htmlspecialchars($fila['id_constancia']) ?>">
                                    <button type="submit" name="eliminar_constancia" class="btn-eliminar" style="border:none; cursor:pointer;">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No se encontraron constancias registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="document.getElementById('customAlert').classList.add('hidden')">Cerrar</button>
    </div>
</div>

<?php if (!empty($errores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('alertMessage').textContent = <?= json_encode(implode("\n", $errores)) ?>;
        document.getElementById('customAlert').classList.remove('hidden');
    });
</script>
<?php endif; ?>

<?php if (!empty($exito_edicion)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('alertMessage').textContent = <?= json_encode($exito_edicion) ?>;
        document.getElementById('customAlert').classList.remove('hidden');
    });
</script>
<?php endif; ?>

</body>
</html>
