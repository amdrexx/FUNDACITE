<?php
// =============================================================================
// CONTROLADOR: Primas del trabajador  (registrar, actualizar y eliminar)
// Guarda en la tabla puente TRABAJADOR_PRIMA (trabajador <-> prima del catálogo PRIMA).
// Regla: el servidor calcula la prima con los datos REALES del trabajador; del
// formulario solo toma el trabajador, el tipo de prima y los montos por hijo.
// =============================================================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_primas.php';
require_once __DIR__ . '/../vistas/includes/guardian.php';
require_once __DIR__ . '/helpers/bitacora_helper.php';

$objprima = new Prima($conexion);

const TIPO_ANTIGUEDAD = 'Prima por antigüedad';
const TIPO_PROFESIONALIZACION = 'Prima por profesionalización';
const TIPO_HIJOS = 'Prima por hijos';
// Primas fijas del sistema; cualquier otra prima viene del catálogo de "nuevas primas"
const TIPOS_FIJOS = [TIPO_ANTIGUEDAD, TIPO_PROFESIONALIZACION, TIPO_HIJOS];

// Tabla 1 - Prima de antigüedad: años de servicio => porcentaje (23 años en adelante = 30%)
const PORCENTAJE_ANTIGUEDAD = [
    1 => 1.0,  2 => 2.0,  3 => 3.0,  4 => 4.0,  5 => 5.0,  6 => 6.2,
    7 => 7.4,  8 => 8.6,  9 => 9.8,  10 => 11.0, 11 => 12.4, 12 => 13.8,
    13 => 15.2, 14 => 16.6, 15 => 18.0, 16 => 19.6, 17 => 21.2, 18 => 22.8,
    19 => 24.4, 20 => 26.0, 21 => 27.8, 22 => 29.6,
];

function porcentajeAntiguedad(int $anios): float
{
    if ($anios < 1) {
        return 0.0;
    }

    return $anios >= 23 ? 30.0 : (float) (PORCENTAJE_ANTIGUEDAD[$anios] ?? 0.0);
}

/* ---------------------------------------------------------------------------
   Redirecciones y mensajes
--------------------------------------------------------------------------- */

