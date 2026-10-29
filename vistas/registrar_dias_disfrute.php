<?php
session_start();
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_dias_disfrute.php';

$diasDisfruteModelo = new DiasDisfrute($conexion);
$trabajadores = $diasDisfruteModelo->listarTrabajadoresDiasDisfrute();

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
    <div class="form-card">

        <form id="formdisfrute" method="POST" action="../controladores/ctrl_dias_disfrute.php">

            <div class="form-grid full-width">
                <h2>DIAS DE DISFRUTE</h2>

                <!-- 1. SELECT de trabajadores con validación de elegibilidad -->
                <div class="field full-width">
                    <label>Seleccionar Trabajador</label>
                    <select name="id_trabajador" id="id_trabajador" required>
                        <option value="">Seleccione un trabajador...</option>
                        <?php foreach ($trabajadores as $t): ?>
                            <?php
                                $elegible = !empty($t['elegible_disfrute']);
                                $motivoNoElegible = $t['motivo_inelegibilidad'] ?? '';
                                $seleccionado = $old && ($old['id_trabajador'] ?? '') == $t['id_trabajador'];
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
                    <input type="text" value="<?= date('d/m/Y') ?>" readonly>
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

                <!-- 6. Fecha de Inicio -->
                <div class="field">
                    <label>Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" required>
                </div>

                <!-- 7. Fecha de Finalizacion -->
                <div class="field">
                    <label>Fecha de Finalizacion</label>
                    <input type="date" name="fecha_finalizacion">
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
                            <input type="radio" name="tipo_remuneracion" value="remunerado">
                            Remunerado
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="tipo_remuneracion" value="no_remunerado">
                            No Remunerado
                        </label>
                    </div>
                </div>

                <!-- 13. Se requiere suplente -->
                <div class="field">
                    <label>Se requiere suplente</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="requiere_suplente" value="si">
                            Si
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="requiere_suplente" value="no">
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
                <button type="submit" name="accion" value="guardar" class="btn-guardar">
                    Guardar
                </button>
            </div>

        </form>

    </div>
</div>

<?php include "includes/alerta.php"; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectTrabajador = document.getElementById('id_trabajador');
    var nombreCompleto   = document.getElementById('nombre_completo');
    var cedulaDisplay    = document.getElementById('cedula_display');
    var cargoDisplay     = document.getElementById('cargo_display');

    selectTrabajador.addEventListener('change', function() {
        var id = this.value;

        if (!id) {
            nombreCompleto.value = '';
            cedulaDisplay.value  = '';
            cargoDisplay.value   = '';
            return;
        }

        fetch('../ajax/ajax_constancia_trabajador.php?id_trabajador=' + encodeURIComponent(id))
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (data.success) {
                    nombreCompleto.value = data.nombre_completo;
                    cedulaDisplay.value  = data.cedula;
                    cargoDisplay.value   = data.cargo;

                    nombreCompleto.classList.add('campo-ok');
                    setTimeout(function() { nombreCompleto.classList.remove('campo-ok'); }, 1500);
                } else {
                    alert('No se encontraron datos del trabajador.');
                }
            })
            .catch(function(err) {
                console.error('Error AJAX:', err);
                alert('Error al consultar datos del trabajador.');
            });
    });

    if (selectTrabajador.value) {
        selectTrabajador.dispatchEvent(new Event('change'));
    }
});
</script>

<?php if (!empty($errores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('alertMessage').textContent = <?= json_encode(implode("\n", $errores)) ?>;
        document.getElementById('customAlert').classList.remove('hidden');
    });
</script>
<?php endif; ?>

<?php if (!empty($exito)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('alertMessage').textContent = <?= json_encode($exito) ?>;
        document.getElementById('customAlert').classList.remove('hidden');
    });
</script>
<?php endif; ?>

<script src="/FUNDACITE/vistas/js/valid_dias_disfrute.js"></script>
</body>
</html>
