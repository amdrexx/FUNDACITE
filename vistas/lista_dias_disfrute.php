<?php
session_start();
include_once "includes/guardian.php";
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
require_once '../modelos/clase_dias_disfrute.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$buscar = trim($_GET['buscar'] ?? '');

$diasDisfruteModelo = new DiasDisfrute($conexion);

// mostrarDiasDisfrute() ya filtra por SQL (trabajador o código del registro)
// y devuelve un array asociativo, no un resultado mysqli.
$registros = $diasDisfruteModelo->mostrarDiasDisfrute($buscar);
registrarBitacora($conexion, 'Días de Disfrute', 'Consultar', $buscar !== '' ? "Consultó el listado de días de disfrute (búsqueda: \"$buscar\")." : 'Consultó el listado de días de disfrute.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Días de Disfrute</title>
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
        <h2 style="text-align:center; color:white;">Lista de Días de Disfrute</h2>

        <form method="GET" action="lista_dias_disfrute.php" class="buscador-container" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input
                type="text"
                name="buscar"
                class="input-busqueda"
                placeholder="Buscar por trabajador o código..."
                value="<?php echo htmlspecialchars($buscar); ?>"
            >
            <button type="submit" class="btn-buscar">Buscar</button>

            <a href="registrar_dias_disfrute.php" class="btn-persona" style="text-decoration:none;">
                + Nuevo Registro
            </a>

            <?php if (esAdministradorODirector()): ?>
            <a href="aprobacion_dias_disfrute.php" class="btn-pdf" style="text-decoration:none;">
                <i class="bi bi-check2-square"></i>
                Aprobaciones/Desaprobaciones
            </a>
            <a
                href="/FUNDACITE/pdf/reporte_dias_disfrute.php<?= $buscar !== '' ? '?buscar=' . urlencode($buscar) : ''; ?>"
                class="btn-pdf"
                target="_blank"
                style="text-decoration:none; margin-left:auto;"
            >
                <i class="bi bi-file-earmark-pdf"></i>
                Exportar PDF
            </a>
            <?php endif; ?>
        </form>

        <table class="tabla">
            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Código</th>
                    <th>Motivo</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registros)): ?>
                    <?php foreach ($registros as $fila): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['trabajador'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['codigo_disfrute'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['motivo'] ?? ''); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($fila['observaciones'] ?? '', ENT_QUOTES, 'UTF-8')); ?></td>
                            <td class="acciones">

                                <a class="btn-ver" href="ver_dias_disfrute.php?id=<?= urlencode($fila['id_dis']) ?>">
                                    <i class="bi bi-eye"></i> Ver
                                </a>

                                <?php if (($fila['estado'] ?? 'Pendiente') === 'Aprobado'): ?>
                                    <a class="btn-editar" target="_blank" href="/FUNDACITE/pdf/dias_disfrute.php?id=<?= urlencode($fila['id_dis']) ?>&print=1">
                                        <i class="bi bi-printer"></i> Imprimir
                                    </a>
                                <?php endif; ?>

                                <?php if (esAdministradorODirector()): ?>
                                <form action="../controladores/ctrl_dias_disfrute.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este registro?');">
                                    <input type="hidden" name="id_dis" value="<?php echo htmlspecialchars($fila['id_dis'] ?? ''); ?>">
                                    <button type="submit" name="eliminar_disfrute" class="btn-eliminar" style="border:none; cursor:pointer;">
                                        <i class="bi bi-trash"></i>
                                        Eliminar
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No se encontraron registros.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Alertas de errores/éxito -->
<?php include "includes/alerta.php"; ?>

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
