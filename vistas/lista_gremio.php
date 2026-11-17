<?php
session_start();
include_once 'includes/guardian.php';
include_once "includes/roles.php";

require_once '../conexion.php';
require_once '../modelos/clase_gremio.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

$buscar = trim($_GET['buscar'] ?? '');
$modelo = new Gremio($conexion);
$gremios = $modelo->listar($buscar);
registrarBitacora($conexion, 'Gremios', 'Consultar', $buscar !== '' ? "Consultó el listado de gremios (búsqueda: \"$buscar\")." : 'Consultó el listado de gremios.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Gremios</title>
   <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>
<?php include 'includes/layout.php'; ?>

<div class="main">
    <div class="glass tabla-container">
        <h2 style="text-align:center;color:white;">Lista de Gremios</h2>

        <form method="GET" action="lista_gremio.php" class="buscador-container" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="text" name="buscar" class="input-busqueda" placeholder="Buscar gremio por nombre..." value="<?php echo htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-buscar">Buscar</button>
            <a href="registrar_gremio.php" class="btn-persona" style="text-decoration:none;">+ Agregar Gremio</a>
        </form>

        <div class="modulo-card" style="margin:20px auto 0;padding:10px;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre del Gremio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($gremios)): ?>
                    <?php foreach ($gremios as $gremio): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($gremio['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span style="display:inline-block;padding:5px 10px;border-radius:20px;font-size:12px;font-weight:600;background:<?php echo $gremio['estado'] === 'Activo' ? 'rgba(34,197,94,.16)' : 'rgba(148,163,184,.18)'; ?>;color:<?php echo $gremio['estado'] === 'Activo' ? '#86efac' : '#cbd5e1'; ?>;">
                                    <?php echo htmlspecialchars($gremio['estado'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="acciones">
                                <a class="btn-editar" href="registrar_gremio.php?id=<?php echo (int)$gremio['id_gremio']; ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center;">No se encontraron gremios registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
