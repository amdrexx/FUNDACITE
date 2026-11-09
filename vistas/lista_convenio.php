<?php
session_start();
include_once "includes/guardian.php";
requireAdministradorODirector();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errores = $_SESSION['error_edicion'] ?? [];
$exito_edicion = $_SESSION['exito_edicion'] ?? '';
$exito_registro = $_SESSION['exito_registro'] ?? '';

unset(
    $_SESSION['error_edicion'],
    $_SESSION['exito_edicion'],
    $_SESSION['exito_registro']
);

require_once '../conexion.php';
require_once '../modelos/clase_convenio.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$buscar = trim($_GET['buscar'] ?? '');

$convenio = new Convenio($conexion);
$convenios = $convenio->listarConvenios($buscar);
registrarBitacora($conexion, 'Convenios', 'Consultar', $buscar !== '' ? "Consultó el listado de convenios (búsqueda: \"$buscar\")." : 'Consultó el listado de convenios.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Convenios</title>
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
        <h2 style="text-align:center; color:white;">Lista de Convenios</h2>

        <form method="GET" action="lista_convenio.php" class="buscador-container" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input
                type="text"
                name="buscar"
                class="input-busqueda"
                placeholder="Buscar por nombre del gremio..."
                value="<?php echo htmlspecialchars($buscar); ?>"
            >
            <button type="submit" class="btn-buscar">Buscar</button>

            <a href="registrar_convenio.php" class="btn-persona" style="text-decoration:none;">
                + Agregar Convenio
            </a>

            <a
                href="/FUNDACITE/pdf/reporte_convenios.php<?= $buscar !== '' ? '?buscar=' . urlencode($buscar) : ''; ?>"
                class="btn-pdf"
                target="_blank"
                style="text-decoration:none; margin-left:auto;"
            >
                <i class="bi bi-file-earmark-pdf"></i>
                Exportar PDF
            </a>
        </form>

        
        <div class="modulo-card" style="margin: 20px auto 0; padding: 10px;">
            <table class="tabla">
            <thead>
                <tr>
                    <th>Gremio</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Vencimiento</th>
                    <th>Días de Disfrute</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($convenios)): ?>
                    <?php foreach ($convenios as $fila): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['nombre_gremio'] ?? ''); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($fila['fecha_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($fila['fecha_vencimiento'])); ?></td>
                            <td><?php echo (int) $fila['dias_disfrute']; ?></td>
                            <td><?php echo Convenio::estadoVigencia($fila['fecha_inicio'], $fila['fecha_vencimiento']); ?></td>
                            <td class="acciones">

                                <a class="btn-editar" href="editar_convenio.php?id=<?php echo urlencode($fila['id_convenio']); ?>">
                                    <i class="bi bi-pencil-square"></i>
                                    Editar
                                </a>

                                <form action="../controladores/ctrl_convenio.php" method="POST" style="display:inline;" data-confirm="¿Seguro que deseas eliminar este convenio?">
                                    <input type="hidden" name="id_convenio" value="<?php echo htmlspecialchars($fila['id_convenio']); ?>">
                                    <button type="submit" name="eliminar_convenio" class="btn-eliminar" style="border:none; cursor:pointer;">
                                        <i class="bi bi-trash"></i>
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No se encontraron convenios registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>