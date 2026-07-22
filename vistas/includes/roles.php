<?php
// ============================================================================
// ARCHIVO: vistas/includes/roles.php
// DESCRIPCIÓN: Control de acceso basado en roles.
//              Roles válidos del sistema: Analista, Director, Administrador
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

/**
 * Director y Administrador comparten un segundo nivel de permisos:
 * eliminar persona/trabajador, ingresar datos de vacaciones y constancias de trabajo.
 */
function esAdministradorODirector(): bool
{
    return esAdministrador() || esDirector();
}

/**
 * Corta la ejecución y redirige al dashboard si el rol de la sesión
 * no está dentro de la lista de roles permitidos.
 *
 * @param string[] $rolesPermitidos Ej: ['Administrador'] o ['Administrador','Director']
 */
function requerirRol(array $rolesPermitidos): void
{
    if (!in_array(usuarioActual(), $rolesPermitidos, true)) {
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
