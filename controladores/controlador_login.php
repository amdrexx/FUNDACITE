<?php

session_start();

require_once "../conexion.php";
require_once "../modelos/clase_usuario.php";
require_once __DIR__ . "/helpers/bitacora_helper.php";

$usuario = new Usuario($conexion);

if(isset($_POST["login"])){

    $nombre = trim($_POST["nombre"]);
    $password = trim($_POST["contrasena"]);

    if(empty($nombre) || empty($password)){

        $_SESSION["error_login"]="Debe ingresar usuario y contraseña.";

        header("Location: ../index.php");
        exit;
    }

    $datos = $usuario->login($nombre,$password);

    if(!$datos){

        // Se registra el intento fallido usando el nombre digitado,
        // ya que aún no hay una sesión de usuario válida.
        $_SESSION['usuario'] = $nombre;
        registrarBitacora($conexion, 'Autenticación', 'Login fallido', "Intento de inicio de sesión fallido para el usuario \"$nombre\".");
        unset($_SESSION['usuario']);

        $_SESSION["error_login"]="Usuario o contraseña incorrectos.";

        header("Location: ../index.php");
        exit;
    }

    $_SESSION["id_usuario"]=$datos["id_usuario"];
    $_SESSION["id_trabajador"]=$datos["id_trabajador"];
    $_SESSION["usuario"]=$datos["nombre"];
    $_SESSION["tipo_usuario"]=$datos["tipo_usuario"];

    registrarBitacora($conexion, 'Autenticación', 'Login', "Inicio de sesión exitoso (rol: {$datos['tipo_usuario']}).");

    header("Location: ../vistas/dashboard.php");
    exit;
}