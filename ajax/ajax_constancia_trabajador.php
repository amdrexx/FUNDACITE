<?php
// ARCHIVO: /FUNDACITE/ajax/ajax_constancia_trabajador.php
define('GUARDIAN_JSON', true); // sin sesión: 401 en JSON (y queda en bitácora)
require_once __DIR__ . '/../vistas/includes/guardian.php';
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_constancia.php';

$response = ['success' => false, 'message' => 'Trabajador no encontrado'];

if (isset($_GET['id_trabajador'])) {
    $id = (int) $_GET['id_trabajador'];

    if ($id > 0) {
        $constanciaModelo = new ConstanciaTrabajo($conexion);
        $trabajador = $constanciaModelo->obtenerTrabajadorCompleto($id);

        if ($trabajador) {
            $response = [
                'success'          => true,
                'id_trabajador'    => $trabajador['id_trabajador'],
                'nombre_completo'  => $trabajador['nombre_completo'],
                'cedula'           => $trabajador['cedula'],
                'cargo'            => $trabajador['cargo'] ?? '',
                'tipo_personal'    => $trabajador['tipo_personal'] ?? '',
                'fecha_primer_contrato' => $trabajador['fecha_primer_contrato'],
                'salario_monto'    => $trabajador['salario_monto'] ?? 0,
                'total_primas'     => $trabajador['total_primas'] ?? 0,
                'total_devengado'  => $trabajador['total_devengado'] ?? 0,
                'origen_salario'   => $trabajador['origen_salario'] ?? 'Sin salario registrado',
                'tiene_salario'     => ((float) ($trabajador['salario_monto'] ?? 0) > 0 && ($trabajador['origen_salario'] ?? 'Sin salario registrado') !== 'Sin salario registrado'),
            ];
        }
    }
}

echo json_encode($response);
exit;
?>
