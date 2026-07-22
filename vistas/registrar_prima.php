<?php
session_start();
include_once "includes/guardian.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once '../modelos/clase_primas.php';

/* =========================
   MENSAJES
========================= */
$errores = $_SESSION['errores'] ?? [];
$exito = $_SESSION['exito'] ?? '';

unset($_SESSION['errores']);
unset($_SESSION['exito']);

/* =========================
   ACCIÓN Y ID
========================= */
$accion = $_GET['accion'] ?? 'registrar';
$id = $_GET['id'] ?? '';

$prima_data = [];

/* =========================
   CONSULTAR / EDITAR
========================= */
if (($accion === 'editar' || $accion === 'consultar') && !empty($id)) {

    $objprima = new Prima($conexion);

    $prima_data = $objprima->buscarPrimaPorId($id);
}

/* =========================
   VARIABLES DEL FORMULARIO
========================= */
$txt_id = $prima_data['id_prima'] ?? '';
$txt_tipo = $prima_data['tipo_prima'] ?? '';
$txt_porcentaje = $prima_data['porcentaje'] ?? '';
$txt_fecha = $prima_data['fecha'] ?? '';
$txt_estado = $prima_data['estado'] ?? '';

/* =========================
   DESHABILITAR CAMPOS
========================= */
$deshabilitado = ($accion === 'consultar') ? 'disabled' : '';

/* =========================
   TITULO Y BOTONES
========================= */
if ($accion === 'registrar') {

    $titulo = "Formulario de prima";
    $btn_texto = "Guardar prima";
    $btn_clase = "btn-success";

} elseif ($accion === 'editar') {

    $titulo = "Modificar prima";
    $btn_texto = "Actualizar prima";
    $btn_clase = "btn-warning";

} elseif ($accion === 'consultar') {

    $titulo = "Consulta de prima";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar primas</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>


<?php include "includes/layout.php"; ?>

<!-- FORMULARIO -->
<div class="main">
    <form class="form-card" id="formprimas" method="POST" action="../controladores/ctrl_primas.php" novalidate>
        <div class="form-grid">
            <h2 style="text-align: center; margin-bottom: 10px;">Registro de primas</h2>
    <div class="field">
   <label>Prima</label>
   <input type="text" name="tipoprima" id="tipoprima">
            </div>
    
<div class="field">
                <label>Porcentaje</label>
                <input type="number" name="porcentaje" id="porcentaje">
            </div>
            <div class="field">
    <label>Fecha de creación</label>
    <input type="date" name="fecha" id="fecha">
</div>
<div class="field">
    <label>Estado</label>
    <select name="estado" id="estado">
        <option value="">seleccione estado</option>
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
    </select>
</div>
            </div>
    
    <div class="botones">
        <button type="submit" name="accion" value="guardar" class="btn-guardar">
            Guardar
        </button>
    </div>

        </div>


        </div>
    </form>


<script src="/FUNDACITE/vistas/js/valid_primas.js"></script>

<!-- Alertas de errores/éxito -->
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

<?php if (!empty($exito)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($exito); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

</body>
</html>