<?php
// ARCHIVO: /FUNDACITE/modelos/clase_contrato.php

class clase_contrato {
    private $db;

    public function __construct($conexion = null) {
        if ($conexion !== null) {
            $this->db = $conexion;
        } else {
            global $conexion;
            $this->db = $conexion;
        }
    }

    // =========================================================================
    // REGISTRAR CONTRATO
    // =========================================================================
    public function registrarContrato($id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente) {
        $sql = "INSERT INTO CONTRATO (id_trabajador, tipo_contrato, fecha_contrato, lugar_trabajo, nombre_presidente, cedula_presidente, gaceta_designacion_presidente) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("issssss", $id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente);
        return $stmt->execute();
    }

    // =========================================================================
    // ACTUALIZAR CONTRATO (NUEVO)
    // =========================================================================
    public function actualizarContrato($id_contrato, $id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente) {
        $sql = "UPDATE CONTRATO 
                SET id_trabajador = ?, 
                    tipo_contrato = ?, 
                    fecha_contrato = ?, 
                    lugar_trabajo = ?, 
                    nombre_presidente = ?, 
                    cedula_presidente = ?, 
                    gaceta_designacion_presidente = ? 
                WHERE id_contrato = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("issssssi", $id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente, $id_contrato);
        return $stmt->execute();
    }

    // =========================================================================
    // BUSCAR CONTRATO POR ID (NUEVO)
    // =========================================================================
    public function buscarPorId($id_contrato) {
        $sql = "SELECT c.*, t.cedula, t.nombres, t.apellidos 
                FROM CONTRATO c
                INNER JOIN TRABAJADOR t ON c.id_trabajador = t.id_trabajador
                WHERE c.id_contrato = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id_contrato);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // LISTAR CONTRATOS (CON BUSCADOR OPCIONAL)
    // =========================================================================
    public function listarContratos($buscar = "") {
        if (!empty($buscar)) {
            $buscar = "%" . $buscar . "%";
            $sql = "SELECT c.id_contrato, c.tipo_contrato, c.fecha_contrato, c.lugar_trabajo,
                           t.cedula, CONCAT(t.nombres, ' ', t.apellidos) AS nombre_trabajador 
                    FROM CONTRATO c
                    INNER JOIN TRABAJADOR t ON c.id_trabajador = t.id_trabajador
                    WHERE t.cedula LIKE ? 
                       OR t.nombres LIKE ? 
                       OR t.apellidos LIKE ? 
                       OR c.tipo_contrato LIKE ?
                    ORDER BY c.id_contrato DESC";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) return [];

            $stmt->bind_param("ssss", $buscar, $buscar, $buscar, $buscar);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $sql = "SELECT c.id_contrato, c.tipo_contrato, c.fecha_contrato, c.lugar_trabajo,
                           t.cedula, CONCAT(t.nombres, ' ', t.apellidos) AS nombre_trabajador 
                    FROM CONTRATO c
                    INNER JOIN TRABAJADOR t ON c.id_trabajador = t.id_trabajador
                    ORDER BY c.id_contrato DESC";

            $resultado = $this->db->query($sql);
            return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
        }
    }

    // =========================================================================
    // ELIMINAR CONTRATO
    // =========================================================================
    public function eliminarContrato($id_contrato) {
        $sql = "DELETE FROM CONTRATO WHERE id_contrato = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("i", $id_contrato);
        return $stmt->execute();
    }
}
?>