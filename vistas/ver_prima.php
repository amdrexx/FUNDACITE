<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_primas.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$idTrabajador = intval($_GET['id'] ?? 0);

$objprima = new Prima($conexion);
$primas   = $idTrabajador > 0 ? $objprima->listarPorTrabajador($idTrabajador) : [];

if (!$primas) {
    $_SESSION['errores'] = ['Este trabajador no tiene primas registradas.'];
    header('Location: lista_primas.php');
    exit;
}

$trab = $objprima->obtenerTrabajador($idTrabajador) ?? [];

$nombre = trim((string) ($trab['nombre_completo'] ?? $primas[0]['nombre_completo'] ?? '')) ?: 'Trabajador';
$cedula = (string) ($trab['cedula'] ?? $primas[0]['cedula'] ?? '');

registrarBitacora($conexion, 'Primas', 'Consultar', "Consultó las primas del trabajador ID $idTrabajador.");

function h($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function fmtMonto($v): string { return number_format((float) $v, 2, ',', '.'); }
function fmtFecha($f): string {
    $t = strtotime((string) $f);
    return $t ? date('d/m/Y', $t) : ((string) $f !== '' ? (string) $f : 'No registrada');
}

$iconos = [
    'Prima por antigüedad'         => 'bi-hourglass-split',
    'Prima por hijos'              => 'bi-people',
    'Prima por profesionalización' => 'bi-mortarboard',
];

$etiquetasOrigen = ['base' => 'Sueldo base', 'cargo' => 'Sueldo por cargo'];

// Salario que devenga = monto_total guardado - monto de esa prima
$salario = max(0.0, (float) $primas[0]['monto_total'] - (float) $primas[0]['monto']);
$sumaPrimas = 0.0;
$estatusSet = [];
foreach ($primas as $p) {
    $sumaPrimas += (float) $p['monto'];
    $estatusSet[(string) ($p['estatus'] ?? 'Activo')] = true;
}
$montoTotal = $salario + $sumaPrimas;

if (count($estatusSet) === 1) {
    $estatusGeneral = array_key_first($estatusSet);
    $estatusActivo  = $estatusGeneral === 'Activo';
} else {
    $estatusGeneral = 'Mixto';
    $estatusActivo  = false;
}

$tipoPersonal = (string) ($trab['tipo_personal'] ?? '');
$modalidad    = $etiquetasOrigen[$trab['origen_salario'] ?? ''] ?? 'No determinada';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Primas</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>

<?php include "includes/alerta.php"; ?>

<div class="main">
    <div class="worker-page">
        <a class="worker-back" href="lista_primas.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Volver
        </a>

        <article class="worker-card">
            <header class="worker-header">
                <div class="worker-avatar" aria-hidden="true"><i class="bi bi-cash-coin"></i></div>
                <div class="worker-header-content">
                    <p class="worker-kicker">Primas del trabajador</p>
                    <h2><?= h($nombre) ?></h2>
                </div>
                <span class="worker-status<?= $estatusActivo ? ' worker-status--active' : '' ?>"><?= h($estatusGeneral) ?></span>
            </header>

            <div class="worker-content">

                <section class="worker-section">
                    <h3><i class="bi bi-person-vcard"></i> Datos del trabajador</h3>
                    <dl class="worker-details">
                        <div class="worker-detail"><dt>Cédula</dt><dd><?= h($cedula !== '' ? $cedula : 'No registrada') ?></dd></div>
                        <div class="worker-detail"><dt>Cargo</dt><dd><?= h(($trab['cargo'] ?? '') !== '' ? $trab['cargo'] : 'Sin cargo asignado') ?></dd></div>
                        <div class="worker-detail"><dt>Tipo de personal</dt><dd><?= h($tipoPersonal !== '' ? $tipoPersonal : 'No registrado') ?></dd></div>
                        <div class="worker-detail"><dt>Estatus laboral</dt><dd><?= h(($trab['status'] ?? '') !== '' ? $trab['status'] : 'No registrado') ?></dd></div>
                    </dl>
                </section>

                <section class="worker-section">
                    <h3><i class="bi bi-wallet2"></i> Resumen salarial</h3>
                    <dl class="worker-details">
                        <div class="worker-detail"><dt>Modalidad salarial</dt><dd><?= h($modalidad) ?></dd></div>
                        <div class="worker-detail"><dt>Salario (Bs.)</dt><dd><?= fmtMonto($salario) ?></dd></div>
                        <div class="worker-detail"><dt>Total primas (Bs.)</dt><dd><?= fmtMonto($sumaPrimas) ?></dd></div>
                        <div class="worker-detail"><dt>Monto total (Bs.)</dt><dd><?= fmtMonto($montoTotal) ?></dd></div>
                    </dl>
                </section>

                <?php foreach ($primas as $p):
                    $icono = $iconos[$p['tipo_prima'] ?? ''] ?? 'bi-cash';
                    $pct   = (float) ($p['porcentaje'] ?? 0);
                    $esProfesionalizacion = ($p['tipo_prima'] ?? '') === 'Prima por profesionalización';

                    // Para profesionalización se muestra el nivel académico real
                    // guardado en TRABAJADOR, no la abreviatura usada para calcular el porcentaje.
                    $condicionVisible = $esProfesionalizacion
                        ? trim((string) ($trab['nivel_educacion'] ?? ''))
                        : trim((string) ($p['condicion'] ?? ''));
                    $etiquetaCondicion = $esProfesionalizacion ? 'Nivel de educación' : 'Condición';
                ?>
                <section class="worker-section">
                    <h3><i class="bi <?= h($icono) ?>"></i> <?= h($p['tipo_prima']) ?></h3>
                    <dl class="worker-details">
                        <div class="worker-detail"><dt><?= h($etiquetaCondicion) ?></dt><dd><?= h($condicionVisible !== '' ? $condicionVisible : 'No registrado') ?></dd></div>
                        <div class="worker-detail"><dt>Porcentaje</dt><dd><?= $pct > 0 ? fmtMonto($pct) . '%' : 'No aplica' ?></dd></div>
                        <div class="worker-detail"><dt>Monto (Bs.)</dt><dd><?= fmtMonto($p['monto']) ?></dd></div>
                        <div class="worker-detail"><dt>Fecha de registro</dt><dd><?= h(fmtFecha($p['fecha'] ?? '')) ?></dd></div>
                        <div class="worker-detail"><dt>Estatus</dt><dd><?= h($p['estatus'] ?? 'Activo') ?></dd></div>
                    </dl>

                </section>
                <?php endforeach; ?>

            </div>
        </article>
    </div>
</div>

</body>
</html>