<?php
// ARCHIVO: /FUNDACITE/modelos/clase_cargo.php

class clase_cargo {
    private $db;

    // Recibimos la conexión desde el controlador
    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // REGISTRAR (Create)
    public function registrarCargo($nombre_cargo) {
        $sql = "INSERT INTO CARGO (nombre_cargo) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombre_cargo);
        return $stmt->execute();
    }

    // OBTENER TODOS LOS CARGOS (Read)
    public function listarCargos() {
        $sql = "SELECT id_cargo, nombre_cargo FROM CARGO";
        $resultado = $this->db->query($sql);
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // OBTENER UN CARGO POR ID (Para editar)
    public function obtenerCargoPorId($id_cargo) {
        $sql = "SELECT id_cargo, nombre_cargo FROM CARGO WHERE id_cargo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_cargo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    // ACTUALIZAR (Update)
    public function actualizarCargo($id_cargo, $nombre_cargo) {
        $sql = "UPDATE CARGO SET nombre_cargo = ? WHERE id_cargo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $nombre_cargo, $id_cargo);
        return $stmt->execute();
    }

    // ELIMINAR (Delete)
    public function eliminarCargo($id_cargo) {
        $sql = "DELETE FROM CARGO WHERE id_cargo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_cargo);
        return $stmt->execute();
    }
}
?>