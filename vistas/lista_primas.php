<?php
session_start();
include_once "includes/guardian.php";
require_once '../conexion.php';
require_once '../modelos/clase_primas.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$buscar = trim($_GET['buscar'] ?? '');

// Mensajes enviados por ctrl_primas.php (los usa includes/alerta.php)
$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
$exito_edicion = $_SESSION['exito_edicion'] ?? '';
unset($_SESSION['errores'], $_SESSION['exito'], $_SESSION['exito_edicion']);
if (!is_array($errores)) { $errores = [$errores]; }

$prima = new Prima($conexion);
$primas = $prima->listar($buscar);
registrarBitacora($conexion, 'Primas', 'Consultar', $buscar !== '' ? "Consultó el listado de primas (búsqueda: \"$buscar\")." : 'Consultó el listado de primas.');

function fmtMonto($v): string { return number_format((float) $v, 2, ',', '.'); }

$nombresCortos = [
    'Prima por antigüedad'         => 'Antigüedad',
    'Prima por hijos'              => 'Hijos',
    'Prima por profesionalización' => 'Profesionalización',
];

// ---------------------------------------------------------------------------
// Se agrupan las primas por trabajador: una sola fila por trabajador en la lista
// ---------------------------------------------------------------------------
$grupos = [];

foreach ($primas as $p) {
    $idT = (int) ($p['id_trabajador'] ?? 0);

    if (!isset($grupos[$idT])) {
        $grupos[$idT] = [
            'id'       => $idT,
            'cedula'   => (string) ($p['cedula'] ?? ''),
            'nombre'   => trim((string) ($p['nombre_completo'] ?? '')),
            // Salario que devenga = monto_total guardado - monto de esa prima
            'salario'  => max(0.0, (float) ($p['monto_total'] ?? 0) - (float) ($p['monto'] ?? 0)),
            'suma'     => 0.0,
            'fecha'    => '',
            'estatus'  => [],
            'tipos'    => [],
            'items'    => [],
        ];
    }

    $g =& $grupos[$idT];
    $g['suma'] += (float) ($p['monto'] ?? 0);
    $fecha = (string) ($p['fecha'] ?? '');
    if ($fecha > $g['fecha']) { $g['fecha'] = $fecha; }
    $g['estatus'][(string) ($p['estatus'] ?? 'Activo')] = true;
    $g['tipos'][] = $nombresCortos[$p['tipo_prima'] ?? ''] ?? (string) ($p['tipo_prima'] ?? '');
    $g['items'][] = [
        'id_prima'   => (int) $p['id_prima'],
        'tipo'       => (string) ($p['tipo_prima'] ?? ''),
        'condicion'  => (string) ($p['condicion'] ?? ''),
        'porcentaje' => (float) ($p['porcentaje'] ?? 0),
        'monto'      => (float) ($p['monto'] ?? 0),
        'fecha'      => $fecha,
        'estatus'    => (string) ($p['estatus'] ?? 'Activo'),
    ];
    unset($g);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listas de primas al trabajador</title>
     <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

  
  <?php include "includes/layout.php"; ?>
  <?php include "includes/alerta.php"; ?>

    <div class="main">
        <div class="glass tabla-container">
            <h2 style="text-align:center; color:white;">Lista Primas al trabajador</h2>

            <form method="GET" action="lista_primas.php" class="buscador-container" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;"></form>
                <input
                    type="text"
                    name="buscar"
                    class="input-busqueda"
                    placeholder="Buscar por trabajador, cédula, tipo o condición..."
                    value="<?= htmlspecialchars($buscar) ?>"
                >
                <button type="submit" class="btn-buscar">Buscar</button>
                <a href="/FUNDACITE/vistas/registrar_prima.php" class="btn-persona" style="text-decoration:none; margin-right:10px;">
                    + Agregar prima
                </a>
                <a href="/FUNDACITE/vistas/registrar_primas_nuevo.php" class="btn-persona" style="text-decoration:none; margin-right:10px;">
                    + Registrar nueva prima
                </a>
                    <a href="actualizar_porcentaje_prima.php" class="btn-persona" style="text-decoration:none;margin-right:10px;">
                    Actualizar
                </a>
                </a>
                    <a href="lista_nuevas_primas.php" class="btn-persona" style="text-decoration:none;margin-right:2px; position:relative; top:10px;">
                    Lista Primas
                </a>
                <?php if (esAdministradorODirector()): ?>
                <a
                    href="/FUNDACITE/pdf/reporte_primas.php<?= $buscar !== '' ? '?buscar=' . urlencode($buscar) : ''; ?>"
                    class="btn-pdf"
                    target="_blank"
                    style="text-decoration:none; margin-left:10px; position:relative; top:10px;">
                
                    <i class="bi bi-file-earmark-pdf"></i>
                    Exportar PDF
                </a>
                <?php endif; ?>
            </form>
    
            <div class="modulo-card" style="margin: 20px auto 0; padding: 10px;">      
                <table class="tabla">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Trabajador</th>
                        <th>Primas registradas</th>
                        <th>Monto primas (Bs.)</th>
                        <th>Monto total (Bs.)</th>
                        <th>Último registro</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
             <tbody>
<?php if (!empty($grupos)): ?>

    <?php foreach ($grupos as $g):
        $estatusKeys = array_keys($g['estatus']);
        $estatusTxt = count($estatusKeys) === 1 ? $estatusKeys[0] : 'Mixto';
        $hayActivas = isset($g['estatus']['Activo']);
    ?>
        <tr>
            <td><?= htmlspecialchars($g['cedula']) ?></td>

            <td><?= htmlspecialchars($g['nombre']) ?></td>

            <td><?= htmlspecialchars(implode(', ', $g['tipos'])) ?> (<?= count($g['items']) ?>)</td>

            <td><?= fmtMonto($g['suma']) ?></td>

            <td><?= fmtMonto($g['salario'] + $g['suma']) ?></td>

            <td><?= htmlspecialchars($g['fecha']) ?></td>

            <td><?= htmlspecialchars($estatusTxt) ?></td>

            <td class="acciones">
                <a class="btn-ver" href="ver_prima.php?id=<?= (int) $g['id'] ?>">
                    <i class="bi bi-eye"></i>
                    Ver
                </a>
                <a class="btn-editar" href="registrar_prima.php?accion=editar&id=<?= (int) $g['id'] ?>">
                    <i class="bi bi-pencil"></i>
                    Editar
                </a>

                <?php if (esAdministradorODirector()): ?>
                <form action="../controladores/ctrl_primas.php" method="POST" style="display:inline;"
                      onsubmit="return confirm('<?= $hayActivas
                          ? '¿Seguro que deseas inactivar las primas de este trabajador?'
                          : '¿Seguro que deseas activar las primas de este trabajador?' ?>');">
                    <input type="hidden" name="id_trabajador" value="<?= (int) $g['id'] ?>">
                    <input type="hidden" name="accion" value="<?= $hayActivas ? 'inactivar_primas' : 'activar_primas' ?>">
                    <?php if ($hayActivas): ?>
                    <button type="submit" class="btn-eliminar" style="border:none; cursor:pointer;">
                        <i class="bi bi-trash"></i>
                        Inactivar
                    </button>
                    <?php else: ?>
                    <button type="submit" class="btn-editar" style="border:none; cursor:pointer;">
                        <i class="bi bi-check-circle"></i>
                        Activar
                    </button>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>

<?php else: ?>

    <tr>
        <td colspan="8" style="text-align:center;">
            No hay primas registradas.
        </td>
    </tr>

<?php endif; ?>
</tbody>
            </table>
        </div>
    </div>

    <script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>

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

<?php if (!empty($exito_edicion)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alertDiv = document.getElementById('customAlert');
        var msg = document.getElementById('alertMessage');
        msg.textContent = <?php echo json_encode($exito_edicion); ?>;
        alertDiv.classList.remove('hidden');
    });
</script>
<?php endif; ?>
</body>
</html>