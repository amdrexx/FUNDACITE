<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../controladores/ctrl_contrato.php");
require_once("../modelos/clase_trabajador.php");

$trabajadorModelo = new Trabajador($conexion);

// El paso anterior conserva los datos del trabajador como borrador de sesión.
$borradorTrabajador = $_SESSION['trabajador_pendiente'] ?? null;
$modoTrabajadorExistente = !is_array($borradorTrabajador) || isset($_GET['id_trabajador']);
$idTrabajadorPreseleccionado = $modoTrabajadorExistente ? (int) ($_GET['id_trabajador'] ?? 0) : 0;

if ($modoTrabajadorExistente) {
    $infoTrabajador = $idTrabajadorPreseleccionado > 0
        ? $trabajadorModelo->obtenerTrabajadorPorId($idTrabajadorPreseleccionado)
        : null;
    if (!$infoTrabajador) {
        $_SESSION['error_registro'] = ['Complete primero los datos del trabajador.'];
        header('Location: registrar_trabajadores.php');
        exit;
    }
    $tipoDocBorrador = $infoTrabajador['tipo_documento'] ?? 'Cédula';
} else {
    $tipoDocBorrador = ($borradorTrabajador['tipoDoc'] ?? '') === 'E' ? 'Cédula de Extranjería' : 'Cédula';
    $infoTrabajador = [
        'tipo_documento' => $tipoDocBorrador,
        'cedula' => $borradorTrabajador['cedula'] ?? '',
        'nombres' => $borradorTrabajador['nombres'] ?? '',
        'apellidos' => $borradorTrabajador['apellidos'] ?? '',
        'tipo_personal' => $borradorTrabajador['tipo_personal'] ?? '',
        'nombre_cargo' => 'Cargo no disponible',
    ];
    foreach ($trabajadorModelo->listarCargos() as $cargoDisponible) {
        if ((string) ($cargoDisponible['id_cargo'] ?? '') === (string) ($borradorTrabajador['id_cargo'] ?? '')) {
            $infoTrabajador['nombre_cargo'] = $cargoDisponible['nombre_cargo'];
            break;
        }
    }
}
$tokenTrabajadorPendiente = $modoTrabajadorExistente ? '' : ($_SESSION['token_trabajador_pendiente'] ?? '');
$datosContrato = $_SESSION['old_contrato'] ?? [];

// -----------------------------------------------------------------------------
// BÚSQUEDA EXCLUSIVA Y DINÁMICA DEL PRESIDENTE EN LA BASE DE DATOS
// -----------------------------------------------------------------------------
$nombrePresidenteAuto = "";
$cedulaPresidenteAuto = "";
$esPresidentePendienteActivo = !$modoTrabajadorExistente
    && ($borradorTrabajador['estatus_laboral'] ?? '') === 'Activo'
    && stripos((string) ($infoTrabajador['nombre_cargo'] ?? ''), 'presidente') !== false;

$sqlPresidente = "SELECT t.nombres, t.apellidos, t.cedula, t.tipo_documento
                  FROM TRABAJADOR t
                  INNER JOIN CARGO c ON t.id_cargo = c.id_cargo
                  WHERE (LOWER(c.nombre_cargo) LIKE '%presidente%' OR LOWER(c.nombre_cargo) LIKE '%presidente(a)%')
                    AND t.status = 'Activo'
                  ORDER BY t.id_trabajador DESC
                  LIMIT 1";

$resPresidente = $esPresidentePendienteActivo ? false : $conexion->query($sqlPresidente);

if ($esPresidentePendienteActivo) {
    $nombrePresidenteAuto = trim(($borradorTrabajador['nombres'] ?? '') . ' ' . ($borradorTrabajador['apellidos'] ?? ''));
    $cedulaPresidenteAuto = $tipoDocBorrador . '-' . trim($borradorTrabajador['cedula'] ?? '');
} elseif ($resPresidente && $resPresidente->num_rows > 0) {
    $datosPresi = $resPresidente->fetch_assoc();
    $nombrePresidenteAuto = trim($datosPresi['nombres'] . ' ' . $datosPresi['apellidos']);
    $tipoDocPresi = !empty($datosPresi['tipo_documento']) ? $datosPresi['tipo_documento'] : 'Cédula';
    $cedulaPresidenteAuto = $tipoDocPresi . '-' . trim($datosPresi['cedula']);
}
// -----------------------------------------------------------------------------

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
    <title>Datos del Contrato</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>

