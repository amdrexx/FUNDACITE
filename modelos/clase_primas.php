<?php

class Prima {

    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // =========================
    // REGISTRAR
    // =========================
    public function registrarPrima($tipo_prima, $porcentaje, $fecha)
{
    $sql = "INSERT INTO prima
            (tipo_prima, porcentaje, estado, fecha_creacion)
            VALUES (?, ?, 'Activo', ?)";

    $stmt = $this->conexion->prepare($sql);

    $stmt->bind_param("sds", $tipo_prima, $porcentaje, $fecha);

    return $stmt->execute();
}
    // =========================
    // MOSTRAR
    // =========================
    public function mostrarPrimas($buscar = '')
{
        if ($buscar !== '') {
            $sql = "SELECT * FROM PRIMA
                    WHERE tipo_prima LIKE ? OR estado LIKE ?
                    ORDER BY id_prima DESC";
            $stmt = $this->conexion->prepare($sql);
            $param = "%{$buscar}%";
            $stmt->bind_param("ss", $param, $param);
        } else {
            $sql = "SELECT * FROM PRIMA ORDER BY id_prima DESC";
            $stmt = $this->conexion->prepare($sql);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }
    // =========================
    // BUSCAR
    // =========================
    public function buscarPrimaPorId($id)
{
    $sql = "SELECT * FROM prima WHERE id_prima = ?";

    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

    // =========================
    // ACTUALIZAR
    // =========================
    public function actualizarPorcentaje($id, $porcentaje)
{
    $sql = "UPDATE prima SET porcentaje = ? WHERE id_prima = ?";

    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("di", $porcentaje, $id);

    return $stmt->execute();
}
// =========================
    // ELIMINAR
    // =========================
public function eliminarPrima($id)
{
    $sql = "DELETE FROM prima WHERE id_prima = ?";

    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    return $stmt->execute();
}

    public function existeTipoPrima($tipoPrima)
    {
        $sql = "SELECT id_prima FROM PRIMA WHERE tipo_prima = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $tipoPrima);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->num_rows > 0;
    }

    public function guardar($tipoPrima, $porcentaje, $estado, $fecha)
    {
        $sql = "INSERT INTO PRIMA (tipo_prima, porcentaje, estado, fecha)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sdss", $tipoPrima, $porcentaje, $estado, $fecha);
        return $stmt->execute();
    }

    public function actualizar($id, $porcentaje, $estado)
    {
        $sql = "UPDATE PRIMA SET porcentaje = ?, estado = ? WHERE id_prima = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("dsi", $porcentaje, $estado, $id);
        return $stmt->execute();
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM PRIMA WHERE id_prima = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM PRIMA WHERE id_prima = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}