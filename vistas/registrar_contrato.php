<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluimos el controlador oficial de contratos
// (este archivo ya deja instanciado $controladorContrato y ejecuta
//  guardar()/actualizar()/eliminar() según corresponda al $_POST/$_GET)
require_once("../controladores/ctrl_contrato.php");

// Traemos los contratos de la BD
$contratos = $controladorContrato->mostrarContratos();

// Traemos los trabajadores activos para llenar el select del formulario
// (usamos el método que ya existe en clase_contrato/ContratoControlador,
//  no hace falta un controlador de trabajadores aparte)
$trabajadores = $controladorContrato->mostrarTrabajadoresActivos();
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
                    } elseif ($_GET['status'] == 'existe') {
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
                        case 'existe': echo "⚠️ Este trabajador ya tiene un contrato registrado."; break;
                        case 'error': echo "⚠️ Hubo un error al procesar la solicitud en la Base de Datos."; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formContratos" action="../controladores/ctrl_contrato.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                <center><h2>Registrar Nuevo Contrato</h2></center>

                <div class="field">
                    <label>Trabajador</label>
                    <select name="id_trabajador" required style="width: 100%; padding: 10px; border-radius: 5px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <option value="" style="color: black;">Seleccione un trabajador...</option>
                        <?php if(!empty($trabajadores)): ?>
                            <?php foreach($trabajadores as $trab): ?>
                                <option value="<?php echo $trab['id_trabajador']; ?>" style="color: black;">
                                    <?php echo htmlspecialchars($trab['nombres'] . ' ' . $trab['apellidos'] . ' (' . $trab['cedula'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Tipo de Contrato</label>
                    <select name="tipo_contrato" required style="width: 100%; padding: 10px; border-radius: 5px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <option value="Indefinido" style="color: black;">Indefinido</option>
                        <option value="Tiempo determinado" style="color: black;" selected>Tiempo determinado</option>
                        <option value="Obra determinada" style="color: black;">Obra determinada</option>
                        <option value="Pasantía" style="color: black;">Pasantía</option>
                        <option value="Suplencia" style="color: black;">Suplencia</option>
                    </select>
                </div>

                <div class="field">
                    <label>Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" required>
                </div>

                <div class="field">
                    <label>Lugar de Trabajo</label>
                    <input type="text" name="lugar_trabajo" placeholder="Ej: FUNDACITE Yaracuy" required>
                </div>

                <div class="field">
                    <label>Salario Base (Bs.)</label>
                    <input type="number" step="0.01" name="salario" placeholder="Ej: 192.00" required min="0.01">
                </div>

                <div class="field">
                    <label>Notas de la Empresa (Opcional)</label>
                    <textarea name="notas_empresa" placeholder="Cláusulas o notas especiales..." style="width: 100%; padding: 10px; border-radius: 5px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); min-height: 80px; font-family: inherit;"></textarea>
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
                            <th>Salario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contratos)): ?>
                            <?php foreach ($contratos as $con): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($con['nombre_trabajador'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($con['tipo_contrato'] ?? ''); ?></td>
                                    <td><?php echo number_format($con['salario'] ?? 0, 2, ',', '.') . " Bs."; ?></td>
                                    <td class="acciones">
                                        <a class="btn-editar" href="ver_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>">Ver</a>
                                        <a class="btn-editar" href="editar_contrato.php?id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>">Editar</a>
                                        <a href="../controladores/ContratoControlador.php?action=eliminar&id=<?php echo urlencode($con['id_contrato'] ?? ''); ?>" 
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

</body>
</html>