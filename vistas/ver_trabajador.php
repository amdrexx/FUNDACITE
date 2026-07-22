<?php
include_once "includes/guardian.php";
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';

$idTrabajador = intval($_GET['id'] ?? 0);

$trabajador = new Trabajador($conexion);
$dato = $trabajador->obtenerTrabajadorPorId($idTrabajador);
$cargos = $trabajador->listarCargos();

if (!$dato) {
    die("Trabajador no encontrado.");
}

// Arma el texto completo de la dirección (Dirección - Parroquia, Municipio, Estado)
$direccionCompleta = '';
if (!empty($dato['direccion'])) {
    $direccionCompleta = $dato['direccion'];

    $ubicacion = array_filter([
        $dato['parroquia'] ?? '',
        $dato['municipio'] ?? '',
        $dato['estado'] ?? ''
    ]);

    if (!empty($ubicacion)) {
        $direccionCompleta .= ' - ' . implode(', ', $ubicacion);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Trabajador</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="custom-alert hidden" id="customAlert">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<div class="main">
    <div class="form-card">
        <div class="form-grid">

            <h2 class="full-width" style="text-align:center; margin-bottom:10px;">
                Ver Trabajador
            </h2>

            <!-- NO EDITABLES -->
            <div class="field">
                <label>Tipo de Documento</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['tipoDoc'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Cédula</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['cedula'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Nombres</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['nombres'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Apellidos</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['apellidos'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Fecha de Nacimiento</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['fecha'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Género</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['genero'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Fecha de Ingreso</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['fecha_ingreso'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Estado Civil</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['estadoCivil'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Correo Electrónico</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['correoElectronico'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Número de Teléfono</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['numeroTelefono'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Cargo</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['nombre_cargo'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Estatus Laboral</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['estatus_laboral'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Edad</label>
                <input
                    type="text"
                    value="<?php echo htmlspecialchars($dato['edad'] ?? ''); ?>"
                    readonly>
            </div>

            <div class="field">
                <label>Dirección</label>
                <input type="text" value="<?php echo htmlspecialchars($direccionCompleta); ?>" readonly>
            </div>

        </div>
    </div>
</div>

</body>
</html>