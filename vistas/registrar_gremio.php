<?php
session_start();
include_once 'includes/guardian.php';
include_once "includes/roles.php";
requerirRol(['Administrador']);

require_once '../conexion.php';
require_once '../modelos/clase_gremio.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$idGremio = (int) ($_GET['id'] ?? 0);
$modelo = new Gremio($conexion);
$dato = $idGremio > 0 ? $modelo->obtenerPorId($idGremio) : null;

if ($idGremio > 0 && !$dato) {
    $_SESSION['flash_alerta'] = ['tipo' => 'warning', 'mensaje' => 'El gremio solicitado no existe.'];
    header('Location: lista_gremio.php');
    exit;
}
if ($dato) {
    registrarBitacora($conexion, 'Gremios', 'Consultar', "Consultó el gremio ID $idGremio para edición.");
}

$errores = $_SESSION[$idGremio > 0 ? 'error_edicion' : 'error_registro'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['error_edicion'], $_SESSION['error_registro'], $_SESSION['old_input']);
$nombre = $old['nombre'] ?? ($dato['nombre'] ?? '');
$estado = $old['estado'] ?? ($dato['estado'] ?? 'Activo');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>
<?php include 'includes/layout.php'; ?>

<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_gremio.php"><i class="bi bi-arrow-left"></i> Volver</a>
        <form class="form-card" method="POST" action="../controladores/ctrl_gremio.php">
            <input type="hidden" name="accion" value="<?php echo $idGremio > 0 ? 'actualizar' : 'guardar'; ?>">
            <?php if ($idGremio > 0): ?>
                <input type="hidden" name="id_gremio" value="<?php echo $idGremio; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <h2 class="full-width" style="text-align:center;margin-bottom:10px;">
                    <?php echo $idGremio > 0 ? 'Editar Gremio' : 'Registrar Gremio'; ?>
                </h2>
                <h3 class="form-section-title full-width">Datos del gremio</h3>

                <div class="field">
                    <label for="nombre">Nombre del Gremio</label>
                    <input type="text" name="nombre" id="nombre" maxlength="150" minlength="3" required
                           placeholder="Ingrese el nombre del gremio"
                           value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="field">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado" required>
                        <option value="Activo" <?php echo $estado === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo $estado === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>

                <div class="form-actions full-width" style="display:flex;justify-content:flex-end;gap:10px;">
                    <a href="lista_gremio.php" class="btn-eliminar" style="text-decoration:none;">Cancelar</a>
                    <button type="submit" class="btn-guardar">
                        <?php echo $idGremio > 0 ? 'Guardar cambios' : 'Registrar Gremio'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>
