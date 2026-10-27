<?php
session_start();
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_salario.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

// Debe venir el id del salario a editar (la lista lo envía como ?editar=ID)
$id = intval($_GET['editar'] ?? 0);

$modelo        = new clase_salario($conexion);
$salarioActual = $id > 0 ? $modelo->obtenerPorId($id) : null;

// Si no existe, de vuelta a la lista
if (!$salarioActual) {
    $_SESSION['errores'] = ["El salario que intenta editar no existe."];
    header("Location: lista_salario.php");
    exit();
}

registrarBitacora($conexion, 'Salarios', 'Consultar', "Consultó el salario ID $id para edición.");

// Cargos para el select
$cargos = [];
$resultCargos = $conexion->query("SELECT id_cargo, nombre_cargo FROM CARGO ORDER BY nombre_cargo ASC");
if ($resultCargos) {
    while ($c = $resultCargos->fetch_assoc()) {
        $cargos[] = $c;
    }
}

// Mensajes y datos digitados si el controlador devolvió un error de validación
$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
$old     = $_SESSION['old'] ?? [];

unset($_SESSION['errores'], $_SESSION['exito'], $_SESSION['old']);

// Valores a mostrar: lo digitado (si hubo error) o lo guardado en la BD
$valTipo  = $old['tipo_salario'] ?? $salarioActual['tipo_salario'] ?? 'base';
$valCargo = $old['id_cargo']     ?? $salarioActual['id_cargo']     ?? '';
$valFecha = $old['fecha']        ?? $salarioActual['fecha']        ?? '';
$valMonto = $old['monto']        ?? $salarioActual['monto']        ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Salario</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/alerta.php"; ?>
<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="form-card">

        <form id="formsalario" method="POST" action="../controladores/ctrl_salario.php">

            <center><h2>Editar Salario</h2></center>

            <input type="hidden" name="id_salario" value="<?= (int) $salarioActual['id_salario'] ?>">

            <div class="field full-width">
                <label for="tipo_salario">Tipo de salario</label>
                <select name="tipo_salario" id="tipo_salario" required onchange="cambiarTipoSalario()">
                    <option value="">Seleccione un tipo</option>
                    <option value="base" <?= $valTipo === 'base' ? 'selected' : '' ?>>Sueldo base</option>
                    <option value="cargo" <?= $valTipo === 'cargo' ? 'selected' : '' ?>>Sueldo por cargo</option>
                </select>
            </div>

            <div class="field full-width" id="campo-cargo">
                <label for="id_cargo">Cargo</label>
                <select name="id_cargo" id="id_cargo">
                    <option value="">Seleccione un cargo</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option
                            value="<?= htmlspecialchars($cargo['id_cargo']) ?>"
                            <?= ((string) $valCargo === (string) $cargo['id_cargo']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($cargo['nombre_cargo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="fecha">Fecha de ingreso</label>
                <input
                    type="date"
                    name="fecha"
                    id="fecha"
                    required
                    value="<?= htmlspecialchars($valFecha) ?>"
                >
            </div>

            <div class="field campo-monto">
                <label for="monto">Monto</label>
                <div style="position:relative; width:70%;">
                    <input
                        type="number"
                        name="monto"
                        id="monto"
                        step="0.01"
                        min="0"
                        required
                        placeholder="Ej. 5000.00"
                        style="width:100%; box-sizing:border-box; padding-right:45px;"
                        value="<?= htmlspecialchars($valMonto) ?>"
                    >
                    <span
                        class="sufijo-bs"
                        style="position:absolute; right:20px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:700; pointer-events:none;"
                    >Bs</span>
                </div>
            </div>

            <div class="form-actions full-width">
                <a
                    href="lista_salario.php"
                    class="btn-secundario"
                    style="display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box; text-decoration:none; text-align:center;"
                >
                    Cancelar
                </a>
                <button type="submit" name="accion" value="actualizar" class="btn-guardar">
                    Actualizar
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function cambiarTipoSalario() {
    const tipo       = document.getElementById("tipo_salario");
    const campoCargo = document.getElementById("campo-cargo");
    const cargo      = document.getElementById("id_cargo");

    if (!tipo || !campoCargo || !cargo) return;

    if (tipo.value === "cargo") {
        campoCargo.style.display = "flex";
        cargo.disabled = false;
        cargo.required = true;
    } else {
        campoCargo.style.display = "none";
        cargo.value = "";
        cargo.disabled = true;
        cargo.required = false;
    }
}

function closeAlert() {
    const alerta = document.getElementById("customAlert");
    if (alerta) alerta.classList.add("hidden");
}

document.addEventListener("DOMContentLoaded", cambiarTipoSalario);
</script>

</body>
</html>