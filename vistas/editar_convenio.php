<?php
include_once "includes/guardian.php";
requireAdministradorODirector();

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errores = $_SESSION['error_edicion'] ?? [];
unset($_SESSION['error_edicion']);

require_once '../conexion.php';
require_once '../modelos/clase_convenio.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$idConvenio = intval($_GET['id'] ?? 0);

$convenio = new Convenio($conexion);
$dato = $convenio->obtenerConvenioPorId($idConvenio);

if (!$dato) {
    die("Convenio no encontrado.");
}

registrarBitacora($conexion, 'Convenios', 'Consultar', "Consultó el convenio ID $idConvenio para edición.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Convenio</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <script src="/FUNDACITE/vistas/js/jquery.min.js"></script>
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_convenio.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver
        </a>
    <form class="form-card" id="formEditarConvenio" method="POST" action="../controladores/ctrl_convenio.php">

        <input type="hidden" name="accion" value="actualizar">
        <input type="hidden" name="id_convenio" value="<?php echo (int) $dato['id_convenio']; ?>">

        <div class="form-grid">
            <h2 class="full-width" style="text-align:center; margin-bottom:10px;">Editar Convenio</h2>

            <h3 class="form-section-title full-width">Datos del convenio</h3>

            <div class="field">
                <label>Nombre del Gremio</label>
                <input type="text" name="nombre_gremio" id="nombre_gremio" maxlength="150"
                       value="<?php echo htmlspecialchars($dato['nombre_gremio']); ?>">
            </div>

            <div class="field">
                <label>Días de Disfrute</label>
                <input type="number" name="dias_disfrute" id="dias_disfrute" min="1" max="255" step="1"
                       value="<?php echo (int) $dato['dias_disfrute']; ?>">
            </div>

            <h3 class="form-section-title full-width">Vigencia</h3>

            <div class="field">
                <label>Fecha de Inicio</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio"
                       value="<?php echo htmlspecialchars($dato['fecha_inicio']); ?>">
            </div>

            <div class="field">
                <label>Fecha de Vencimiento</label>
                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento"
                       value="<?php echo htmlspecialchars($dato['fecha_vencimiento']); ?>">
            </div>


            <div class="contenedor-botones full-width">
                <button type="submit" class="btn-guardar" style="margin-left: 10px;">
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>
    </div>
</div>

<script src="/FUNDACITE/vistas/js/valid_convenio.js?v=1"></script>
</body>
</html>
