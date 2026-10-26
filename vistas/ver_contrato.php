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
require_once __DIR__ . "/../controladores/ctrl_contrato.php";

$id_contrato = intval($_GET['id'] ?? 0);

if ($id_contrato <= 0) {
    header("Location: registrar_contrato.php");
    exit;
}

// Consultar datos actuales del contrato usando el controlador
$contratoActual = $controladorContrato->buscarPorId($id_contrato);

if (!$contratoActual) {
    header("Location: registrar_contrato.php?status=error");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Contrato</title>
    <link rel="stylesheet" href="css/style_dashboard.css">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <script src="js/bootstrap.min.js"></script>
    <style>
        .contract-page {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }

        .contract-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 16px;
            background: rgba(9, 62, 97, 0.9);
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
        }

        .contract-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding: 28px 30px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(15, 76, 117, 0.72);
        }

        .contract-kicker {
            margin: 0 0 7px;
            color: #b8dcf5;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .contract-header h2 {
            margin: 0;
            color: #fff;
            font-size: clamp(22px, 3vw, 28px);
            line-height: 1.2;
        }

        .contract-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            flex-shrink: 0;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.26);
            border-radius: 999px;
            color: #fff;
            background: rgba(50, 130, 184, 0.36);
            font-size: 13px;
            font-weight: 600;
        }

        .contract-content {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            padding: 24px 30px 28px;
        }

        .contract-section {
            padding: 19px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
        }

        .contract-section--full {
            grid-column: 1 / -1;
        }

        .contract-section h3 {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 15px;
            color: #8ecdf5;
            font-size: 15px;
        }

        .contract-details {
            display: grid;
            gap: 11px;
            margin: 0;
        }

        .contract-detail {
            display: grid;
            grid-template-columns: minmax(120px, 0.8fr) minmax(0, 1.3fr);
            gap: 12px;
            align-items: start;
            margin: 0;
            color: #fff;
            line-height: 1.45;
        }

        .contract-detail dt {
            color: #b8dcf5;
            font-weight: 600;
        }

        .contract-detail dd {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .contract-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 0 30px 30px;
        }

        .contract-action {
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
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }

        .contract-action:hover {
            color: #fff;
            transform: translateY(-2px);
        }

        .contract-action:active {
            transform: translateY(0);
        }

        .contract-action--pdf { background: #28a745; }
        .contract-action--edit { background: #007bff; }
        .contract-action--back {
            border-color: rgba(255, 255, 255, 0.55);
            background: transparent;
        }

        @media (max-width: 720px) {
            .contract-header,
            .contract-content,
            .contract-actions {
                padding-left: 20px;
                padding-right: 20px;
            }

            .contract-header {
                flex-direction: column;
            }

            .contract-content {
                grid-template-columns: 1fr;
            }

            .contract-section--full {
                grid-column: auto;
            }

            .contract-detail {
                grid-template-columns: 1fr;
                gap: 2px;
            }

            .contract-action {
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
            .contract-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                background: white !important;
            }
            .contract-header,
            .contract-section {
                background: white !important;
            }
            .contract-header h2,
            .contract-detail,
            .contract-detail dd {
                color: black !important;
            }
        }
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="contract-page">
        <article class="contract-card">
            <header class="contract-header">
                <div>
                    <p class="contract-kicker">Información registrada</p>
                    <h2>Contrato N° #<?php echo str_pad($contratoActual['id_contrato'], 5, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <span class="contract-status"><i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($contratoActual['tipo_contrato']); ?></span>
            </header>

            <div class="contract-content">
                <section class="contract-section">
                    <h3><i class="bi bi-person"></i> Información del trabajador</h3>
                    <dl class="contract-details">
                        <div class="contract-detail"><dt>Cédula</dt><dd><?php echo htmlspecialchars($contratoActual['cedula_trabajador'] ?? $contratoActual['cedula'] ?? 'N/A'); ?></dd></div>
                        <div class="contract-detail"><dt>Nombre completo</dt><dd><?php echo htmlspecialchars($contratoActual['nombre_trabajador'] ?? (($contratoActual['nombres'] ?? '') . ' ' . ($contratoActual['apellidos'] ?? ''))); ?></dd></div>
                        <div class="contract-detail"><dt>Cargo</dt><dd><?php echo htmlspecialchars($contratoActual['nombre_cargo'] ?? $contratoActual['cargo'] ?? 'Sin cargo asignado'); ?></dd></div>
                    </dl>
                </section>

                <section class="contract-section">
                    <h3><i class="bi bi-calendar3"></i> Vigencia del contrato</h3>
                    <dl class="contract-details">
                        <div class="contract-detail"><dt>Tipo</dt><dd><?php echo htmlspecialchars($contratoActual['tipo_contrato']); ?></dd></div>
                        <div class="contract-detail"><dt>Fecha de inicio</dt><dd><?php echo !empty($contratoActual['fecha_contrato']) ? date("d/m/Y", strtotime($contratoActual['fecha_contrato'])) : 'N/A'; ?></dd></div>
                        <div class="contract-detail"><dt>Fecha de finalización</dt><dd><?php echo (!empty($contratoActual['fecha_fin']) && $contratoActual['fecha_fin'] !== '0000-00-00') ? date("d/m/Y", strtotime($contratoActual['fecha_fin'])) : 'Indefinido'; ?></dd></div>
                    </dl>
                </section>

                <section class="contract-section contract-section--full">
                    <h3><i class="bi bi-geo-alt"></i> Lugar de trabajo</h3>
                    <dl class="contract-details">
                        <div class="contract-detail"><dt>Ubicación</dt><dd><?php echo htmlspecialchars($contratoActual['lugar_trabajo']); ?></dd></div>
                    </dl>
                </section>

                <section class="contract-section contract-section--full">
                    <h3><i class="bi bi-building"></i> Representación institucional</h3>
                    <dl class="contract-details">
                        <div class="contract-detail"><dt>Presidente</dt><dd><?php echo htmlspecialchars($contratoActual['nombre_presidente']); ?></dd></div>
                        <div class="contract-detail"><dt>Cédula</dt><dd><?php echo htmlspecialchars($contratoActual['cedula_presidente']); ?></dd></div>
                        <div class="contract-detail"><dt>Gaceta oficial</dt><dd><?php echo htmlspecialchars($contratoActual['gaceta_designacion_presidente']); ?></dd></div>
                    </dl>
                </section>
            </div>

            <footer class="contract-actions no-print">
                <a href="registrar_contrato.php" class="contract-action contract-action--back"><i class="bi bi-arrow-left"></i> Volver</a>
                <a href="../pdf/contrato.php?id_contrato=<?php echo urlencode($contratoActual['id_contrato']); ?>" target="_blank" class="contract-action contract-action--pdf"><i class="bi bi-printer"></i> Imprimir / Guardar PDF</a>
                <a href="editar_contrato.php?id=<?php echo urlencode($contratoActual['id_contrato']); ?>" class="contract-action contract-action--edit"><i class="bi bi-pencil-square"></i> Editar</a>

            </footer>
        </article>
    </div>
</div>

</body>
</html>
