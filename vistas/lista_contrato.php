<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cargamos el controlador oficial de contratos
require_once("../controladores/ctrl_contrato.php");

// Traemos los contratos reales de la BD
$contratos = $controladorContrato->mostrarContratos();

// Determinamos el mensaje y estado para el Modal
$status = $_GET['status'] ?? null;
$mensajeExito = $_SESSION['exito_contrato'] ?? null;
$mensajeError = isset($_SESSION['error_contrato']) && is_array($_SESSION['error_contrato']) ? implode("\n", $_SESSION['error_contrato']) : null;

unset($_SESSION['exito_contrato'], $_SESSION['error_contrato']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Contratos</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>

<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">

    <div style="max-width: 950px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <!-- TABLA DE CONTRATOS (SOLO CONSULTA / VISTA DEL SIDEBAR) -->
        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white; margin-bottom: 20px;">Listado General de Contratos</h2>

                <div style="text-align:right; margin-bottom:15px;">
                    <a
                        href="/FUNDACITE/pdf/reporte_contratos.php"
                        class="btn-pdf"
                        target="_blank"
                        style="text-decoration:none;"
                    >
                        <i class="bi bi-file-earmark-pdf"></i>
                        Exportar PDF
                    </a>
                </div>

                <table class="tabla" style="width: 100% !important;">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Cargo</th>
                            <th>Tipo de Contrato</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contratos)): ?>
                            <?php foreach ($contratos as $con): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($con['nombre_trabajador'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($con['nombre_cargo'] ?? $con['cargo'] ?? 'Sin asignar'); ?></td>
                                    <td><?php echo htmlspecialchars($con['tipo_contrato'] ?? ''); ?></td>
                                    <td><?php echo !empty($con['fecha_contrato']) ? date("d/m/Y", strtotime($con['fecha_contrato'])) : ''; ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($con['fecha_fin']) && $con['fecha_fin'] !== '0000-00-00') {
                                            echo date("d/m/Y", strtotime($con['fecha_fin']));
                                        } else {
                                            echo '<span style="opacity: 0.8; font-style: italic;">Indefinido</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="acciones" style="text-align: center;">
                                        <a class="btn-editar" href="ver_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>"><i class="bi bi-eye"></i> Ver</a>
                                        <a class="btn-editar" href="editar_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                        <a href="../controladores/ctrl_contrato.php?accion=eliminar&id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>" 
                                           class="btn-eliminar" 
                                           style="text-decoration: none; display: inline-block;" 
                                           data-confirm="¿Seguro que deseas eliminar este contrato?">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No se encontraron contratos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>



</body>
</html>