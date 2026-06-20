<?php
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

<div class="topbar">
    <img src="/FUNDACITE/vistas/img/logo.png" alt="Logo">
</div>

<div class="custom-alert hidden" id="customAlert">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li><a href="dashboard.html" class="submenu-link"><i class="bi bi-house-door-fill"></i> <b>INICIO</b></a></li>
        <li><a href="lista_personas.html" class="submenu-link"><i class="bi bi-people-fill"></i> <b>PERSONAS</b></a></li>
        <li><a href="lista_trabajadores.php" class="submenu-link"><i class="bi bi-person-workspace"></i> <b>TRABAJADORES</b></a></li>
        <li><a href="lista_cargos.html" class="submenu-link"><i class="bi bi-briefcase-fill"></i> <b>CARGO</b></a></li>
        <li><a href="lista_direcciones.html" class="submenu-link"><i class="bi bi-geo-alt-fill"></i> <b>DIRECCION</b></a></li>
        <li><a href="lista_contratos.html" class="submenu-link"><i class="bi bi-file-earmark-text-fill"></i> <b>CONTRATOS</b></a></li>
        <li><a href="lista_usuarios.html" class="submenu-link"><i class="bi bi-person-circle"></i> <b>USUARIO</b></a></li>
        <li><a href="lista_solicitudes.html" class="submenu-link"><i class="bi bi-calendar2-check-fill"></i> <b>SOLICITUDES DE DÍAS DE DISFRUTE</b></a></li>
    </ul>

    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
        <li><a href="" class="submenu-link"><i class="bi bi-box-arrow-right"></i> <b>CERRAR SESIÓN</b></a></li>
    </ul>
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

        </div>
    </div>
</div>

</body>
</html>