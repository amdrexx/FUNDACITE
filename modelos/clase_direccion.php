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

    /*=========================================
      LISTAR ESTADOS
    =========================================*/
    public function listarEstados()
    {
        $sql = "SELECT cod_est, nombre
                FROM ESTADO
                ORDER BY nombre ASC";

        $resultado = $this->conexion->query($sql);

        $datos = array();

        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        return $datos;
    }

    /*=========================================
      LISTAR MUNICIPIOS POR ESTADO
    =========================================*/
    public function listarMunicipios($cod_est)
    {
        $cod_est = (int)$cod_est;

        $sql = "SELECT cod_muni, nombre
                FROM MUNICIPIO
                WHERE cod_est = $cod_est
                ORDER BY nombre ASC";

        $resultado = $this->conexion->query($sql);

        $datos = array();

        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        return $datos;
    }

    /*=========================================
      LISTAR PARROQUIAS POR MUNICIPIO
    =========================================*/
    public function listarParroquias($cod_muni)
    {
        $cod_muni = (int)$cod_muni;

        $sql = "SELECT cod_par, nombre
                FROM PARROQUIA
                WHERE cod_muni = $cod_muni
                ORDER BY nombre ASC";

        $resultado = $this->conexion->query($sql);

        $datos = array();

        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        return $datos;
    }

    /*=========================================
      LISTAR DIRECCIONES
    =========================================*/
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
                INNER JOIN PARROQUIA p
                    ON d.cod_par = p.cod_par
                INNER JOIN MUNICIPIO m
                    ON p.cod_muni = m.cod_muni
                INNER JOIN ESTADO e
                    ON m.cod_est = e.cod_est
                ORDER BY
                    e.nombre,
                    m.nombre,
                    p.nombre,
                    d.nombre";

        $resultado = $this->conexion->query($sql);

        $datos = array();

        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        return $datos;
    }

    /*=========================================
      BUSCAR DIRECCION
    =========================================*/
    public function buscar($id_dir)
    {
        $id_dir = (int)$id_dir;

        $sql = "SELECT *
                FROM DIRECCION
                WHERE id_dir = $id_dir";

        $resultado = $this->conexion->query($sql);

        return $resultado->fetch_assoc();
    }

    /*=========================================
      REGISTRAR
    =========================================*/
    public function registrar($cod_par, $nombre)
    {
        $stmt = $this->conexion->prepare("INSERT INTO DIRECCION(cod_par,nombre)
                                          VALUES(?,?)");

        $stmt->bind_param("is", $cod_par, $nombre);

        return $stmt->execute();
    }

    /*=========================================
      EDITAR
    =========================================*/
    public function editar($id_dir, $cod_par, $nombre)
    {
        $stmt = $this->conexion->prepare("UPDATE DIRECCION
                                          SET cod_par=?, nombre=?
                                          WHERE id_dir=?");

        $stmt->bind_param("isi", $cod_par, $nombre, $id_dir);

        return $stmt->execute();
    }

    /*=========================================
      ELIMINAR
    =========================================*/
    public function eliminar($id_dir)
    {
        $stmt = $this->conexion->prepare("DELETE FROM DIRECCION
                                          WHERE id_dir=?");

        $stmt->bind_param("i", $id_dir);

        return $stmt->execute();
    }
}
?>