<?php
session_start();
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_dias_disfrute.php';

$diasDisfruteModelo = new DiasDisfrute($conexion);
$fechaActualCaracas = new DateTimeImmutable('today', new DateTimeZone('America/Caracas'));
$hoyLocal = $fechaActualCaracas->format('Y-m-d');
$hoyVisible = $fechaActualCaracas->format('d/m/Y');
// Usuario Estándar: el select solo contiene a su propio trabajador.
$trabajadores = $diasDisfruteModelo->listarTrabajadoresDiasDisfrute(filtroTrabajadorSesion());
$soloPropio = esUsuarioEstandar() && count($trabajadores) === 1 && !empty($trabajadores[0]['elegible_disfrute']);

// Los días que le corresponden a cualquier trabajador son los que indique
// el convenio colectivo vigente; ya no hay un valor fijo para todos.
$diasConvenioVigente = $diasDisfruteModelo->calcularDiasDisfrute();
$diasConvenioTexto = $diasConvenioVigente !== null
    ? $diasConvenioVigente . ' días hábiles'
    : 'Sin convenio vigente registrado';

$old = $_SESSION['old'] ?? null;
$errores = $_SESSION['errores'] ?? [];
$exito = $_SESSION['exito'] ?? '';

unset($_SESSION['errores'], $_SESSION['exito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dias de Disfrute</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <style>
        #formdisfrute .contenedor-botones {
            justify-content: center;
        }
        #formdisfrute .btn-accion {
            flex: 0 0 auto;
            width: 180px;
        }
        .campo-auto {
            background-color: rgba(255, 255, 255, 0.1);
            cursor: not-allowed;
        }
        .campo-ok {
            color: #ccffcc;
        }
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_dias_disfrute.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver
        </a>
    <div class="form-card">

        <form id="formdisfrute" method="POST" action="../controladores/ctrl_dias_disfrute.php">

            <div class="form-grid full-width">
                <h2>DIAS DE DISFRUTE</h2>

                <?php if ($diasConvenioVigente === null): ?>
                    <div class="full-width" style="color:#ffd7d7;background:rgba(180,35,24,.25);border:1px solid rgba(180,35,24,.5);border-radius:8px;padding:10px 14px;margin-bottom:10px;">
                        No hay un convenio vigente registrado. Debe registrar o actualizar el convenio colectivo antes de poder registrar días de disfrute.
                    </div>
                <?php endif; ?>

                <!-- 1. Selección de trabajadores activos -->
                <div class="field full-width">
                    <label>Seleccionar Trabajador</label>
                    <select name="id_trabajador" id="id_trabajador" required>
                        <?php if (!$soloPropio): ?>
                        <option value="">Seleccione un trabajador...</option>
                        <?php endif; ?>
                        <?php foreach ($trabajadores as $t): ?>
                            <?php
                                $elegible = !empty($t['elegible_disfrute']);
                                $motivoNoElegible = $t['motivo_inelegibilidad'] ?? '';
                                $seleccionado = $soloPropio || ($old && ($old['id_trabajador'] ?? '') == $t['id_trabajador']);
                            ?>
                            <option value="<?= (int) $t['id_trabajador'] ?>"
                                <?= !$elegible ? 'disabled' : '' ?>
                                <?= ($seleccionado && $elegible) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['cedula'] . ' - ' . $t['nombre_completo'], ENT_QUOTES, 'UTF-8') ?><?= !$elegible ? ' — No elegible: ' . htmlspecialchars($motivoNoElegible, ENT_QUOTES, 'UTF-8') : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>

                <!-- 2. Fecha actual -->
                <div class="field">
                    <label>Fecha De Emision</label>
                    <input type="text" value="<?= htmlspecialchars($hoyVisible, ENT_QUOTES, 'UTF-8') ?>" readonly>
                </div>

                <!-- 3. Nombre completo (auto-rellenado) -->
                <div class="field full-width">
                    <label>Apellido y Nombres del Trabajador(a)</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['nombre_completo'] ?? '') : '' ?>"
                           readonly placeholder="Se auto-rellena al seleccionar trabajador...">
                </div>

                <!-- 4. Cedula (auto-rellenado) -->
                <div class="field">
                    <label>N° Cedula de Identidad</label>
                    <input type="text" id="cedula_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['cedula'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 5. Cargo (auto-rellenado) -->
                <div class="field">
                    <label>Cargo del Solicitante</label>
                    <input type="text" id="cargo_display" name="cargo_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['cargo'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 5b. Cantidad de días de disfrute -->
                <div class="field">
                    <label>Días de disfrute que le corresponden</label>
                    <input type="text" id="dias_correspondientes_display" class="campo-auto"
                           value="<?= htmlspecialchars($diasConvenioTexto, ENT_QUOTES, 'UTF-8') ?>" readonly>
                </div>

                <!-- 6. Fecha de Inicio: se fija con la fecha actual de la solicitud -->
                <div class="field">
                    <label>Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($hoyLocal, ENT_QUOTES, 'UTF-8') ?>"
                           min="<?= htmlspecialchars($hoyLocal, ENT_QUOTES, 'UTF-8') ?>"
                           max="<?= htmlspecialchars($hoyLocal, ENT_QUOTES, 'UTF-8') ?>" readonly required>
                </div>

                <!-- 7. Fecha de Finalización: se calcula cuando se apruebe -->
                <div class="field">
                    <label>Fecha de Finalización</label>
                    <input type="text" name="fecha_finalizacion_display" value="Se calculará al aprobar la solicitud" readonly class="campo-auto">
                </div>

                <!-- 8. Descripcion / Motivo -->
                <div class="field full-width">
                    <label>Descripcion / Motivo</label>
                    <textarea name="descripcion" rows="3" required
                              placeholder="Detalle del motivo de los dias de disfrute..."><?= htmlspecialchars($old['descripcion'] ?? '') ?></textarea>
                </div>



                <!-- 12. Permiso remunerado -->
                <div class="field">
                    <label>Permiso</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="tipo_remuneracion" value="remunerado" required <?= (($old['tipo_remuneracion'] ?? '') === 'remunerado') ? 'checked' : '' ?>>
                            Remunerado
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="tipo_remuneracion" value="no_remunerado" <?= (($old['tipo_remuneracion'] ?? '') === 'no_remunerado') ? 'checked' : '' ?>>
                            No Remunerado
                        </label>
                    </div>
                </div>

                <!-- 13. Se requiere suplente -->
                <div class="field">
                    <label>Se requiere suplente</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="requiere_suplente" value="si" required <?= (($old['requiere_suplente'] ?? '') === 'si') ? 'checked' : '' ?>>
                            Si
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="requiere_suplente" value="no" <?= (($old['requiere_suplente'] ?? '') === 'no') ? 'checked' : '' ?>>
                            No
                        </label>
                    </div>

                </div>



                <!-- 15. Observaciones -->
                <div class="field full-width">
                    <label>Observaciones</label>
                    <textarea name="observaciones" rows="3" required><?= htmlspecialchars($old['observaciones'] ?? '') ?></textarea>
                </div>



            </div>

            <div class="form-actions full-width">
                <button type="submit" name="accion" value="limpiar" class="btn-secundario">
                    Limpiar
                </button>
                <button type="submit" name="accion" value="guardar" class="btn-guardar" <?= $diasConvenioVigente === null ? 'disabled title="No hay un convenio vigente registrado."' : '' ?>>
                    Guardar
                </button>
            </div>

        </form>

    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectTrabajador = document.getElementById('id_trabajador');
    var nombreCompleto = document.getElementById('nombre_completo');
    var cedulaDisplay = document.getElementById('cedula_display');
    var cargoDisplay = document.getElementById('cargo_display');
    var diasDisplay = document.getElementById('dias_correspondientes_display');
    var diasConvenioTexto = <?= json_encode($diasConvenioTexto, JSON_UNESCAPED_UNICODE) ?>;

    if (diasDisplay) diasDisplay.value = diasConvenioTexto;

    selectTrabajador.addEventListener('change', function() {
        var id = this.value;
        if (!id) {
            nombreCompleto.value = '';
            cedulaDisplay.value = '';
            cargoDisplay.value = '';
            if (diasDisplay) diasDisplay.value = diasConvenioTexto;
            return;
        }

        fetch('../ajax/ajax_constancia_trabajador.php?id_trabajador=' + encodeURIComponent(id))
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (data.success) {
                    nombreCompleto.value = data.nombre_completo || '';
                    cedulaDisplay.value = data.cedula || '';
                    cargoDisplay.value = data.cargo || '';
                    if (diasDisplay) diasDisplay.value = diasConvenioTexto;
                    nombreCompleto.classList.add('campo-ok');
                    setTimeout(function() { nombreCompleto.classList.remove('campo-ok'); }, 1500);
                } else {
                    FundaciteAlerta.mostrar('No se encontraron datos del trabajador.', 'warning');
                }
            })
            .catch(function(err) {
                console.error('Error AJAX:', err);
                FundaciteAlerta.mostrar('Error al consultar datos del trabajador.', 'error');
            });
    });

    if (selectTrabajador.value) {
        selectTrabajador.dispatchEvent(new Event('change'));
    }
});
</script>



<script src="/FUNDACITE/vistas/js/valid_dias_disfrute.js"></script>
</body>
</html>
