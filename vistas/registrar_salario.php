<?php
session_start();
include_once "includes/guardian.php";
include_once "includes/roles.php";
requerirRol(['Administrador', 'Director', 'Analista']);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../conexion.php");

// Cargos para el select
$cargos = [];
$resultCargos = $conexion->query("SELECT id_cargo, nombre_cargo FROM CARGO ORDER BY nombre_cargo ASC");
if ($resultCargos) {
    while ($cargo = $resultCargos->fetch_assoc()) {
        $cargos[] = $cargo;
    }
}

// Última fecha registrada por tipo: salario mínimo (base) y cada cargo por separado
$ultimas = ['base' => null, 'cargo' => []];
$resUltimas = $conexion->query(
    "SELECT tipo_salario, id_cargo, MAX(fecha) AS ultima_fecha
     FROM SALARIO
     WHERE estado = 'Vigente'
     GROUP BY tipo_salario, id_cargo"
);
if ($resUltimas) {
    while ($u = $resUltimas->fetch_assoc()) {
        if ($u['tipo_salario'] === 'base') {
            $ultimas['base'] = $u['ultima_fecha'];
        } elseif ($u['tipo_salario'] === 'cargo' && $u['id_cargo'] !== null) {
            $ultimas['cargo'][(string) $u['id_cargo']] = $u['ultima_fecha'];
        }
    }
}

// Mensajes y datos digitados si el controlador devolvió un error de validación
$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
$old     = $_SESSION['old'] ?? [];

unset($_SESSION['errores'], $_SESSION['exito'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Salario</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_trabajadores.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver 
        </a>
    <div class="form-card">

        <form id="formsalario" method="POST" action="../controladores/ctrl_salario.php" autocomplete="off">

            <input type="hidden" name="volver" value="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">

            <center><h2>Registro de Salario</h2></center>

            <?php if (!empty($errores)): ?>
                <div class="field full-width" style="color:#b00020; font-weight:600;">
                    <?php foreach ($errores as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($exito)): ?>
                <div class="field full-width" style="color:#1b7f3b; font-weight:600;">
                    <p><?= htmlspecialchars($exito) ?></p>
                </div>
            <?php endif; ?>

            <div class="field full-width">
                <label for="tipo_salario">Tipo de salario</label>
                <select name="tipo_salario" id="tipo_salario" required autocomplete="off" onchange="cambiarTipoSalario()">
                    <option value="">Seleccione un tipo</option>
                    <option value="base" <?= (($old['tipo_salario'] ?? '') === 'base') ? 'selected' : '' ?>>Salario Minimo</option>
                    <option value="cargo" <?= (($old['tipo_salario'] ?? '') === 'cargo') ? 'selected' : '' ?>>Sueldo por cargo</option>
                </select>
            </div>

            <div class="field full-width" id="campo-cargo">
                <label for="id_cargo">Cargo</label>
                <select name="id_cargo" id="id_cargo">
                    <option value="">Seleccione un cargo</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option
                            value="<?= htmlspecialchars($cargo['id_cargo']) ?>"
                            <?= ((string) ($old['id_cargo'] ?? '') === (string) $cargo['id_cargo']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($cargo['nombre_cargo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" id="campo-fecha">
                <label for="fecha">Fecha de ingreso</label>
                <input
                    type="date"
                    name="fecha"
                    id="fecha"
                    required
                    value="<?= htmlspecialchars($old['fecha'] ?? '') ?>"
                >
                <small id="aviso-plazo" style="display:none; color:#b00020; font-weight:600;"></small>
            </div>

            <div class="field campo-monto" id="campo-monto">
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
                        style="width:100%; box-sizing:border-box; padding-right:3px;"
                        value="<?= htmlspecialchars($old['monto'] ?? '') ?>"
                    >
                    <span
                        class="sufijo-bs"
                        style="position:absolute; right:20px; top:50%; left:auto; transform:translateY(-50%); margin:0; color:#333; font-weight:700; pointer-events:none;"
                    >Bs</span>
                </div>
            </div>

            <div class="form-actions full-width">
                <button type="reset" class="btn-secundario" id="btnLimpiar">Limpiar</button>
                <button type="submit" name="accion" value="guardar" class="btn-guardar">Guardar</button>
            </div>

        </form>
    </div>
    </div>
</div>

<script>
// Datos del servidor para la validación del plazo
const ULTIMAS    = <?= json_encode($ultimas) ?>; // { base: 'YYYY-MM-DD'|null, cargo: { "id_cargo": 'YYYY-MM-DD' } }
const PLAZO_DIAS = 20;
</script>
<script src="/FUNDACITE/vistas/js/valid_salario.js"></script>

</body>
</html>