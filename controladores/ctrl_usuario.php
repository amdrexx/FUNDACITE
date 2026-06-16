<?php
session_start();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_usuario.php';

$usuarioModel = new Usuario($conexion);

function volver_a_vista($id = null)
{
    $url = '../vistas/registro_usuario.php';

    if (!empty($id)) {
        $url .= '?id=' . urlencode($id);
    }

    header("Location: $url");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario    = trim($_POST['id_usuario'] ?? '');
    $id_trabajador = trim($_POST['trabajadorAsociado'] ?? '');
    $nombre        = trim($_POST['usuario'] ?? '');
    $contrasena    = $_POST['contrasena'] ?? '';
    $tipo_usuario  = trim($_POST['tipoUsuario'] ?? '');
    $status        = trim($_POST['status'] ?? 'Activo');

    $_SESSION['old_usuario_form'] = $_POST;

    if ($id_trabajador === '' || $nombre === '' || $tipo_usuario === '' || $status === '') {
        $_SESSION['mensaje_error'] = 'Por favor, complete todos los campos obligatorios.';
        volver_a_vista($id_usuario);
    }

    $id_usuario_excluir = !empty($id_usuario) ? (int)$id_usuario : null;
    $id_trabajador = (int)$id_trabajador;

    if (empty($id_usuario) && $contrasena === '') {
        $_SESSION['mensaje_error'] = 'La contraseña es obligatoria para nuevos registros.';
        volver_a_vista();
    }

    if ($usuarioModel->existeNombre($nombre, $id_usuario_excluir)) {
        $_SESSION['mensaje_error'] = 'Ya existe un usuario con ese nombre.';
        volver_a_vista($id_usuario);
    }

    if ($usuarioModel->existeTrabajador($id_trabajador, $id_usuario_excluir)) {
        $_SESSION['mensaje_error'] = 'Ese trabajador ya tiene un usuario asignado.';
        volver_a_vista($id_usuario);
    }

    try {
        $datos = [
            'id_trabajador' => $id_trabajador,
            'nombre'        => $nombre,
            'contrasena'    => $contrasena,
            'tipo_usuario'  => $tipo_usuario,
            'status'        => $status
        ];

        if (!empty($id_usuario)) {
            $usuarioModel->actualizar((int)$id_usuario, $datos);
            $_SESSION['mensaje_exito'] = 'Usuario actualizado correctamente.';
        } else {
            $usuarioModel->registrar($datos);
            $_SESSION['mensaje_exito'] = 'Usuario registrado correctamente.';
        }

        unset($_SESSION['old_usuario_form']);
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = 'Error en la base de datos: ' . $e->getMessage();
    }

    volver_a_vista();
}

if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    try {
        $usuarioModel->eliminar((int)$_GET['id']);
        $_SESSION['mensaje_exito'] = 'Usuario eliminado correctamente.';
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = 'Error al eliminar: ' . $e->getMessage();
    }

    volver_a_vista();
}

volver_a_vista();