<?php
// ARCHIVO: /FUNDACITE/modelos/clase_convenio.php
// Acceso a datos del módulo Convenio (convención colectiva con un gremio).

class Convenio
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    /**
     * Estado del convenio según la fecha de hoy:
     * Por iniciar / Vigente / Vencido.
     */
    public static function estadoVigencia($fechaInicio, $fechaVencimiento): string
    {
        $hoy = date('Y-m-d');

        if ($fechaInicio > $hoy) {
            return 'Por iniciar';
        }
        if ($fechaVencimiento < $hoy) {
            return 'Vencido';
        }
        return 'Vigente';
    }

    /**
     * Un gremio no puede tener dos convenios con periodos que se solapen.
     * $idExcluir se usa al actualizar para no compararse consigo mismo.
     */
    public function existeSolape($nombreGremio, $fechaInicio, $fechaVencimiento, $idExcluir = 0): bool
    {
        $nombreGremio = trim($nombreGremio);
        $idExcluir = (int) $idExcluir;

        $sql = "SELECT id_convenio FROM CONVENIO
                WHERE LOWER(nombre_gremio) = LOWER(?)
                  AND fecha_inicio <= ?
                  AND fecha_vencimiento >= ?
                  AND id_convenio != ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssi", $nombreGremio, $fechaVencimiento, $fechaInicio, $idExcluir);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function registrarConvenio($nombreGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute)
    {
        $nombreGremio = trim($nombreGremio);
        $diasDisfrute = (int) $diasDisfrute;

        $sql = "INSERT INTO CONVENIO (nombre_gremio, fecha_inicio, fecha_vencimiento, dias_disfrute)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssi", $nombreGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute);

        return $stmt->execute() ? $this->conexion->insert_id : false;
    }

    /**
     * El convenio colectivo vigente hoy (fecha_inicio <= hoy <= fecha_vencimiento).
     * Si hay más de uno vigente al mismo tiempo, se toma el de fecha_inicio más
     * reciente. Devuelve null si no hay ningún convenio vigente registrado.
     */
    public function obtenerConvenioVigente()
    {
        $sql = "SELECT id_convenio, nombre_gremio, fecha_inicio, fecha_vencimiento, dias_disfrute
                FROM CONVENIO
                WHERE fecha_inicio <= CURDATE() AND fecha_vencimiento >= CURDATE()
                ORDER BY fecha_inicio DESC, id_convenio DESC
                LIMIT 1";
        $resultado = $this->conexion->query($sql);

        return ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_assoc() : null;
    }

    public function listarConvenios($buscar = '')
    {
        $sql = "SELECT id_convenio, nombre_gremio, fecha_inicio, fecha_vencimiento, dias_disfrute
                FROM CONVENIO";

        if ($buscar !== '') {
            $sql .= " WHERE nombre_gremio LIKE ?";
            $stmt = $this->conexion->prepare($sql . " ORDER BY id_convenio DESC");
            $like = '%' . $buscar . '%';
            $stmt->bind_param("s", $like);
            $stmt->execute();
            $resultado = $stmt->get_result();
        } else {
            $resultado = $this->conexion->query($sql . " ORDER BY id_convenio DESC");
        }

        return ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerConvenioPorId($idConvenio)
    {
        $sql = "SELECT id_convenio, nombre_gremio, fecha_inicio, fecha_vencimiento, dias_disfrute
                FROM CONVENIO WHERE id_convenio = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idConvenio);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function actualizarConvenio($idConvenio, $nombreGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute)
    {
        $nombreGremio = trim($nombreGremio);
        $diasDisfrute = (int) $diasDisfrute;

        $sql = "UPDATE CONVENIO
                SET nombre_gremio = ?, fecha_inicio = ?, fecha_vencimiento = ?, dias_disfrute = ?
                WHERE id_convenio = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssii", $nombreGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute, $idConvenio);

        return $stmt->execute();
    }

    public function eliminarConvenio($idConvenio)
    {
        $sql = "DELETE FROM CONVENIO WHERE id_convenio = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idConvenio);

        return $stmt->execute();
    }
}
?>
