<?php
session_start();
include_once "includes/guardian.php";
require_once '../conexion.php';
require_once '../controladores/ctrl_salario.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

/* =====================================================
   ACTUALIZAR SALARIOS POR PORCENTAJE
   ===================================================== */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'aumento_porcentaje'
) {

    $porcentaje = (float) str_replace(',', '.', $_POST['porcentaje'] ?? '0');

    if (
        function_exists('esAdministradorODirector')
        && !esAdministradorODirector()
    ) {

        $_SESSION['errores'] = ['No tiene permisos para actualizar salarios.'];

    } elseif ($porcentaje <= 0 || $porcentaje > 1000) {

        $_SESSION['errores'] = ['Ingrese un porcentaje válido (mayor que 0 y hasta 1000).'];

    } else {

        $conexion->begin_transaction();

        try {

            $stmt = $conexion->prepare("
                UPDATE SALARIO
                SET monto = ROUND(monto * (1 + ? / 100), 2)
                WHERE estado = 'Vigente'
            ");

            $stmt->bind_param('d', $porcentaje);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $filas = $stmt->affected_rows;
            $stmt->close();

            $conexion->commit();

            $textoPct = rtrim(rtrim(number_format($porcentaje, 2, ',', '.'), '0'), ',');

            $_SESSION['exito'] = "Se actualizaron $filas salarios con un aumento del $textoPct%.";

            registrarBitacora(
                $conexion,
                'Salarios',
                'Actualizar',
                "Aplicó un aumento del $textoPct% a $filas salarios vigentes."
            );

        } catch (Throwable $e) {

            $conexion->rollback();

            $_SESSION['errores'] = ['Error al actualizar los salarios.'];

        }

    }

    header('Location: lista_salario.php');
    exit;

}

/* =====================================================
   LISTADO + BÚSQUEDA
   ===================================================== */
$buscar = trim($_GET['buscar'] ?? '');

$todos = listarSalarios($conexion) ?? [];

function etiquetaTipoSalario($fila) {
    return (($fila['tipo_salario'] ?? '') === 'cargo')
        ? 'Sueldo por cargo'
        : 'Sueldo base';
}

if ($buscar !== '') {

    $salarios = array_filter($todos, function ($f) use ($buscar) {

        $texto = implode(' ', [
            etiquetaTipoSalario($f),
            $f['nombre_cargo'] ?? '',
            $f['fecha'] ?? '',
            $f['monto'] ?? '',
            $f['estado'] ?? '',
        ]);

        return mb_stripos($texto, $buscar) !== false;

    });

} else {

    $salarios = $todos;

}

/* Salarios vigentes (los que afecta el aumento por porcentaje) */
$vigentes = array_filter(
    $todos,
    fn($s) => ($s['estado'] ?? '') === 'Vigente'
);

registrarBitacora(
    $conexion,
    'Salarios',
    'Consultar',
    $buscar !== ''
        ? "Consultó el listado de salarios (búsqueda: \"$buscar\")."
        : 'Consultó el listado de salarios.'
);

$puedeActualizar = (
    !function_exists('esAdministradorODirector')
    || esAdministradorODirector()
);

$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito'] ?? '';
unset($_SESSION['errores'], $_SESSION['exito']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de salario</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
</head>

<body>

    <?php include "includes/layout.php"; ?>
    <?php include "includes/alerta.php"; ?>

    <div class="main">
        <div class="glass tabla-container">

            <h2 style="text-align:center;color:white;">Lista de Salarios</h2>

            <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;">

                <input
                    type="text"
                    name="buscar"
                    class="input-busqueda"
                    placeholder="Buscar por tipo, cargo, fecha, monto o estado"
                    value="<?php echo htmlspecialchars($buscar); ?>"
                >

                <button class="btn-buscar">Buscar</button>

                <a href="registrar_salario.php" class="btn-persona" style="text-decoration:none;">
                    + Registrar Salario
                </a>
                <a href="actualizar_salario.php" class="btn-persona" style="text-decoration:none;">
                    Actualizar por %
                </a>
                <?php if (esAdministradorODirector()): ?>
                    <a
                        href="/FUNDACITE/pdf/reporte_salarios.php"
                        class="btn-pdf"
                        target="_blank"
                        style="text-decoration:none; margin-left:auto;"
                    >
                        <i class="bi bi-file-earmark-pdf"></i>
                        Exportar PDF
                    </a>
                <?php endif; ?>

            </form>

            <?php if ($puedeActualizar): ?>

            <?php endif; ?>

            <table class="tabla">

                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Cargo</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($salarios)): ?>

                        <?php foreach ($salarios as $fila): ?>

                            <tr>

                                <td><?php echo etiquetaTipoSalario($fila); ?></td>

                                <td><?php echo !empty($fila['nombre_cargo']) ? htmlspecialchars($fila['nombre_cargo']) : ''; ?></td>

                                <td><?php echo htmlspecialchars($fila['fecha']); ?></td>

                                <td>Bs <?php echo number_format($fila['monto'], 2, ',', '.'); ?></td>

                                <td><?php echo htmlspecialchars($fila['estado']); ?></td>

                                <td class="acciones">

                                    <a class="btn-editar" href="editar_salario.php?editar=<?php echo $fila['id_salario']; ?>">
                                        Editar
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <a
                                        class="btn-eliminar"
                                        onclick="return confirm('¿Desea inhabilitar este salario?');"
                                        href="../controladores/ctrl_salario.php?eliminar=<?php echo $fila['id_salario']; ?>"
                                    >
                                        <i class="bi bi-trash"></i>
                                        Inhabilitar
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">No existen salarios registrados.</td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>
    </div>

    <script>

        /* =====================================================
           ALERTA
           ===================================================== */
        function closeAlert() {
            const alerta = document.getElementById("customAlert");
            if (alerta) {
                alerta.classList.add("hidden");
            }
        }

        /* =====================================================
           ACTUALIZAR SALARIOS POR PORCENTAJE
           ===================================================== */
        document.addEventListener("DOMContentLoaded", function () {

            const panel      = document.getElementById("panelAumento");
            const btnMostrar = document.getElementById("btnMostrarAumento");
            const btnCancel  = document.getElementById("btnCancelarAumento");
            const inputPct   = document.getElementById("porcentaje");
            const formAum    = document.getElementById("formAumento");

            if (!panel || !inputPct || !formAum) {
                return;
            }

            const formatear = function (n) {
                return "Bs " + n.toLocaleString("es-VE", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            function recalcular() {

                const p = parseFloat(inputPct.value) || 0;

                panel.querySelectorAll("tbody tr").forEach(function (tr) {

                    const celdaActual = tr.querySelector(".monto-actual");
                    const celdaNuevo  = tr.querySelector(".monto-nuevo");

                    if (!celdaActual || !celdaNuevo) {
                        return;
                    }

                    const actual = parseFloat(celdaActual.dataset.monto);

                    celdaNuevo.textContent = p > 0
                        ? formatear(Math.round(actual * (1 + p / 100) * 100) / 100)
                        : "—";

                });

            }

            if (btnMostrar) {
                btnMostrar.addEventListener("click", function () {
                    panel.style.display = "block";
                    panel.scrollIntoView({ behavior: "smooth" });
                    inputPct.focus();
                });
            }

            if (btnCancel) {
                btnCancel.addEventListener("click", function () {
                    panel.style.display = "none";
                    formAum.reset();
                    recalcular();
                });
            }

            inputPct.addEventListener("input", recalcular);

            formAum.addEventListener("submit", function (e) {
                const mensaje = "¿Aplicar un aumento del " + inputPct.value
                    + "% a todos los salarios vigentes?";
                if (!confirm(mensaje)) {
                    e.preventDefault();
                }
            });

        });

    </script>

    <!-- MOSTRAR ERRORES -->
    <?php if (!empty($errores)): ?>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const mensaje = document.getElementById("alertMessage");
                const alerta  = document.getElementById("customAlert");
                if (mensaje && alerta) {
                    mensaje.textContent = <?php echo json_encode(implode("\n", $errores)); ?>;
                    alerta.classList.remove("hidden");
                }
            });
        </script>

    <?php endif; ?>

    <!-- MOSTRAR ÉXITO -->
    <?php if (!empty($exito)): ?>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const mensaje = document.getElementById("alertMessage");
                const alerta  = document.getElementById("customAlert");
                if (mensaje && alerta) {
                    mensaje.textContent = <?php echo json_encode($exito); ?>;
                    alerta.classList.remove("hidden");
                }
            });
        </script>

    <?php endif; ?>

</body>

</html>