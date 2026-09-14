<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el controlador para mantener la arquitectura MVC
require_once __DIR__ . "/../controladores/ctrl_constancia.php";

$id_constancia = intval($_GET['id'] ?? 0);

if ($id_constancia <= 0) {
    header("Location: registrar_constancia.php");
    exit;
}

// Consultar datos actuales de la constancia usando el controlador
$constanciaActual = $controladorConstancia->buscarPorId($id_constancia);

if (!$constanciaActual) {
    header("Location: registrar_constancia.php?status=error");
    exit;
}

// --- Normalización de campos (con fallbacks por si el controlador no hace JOIN) ---
$nombreTrabajador = $constanciaActual['nombre_trabajador']
    ?? trim(($constanciaActual['nombres'] ?? '') . ' ' . ($constanciaActual['apellidos'] ?? ''))
    ?: 'N/A';

$cedulaTrabajador = $constanciaActual['cedula_trabajador'] ?? $constanciaActual['cedula'] ?? 'N/A';
$cargoTrabajador  = $constanciaActual['nombre_cargo'] ?? $constanciaActual['cargo'] ?? 'Sin cargo asignado';

$fechaIngreso = !empty($constanciaActual['fecha_ingreso'])
    ? date('d/m/Y', strtotime($constanciaActual['fecha_ingreso']))
    : 'No registrada';

$fechaEmision = !empty($constanciaActual['fecha'])
    ? date('d/m/Y', strtotime($constanciaActual['fecha']))
    : 'No registrada';

$salarioMonto = $constanciaActual['salario_monto'] ?? $constanciaActual['salario'] ?? null;
$salarioTexto = ($salarioMonto !== null && $salarioMonto !== '')
    ? 'Bs. ' . number_format((float)$salarioMonto, 2, ',', '.')
    : 'No registrado';

