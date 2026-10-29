<?php
session_start();

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
                <input
                    type="password"
                    name="contrasena"
                    placeholder="Ingrese su contraseña"
                    required
                >
            </div>

            <button type="submit" name="login" class="btn">
                Iniciar Sesión
            </button>

        </form>

    </div>

</div>

</body>
</html>