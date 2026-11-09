<?php
// =============================================================================
// MODELO: Prima  (primas asignadas a cada trabajador)
// -----------------------------------------------------------------------------
// - Usa la tabla puente TRABAJADOR_PRIMA (trabajador <-> prima del catálogo PRIMA).
//   Las tablas se crean con bd/migracion_primas.sql (esta clase ya no modifica la
//   estructura de la base de datos).
// - Los datos del trabajador se leen de TRABAJADOR / CONTRATO / CARGO / SALARIO.
//   Si a TRABAJADOR le falta alguna columna opcional (hijos, años de servicio,
//   nivel de educación) la consulta se adapta y NO falla.
// =============================================================================

class Prima
{
    private mysqli $conexion;
    private ?array $columnasTrabajador = null;

    /** Último error de base de datos (para mostrarlo en pantalla). */
    public ?string $ultimoError = null;

    public function __construct(mysqli $conexion)
    {
        $this->conexion = $conexion;
    }

    /* =====================================================
       COLUMNAS OPCIONALES DE TRABAJADOR
    ===================================================== */

    private function tieneColumna(string $nombre): bool
    {
        if ($this->columnasTrabajador === null) {
            $this->columnasTrabajador = [];
            $res = $this->conexion->query('SHOW COLUMNS FROM TRABAJADOR');

            if ($res) {
                while ($f = $res->fetch_assoc()) {
                    $this->columnasTrabajador[] = strtolower((string) $f['Field']);
                }
            }
        }

        return in_array(strtolower($nombre), $this->columnasTrabajador, true);
    }

    /* =====================================================
       SUELDO BASE VIGENTE
    ===================================================== */

    public function obtenerSueldoBase(): ?float
    {
        $res = $this->conexion->query(
            "SELECT monto FROM SALARIO
             WHERE tipo_salario = 'base' AND estado = 'Vigente'
             ORDER BY fecha DESC, id_salario DESC
             LIMIT 1"
        );

        if (!$res || $res->num_rows === 0) {
            return null;
        }

        $monto = (float) $res->fetch_assoc()['monto'];

        return $monto > 0 ? $monto : null;
    }

    /**
     * Regla de salario (única fuente de verdad):
     *  - Obrero             -> solo el sueldo base (sueldo mínimo).
     *  - Resto del personal -> solo el sueldo del cargo (sueldo habitual asignado al cargo).
     * Las primas se calculan sobre 'base_prima' (= el salario que devenga el trabajador).
     */
    private function resolverSalario(string $tipoPersonal, float $base, float $montoCargo): array
    {
        if ($tipoPersonal === 'Obrero') {
            // El obrero siempre devenga el sueldo base (no se usa el salario del cargo).
            $mensual   = $base;
            $cargo     = 0.0;
            $origen    = 'base';
            $basePrima = $mensual;
        } else {
            // Personal no obrero: devenga únicamente el sueldo de su cargo
            $mensual   = $montoCargo;
            $cargo     = $montoCargo;
            $origen    = 'cargo';
            $basePrima = $mensual;
        }

        return [
            'origen_salario'  => $origen,
            'sueldo_base'     => round($base, 2),
            'sueldo_cargo'    => round($cargo, 2),
            'salario_mensual' => round($mensual, 2),
            'base_prima'      => round($basePrima, 2),
        ];
    }

    /* =====================================================
       TRABAJADORES CON TODOS SUS DATOS
    ===================================================== */

