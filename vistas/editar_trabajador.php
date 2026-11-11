<?php
include_once "includes/guardian.php";
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errores = $_SESSION['error_edicion'] ?? [];
$exito   = $_SESSION['exito_edicion'] ?? '';

unset($_SESSION['error_edicion'], $_SESSION['exito_edicion']);

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';
require_once '../modelos/clase_direccion.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$idTrabajador = intval($_GET['id'] ?? 0);

$trabajador = new Trabajador($conexion);
$dato = $trabajador->obtenerTrabajadorPorId($idTrabajador);
$hijosRegistrados = $trabajador->listarHijosTrabajador($idTrabajador);

if (!$dato) {
    die("Trabajador no encontrado.");
}

registrarBitacora($conexion, 'Trabajadores', 'Consultar', "Consultó al trabajador ID $idTrabajador para edición.");


// ── Precarga de Estado/Municipio/Parroquia YA SELECCIONADOS ──
$direccionModelo = new Direccion();

$estados    = $direccionModelo->listarEstados();
$municipios = !empty($dato['cod_est'])  ? $direccionModelo->listarMunicipios($dato['cod_est'])   : [];
$parroquias = !empty($dato['cod_muni']) ? $direccionModelo->listarParroquias($dato['cod_muni']) : [];

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
    <script src="/FUNDACITE/vistas/js/jquery.min.js?v=20260727"></script>
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js?v=20260727"></script>
    <script src="/FUNDACITE/vistas/js/editar_direccion.js?v=20260727d"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<?php if (isset($_GET['debug'])): ?>
<div style="background:#222;color:#0f0;padding:12px;margin:10px auto;max-width:900px;font-family:monospace;font-size:13px;border-radius:6px;">
    <strong>DEBUG — datos crudos devueltos por obtenerTrabajadorPorId():</strong>
    <pre style="white-space:pre-wrap;"><?php echo htmlspecialchars(print_r($dato, true)); ?></pre>
</div>
<?php endif; ?>



