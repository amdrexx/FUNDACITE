<?php
class Usuario
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTrabajadores()
    {
        $sql = "SELECT 
                    id_trabajador,
                    cedula,
                    nombres,
                    apellidos,
                    CONCAT(cedula, ' - ', nombres, ' ', apellidos) AS nombre_completo
                FROM TRABAJADOR
                ORDER BY apellidos, nombres";

        $resultado = $this->conexion->query($sql);

        $datos = [];
        if ($resultado) {
            while ($fila = $resultado->fetch_assoc()) {
                $datos[] = $fila;
            }
        }

        return $datos;
    }

    public function obtenerUsuarioPorId($id_usuario)
    {
        $sql = "SELECT id_usuario, id_trabajador, nombre, contrasena, tipo_usuario, status
                FROM USUARIO
                WHERE id_usuario = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc() ?: null;
    }

    public function existeNombre($nombre, $id_usuario_excluir = null)
    {
        if (!empty($id_usuario_excluir)) {
            $sql = "SELECT COUNT(*) AS total
                    FROM USUARIO
                    WHERE nombre = ? AND id_usuario <> ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("si", $nombre, $id_usuario_excluir);
        } else {
            $sql = "SELECT COUNT(*) AS total
                    FROM USUARIO
                    WHERE nombre = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("s", $nombre);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();

        return ((int)$fila['total'] > 0);
    }

    public function existeTrabajador($id_trabajador, $id_usuario_excluir = null)
    {
        if (!empty($id_usuario_excluir)) {
            $sql = "SELECT COUNT(*) AS total
                    FROM USUARIO
                    WHERE id_trabajador = ? AND id_usuario <> ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("ii", $id_trabajador, $id_usuario_excluir);
        } else {
            $sql = "SELECT COUNT(*) AS total
                    FROM USUARIO
                    WHERE id_trabajador = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $id_trabajador);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();

        return ((int)$fila['total'] > 0);
    }

    public function registrar($datos)
    {
        $sql = "INSERT INTO USUARIO (id_trabajador, nombre, contrasena, tipo_usuario, status)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        $hash = password_hash($datos['contrasena'], PASSWORD_BCRYPT);

        $stmt->bind_param(
            "issss",
            $datos['id_trabajador'],
            $datos['nombre'],
            $hash,
            $datos['tipo_usuario'],
            $datos['status']
        );

        return $stmt->execute();
    }

    public function actualizar($id_usuario, $datos)
    {
        if (!empty($datos['contrasena'])) {
            $sql = "UPDATE USUARIO
                    SET id_trabajador = ?,
                        nombre = ?,
                        contrasena = ?,
                        tipo_usuario = ?,
                        status = ?
                    WHERE id_usuario = ?";

            $stmt = $this->conexion->prepare($sql);
            $hash = password_hash($datos['contrasena'], PASSWORD_BCRYPT);

            $stmt->bind_param(
                "issssi",
                $datos['id_trabajador'],
                $datos['nombre'],
                $hash,
                $datos['tipo_usuario'],
                $datos['status'],
                $id_usuario
            );
        } else {
            $sql = "UPDATE USUARIO
                    SET id_trabajador = ?,
                        nombre = ?,
                        tipo_usuario = ?,
                        status = ?
                    WHERE id_usuario = ?";

            $stmt = $this->conexion->prepare($sql);

            $stmt->bind_param(
                "isssi",
                $datos['id_trabajador'],
                $datos['nombre'],
                $datos['tipo_usuario'],
                $datos['status'],
                $id_usuario
            );
        }

        return $stmt->execute();
    }

    public function eliminar($id_usuario)
    {
        $sql = "DELETE FROM USUARIO WHERE id_usuario = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        return $stmt->execute();
    }
}