<?php
include "includes/guardian.php";
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errores = $_SESSION['error_edicion'] ?? [];
$exito   = $_SESSION['exito_edicion'] ?? '';

unset($_SESSION['error_edicion'], $_SESSION['exito_edicion']);

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';

$idTrabajador = intval($_GET['id'] ?? 0);

$trabajador = new Trabajador($conexion);
$dato = $trabajador->obtenerTrabajadorPorId($idTrabajador);
$cargos = $trabajador->listarCargos();

if (!$dato) {
    die("Trabajador no encontrado.");
}

$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';

unset($_SESSION['errores'], $_SESSION['exito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Trabajador</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>
<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>


<div class="main">
  <form class="form-card" id="formEditarTrabajador" method="POST" action="../controladores/ctrl_trabajador.php">
        <div class="form-grid">

            <h2 class="full-width" style="text-align:center; margin-bottom:10px;">
                Editar Trabajador
            </h2>

      

            <input type="hidden" name="editar_trabajador" value="1">
            <input type="hidden" name="id_trabajador" value="<?php echo htmlspecialchars($dato['id_trabajador']); ?>">

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

            <!-- EDITABLES -->
            <div class="field">
                <label>Estado Civil</label>
                <select name="estadoCivil" required>
                    <option value="">Seleccione...</option>
                    <option value="Soltero(a)" <?php echo (($dato['estadoCivil'] ?? '') == 'Soltero(a)') ? 'selected' : ''; ?>>Soltero(a)</option>
                    <option value="Casado(a)" <?php echo (($dato['estadoCivil'] ?? '') == 'Casado(a)') ? 'selected' : ''; ?>>Casado(a)</option>
                    <option value="Divorciado(a)" <?php echo (($dato['estadoCivil'] ?? '') == 'Divorciado(a)') ? 'selected' : ''; ?>>Divorciado(a)</option>
                    <option value="Viudo(a)" <?php echo (($dato['estadoCivil'] ?? '') == 'Viudo(a)') ? 'selected' : ''; ?>>Viudo(a)</option>
                </select>
            </div>

            <div class="field">
                <label>Correo Electrónico</label>
                <input type="email" name="correoElectronico" value="<?php echo htmlspecialchars($dato['correoElectronico'] ?? ''); ?>" required>
            </div>

            <div class="field">
                <label>Número de Teléfono</label>
                <input type="text" name="numeroTelefono" value="<?php echo htmlspecialchars($dato['numeroTelefono'] ?? ''); ?>" required>
            </div>

            <div class="field">
                <label>Cargo</label>
                <select name="cargo_id" required>
                    <option value="">Seleccione un cargo</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo htmlspecialchars($cargo['id_cargo']); ?>"
                            <?php echo (($dato['id_cargo'] ?? '') == $cargo['id_cargo']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cargo['nombre_cargo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Estatus Laboral</label>
                <select name="estatus_laboral" required>
                    <option value="">Seleccione...</option>
                    <option value="Activo" <?php echo (($dato['estatus_laboral'] ?? '') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Jubilado" <?php echo (($dato['estatus_laboral'] ?? '') == 'Jubilado') ? 'selected' : ''; ?>>Jubilado</option>
                    <option value="Suspendido" <?php echo (($dato['estatus_laboral'] ?? '') == 'Suspendido') ? 'selected' : ''; ?>>Suspendido</option>
                    <option value="Retirado" <?php echo (($dato['estatus_laboral'] ?? '') == 'Retirado') ? 'selected' : ''; ?>>Retirado</option>
                </select>
            </div>

            <div class="contenedor-botones full-width">
                <button type="submit" class="btn-guardar" style="margin-left: 10px;">
                    Guardar cambios
                </button>
            </div>

        </div>
    </form>
</div>

<script src="/FUNDACITE/vistas/js/valid_trabajadores.js"></script>
</body>
</html>