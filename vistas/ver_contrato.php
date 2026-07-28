<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../conexion.php";
require_once "../modelos/clase_contrato.php";

$id_contrato = intval($_GET['id'] ?? 0);

if ($id_contrato <= 0) {
    header("Location: registrar_contrato.php");
    exit;
}

$contratoObj = new clase_contrato($conexion);
$contratoActual = $contratoObj->buscarPorId($id_contrato);

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
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .form-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }
        }
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 650px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <div class="form-card" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                
                <center>
                    <h2>Detalle del Contrato N° #<?php echo str_pad($contratoActual['id_contrato'], 5, '0', STR_PAD_LEFT); ?></h2>
                </center>

                <!-- DATOS DEL TRABAJADOR -->
                <div style="border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px; margin-bottom: 15px;">
                    <h3 style="font-size: 16px; color: #007bff; margin-bottom: 10px;">Información del Trabajador</h3>
                    <p><strong>Cédula:</strong> <?php echo htmlspecialchars($contratoActual['cedula'] ?? 'N/A'); ?></p>
                    <p><strong>Nombres y Apellidos:</strong> <?php echo htmlspecialchars(($contratoActual['nombres'] ?? '') . ' ' . ($contratoActual['apellidos'] ?? '')); ?></p>
                </div>

                <!-- DATOS DEL CONTRATO -->
                <div style="border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px; margin-bottom: 15px;">
                    <h3 style="font-size: 16px; color: #007bff; margin-bottom: 10px;">Especificaciones del Contrato</h3>
                    <p><strong>Tipo de Contrato:</strong> <?php echo htmlspecialchars($contratoActual['tipo_contrato']); ?></p>
                    <p><strong>Fecha de Emisión/Inicio:</strong> <?php echo date("d/m/Y", strtotime($contratoActual['fecha_contrato'])); ?></p>
                    <p><strong>Lugar de Trabajo:</strong> <?php echo htmlspecialchars($contratoActual['lugar_trabajo']); ?></p>
                </div>

                <!-- DATOS INSTITUCIONALES / PRESIDENTE -->
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px; color: #007bff; margin-bottom: 10px;">Representación Institucional</h3>
                    <p><strong>Presidente:</strong> <?php echo htmlspecialchars($contratoActual['nombre_presidente']); ?></p>
                    <p><strong>Cédula Presidente:</strong> <?php echo htmlspecialchars($contratoActual['cedula_presidente']); ?></p>
                    <p><strong>Gaceta Oficial:</strong> <?php echo htmlspecialchars($contratoActual['gaceta_designacion_presidente']); ?></p>
                </div>

                <!-- BOTONES DE ACCIÓN (Se ocultan al imprimir) -->
                <div class="no-print" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button onclick="window.print();" class="btn-guardar full-width" style="background-color: #28a745; border: none; cursor: pointer;">
                        Imprimir / Guardar PDF
                    </button>
                    <a href="editar_contrato.php?id=<?php echo $contratoActual['id_contrato']; ?>" class="btn-guardar full-width" style="text-align:center; text-decoration:none; display: inline-block; line-height: 2.2;">
                    <i class="bi bi-pencil-square"></i>    
                    Editar
                    </a>
                    <a href="registrar_contrato.php" class="btn-persona full-width" style="text-align:center; text-decoration:none; display: inline-block; line-height: 2.2;">
                        Volver
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

</body>
</html>