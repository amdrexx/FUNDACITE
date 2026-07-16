<?php
// ============================================================================
// ARCHIVO: modelos/clase_usuario.php
// DESCRIPCIÓN: Modelo para la gestión de usuarios
// ============================================================================

class Usuario
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // =========================================================================
    // REGISTRAR USUARIO
    // =========================================================================
    public function registrar($id_trabajador, $nombre, $contrasena, $tipo_usuario)
    {
        $sql = "INSERT INTO USUARIO
                (id_trabajador, nombre, contrasena, tipo_usuario, status)
                VALUES (?, ?, ?, ?, 'Activo')";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmt->bind_param(
            "isss",
            $id_trabajador,
            $nombre,
            $passwordHash,
            $tipo_usuario
        );

        return $stmt->execute();
    }

    // =========================================================================
    // ACTUALIZAR USUARIO (SIN CAMBIAR CONTRASEÑA)
    // =========================================================================
    public function actualizar($id_usuario, $id_trabajador, $nombre, $tipo_usuario)
    {
        $sql = "UPDATE USUARIO
                SET
                    id_trabajador=?,
                    nombre=?,
                    tipo_usuario=?
                WHERE id_usuario=?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "issi",
            $id_trabajador,
            $nombre,
            $tipo_usuario,
            $id_usuario
        );

        return $stmt->execute();
    }

    // =========================================================================
    // ACTUALIZAR CONTRASEÑA
    // =========================================================================
    public function actualizarPassword($id_usuario, $password)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE USUARIO
                SET contrasena=?
                WHERE id_usuario=?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "si",
            $passwordHash,
            $id_usuario
        );

        return $stmt->execute();
    }

    // =========================================================================
    // ELIMINACIÓN LÓGICA
    // =========================================================================
    public function eliminar($id_usuario)
    {
        $sql = "UPDATE USUARIO
                SET status='Inactivo'
                WHERE id_usuario=?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_usuario);

        return $stmt->execute();
    }

    // =========================================================================
    // ACTIVAR USUARIO
    // =========================================================================
    public function activar($id_usuario)
    {
        $sql = "UPDATE USUARIO
                SET status='Activo'
                WHERE id_usuario=?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_usuario);

        return $stmt->execute();
    }

    // =========================================================================
    // BUSCAR POR ID
    // =========================================================================
    public function buscarPorId($id_usuario)
    {
        $sql = "SELECT *
                FROM USUARIO
                WHERE id_usuario=?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // VERIFICAR SI EXISTE EL NOMBRE DE USUARIO
    // =========================================================================
    public function existeNombre($nombre, $id_usuario = null)
    {
        if ($id_usuario == null) {

            $sql = "SELECT id_usuario
                    FROM USUARIO
                    WHERE nombre=?";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("s", $nombre);

        } else {

            $sql = "SELECT id_usuario
                    FROM USUARIO
                    WHERE nombre=?
                    AND id_usuario<>?";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("si", $nombre, $id_usuario);
        }

        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    // =========================================================================
    // VERIFICAR SI EL TRABAJADOR YA TIENE USUARIO
    // =========================================================================
    public function trabajadorTieneUsuario($id_trabajador, $id_usuario = null)
    {
        if ($id_usuario == null) {

            $sql = "SELECT id_usuario
                    FROM USUARIO
                    WHERE id_trabajador=?";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $id_trabajador);

        } else {

            $sql = "SELECT id_usuario
                    FROM USUARIO
                    WHERE id_trabajador=?
                    AND id_usuario<>?";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("ii", $id_trabajador, $id_usuario);
        }

        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    // =========================================================================
    // LISTAR TODOS LOS USUARIOS
    // =========================================================================
    public function listar($buscar = "")
    {
        $buscar = "%".$buscar."%";

        $sql = "SELECT
                    u.id_usuario,
                    u.nombre,
                    u.tipo_usuario,
                    u.status,
                    u.id_trabajador,
                    t.cedula,
                    t.nombres,
                    t.apellidos
                FROM USUARIO u
                INNER JOIN TRABAJADOR t
                    ON u.id_trabajador=t.id_trabajador
                WHERE
                    u.nombre LIKE ?
                    OR t.cedula LIKE ?
                    OR t.nombres LIKE ?
                    OR t.apellidos LIKE ?
                ORDER BY u.id_usuario DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            "ssss",
            $buscar,
            $buscar,
            $buscar,
            $buscar
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // OBTENER SOLO USUARIOS ACTIVOS
    // =========================================================================
    public function listarActivos()
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombre,
                    u.tipo_usuario,
                    u.status,
                    t.cedula,
                    t.nombres,
                    t.apellidos
                FROM USUARIO u
                INNER JOIN TRABAJADOR t
                    ON u.id_trabajador=t.id_trabajador
                WHERE u.status='Activo'
                ORDER BY t.nombres ASC";

        $resultado = $this->conexion->query($sql);

        if (!$resultado) {
            return [];
        }

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // LOGIN
    // =========================================================================
    public function login($nombre, $password)
    {
        $sql = "SELECT *
                FROM USUARIO
                WHERE nombre=?
                AND status='Activo'
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $nombre);

        $stmt->execute();

        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario) {
            return false;
        }

        if (!password_verify($password, $usuario['contrasena'])) {
            return false;
        }

        return $usuario;
    }

    // =========================================================================
    // CONTAR USUARIOS
    // =========================================================================
    public function totalUsuarios()
    {
        $sql = "SELECT COUNT(*) AS total FROM USUARIO";

        $resultado = $this->conexion->query($sql);

        if (!$resultado) {
            return 0;
        }

        $fila = $resultado->fetch_assoc();

        return $fila['total'];
    }

    // =========================================================================
    // OBTENER USUARIO POR NOMBRE
    // =========================================================================
    public function obtenerPorNombre($nombre)
    {
        $sql = "SELECT *
                FROM USUARIO
                WHERE nombre=?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $nombre);

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
    
}

?>