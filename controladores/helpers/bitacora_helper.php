<?php
// ============================================================================
// ARCHIVO: controladores/helpers/bitacora_helper.php
// DESCRIPCIÓN: Función auxiliar para registrar en la BITÁCORA las acciones
//              que los usuarios realizan desde cualquier controlador
//              (crear, editar, eliminar, iniciar/cerrar sesión, listar,
//              consultar o generar reportes/PDF).
//
// USO:
//   require_once __DIR__ . '/helpers/bitacora_helper.php';
//   registrarBitacora($conexion, 'Trabajadores', 'Crear', "Registró al trabajador CI $cedula");
// ============================================================================

require_once __DIR__ . '/../../modelos/clase_bitacora.php';

if (!function_exists('registrarBitacora')) {

    /**
     * Registra una acción del usuario autenticado (o del sistema) en la bitácora.
     * Nunca interrumpe el flujo normal de la aplicación: si algo falla al
     * escribir el registro (p. ej. la tabla aún no existe), solo se deja
     * constancia en el log de errores de PHP.
     *
     * @param mysqli $conexion    Conexión activa a la base de datos.
     * @param string $modulo      Módulo/entidad afectada (ej: "Trabajadores").
     * @param string $accion      "Crear" | "Editar" | "Eliminar" | "Login" | "Logout" | "Listar" | "Consultar" | "Generar PDF"
     * @param string $descripcion Detalle legible de la acción realizada.
     */
    function registrarBitacora($conexion, string $modulo, string $accion, string $descripcion = ''): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        try {
            if (!($conexion instanceof mysqli)) {
                return;
            }

            $bitacora = new Bitacora($conexion);

            $id_usuario = $_SESSION['id_usuario'] ?? null;
            $usuario    = $_SESSION['usuario'] ?? 'Sistema';

            $bitacora->registrar($id_usuario, $usuario, $modulo, $accion, $descripcion);
        } catch (\Throwable $e) {
            error_log('Error al registrar bitácora: ' . $e->getMessage());
        }
    }
}
