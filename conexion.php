<?php
// ======================================================
// ARCHIVO: conexion.php
// DESCRIPCIÓN: Configura la conexión a la base de datos MySQL
// ======================================================

$host     = "localhost";        // Servidor de la BD
$usuario  = "root";             // Usuario de MySQL
$password = "";                 // Contraseña (en LAMPP/XAMPP suele estar vacía)
$database = "nombre_de_tu_bd";  // 👈 CAMBIA ESTO por el nombre real de tu BD

// Crear la conexión usando MySQLi (orientado a objetos)
$conexion = new mysqli($host, $usuario, $password, $database);

// Verificar si hubo error de conexión
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

// (Opcional) Definir el charset para evitar problemas con tildes y eñes
$conexion->set_charset("utf8mb4");

// La variable $conexion ya está disponible para ser usada en otros archivos
// que incluyan este archivo (con require_once o include_once)
?>