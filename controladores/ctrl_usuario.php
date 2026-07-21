<?php
// ============================================================================
// ARCHIVO: controladores/ctrl_usuario.php
// DESCRIPCIÓN: Controlador para la gestión de usuarios
// ============================================================================
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vistas/includes/permissions.php';
require_once "../conexion.php";
require_once "../modelos/clase_usuario.php";

$usuario = new Usuario($conexion);

// ============================================================================
// REGISTRAR USUARIO
// ============================================================================
if (isset($_POST['registrar_usuario'])) {

    if (!esAdministrador()) {
        $_SESSION['error_registro'] = ["No tienes permisos para registrar usuarios."];
        header("Location: ../vistas/lista_usuarios.php");
        exit;
    }

    $id_trabajador = trim($_POST['id_trabajador'] ?? '');
    $nombre         = trim($_POST['nombre'] ?? '');
    $contrasena     = trim($_POST['contrasena'] ?? '');
    $tipo_usuario   = trim($_POST['tipo_usuario'] ?? '');

    $errores = [];

    $_SESSION['old_input'] = $_POST;

    // ==========================
    // VALIDACIONES
    // ==========================

    if (empty($id_trabajador)) {
        $errores[] = "Debe seleccionar un trabajador.";
    }

    if (empty($nombre)) {
        $errores[] = "Debe ingresar un nombre de usuario.";
    }

    if (strlen($nombre) < 4) {
        $errores[] = "El nombre de usuario debe tener al menos 4 caracteres.";
    }

    if (empty($contrasena)) {
        $errores[] = "Debe ingresar una contraseña.";
    }

    if (strlen($contrasena) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if (empty($tipo_usuario)) {
        $errores[] = "Debe seleccionar un tipo de usuario.";
    }

    if ($usuario->existeNombre($nombre)) {
        $errores[] = "El nombre de usuario ya existe.";
    }

    if ($usuario->trabajadorTieneUsuario($id_trabajador)) {
        $errores[] = "El trabajador ya posee un usuario registrado.";
    }

    // ==========================
    // SI HAY ERRORES
    // ==========================

    if (!empty($errores)) {

        $_SESSION['error_registro'] = $errores;

        header("Location: ../vistas/registrar_usuario.php");
        exit;
    }

    // ==========================
    // REGISTRAR
    // ==========================

    if ($usuario->registrar(
        $id_trabajador,
        $nombre,
        $contrasena,
        $tipo_usuario
    )) {

        unset($_SESSION['old_input']);

        $_SESSION['exito_registro'] = "Usuario registrado correctamente.";

    } else {

        $_SESSION['error_registro'][] = "No fue posible registrar el usuario.";
    }

    header("Location: ../vistas/registrar_usuario.php");
    exit;
}

// ============================================================================
// ACTUALIZAR USUARIO
// ============================================================================
if (isset($_POST['editar_usuario'])) {

    if (!esAdministrador()) {
        $_SESSION['error_registro'] = ["No tienes permisos para editar usuarios."];
        header("Location: ../vistas/lista_usuarios.php");
        exit;
    }

    $id_usuario     = intval($_POST['id_usuario']);
    $id_trabajador  = intval($_POST['id_trabajador']);
    $nombre         = trim($_POST['nombre']);
    $tipo_usuario   = trim($_POST['tipo_usuario']);
    $contrasena     = trim($_POST['contrasena']);

    $errores = [];

    if (empty($id_trabajador)) {
        $errores[] = "Debe seleccionar un trabajador.";
    }

    if (empty($nombre)) {
        $errores[] = "Debe ingresar un nombre de usuario.";
    }

    if (empty($tipo_usuario)) {
        $errores[] = "Debe seleccionar un tipo de usuario.";
    }

    if ($usuario->existeNombre($nombre, $id_usuario)) {
        $errores[] = "Ese nombre de usuario ya está registrado.";
    }

    if ($usuario->trabajadorTieneUsuario($id_trabajador, $id_usuario)) {
        $errores[] = "Ese trabajador ya tiene un usuario asignado.";
    }

    if (!empty($errores)) {

        $_SESSION['error_registro'] = $errores;

        header("Location: ../vistas/lista_usuarios.php");
        exit;
    }

    $actualizado = $usuario->actualizar(
        $id_usuario,
        $id_trabajador,
        $nombre,
        $tipo_usuario
    );

    if ($actualizado) {

        if (!empty($contrasena)) {
            $usuario->actualizarPassword($id_usuario, $contrasena);
        }

        $_SESSION['exito_registro'] = "Usuario actualizado correctamente.";

    } else {

        $_SESSION['error_registro'][] = "No fue posible actualizar el usuario.";
    }

    header("Location: ../vistas/lista_usuarios.php");
    exit;
}

// ============================================================================
// ELIMINACIÓN LÓGICA
// ============================================================================
if (isset($_GET['eliminar'])) {

    if (!esAdministrador()) {
        $_SESSION['error_registro'] = ["No tienes permisos para eliminar usuarios."];
        header("Location: ../vistas/lista_usuarios.php");
        exit;
    }

    $id_usuario = intval($_GET['eliminar']);

    if ($usuario->eliminar($id_usuario)) {

        $_SESSION['exito_registro'] = "Usuario desactivado correctamente.";

    } else {

        $_SESSION['error_registro'][] = "No fue posible desactivar el usuario.";
    }

    header("Location: ../vistas/lista_usuarios.php");
    exit;
}

// ============================================================================
// ACTIVAR USUARIO
// ============================================================================
if (isset($_GET['activar'])) {

    if (!esAdministrador()) {
        $_SESSION['error_registro'] = ["No tienes permisos para activar usuarios."];
        header("Location: ../vistas/lista_usuarios.php");
        exit;
    }

    $id_usuario = intval($_GET['activar']);

    if ($usuario->activar($id_usuario)) {

        $_SESSION['exito_registro'] = "Usuario activado correctamente.";

    } else {

        $_SESSION['error_registro'][] = "No fue posible activar el usuario.";
    }

    header("Location: ../vistas/lista_usuarios.php");
    exit;
}

// ============================================================================
// BUSCAR USUARIO POR ID
// ============================================================================
if (isset($_GET['buscar'])) {

    $id_usuario = intval($_GET['buscar']);

    $datos = $usuario->buscarPorId($id_usuario);

    if ($datos) {

        $_SESSION['usuario_editar'] = $datos;

    } else {

        $_SESSION['error_registro'][] = "Usuario no encontrado.";
    }

    header("Location: ../vistas/lista_usuarios.php");
    exit;
}
?>