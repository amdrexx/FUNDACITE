<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
include_once "includes/guardian.php";
require_once '../conexion.php';
require_once '../modelos/clase_nueva_prima.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$buscar = trim($_GET['buscar'] ?? '');

// Mensajes enviados por ctrl_nueva_prima.php (los usa includes/alerta.php)
$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
unset($_SESSION['errores'], $_SESSION['exito'], $_SESSION['old']);
if (!is_array($errores)) { $errores = [$errores]; }

$modelo = new NuevaPrima($conexion);
$primas = $modelo->listar($buscar);
registrarBitacora($conexion, 'Nuevas primas', 'Consultar', $buscar !== '' ? "Consultó el listado de nuevas primas (búsqueda: \"$buscar\")." : 'Consultó el listado de nuevas primas.');

function fmtFecha(string $f): string {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d ? $d->format('d/m/Y') : $f;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de nuevas primas</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

<?php include "includes/layout.php"; ?>


<div class="main">
    <div class="glass tabla-container">
        <h2 style="text-align:center; color:white;">Lista de nuevas primas</h2>

        <form method="GET" action="lista_nuevas_primas.php" class="buscador-container" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input
                type="text"
                name="buscar"
                class="input-busqueda"
                placeholder="Buscar por nombre, fecha, porcentaje o estatus..."
                value="<?= htmlspecialchars($buscar) ?>"
            >
            <button type="submit" class="btn-buscar">Buscar</button>
            <a href="registrar_primas_nuevo.php" class="btn-persona" style="text-decoration:none;">
                + Nueva prima
            </a>
        </form>

        <table class="tabla">
            <thead>
                <tr>
                    <th>Prima</th>
                    <th>Fecha</th>
                    <th>Porcentaje</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($primas)): ?>
                <?php foreach ($primas as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars(fmtFecha($p['fecha'])) ?></td>
                        <td><?= number_format((float) $p['porcentaje'], 2, ',', '.') ?>%</td>
                        <td><?= htmlspecialchars($p['estatus']) ?></td>
                        <td class="acciones">
                            <a class="btn-editar" href="registrar_primas_nuevo.php?editar=<?= (int) $p['id_nueva_prima'] ?>">
                                <i class="bi bi-pencil-square"></i>
                                Editar
                            </a>

                            <?php if (esAdministradorODirector()): ?>
                            <form action="../controladores/ctrl_nueva_prima.php" method="POST" style="display:inline;"
                                  data-confirm="<?= htmlspecialchars($p['estatus'] === 'Activo' ? '¿Seguro que deseas inactivar esta prima?' : '¿Seguro que deseas activar esta prima?', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_nueva_prima" value="<?= (int) $p['id_nueva_prima'] ?>">
                                <input type="hidden" name="accion" value="<?= $p['estatus'] === 'Activo' ? 'inactivar' : 'activar' ?>">
                                <?php if ($p['estatus'] === 'Activo'): ?>
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
                    <td colspan="5" style="text-align:center;">No hay primas registradas.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>



</body>
</html>