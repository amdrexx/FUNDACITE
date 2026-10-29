<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

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
$estadoFiltro = trim($_GET['estado'] ?? '');
if (!in_array($estadoFiltro, ['Pendiente', 'Aprobado', 'Desaprobado'], true)) {
    $estadoFiltro = '';
}

// Query string para volver al mismo filtro/búsqueda tras aprobar o desaprobar.
$queryActual = http_build_query(array_filter([
    'buscar' => $buscar,
    'estado' => $estadoFiltro,
]));

$diasDisfruteModelo = new DiasDisfrute($conexion);

// mostrarDiasDisfrute() ya filtra por SQL (trabajador/código y estado)
// y devuelve un array asociativo, no un resultado mysqli.
$registros = $diasDisfruteModelo->mostrarDiasDisfrute($buscar, $estadoFiltro);

$detalleConsulta = [];
if ($buscar !== '') {
    $detalleConsulta[] = "búsqueda: \"$buscar\"";
}
if ($estadoFiltro !== '') {
    $detalleConsulta[] = "estado: $estadoFiltro";
}
registrarBitacora($conexion, 'Días de Disfrute', 'Consultar', !empty($detalleConsulta) ? 'Consultó el catálogo de aprobaciones de días de disfrute (' . implode(', ', $detalleConsulta) . ').' : 'Consultó el catálogo de aprobaciones de días de disfrute.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aprobación de Días de Disfrute</title>
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
        <h2 style="text-align:center; color:white;">Aprobación/Desaprobaciones de Días de Disfrute</h2>
    
        <form method="GET" action="aprobacion_dias_disfrute.php" class="buscador-container" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input
                type="text"
                name="buscar"
                class="input-busqueda"
                placeholder="Buscar por trabajador o código..."
                value="<?php echo htmlspecialchars($buscar); ?>"
            >

            <select name="estado" class="filtro-estado" onchange="this.form.submit()">
                <option value="" <?= $estadoFiltro === '' ? 'selected' : ''; ?>>Todos los estados</option>
                <option value="Pendiente" <?= $estadoFiltro === 'Pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                <option value="Aprobado" <?= $estadoFiltro === 'Aprobado' ? 'selected' : ''; ?>>Aprobados</option>
                <option value="Desaprobado" <?= $estadoFiltro === 'Desaprobado' ? 'selected' : ''; ?>>Desaprobados</option>
            </select>

            <button type="submit" class="btn-buscar">Buscar</button>

            <a href="lista_dias_disfrute.php" class="btn-pdf" style="text-decoration:none; margin-left:auto;">
                <i class="bi bi-list-ul"></i>
                Ver lista completa
            </a>
        </form>

        <table class="tabla">
            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Código</th>
                    <th>Observaciones</th>
                    <th>Motivo</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Finalización</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registros)): ?>
                    <?php foreach ($registros as $fila): ?>
                        <?php
                            $estadoFila = $fila['estado'] ?? 'Pendiente';
                            $claseBadge = $estadoFila === 'Aprobado'
                                ? 'badge-aprobado'
                                : ($estadoFila === 'Desaprobado' ? 'badge-desaprobado' : 'badge-pendiente');
                            $iconoBadge = $estadoFila === 'Aprobado'
                                ? 'bi-check-circle-fill'
                                : ($estadoFila === 'Desaprobado' ? 'bi-x-circle-fill' : 'bi-hourglass-split');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['trabajador'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['codigo_disfrute'] ?? ''); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($fila['observaciones'] ?? '', ENT_QUOTES, 'UTF-8')); ?></td>
                            <td><?php echo htmlspecialchars($fila['motivo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_inicio'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_finalizacion'] ?? ''); ?></td>
                            <td>
                                <span class="badge-estado <?= $claseBadge; ?>">
                                    <i class="bi <?= $iconoBadge; ?>"></i>
                                    <?= htmlspecialchars($estadoFila); ?>
                                </span>
                            </td>
                            <td class="acciones">
                                <?php if ($estadoFila === 'Aprobado'): ?>
                                   
                                <?php endif; ?>

                                <form action="../controladores/ctrl_dias_disfrute.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_dis" value="<?= htmlspecialchars($fila['id_dis'] ?? '') ?>">
                                    <input type="hidden" name="origen" value="aprobacion_dias_disfrute.php">
                                    <input type="hidden" name="query" value="<?= htmlspecialchars($queryActual, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="accion_estado" value="Aprobar">
                                    <button type="submit" name="cambiar_estado_disfrute" class="btn-aprobar" style="border:none; cursor:pointer;" <?= $estadoFila === 'Aprobado' ? 'disabled title="La solicitud ya está aprobada"' : '' ?>>
                                        <i class="bi bi-check-circle"></i> Aprobar
                                    </button>
                                </form>

                                <form action="../controladores/ctrl_dias_disfrute.php" method="POST" class="form-desaprobar" style="display:inline;" onsubmit="return solicitarJustificacion(this);">
                                    <input type="hidden" name="id_dis" value="<?= htmlspecialchars($fila['id_dis'] ?? '') ?>">
                                    <input type="hidden" name="origen" value="aprobacion_dias_disfrute.php">
                                    <input type="hidden" name="query" value="<?= htmlspecialchars($queryActual, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="accion_estado" value="Desaprobar">
                                    <input type="hidden" name="justificacion_desaprobacion" value="">
                                    <button type="submit" name="cambiar_estado_disfrute" class="btn-desaprobar" style="border:none; cursor:pointer;" <?= $estadoFila === 'Desaprobado' ? 'disabled title="La solicitud ya está desaprobada"' : '' ?>>
                                        <i class="bi bi-x-circle"></i> Desaprobar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">No se encontraron registros.</td>
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
<script>
function solicitarJustificacion(form) {
    if (form.querySelector('button').disabled) return false;
    const justificacion = window.prompt('Indique la justificación de la desaprobación:');
    if (justificacion === null) return false;
    if (!justificacion.trim()) {
        window.alert('La justificación es obligatoria para desaprobar.');
        return false;
    }
    form.querySelector('[name="justificacion_desaprobacion"]').value = justificacion.trim();
    return true;
}
</script>
</body>
</html>
