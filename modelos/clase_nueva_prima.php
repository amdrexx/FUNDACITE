<?php
// =============================================================================
// MODELO: NuevaPrima  (catálogo de primas)
// -----------------------------------------------------------------------------
// Tablas (ver bd/migracion_primas.sql):
//   PRIMA        (id_prima, nombre, tipo_valor 'porcentaje'|'monto', fecha, estatus)
//   PRIMA_VALOR  (id_prima_valor, id_prima, valor, orden)  -> una prima puede tener
//                varios valores (por ejemplo 12 % y 56 %)
//
// Las primas que se asignan a un trabajador viven en TRABAJADOR_PRIMA (tabla puente)
// y las maneja clase_primas.php. Aquí solo se administra el catálogo.
// =============================================================================

class NuevaPrima
{
    public const TIPOS_VALOR  = ['porcentaje', 'monto'];
    public const MAX_VALORES  = 10;

    private mysqli $conexion;

    /** Último error de base de datos (para mostrarlo en pantalla). */
    public ?string $ultimoError = null;

    /** Se pone en true cuando el último registro/actualización falló por duplicado. */
    public bool $esDuplicado = false;

    public function __construct(mysqli $conexion)
    {
        $this->conexion = $conexion;
    }

    /* =====================================================
       Auxiliares
    ===================================================== */

    /** Ejecuta y lanza excepción si falla (funciona con o sin mysqli_report). */
    private function ejecutar(mysqli_stmt $stmt): void
    {
        if (!$stmt->execute()) {
            throw new RuntimeException($stmt->error, $stmt->errno);
        }
    }

