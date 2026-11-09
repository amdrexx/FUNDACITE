<?php
// =============================================================================
// MODELO: NuevaPrima  (catálogo de primas nuevas, guardado en la tabla PRIMA)
// -----------------------------------------------------------------------------
// Usa la misma tabla PRIMA que ya existe:
//   - Prima asignada a un trabajador  -> id_trabajador con valor (como siempre).
//   - Prima nueva del catálogo        -> id_trabajador = NULL.
//     nombre -> tipo_prima | porcentaje -> porcentaje | fecha -> fecha | estatus -> estatus
//
// Para que esto funcione la tabla necesita (la clase lo hace sola; el SQL
// equivalente está en nueva_prima.sql):
//   1) id_trabajador debe aceptar NULL.
//   2) Columna clave_catalogo + índice UNIQUE: impide registrar la misma prima
//      dos veces el mismo día (nombre + fecha), incluso con doble clic o dos
//      usuarios guardando a la vez. Las primas asignadas a trabajadores dejan
//      esta columna en NULL, así que no se ven afectadas.
// Las pantallas y consultas existentes de primas ya filtran por
// id_trabajador IS NOT NULL, por eso no mezclan estas filas.
// =============================================================================

class NuevaPrima
{
    private mysqli $conexion;
    private bool $tieneClave = false;

    /** Último error de base de datos (para mostrarlo en pantalla). */
    public ?string $ultimoError = null;

    /** Se pone en true cuando el último registro/actualización falló por duplicado. */
    public bool $esDuplicado = false;

    public function __construct(mysqli $conexion)
    {
        $this->conexion = $conexion;
        $this->asegurarTabla();
    }

    /* =====================================================
       TABLA PRIMA: lo mínimo necesario para el catálogo
    ===================================================== */

    private function asegurarTabla(): void
    {
        try {
            $res = $this->conexion->query('SHOW COLUMNS FROM PRIMA');

            if (!$res) {
                $this->ultimoError = 'No se pudo leer la tabla PRIMA: ' . $this->conexion->error;
                return;
            }

            $cols = [];
            while ($f = $res->fetch_assoc()) {
                $cols[strtolower((string) $f['Field'])] = strtoupper((string) $f['Null']);
            }

            // 1) id_trabajador debe aceptar NULL (las primas del catálogo no pertenecen a un trabajador)
            if (($cols['id_trabajador'] ?? 'YES') === 'NO') {
                $this->conexion->query('ALTER TABLE PRIMA MODIFY id_trabajador INT UNSIGNED NULL');
            }

            // 2) Columna clave_catalogo
            if (!isset($cols['clave_catalogo'])) {
                $this->conexion->query('ALTER TABLE PRIMA ADD COLUMN clave_catalogo VARCHAR(120) NULL');
            }

            // 3) Índice único sobre clave_catalogo (varias filas NULL están permitidas)
            $idx = $this->conexion->query("SHOW INDEX FROM PRIMA WHERE Key_name = 'uq_prima_catalogo'");
            if ($idx && $idx->num_rows === 0) {
                $this->conexion->query('ALTER TABLE PRIMA ADD UNIQUE KEY uq_prima_catalogo (clave_catalogo)');
            }

            $chk = $this->conexion->query("SHOW COLUMNS FROM PRIMA LIKE 'clave_catalogo'");
            $this->tieneClave = (bool) ($chk && $chk->num_rows > 0);
        } catch (\Throwable $e) {
            $this->ultimoError = 'No se pudo preparar la tabla PRIMA para nuevas primas: ' . $e->getMessage();
        }
    }

    /** Clave única de una prima del catálogo: nombre (sin distinguir mayúsculas) + fecha. */
    private function clave(string $nombre, string $fecha): string
    {
        return mb_strtolower($nombre, 'UTF-8') . '|' . $fecha;
    }

    /* =====================================================
       CONSULTAS (solo filas del catálogo: id_trabajador IS NULL)
    ===================================================== */

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT id_prima AS id_nueva_prima, tipo_prima AS nombre, fecha, porcentaje, estatus
                FROM PRIMA
                WHERE id_trabajador IS NULL";

