<?php

class Solicitud
{

    private $conexion;


    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }



    // =========================
    // REGISTRAR SOLICITUD
    // =========================

    public function registrar(
        $id_trabajador,
        $codigo_solicitud,
        $tipo_solicitud,
        $motivo_solicitud,
        $fecha_inicio,
        $fecha_finalizacion
    )
    {

        $sql = "INSERT INTO SOLICITUD
        (
            id_trabajador,
            codigo_solicitud,
            tipo_solicitud,
            motivo_solicitud,
            fecha_inicio,
            fecha_finalizacion
        )

        VALUES (?,?,?,?,?,?)";


        $stmt = $this->conexion->prepare($sql);


        if(!$stmt){
            return false;
        }


        $stmt->bind_param(
            "isssss",
            $id_trabajador,
            $codigo_solicitud,
            $tipo_solicitud,
            $motivo_solicitud,
            $fecha_inicio,
            $fecha_finalizacion
        );


        $ok = $stmt->execute();

        // Para poder devolver el id_solicitud recién creado al controlador
        return $ok ? $this->conexion->insert_id : false;

    }




    // =========================
    // CONSULTAR SOLICITUD POR ID
    // =========================

    public function obtenerPorId($id)
    {

        $sql = "SELECT

                s.*,

                t.nombres,
                t.apellidos,
                t.cedula

                FROM SOLICITUD s

                INNER JOIN TRABAJADOR t

                ON s.id_trabajador = t.id_trabajador

                WHERE s.id_solicitud = ?";


        $stmt = $this->conexion->prepare($sql);


        if(!$stmt){
            return null;
        }


        $stmt->bind_param(
            "i",
            $id
        );


        $stmt->execute();


        return $stmt->get_result()->fetch_assoc();

    }




    // =========================
    // CONSULTAR TRABAJADOR + SOLICITUDES PREVIAS POR CÉDULA
    // (usado por el botón "Consultar" del formulario)
    // =========================

    public function obtenerTrabajadorPorCedula($cedula)
{
    $sql = "SELECT
            t.id_trabajador,
            t.cedula,
            t.nombres,
            t.apellidos,
            c.nombre_cargo AS cargo
            FROM TRABAJADOR t
            LEFT JOIN CARGO c ON t.id_cargo = c.id_cargo
            WHERE t.cedula = ?
            LIMIT 1";

    $stmt = $this->conexion->prepare($sql);

    if(!$stmt){
        return null;
    }

    $stmt->bind_param("s", $cedula);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}



    // =========================
    // LISTAR SOLICITUDES
    // =========================

    public function mostrarSolicitudes()
    {


        $sql = "SELECT

                s.id_solicitud,

                s.codigo_solicitud,

                s.tipo_solicitud,

                s.motivo_solicitud,

                s.fecha_inicio,

                s.fecha_finalizacion,


                CONCAT(
                    t.apellidos,' ',
                    t.nombres
                ) AS trabajador


                FROM SOLICITUD s


                INNER JOIN TRABAJADOR t


                ON s.id_trabajador = t.id_trabajador

                ORDER BY s.id_solicitud DESC";



        return $this->conexion->query($sql);

    }





    // =========================
    // ELIMINAR SOLICITUD
    // =========================

    public function eliminar($id)
    {

        $sql = "DELETE FROM SOLICITUD
                WHERE id_solicitud=?";


        $stmt = $this->conexion->prepare($sql);


        if(!$stmt){
            return false;
        }


        $stmt->bind_param(
            "i",
            $id
        );


        return $stmt->execute();

    }


}