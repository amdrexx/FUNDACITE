<?php
// ======================================================
// ARCHIVO: conexion.php
// DESCRIPCIÓN: Configura la conexión a la base de datos MySQL
// ======================================================

$host     = "localhost";        // Servidor de la BD
$usuario  = "root";             // Usuario de MySQL
$password = "";                 // Contraseña (en LAMPP/XAMPP suele estar vacía)
$database = "bd_rrhh_fundacite_yaracuy";  // 👈 CAMBIA ESTO por el nombre real de tu BD

// Desde PHP 8.1, mysqli lanza una excepción (en vez de solo marcar connect_error)
// cuando la conexión falla, incluida la base de datos no existente (error 1049).
// Eso convertía un simple "no hay BD" en un error 500 que tumbaba todo el sistema,
// incluido el panel de Exportar/Importar que se necesita justamente para restaurarla.
// Se desactivan las excepciones de mysqli para manejar el caso nosotros mismos.
mysqli_report(MYSQLI_REPORT_OFF);

// Crear la conexión usando MySQLi (orientado a objetos)
$conexion = new mysqli($host, $usuario, $password, $database);

// Si la base de datos configurada todavía no existe, nos conectamos al servidor
// sin seleccionarla. No la creamos aquí: el importador debe crearla y cargar el
// respaldo antes de que las páginas de negocio intenten usar sus tablas.
if ($conexion->connect_error && $conexion->connect_errno === 1049) {
    $conexion = new mysqli($host, $usuario, $password);
}

// Verificar si hubo error de conexión (servidor caído, credenciales inválidas, etc.)
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

// (Opcional) Definir el charset para evitar problemas con tildes y eñes
$conexion->set_charset("utf8mb4");

// La variable $conexion ya está disponible para ser usada en otros archivos
// que incluyan este archivo (con require_once o include_once)
?>
