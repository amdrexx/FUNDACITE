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

    // Usuario Estándar: solo puede consultar los datos de su propio trabajador.
    if ($id > 0 && !puedeVerDeTrabajador($id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tiene permiso para consultar este trabajador.']);
        exit;
    }

    if ($id > 0) {
        $constanciaModelo = new ConstanciaTrabajo($conexion);
        $trabajador = $constanciaModelo->obtenerTrabajadorCompleto($id);

        if ($trabajador) {
            $cantidadPrimasActivas = $constanciaModelo->contarPrimasActivas($id);
            $nombreTrabajador = (string) ($trabajador['nombre_completo'] ?? 'El trabajador seleccionado');
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
                'cantidad_primas_activas' => $cantidadPrimasActivas,
                'tiene_primas_activas' => ($cantidadPrimasActivas > 0),
                'mensaje_primas' => 'No se puede emitir la Constancia de egreso para ' . $nombreTrabajador . ' porque no tiene primas activas registradas. Registre al menos una prima activa o seleccione Constancia normal.',
                'total_devengado'  => $trabajador['total_devengado'] ?? 0,
                'origen_salario'   => $trabajador['origen_salario'] ?? 'Sin salario registrado',
                'regla_salario'    => $trabajador['regla_salario'] ?? '',
                'mensaje_salario'  => (mb_strtolower(trim((string) ($trabajador['tipo_personal'] ?? '')), 'UTF-8') === 'obrero')
                    ? 'El personal obrero requiere un salario base/mínimo vigente mayor que cero.'
                    : 'Este tipo de personal requiere un salario vigente asociado a su cargo mayor que cero.',
                'tiene_salario'     => ((float) ($trabajador['salario_monto'] ?? 0) > 0 && ($trabajador['origen_salario'] ?? 'Sin salario registrado') !== 'Sin salario registrado'),
            ];
        }
    }
}

echo json_encode($response);
exit;
?>
