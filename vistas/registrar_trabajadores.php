<?php
include_once "includes/guardian.php";

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Al entrar desde el menú a un registro nuevo, descartar el borrador que quedó
// esperando por el contrato. El enlace "Volver" del contrato lleva flujo=contrato
// y conserva esos datos intencionalmente.
$idTrabajadorEdicion = intval($_GET['id'] ?? 0);
$flujoContrato = $_GET['flujo'] ?? '';
$vieneDeFlujoContrato = $flujoContrato === 'contrato';
if ($idTrabajadorEdicion === 0 && !$vieneDeFlujoContrato && isset($_SESSION['trabajador_pendiente'])) {
    unset(
        $_SESSION['trabajador_pendiente'],
        $_SESSION['token_trabajador_pendiente'],
        $_SESSION['old_input'],
        $_SESSION['old_contrato']
    );
}

$errores = $_SESSION['error_registro'] ?? [];
$old = $_SESSION['old_input'] ?? [];
$exito_registro = $_SESSION['exito_registro'] ?? '';

unset(
    $_SESSION['error_registro'],
    $_SESSION['old_input'],
    $_SESSION['exito_registro']
);

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';

$trabajador = new Trabajador($conexion);
$direcciones = $trabajador->listarDirecciones();

// Obtenemos los cargos de la base de datos para el desplegable
$cargos = method_exists($trabajador, 'listarCargos') ? $trabajador->listarCargos() : [];

