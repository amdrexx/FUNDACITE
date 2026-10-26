<?php
require_once("../conexion.php");

class Direccion
{
    private $conexion;

    public function __construct()
    {
        global $conexion;
        $this->conexion = $conexion;
    }

    public function listarEstados()
    {
        $sql = "SELECT cod_est, nombre FROM ESTADO ORDER BY nombre ASC";
        $resultado = $this->conexion->query($sql);
        $datos = array();
        while ($fila = $resultado->fetch_assoc()) { $datos[] = $fila; }
        return $datos;
    }

    public function listarMunicipios($cod_est)
    {
        $cod_est = (int)$cod_est;
        $sql = "SELECT cod_muni, nombre FROM MUNICIPIO WHERE cod_est = $cod_est ORDER BY nombre ASC";
        $resultado = $this->conexion->query($sql);
        $datos = array();
        while ($fila = $resultado->fetch_assoc()) { $datos[] = $fila; }
        return $datos;
    }

    public function listarParroquias($cod_muni)
    {
        $cod_muni = (int)$cod_muni;
        $sql = "SELECT cod_par, nombre FROM PARROQUIA WHERE cod_muni = $cod_muni ORDER BY nombre ASC";
        $resultado = $this->conexion->query($sql);
        $datos = array();
        while ($fila = $resultado->fetch_assoc()) { $datos[] = $fila; }
        return $datos;
    }

    public function listar()
    {
        $sql = "SELECT
                    d.id_dir,
                    d.nombre AS direccion,
                    p.cod_par,
                    p.nombre AS parroquia,
                    m.cod_muni,
                    m.nombre AS municipio,
                    e.cod_est,
                    e.nombre AS estado
                FROM DIRECCION d
                INNER JOIN PARROQUIA p ON d.cod_par = p.cod_par
                INNER JOIN MUNICIPIO m ON p.cod_muni = m.cod_muni
                INNER JOIN ESTADO e ON m.cod_est = e.cod_est
                ORDER BY e.nombre, m.nombre, p.nombre";

        $resultado = $this->conexion->query($sql);
        $datos = array();
        while ($fila = $resultado->fetch_assoc()) { $datos[] = $fila; }
        return $datos;
    }

    public function buscar($id_dir)
    {
        $id_dir = (int)$id_dir;
        $sql = "SELECT * FROM DIRECCION WHERE id_dir = $id_dir";
        $resultado = $this->conexion->query($sql);
        return $resultado->fetch_assoc();
    }

    /*=========================================
      REGISTRAR — ya no recibe texto de dirección,
      solo la ubicación (parroquia). El texto lo
      completa después el módulo de Trabajador.
    =========================================*/
    public function registrar($cod_par)
    {
        $stmt = $this->conexion->prepare("INSERT INTO DIRECCION(cod_par, nombre) VALUES(?, NULL)");
        $stmt->bind_param("i", $cod_par);
        return $stmt->execute();
    }

    /*=========================================
      EDITAR — aquí solo se permite cambiar la
      ubicación (parroquia), no el texto.
    =========================================*/
    public function editar($id_dir, $cod_par)
    {
        $stmt = $this->conexion->prepare("UPDATE DIRECCION SET cod_par=? WHERE id_dir=?");
        $stmt->bind_param("ii", $cod_par, $id_dir);
        return $stmt->execute();
    }

    /*=========================================
      Usado desde el módulo de Trabajador para
      completar el texto de la dirección específica.
    =========================================*/
    public function actualizarNombre($id_dir, $nombre)
    {
        $stmt = $this->conexion->prepare("UPDATE DIRECCION SET nombre=? WHERE id_dir=?");
        $stmt->bind_param("si", $nombre, $id_dir);
        return $stmt->execute();
    }

    public function eliminar($id_dir)
    {
        $stmt = $this->conexion->prepare("DELETE FROM DIRECCION WHERE id_dir=?");
        $stmt->bind_param("i", $id_dir);

        try {
            $stmt->execute();
            return true;
        } catch (mysqli_sql_exception $e) {
            error_log("Eliminar DIRECCION id={$id_dir} excepcion: " . $e->getMessage());
            if ($e->getCode() == 1451) {
                return "RESTRICT";
            }
            return false;
        }
    }
}
?>