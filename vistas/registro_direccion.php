<?php
session_start();
include_once "includes/guardian.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../controladores/ctrl_direccion.php");

$controller = new DireccionController();
$direcciones = $controller->listar();
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <title>Direcciones Registradas</title>

    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">

</head>

<body>

<?php include "includes/layout.php"; ?>

<div class="main">

    <div class="contenedor-estados">

        <div class="form-card">

            <center>
                <h2>Direcciones Registradas</h2>
            </center>

            <table class="tabla">

                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Municipio</th>
                        <th>Parroquia</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($direcciones)) { ?>

                    <?php foreach ($direcciones as $fila) { ?>

                        <tr>

                            <td><?= htmlspecialchars($fila["estado"]) ?></td>

                            <td><?= htmlspecialchars($fila["municipio"]) ?></td>

                            <td><?= htmlspecialchars($fila["parroquia"]) ?></td>

                            <td>
                                <?=
                                !empty($fila["direccion"])
                                    ? htmlspecialchars($fila["direccion"])
                                    : '<em style="opacity:.6;">Pendiente (se asigna en Trabajador)</em>';
                                ?>
                            </td>

                            <td class="acciones">

                                <a
                                    href="registro_direccion.php?editar=<?= $fila["id_dir"] ?>"
                                    class="btn-editar">
                                    <i class="bi bi-pencil-square"></i>
                                    Editar
                                </a>

                                <a
                                    href="../controladores/ctrl_direccion.php?eliminar=<?= $fila["id_dir"] ?>"
                                    class="btn-eliminar"
                                    onclick="return confirm('¿Está seguro de eliminar esta dirección?');">
                                    <i class="bi bi-trash"></i>
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="5" style="text-align:center;">
                            No hay direcciones registradas.
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script src="/FUNDACITE/vistas/js/jquery.min.js"></script>
<script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
<script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>

</body>

</html>