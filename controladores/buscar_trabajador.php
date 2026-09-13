<?php
// ARCHIVO: controladores/buscar_trabajador.php

ob_start();
header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function ($e) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "Error PHP: " . $e->getMessage()
    ]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// 1. Incluir conexion.php
$rutaConexion = __DIR__ . "/../conexion.php";
if (!file_exists($rutaConexion)) {
    $rutaConexion = $_SERVER['DOCUMENT_ROOT'] . "/FUNDACITE/conexion.php";
}

if (!file_exists($rutaConexion)) {
    throw new Exception("No se encontró el archivo conexion.php");
}

require_once $rutaConexion;

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    throw new Exception("La conexión no es un objeto MySQLi válido.");
}

$cedulaRecibida = $_GET['cedula'] ?? '';
$soloNumeros = preg_replace('/[^0-9]/', '', $cedulaRecibida);

if (empty($soloNumeros)) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "Por favor ingresa una cédula válida."
    ]);
    exit;
}

// 2. Consulta de TRABAJADOR unida con CARGO
$sql = "SELECT 
            t.id_trabajador, 
            t.nombres, 
            t.apellidos, 
            t.id_cargo,
            c.nombre_cargo
        FROM TRABAJADOR t
        LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
        WHERE t.cedula = ? OR t.cedula LIKE ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    throw new Exception("Error en la consulta SQL: " . $conexion->error);
}

$paramLike = '%' . $soloNumeros . '%';
$stmt->bind_param("ss", $cedulaRecibida, $paramLike);
$stmt->execute();
$stmt->bind_result($id_trabajador, $nombres, $apellidos, $id_cargo, $nombre_cargo);

if ($stmt->fetch()) {
    $stmt->close();

    if (!empty($nombre_cargo)) {
        $cargoFinal = $nombre_cargo;
    } elseif (!empty($id_cargo)) {
        $cargoFinal = "ID Cargo (" . $id_cargo . ") sin nombre asociado";
    } else {
        $cargoFinal = "Sin cargo asignado";
    }

    if (ob_get_length()) ob_clean();

    echo json_encode([
        "success" => true,
        "id_trabajador" => $id_trabajador,
        "nombre_completo" => trim($nombres . ' ' . $apellidos),
        "cargo" => $cargoFinal
    ]);
    exit;

} else {
    $stmt->close();
    if (ob_get_length()) ob_clean();

    echo json_encode([
        "success" => false,
        "message" => "Cédula no encontrada."
    ]);
    exit;
}