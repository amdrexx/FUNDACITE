<?php
// ARCHIVO: /FUNDACITE/modelos/clase_gremio.php
// Catálogo de gremios. Los registros se conservan y se inactivan para
// mantener las relaciones históricas con convenios y trabajadores.

class Gremio
{
    private mysqli $conexion;

    public function __construct(mysqli $conexion)
    {
        $this->conexion = $conexion;
    }

    public function existeNombre(string $nombre, int $idExcluir = 0): bool
    {
        $sql = "SELECT id_gremio FROM GREMIO
                WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))
                  AND id_gremio <> ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('si', $nombre, $idExcluir);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function registrar(string $nombre, string $estado = 'Activo')
    {
        $sql = "INSERT INTO GREMIO (nombre, estado) VALUES (?, ?)";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('ss', $nombre, $estado);
        $ok = $stmt->execute();
        $id = $ok ? $this->conexion->insert_id : false;
        $stmt->close();
        return $id;
    }

    public function actualizar(int $idGremio, string $nombre, string $estado): bool
    {
        $sql = "UPDATE GREMIO SET nombre = ?, estado = ? WHERE id_gremio = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('ssi', $nombre, $estado, $idGremio);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function actualizarEstado(int $idGremio, string $estado): bool
    {
        $stmt = $this->conexion->prepare("UPDATE GREMIO SET estado = ? WHERE id_gremio = ?");
        if (!$stmt) return false;

        $stmt->bind_param('si', $estado, $idGremio);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function obtenerPorId(int $idGremio): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_gremio, nombre, estado FROM GREMIO WHERE id_gremio = ? LIMIT 1"
        );
        if (!$stmt) return null;

        $stmt->bind_param('i', $idGremio);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $fila;
    }

    public function listar(string $buscar = ''): array
    {
        if ($buscar !== '') {
            $stmt = $this->conexion->prepare(
                "SELECT id_gremio, nombre, estado FROM GREMIO
                 WHERE nombre LIKE ? ORDER BY nombre ASC"
            );
            if (!$stmt) return [];
            $like = '%' . $buscar . '%';
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $filas = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $filas;
        }

        $resultado = $this->conexion->query(
            "SELECT id_gremio, nombre, estado FROM GREMIO ORDER BY nombre ASC"
        );
        return ($resultado && $resultado->num_rows > 0)
            ? $resultado->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    /** Lista gremios activos y, si se indica, conserva el gremio actual aunque esté inactivo. */
    public function listarDisponibles(int $idSeleccionado = 0): array
    {
        if ($idSeleccionado > 0) {
            $stmt = $this->conexion->prepare(
                "SELECT id_gremio, nombre, estado FROM GREMIO
                 WHERE estado = 'Activo' OR id_gremio = ? ORDER BY nombre ASC"
            );
            if (!$stmt) return [];
            $stmt->bind_param('i', $idSeleccionado);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $filas = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $filas;
        }

        $resultado = $this->conexion->query(
            "SELECT id_gremio, nombre, estado FROM GREMIO
             WHERE estado = 'Activo' ORDER BY nombre ASC"
        );
        return ($resultado && $resultado->num_rows > 0)
            ? $resultado->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    public function existeActivo(int $idGremio): bool
    {
        $stmt = $this->conexion->prepare(
            "SELECT 1 FROM GREMIO WHERE id_gremio = ? AND estado = 'Activo' LIMIT 1"
        );
        if (!$stmt) return false;

        $stmt->bind_param('i', $idGremio);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $existe;
    }
}
