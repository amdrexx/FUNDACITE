<?php
// ARCHIVO: /FUNDACITE/ajax/ajax_constancia_trabajador.php
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_constancia.php';

$response = ['success' => false, 'message' => 'Trabajador no encontrado'];

if (isset($_GET['id_trabajador'])) {
    $id = (int) $_GET['id_trabajador'];

    if ($id > 0) {
        $constancia = new clase_constancia($conexion);
        $trabajador = $constancia->obtenerTrabajadorCompleto($id);

        if ($trabajador) {
            $response = [
                'success'          => true,
                'id_trabajador'    => $trabajador['id_trabajador'],
                'nombre_completo'  => $trabajador['nombre_completo'],
                'cedula'           => $trabajador['cedula'],
                'cargo'            => $trabajador['cargo'] ?? '',
                'fecha_ingreso'    => $trabajador['fecha_ingreso'],
                'salario_monto'    => $trabajador['salario_monto'] ?? '',
            ];
        }
    }
}

echo json_encode($response);
exit;
?>
