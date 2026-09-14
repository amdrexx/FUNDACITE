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

$fechaNacimiento = !empty($dato['fecha']) ? date('d/m/Y', strtotime($dato['fecha'])) : 'No registrada';
$fechaIngreso = !empty($dato['fecha_ingreso']) ? date('d/m/Y', strtotime($dato['fecha_ingreso'])) : 'No registrada';
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
    <style>
        .worker-page {
            max-width: 960px;
            width: 100%;
            margin: 0 auto;
        }

        .worker-card {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 16px;
            background: rgba(9, 62, 97, 0.9);
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
        }

        .worker-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 28px 30px 24px;
            background: rgba(15, 76, 117, 0.72);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .worker-avatar {
            display: grid;
            place-items: center;
            flex: 0 0 52px;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            color: #fff;
            background: #3282B8;
            font-size: 23px;
        }

        .worker-header-content {
            min-width: 0;
            flex: 1;
        }

        .worker-kicker {
            margin: 0 0 5px;
            color: #b8dcf5;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .worker-header h2 {
            margin: 0;
            color: #fff;
            font-size: clamp(22px, 3vw, 28px);
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .worker-status {
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.34);
            border-radius: 999px;
            color: #fff;
            background: rgba(50, 130, 184, 0.38);
            font-size: 13px;
            font-weight: 700;
        }

        .worker-status--active {
            background: rgba(40, 167, 69, 0.38);
        }

        .worker-content {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            padding: 24px 30px 30px;
        }

        .worker-section {
            padding: 19px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
        }

        .worker-section--full {
            grid-column: 1 / -1;
        }

        .worker-section h3 {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 15px;
            color: #8ecdf5;
            font-size: 15px;
        }

        .worker-details {
            display: grid;
            gap: 11px;
            margin: 0;
        }

        .worker-detail {
            display: grid;
            grid-template-columns: minmax(116px, 0.8fr) minmax(0, 1.35fr);
            gap: 12px;
            margin: 0;
            color: #fff;
            line-height: 1.45;
        }

        .worker-detail dt {
            color: #b8dcf5;
            font-weight: 600;
        }

        .worker-detail dd {
            margin: 0;
            overflow-wrap: anywhere;
        }

        @media (max-width: 720px) {
            .worker-header,
            .worker-content {
                padding-left: 20px;
                padding-right: 20px;
            }

            .worker-header {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .worker-status {
                margin-left: 68px;
                margin-top: -10px;
            }

            .worker-content {
                grid-template-columns: 1fr;
            }

            .worker-section--full {
                grid-column: auto;
            }

            .worker-detail {
                grid-template-columns: 1fr;
                gap: 2px;
            }
        }
    </style>
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
    <div class="worker-page">
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
                        <div class="worker-detail"><dt>Fecha de ingreso</dt><dd><?php echo htmlspecialchars($fechaIngreso); ?></dd></div>
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
