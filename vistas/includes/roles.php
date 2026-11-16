<?php
// ============================================================================
// ARCHIVO: vistas/includes/roles.php
// DESCRIPCIÓN: Control de acceso basado en roles.
//              Roles válidos del sistema: Analista, Director, Administrador,
//              Usuario Estándar
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devuelve el rol (tipo_usuario) del usuario autenticado en la sesión actual.
 */
function usuarioActual(): string
{
    return $_SESSION['tipo_usuario'] ?? '';
}

function esAdministrador(): bool
{
    return usuarioActual() === 'Administrador';
}

function esDirector(): bool
{
    return usuarioActual() === 'Director';
}

function esAnalista(): bool
{
    return usuarioActual() === 'Analista';
}

function esUsuarioEstandar(): bool
{
    return usuarioActual() === 'Usuario Estándar';
}

/**
 * id_trabajador asociado al usuario autenticado (0 si no hay).
 * El Usuario Estándar solo puede ver y generar SUS propios registros
 * (solicitudes de días de disfrute y constancias de trabajo).
 */
function idTrabajadorSesion(): int
{
    return (int) ($_SESSION['id_trabajador'] ?? 0);
}

/**
 * Si el usuario es Usuario Estándar devuelve su id_trabajador para filtrar
 * consultas; para el resto de roles devuelve null (sin filtro, igual que antes).
 */
function filtroTrabajadorSesion(): ?int
{
    return esUsuarioEstandar() ? idTrabajadorSesion() : null;
}

/**
 * ¿Puede el usuario actual acceder a un registro de este trabajador?
 * Usuario Estándar: solo el suyo. Resto de roles: sin cambios (siempre true).
 */
function puedeVerDeTrabajador(int $idTrabajador): bool
{
    if (!esUsuarioEstandar()) {
        return true;
    }
    return $idTrabajador > 0 && $idTrabajador === idTrabajadorSesion();
}

/**
 * Director y Administrador comparten un segundo nivel de permisos:
 * eliminar persona/trabajador, ingresar datos de vacaciones y constancias de trabajo.
 */
function esAdministradorODirector(): bool
{
    return esAdministrador() || esDirector();
}

function esAdministradorODirectorOAnalista(): bool
{
    return esAdministrador() || esDirector() || esAnalista();
}

/**
 * Corta la ejecución si el rol de la sesión no está dentro de la lista de roles
 * permitidos, y lo deja en la bitácora:
 *   · sin sesión          → "Acceso sin sesión" y redirige al login.
 *   · rol sin permiso     → "Acceso denegado" (usuario, rol y URL) y redirige al dashboard.
 *
 * @param string[] $rolesPermitidos Ej: ['Administrador'] o ['Administrador','Director']
 */
function requerirRol(array $rolesPermitidos): void
{
    if (!isset($_SESSION['id_usuario'])) {
        require_once __DIR__ . '/../../controladores/helpers/bitacora_helper.php';
        registrarAccesoRestringido(false);

        header('Location: /FUNDACITE/index.php');
        exit;
    }

    if (!in_array(usuarioActual(), $rolesPermitidos, true)) {
        require_once __DIR__ . '/../../controladores/helpers/bitacora_helper.php';
        registrarAccesoRestringido(true, $rolesPermitidos);

        $_SESSION['error_permiso'] = "No tiene permisos para acceder a esta sección.";
        header('Location: /FUNDACITE/vistas/dashboard.php');
        exit;
    }
}

/**
 * Acceso exclusivo para Administrador.
 * Casos de uso: registrar/editar/eliminar usuario, roles y permisos,
 * administración general del sistema.
 */
function requireAdministrador(): void
{
    requerirRol(['Administrador']);
}

/**
 * Acceso para Administrador o Director.
 * Casos de uso: registrar/editar contrato, registrar vacaciones,
 * constancias de trabajo / generación de PDF.
 * (Eliminar persona/trabajador es una función compartida por los 3 roles
 * y por lo tanto NO usa este guard; solo requiere sesión iniciada.)
 */
function requireAdministradorODirector(): void
{
    requerirRol(['Administrador', 'Director']);
}

/**
 * Acceso para Administrador, Director o Usuario Estándar.
 * Casos de uso: solicitar días de disfrute y sacar constancia de trabajo
 * (el Usuario Estándar queda limitado a sus propios datos; ver
 * filtroTrabajadorSesion() / puedeVerDeTrabajador()).
 */
function requireAdminDirectorOEstandar(): void
{
    requerirRol(['Administrador', 'Director', 'Usuario Estándar']);
}

/**
 * Deniega el acceso a un registro que pertenece a otro trabajador (solo aplica
 * al Usuario Estándar). Lo deja en la bitácora y lo devuelve al dashboard.
 */
function denegarRegistroAjeno(): void
{
    require_once __DIR__ . '/../../controladores/helpers/bitacora_helper.php';
    registrarAccesoRestringido(true, ['Administrador', 'Director', 'propietario del registro']);

    $_SESSION['error_permiso'] = "Solo puede consultar sus propios registros.";
    header('Location: /FUNDACITE/vistas/dashboard.php');
    exit;
}
