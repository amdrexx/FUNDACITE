<?php
class clase_asignar_cargo {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Obtener todos los cargos disponibles para el select
    public function obtenerCargos() {
        $sql = "SELECT id_cargo, nombre_cargo FROM CARGO ORDER BY nombre_cargo ASC";
        $result = mysqli_query($this->conexion, $sql);
        $cargos = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cargos[] = $row;
            }
        }
        return $cargos;
    }

    // Obtener TODOS los trabajadores activos (con o sin cargo) para el select de asignación
    public function obtenerTodosLosTrabajadores() {
        $sql = "SELECT t.id_trabajador, t.cedula, CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo, t.id_cargo
                FROM TRABAJADOR t 
                WHERE t.status = 'Activo' 
                ORDER BY t.nombres ASC";
        $result = mysqli_query($this->conexion, $sql);
        $lista = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $lista[] = $row;
            }
        }
        return $lista;
    }

    // Listar ÚNICAMENTE trabajadores que YA TIENEN un cargo asignado (INNER JOIN)
    public function listarTrabajadoresConCargo() {
        $sql = "SELECT t.id_trabajador, t.cedula, CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo, 
                       c.nombre_cargo, t.id_cargo
                FROM TRABAJADOR t 
                INNER JOIN CARGO c ON t.id_cargo = c.id_cargo 
                WHERE t.status = 'Activo' 
                ORDER BY t.nombres ASC";
        $result = mysqli_query($this->conexion, $sql);
        $lista = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $lista[] = $row;
            }
        }
        return $lista;
    }

    // Asignar o actualizar el cargo del trabajador
    public function asignarCargo($id_trabajador, $id_cargo) {
        $id_trabajador = intval($id_trabajador);
        $id_cargo = ($id_cargo === null || $id_cargo === '') ? "NULL" : intval($id_cargo);

        $sql = "UPDATE TRABAJADOR SET id_cargo = $id_cargo WHERE id_trabajador = $id_trabajador";
        return mysqli_query($this->conexion, $sql);
    }
}
?>