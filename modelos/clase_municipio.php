<?php
require_once("../conexion.php");

class MunicipioModel
{
    private $conexion;

    public function __construct()
    {
        global $conexion;
        $this->conexion = $conexion;
    }

    // Obtener todos los estados
    public function obtenerEstados()
    {
        $sql = "SELECT cod_est, nombre
                FROM ESTADO
                ORDER BY nombre ASC";

        $resultado = $this->conexion->query($sql);

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // Registrar municipio
    public function guardar($cod_est, $nombre)
    {
        $sql = "INSERT INTO MUNICIPIO (cod_est, nombre)
                VALUES (?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("is", $cod_est, $nombre);

        return $stmt->execute();
    }

    // Listar municipios con su estado
    public function listar()
    {
        $sql = "SELECT
                    m.cod_muni,
                    m.cod_est,
                    m.nombre AS municipio,
                    e.nombre AS estado
                FROM MUNICIPIO m
                INNER JOIN ESTADO e
                    ON m.cod_est = e.cod_est
                ORDER BY e.nombre ASC, m.nombre ASC";

        $resultado = $this->conexion->query($sql);

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // Buscar un municipio por ID
    public function buscar($cod_muni)
    {
        $sql = "SELECT *
                FROM MUNICIPIO
                WHERE cod_muni = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $cod_muni);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->fetch_assoc();
    }

    // Actualizar municipio
    public function actualizar($cod_muni, $cod_est, $nombre)
    {
        $sql = "UPDATE MUNICIPIO
                SET cod_est = ?, nombre = ?
                WHERE cod_muni = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("isi", $cod_est, $nombre, $cod_muni);

        return $stmt->execute();
    }

    // Eliminar municipio
    public function eliminar($cod_muni)
    {
        $sql = "DELETE FROM MUNICIPIO
                WHERE cod_muni = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $cod_muni);

        return $stmt->execute();
    }
}
?>