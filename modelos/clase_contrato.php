<?php
// ======================================================
// ARCHIVO: modelos/clase_contrato.php
// DESCRIPCIÓN: Modelo para la gestión de contratos
// ======================================================

class clase_contrato
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // ======================================================
    // OBTENER TRABAJADORES ACTIVOS
    // ======================================================
    public function obtenerTrabajadoresActivos()
    {
        $sql = "SELECT
                    id_trabajador,
                    cedula,
                    nombres,
                    apellidos
                FROM TRABAJADOR
                WHERE status = 'Activo'
                ORDER BY apellidos ASC, nombres ASC";

        $resultado = $this->conexion->query($sql);

        if ($resultado) {
            return $resultado->fetch_all(MYSQLI_ASSOC);
        }

        return [];
    }

    // ======================================================
    // REGISTRAR CONTRATO
    // ======================================================
    public function registrarContrato(
        $id_trabajador,
        $notas_empresa,
        $tipo_contrato,
        $fecha_ingreso,
        $lugar_trabajo,
        $salario
    ) {

        $sql = "INSERT INTO CONTRATO
                (
                    id_trabajador,
                    notas_empresa,
                    tipo_contrato,
                    fecha_ingreso,
                    lugar_trabajo,
                    salario
                )
                VALUES
                (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "issssd",
            $id_trabajador,
            $notas_empresa,
            $tipo_contrato,
            $fecha_ingreso,
            $lugar_trabajo,
            $salario
        );

        return $stmt->execute();
    }

    // ======================================================
    // LISTAR CONTRATOS
    // ======================================================
    public function listarContratos()
    {
        $sql = "SELECT

                    c.id_contrato,
                    c.id_trabajador,

                    CONCAT(
                        t.nombres,
                        ' ',
                        t.apellidos
                    ) AS nombre_trabajador,

                    t.cedula,

                    c.notas_empresa,
                    c.tipo_contrato,
                    c.fecha_ingreso,
                    c.lugar_trabajo,
                    c.salario

                FROM CONTRATO c

                INNER JOIN TRABAJADOR t
                    ON c.id_trabajador = t.id_trabajador

                ORDER BY c.id_contrato DESC";

        $resultado = $this->conexion->query($sql);

        if ($resultado) {
            return $resultado->fetch_all(MYSQLI_ASSOC);
        }

        return [];
    }

    // ======================================================
    // OBTENER UN CONTRATO
    // ======================================================
    public function obtenerContrato($id_contrato)
    {
        $sql = "SELECT *

                FROM CONTRATO

                WHERE id_contrato = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_contrato);

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // ======================================================
    // ACTUALIZAR CONTRATO
    // ======================================================
    public function actualizarContrato(
        $id_contrato,
        $id_trabajador,
        $notas_empresa,
        $tipo_contrato,
        $fecha_ingreso,
        $lugar_trabajo,
        $salario
    ) {

        $sql = "UPDATE CONTRATO
                SET
                    id_trabajador = ?,
                    notas_empresa = ?,
                    tipo_contrato = ?,
                    fecha_ingreso = ?,
                    lugar_trabajo = ?,
                    salario = ?
                WHERE id_contrato = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "issssdi",
            $id_trabajador,
            $notas_empresa,
            $tipo_contrato,
            $fecha_ingreso,
            $lugar_trabajo,
            $salario,
            $id_contrato
        );

        return $stmt->execute();
    }

    // ======================================================
    // ELIMINAR CONTRATO
    // ======================================================
    public function eliminarContrato($id_contrato)
    {
        $sql = "DELETE FROM CONTRATO
                WHERE id_contrato = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_contrato);

        return $stmt->execute();
    }

    // ======================================================
    // VERIFICAR SI EL TRABAJADOR YA TIENE CONTRATO
    // ======================================================
    public function trabajadorTieneContrato($id_trabajador)
    {
        $sql = "SELECT id_contrato
                FROM CONTRATO
                WHERE id_trabajador = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_trabajador);

        $stmt->execute();

        $resultado = $stmt->get_result();

        return ($resultado->num_rows > 0);
    }
}
?>