    private function consultaTrabajadores(string $where): string
    {
        $hijos = $this->tieneColumna('hijos') ? 't.hijos' : '0';
        $anos  = $this->tieneColumna('anos_servicio_administracion_publica')
            ? 't.anos_servicio_administracion_publica' : '0';
        $nivel = $this->tieneColumna('nivel_educacion') ? "COALESCE(t.nivel_educacion, '')" : "''";
        $ingreso = $this->tieneColumna('fecha_ingreso') ? 't.fecha_ingreso' : 'NULL';

        return "SELECT
                    t.id_trabajador,
                    t.cedula,
                    t.nombres,
                    t.apellidos,
                    CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo,
                    t.status,
                    t.id_cargo,
                    COALESCE(c.nombre_cargo, '') AS cargo,
                    COALESCE(t.tipo_personal, '') AS tipo_personal,
                    {$hijos} AS hijos,
                    {$nivel} AS nivel_educacion,
                    COALESCE(NULLIF({$anos}, 0),
                             TIMESTAMPDIFF(
                                 YEAR,
                                 COALESCE({$ingreso}, (
                                     SELECT MIN(co2.fecha_contrato) FROM CONTRATO co2
                                     WHERE co2.id_trabajador = t.id_trabajador
                                 )),
                                 CURDATE()
                             ),
                             0) AS anos_servicio,
                    COALESCE((
                        SELECT s.monto FROM SALARIO s
                        WHERE s.id_cargo = t.id_cargo
                          AND s.tipo_salario = 'cargo'
                          AND s.estado = 'Vigente'
                        ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1
                    ), 0) AS monto_cargo,
                    (SELECT s.id_salario FROM SALARIO s
                     WHERE s.id_cargo = t.id_cargo
                       AND s.tipo_salario = 'cargo'
                       AND s.estado = 'Vigente'
                     ORDER BY s.fecha DESC, s.id_salario DESC LIMIT 1) AS salario_cargo_id,
                    EXISTS(SELECT 1 FROM SALARIO sx
                           WHERE sx.id_cargo = t.id_cargo
                             AND sx.tipo_salario = 'cargo'
                             AND sx.estado = 'Vigente'
                             AND sx.monto > 0) AS salario_cargo_registrado
                FROM TRABAJADOR t
                LEFT JOIN CARGO c ON c.id_cargo = t.id_cargo
                {$where}
                ORDER BY t.nombres ASC, t.apellidos ASC";
    }

    private function completarTrabajador(array $fila, float $base): array
    {
        $salario = $this->resolverSalario(
            (string) $fila['tipo_personal'],
            $base,
            (float) $fila['monto_cargo']
        );

        unset($fila['monto_cargo']);
        $fila['salario_cargo_registrado'] = (bool) ($fila['salario_cargo_registrado'] ?? false);
        $fila['id_cargo'] = isset($fila['id_cargo']) ? (int) $fila['id_cargo'] : null;
        $fila['salario_cargo_id'] = isset($fila['salario_cargo_id']) ? (int) $fila['salario_cargo_id'] : null;

        $fila['hijos']         = (int) $fila['hijos'];
        $fila['anos_servicio'] = max(0, (int) $fila['anos_servicio']);

        return array_merge($fila, $salario);
    }

