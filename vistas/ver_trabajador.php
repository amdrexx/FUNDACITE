<?php
include_once "includes/guardian.php";
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$idTrabajador = intval($_GET['id'] ?? 0);

$trabajador = new Trabajador($conexion);
$dato = $trabajador->obtenerTrabajadorPorId($idTrabajador);

if (!$dato) {
    die("Trabajador no encontrado.");
}

registrarBitacora($conexion, 'Trabajadores', 'Consultar', "Consultó la ficha del trabajador ID $idTrabajador.");

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

$fechaNacimiento = !empty($dato['fecha']) ? date('d/m/Y', strtotime($dato['fecha'])) : 'No registrada';
$estatus = $dato['estatus_laboral'] ?? 'No registrado';
$estatusActivo = mb_strtolower($estatus, 'UTF-8') === 'activo';
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

<?php include "includes/alerta.php"; ?>

<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_trabajadores.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver 
        </a>
        <article class="worker-card">
            <header class="worker-header">
                <div class="worker-avatar" aria-hidden="true"><i class="bi bi-person"></i></div>
                <div class="worker-header-content">
                    <p class="worker-kicker">Expediente de trabajador</p>
                    <h2><?php echo htmlspecialchars(trim(($dato['nombres'] ?? '') . ' ' . ($dato['apellidos'] ?? '')) ?: 'Trabajador'); ?></h2>
                </div>
                <span class="worker-status<?php echo $estatusActivo ? ' worker-status--active' : ''; ?>"><?php echo htmlspecialchars($estatus); ?></span>
            </header>

            <div class="worker-content">
                <section class="worker-section">
                    <h3><i class="bi bi-person-vcard"></i> Datos personales</h3>
                    <dl class="worker-details">
                        <div class="worker-detail"><dt>Documento</dt><dd><?php echo htmlspecialchars($dato['tipoDoc'] ?? 'No registrado'); ?></dd></div>
                        <div class="worker-detail"><dt>Cédula</dt><dd><?php echo htmlspecialchars($dato['cedula'] ?? 'No registrada'); ?></dd></div>
                        <div class="worker-detail"><dt>Fecha de nacimiento</dt><dd><?php echo htmlspecialchars($fechaNacimiento); ?></dd></div>
                        <div class="worker-detail"><dt>Edad</dt><dd><?php echo htmlspecialchars(($dato['edad'] ?? '') !== '' ? $dato['edad'] . ' años' : 'No registrada'); ?></dd></div>
                        <div class="worker-detail"><dt>Género</dt><dd><?php echo htmlspecialchars($dato['genero'] ?? 'No registrado'); ?></dd></div>
                        <div class="worker-detail"><dt>Estado civil</dt><dd><?php echo htmlspecialchars($dato['estadoCivil'] ?? 'No registrado'); ?></dd></div>
                    </dl>
                </section>

                <section class="worker-section">
                    <h3><i class="bi bi-briefcase"></i> Información laboral</h3>
                    <dl class="worker-details">
                        <div class="worker-detail"><dt>Estatus laboral</dt><dd><?php echo htmlspecialchars($estatus); ?></dd></div>
                        <div class="worker-detail"><dt>Cargo</dt><dd><?php echo htmlspecialchars($dato['nombre_cargo'] ?? 'Sin cargo asignado'); ?></dd></div>
                        <div class="worker-detail"><dt>Nivel de educación</dt><dd><?php echo htmlspecialchars($dato['nivel_educacion'] ?? 'No registrado'); ?></dd></div>
                        <div class="worker-detail"><dt>Cantidad de hijos</dt><dd><?php echo htmlspecialchars((string) ($dato['hijos'] ?? 0)); ?></dd></div>
                        <div class="worker-detail worker-detail--service"><dt>Años de servicio en la Administración Pública</dt><dd><?php echo htmlspecialchars((string) ($dato['anos_servicio_administracion_publica'] ?? 0)); ?></dd></div>
                    </dl>
                </section>

                <section class="worker-section">
                    <h3><i class="bi bi-telephone"></i> Contacto</h3>
                    <dl class="worker-details">
                        <div class="worker-detail"><dt>Correo electrónico</dt><dd><?php echo htmlspecialchars($dato['correoElectronico'] ?? 'No registrado'); ?></dd></div>
                        <div class="worker-detail"><dt>Teléfono</dt><dd><?php echo htmlspecialchars($dato['numeroTelefono'] ?? 'No registrado'); ?></dd></div>
                    </dl>
                </section>

                <section class="worker-section">
                    <h3><i class="bi bi-geo-alt"></i> Dirección</h3>
                    <dl class="worker-details">
                        <div class="worker-detail"><dt>Ubicación</dt><dd><?php echo htmlspecialchars($direccionCompleta ?: 'No registrada'); ?></dd></div>
                    </dl>
                </section>
            </div>
        </article>
    </div>
</div>

</body>
</html>
