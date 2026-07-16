<?php
session_start();

include "includes/guardian.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}

require_once("../conexion.php");

/*=========================================================
=            CONTAR PERSONAL POR STATUS                   =
=========================================================*/

$activo = 0;
$jubilado = 0;
$inactivo = 0;

$sql = "
SELECT
    SUM(CASE WHEN status='Activo' THEN 1 ELSE 0 END) AS activo,
    SUM(CASE WHEN status='Jubilado' THEN 1 ELSE 0 END) AS jubilado,
    SUM(CASE WHEN status='Inactivo' THEN 1 ELSE 0 END) AS inactivo
FROM TRABAJADOR
";

$resultado = $conexion->query($sql);

if ($resultado) {

    $fila = $resultado->fetch_assoc();

    $activo = (int)$fila["activo"];
    $jubilado = (int)$fila["jubilado"];
    $inactivo = (int)$fila["inactivo"];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Dashboard</title>

    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">

    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>

</head>

<body>

<?php include "includes/layout.php"; ?>

<div class="main">

    <div class="dashboard-container">

        <!-- ======================= TARJETAS ======================= -->

        <div class="cards">

            <div class="card">

                <div class="icon">
                    <img src="/FUNDACITE/vistas/img/persona_activa.png" alt="">
                </div>

                <h3>PERSONAL ACTIVO</h3>

                <div class="status green"></div>

                <div class="number">
                    <?= $activo ?>
                </div>

            </div>

            <div class="card">

                <div class="icon">
                    <img src="/FUNDACITE/vistas/img/persona_jubilada.png" alt="">
                </div>

                <h3>PERSONAL JUBILADO</h3>

                <div class="status gray"></div>

                <div class="number">
                    <?= $jubilado ?>
                </div>

            </div>

            <div class="card">

                <div class="icon">
                    <img src="/FUNDACITE/vistas/img/persona_inactiva.png" alt="">
                </div>

                <h3>PERSONAL INACTIVO</h3>

                <div class="status red"></div>

                <div class="number">
                    <?= $inactivo ?>
                </div>

            </div>

        </div>

        <!-- ======================= GRÁFICO ======================= -->

        <div class="chart-section">

            <h2>Distribución del Personal</h2>

            <div class="chart-container">

                <canvas id="personalChart"></canvas>

            </div>

        </div>

    </div>

</div>

<script src="/FUNDACITE/vistas/js/chart.js"></script>

<script>

const ctx = document.getElementById('personalChart');

new Chart(ctx, {

    type: 'doughnut',

    data: {

        labels: [

            'Activo',
            'Jubilado',
            'Inactivo'

        ],

        datasets: [{

            data: [

                <?= $activo ?>,
                <?= $jubilado ?>,
                <?= $inactivo ?>

            ],

            backgroundColor: [

                '#4CAF50',
                '#9E9E9E',
                '#F44336'

            ],

            borderWidth: 0

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {

                position: 'bottom',

                labels: {

                    color: '#000',
                    font: {

                        size: 14

                    }

                }

            }

        },

        cutout: '65%'

    }

});

</script>

</body>

</html>