<?php
// ARCHIVO: /FUNDACITE/controladores/buscar_trabajador.php
header('Content-Type: application/json');

// Desactivamos visualización de errores temporalmente para que no rompan el JSON que espera JS
ini_set('display_errors', 0); 
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';

$response = ['success' => false, 'message' => 'Trabajador no encontrado'];

if (isset($_GET['cedula'])) {
    $cedula = trim($_GET['cedula']);

    if (!empty($cedula)) {
        // Usamos nombres y apellidos en plural tal como los tienes en tu base de datos
        $sql = "SELECT id_trabajador, nombres, apellidos FROM TRABAJADOR WHERE cedula = ? LIMIT 1";
        
        if (isset($conexion)) {
            $stmt = $conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $cedula);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado->num_rows > 0) {
                    $trabajador = $resultado->fetch_assoc();
                    $response = [
                        'success' => true,
                        'id_trabajador' => $trabajador['id_trabajador'],
                        'nombre_completo' => $trabajador['nombres'] . ' ' . $trabajador['apellidos']
                    ];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Error al preparar la consulta SQL'];
            }
        } else {
            $response = ['success' => false, 'message' => 'No se detectó la variable $conexion'];
        }
    }
}

echo json_encode($response);
exit;
?>