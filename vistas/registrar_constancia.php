<?php
session_start();
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_constancia.php';

$constanciaObj = new clase_constancia($conexion);
$trabajadores = $constanciaObj->listarTrabajadoresActivos();

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
                    <label>1. Seleccionar Trabajador</label>
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
                    <label>2. Fecha de Emisión</label>
                    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- 3. Nombre completo (auto-rellenado) -->
                <div class="field full-width">
                    <label>3. Apellido y Nombres del Trabajador(a)</label>
                    <input type="text" id="nombre_completo" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['nombre_completo'] ?? '') : '' ?>"
                           readonly placeholder="Se auto-rellena al seleccionar trabajador...">
                </div>

                <!-- 4. Cédula (auto-rellenado) -->
                <div class="field">
                    <label>4. N° Cédula de Identidad</label>
                    <input type="text" id="cedula_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['cedula'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 5. Cargo (auto-rellenado) -->
                <div class="field">
                    <label>5. Cargo Actual</label>
                    <input type="text" id="cargo_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['cargo'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 6. Fecha de ingreso (auto-rellenado) -->
                <div class="field">
                    <label>6. Fecha de Ingreso</label>
                    <input type="text" id="fecha_ingreso_display" class="campo-auto"
                           value="<?= $old ? htmlspecialchars($old['fecha_ingreso'] ?? '') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 7. Salario (auto-rellenado) -->
                <div class="field">
                    <label>7. Salario Mensual (Bs.)</label>
                    <input type="text" id="salario_display" class="campo-auto"
                           value="<?= $old && isset($old['salario_monto']) ? number_format($old['salario_monto'], 2, ',', '.') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 8. Tipo de personal -->
                <div class="field">
                    <label>8. Tipo de Personal</label>
                    <select name="tipo_personal" required>
                        <option value="" disabled selected>Seleccione...</option>
                        <option value="Fijo">Empleado Fijo</option>
                        <option value="Contratado">Contratado</option>
                        <option value="Obrero">Obrero</option>
                        <option value="Empleado">Empleado</option>
                    </select>
                </div>

                <!-- 9. Nombre Director -->
                <div class="field full-width">
                    <label>9. Nombre del Director de Departamento (quien firma)</label>
                    <input type="text" name="nombre_director"
                           value="<?= htmlspecialchars($_POST['nombre_director'] ?? '') ?>"
                           required placeholder="Ej: MSc. KARLA Y. MONTANEZ O">
                </div>

                <!-- 10. Motivo -->
                <div class="field full-width">
                    <label>10. Motivo de la Solicitud</label>
                    <textarea name="motivo" rows="3" required
                              placeholder="Ej: A solicitud de la parte interesada..."><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>
                </div>

                <!-- 11. Firma trabajador -->
                <div class="field">
                    <label>11. Firma del Trabajador(a)</label>
                    <input type="text" readonly class="campo-auto">
                </div>

            </div>

            <div class="contenedor-botones full-width">
                <button type="submit" name="accion" value="guardar" class="btn-accion">
                    Guardar
                </button>
                <button type="submit" name="accion" value="limpiar" class="btn-accion btn-eliminar">
                    Limpiar
                </button>
            </div>

        </form>

    </div>
</div>

<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="document.getElementById('customAlert').classList.add('hidden')">Cerrar</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectTrabajador = document.getElementById('id_trabajador');
    var nombreCompleto   = document.getElementById('nombre_completo');
    var cedulaDisplay    = document.getElementById('cedula_display');
    var cargoDisplay     = document.getElementById('cargo_display');
    var fechaIngresoDisp = document.getElementById('fecha_ingreso_display');
    var salarioDisplay   = document.getElementById('salario_display');

    selectTrabajador.addEventListener('change', function() {
        var id = this.value;

        if (!id) {
            nombreCompleto.value = '';
            cedulaDisplay.value  = '';
            cargoDisplay.value   = '';
            fechaIngresoDisp.value = '';
            salarioDisplay.value = '';
            return;
        }

        fetch('../ajax/ajax_constancia_trabajador.php?id_trabajador=' + encodeURIComponent(id))
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (data.success) {
                    nombreCompleto.value   = data.nombre_completo;
                    cedulaDisplay.value    = data.cedula;
                    cargoDisplay.value     = data.cargo;
                    fechaIngresoDisp.value = data.fecha_ingreso;
                    salarioDisplay.value   = data.salario_monto
                        ? parseFloat(data.salario_monto).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                        : 'No registrado';

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
