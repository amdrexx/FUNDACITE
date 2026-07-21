<?php
include "includes/guardian.php";
session_start();
require_once __DIR__ . '/includes/permissions.php';
requireAdministradorODirector();

$consulta_exitosa = $_SESSION['consulta_exitosa'] ?? false;
$old = $_SESSION['old'] ?? [];
$errores = $_SESSION['errores'] ?? [];

unset($_SESSION['consulta_exitosa']);
unset($_SESSION['old']);
unset($_SESSION['errores']);

function calcularDias(string $desde, string $hasta): string {
    $d1 = DateTime::createFromFormat('d-m-Y', $desde);
    $d2 = DateTime::createFromFormat('d-m-Y', $hasta);

    if (!$d1 || !$d2) return '';

    return $d1->diff($d2)->days + 1; // Incluye el primer día
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

<?php include "includes/layout.php"; ?>

<!-- FORMULARIO -->
<div class="main">
    <form class="form-card" id="formPersonas" method="POST" action="../controladores/ctrl_dias_disfrute.php">
        <div class="form-grid">
            <h2 style="text-align: center; margin-bottom: 10px;">Solicitud de Días de Disfrute</h2>
            <!-- Cédula -->
            <div class="field">
                <label>Cédula</label>
                <input type="text" name="cedula" id="cedula" maxlength="8" placeholder="Ingrese cédula"
                       value="<?php echo htmlspecialchars($old['cedula'] ?? ''); ?>" >
            </div>

            <!-- Cargo -->
            <div class="field">
                <label>Cargo</label>
                <input type="text" name="cargo" id="nombre_cargo" maxlength="50" placeholder="Ingrese nombres"
                       value="<?php echo htmlspecialchars($old['nombre_cargo'] ?? ''); ?>"readonly>
            </div>

            <!-- Descripcion -->
            <div class="field direccion-field">
                <label>Descripción</label>
                <input type="text" name="descripcion" id="descripcion" placeholder="Ingrese el motivo o detalle"
                       value="<?php echo htmlspecialchars($old['descripcion'] ?? ''); ?>"
       <?php echo !$consulta_exitosa ? 'readonly' : ''; ?>>
            </div>

            <!-- Desde -->
            <div class="field">
                <label>Desde</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($old['descripcion'] ?? ''); ?>"
       <?php echo !$consulta_exitosa ? 'readonly' : ''; ?>>
            </div>

            <!--Hasta-->
            <div class="field">
                <label>Hasta</label>
                <input type="text" name="hasta" id="hasta" placeholder="se calculara automaticamente"
                       value="<?php echo htmlspecialchars($old['hasta'] ?? ''); ?>"readonly>
            </div>
            <!-- Total de dias de disfrute -->
            <div class="field direccion-field">
                <label>Total de dias de disfrute</label>
                <input type="text" name="descripcion" id="descripcion" placeholder="Ingrese el motivo o detalle"
                       value="<?php 
                 if (!empty($old['fecha']) && !empty($old['hasta'])) {
                     // Calcula la diferencia de días
                     $inicio = new DateTime($old['fecha']);
                     $fin = new DateTime($old['hasta']);
                     $dias = $inicio->diff($fin)->days + 1; // +1 para incluir el día inicial
                     echo $dias . ' días en total';
                 } 
               ?>" readonly>
            </div>

            <button type="submit" name="accion" value="consultar" class="btn-guardar full-width">
    Consultar
</button>

<button type="submit" name="accion" value="calcular" class="btn-guardar full-width">
    Calcular
</button>

<button type="submit" name="accion" value="calcular" class="btn-guardar full-width">
    Guardar
</button>
        </div>
    </form>
</div>

<script src="/FUNDACITE/vistas/js/valid_trabajadores.js"></script>

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