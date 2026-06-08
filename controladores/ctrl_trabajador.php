<?php
require_once __DIR__ . '/../modelos/clase_trabajador.php';

class TrabajadorController {
    private $conexion;
    private $trabajadorModel;

    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->trabajadorModel = new Trabajador($conexion);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Mapeo de tipoDoc del formulario a los valores reales de la BD
            $tipoDocMap = [
                'V' => 'Cédula',
                'E' => 'Cédula de Extranjería'
            ];
            $tipoDoc = $_POST['tipoDoc'] ?? '';
            $tipo_documento = $tipoDocMap[$tipoDoc] ?? '';

            // Mapeo de estatus_laboral (el formulario usa "Retirado" -> BD usa "Inactivo")
            $estatus_laboral = $_POST['estatus_laboral'] ?? '';
            $statusMap = [
                'Activo'     => 'Activo',
                'Jubilado'   => 'Jubilado',
                'Suspendido' => 'Suspendido',
                'Retirado'   => 'Inactivo'   // ajuste obligatorio
            ];
            $status = $statusMap[$estatus_laboral] ?? '';

            $this->trabajadorModel->tipo_documento   = $tipo_documento;
            $this->trabajadorModel->cedula            = $_POST['cedula'] ?? '';
            $this->trabajadorModel->nombres           = $_POST['nombres'] ?? '';
            $this->trabajadorModel->apellidos         = $_POST['apellidos'] ?? '';
            $this->trabajadorModel->fecha_nacimiento  = $_POST['fecha'] ?? '';
            $this->trabajadorModel->genero            = $_POST['genero'] ?? '';
            $this->trabajadorModel->estado_civil      = $_POST['estadoCivil'] ?? '';
            $this->trabajadorModel->correo            = $_POST['correoElectronico'] ?? '';
            $this->trabajadorModel->telefono          = $_POST['numeroTelefono'] ?? '';
            $this->trabajadorModel->fecha_ingreso     = $_POST['fecha_ingreso'] ?? '';
            $this->trabajadorModel->id_cargo          = $_POST['cargo_id'] ?? '';
            $this->trabajadorModel->status            = $status;

            $errores = $this->trabajadorModel->validar();

            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $_SESSION['old_input'] = $_POST;
                header('Location: ../vistas/registro_trabajador.php');
                exit;
            }

            if ($this->trabajadorModel->guardar()) {
                $_SESSION['exito'] = "Trabajador registrado correctamente.";
                unset($_SESSION['old_input']);
                header('Location: ../vistas/registro_trabajador.php?exito=1');
            } else {
                $_SESSION['errores'] = ["Error al guardar en la base de datos: " . $this->conexion->error];
                $_SESSION['old_input'] = $_POST;
                header('Location: ../vistas/registro_trabajador.php');
            }
            exit;
        }
    }
}