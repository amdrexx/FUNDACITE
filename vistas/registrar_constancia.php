<?php
session_start();
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_constancia.php';

$constanciaModelo = new ConstanciaTrabajo($conexion);
$trabajadores = $constanciaModelo->listarTrabajadoresActivos();

$old = $_SESSION['old_constancia'] ?? null;
$errores = $_SESSION['errores_constancia'] ?? [];
$exito = $_SESSION['exito_constancia'] ?? '';

unset($_SESSION['errores_constancia'], $_SESSION['exito_constancia']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Constancia de Trabajo</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <style>
        #formconstancia .contenedor-botones {
            justify-content: center;
        }
        #formconstancia .btn-accion {
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

        <form id="formconstancia" method="POST" action="../controladores/ctrl_constancia.php">

            <div class="form-grid full-width">
                <h2>CONSTANCIA DE TRABAJO</h2>

                <!-- 1. SELECT de trabajadores -->
                <div class="field full-width">
                    <label>Seleccionar Trabajador</label>
                    <select name="id_trabajador" id="id_trabajador" required>
                        <option value="">Seleccione un trabajador...</option>
                        <?php foreach ($trabajadores as $t): ?>
                            <option value="<?= $t['id_trabajador'] ?>"
                                <?= ($old && ($old['id_trabajador'] ?? '') == $t['id_trabajador']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['cedula'] . ' - ' . $t['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Fecha emisión -->
                <div class="field">
                    <label>Fecha de Emisión</label>
                    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- 3. Nombre completo (auto-rellenado) -->
                <div class="field full-width">
                    <label>Apellido y Nombres del Trabajador(a)</label>
                    <input type="text" id="nombre_completo" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['nombre_completo'] ?? '') : '' ?>"
                           readonly placeholder="Se auto-rellena al seleccionar trabajador...">
                </div>

                <!-- 4. Cédula (auto-rellenado) -->
                <div class="field">
                    <label>N° Cédula de Identidad</label>
                    <input type="text" id="cedula_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['cedula'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 5. Cargo (auto-rellenado) -->
                <div class="field">
                    <label>Cargo Actual</label>
                    <input type="text" id="cargo_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['cargo'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 6. Fecha de ingreso (auto-rellenado) -->
                <div class="field">
                    <label>Fecha de Ingreso</label>
                    <input type="text" id="fecha_primer_contrato_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['fecha_primer_contrato'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 7. Salario (auto-rellenado) -->
                <div class="field">
                    <label>Salario Mensual (Bs.)</label>
                    <input type="text" id="salario_display" class="campo-auto"
                           value="<?= $old && isset($old['salario_monto']) ? number_format($old['salario_monto'], 2, ',', '.') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 8. Tipo de personal -->
                <div class="field">
                    <label>Tipo de Personal</label>
                    <input type="text" id="tipo_personal" name="tipo_personal" class="campo-auto"
                           value="<?= htmlspecialchars($old['tipo_personal'] ?? '') ?>"
                           readonly required placeholder="Se completa al seleccionar trabajador...">
                </div>

                <!-- 9. Motivo -->
                <div class="field full-width">
                    <label>Motivo</label>
                    <textarea name="motivo" rows="3" required
                              placeholder="Ej: Indique el motivo de la constancia..."><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>
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

<script src="/FUNDACITE/vistas/js/valid_constancia.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectTrabajador = document.getElementById('id_trabajador');
    var nombreCompleto   = document.getElementById('nombre_completo');
    var cedulaDisplay    = document.getElementById('cedula_display');
    var cargoDisplay     = document.getElementById('cargo_display');
    var fechaIngresoDisp = document.getElementById('fecha_primer_contrato_display');
    var salarioDisplay   = document.getElementById('salario_display');
    var tipoPersonal     = document.getElementById('tipo_personal');

    selectTrabajador.addEventListener('change', function() {
        var id = this.value;

        if (!id) {
            nombreCompleto.value = '';
            cedulaDisplay.value  = '';
            cargoDisplay.value   = '';
            fechaIngresoDisp.value = '';
            salarioDisplay.value = '';
            tipoPersonal.value = '';
            return;
        }

        fetch('../ajax/ajax_constancia_trabajador.php?id_trabajador=' + encodeURIComponent(id))
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (data.success) {
                    nombreCompleto.value   = data.nombre_completo;
                    cedulaDisplay.value    = data.cedula;
                    cargoDisplay.value     = data.cargo;
                    fechaIngresoDisp.value = data.fecha_primer_contrato || 'Sin contrato registrado';
                    salarioDisplay.value   = data.salario_monto
                        ? parseFloat(data.salario_monto).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                        : 'No registrado';
                    tipoPersonal.value = data.tipo_personal || '';

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

</body>
</html>
