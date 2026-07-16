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

    // REGISTRAR CONTRATO
    public function registrarContrato($id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente) {
        $sql = "INSERT INTO CONTRATO (id_trabajador, tipo_contrato, fecha_contrato, lugar_trabajo, nombre_presidente, cedula_presidente, gaceta_designacion_presidente) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("issssss", $id_trabajador, $tipo_contrato, $fecha_contrato, $lugar_trabajo, $nombre_presidente, $cedula_presidente, $gaceta_designacion_presidente);
        return $stmt->execute();
    }

    // LISTAR CONTRATOS (CORREGIDO CON LAS COLUMNAS 'nombres' Y 'apellidos')
    public function listarContratos() {
        $sql = "SELECT c.id_contrato, c.tipo_contrato, c.fecha_contrato, 
                       CONCAT(t.nombres, ' ', t.apellidos) AS nombre_trabajador 
                FROM CONTRATO c
                INNER JOIN TRABAJADOR t ON c.id_trabajador = t.id_trabajador
                ORDER BY c.id_contrato DESC";
        $resultado = $this->db->query($sql);
        return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ELIMINAR CONTRATO
    public function eliminarContrato($id_contrato) {
        $sql = "DELETE FROM CONTRATO WHERE id_contrato = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_contrato);
        return $stmt->execute();
    }
}
?>