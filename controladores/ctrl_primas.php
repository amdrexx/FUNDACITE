<?php
// =============================================================================
// CONTROLADOR: Primas  (registrar, actualizar y eliminar)
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
const MONTOS_HIJO_VALIDOS = [56.00, 12.50];

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
        'estatus'       => $_POST['estatus'] ?? 'Activo',
    ];
    irRegistro($idEditar);
}

/* ---------------------------------------------------------------------------
   Cálculo de la prima a partir de los datos del trabajador
   Devuelve ['condicion','porcentaje','monto'] o ['error' => mensaje]
--------------------------------------------------------------------------- */

function calcularPrima(array $trab, string $tipo, array $post): array
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
            'porcentaje' => (float) $anios,
            'monto'      => round($base * $anios / 100, 2),
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
        $cantidad = (int) $trab['hijos'];

        if ($cantidad < 1) {
            $enviado = trim((string) ($post['cantidad_hijos'] ?? ''));
            $cantidad = ctype_digit($enviado) ? (int) $enviado : 0;
        }

        if ($cantidad < 1 || $cantidad > 20) {
            return ['error' => 'La cantidad de hijos debe estar entre 1 y 20.'];
        }

        $montos = $post['monto_hijo'] ?? [];
        $montos = is_array($montos) ? array_values($montos) : [$montos];

        if (count($montos) !== $cantidad) {
            return ['error' => "Debe indicar el monto de cada uno de los $cantidad hijos."];
        }

        $validos = [];
        $total = 0.0;

        foreach ($montos as $m) {
            $m = round((float) str_replace(',', '.', trim((string) $m)), 2);

            if (!in_array($m, MONTOS_HIJO_VALIDOS, true)) {
                return ['error' => 'Solo se permiten montos de 56,00 Bs. o 12,50 Bs. por hijo.'];
            }

            $validos[] = number_format($m, 2, '.', '');
            $total += $m;
        }

        return [
            'condicion'  => $cantidad . ($cantidad === 1 ? ' hijo: ' : ' hijos: ') . implode(', ', $validos),
            'porcentaje' => 0.0,
            'monto'      => round($total, 2),
        ];
    }

    return ['error' => 'Debe seleccionar un tipo de prima válido.'];
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
   REGISTRAR / ACTUALIZAR
--------------------------------------------------------------------------- */

if (!in_array($accion, ['guardar', 'actualizar'], true)) {
    $_SESSION['errores'] = ['La acción solicitada no es válida.'];
    irLista();
}

$editando = $accion === 'actualizar';
$idPrima = $editando ? (int) filter_input(INPUT_POST, 'id_prima', FILTER_VALIDATE_INT) : 0;
$idTrabajador = (int) filter_input(INPUT_POST, 'id_trabajador', FILTER_VALIDATE_INT);
$tipo = trim($_POST['tipo_prima'] ?? '');
$estatusPrima = trim((string) ($_POST['estatus'] ?? 'Activo'));
if (!in_array($estatusPrima, ['Activo', 'Inactivo'], true)) {
    fallo('El estatus de la prima no es válido.', (int) ($_POST['id_prima'] ?? 0));
}

if ($editando) {
    $actual = $idPrima > 0 ? $objprima->obtenerPorId($idPrima) : null;

    if (!$actual) {
        $_SESSION['errores'] = ['No se encontró la prima que intenta actualizar.'];
        irLista();
    }

    // En edición el trabajador no cambia: se toma el de la prima guardada.
    $idTrabajador = (int) $actual['id_trabajador'];
}

if ($idTrabajador <= 0) {
    fallo('Debe seleccionar un trabajador.', $idPrima);
}

$trab = $objprima->obtenerTrabajador($idTrabajador);

if ($trab === null) {
    fallo('El trabajador seleccionado no existe.', $idPrima);
}

// VALIDACIÓN DE SEGURIDAD: Los obreros no pueden recibir prima por profesionalización
$tipoPersonalTrabajador = strtolower(trim((string) ($trab['tipo_personal'] ?? '')));
if (strpos($tipoPersonalTrabajador, 'obrero') !== false && $tipo === TIPO_PROFESIONALIZACION) {
    fallo('Los trabajadores con estatus u ocupación de obrero no pueden recibir prima por profesionalización.', $idPrima);
}

if (!$editando && strtolower(trim((string) $trab['status'])) !== 'activo') {
    fallo('Solo se pueden registrar primas a trabajadores con estatus Activo.');
}

// Se permite utilizar el salario vigente del cargo cuando corresponda.
// Para las primas porcentuales también debe existir una base de cálculo positiva.
if ((float) ($trab['salario_mensual'] ?? 0) <= 0) {
    fallo('No existe un salario vigente aplicable al trabajador. Registre el sueldo base o el sueldo de su cargo en el módulo de salarios.', $idPrima);
}

if (in_array($tipo, [TIPO_ANTIGUEDAD, TIPO_PROFESIONALIZACION], true)
    && (float) ($trab['base_prima'] ?? 0) <= 0) {
    fallo('No existe una base salarial vigente para calcular esta prima. Revise el sueldo base o el salario asignado al cargo.', $idPrima);
}

$calculo = calcularPrima($trab, $tipo, $_POST);

if (isset($calculo['error'])) {
    fallo($calculo['error'], $idPrima);
}

if ($calculo['monto'] <= 0) {
    fallo('El monto de la prima debe ser mayor que cero.', $idPrima);
}

if ($objprima->existePrima($idTrabajador, $tipo, $idPrima)) {
    fallo('Este trabajador ya tiene registrada una "' . $tipo . '". Edite la existente.', $idPrima);
}

$montoTotal = round((float) $trab['salario_mensual'] + $calculo['monto'], 2);
$nombre = trim((string) $trab['nombre_completo']);

if ($editando) {
    $ok = $objprima->actualizar(
        $idPrima,
        $tipo,
        $calculo['condicion'],
        $calculo['porcentaje'],
        $calculo['monto'],
        $montoTotal,
        $estatusPrima
    );

    if ($ok) {
        registrarBitacora(
            $conexion,
            'Primas',
            'Editar',
            "Actualizó la \"$tipo\" del trabajador \"$nombre\": condición \"{$actual['condicion']}\" → " .
            "\"{$calculo['condicion']}\", monto Bs {$calculo['monto']}, estatus \"{$actual['estatus']}\" → \"{$estatusPrima}\"."
        );
        $_SESSION['exito'] = 'La prima se actualizó correctamente.';
        irLista();
    }

    fallo('No se pudo actualizar la prima. ' . ($objprima->ultimoError ?? ''), $idPrima);
}

$ok = $objprima->registrar(
    $idTrabajador,
    $tipo,
    $calculo['condicion'],
    $calculo['porcentaje'],
    $calculo['monto'],
    $montoTotal,
    $estatusPrima
);

if ($ok) {
    registrarBitacora(
        $conexion,
        'Primas',
        'Crear',
        "Registró la \"$tipo\" al trabajador \"$nombre\" con condición \"{$calculo['condicion']}\" " .
        "y un monto de Bs {$calculo['monto']}, estatus \"{$estatusPrima}\"."
    );
    $_SESSION['exito'] = 'La prima se registró correctamente.';
    irRegistro();
}

fallo('No se pudo registrar la prima. ' . ($objprima->ultimoError ?? ''));