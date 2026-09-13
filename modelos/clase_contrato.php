<?php
// =============================================================================
// ARCHIVO: /FUNDACITE/modelos/clase_contrato.php
// Modelo de acceso a datos para la tabla CONTRATO
// Compatible con ContratoControlador (ctrl_contrato.php)
// =============================================================================

class clase_contrato {

    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    private function obtenerCargoDeTrabajador(int $id_trabajador) {
        $sql = "SELECT id_cargo FROM TRABAJADOR WHERE id_trabajador = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_trabajador);
        $stmt->execute();
        $res = $stmt->get_result();
        $fila = $res->fetch_assoc();
        $stmt->close();

        if (!$fila || empty($fila['id_cargo'])) {
            return null;
        }
        return (int) $fila['id_cargo'];
    }

    public function registrarContrato(
        int $id_trabajador,
        string $tipo_contrato,
        string $fecha_contrato,
        ?string $fecha_fin,
        string $lugar_trabajo,
        string $nombre_presidente,
        string $cedula_presidente,
        string $gaceta
    ) {
        $id_cargo = $this->obtenerCargoDeTrabajador($id_trabajador);

        if ($id_cargo === null) {
            return false;
        }

        $sql = "INSERT INTO CONTRATO (
                    id_trabajador, id_cargo, tipo_contrato, fecha_contrato,
                    fecha_fin, lugar_trabajo, nombre_presidente,
                    cedula_presidente, gaceta_designacion_presidente
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "iisssssss",
            $id_trabajador,
            $id_cargo,
            $tipo_contrato,
            $fecha_contrato,
            $fecha_fin,
            $lugar_trabajo,
            $nombre_presidente,
            $cedula_presidente,
            $gaceta
        );

        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function actualizarContrato(
        int $id_contrato,
        int $id_trabajador,
        string $tipo_contrato,
        string $fecha_contrato,
        ?string $fecha_fin,
        string $lugar_trabajo,
        string $nombre_presidente,
        string $cedula_presidente,
        string $gaceta
    ) {
        $id_cargo = $this->obtenerCargoDeTrabajador($id_trabajador);

        if ($id_cargo === null) {
            $id_cargo = $this->obtenerCargoDeContrato($id_contrato);
        }

        if ($id_cargo === null) {
            return false;
        }

        $sql = "UPDATE CONTRATO SET
                    id_trabajador = ?,
                    id_cargo = ?,
                    tipo_contrato = ?,
                    fecha_contrato = ?,
                    fecha_fin = ?,
                    lugar_trabajo = ?,
                    nombre_presidente = ?,
                    cedula_presidente = ?,
                    gaceta_designacion_presidente = ?
                WHERE id_contrato = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "iisssssssi",
            $id_trabajador,
            $id_cargo,
            $tipo_contrato,
            $fecha_contrato,
            $fecha_fin,
            $lugar_trabajo,
            $nombre_presidente,
            $cedula_presidente,
            $gaceta,
            $id_contrato
        );

        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    private function obtenerCargoDeContrato(int $id_contrato) {
        $sql = "SELECT id_cargo FROM CONTRATO WHERE id_contrato = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_contrato);
        $stmt->execute();
        $res = $stmt->get_result();
        $fila = $res->fetch_assoc();
        $stmt->close();

        return $fila ? (int) $fila['id_cargo'] : null;
    }

    public function eliminarContrato(int $id_contrato) {
        $sql = "DELETE FROM CONTRATO WHERE id_contrato = ?";
        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_contrato);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function obtenerContratos() {
        $sql = "SELECT
                    ct.id_contrato,
                    ct.id_trabajador,
                    ct.id_cargo,
                    ct.tipo_contrato,
                    ct.fecha_contrato,
                    ct.fecha_fin,
                    ct.lugar_trabajo,
                    ct.nombre_presidente,
                    ct.cedula_presidente,
                    ct.gaceta_designacion_presidente,
                    t.cedula AS cedula_trabajador,
                    t.nombres AS nombres_trabajador,
                    t.apellidos AS apellidos_trabajador,
                    CONCAT(t.nombres, ' ', t.apellidos) AS nombre_trabajador,
                    c.nombre_cargo AS nombre_cargo
                FROM CONTRATO ct
                INNER JOIN TRABAJADOR t ON ct.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON ct.id_cargo = c.id_cargo
                ORDER BY ct.id_contrato DESC";

        $resultado = $this->conexion->query($sql);

        if (!$resultado) {
            return [];
        }

        $contratos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $contratos[] = $fila;
        }

        return $contratos;
    }

    public function obtenerPorId(int $id_contrato) {
        $sql = "SELECT
                    ct.id_contrato,
                    ct.id_trabajador,
                    ct.id_cargo,
                    ct.tipo_contrato,
                    ct.fecha_contrato,
                    ct.fecha_fin,
                    ct.lugar_trabajo,
                    ct.nombre_presidente,
                    ct.cedula_presidente,
                    ct.gaceta_designacion_presidente,
                    t.cedula AS cedula_trabajador,
                    t.nombres AS nombres_trabajador,
                    t.apellidos AS apellidos_trabajador,
                    CONCAT(t.nombres, ' ', t.apellidos) AS nombre_trabajador,
                    c.nombre_cargo AS nombre_cargo
                FROM CONTRATO ct
                INNER JOIN TRABAJADOR t ON ct.id_trabajador = t.id_trabajador
                LEFT JOIN CARGO c ON ct.id_cargo = c.id_cargo
                WHERE ct.id_contrato = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_contrato);
        $stmt->execute();

        $res = $stmt->get_result();
        $fila = $res->fetch_assoc();

        $stmt->close();

        return $fila ?: false;
    }

    public function obtenerPorTrabajador(int $id_trabajador) {
        $sql = "SELECT
                    ct.id_contrato,
                    ct.tipo_contrato,
                    ct.fecha_contrato,
                    ct.fecha_fin,
                    ct.lugar_trabajo,
                    ct.nombre_presidente,
                    ct.cedula_presidente,
                    ct.gaceta_designacion_presidente,
                    c.nombre_cargo
                FROM CONTRATO ct
                LEFT JOIN CARGO c ON ct.id_cargo = c.id_cargo
                WHERE ct.id_trabajador = ?
                ORDER BY ct.fecha_contrato DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $id_trabajador);
        $stmt->execute();

        $res = $stmt->get_result();
        $contratos = [];

        while ($fila = $res->fetch_assoc()) {
            $contratos[] = $fila;
        }

        $stmt->close();

        return $contratos;
    }
}
?>