function irA(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

function irRegistro(int $idEditar = 0): void
{
    irA('../vistas/registrar_prima.php' . ($idEditar > 0 ? '?accion=editar&id=' . $idEditar : ''));
}

function irLista(): void
{
    irA('../vistas/lista_primas.php');
}

function fallo(string $mensaje, int $idEditar = 0): void
{
    $_SESSION['errores'] = [$mensaje];
    $_SESSION['old'] = [
        'id_trabajador' => $_POST['id_trabajador'] ?? '',
        'tipo_prima'    => $_POST['tipo_prima'] ?? '',
        'tipos_prima'   => is_array($_POST['tipos_prima'] ?? null) ? array_values($_POST['tipos_prima']) : [],
        'estatus'       => $_POST['estatus'] ?? 'Activo',
        'valor_prima'   => is_array($_POST['valor_prima'] ?? null) ? $_POST['valor_prima'] : [],
    ];
    irRegistro($idEditar);
}

/* ---------------------------------------------------------------------------
   Cálculo de la prima a partir de los datos del trabajador
   Devuelve ['condicion','porcentaje','monto'] o ['error' => mensaje]
--------------------------------------------------------------------------- */

function calcularPrima(array $trab, string $tipo, array $post, ?array $tarifasHijo = null): array
{
    $base = (float) $trab['base_prima'];

    if ($tipo === TIPO_ANTIGUEDAD) {
        // Años de servicio del trabajador; si no están registrados se acepta el valor enviado.
        $anios = (int) $trab['anos_servicio'];

        if ($anios < 1) {
            $enviado = trim((string) ($post['condicion'] ?? ''));
            $anios = ctype_digit($enviado) ? (int) $enviado : 0;
        }

        $anios = min($anios, 30);

        if ($anios < 1) {
            return ['error' => 'El trabajador no tiene años de servicio registrados para la prima por antigüedad.'];
        }

        return [
            'condicion'  => $anios . ($anios === 1 ? ' año' : ' años'),
            'porcentaje' => porcentajeAntiguedad($anios),
            'monto'      => round($base * porcentajeAntiguedad($anios) / 100, 2),
        ];
    }

    if ($tipo === TIPO_PROFESIONALIZACION) {
        $porNivel = [
            'Técnico Superior Universitario' => ['TSU', 20],
            'Profesional'                    => ['Lic. / Ing.', 25],
            'Especialista'                   => ['Doc. / Post.', 35],
            'Maestría'                       => ['Doc. / Post.', 35],
            'Doctor'                         => ['Doc. / Post.', 35],
        ];
        $porCondicion = ['TSU' => 20, 'Lic. / Ing.' => 25, 'Doc. / Post.' => 35];

        $nivel = trim((string) $trab['nivel_educacion']);

        if ($nivel !== '') {
            if (!isset($porNivel[$nivel])) {
                return ['error' => "El nivel de educación del trabajador ($nivel) no genera prima por profesionalización."];
            }
            [$condicion, $pct] = $porNivel[$nivel];
        } else {
            $condicion = trim((string) ($post['condicion'] ?? ''));
            if (!isset($porCondicion[$condicion])) {
                return ['error' => 'Seleccione un nivel académico válido.'];
            }
            $pct = $porCondicion[$condicion];
        }

        return [
            'condicion'  => $condicion,
            'porcentaje' => (float) $pct,
            'monto'      => round($base * $pct / 100, 2),
        ];
    }

    if ($tipo === TIPO_HIJOS) {
        // Los hijos se leen de la base de datos (HIJO_TRABAJADOR), no del formulario:
        //   hijos de 0 a 5 años  -> primer valor de la prima por hijos del catálogo
        //   hijos de 6 a 21 años -> segundo valor
        // (corte propio de la prima; no coincide con el menor/mayor de edad legal)
        $menores = (int) ($trab['hijos_menores'] ?? 0);
        $mayores = (int) ($trab['hijos_mayores'] ?? 0);

        if ($menores + $mayores < 1) {
            return ['error' => (int) ($trab['hijos_fuera_rango'] ?? 0) > 0
                ? 'El trabajador no tiene hijos de 0 a 21 años (los hijos registrados superan los 21 años).'
                : 'El trabajador no tiene hijos con edad registrada. Regístrelos primero en el módulo de trabajadores.'];
        }

        if (!$tarifasHijo) {
            return ['error' => 'La prima por hijos del catálogo debe ser de monto fijo con dos valores: primero el del hijos de 0 a 5 años y luego el de hijos de 6 a 21 años.'];
        }

        $fmt = static fn(float $n): string => number_format($n, 2, ',', '.');
        $partes = [];

        if ($menores > 0) {
            $partes[] = $menores . ($menores === 1 ? ' hijo' : ' hijos') . ' de 0 a 5 años x ' . $fmt($tarifasHijo['menor']);
        }
        if ($mayores > 0) {
            $partes[] = $mayores . ($mayores === 1 ? ' hijo' : ' hijos') . ' de 6 a 21 años x ' . $fmt($tarifasHijo['mayor']);
        }

        return [
            'condicion'  => implode(' + ', $partes),
            'porcentaje' => 0.0,
            'monto'      => round($menores * $tarifasHijo['menor'] + $mayores * $tarifasHijo['mayor'], 2),
        ];
    }

    return ['error' => 'Debe seleccionar un tipo de prima válido.'];
}

/**
 * Prima nueva del catálogo.
 *  - tipo 'porcentaje': monto = base salarial del trabajador x porcentaje / 100
 *  - tipo 'monto'     : monto = el monto fijo del catálogo
 * Si la prima tiene varios valores, el formulario envía cuál se aplica en
 * valor_prima[<id_prima>] = id_prima_valor.
 * Devuelve ['condicion','porcentaje','monto','id_valor'] o ['error' => mensaje]
 */
function calcularPrimaNueva(array $trab, array $info, array $post): array
{
    $valores = $info['valores'] ?? [];

    if (!$valores) {
        return ['error' => 'La prima no tiene valores registrados en el catálogo.'];
    }

    if (count($valores) > 1) {
        $elegidos = $post['valor_prima'] ?? [];
        $idElegido = is_array($elegidos) ? (int) ($elegidos[$info['id_prima']] ?? 0) : 0;
        $sel = null;

        foreach ($valores as $v) {
            if ((int) $v['id'] === $idElegido) {
                $sel = $v;
                break;
            }
        }

        if ($sel === null) {
            return ['error' => 'Seleccione cuál de los valores de la prima desea aplicar.'];
        }
    } else {
        $sel = $valores[0];
    }

    $valor = round((float) $sel['valor'], 2);
    $idValor = (int) $sel['id'] > 0 ? (int) $sel['id'] : null;
    $esPct = ($info['tipo_valor'] ?? 'porcentaje') === 'porcentaje';

    if ($esPct) {
        if ($valor <= 0 || $valor > 100) {
            return ['error' => 'El porcentaje de la prima no es válido (debe ser mayor que 0 y hasta 100).'];
        }

        $base = (float) $trab['base_prima'];

        if ($base <= 0) {
            return ['error' => 'No existe una base salarial vigente para calcular esta prima. Revise el sueldo base o el salario asignado al cargo.'];
        }

        return [
            'condicion'  => rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.') . '% del salario',
            'porcentaje' => $valor,
            'monto'      => round($base * $valor / 100, 2),
            'id_valor'   => $idValor,
        ];
    }

    if ($valor <= 0) {
        return ['error' => 'El monto de la prima no es válido (debe ser mayor que 0).'];
    }

    return [
        'condicion'  => 'Monto fijo de ' . number_format($valor, 2, ',', '.') . ' Bs.',
        'porcentaje' => 0.0,
        'monto'      => $valor,
        'id_valor'   => $idValor,
    ];
}

/* ---------------------------------------------------------------------------
   Validaciones por tipo de prima y datos del formulario de registro múltiple
--------------------------------------------------------------------------- */

function validarTipoParaTrabajador(array $trab, string $tipo): ?string
{
    // Los obreros no pueden recibir prima por profesionalización
    $tipoPersonal = strtolower(trim((string) ($trab['tipo_personal'] ?? '')));
    if (strpos($tipoPersonal, 'obrero') !== false && $tipo === TIPO_PROFESIONALIZACION) {
        return 'Los trabajadores con estatus u ocupación de obrero no pueden recibir prima por profesionalización.';
    }

    if ($tipo !== TIPO_HIJOS
        && (float) ($trab['base_prima'] ?? 0) <= 0) {
        return 'No existe una base salarial vigente para calcular esta prima. Revise el sueldo base o el salario asignado al cargo.';
    }

    return null;
}

/** En el registro múltiple cada prima envía su propio campo de condición. */
function postParaTipo(string $tipo, array $post): array
{
    if ($tipo === TIPO_ANTIGUEDAD) {
        $post['condicion'] = $post['antiguedad_anios'] ?? '';
    } elseif ($tipo === TIPO_PROFESIONALIZACION) {
        $post['condicion'] = $post['profesionalizacion_nivel'] ?? '';
    }

    return $post;
}

/** Datos mínimos de una prima ya asignada (por si ya no está activa en el catálogo). */
function infoDesdeFila(array $fila): array
{
    $pct = (float) ($fila['porcentaje'] ?? 0);
    $esPct = $pct > 0;

    return [
        'id_prima'   => (int) ($fila['id_prima_catalogo'] ?? 0),
        'tipo_valor' => $esPct ? 'porcentaje' : 'monto',
        'valores'    => [[
            'id'    => (int) ($fila['id_prima_valor'] ?? 0),
            'valor' => $esPct ? $pct : (float) ($fila['monto'] ?? 0),
        ]],
    ];
}

/**
 * Calcula una prima sea fija (antigüedad, hijos, profesionalización) o nueva del catálogo.
 * $filaGuardada: la asignación que ya tenía el trabajador (se usa al editar si la prima
 * nueva ya no está activa en el catálogo).
 * Devuelve ['condicion','porcentaje','monto','id_valor'] o ['error' => mensaje]
 */
function calcularPrimaTipo(array $trab, string $tipo, array $post, array $catalogo, ?array $filaGuardada = null): array
{
    $infoCatalogo = $catalogo[$tipo] ?? null;

    if (in_array($tipo, TIPOS_FIJOS, true)) {
        // Prima por hijos: en el catálogo es de monto fijo con dos valores
        // (1.º = hijo menor 0-5 años, 2.º = hijo mayor 6-21 años)
        $tarifasHijo = null;
        if ($tipo === TIPO_HIJOS && $infoCatalogo !== null
            && ($infoCatalogo['tipo_valor'] ?? '') === 'monto'
            && count($infoCatalogo['valores']) >= 2) {
            $tarifasHijo = [
                'menor' => round((float) $infoCatalogo['valores'][0]['valor'], 2),
                'mayor' => round((float) $infoCatalogo['valores'][1]['valor'], 2),
            ];
        }

        $calc = calcularPrima($trab, $tipo, postParaTipo($tipo, $post), $tarifasHijo);

        if (!isset($calc['error'])) {
            $calc['id_valor'] = null;
        }

        return $calc;
    }

    $info = $infoCatalogo ?? ($filaGuardada !== null ? infoDesdeFila($filaGuardada) : null);

    if ($info === null) {
        return ['error' => 'La prima no está disponible en el catálogo de primas nuevas.'];
    }

    return calcularPrimaNueva($trab, $info, $post);
}

/* ---------------------------------------------------------------------------
   Solo se aceptan peticiones POST
--------------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    irLista();
}

$accion = trim($_POST['accion'] ?? '');

/* ---------------------------------------------------------------------------
   ELIMINAR
--------------------------------------------------------------------------- */

if ($accion === 'eliminar') {
    $idPrima = filter_input(INPUT_POST, 'id_prima', FILTER_VALIDATE_INT);
    $prima = $idPrima ? $objprima->obtenerPorId($idPrima) : null;

    if (!$prima) {
        $_SESSION['errores'] = ['No se encontró la prima que desea eliminar.'];
        irLista();
    }

    if ($objprima->eliminar($idPrima)) {
        registrarBitacora(
            $conexion,
            'Primas',
            'Eliminar',
            'Eliminó la "' . $prima['tipo_prima'] . '" (' . $prima['condicion'] . ') del trabajador "' .
            trim((string) $prima['nombre_completo']) . '".'
        );
        $_SESSION['exito'] = 'La prima se eliminó correctamente.';
    } else {
        $_SESSION['errores'] = ['No se pudo eliminar la prima.'];
    }

    irLista();
}

/* ---------------------------------------------------------------------------
   INACTIVAR / ACTIVAR todas las primas de un trabajador (desde la lista)
--------------------------------------------------------------------------- */

if (in_array($accion, ['inactivar_primas', 'activar_primas'], true)) {
    if (function_exists('esAdministradorODirector') && !esAdministradorODirector()) {
        $_SESSION['errores'] = ['No tiene permisos para cambiar el estatus de las primas.'];
        irLista();
    }

    $idT = (int) filter_input(INPUT_POST, 'id_trabajador', FILTER_VALIDATE_INT);
    $nuevoEstatus = $accion === 'inactivar_primas' ? 'Inactivo' : 'Activo';
    $trabajadorPrimas = $idT > 0 ? $objprima->obtenerTrabajador($idT) : null;

    if ($trabajadorPrimas === null) {
        $_SESSION['errores'] = ['No se encontró el trabajador seleccionado.'];
        irLista();
    }

    if ($objprima->cambiarEstatusPorTrabajador($idT, $nuevoEstatus)) {
        registrarBitacora(
            $conexion,
            'Primas',
            'Editar',
            ($nuevoEstatus === 'Inactivo' ? 'Inactivó' : 'Activó') . ' las primas del trabajador "' .
            trim((string) $trabajadorPrimas['nombre_completo']) . '".'
        );
        $_SESSION['exito'] = $nuevoEstatus === 'Inactivo'
            ? 'Las primas del trabajador se inactivaron correctamente.'
            : 'Las primas del trabajador se activaron correctamente.';
    } else {
        $_SESSION['errores'] = ['No se pudo cambiar el estatus de las primas.'];
    }

    irLista();
}

/* ---------------------------------------------------------------------------
   REGISTRAR / ACTUALIZAR
--------------------------------------------------------------------------- */

if (!in_array($accion, ['guardar', 'actualizar'], true)) {
    $_SESSION['errores'] = ['La acción solicitada no es válida.'];
    irLista();
}

$editando = $accion === 'actualizar';
$idEditar = $editando ? (int) filter_input(INPUT_POST, 'id_trabajador', FILTER_VALIDATE_INT) : 0; // al editar, el id es el del trabajador
$idTrabajador = (int) filter_input(INPUT_POST, 'id_trabajador', FILTER_VALIDATE_INT);
$tipo = trim($_POST['tipo_prima'] ?? '');
$estatusPrima = trim((string) ($_POST['estatus'] ?? 'Activo'));
if (!in_array($estatusPrima, ['Activo', 'Inactivo'], true)) {
    fallo('El estatus de la prima no es válido.', $editando ? (int) ($_POST['id_trabajador'] ?? 0) : 0);
}

if ($editando) {
    $existentes = $idEditar > 0 ? $objprima->listarPorTrabajador($idEditar) : [];

    if (!$existentes) {
        $_SESSION['errores'] = ['No se encontraron primas registradas para este trabajador.'];
        irLista();
    }

    // En edición el trabajador no cambia.
    $idTrabajador = $idEditar;
}

if ($idTrabajador <= 0) {
    fallo('Debe seleccionar un trabajador.', $idEditar);
}

$trab = $objprima->obtenerTrabajador($idTrabajador);

if ($trab === null) {
    fallo('El trabajador seleccionado no existe.', $idEditar);
}

if (!$editando && strtolower(trim((string) $trab['status'])) !== 'activo') {
    fallo('Solo se pueden registrar primas a trabajadores con estatus Activo.');
}

if (!$editando && !$objprima->tieneContrato($idTrabajador)) {
    fallo('Solo se pueden registrar primas a trabajadores que tengan un contrato registrado.');
}

// Se permite utilizar el salario vigente del cargo cuando corresponda.
if ((float) ($trab['salario_mensual'] ?? 0) <= 0) {
    fallo('No existe un salario vigente aplicable al trabajador. Registre en el módulo de salarios el sueldo de su cargo (o el sueldo base si es obrero).', $idEditar);
}

$nombre = trim((string) $trab['nombre_completo']);

// Primas nuevas activas del catálogo: [nombre => porcentaje]
$catalogo = $objprima->listarCatalogoNuevas();

/* ---------------------------------------------------------------------------
   REGISTRAR: varias primas a la vez (una sola transacción)
--------------------------------------------------------------------------- */

if (!$editando) {
    $enviadas = $_POST['tipos_prima'] ?? [];
    $enviadas = is_array($enviadas) ? $enviadas : [$enviadas];
    $tipos = array_values(array_unique(array_filter(array_map(
        static fn($t) => trim((string) $t),
        $enviadas
    ), static fn($t) => $t !== '')));

    if (!$tipos) {
        fallo('Seleccione al menos una prima para registrar.');
    }

    // Solo las primas registradas (y activas) en "Registrar nueva prima".
    // strval: un nombre numérico (ej. "2024") se convierte en clave entera de array
    $permitidos = array_map('strval', array_keys($catalogo));
    foreach ($tipos as $t) {
        if (!in_array($t, $permitidos, true)) {
            fallo('Debe seleccionar un tipo de prima válido.');
        }
    }

    $items = [];
    $duplicadas = [];

    foreach ($tipos as $t) {
        $error = validarTipoParaTrabajador($trab, $t);
        if ($error !== null) {
            fallo($error);
        }

        $calc = calcularPrimaTipo($trab, $t, $_POST, $catalogo);
        if (isset($calc['error'])) {
            fallo($t . ': ' . $calc['error']);
        }

        if ($calc['monto'] <= 0) {
            fallo($t . ': el monto de la prima debe ser mayor que cero.');
        }

        if ($objprima->existePrima($idTrabajador, $t, 0)) {
            $duplicadas[] = $t;
        }

        $items[] = [
            'tipo'        => $t,
            'id_prima'    => (int) $catalogo[$t]['id_prima'],
            'id_valor'    => $calc['id_valor'] ?? null,
            'condicion'   => $calc['condicion'],
            'porcentaje'  => $calc['porcentaje'],
            'monto'       => $calc['monto'],
            'monto_total' => round((float) $trab['salario_mensual'] + $calc['monto'], 2),
        ];
    }

    if ($duplicadas) {
        fallo('Este trabajador ya tiene registrada: ' . implode(', ', $duplicadas) . '. Desmárquela o edite la existente.');
    }

    if (!$objprima->registrarLote($idTrabajador, $items, $estatusPrima)) {
        $detalle = (string) ($objprima->ultimoError ?? '');
        if (stripos($detalle, 'duplicate') !== false) {
            fallo('Una o más de las primas ya estaban registradas para este trabajador. No se guardó ninguna.');
        }
        fallo('No se pudieron registrar las primas (no se guardó ninguna). ' . $detalle);
    }

    foreach ($items as $it) {
        registrarBitacora(
            $conexion,
            'Primas',
            'Crear',
            "Registró la \"{$it['tipo']}\" al trabajador \"$nombre\" con condición \"{$it['condicion']}\" " .
            "y un monto de Bs {$it['monto']}, estatus \"{$estatusPrima}\"."
        );
    }

    $_SESSION['exito'] = count($items) === 1
        ? 'La prima se registró correctamente.'
        : 'Se registraron ' . count($items) . ' primas correctamente: ' . implode(', ', array_column($items, 'tipo')) . '.';
    irRegistro();
}

/* ---------------------------------------------------------------------------
   ACTUALIZAR: todas las primas del trabajador en una sola transacción
--------------------------------------------------------------------------- */

$porTipo = [];
foreach ($existentes as $e) {
    $porTipo[$e['tipo_prima']] = $e;
}

$enviadas = $_POST['tipos_prima'] ?? [];
$enviadas = is_array($enviadas) ? $enviadas : [$enviadas];
$tipos = array_values(array_unique(array_filter(array_map(
    static fn($t) => trim((string) $t),
    $enviadas
), static fn($t) => $t !== '')));

if (!$tipos) {
    $tipos = array_keys($porTipo);
}

$items = [];

foreach ($tipos as $t) {
    if (!isset($porTipo[$t])) {
        fallo('El trabajador no tiene registrada la prima: ' . $t . '.', $idEditar);
    }

    $error = validarTipoParaTrabajador($trab, $t);
    if ($error !== null) {
        fallo($t . ': ' . $error, $idEditar);
    }

    $calc = calcularPrimaTipo($trab, $t, $_POST, $catalogo, $porTipo[$t]);
    if (isset($calc['error'])) {
        fallo($t . ': ' . $calc['error'], $idEditar);
    }

    if ($calc['monto'] <= 0) {
        fallo($t . ': el monto de la prima debe ser mayor que cero.', $idEditar);
    }

    $items[] = [
        'id_prima'    => (int) $porTipo[$t]['id_prima'],   // id de la asignación (TRABAJADOR_PRIMA)
        'id_valor'    => $calc['id_valor'] ?? null,
        'tipo'        => $t,
        'condicion'   => $calc['condicion'],
        'porcentaje'  => $calc['porcentaje'],
        'monto'       => $calc['monto'],
        'monto_total' => round((float) $trab['salario_mensual'] + $calc['monto'], 2),
    ];
}

if (!$objprima->actualizarLote($items, $estatusPrima)) {
    fallo('No se pudieron actualizar las primas (no se modificó ninguna). ' . ($objprima->ultimoError ?? ''), $idEditar);
}

foreach ($items as $it) {
    $ant = $porTipo[$it['tipo']];
    registrarBitacora(
        $conexion,
        'Primas',
        'Editar',
        "Actualizó la \"{$it['tipo']}\" del trabajador \"$nombre\": condición \"{$ant['condicion']}\" → " .
        "\"{$it['condicion']}\", monto Bs {$it['monto']}, estatus \"{$ant['estatus']}\" → \"{$estatusPrima}\"."
    );
}

$_SESSION['exito'] = count($items) === 1
    ? 'La prima se actualizó correctamente.'
    : 'Se actualizaron ' . count($items) . ' primas correctamente.';
irLista();