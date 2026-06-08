<?php
session_start();
$errores  = $_SESSION['errores']  ?? [];
$old      = $_SESSION['old_input'] ?? [];
$exito    = $_SESSION['exito']    ?? '';
unset($_SESSION['errores'], $_SESSION['old_input'], $_SESSION['exito']);

// Cargar cargos desde la tabla CARGO
require_once '../conexion.php'; // Ajusta la ruta según la ubicación
$cargos = [];
$sqlCargos = "SELECT id_cargo, nombre_cargo FROM CARGO ORDER BY nombre_cargo";
$resultCargos = $conexion->query($sqlCargos);
if ($resultCargos && $resultCargos->num_rows > 0) {
    while ($row = $resultCargos->fetch_assoc()) {
        $cargos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Trabajadores</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <img src="logo.png" alt="Logo">
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar" style="display: flex; flex-direction: column; justify-content: space-between; height: calc(102vh - 70px);">
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li><a href="dashboard.html" class="submenu-link"><i class="bi bi-house-door-fill"></i> <b>INICIO</b></a></li>
        <li><a href="lista_personas.html" class="submenu-link"><i class="bi bi-people-fill"></i> <b>PERSONAS</b></a></li>
        <li><a href="lista_trabajadores.html" class="submenu-link"><i class="bi bi-person-workspace"></i> <b>TRABAJADORES</b></a></li>
        <li><a href="lista_cargos.html" class="submenu-link"><i class="bi bi-briefcase-fill"></i> <b>CARGO</b></a></li>
        <li><a href="lista_direcciones.html" class="submenu-link"><i class="bi bi-geo-alt-fill"></i> <b>DIRECCION</b></a></li>
        <li><a href="lista_contratos.html" class="submenu-link"><i class="bi bi-file-earmark-text-fill"></i> <b>CONTRATOS</b></a></li>
        <li><a href="lista_usuarios.html" class="submenu-link"><i class="bi bi-person-circle"></i> <b>USUARIO</b></a></li>
        <li><a href="lista_solicitudes.html" class="submenu-link"><i class="bi bi-calendar2-check-fill"></i> <b>SOLICITUDES DE DIAS DE DISFRUTE</b></a></li>
    </ul>
    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
        <li><a href="" class="submenu-link"><i class="bi bi-box-arrow-right"></i> <b>CERRAR SESIÓN</b></a></li>
    </ul>
</div>

<!-- FORMULARIO -->
<div class="main">
    <form class="form-card" id="formPersonas" method="POST" action="../procesar_trabajador.php">
        <div class="form-grid">
            <h2 style="text-align: center; margin-bottom: 10px;">Registro de Trabajador</h2>

            <!-- Tipo Documento -->
            <div class="field">
                <label>Tipo de Documento</label>
                <select name="tipoDoc" id="tipoDoc">
                    <option value="">Seleccione...</option>
                    <option value="V" <?php echo (isset($old['tipoDoc']) && $old['tipoDoc'] == 'V') ? 'selected' : ''; ?>>Venezolano (Cédula)</option>
                    <option value="E" <?php echo (isset($old['tipoDoc']) && $old['tipoDoc'] == 'E') ? 'selected' : ''; ?>>Extranjero (Cédula de Extranjería)</option>
                </select>
            </div>

            <!-- Edad (no se envía, solo decorativo) -->
            <div class="field">
                <label>Edad</label>
                <input type="text" id="edad" placeholder="Se calculará automáticamente" readonly>
            </div>

            <!-- Cédula -->
            <div class="field">
                <label>Cédula</label>
                <input type="text" name="cedula" id="cedula" maxlength="8" placeholder="Ingrese cédula"
                       value="<?php echo htmlspecialchars($old['cedula'] ?? ''); ?>">
            </div>

            <!-- Estado Civil -->
            <div class="field">
                <label>Estado Civil</label>
                <select name="estadoCivil" id="estadoCivil">
                    <option value="">Seleccione...</option>
                    <option value="Soltero(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Soltero(a)') ? 'selected' : ''; ?>>Soltero(a)</option>
                    <option value="Casado(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Casado(a)') ? 'selected' : ''; ?>>Casado(a)</option>
                    <option value="Divorciado(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Divorciado(a)') ? 'selected' : ''; ?>>Divorciado(a)</option>
                    <option value="Viudo(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Viudo(a)') ? 'selected' : ''; ?>>Viudo(a)</option>
                    <option value="Concubinato" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Concubinato') ? 'selected' : ''; ?>>Concubinato</option>
                </select>
            </div>

            <!-- Nombres -->
            <div class="field">
                <label>Nombres</label>
                <input type="text" name="nombres" id="nombres" maxlength="50" placeholder="Ingrese nombres"
                       value="<?php echo htmlspecialchars($old['nombres'] ?? ''); ?>">
            </div>

            <!-- Género -->
            <div class="field">
                <label>Género</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="genero" value="Masculino" <?php echo (isset($old['genero']) && $old['genero'] == 'Masculino') ? 'checked' : ''; ?>>
                        <span>Masculino</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="genero" value="Femenino" <?php echo (isset($old['genero']) && $old['genero'] == 'Femenino') ? 'checked' : ''; ?>>
                        <span>Femenino</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="genero" value="No Binario" <?php echo (isset($old['genero']) && $old['genero'] == 'No Binario') ? 'checked' : ''; ?>>
                        <span>No Binario</span>
                    </label>
                </div>
            </div>

            <!-- Apellidos -->
            <div class="field">
                <label>Apellidos</label>
                <input type="text" name="apellidos" id="apellidos" maxlength="50" placeholder="Ingrese apellidos"
                       value="<?php echo htmlspecialchars($old['apellidos'] ?? ''); ?>">
            </div>

            <!-- Correo Electrónico -->
            <div class="field">
                <label>Correo Electrónico</label>
                <input type="text" name="correoElectronico" id="correoElectronico" maxlength="100" placeholder="Ingrese correo electrónico"
                       value="<?php echo htmlspecialchars($old['correoElectronico'] ?? ''); ?>">
            </div>

            <!-- Fecha de Nacimiento -->
            <div class="field">
                <label>Fecha de Nacimiento</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($old['fecha'] ?? ''); ?>">
            </div>

            <!-- Número de Teléfono -->
            <div class="field">
                <label>Número de Teléfono</label>
                <input type="text" name="numeroTelefono" id="numeroTelefono" maxlength="11" placeholder="Ingrese Número de Teléfono"
                       value="<?php echo htmlspecialchars($old['numeroTelefono'] ?? ''); ?>">
            </div>

            <!-- Fecha de Ingreso -->
            <div class="field">
                <label>Fecha de Ingreso</label>
                <input type="date" name="fecha_ingreso" id="fecha_ingreso" value="<?php echo htmlspecialchars($old['fecha_ingreso'] ?? ''); ?>">
            </div>

            <!-- Cargo (dinámico desde CARGO) -->
            <div class="field">
                <label>Cargo</label>
                <select name="cargo_id">
                    <option value="">Seleccione un cargo</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id_cargo']; ?>"
                            <?php echo (isset($old['cargo_id']) && $old['cargo_id'] == $cargo['id_cargo']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cargo['nombre_cargo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Estatus Laboral -->
            <div class="field">
                <label>Estatus Laboral</label>
                <select name="estatus_laboral">
                    <option value="">Seleccione</option>
                    <option value="Activo" <?php echo (isset($old['estatus_laboral']) && $old['estatus_laboral'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Jubilado" <?php echo (isset($old['estatus_laboral']) && $old['estatus_laboral'] == 'Jubilado') ? 'selected' : ''; ?>>Jubilado</option>
                    <option value="Suspendido" <?php echo (isset($old['estatus_laboral']) && $old['estatus_laboral'] == 'Suspendido') ? 'selected' : ''; ?>>Suspendido</option>
                    <option value="Retirado" <?php echo (isset($old['estatus_laboral']) && $old['estatus_laboral'] == 'Retirado') ? 'selected' : ''; ?>>Retirado (se guarda como Inactivo)</option>
                </select>
            </div>

            <button type="submit" class="btn-guardar full-width">Registrar</button>
        </div>
    </form>
</div>

<script src="/FUNDACITE/vistas/js/valid_personas.js"></script>

<!-- Alertas de errores/éxito -->
<?php if (!empty($errores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode(implode("\n", $errores)); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

<?php if (!empty($exito)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($exito); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>

</body>
</html>