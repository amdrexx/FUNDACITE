<?php
include_once "includes/guardian.php";
requireAdministradorODirector();

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errores = $_SESSION['error_registro'] ?? [];
$old = $_SESSION['old_input'] ?? [];
$exito_registro = $_SESSION['exito_registro'] ?? '';

unset(
    $_SESSION['error_registro'],
    $_SESSION['old_input'],
    $_SESSION['exito_registro']
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Datos del Convenio</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/jquery.min.js"></script>
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<!-- FORMULARIO DE REGISTRO -->
<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_convenio.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver
        </a>
    <form class="form-card" id="formConvenios" method="POST" action="../controladores/ctrl_convenio.php">

        <input type="hidden" name="accion" value="guardar">

        <div class="form-grid">
            <h2 style="text-align: center; margin-bottom: 10px;" class="full-width">Datos del Convenio</h2>

            <h3 class="form-section-title full-width">Datos del convenio</h3>

            <!-- Nombre del Gremio -->
            <div class="field">
                <label>Nombre del Gremio</label>
                <input type="text" name="nombre_gremio" id="nombre_gremio" maxlength="150" placeholder="Ingrese el nombre del gremio"
                       value="<?php echo htmlspecialchars($old['nombre_gremio'] ?? ''); ?>">
            </div>

            <!-- Días de Disfrute -->
            <div class="field">
                <label>Días de Disfrute</label>
                <input type="number" name="dias_disfrute" id="dias_disfrute" min="1" max="255" step="1" placeholder="Ej: 30"
                       value="<?php echo htmlspecialchars($old['dias_disfrute'] ?? ''); ?>">
            </div>

            <h3 class="form-section-title full-width">Vigencia</h3>

            <!-- Fecha de Inicio -->
            <div class="field">
                <label>Fecha de Inicio</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio"
                       value="<?php echo htmlspecialchars($old['fecha_inicio'] ?? ''); ?>">
            </div>

            <!-- Fecha de Vencimiento -->
            <div class="field">
                <label>Fecha de Vencimiento</label>
                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento"
                       value="<?php echo htmlspecialchars($old['fecha_vencimiento'] ?? ''); ?>">
            </div>


            <!-- BOTONES DE ACCIÓN -->
            <div class="form-actions full-width" style="display: flex; justify-content: flex-end; align-items: center;">
                <button type="submit" class="btn-guardar">Registrar Convenio</button>
            </div>
        </div>
    </form>
    </div>
</div>

<script src="/FUNDACITE/vistas/js/valid_convenio.js?v=1"></script>
</body>
</html>