        try {
            if ($buscar !== '') {
                $sql .= " AND (tipo_prima LIKE ? OR estatus LIKE ? OR DATE_FORMAT(fecha, '%d/%m/%Y') LIKE ?
                              OR CAST(porcentaje AS CHAR) LIKE ?)";
            }
            $sql .= " ORDER BY fecha DESC, id_prima DESC";

            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) {
                $this->ultimoError = $this->conexion->error;
                return [];
            }

            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $stmt->bind_param('ssss', $like, $like, $like, $like);
            }

            $stmt->execute();
            $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return $filas;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return [];
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->conexion->prepare(
                'SELECT id_prima AS id_nueva_prima, tipo_prima AS nombre, fecha, porcentaje, estatus
                 FROM PRIMA WHERE id_prima = ? AND id_trabajador IS NULL'
            );
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $fila ?: null;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return null;
        }
    }

    /**
     * ¿Ya existe una prima nueva con ese nombre en esa fecha?
     * No distingue mayúsculas/minúsculas ni espacios sobrantes.
     * $excluirId sirve al editar para no compararse contra el propio registro.
     */
    public function existeDuplicado(string $nombre, string $fecha, int $excluirId = 0): bool
    {
        try {
            $stmt = $this->conexion->prepare(
                'SELECT COUNT(*) FROM PRIMA
                 WHERE id_trabajador IS NULL AND LOWER(TRIM(tipo_prima)) = LOWER(?)
                   AND fecha = ? AND id_prima <> ?'
            );
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('ssi', $nombre, $fecha, $excluirId);
            $stmt->execute();
            $stmt->bind_result($total);
            $stmt->fetch();
            $stmt->close();

            return $total > 0;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /* =====================================================
       ESCRITURA
    ===================================================== */

    /** @return int id insertado, o 0 si falló (ver $esDuplicado y $ultimoError). */
    public function registrar(string $nombre, string $fecha, float $porcentaje): int
    {
        $this->esDuplicado = false;

        try {
            if ($this->tieneClave) {
                $clave = $this->clave($nombre, $fecha);
                $stmt = $this->conexion->prepare(
                    "INSERT INTO PRIMA (id_trabajador, tipo_prima, condicion, porcentaje, monto, monto_total, fecha, estatus, clave_catalogo)
                     VALUES (NULL, ?, 'Prima nueva', ?, 0, 0, ?, 'Activo', ?)"
                );
                if (!$stmt) {
                    $this->ultimoError = $this->conexion->error;
                    return 0;
                }
                $stmt->bind_param('sdss', $nombre, $porcentaje, $fecha, $clave);
            } else {
                $stmt = $this->conexion->prepare(
                    "INSERT INTO PRIMA (id_trabajador, tipo_prima, condicion, porcentaje, monto, monto_total, fecha, estatus)
                     VALUES (NULL, ?, 'Prima nueva', ?, 0, 0, ?, 'Activo')"
                );
                if (!$stmt) {
                    $this->ultimoError = $this->conexion->error;
                    return 0;
                }
                $stmt->bind_param('sds', $nombre, $porcentaje, $fecha);
            }

            $stmt->execute();
            $id = (int) $this->conexion->insert_id;
            $stmt->close();

            return $id;
        } catch (\Throwable $e) {
            $this->marcarError($e);
            return 0;
        }
    }

    public function actualizar(int $id, string $nombre, string $fecha, float $porcentaje): bool
    {
        $this->esDuplicado = false;

        try {
            if ($this->tieneClave) {
                $clave = $this->clave($nombre, $fecha);
                $stmt = $this->conexion->prepare(
                    'UPDATE PRIMA SET tipo_prima = ?, fecha = ?, porcentaje = ?, clave_catalogo = ?
                     WHERE id_prima = ? AND id_trabajador IS NULL'
                );
                if (!$stmt) {
                    $this->ultimoError = $this->conexion->error;
                    return false;
                }
                $stmt->bind_param('ssdsi', $nombre, $fecha, $porcentaje, $clave, $id);
            } else {
                $stmt = $this->conexion->prepare(
                    'UPDATE PRIMA SET tipo_prima = ?, fecha = ?, porcentaje = ?
                     WHERE id_prima = ? AND id_trabajador IS NULL'
                );
                if (!$stmt) {
                    $this->ultimoError = $this->conexion->error;
                    return false;
                }
                $stmt->bind_param('ssdi', $nombre, $fecha, $porcentaje, $id);
            }

            $stmt->execute();
            $stmt->close();

            return true;
        } catch (\Throwable $e) {
            $this->marcarError($e);
            return false;
        }
    }

    /**
     * Actualiza el porcentaje de varias primas del catálogo en una sola transacción:
     * se guardan todos los cambios o ninguno.
     *
     * @param array<int,float> $cambios  [id_prima => nuevo porcentaje]
     */
    public function actualizarPorcentajes(array $cambios): bool
    {
        if (!$cambios) {
            return false;
        }

        try {
            $this->conexion->begin_transaction();

            $stmt = $this->conexion->prepare(
                'UPDATE PRIMA SET porcentaje = ? WHERE id_prima = ? AND id_trabajador IS NULL'
            );

            if (!$stmt) {
                $this->ultimoError = $this->conexion->error;
                $this->conexion->rollback();
                return false;
            }

            foreach ($cambios as $id => $porcentaje) {
                $pct = round((float) $porcentaje, 2);
                $idPrima = (int) $id;
                $stmt->bind_param('di', $pct, $idPrima);
                $stmt->execute();
            }

            $stmt->close();
            $this->conexion->commit();

            return true;
        } catch (\Throwable $e) {
            try { $this->conexion->rollback(); } catch (\Throwable $x) {}
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function cambiarEstatus(int $id, string $estatus): bool
    {
        if (!in_array($estatus, ['Activo', 'Inactivo'], true)) {
            return false;
        }

        try {
            $stmt = $this->conexion->prepare(
                'UPDATE PRIMA SET estatus = ? WHERE id_prima = ? AND id_trabajador IS NULL'
            );
            if (!$stmt) {
                $this->ultimoError = $this->conexion->error;
                return false;
            }
            $stmt->bind_param('si', $estatus, $id);
            $stmt->execute();
            $stmt->close();

            return true;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    private function marcarError(\Throwable $e): void
    {
        $this->ultimoError = $e->getMessage();
        // 1062 = Duplicate entry (índice UNIQUE de clave_catalogo: nombre + fecha)
        $this->esDuplicado = ((int) $e->getCode() === 1062) || stripos($e->getMessage(), 'duplicate') !== false;
    }
}