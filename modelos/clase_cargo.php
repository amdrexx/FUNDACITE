<?php
// ARCHIVO: /FUNDACITE/modelos/clase_cargo.php

class clase_cargo {
    private $db;

    // Recibimos la conexión desde el controlador
    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // VALIDAR SI EL CARGO YA EXISTE (Duplicados)
    // Se pasa opcionalmente $id_excluir para el proceso de actualización (UPDATE)
    public function existeCargo($nombre_cargo, $id_excluir = null) {
        $nombreLimpio = trim($nombre_cargo);

        if ($id_excluir) {
            // Para actualizar: busca si existe en OTRO registro que no sea el actual
            $sql = "SELECT id_cargo FROM CARGO WHERE LOWER(nombre_cargo) = LOWER(?) AND id_cargo != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $nombreLimpio, $id_excluir);
        } else {
            // Para registrar: busca si ya existe cualquier registro con ese nombre
            $sql = "SELECT id_cargo FROM CARGO WHERE LOWER(nombre_cargo) = LOWER(?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $nombreLimpio);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0; // Retorna true si ya existe
    }

    // REGISTRAR (Create)
    public function registrarCargo($nombre_cargo) {
        $nombreLimpio = trim($nombre_cargo);

        // Si ya existe, detenemos la inserción y devolvemos false (o manejamos el error)
        if ($this->existeCargo($nombreLimpio)) {
            return false;
        }

        $sql = "INSERT INTO CARGO (nombre_cargo) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombreLimpio);
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
        $nombreLimpio = trim($nombre_cargo);

        // Verifica si el nuevo nombre choca con OTRO cargo existente
        if ($this->existeCargo($nombreLimpio, $id_cargo)) {
            return false;
        }

        $sql = "UPDATE CARGO SET nombre_cargo = ? WHERE id_cargo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $nombreLimpio, $id_cargo);
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