    private function preparar(string $sql): mysqli_stmt
    {
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException($this->conexion->error, $this->conexion->errno);
        }
        return $stmt;
    }

    /** "12:12.00|13:56.00" -> [['id'=>12,'valor'=>12.0], ['id'=>13,'valor'=>56.0]] */
    private function parsearValores(?string $txt): array
    {
        $salida = [];

        if ($txt === null || $txt === '') {
            return $salida;
        }

        foreach (explode('|', $txt) as $par) {
            [$id, $valor] = array_pad(explode(':', $par, 2), 2, '0');
            $salida[] = ['id' => (int) $id, 'valor' => (float) $valor];
        }

        return $salida;
    }

    private const SELECT_PRIMA = "SELECT p.id_prima AS id_nueva_prima, p.nombre, p.tipo_valor, p.fecha, p.estatus,
            (SELECT GROUP_CONCAT(CONCAT(v.id_prima_valor, ':', v.valor)
                                 ORDER BY v.orden, v.id_prima_valor SEPARATOR '|')
               FROM PRIMA_VALOR v WHERE v.id_prima = p.id_prima) AS valores
        FROM PRIMA p";

    /* =====================================================
       CONSULTAS
    ===================================================== */

    /** @return array<int,array> cada fila trae 'valores' => [['id'=>..,'valor'=>..], ...] */
    public function listar(string $buscar = ''): array
    {
        $sql = self::SELECT_PRIMA;

        try {
            if ($buscar !== '') {
                $sql .= " WHERE (p.nombre LIKE ? OR p.estatus LIKE ? OR p.tipo_valor LIKE ?
                                 OR DATE_FORMAT(p.fecha, '%d/%m/%Y') LIKE ?
                                 OR EXISTS (SELECT 1 FROM PRIMA_VALOR x
                                            WHERE x.id_prima = p.id_prima
                                              AND CAST(x.valor AS CHAR) LIKE ?))";
            }
            $sql .= " ORDER BY p.fecha DESC, p.id_prima DESC";

            $stmt = $this->preparar($sql);

            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $stmt->bind_param('sssss', $like, $like, $like, $like, $like);
            }

            $this->ejecutar($stmt);
            $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($filas as &$f) {
                $f['valores'] = $this->parsearValores($f['valores']);
            }
            unset($f);

            return $filas;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return [];
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->preparar(self::SELECT_PRIMA . ' WHERE p.id_prima = ?');
            $stmt->bind_param('i', $id);
            $this->ejecutar($stmt);
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$fila) {
                return null;
            }

            $fila['valores'] = $this->parsearValores($fila['valores']);

            return $fila;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return null;
        }
    }

    /**
     * Cada valor de porcentaje de las primas ACTIVAS de tipo porcentaje
     * (lo usa la pantalla "Actualizar porcentajes").
     */
    public function listarValoresPorcentaje(): array
    {
        try {
            $res = $this->conexion->query(
                "SELECT v.id_prima_valor, p.id_prima, p.nombre, p.fecha, v.valor,
                        (SELECT COUNT(*) FROM PRIMA_VALOR c WHERE c.id_prima = p.id_prima) AS total_valores,
                        (SELECT COUNT(*) FROM PRIMA_VALOR c2
                          WHERE c2.id_prima = p.id_prima
                            AND (c2.orden < v.orden
                                 OR (c2.orden = v.orden AND c2.id_prima_valor <= v.id_prima_valor))) AS posicion
                 FROM PRIMA_VALOR v
                 JOIN PRIMA p ON p.id_prima = v.id_prima
                 WHERE p.estatus = 'Activo' AND p.tipo_valor = 'porcentaje'
                 ORDER BY p.fecha DESC, p.id_prima DESC, v.orden, v.id_prima_valor"
            );

            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return [];
        }
    }

    /**
     * ¿Ya existe una prima con ese nombre en esa fecha?
     * $excluirId sirve al editar para no compararse contra el propio registro.
     */
    public function existeDuplicado(string $nombre, string $fecha, int $excluirId = 0): bool
    {
        try {
            $stmt = $this->preparar(
                'SELECT COUNT(*) FROM PRIMA
                 WHERE LOWER(TRIM(nombre)) = LOWER(?) AND fecha = ? AND id_prima <> ?'
            );
            $stmt->bind_param('ssi', $nombre, $fecha, $excluirId);
            $this->ejecutar($stmt);
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

    /**
     * Registra la prima y todos sus valores en una sola transacción.
     * @param float[] $valores
     * @return int id insertado, o 0 si falló (ver $esDuplicado y $ultimoError).
     */
    public function registrar(string $nombre, string $fecha, string $tipoValor, array $valores): int
    {
        $this->esDuplicado = false;

        try {
            $this->conexion->begin_transaction();

            $stmt = $this->preparar(
                "INSERT INTO PRIMA (nombre, tipo_valor, fecha, estatus) VALUES (?, ?, ?, 'Activo')"
            );
            $stmt->bind_param('sss', $nombre, $tipoValor, $fecha);
            $this->ejecutar($stmt);
            $id = (int) $this->conexion->insert_id;
            $stmt->close();

            $this->sincronizarValores($id, $valores);

            $this->conexion->commit();

            return $id;
        } catch (\Throwable $e) {
            $this->revertir();
            $this->marcarError($e);
            return 0;
        }
    }

    /** @param float[] $valores */
    public function actualizar(int $id, string $nombre, string $fecha, string $tipoValor, array $valores): bool
    {
        $this->esDuplicado = false;

        try {
            $this->conexion->begin_transaction();

            $stmt = $this->preparar(
                'UPDATE PRIMA SET nombre = ?, tipo_valor = ?, fecha = ? WHERE id_prima = ?'
            );
            $stmt->bind_param('sssi', $nombre, $tipoValor, $fecha, $id);
            $this->ejecutar($stmt);
            $stmt->close();

            $this->sincronizarValores($id, $valores);

            $this->conexion->commit();

            return true;
        } catch (\Throwable $e) {
            $this->revertir();
            $this->marcarError($e);
            return false;
        }
    }

    /**
     * Deja en PRIMA_VALOR exactamente la lista recibida, respetando el orden:
     * el valor N actualiza la fila N existente (así se conserva su id y el enlace
     * con TRABAJADOR_PRIMA), los que sobran se agregan y los que faltan se borran.
     * Debe llamarse dentro de una transacción.
     *
     * @param float[] $valores
     */
    private function sincronizarValores(int $idPrima, array $valores): void
    {
        $stmt = $this->preparar(
            'SELECT id_prima_valor FROM PRIMA_VALOR WHERE id_prima = ? ORDER BY orden, id_prima_valor'
        );
        $stmt->bind_param('i', $idPrima);
        $this->ejecutar($stmt);
        $existentes = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id_prima_valor'));
        $stmt->close();

        $upd = $this->preparar('UPDATE PRIMA_VALOR SET valor = ?, orden = ? WHERE id_prima_valor = ?');
        $ins = $this->preparar('INSERT INTO PRIMA_VALOR (id_prima, valor, orden) VALUES (?, ?, ?)');
        $del = $this->preparar('DELETE FROM PRIMA_VALOR WHERE id_prima_valor = ?');

        foreach (array_values($valores) as $i => $valor) {
            $v = round((float) $valor, 2);
            $orden = $i + 1;

            if (isset($existentes[$i])) {
                $idValor = $existentes[$i];
                $upd->bind_param('dii', $v, $orden, $idValor);
                $this->ejecutar($upd);
            } else {
                $ins->bind_param('idi', $idPrima, $v, $orden);
                $this->ejecutar($ins);
            }
        }

        for ($i = count($valores); $i < count($existentes); $i++) {
            $idValor = $existentes[$i];
            $del->bind_param('i', $idValor);
            $this->ejecutar($del);
        }

        $upd->close();
        $ins->close();
        $del->close();
    }

    /**
     * Actualiza varios valores de porcentaje en una sola transacción:
     * se guardan todos los cambios o ninguno.
     *
     * @param array<int,float> $cambios  [id_prima_valor => nuevo porcentaje]
     */
    public function actualizarValores(array $cambios): bool
    {
        if (!$cambios) {
            return false;
        }

        try {
            $this->conexion->begin_transaction();

            $stmt = $this->preparar('UPDATE PRIMA_VALOR SET valor = ? WHERE id_prima_valor = ?');

            foreach ($cambios as $id => $valor) {
                $v = round((float) $valor, 2);
                $idValor = (int) $id;
                $stmt->bind_param('di', $v, $idValor);
                $this->ejecutar($stmt);
            }

            $stmt->close();
            $this->conexion->commit();

            return true;
        } catch (\Throwable $e) {
            $this->revertir();
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
            $stmt = $this->preparar('UPDATE PRIMA SET estatus = ? WHERE id_prima = ?');
            $stmt->bind_param('si', $estatus, $id);
            $this->ejecutar($stmt);
            $stmt->close();

            return true;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    private function revertir(): void
    {
        try { $this->conexion->rollback(); } catch (\Throwable $x) {}
    }

    private function marcarError(\Throwable $e): void
    {
        $this->ultimoError = $e->getMessage();
        // 1062 = Duplicate entry (índice UNIQUE nombre + fecha)
        $this->esDuplicado = ((int) $e->getCode() === 1062) || stripos($e->getMessage(), 'duplicate') !== false;
    }
}