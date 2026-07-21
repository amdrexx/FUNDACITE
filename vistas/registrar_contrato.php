<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/permissions.php';
requireAdministrador();

// Cargamos el controlador oficial de contratos
require_once("../controladores/ctrl_contrato.php");

// Traemos los contratos reales de la BD
$contratos = $controladorContrato->mostrarContratos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Contratos</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>

<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">

    <div style="max-width: 600px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <?php if (isset($_GET['status'])): ?>
            <div style="margin: 0 auto 20px auto; width: 100%; text-align: center; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 14px; box-sizing: border-box;
                <?php
                    if ($_GET['status'] == 'success' || $_GET['status'] == 'updated') {
                        echo 'background-color: rgba(0, 255, 0, 0.2); color: #ccffcc; border: 1px solid #00ff00;';
                    } elseif ($_GET['status'] == 'deleted') {
                        echo 'background-color: rgba(255, 165, 0, 0.2); color: #ffe0b3; border: 1px solid #ffa500;';
                    } else {
                        echo 'background-color: rgba(255, 0, 0, 0.2); color: #ffcccc; border: 1px solid #ff0000;';
                    }
                ?>">
                <?php
                    switch ($_GET['status']) {
                        case 'success': echo "✅ ¡Contrato registrado exitosamente!"; break;
                        case 'updated': echo "🔄 ¡Contrato actualizado correctamente!"; break;
                        case 'deleted': echo "🗑️ ¡Contrato eliminado correctamente!"; break;
                        case 'error': echo "⚠️ Hubo un error al procesar la solicitud."; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formContratos" action="../controladores/ctrl_contrato.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <center><h2>Registrar Nuevo Contrato</h2></center>

                <div class="field">
                    <label>Cédula del Trabajador</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="cedula_trabajador" placeholder="Ej: V-25785010" required style="flex-grow: 1;">
                        <button type="button" id="btn_buscar_trabajador" style="padding: 10px 15px; border-radius: 5px; background-color: #007bff; color: white; border: none; cursor: pointer; font-weight: bold;">Buscar</button>
                    </div>
                </div>

                <input type="hidden" name="id_trabajador" id="id_trabajador" required>

                <div class="field">
                    <label>Trabajador Seleccionado</label>
                    <input type="text" id="nombre_trabajador" placeholder="Busca un trabajador primero...">
                </div>

                <div class="field">
                    <label>Tipo de Contrato</label>
                    <select name="tipo_contrato" required>
                        <option value="Indefinido" style="color: black;">Indefinido</option>
                        <option value="Tiempo determinado" style="color: black;" selected>Tiempo determinado</option>
                        <option value="Obra determinada" style="color: black;">Obra determinada</option>
                        <option value="Pasantía" style="color: black;">Pasantía</option>
                        <option value="Suplencia" style="color: black;">Suplencia</option>
                    </select>
                </div>

                <div class="field">
                    <label>Fecha del Contrato</label>
                    <input type="date" name="fecha_contrato" required>
                </div>

                <div class="field">
                    <label>Lugar de Trabajo</label>
                    <input type="text" name="lugar_trabajo" placeholder="Ej: Zona Industrial, Edificio FUNDACITE, San Felipe" required>
                </div>

                <div class="field">
                    <label>Nombre del Presidente</label>
                    <input type="text" name="nombre_presidente" placeholder="Ej: Miguel Ángel Solórzano Belizario" required>
                </div>

                <div class="field">
                    <label>Cédula del Presidente</label>
                    <input type="text" name="cedula_presidente" placeholder="Ej: V-19.817.987" required>
                </div>

                <div class="field">
                    <label>Gaceta Oficial de Designación</label>
                    <input type="text" name="gaceta_designacion_presidente" placeholder="Ej: N° 41.823" required>
                </div>

                <button type="submit" name="registrar_contrato" class="btn-guardar">Registrar Contrato</button>
            </form>
        </div>

        <div style="width: 100%; display: block; box-sizing: border-box;">
            <div class="glass tabla-container" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <h2 style="text-align:center; color:white;">Lista de Contratos</h2>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contratos)): ?>
                            <?php foreach ($contratos as $con): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($con['nombre_trabajador'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($con['tipo_contrato'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($con['fecha_contrato'] ?? ''); ?></td>
                                    <td class="acciones">
                                        <a class="btn-editar" href="ver_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>">Ver</a>
                                        <a class="btn-editar" href="editar_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>">Editar</a>
                                        <a href="../controladores/ctrl_contrato.php?action=eliminar&id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>" 
                                           class="btn-eliminar" 
                                           style="text-decoration: none; display: inline-block;" 
                                           onclick="return confirm('¿Seguro que deseas eliminar este contrato?');">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">No se encontraron contratos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

    <script src="/FUNDACITE/vistas/js/validar_contrato.js"></script>
    <script src="/FUNDACITE/vistas/js/ajax_contrato.js"></script>
</body>
</html>