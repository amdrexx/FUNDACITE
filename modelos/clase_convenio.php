<?php
// ARCHIVO: /FUNDACITE/modelos/clase_convenio.php
// Acceso a datos del módulo Convenio relacionado con el catálogo GREMIO.

class Convenio
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public static function estadoVigencia($fechaInicio, $fechaVencimiento, $estado = 'Vigente'): string
    {
        // El estado administrativo prevalece sobre las fechas: un convenio
        // sustituido por otro del mismo gremio permanece como No vigente.
        if (($estado ?? 'Vigente') === 'No vigente') {
            return 'No vigente';
        }

        $hoy = date('Y-m-d');
        if ($fechaInicio > $hoy) return 'Por iniciar';
        if ($fechaVencimiento !== null && $fechaVencimiento !== '' && $fechaVencimiento < $hoy) return 'Vencido';
        return 'Vigente';
    }

    /** En edición permite mantener el gremio actual aunque haya sido inactivado. */
    public function gremioValidoParaConvenio(int $idGremio, int $idConvenioActual = 0): bool
    {
        if ($idGremio <= 0) return false;
        $sql = "SELECT 1 FROM GREMIO g
                WHERE g.id_gremio = ?
                  AND (g.estado = 'Activo' OR EXISTS (
                      SELECT 1 FROM CONVENIO c
                      WHERE c.id_convenio = ? AND c.id_gremio = g.id_gremio
                  )) LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('ii', $idGremio, $idConvenioActual);
        $stmt->execute();
        $valido = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $valido;
    }

    /**
     * Registra un convenio como Vigente y desactiva, en la misma transacción,
     * cualquier convenio anterior que estuviera Vigente para ese mismo gremio.
     * Los convenios de otros gremios no se modifican.
     */
    public function registrarConvenio($idGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute)
    {
        $idGremio = (int) $idGremio;
        $diasDisfrute = (int) $diasDisfrute;
        $fechaVencimiento = ($fechaVencimiento === '' || $fechaVencimiento === null) ? null : $fechaVencimiento;

        try {
            $this->conexion->begin_transaction();

            // Bloquea la fila del gremio para serializar nuevos convenios del mismo gremio.
            $bloqueo = $this->conexion->prepare("SELECT id_gremio FROM GREMIO WHERE id_gremio = ? FOR UPDATE");
            if (!$bloqueo) throw new RuntimeException($this->conexion->error);
            $bloqueo->bind_param('i', $idGremio);
            if (!$bloqueo->execute()) throw new RuntimeException($bloqueo->error);
            $gremioExiste = $bloqueo->get_result()->fetch_assoc();
            $bloqueo->close();
            if (!$gremioExiste) throw new RuntimeException('El gremio seleccionado ya no existe.');

            $desactivar = $this->conexion->prepare(
                "UPDATE CONVENIO SET estado = 'No vigente' WHERE id_gremio = ? AND estado = 'Vigente'"
            );
            if (!$desactivar) throw new RuntimeException($this->conexion->error);
            $desactivar->bind_param('i', $idGremio);
            if (!$desactivar->execute()) throw new RuntimeException($desactivar->error);
            $desactivar->close();

            $sql = "INSERT INTO CONVENIO (id_gremio, fecha_inicio, fecha_vencimiento, dias_disfrute, estado)
                    VALUES (?, ?, ?, ?, 'Vigente')";
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) throw new RuntimeException($this->conexion->error);
            $stmt->bind_param('issi', $idGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute);
            if (!$stmt->execute()) throw new RuntimeException($stmt->error);
            $id = (int) $this->conexion->insert_id;
            $stmt->close();

            $this->conexion->commit();
            return $id;
        } catch (Throwable $e) {
            $this->conexion->rollback();
            error_log('Error al registrar convenio: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerConvenioVigente()
    {
        $sql = "SELECT c.id_convenio, c.id_gremio, g.nombre AS nombre_gremio,
                       c.fecha_inicio, c.fecha_vencimiento, c.dias_disfrute, c.estado
                FROM CONVENIO c
                INNER JOIN GREMIO g ON g.id_gremio = c.id_gremio
                WHERE c.estado = 'Vigente'
                  AND c.fecha_inicio <= CURDATE()
                  AND (c.fecha_vencimiento IS NULL OR c.fecha_vencimiento >= CURDATE())
                ORDER BY c.fecha_inicio DESC, c.id_convenio DESC
                LIMIT 1";
        $resultado = $this->conexion->query($sql);
        return ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_assoc() : null;
    }

    public function listarConvenios($buscar = ''): array
    {
        $sql = "SELECT c.id_convenio, c.id_gremio, g.nombre AS nombre_gremio,
                       c.fecha_inicio, c.fecha_vencimiento, c.dias_disfrute, c.estado
                FROM CONVENIO c
                INNER JOIN GREMIO g ON g.id_gremio = c.id_gremio";

        if ($buscar !== '') {
            $stmt = $this->conexion->prepare($sql . " WHERE g.nombre LIKE ? ORDER BY c.id_convenio DESC");
            if (!$stmt) return [];
            $like = '%' . $buscar . '%';
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $filas = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $filas;
        }

        $resultado = $this->conexion->query($sql . " ORDER BY c.id_convenio DESC");
        return ($resultado && $resultado->num_rows > 0)
            ? $resultado->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    public function obtenerConvenioPorId($idConvenio)
    {
        $sql = "SELECT c.id_convenio, c.id_gremio, g.nombre AS nombre_gremio,
                       c.fecha_inicio, c.fecha_vencimiento, c.dias_disfrute, c.estado
                FROM CONVENIO c
                INNER JOIN GREMIO g ON g.id_gremio = c.id_gremio
                WHERE c.id_convenio = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('i', $idConvenio);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $fila;
    }

    public function actualizarConvenio($idConvenio, $idGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute): bool
    {
        $idConvenio = (int) $idConvenio;
        $idGremio = (int) $idGremio;
        $diasDisfrute = (int) $diasDisfrute;
        $fechaVencimiento = ($fechaVencimiento === '' || $fechaVencimiento === null) ? null : $fechaVencimiento;

        try {
            $this->conexion->begin_transaction();

            $actual = $this->conexion->prepare("SELECT id_gremio, estado FROM CONVENIO WHERE id_convenio = ? FOR UPDATE");
            if (!$actual) throw new RuntimeException($this->conexion->error);
            $actual->bind_param('i', $idConvenio);
            if (!$actual->execute()) throw new RuntimeException($actual->error);
            $registroActual = $actual->get_result()->fetch_assoc();
            $actual->close();
            if (!$registroActual) throw new RuntimeException('El convenio que intenta editar ya no existe.');

            // Si se cambia el gremio de un convenio Vigente, se mantiene la
            // regla de un solo convenio Vigente para el gremio de destino.
            if (($registroActual['estado'] ?? '') === 'Vigente') {
                $desactivar = $this->conexion->prepare(
                    "UPDATE CONVENIO SET estado = 'No vigente'
                     WHERE id_gremio = ? AND id_convenio <> ? AND estado = 'Vigente'"
                );
                if (!$desactivar) throw new RuntimeException($this->conexion->error);
                $desactivar->bind_param('ii', $idGremio, $idConvenio);
                if (!$desactivar->execute()) throw new RuntimeException($desactivar->error);
                $desactivar->close();
            }

            $sql = "UPDATE CONVENIO
                    SET id_gremio = ?, fecha_inicio = ?, fecha_vencimiento = ?, dias_disfrute = ?
                    WHERE id_convenio = ?";
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) throw new RuntimeException($this->conexion->error);
            $stmt->bind_param('issii', $idGremio, $fechaInicio, $fechaVencimiento, $diasDisfrute, $idConvenio);
            if (!$stmt->execute()) throw new RuntimeException($stmt->error);
            $stmt->close();

            $this->conexion->commit();
            return true;
        } catch (Throwable $e) {
            $this->conexion->rollback();
            error_log('Error al actualizar convenio: ' . $e->getMessage());
            return false;
        }
    }

    public function tieneSolicitudes($idConvenio): bool
    {
        $idConvenio = (int) $idConvenio;
        $stmt = $this->conexion->prepare("SELECT 1 FROM DISFRUTE_DE_VACACIONES WHERE id_convenio = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('i', $idConvenio);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function eliminarConvenio($idConvenio): bool
    {
        $stmt = $this->conexion->prepare("DELETE FROM CONVENIO WHERE id_convenio = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $idConvenio);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
