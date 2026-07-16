<?php
require_once("../conexion.php");

class Parroquia
{
    private $conexion;

    public function __construct()
    {
        global $conexion;
        $this->conexion = $conexion;
    }

    /*=========================
      LISTAR ESTADOS
    =========================*/
    public function listarEstados()
    {
        $sql = "SELECT cod_est, nombre
                FROM ESTADO
                ORDER BY nombre ASC";

        return mysqli_query($this->conexion, $sql);
    }

    /*=========================
      LISTAR MUNICIPIOS POR ESTADO
    =========================*/
    public function listarMunicipios($cod_est)
    {
        $cod_est = mysqli_real_escape_string($this->conexion, $cod_est);

        $sql = "SELECT cod_muni, nombre
                FROM MUNICIPIO
                WHERE cod_est='$cod_est'
                ORDER BY nombre ASC";

        return mysqli_query($this->conexion, $sql);
    }

    /*=========================
      REGISTRAR PARROQUIAS
    =========================*/
    public function registrar($cod_muni, $parroquias)
    {
        $cod_muni = mysqli_real_escape_string($this->conexion, $cod_muni);

        foreach ($parroquias as $nombre)
        {
            $nombre = trim($nombre);

            if ($nombre == "")
            {
                continue;
            }

            $nombre = mysqli_real_escape_string($this->conexion, $nombre);

            $verificar = mysqli_query(
                $this->conexion,
                "SELECT cod_par
                 FROM PARROQUIA
                 WHERE nombre='$nombre'
                 AND cod_muni='$cod_muni'"
            );

            if(mysqli_num_rows($verificar) == 0)
            {
                mysqli_query(
                    $this->conexion,
                    "INSERT INTO PARROQUIA(nombre,cod_muni)
                     VALUES('$nombre','$cod_muni')"
                );
            }
        }

        return true;
    }

    /*=========================
      LISTAR PARROQUIAS
    =========================*/
    public function listar()
    {
        $sql = "SELECT
                    p.cod_par,
                    p.nombre AS parroquia,
                    m.cod_muni,
                    m.nombre AS municipio,
                    e.cod_est,
                    e.nombre AS estado
                FROM PARROQUIA p
                INNER JOIN MUNICIPIO m
                    ON p.cod_muni = m.cod_muni
                INNER JOIN ESTADO e
                    ON m.cod_est = e.cod_est
                ORDER BY
                    e.nombre,
                    m.nombre,
                    p.nombre";

        return mysqli_query($this->conexion, $sql);
    }

    /*=========================
      BUSCAR POR ID
    =========================*/
    public function buscar($cod_par)
    {
        $cod_par = mysqli_real_escape_string($this->conexion, $cod_par);

        $sql = "SELECT
                    p.cod_par,
                    p.nombre,
                    p.cod_muni,
                    m.nombre AS municipio,
                    m.cod_est,
                    e.nombre AS estado
                FROM PARROQUIA p
                INNER JOIN MUNICIPIO m
                    ON p.cod_muni = m.cod_muni
                INNER JOIN ESTADO e
                    ON m.cod_est = e.cod_est
                WHERE p.cod_par = '$cod_par'
                LIMIT 1";

        return mysqli_query($this->conexion, $sql);
    }

    /*=========================
      EDITAR
    =========================*/
    public function editar($cod_par,$cod_muni,$nombre)
    {
        $cod_par = mysqli_real_escape_string($this->conexion,$cod_par);
        $cod_muni = mysqli_real_escape_string($this->conexion,$cod_muni);
        $nombre = mysqli_real_escape_string($this->conexion,$nombre);

        $sql = "UPDATE PARROQUIA
                SET
                    nombre='$nombre',
                    cod_muni='$cod_muni'
                WHERE cod_par='$cod_par'";

        return mysqli_query($this->conexion,$sql);
    }

    /*=========================
      ELIMINAR
    =========================*/
    public function eliminar($cod_par)
    {
        $cod_par = mysqli_real_escape_string($this->conexion,$cod_par);

        $sql = "DELETE FROM PARROQUIA
                WHERE cod_par='$cod_par'";

        return mysqli_query($this->conexion,$sql);
    }
}
?>