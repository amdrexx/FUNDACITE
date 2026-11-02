<?php
// Consulta en tiempo real del salario vigente asociado al cargo del trabajador.
define('GUARDIAN_JSON', true);
require_once __DIR__ . '/../vistas/includes/guardian.php';
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../conexion.php';
    require_once __DIR__ . '/../modelos/clase_primas.php';

    $idTrabajador = filter_input(INPUT_GET, 'id_trabajador', FILTER_VALIDATE_INT);
    if (!$idTrabajador || $idTrabajador < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Seleccione un trabajador válido.']);
        exit;
    }

    $modelo = new Prima($conexion);
    $trabajador = $modelo->obtenerTrabajador((int) $idTrabajador);
    if (!$trabajador) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se encontró el trabajador seleccionado.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'id_trabajador' => (int) $trabajador['id_trabajador'],
        'id_cargo' => $trabajador['id_cargo'] ?? null,
        'cargo' => (string) ($trabajador['cargo'] ?? ''),
        'salario_cargo_registrado' => (bool) ($trabajador['salario_cargo_registrado'] ?? false),
        'salario_cargo_id' => $trabajador['salario_cargo_id'] ?? null,
        'sueldo_base' => (float) ($trabajador['sueldo_base'] ?? 0),
        'sueldo_cargo' => (float) ($trabajador['sueldo_cargo'] ?? 0),
        'salario_mensual' => (float) ($trabajador['salario_mensual'] ?? 0),
        'base_prima' => (float) ($trabajador['base_prima'] ?? 0),
        'origen_salario' => (string) ($trabajador['origen_salario'] ?? 'base'),
        'tipo_personal' => (string) ($trabajador['tipo_personal'] ?? ''),
        'status' => (string) ($trabajador['status'] ?? '')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Consulta de salario por cargo: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'No fue posible consultar el salario del cargo.']);
}