<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">

    <div style="max-width: 900px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">

        <!-- FORMULARIO DE REGISTRO DE CONTRATO (PASO 2) -->
        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <form class="form-card" id="formContratos" action="../controladores/ctrl_contrato.php" method="POST" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">
                
                <input type="hidden" name="accion" value="guardar">
                <?php if ($modoTrabajadorExistente): ?>
                    <input type="hidden" name="id_trabajador" value="<?php echo $idTrabajadorPreseleccionado; ?>">
                <?php else: ?>
                    <input type="hidden" name="token_trabajador_pendiente" value="<?php echo htmlspecialchars($tokenTrabajadorPendiente, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <h2 class="full-width" style="text-align: center; margin-bottom: 20px;">Datos del Contrato</h2>

                    <h3 class="form-section-title full-width">
                    Trabajador Asociado: <?php echo htmlspecialchars($infoTrabajador['nombres'] ?? '') . ' ' . htmlspecialchars($infoTrabajador['apellidos'] ?? ''); ?> - Tipo De Documento: <?php echo htmlspecialchars(($infoTrabajador['tipo_documento'] ?? 'Cédula') . '-' . ($infoTrabajador['cedula'] ?? 'N/A')); ?>
                    </h3>


                    <h3 class="form-section-title full-width">Detalles del Contrato</h3>

                    <!-- Tipo de Contrato -->
                    <div class="field">
                        <label>Tipo de Contrato</label>
                        <select name="tipo_contrato" id="tipo_contrato" required>
                            <option value="Tiempo determinado" style="color: black;" <?php echo empty($datosContrato['tipo_contrato']) || ($datosContrato['tipo_contrato'] ?? '') === 'Tiempo determinado' ? '' : ''; ?>>Tiempo determinado</option>
                            <option value="Tiempo indeterminado" style="color: black;" <?php echo ($datosContrato['tipo_contrato'] ?? '') === 'Tiempo indeterminado' ? '' : ''; ?>>Tiempo indeterminado</option>
                        </select>
                    </div>

                    <!-- Tipo de personal registrado en TRABAJADOR -->
                    <div class="field">
                        <label>Tipo de Personal</label>
                        <input type="text" value="<?php echo htmlspecialchars($infoTrabajador['tipo_personal'] ?? 'No especificado', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>

                    <!-- Lugar de Trabajo -->
                    <div class="field">
                        <label>Lugar de Trabajo</label>
                        <input type="text" name="lugar_trabajo" value="<?php echo htmlspecialchars($datosContrato['lugar_trabajo'] ?? 'Zona Industrial, Edificio FUNDACITE, San Felipe', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej: Zona Industrial, Edificio FUNDACITE, San Felipe" required>
                    </div>

                    <!-- Fecha de Ingreso (Contrato) -->
                    <div class="field">
                        <label>Fecha de Ingreso</label>
                        <input type="date" name="fecha_contrato" value="<?php echo htmlspecialchars($datosContrato['fecha_contrato'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <!-- Fecha de Finalización -->
                    <div class="field">
                        <label>Fecha de Finalización</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($datosContrato['fecha_fin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <h3 class="form-section-title full-width">Datos del Representante (Presidente)</h3>

                    <!-- Nombre del Presidente -->
                    <div class="field">
                        <label>Nombre del Presidente (Actual en BD)</label>
                        <input type="text" name="nombre_presidente" value="<?php echo htmlspecialchars($nombrePresidenteAuto); ?>" readonly required style="background-color: rgba(255, 255, 255, 0.05); cursor: not-allowed;" title="Este campo se carga automáticamente del registro del Presidente activo">
                    </div>

                    <!-- Cédula del Presidente -->
                    <div class="field">
                        <label>Cédula del Presidente (Actual en BD)</label>
                        <input type="text" name="cedula_presidente" value="<?php echo htmlspecialchars($cedulaPresidenteAuto); ?>" readonly required style="background-color: rgba(255, 255, 255, 0.05); cursor: not-allowed;" title="Este campo se carga automáticamente del registro del Presidente activo">
                    </div>
<!-- Botones de Acción / Navegación -->
                    <div class="form-actions full-width" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <a href="<?php echo $modoTrabajadorExistente ? 'registrar_trabajadores.php?id=' . $idTrabajadorPreseleccionado . '&flujo=contrato' : 'registrar_trabajadores.php?flujo=contrato'; ?>" onclick="event.stopPropagation();" style="color: #bbb; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                            <i class="bi bi-arrow-left"></i> Modificar datos del Trabajador
                        </a>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <?php if (!$modoTrabajadorExistente): ?>
                                <a href="../controladores/ctrl_contrato.php?accion=cancelar_registro" style="color:#bbb; text-decoration:none;">Cancelar registro</a>
                            <?php endif; ?>
                            <button type="submit" name="registrar_contrato" class="btn-guardar">Finalizar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>



<script src="/FUNDACITE/vistas/js/valid_contrato_extra.js"></script>
</body>
</html>