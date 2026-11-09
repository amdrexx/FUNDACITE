<?php

require_once("../conexion.php");

class Estado
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    // =========================
    // LISTAR
    // =========================
    public function listar()
    {
        $sql = "SELECT * FROM ESTADO ORDER BY nombre ASC";
        $resultado = $this->db->query($sql);

        return $resultado;
    }

    // =========================
    // BUSCAR
    // =========================
    public function buscar($id)
    {
        $sql = "SELECT * FROM ESTADO WHERE cod_est=?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================
    // VERIFICAR DUPLICADOS
    // =========================
    public function existe($nombre, $id = null)
    {

        if ($id == null) {

            $sql = "SELECT * FROM ESTADO WHERE nombre=?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $nombre);

        } else {

            $sql = "SELECT * FROM ESTADO
                    WHERE nombre=?
                    AND cod_est<>?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $nombre, $id);

        }

        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    // =========================
    // INSERTAR
    // =========================
    public function insertar($nombre)
    {
        $sql = "INSERT INTO ESTADO(nombre)
                VALUES(?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombre);

        return $stmt->execute();
    }

    // =========================
    // ACTUALIZAR
    // =========================
    public function actualizar($id, $nombre)
    {
        $sql = "UPDATE ESTADO
                SET nombre=?
                WHERE cod_est=?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $nombre, $id);

        return $stmt->execute();
    }

    // =========================
    // ELIMINAR
    // =========================
public function eliminar($id)
{
    $sql = "DELETE FROM ESTADO
            WHERE cod_est=?";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $id);

    try {

        $ok = $stmt->execute();

        // Si execute() devuelve false sin lanzar excepción
        // (por ejemplo si mysqli_report está desactivado)
        if ($ok === false) {
            error_log("Eliminar ESTADO id={$id} fallo: " . $stmt->error . " (errno " . $stmt->errno . ")");

            if ($stmt->errno == 1451) {
                return "RESTRICT";
            }
            return false;
        }

        // Si no lanzó error pero tampoco borró ninguna fila
        if ($stmt->affected_rows === 0) {
            error_log("Eliminar ESTADO id={$id}: no existe ninguna fila con ese cod_est");
            return "NOT_FOUND";
        }

        return true;

    } catch (mysqli_sql_exception $e) {

        error_log("Eliminar ESTADO id={$id} excepcion: " . $e->getMessage() . " (code " . $e->getCode() . ")");

        if ($e->getCode() == 1451) {
            return "RESTRICT";
        }

        return false;
    }
}
}