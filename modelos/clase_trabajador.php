<?php
class Trabajador {
    private $conexion;
    public $tipo_documento;
    public $cedula;
    public $nombres;
    public $apellidos;
    public $fecha_nacimiento;
    public $genero;
    public $estado_civil;
    public $correo;
    public $telefono;
    public $fecha_ingreso;      // no está en TRABAJADOR, se usará en CONTRATO
    public $id_cargo;
    public $status;             // antes estatus_laboral

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function validar() {
        $errores = [];
        if (empty($this->tipo_documento) || !in_array($this->tipo_documento, ['Cédula','Pasaporte','Cédula de Extranjería']))
            $errores[] = "Tipo de documento inválido.";
        if (empty($this->cedula) || !preg_match('/^\d{6,8}$/', $this->cedula))
            $errores[] = "Cédula inválida.";
        if (empty($this->nombres) || strlen($this->nombres) > 100)
            $errores[] = "Nombres requeridos (máx 100).";
        if (empty($this->apellidos) || strlen($this->apellidos) > 100)
            $errores[] = "Apellidos requeridos (máx 100).";
        if (empty($this->fecha_nacimiento))
            $errores[] = "Fecha de nacimiento requerida.";
        if (empty($this->genero) || !in_array($this->genero, ['Masculino','Femenino','No Binario']))
            $errores[] = "Género inválido.";
        if (empty($this->estado_civil) || !in_array($this->estado_civil, ['Soltero(a)','Casado(a)','Divorciado(a)','Viudo(a)','Concubinato']))
            $errores[] = "Estado civil inválido.";
        if (!empty($this->correo) && !filter_var($this->correo, FILTER_VALIDATE_EMAIL))
            $errores[] = "Correo electrónico no válido.";
        if (empty($this->fecha_ingreso))
            $errores[] = "Fecha de ingreso requerida.";
        if (empty($this->id_cargo))
            $errores[] = "Seleccione un cargo.";
        if (empty($this->status) || !in_array($this->status, ['Activo','Inactivo','Suspendido','Jubilado']))
            $errores[] = "Estatus laboral inválido.";
        return $errores;
    }

    public function guardar() {
        // Insertar en TRABAJADOR (sin edad, la calcula la vista)
        $sql = "INSERT INTO TRABAJADOR
                (tipo_documento, cedula, nombres, apellidos, fecha_nacimiento, genero, estado_civil, telefono, correo, status, id_cargo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            die("Error en la preparación: " . $this->conexion->error);
        }

        $stmt->bind_param(
            "ssssssssssi",
            $this->tipo_documento,
            $this->cedula,
            $this->nombres,
            $this->apellidos,
            $this->fecha_nacimiento,
            $this->genero,
            $this->estado_civil,
            $this->telefono,
            $this->correo,
            $this->status,
            $this->id_cargo
        );

        $resultado = $stmt->execute();
        $stmt->close();

        // Si se insertó correctamente, insertar en CONTRATO (si se desea)
        if ($resultado) {
            $id_trabajador = $this->conexion->insert_id;
            // Aquí podrías insertar en CONTRATO con fecha_ingreso y salario
            // Por simplicidad, lo omitimos o lo haces después.
        }
        return $resultado;
    }
}