$tipoPersonal   = $constanciaActual['tipo_personal'] ?? 'No especificado';
$nombreDirector = $constanciaActual['nombre_director'] ?? 'No registrado';
$motivo         = $constanciaActual['motivo'] ?? 'No especificado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de la Constancia</title>
    <link rel="stylesheet" href="css/style_dashboard.css">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <script src="js/bootstrap.min.js"></script>
    <style>
        .cert-page {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }

        .cert-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 16px;
            background: rgba(9, 62, 97, 0.9);
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
        }

        .cert-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding: 28px 30px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(15, 76, 117, 0.72);
        }

        .cert-kicker {
            margin: 0 0 7px;
            color: #b8dcf5;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .cert-header h2 {
            margin: 0;
            color: #fff;
            font-size: clamp(22px, 3vw, 28px);
            font-weight: 700;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .cert-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            flex-shrink: 0;
            padding: 8px 13px;
            border: 1px solid rgba(255, 255, 255, 0.26);
            border-radius: 999px;
            color: #fff;
            background: rgba(50, 130, 184, 0.36);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .cert-content {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            padding: 24px 30px 28px;
        }

        .cert-section {
            padding: 19px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
        }

        .cert-section--full {
            grid-column: 1 / -1;
        }

        .cert-section h3 {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 15px;
            color: #8ecdf5;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .cert-details {
            display: grid;
            gap: 11px;
            margin: 0;
        }

        .cert-detail {
            display: grid;
            grid-template-columns: minmax(130px, 0.8fr) minmax(0, 1.3fr);
            gap: 12px;
            align-items: start;
            margin: 0;
            color: #fff;
            line-height: 1.45;
        }

        .cert-detail dt {
            color: #b8dcf5;
            font-weight: 600;
            font-size: 13.5px;
        }

        .cert-detail dd {
            margin: 0;
            overflow-wrap: anywhere;
            color: rgba(255, 255, 255, 0.92);
        }

        .cert-motivo {
            margin: 0;
            color: rgba(255, 255, 255, 0.88);
            line-height: 1.6;
            white-space: pre-line;
        }

        .cert-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 0 30px 30px;
        }

        .cert-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 9px;
            color: #fff;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            text-decoration: none;
            transition: transform 220ms cubic-bezier(0.16, 1, 0.3, 1),
                        background-color 220ms cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 220ms cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 220ms cubic-bezier(0.16, 1, 0.3, 1);
        }

        .cert-action:hover {
            color: #fff;
            transform: translateY(-2px);
        }

        .cert-action:active {
            transform: translateY(0) scale(0.98);
        }

        .cert-action:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(142, 205, 245, 0.55);
        }

        .cert-action--pdf {
            background: #1f9d4a;
            box-shadow: 0 8px 18px rgba(31, 157, 74, 0.28);
        }
        .cert-action--pdf:hover {
            background: #24b154;
            box-shadow: 0 10px 22px rgba(31, 157, 74, 0.36);
        }

        .cert-action--edit {
            background: #2f7fd1;
            box-shadow: 0 8px 18px rgba(47, 127, 209, 0.28);
        }
        .cert-action--edit:hover {
            background: #3a8de0;
            box-shadow: 0 10px 22px rgba(47, 127, 209, 0.36);
        }

        .cert-action--back {
            border-color: rgba(255, 255, 255, 0.55);
            background: transparent;
        }
        .cert-action--back:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 720px) {
            .cert-header,
            .cert-content,
            .cert-actions {
                padding-left: 20px;
                padding-right: 20px;
            }

            .cert-header {
                flex-direction: column;
            }

            .cert-content {
                grid-template-columns: 1fr;
            }

            .cert-section--full {
                grid-column: auto;
            }

            .cert-detail {
                grid-template-columns: 1fr;
                gap: 2px;
            }

            .cert-action {
                width: 100%;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .cert-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                background: white !important;
            }
            .cert-header,
            .cert-section {
                background: white !important;
            }
            .cert-header h2,
            .cert-detail,
            .cert-detail dd,
            .cert-motivo {
                color: black !important;
            }
        }
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="cert-page">
        <article class="cert-card">
            <header class="cert-header">
                <div>
                    <p class="cert-kicker">Información registrada</p>
                    <h2>Constancia N° #<?php echo str_pad((string)$id_constancia, 5, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <span class="cert-status"><i class="bi bi-file-earmark-check"></i> <?php echo htmlspecialchars($tipoPersonal); ?></span>
            </header>

            <div class="cert-content">
                <section class="cert-section">
                    <h3><i class="bi bi-person"></i> Información del trabajador</h3>
                    <dl class="cert-details">
                        <div class="cert-detail"><dt>Cédula</dt><dd><?php echo htmlspecialchars($cedulaTrabajador); ?></dd></div>
                        <div class="cert-detail"><dt>Nombre completo</dt><dd><?php echo htmlspecialchars($nombreTrabajador); ?></dd></div>
                        <div class="cert-detail"><dt>Cargo</dt><dd><?php echo htmlspecialchars($cargoTrabajador); ?></dd></div>
                    </dl>
                </section>

                <section class="cert-section">
                    <h3><i class="bi bi-briefcase"></i> Datos laborales</h3>
                    <dl class="cert-details">
                        <div class="cert-detail"><dt>Fecha de ingreso</dt><dd><?php echo htmlspecialchars($fechaIngreso); ?></dd></div>
                        <div class="cert-detail"><dt>Salario mensual</dt><dd><?php echo htmlspecialchars($salarioTexto); ?></dd></div>
                        <div class="cert-detail"><dt>Tipo de personal</dt><dd><?php echo htmlspecialchars($tipoPersonal); ?></dd></div>
                    </dl>
                </section>

                <section class="cert-section cert-section--full">
                    <h3><i class="bi bi-calendar3"></i> Detalles de la constancia</h3>
                    <dl class="cert-details">
                        <div class="cert-detail"><dt>Fecha de emisión</dt><dd><?php echo htmlspecialchars($fechaEmision); ?></dd></div>
                        <div class="cert-detail"><dt>Motivo de la solicitud</dt><dd class="cert-motivo"><?php echo htmlspecialchars($motivo); ?></dd></div>
                    </dl>
                </section>

                <section class="cert-section cert-section--full">
                    <h3><i class="bi bi-pen"></i> Responsable de la firma</h3>
                    <dl class="cert-details">
                        <div class="cert-detail"><dt>Director de departamento</dt><dd><?php echo htmlspecialchars($nombreDirector); ?></dd></div>
                    </dl>
                </section>
            </div>

            <footer class="cert-actions no-print">
                <a href="registrar_constancia.php" class="cert-action cert-action--back"><i class="bi bi-arrow-left"></i> Volver</a>
                <a href="editar_constancia.php?id=<?php echo urlencode($id_constancia); ?>" class="cert-action cert-action--edit"><i class="bi bi-pencil-square"></i> Editar</a>
            </footer>
        </article>
    </div>
</div>

</body>
</html>