<?php
class clase_asignar_cargo {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Obtener todos los cargos disponibles para el select
    public function obtenerCargos() {
        $sql = "SELECT id_cargo, nombre_cargo FROM CARGO ORDER BY nombre_cargo ASC";
        $result = $this->conexion->query($sql);
        $cargos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cargos[] = $row;
            }
        }
        return $cargos;
    }

    // Obtener SOLO los trabajadores activos que NO TIENEN cargo asignado (desaparecen al asignárselo)
    public function obtenerTodosLosTrabajadores() {
        $sql = "SELECT t.id_trabajador, t.cedula, t.nombres, t.apellidos, 
                       CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo, t.id_cargo
                FROM TRABAJADOR t 
                WHERE t.status = 'Activo' 
                  AND t.id_cargo IS NULL
                ORDER BY t.nombres ASC";
        $result = $this->conexion->query($sql);
        $lista = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $lista[] = $row;
            }
        }
        return $lista;
    }

    // Listar ÚNICAMENTE trabajadores que YA TIENEN un cargo asignado (para la tabla inferior)
    public function listarTrabajadoresConCargo() {
        $sql = "SELECT t.id_trabajador, t.cedula, t.nombres, t.apellidos, 
                       CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo, 
                       c.nombre_cargo, t.id_cargo
                FROM TRABAJADOR t 
                INNER JOIN CARGO c ON t.id_cargo = c.id_cargo 
                WHERE t.status = 'Activo' 
                ORDER BY t.nombres ASC";
        $result = $this->conexion->query($sql);
        $lista = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $lista[] = $row;
            }
        }
        return $lista;
    }

    // Buscar por cédula para búsquedas AJAX (si se requiere)
    public function buscarTrabajadorPorCedula($cedula) {
        $cedula = $this->conexion->real_escape_string($cedula);
        $sql = "SELECT t.id_trabajador, t.cedula, t.nombres, t.apellidos, t.id_cargo, c.nombre_cargo
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
                WHERE t.cedula = '$cedula' AND t.status = 'Activo'
                LIMIT 1";
        $result = $this->conexion->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    // Asignar o desvincular el cargo (UPDATE en TRABAJADOR.id_cargo)
    public function asignarCargo($id_trabajador, $id_cargo) {
        $id_trabajador = intval($id_trabajador);

        if ($id_cargo === null || $id_cargo === '' || $id_cargo === 0) {
            $stmt = $this->conexion->prepare("UPDATE TRABAJADOR SET id_cargo = NULL WHERE id_trabajador = ?");
            $stmt->bind_param("i", $id_trabajador);
        } else {
            $id_cargo = intval($id_cargo);
            $stmt = $this->conexion->prepare("UPDATE TRABAJADOR SET id_cargo = ? WHERE id_trabajador = ?");
            $stmt->bind_param("ii", $id_cargo, $id_trabajador);
        }

        $resultado = $stmt->execute();
        $stmt->close();

        return $resultado;
    }
}
?>