<div class="main">
        <div class="worker-page">
        <a class="worker-back" href="lista_trabajadores.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver 
        </a>
  <form class="form-card" id="formEditarTrabajador" method="POST" action="../controladores/ctrl_trabajador.php">
        <div class="form-grid">

            <h2 class="full-width" style="text-align:center; margin-bottom:10px;">
                Editar Trabajador
            </h2>

            <input type="hidden" name="editar_trabajador" value="1">
            <input type="hidden" name="id_trabajador" value="<?php echo htmlspecialchars($dato['id_trabajador']); ?>">
            <input type="hidden" name="id_dir" value="<?php echo htmlspecialchars($dato['id_dir'] ?? ''); ?>">

        <h3 class="form-section-title full-width">Datos personales</h3>
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
                <label>Género</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input
                            type="radio"
                            name="genero"
                            value="Masculino"
                            <?php echo (($dato['genero'] ?? '') == 'Masculino') ? 'checked' : ''; ?>
                            onclick="return false;">
                        <span>Masculino</span>
                    </label>

                    <label class="radio-option">
                        <input
                            type="radio"
                            name="genero"
                            value="Femenino"
                            <?php echo (($dato['genero'] ?? '') == 'Femenino') ? 'checked' : ''; ?>
                            onclick="return false;">
                        <span>Femenino</span>
                    </label>
                </div>
            </div>

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
                <label>Fecha de Nacimiento</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['fecha'] ?? ''); ?>" readonly>
            </div>

            <div class="field">
                <label>Edad</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['edad'] ?? ''); ?>" readonly>
            </div>

            
            <div class="field full-width" id="seccionHijos">
                <label>¿El trabajador tiene hijos?</label>
                <div class="radio-group">
                    <label class="radio-option"><input type="radio" name="tiene_hijos" value="si" <?php echo !empty($hijosRegistrados) ? 'checked' : ''; ?>><span>Sí</span></label>
                    <label class="radio-option"><input type="radio" name="tiene_hijos" value="no" <?php echo empty($hijosRegistrados) ? 'checked' : ''; ?>><span>No</span></label>
                </div>
                <div id="panelHijos" style="display:none; margin-top:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <strong>Fecha de nacimiento de cada hijo</strong>
                        <button type="button" class="btn-guardar" id="agregarHijo">+ Agregar hijo</button>
                    </div>
                    <div id="listaEdadesHijos" class="form-grid" style="margin-top:10px;">
                        <?php foreach ($hijosRegistrados as $hijo): ?>
                        <div class="field fila-hijo">
                           
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                                <input type="date" name="fechas_nacimiento_hijos[]" required value="<?php echo htmlspecialchars((string)($hijo['fecha_nacimiento'] ?? '')); ?>">
                                <input type="text" class="edad-hijo-calculada" placeholder="Edad" readonly style="max-width:110px">
                                <button type="button" class="btn-eliminar-hijo" aria-label="Quitar hijo">Quitar</button>
                            </div>
                            <small class="clasificacion-hijo"></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                  
                </div>
            </div>

            <h3 class="form-section-title full-width">Contacto</h3>

            <div class="field">
                <label>Correo Electrónico</label>
                <input type="email" name="correoElectronico" value="<?php echo htmlspecialchars($dato['correoElectronico'] ?? ''); ?>" required>
            </div>

            <div class="field">
                <label>Número de Teléfono</label>
                <input type="text" name="numeroTelefono" value="<?php echo htmlspecialchars($dato['numeroTelefono'] ?? ''); ?>" required>
            </div>


            <h3 class="form-section-title full-width">Datos laborales</h3>
            
            <div class="field">
                <label for="anos_servicio_administracion_publica">Años de Servicio en la Administración Pública</label>
                <input type="number" name="anos_servicio_administracion_publica" id="anos_servicio_administracion_publica" min="0" max="70" step="1" required
                       value="<?php echo htmlspecialchars($dato['anos_servicio_administracion_publica'] ?? '0'); ?>">
            </div>

            <div class="field">
                <label for="nivel_educacion">Nivel de Educación</label>
                <select name="nivel_educacion" id="nivel_educacion" required>
                    <option value="" disabled <?php echo empty($dato['nivel_educacion']) ? 'selected' : ''; ?>>Seleccione el nivel de educación...</option>
                    <?php foreach (['Bachiller', 'Técnico Superior Universitario', 'Profesional', 'Especialista', 'Maestría', 'Doctor'] as $nivel): ?>
                        <option value="<?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($dato['nivel_educacion'] ?? '') === $nivel ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8'); ?>
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
                    <option value="Inactivo" <?php echo (($dato['estatus_laboral'] ?? '') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    
                </select>
            </div>

            <!-- El cargo se administra exclusivamente desde Asignación de cargo. -->
            <div class="field">
                <label>Cargo actual</label>
                <input type="text" value="<?php echo htmlspecialchars($dato['nombre_cargo'] ?? 'Sin cargo asignado', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <small style="color:#d5dce5;">Para asignar o desvincular un cargo, utilice Asignación de cargo.</small>
            </div>

            <div class="field">
                <label for="tipo_personal">Tipo de Personal</label>
                <select name="tipo_personal" id="tipo_personal" required>
                    <option value="">Seleccione el tipo de personal...</option>
                    <?php foreach (['Fijo', 'Contratado', 'Obrero', 'Empleado', 'Jubilado'] as $tipoPersonal): ?>
                        <option value="<?php echo htmlspecialchars($tipoPersonal, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($dato['tipo_personal'] ?? '') === $tipoPersonal) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipoPersonal, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <h3 class="form-section-title full-width">Dirección</h3>
            <!-- Dirección: Estado -->
            <div class="field">
                <label>Estado</label>
                <select name="cod_est" id="selectEstado">
                    <option value="">Seleccione un Estado</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?php echo htmlspecialchars($e['cod_est']); ?>"
                            <?php echo ((string)($dato['cod_est'] ?? '') === (string)$e['cod_est']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dirección: Municipio -->
            <div class="field">
                <label>Municipio</label>
                <select name="cod_muni" id="selectMunicipio" <?php echo empty($municipios) ? 'disabled' : ''; ?>>
                    <option value="">Seleccione un Municipio</option>
                    <?php foreach ($municipios as $m): ?>
                        <option value="<?php echo htmlspecialchars($m['cod_muni']); ?>"
                            <?php echo ((string)($dato['cod_muni'] ?? '') === (string)$m['cod_muni']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dirección: Parroquia -->
            <div class="field">
                <label>Parroquia</label>
                <select name="cod_par" id="selectParroquia" <?php echo empty($parroquias) ? 'disabled' : ''; ?>>
                    <option value="">Seleccione una Parroquia</>
                    <?php foreach ($parroquias as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['cod_par']); ?>"
                            <?php echo ((string)($dato['cod_par'] ?? '') === (string)$p['cod_par']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dirección exacta -->
            <div class="field full-width">
                <label>Dirección</label>
                <textarea name="direccion_texto" id="direccion_texto" maxlength="255"
                    placeholder="Ingrese dirección exacta (calle, avenida, casa, referencia, etc.)"><?php echo htmlspecialchars($dato['direccion'] ?? ''); ?></textarea>
            </div>

            <div class="contenedor-botones full-width">
                <button type="submit" class="btn-guardar" style="margin-left: 10px;">
                    Guardar cambios
                </button>
            </div>

        </div>
    </form>
</div>

<script src="/FUNDACITE/vistas/js/hijos_trabajador.js?v=20261111"></script>
<script src="/FUNDACITE/vistas/js/valid_trabajadores.js?v=20261110"></script>
</body>
</html>
