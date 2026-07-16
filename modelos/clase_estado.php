<?php

require_once("../conexion.php");

class Estado {

    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function listar() {
        $sql = "SELECT * FROM ESTADO ORDER BY cod_est DESC";
        return $this->db->query($sql);
    }

    public function buscar($id) {
        $sql = "SELECT * FROM ESTADO WHERE cod_est = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function insertar($nombre) {
        $sql = "INSERT INTO ESTADO(nombre) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombre);
        return $stmt->execute();
    }

    public function actualizar($id, $nombre) {
        $sql = "UPDATE ESTADO SET nombre=? WHERE cod_est=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $nombre, $id);
        return $stmt->execute();
    }

    public function eliminar($id) {
        $sql = "DELETE FROM ESTADO WHERE cod_est=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}