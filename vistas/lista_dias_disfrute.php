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
require_once '../modelos/clase_solicitud.php';

$buscar = trim($_GET['buscar'] ?? '');

$solicitud = new Solicitud($conexion);

// El modelo no filtra en la consulta (mostrarSolicitudes no recibe parámetros),
// así que traemos todo y filtramos aquí si el usuario buscó algo.
$resultado = $solicitud->mostrarSolicitudes();

$solicitudes = [];
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $solicitudes[] = $fila;
    }
}

if ($buscar !== '') {
    $buscarLower = mb_strtolower($buscar);
    $solicitudes = array_filter($solicitudes, function ($fila) use ($buscarLower) {
        return str_contains(mb_strtolower($fila['trabajador'] ?? ''), $buscarLower)
            || str_contains(mb_strtolower($fila['codigo_solicitud'] ?? ''), $buscarLower);
    });
}
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
                placeholder="Buscar por trabajador o código de solicitud..."
                value="<?php echo htmlspecialchars($buscar); ?>"
            >
            <button type="submit" class="btn-buscar">Buscar</button>

            <a href="registrar_dias_disfrute.php" class="btn-persona" style="text-decoration:none;">
                + Nuevo Registro
            </a>
        </form>

        <table class="tabla">
            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Código</th>
                    <th>Tipo de Solicitud</th>
                    <th>Motivo</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Finalización</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($solicitudes)): ?>
                    <?php foreach ($solicitudes as $fila): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['trabajador'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['codigo_solicitud'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['tipo_solicitud'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['motivo_solicitud'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_inicio'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_finalizacion'] ?? ''); ?></td>
                            <td class="acciones">

                                <a class="btn-ver" href="ver_solicitud.php?id=<?php echo urlencode($fila['id_solicitud'] ?? ''); ?>">
                                    <i class="bi bi-eye"></i>
                                    Ver
                                </a>
                                <a class="btn-editar" href="editar_solicitud.php?id=<?php echo urlencode($fila['id_solicitud'] ?? ''); ?>">
                                    <i class="bi bi-pencil-square"></i>
                                    Editar
                                </a>

                                <?php if (esAdministradorODirector()): ?>
                                <form action="../controladores/ctrl_solicitud.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar esta solicitud?');">
                                    <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($fila['id_solicitud'] ?? ''); ?>">
                                    <button type="submit" name="eliminar_solicitud" class="btn-eliminar" style="border:none; cursor:pointer;">
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
                        <td colspan="7" style="text-align:center;">No se encontraron solicitudes registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Alertas de errores/éxito -->
<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="document.getElementById('customAlert').classList.add('hidden')">Cerrar</button>
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