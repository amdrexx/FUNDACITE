<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function esAdministradorODirector(): bool
{
    return esAdministrador() || esDirector();
}

function requireAdministrador(): void
{
    if (!esAdministrador()) {
        header('Location: /FUNDACITE/vistas/dashboard.php');
        exit;
    }
}

function requireAdministradorODirector(): void
{
    if (!esAdministradorODirector()) {
        header('Location: /FUNDACITE/vistas/dashboard.php');
        exit;
    }
}