if ($idTrabajadorEdicion > 0) {
    $datosEdicion = $trabajador->obtenerTrabajadorPorId($idTrabajadorEdicion);
    if ($datosEdicion && empty($old)) {
        $old = $datosEdicion;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo ($idTrabajadorEdicion > 0) ? 'Modificar Datos del Trabajador' : 'Datos del Trabajador'; ?></title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/jquery.min.js"></script>
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <script src="/FUNDACITE/vistas/js/ajax_direccion.js"></script>
</head>
<body>

<?php include "includes/alerta.php"; ?>

<?php include "includes/layout.php"; ?>

<!-- FORMULARIO DE REGISTRO / EDICIÓN PASO 1 -->
<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_trabajadores.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver 
        </a>
    <form class="form-card" id="formPersonas" method="POST" action="http://localhost/FUNDACITE/controladores/ctrl_trabajador.php">
        
        <!-- CAMPOS OCULTOS INTELIGENTES -->
        <input type="hidden" name="accion" value="<?php echo ($idTrabajadorEdicion > 0) ? 'actualizar' : 'guardar'; ?>">
        <?php if ($idTrabajadorEdicion > 0): ?>
            <input type="hidden" name="id_trabajador" value="<?php echo $idTrabajadorEdicion; ?>">
        <?php endif; ?>
        <input type="hidden" name="origen_flujo" value="<?php echo htmlspecialchars($flujoContrato); ?>">

        <div class="form-grid">
            <h2 style="text-align: center; margin-bottom: 10px;" class="full-width">
                <?php echo ($idTrabajadorEdicion > 0) ? 'Modificar Datos del Trabajador' : 'Datos del Trabajador'; ?>
            </h2>

            <h3 class="form-section-title full-width">Datos personales</h3>

            <!-- Nombres -->
            <div class="field">
                <label>Nombres</label>
                <input type="text" name="nombres" id="nombres" maxlength="50" placeholder="Ingrese nombres"
                        value="<?php echo htmlspecialchars($old['nombres'] ?? ''); ?>">
            </div>

            <!-- Apellidos -->
            <div class="field">
                <label>Apellidos</label>
                <input type="text" name="apellidos" id="apellidos" maxlength="50" placeholder="Ingrese apellidos"
                        value="<?php echo htmlspecialchars($old['apellidos'] ?? ''); ?>">
            </div>

            <!-- Tipo Documento -->
            <div class="field">
                <label>Tipo de Documento</label>
                <select name="tipoDoc" id="tipoDoc">
                    <option value="" disabled selected>Seleccione...</option>
                    <option value="V" <?php echo (isset($old['tipoDoc']) && ($old['tipoDoc'] == 'V' || $old['tipoDoc'] == 'Cédula')) ? 'selected' : ''; ?>>Venezolano (Cédula)</option>
                    <option value="E" <?php echo (isset($old['tipoDoc']) && ($old['tipoDoc'] == 'E' || $old['tipoDoc'] == 'Cédula de Extranjería')) ? 'selected' : ''; ?>>Extranjero (Cédula de Extranjería)</option>
                </select>
            </div>

            <!-- Cédula -->
            <div class="field">
                <label>Cédula</label>
                <input type="text" name="cedula" id="cedula" maxlength="8" placeholder="Ingrese cédula"
                        value="<?php echo htmlspecialchars($old['cedula'] ?? ''); ?>">
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
                </div>
            </div>

            <!-- Estado Civil -->
            <div class="field">
                <label>Estado Civil</label>
                <select name="estadoCivil" id="estadoCivil">
                    <option value="" disabled selected>Seleccione...</option>
                    <option value="Soltero(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Soltero(a)') ? 'selected' : ''; ?>>Soltero(a)</option>
                    <option value="Casado(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Casado(a)') ? 'selected' : ''; ?>>Casado(a)</option>
                    <option value="Divorciado(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Divorciado(a)') ? 'selected' : ''; ?>>Divorciado(a)</option>
                    <option value="Viudo(a)" <?php echo (isset($old['estadoCivil']) && $old['estadoCivil'] == 'Viudo(a)') ? 'selected' : ''; ?>>Viudo(a)</option>
                </select>
            </div>

            <!-- Fecha de Nacimiento -->
            <div class="field">
                <label>Fecha de Nacimiento</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($old['fecha'] ?? ''); ?>">
            </div>

            <!-- Edad (decorativo) -->
            <div class="field">
                <label>Edad</label>
                <input type="text" id="edad" placeholder="Se calculará automáticamente" readonly>
            </div>

            <h3 class="form-section-title full-width">Contacto</h3>

            <!-- Correo Electrónico -->
            <div class="field">
                <label>Correo Electrónico</label>
                <input type="text" name="correoElectronico" id="correoElectronico" maxlength="100" placeholder="Ingrese correo electrónico"
                        value="<?php echo htmlspecialchars($old['correoElectronico'] ?? ''); ?>">
            </div>

            <!-- Número de Teléfono -->
            <div class="field">
                <label>Número de Teléfono</label>
                <input type="text" name="numeroTelefono" id="numeroTelefono" maxlength="11" placeholder="Ingrese Número de Teléfono"
                        value="<?php echo htmlspecialchars($old['numeroTelefono'] ?? ''); ?>">
            </div>

            <h3 class="form-section-title full-width">Datos laborales</h3>

            <!-- Cantidad de Hijos -->
            <div class="field">
                <label for="hijos">Cantidad de Hijos</label>
                <input type="number" name="hijos" id="hijos" min="0" max="30" step="1" required
                        value="<?php echo htmlspecialchars($old['hijos'] ?? '0'); ?>">
            </div>

            <!-- Años de Servicio -->
            <div class="field">
                <label for="anos_servicio_administracion_publica">Años de Servicio en la Administración Pública</label>
                <input type="number" name="anos_servicio_administracion_publica" id="anos_servicio_administracion_publica" min="0" max="70" step="1" required
                        value="<?php echo htmlspecialchars($old['anos_servicio_administracion_publica'] ?? '0'); ?>">
            </div>

            <!-- Nivel de Educación -->
            <div class="field">
                <label for="nivel_educacion">Nivel de Educación</label>
                <select name="nivel_educacion" id="nivel_educacion" required>
                    <option value="" disabled <?php echo empty($old['nivel_educacion']) ? 'selected' : ''; ?>>Seleccione el nivel de educación...</option>
                    <?php foreach (['Bachiller','Técnico Superior Universitario', 'Profesional', 'Especialista', 'Maestría', 'Doctor'] as $nivel): ?>
                        <option value="<?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($old['nivel_educacion'] ?? '') === $nivel ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Asignar Cargo -->
            <div class="field">
                <label>Cargo a Asignar</label>
                <select name="id_cargo" id="id_cargo" required>
                    <option value="" disabled selected>Seleccione un cargo...</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id_cargo']; ?>" 
                            <?php echo (isset($old['id_cargo']) && $old['id_cargo'] == $cargo['id_cargo']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cargo['nombre_cargo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Estatus Laboral -->
            <div class="field">
                <label>Estatus Laboral</label>
                <select name="estatus_laboral" required>
                    <option value="" disabled selected>Seleccione</option>
                    <option value="Activo" <?php echo (isset($old['estatus_laboral']) && $old['estatus_laboral'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Jubilado" <?php echo (isset($old['estatus_laboral']) && $old['estatus_laboral'] == 'Jubilado') ? 'selected' : ''; ?>>Jubilado</option>
                    <option value="Inactivo" <?php echo (isset($old['estatus_laboral']) && ($old['estatus_laboral'] == 'Inactivo' || $old['estatus_laboral'] == 'Retirado' || $old['estatus_laboral'] == 'Suspendido')) ? 'selected' : ''; ?>>Inactivo / Retirado / Suspendido</option>
                </select>
            </div>

            <h3 class="form-section-title full-width">Dirección</h3>

            <!-- Dirección: Estado -->
            <div class="field">
                <label>Estado</label>
                <select name="cod_est" id="selectEstado" data-selected="<?php echo htmlspecialchars($old['cod_est'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <option value="" disabled selected>Seleccione un estado</option>
                </select>
            </div>

            <!-- Dirección: Municipio -->
            <div class="field">
                <label>Municipio</label>
                <select name="cod_muni" id="selectMunicipio" data-selected="<?php echo htmlspecialchars($old['cod_muni'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <option value="" disabled selected>Seleccione un municipio</option>
                </select>
            </div>

            <!-- Dirección: Parroquia -->
            <div class="field">
                <label>Parroquia</label>
                <select name="cod_par" id="selectParroquia" data-selected="<?php echo htmlspecialchars($old['cod_par'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <option value="" disabled selected>Seleccione una parroquia</option>
                </select>
            </div>

            <!-- Dirección exacta -->
            <div class="field full-width">
                <label>Dirección</label>
                <textarea name="direccion_texto" id="direccion_texto" maxlength="255"
                    placeholder="Ingrese dirección exacta (calle, avenida, casa, referencia, etc.)"><?php echo htmlspecialchars($old['direccion_texto'] ?? ''); ?></textarea>
            </div>

            <!-- BOTONES DE ACCIÓN / RETORNO -->
            <div class="form-actions full-width" style="display: flex; justify-content: space-between; align-items: center;">
                <?php if ($idTrabajadorEdicion > 0 && $flujoContrato === 'contrato'): ?>
                    <a href="registrar_contrato.php?id_trabajador=<?php echo $idTrabajadorEdicion; ?>" style="color: #bbb; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                        <i class="bi bi-arrow-left"></i> Volver al Contrato sin guardar
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <button type="submit" class="btn-guardar" style="display: flex; align-items: center; gap: 8px;">
                    <?php echo ($idTrabajadorEdicion > 0 && $flujoContrato === 'contrato') ? 'Guardar cambios y volver al Contrato' : 'Siguiente: Registrar Contrato'; ?>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<script src="/FUNDACITE/vistas/js/valid_trabajadores.js?v=20260727"></script>

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

<?php if (!empty($exito_registro)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var alertDiv = document.getElementById('customAlert');
    var msg = document.getElementById('alertMessage');

    msg.textContent = <?php echo json_encode($exito_registro); ?>;
    alertDiv.classList.remove('hidden');

    const boton = document.querySelector(".alert-box button");
    if (boton) {
        boton.onclick = function() {
            window.location.href = "/FUNDACITE/vistas/registrar_contrato.php";
        };
    }
});
</script>
<?php endif; ?>
</body>
</html>
