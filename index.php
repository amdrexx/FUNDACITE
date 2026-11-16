<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = $_SESSION['error_login'] ?? '';
unset($_SESSION['error_login']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Fundacite</title>

    <link rel="stylesheet" href="/FUNDACITE/vistas/css/styles.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/password-toggle.css">
</head>

<body id="cuerpo-fondo">

<div class="contenedor">

    <div id="caja-login" class="login-box">

        <h2>Iniciar Sesión</h2>

        <?php if(!empty($error)): ?>
            <div style="color:red; margin-bottom:15px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="controladores/controlador_login.php" method="POST">

            <div class="input-group">
                <label>Usuario</label>
                <input
                    type="text"
                    name="nombre"
                    placeholder="Ingrese su usuario"
                    required
                >
            </div>

            <div class="input-group">
                <label>Contraseña</label>
                <div class="password-input-wrapper">
                    <input
                        type="password"
                        name="contrasena"
                        id="contrasena"
                        placeholder="Ingrese su contraseña"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="password-toggle" data-password-toggle
                            aria-label="Mostrar contraseña" title="Mostrar contraseña" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="btn">
                Iniciar Sesión
            </button>

        </form>

    </div>

</div>

    <script src="/FUNDACITE/vistas/js/password-toggle.js"></script>
</body>
</html>