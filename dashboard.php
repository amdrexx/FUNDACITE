<?php
$activo   = 56;   // Personal activo
$jubilado = 5;    // Personal jubilado
$inactivo = 16;   // Personal inactivo
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="style_dashboard.css">
  <link rel="stylesheet" href="bootstrap.min.css">
  <link rel="stylesheet" href="bootstrap-icons.min.css">
  <script src="bootstrap.min.js"></script>
</head>
<body>

<!-- ================= TOPBAR ================= -->
<div class="topbar">
    <img src="logo.png" alt="Logo">
</div>

<!-- ================= SIDEBAR ================= -->
<div class="sidebar" id="sidebar" style="display: flex; flex-direction: column; justify-content: space-between; height: calc(102vh - 70px);">
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li>
            <a href="dashboard.php" class="submenu-link">
                <i class="bi bi-house-door-fill"></i> <b>INICIO</b>
            </a>
        </li>
        <li>
            <a href="lista_personas.php" class="submenu-link">
                <i class="bi bi-people-fill"></i> <b>PERSONAS</b>
            </a>
        </li>
        <li>
            <a href="lista_trabajadores.php" class="submenu-link">
                <i class="bi bi-person-workspace"></i> <b>TRABAJADORES</b>
            </a>
        </li>
        <li>
            <a href="lista_cargos.php" class="submenu-link">
                <i class="bi bi-briefcase-fill"></i> <b>CARGO</b>
            </a>
        </li>
        <li>
            <a href="lista_direcciones.php" class="submenu-link">
                <i class="bi bi-geo-alt-fill"></i> <b>DIRECCION</b>
            </a>
        </li>
        <li>
            <a href="lista_contratos.php" class="submenu-link">
                <i class="bi bi-file-earmark-text-fill"></i> <b>CONTRATOS</b>
            </a>
        </li>
        <li>
            <a href="lista_usuarios.php" class="submenu-link">
                <i class="bi bi-person-circle"></i> <b>USUARIO</b>
            </a>
        </li>
        <li>
            <a href="lista_solicitudes.php" class="submenu-link">
                <i class="bi bi-calendar2-check-fill"></i> <b>SOLICITUDES DE DIAS DE DISFRUTE</b>
            </a>
        </li>
    </ul>

    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
        <li>
            <a href="" class="submenu-link">
                <i class="bi bi-box-arrow-right"></i> <b>CERRAR SESIÓN</b>
            </a>
        </li>
    </ul>
</div>

<!-- ================= MAIN CONTENT ================= -->
<div class="main">
    <div class="dashboard-container">
        <!-- TARJETAS (CARDS) CON CANTIDADES DINÁMICAS (AHORA DESDE PHP SIN BD) -->
        <div class="cards">
            <div class="card">
                <div class="icon">
                    <img src="persona_activa.png" alt="icono">
                </div>
                <h3>PERSONAL ACTIVO</h3>
                <div class="status green"></div>
                <div class="number"><?php echo $activo; ?></div>
            </div>

            <div class="card">
                <div class="icon">
                    <img src="persona_jubilada.png" alt="icono">
                </div>
                <h3>PERSONAL JUBILADO</h3>
                <div class="status gray"></div>
                <div class="number"><?php echo $jubilado; ?></div>
            </div>

            <div class="card">
                <div class="icon">
                    <img src="persona_inactiva.png" alt="icono">
                </div>
                <h3>PERSONAL INACTIVO</h3>
                <div class="status red"></div>
                <div class="number"><?php echo $inactivo; ?></div>
            </div>
        </div>

        <!-- GRÁFICO CIRCULAR (DOUGHNUT) CON DATOS EN VIVO -->
        <div class="chart-section">
            <h2>Distribución del Personal</h2>
            <div class="chart-container">
                <canvas id="personalChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="chart.js"></script>

<script>
const ctx = document.getElementById('personalChart');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Activo', 'Jubilado', 'Inactivo'],
        datasets: [{
            data: [<?php echo $activo; ?>, <?php echo $jubilado; ?>, <?php echo $inactivo; ?>],
            backgroundColor: ['#4CAF50', '#b4b4b4', '#ff6b6b'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#000000'
                }
            }
        },
        cutout: '65%'
    }
});
</script>

</body>
</html>