    /**
     * Trabajadores ACTIVOS para el select. Con $incluirId se agrega además ese
     * trabajador aunque ya no esté activo (útil al editar una prima).
     */
    /** ¿El trabajador tiene al menos un contrato registrado? */
    public function tieneContrato(int $idTrabajador): bool
    {
        $stmt = $this->conexion->prepare('SELECT 1 FROM CONTRATO WHERE id_trabajador = ? LIMIT 1');

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $idTrabajador);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $existe;
    }

    public function listarTrabajadoresActivos(int $incluirId = 0): array
    {
        $base  = $this->obtenerSueldoBase() ?? 0.0;
        // Solo trabajadores activos que tengan al menos un contrato registrado.
        // Al editar se incluye siempre el trabajador de la prima que se está editando.
        $where = "WHERE ((LOWER(TRIM(t.status)) = 'activo'
                          AND EXISTS (SELECT 1 FROM CONTRATO cx WHERE cx.id_trabajador = t.id_trabajador))";

        if ($incluirId > 0) {
            $where .= ' OR t.id_trabajador = ' . $incluirId;
        }

        $where .= ')';

        $res = $this->conexion->query($this->consultaTrabajadores($where));

        if (!$res) {
            $this->ultimoError = $this->conexion->error;
            error_log('Primas - error al listar trabajadores: ' . $this->conexion->error);
            return [];
        }

        $filas = [];

        foreach ($res->fetch_all(MYSQLI_ASSOC) as $fila) {
            $filas[] = $this->completarTrabajador($fila, $base);
        }

        return $filas;
    }

    public function obtenerTrabajador(int $id): ?array
    {
        $res = $this->conexion->query(
            $this->consultaTrabajadores('WHERE t.id_trabajador = ' . (int) $id)
        );

        if (!$res || $res->num_rows === 0) {
            if (!$res) {
                $this->ultimoError = $this->conexion->error;
            }
            return null;
        }

        return $this->completarTrabajador(
            $res->fetch_assoc(),
            $this->obtenerSueldoBase() ?? 0.0
        );
    }

    /* =====================================================
       PRIMAS ASIGNADAS A TRABAJADORES  (tabla puente TRABAJADOR_PRIMA)
       Se mantienen los alias de siempre (id_prima = id de la asignación,
       tipo_prima = nombre de la prima) para no romper las vistas.
    ===================================================== */

    private const SELECT_PRIMAS = "SELECT
                tp.id_trabajador_prima AS id_prima,
                tp.id_trabajador,
                tp.id_prima AS id_prima_catalogo,
                tp.id_prima_valor,
                p.nombre AS tipo_prima,
                p.tipo_valor,
                tp.condicion,
                tp.porcentaje, tp.monto, tp.monto_total, tp.fecha, tp.estatus,
                t.cedula,
                CONCAT(t.nombres, ' ', t.apellidos) AS nombre_completo
            FROM TRABAJADOR_PRIMA tp
            JOIN PRIMA p ON p.id_prima = tp.id_prima
            LEFT JOIN TRABAJADOR t ON t.id_trabajador = tp.id_trabajador";

    /** Cambia el estatus (Activo/Inactivo) de todas las primas de un trabajador. */
    public function cambiarEstatusPorTrabajador(int $idTrabajador, string $estatus): bool
    {
        if (!in_array($estatus, ['Activo', 'Inactivo'], true)) {
            return false;
        }

        $stmt = $this->conexion->prepare('UPDATE TRABAJADOR_PRIMA SET estatus = ? WHERE id_trabajador = ?');

        if (!$stmt) {
            $this->ultimoError = $this->conexion->error;
            return false;
        }

        $stmt->bind_param('si', $estatus, $idTrabajador);
        $ok = $stmt->execute();

        if (!$ok) {
            $this->ultimoError = $stmt->error;
        }

        $stmt->close();
        return $ok;
    }

    /** Todas las primas registradas a un trabajador (de la más antigua a la más reciente). */
    public function listarPorTrabajador(int $idTrabajador): array
    {
        $stmt = $this->conexion->prepare(
            self::SELECT_PRIMAS . ' WHERE tp.id_trabajador = ? ORDER BY tp.id_trabajador_prima ASC'
        );

        if (!$stmt) {
            $this->ultimoError = $this->conexion->error;
            return [];
        }

        $stmt->bind_param('i', $idTrabajador);

        if (!$stmt->execute()) {
            $this->ultimoError = $stmt->error;
            $stmt->close();
            return [];
        }

        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $filas;
    }

    public function listar(string $buscar = ''): array
    {
        $sql = self::SELECT_PRIMAS . "
                WHERE (   p.nombre LIKE ?
                       OR tp.condicion LIKE ?
                       OR COALESCE(t.cedula, '') LIKE ?
                       OR COALESCE(t.nombres, '') LIKE ?
                       OR COALESCE(t.apellidos, '') LIKE ?
                       OR CONCAT(COALESCE(t.nombres, ''), ' ', COALESCE(t.apellidos, '')) LIKE ?)
                ORDER BY tp.id_trabajador_prima DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            $this->ultimoError = $this->conexion->error;
            return [];
        }

        $t = '%' . $buscar . '%';
        $stmt->bind_param('ssssss', $t, $t, $t, $t, $t, $t);

        if (!$stmt->execute()) {
            $this->ultimoError = $stmt->error;
            $stmt->close();
            return [];
        }

        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $filas;
    }

    /** $id = id de la asignación (TRABAJADOR_PRIMA.id_trabajador_prima). */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->conexion->prepare(self::SELECT_PRIMAS . ' WHERE tp.id_trabajador_prima = ? LIMIT 1');

        if (!$stmt) {
            $this->ultimoError = $this->conexion->error;
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $fila ?: null;
    }

    /**
     * Tipos de prima ya registrados por trabajador: [id_trabajador => [tipo, ...]]
     * @return array<int,array<int,string>>
     */
    public function tiposRegistradosPorTrabajador(): array
    {
        $res = $this->conexion->query(
            'SELECT tp.id_trabajador, p.nombre AS tipo_prima
             FROM TRABAJADOR_PRIMA tp
             JOIN PRIMA p ON p.id_prima = tp.id_prima'
        );
        $mapa = [];

        while ($res && ($f = $res->fetch_assoc())) {
            $mapa[(int) $f['id_trabajador']][] = (string) $f['tipo_prima'];
        }

        return $mapa;
    }

    /**
     * Catálogo de primas ACTIVAS que se pueden asignar a un trabajador.
     * De cada nombre se toma la versión más reciente (por fecha) y solo se incluye
     * si está Activa y tiene al menos un valor.
     *
     * @return array<string,array{id_prima:int,tipo_valor:string,valores:array<int,array{id:int,valor:float}>}>
     */
    public function listarCatalogoNuevas(): array
    {
        $res = $this->conexion->query(
            'SELECT p.id_prima, p.nombre, p.tipo_valor, p.estatus, v.id_prima_valor, v.valor
             FROM PRIMA p
             LEFT JOIN PRIMA_VALOR v ON v.id_prima = p.id_prima
             ORDER BY p.fecha DESC, p.id_prima DESC, v.orden, v.id_prima_valor'
        );

        $masReciente = [];   // clave del nombre => id_prima de su versión más reciente
        $catalogo = [];

        while ($res && ($f = $res->fetch_assoc())) {
            $nombre = (string) $f['nombre'];
            $clave = mb_strtolower(trim($nombre), 'UTF-8');
            $idPrima = (int) $f['id_prima'];

            if (!isset($masReciente[$clave])) {
                $masReciente[$clave] = $idPrima;
            }

            if ($masReciente[$clave] !== $idPrima) {
                continue; // versión más antigua de la misma prima
            }

            if (!isset($catalogo[$nombre])) {
                $catalogo[$nombre] = [
                    'id_prima'   => $idPrima,
                    'tipo_valor' => (string) $f['tipo_valor'],
                    'estatus'    => (string) $f['estatus'],
                    'valores'    => [],
                ];
            }

            if ($f['id_prima_valor'] !== null) {
                $catalogo[$nombre]['valores'][] = [
                    'id'    => (int) $f['id_prima_valor'],
                    'valor' => (float) $f['valor'],
                ];
            }
        }

        foreach ($catalogo as $nombre => $info) {
            if ($info['estatus'] !== 'Activo' || !$info['valores']) {
                unset($catalogo[$nombre]);
                continue;
            }
            unset($catalogo[$nombre]['estatus']);
        }

        return $catalogo;
    }

    /** ¿El trabajador ya tiene una prima de este tipo (nombre)? (excluye $idExcluir al editar) */
    public function existePrima(int $idTrabajador, string $tipo, int $idExcluir = 0): bool
    {
        $stmt = $this->conexion->prepare(
            'SELECT tp.id_trabajador_prima
             FROM TRABAJADOR_PRIMA tp
             JOIN PRIMA p ON p.id_prima = tp.id_prima
             WHERE tp.id_trabajador = ? AND p.nombre = ? AND tp.id_trabajador_prima <> ? LIMIT 1'
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('isi', $idTrabajador, $tipo, $idExcluir);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $existe;
    }

    /**
     * Asigna una prima del catálogo a un trabajador.
     * $idValor: valor elegido de PRIMA_VALOR (null si la prima no usa uno del catálogo).
     */
    public function registrar(
        int $idTrabajador,
        int $idPrima,
        ?int $idValor,
        string $condicion,
        float $porcentaje,
        float $monto,
        float $montoTotal,
        string $estatus = 'Activo'
    ): bool {
        $stmt = $this->conexion->prepare(
            'INSERT INTO TRABAJADOR_PRIMA
                (id_trabajador, id_prima, id_prima_valor, condicion, porcentaje, monto, monto_total, fecha, estatus)
             VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)'
        );

        if (!$stmt) {
            $this->ultimoError = $this->conexion->error;
            return false;
        }

        $stmt->bind_param('iiisddds', $idTrabajador, $idPrima, $idValor, $condicion, $porcentaje, $monto, $montoTotal, $estatus);
        $ok = $stmt->execute();

        if (!$ok) {
            $this->ultimoError = $stmt->error;
        }

        $stmt->close();

        return $ok;
    }

    /**
     * Asigna varias primas a un mismo trabajador en una sola transacción:
     * se guardan todas o ninguna.
     *
     * @param array<int,array{id_prima:int,id_valor:?int,condicion:string,porcentaje:float,monto:float,monto_total:float}> $items
     */
    public function registrarLote(int $idTrabajador, array $items, string $estatus = 'Activo'): bool
    {
        if (!$items) {
            $this->ultimoError = 'No hay primas para registrar.';
            return false;
        }

        try {
            $this->conexion->begin_transaction();

            foreach ($items as $it) {
                $ok = $this->registrar(
                    $idTrabajador,
                    (int) $it['id_prima'],
                    $it['id_valor'] ?? null,
                    $it['condicion'],
                    $it['porcentaje'],
                    $it['monto'],
                    $it['monto_total'],
                    $estatus
                );

                if (!$ok) {
                    $this->conexion->rollback();
                    return false;
                }
            }

            $this->conexion->commit();
            return true;
        } catch (\Throwable $e) {
            try { $this->conexion->rollback(); } catch (\Throwable $x) {}
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /** $idAsignacion = TRABAJADOR_PRIMA.id_trabajador_prima (la prima del catálogo no cambia). */
    public function actualizar(
        int $idAsignacion,
        ?int $idValor,
        string $condicion,
        float $porcentaje,
        float $monto,
        float $montoTotal,
        string $estatus = 'Activo'
    ): bool {
        $stmt = $this->conexion->prepare(
            'UPDATE TRABAJADOR_PRIMA
             SET id_prima_valor = ?, condicion = ?, porcentaje = ?, monto = ?,
                 monto_total = ?, fecha = CURDATE(), estatus = ?
             WHERE id_trabajador_prima = ?'
        );

        if (!$stmt) {
            $this->ultimoError = $this->conexion->error;
            return false;
        }

        $stmt->bind_param('isdddsi', $idValor, $condicion, $porcentaje, $monto, $montoTotal, $estatus, $idAsignacion);
        $ok = $stmt->execute();

        if (!$ok) {
            $this->ultimoError = $stmt->error;
        }

        $stmt->close();

        return $ok;
    }

    /**
     * Actualiza varias primas de un trabajador en una sola transacción.
     * @param array<int,array{id_prima:int,id_valor:?int,condicion:string,porcentaje:float,monto:float,monto_total:float}> $items
     *        (aquí 'id_prima' es el id de la asignación, como siempre)
     */
    public function actualizarLote(array $items, string $estatus = 'Activo'): bool
    {
        if (!$items) {
            $this->ultimoError = 'No hay primas para actualizar.';
            return false;
        }

        try {
            $this->conexion->begin_transaction();

            foreach ($items as $it) {
                $ok = $this->actualizar(
                    (int) $it['id_prima'],
                    $it['id_valor'] ?? null,
                    $it['condicion'],
                    $it['porcentaje'],
                    $it['monto'],
                    $it['monto_total'],
                    $estatus
                );

                if (!$ok) {
                    $this->conexion->rollback();
                    return false;
                }
            }

            $this->conexion->commit();
            return true;
        } catch (\Throwable $e) {
            try { $this->conexion->rollback(); } catch (\Throwable $x) {}
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function eliminar(int $idAsignacion): bool
    {
        $stmt = $this->conexion->prepare('DELETE FROM TRABAJADOR_PRIMA WHERE id_trabajador_prima = ?');

        if (!$stmt) {
            $this->ultimoError = $this->conexion->error;
            return false;
        }

        $stmt->bind_param('i', $idAsignacion);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /* =====================================================
       TOTAL DEVENGADO (para la constancia): salario + primas
    ===================================================== */

    public function obtenerTotalDevengado(int $idTrabajador): ?array
    {
        $trab = $this->obtenerTrabajador($idTrabajador);

        if ($trab === null) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            "SELECT COALESCE(SUM(monto), 0) AS total FROM TRABAJADOR_PRIMA WHERE id_trabajador = ? AND estatus = 'Activo'"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $idTrabajador);
        $stmt->execute();
        $primas = (float) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $trab['total_primas']    = round($primas, 2);
        $trab['total_devengado'] = round($trab['salario_mensual'] + $primas, 2);

        return $trab;
    }
}