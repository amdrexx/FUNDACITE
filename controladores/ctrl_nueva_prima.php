<?php
// =============================================================================
// CONTROLADOR: Nuevas primas  (guardar, actualizar, inactivar, activar y actualizar porcentajes)
// Las primas nuevas se guardan en la tabla PRIMA con id_trabajador = NULL.
// =============================================================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../vistas/includes/guardian.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_nueva_prima.php';
require_once __DIR__ . '/helpers/bitacora_helper.php';

$modelo = new NuevaPrima($conexion);

const RUTA_REGISTRO = '../vistas/registrar_primas_nuevo.php';
const RUTA_LISTA    = '../vistas/lista_nuevas_primas.php';

/* ---------------------------------------------------------------------------
   Auxiliares
--------------------------------------------------------------------------- */

function irA(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

/** Quita espacios sobrantes (inicio, fin y repetidos). */
function normalizarNombre(string $nombre): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $nombre));
}

/** Acepta coma o punto como separador decimal. */
function normalizarPorcentaje(string $valor): string
{
    return str_replace(',', '.', trim($valor));
}

function fechaLegible(string $fecha): string
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}

/** Devuelve la lista de errores de validación (vacía si todo está bien). */
function validarDatos(string $nombre, string $fecha, string $porcentaje): array
{
    $errores = [];

    // Nombre
    $largo = mb_strlen($nombre, 'UTF-8');
    if ($nombre === '') {
        $errores[] = 'Debe ingresar el nombre de la prima.';
    } elseif ($largo < 3 || $largo > 100) {
        $errores[] = 'El nombre de la prima debe tener entre 3 y 100 caracteres.';
    } elseif (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} .\-\/]*$/u', $nombre)) {
        $errores[] = 'El nombre solo puede contener letras, números, espacios, puntos, guiones y barras.';
    }

    // Fecha
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($fecha === '') {
        $errores[] = 'Debe seleccionar una fecha.';
    } elseif (!$d || $d->format('Y-m-d') !== $fecha) {
        $errores[] = 'La fecha no es válida.';
    } elseif ((int) $d->format('Y') < 2000) {
        $errores[] = 'La fecha no puede ser anterior al año 2000.';
    }

    // Porcentaje
    if ($porcentaje === '') {
        $errores[] = 'Debe ingresar el porcentaje.';
    } elseif (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $porcentaje) || (float) $porcentaje <= 0 || (float) $porcentaje > 100) {
        $errores[] = 'El porcentaje debe ser un número mayor que 0 y hasta 100 (máximo 2 decimales).';
    }

    return $errores;
}

/* ---------------------------------------------------------------------------
   Solo se aceptan peticiones POST
--------------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    irA(RUTA_LISTA);
}

$accion = trim($_POST['accion'] ?? '');

/* ---------------------------------------------------------------------------
   INACTIVAR / ACTIVAR
--------------------------------------------------------------------------- */

if (in_array($accion, ['inactivar', 'activar'], true)) {
    if (function_exists('esAdministradorODirector') && !esAdministradorODirector()) {
        $_SESSION['errores'] = ['No tiene permisos para cambiar el estatus de las primas.'];
        irA(RUTA_LISTA);
    }

    $id = (int) filter_input(INPUT_POST, 'id_nueva_prima', FILTER_VALIDATE_INT);
    $registro = $id > 0 ? $modelo->obtenerPorId($id) : null;

    if (!$registro) {
        $_SESSION['errores'] = ['No se encontró la prima seleccionada.'];
        irA(RUTA_LISTA);
    }

    $nuevo = $accion === 'inactivar' ? 'Inactivo' : 'Activo';

    if ($modelo->cambiarEstatus($id, $nuevo)) {
        registrarBitacora(
            $conexion,
            'Nuevas primas',
            'Editar',
            ($nuevo === 'Inactivo' ? 'Inactivó' : 'Activó') . ' la prima "' . $registro['nombre'] . '" (fecha ' . fechaLegible($registro['fecha']) . ').'
        );
        $_SESSION['exito'] = $nuevo === 'Inactivo'
            ? 'La prima se inactivó correctamente.'
            : 'La prima se activó correctamente.';
    } else {
        $_SESSION['errores'] = ['No se pudo cambiar el estatus de la prima.'];
    }

    irA(RUTA_LISTA);
}

/* ---------------------------------------------------------------------------
   ACTUALIZAR PORCENTAJES (una por una o todas a la vez)
--------------------------------------------------------------------------- */

if ($accion === 'actualizar_porcentajes') {
    $destinoPct = '../vistas/actualizar_porcentaje_prima.php';

    if (function_exists('esAdministradorODirector') && !esAdministradorODirector()) {
        $_SESSION['errores'] = ['No tiene permisos para actualizar los porcentajes de las primas.'];
        irA(RUTA_LISTA);
    }

    // Solo se pueden modificar las primas activas del catálogo
    $activas = [];
    foreach ($modelo->listar() as $fila) {
        if ($fila['estatus'] === 'Activo') {
            $activas[(int) $fila['id_nueva_prima']] = $fila;
        }
    }

    $fmt = static fn(float $n): string => number_format($n, 2, ',', '.');
    $modo = (string) ($_POST['modo'] ?? 'individual');
    $cambios = [];
    $errores = [];

    if ($modo === 'todas') {
        $operacion = (string) ($_POST['operacion'] ?? '');
        $valorTxt = normalizarPorcentaje((string) ($_POST['valor'] ?? ''));

        if (!in_array($operacion, ['establecer', 'subir', 'bajar'], true)) {
            $errores[] = 'Seleccione qué desea hacer con el porcentaje (establecer, subir o bajar).';
        }

        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $valorTxt) || (float) $valorTxt <= 0 || (float) $valorTxt > 100) {
            $errores[] = 'El valor debe ser un número mayor que 0 y hasta 100 (máximo 2 decimales).';
        }

        $seleccion = $_POST['seleccion'] ?? [];
        $seleccion = is_array($seleccion) ? array_unique(array_map('intval', $seleccion)) : [];
        $seleccion = array_values(array_filter($seleccion, static fn($i) => isset($activas[$i])));

        if (!$seleccion) {
            $errores[] = 'Seleccione al menos una prima.';
        }

        if (!$errores) {
            $valor = round((float) $valorTxt, 2);

            foreach ($seleccion as $idPrima) {
                $actualPct = (float) $activas[$idPrima]['porcentaje'];
                $nuevoPct = $operacion === 'establecer'
                    ? $valor
                    : ($operacion === 'subir' ? $actualPct + $valor : $actualPct - $valor);
                $nuevoPct = round($nuevoPct, 2);

                if ($nuevoPct <= 0 || $nuevoPct > 100) {
                    $errores[] = 'La prima "' . $activas[$idPrima]['nombre'] . '" quedaría en ' . $fmt($nuevoPct) .
                                 '%. El porcentaje debe estar entre 0,01 y 100.';
                    continue;
                }

                if (abs($nuevoPct - $actualPct) >= 0.005) {
                    $cambios[$idPrima] = $nuevoPct;
                }
            }
        }
    } else {
        $nuevos = $_POST['nuevo'] ?? [];
        $nuevos = is_array($nuevos) ? $nuevos : [];

        foreach ($activas as $idPrima => $fila) {
            $txt = normalizarPorcentaje((string) ($nuevos[$idPrima] ?? ''));

            if ($txt === '') {
                continue;
            }

            if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $txt) || (float) $txt <= 0 || (float) $txt > 100) {
                $errores[] = 'La prima "' . $fila['nombre'] . '": el porcentaje debe ser mayor que 0 y hasta 100 (máximo 2 decimales).';
                continue;
            }

            $nuevoPct = round((float) $txt, 2);

            if (abs($nuevoPct - (float) $fila['porcentaje']) >= 0.005) {
                $cambios[$idPrima] = $nuevoPct;
            }
        }
    }

    if (!$errores && !$cambios) {
        $errores[] = 'No hay cambios que guardar: los porcentajes indicados son iguales a los actuales.';
    }

    if ($errores) {
        $_SESSION['errores'] = $errores;
        irA($destinoPct);
    }

    if ($modelo->actualizarPorcentajes($cambios)) {
        foreach ($cambios as $idPrima => $nuevoPct) {
            registrarBitacora(
                $conexion,
                'Nuevas primas',
                'Editar',
                'Actualizó el porcentaje de la prima "' . $activas[$idPrima]['nombre'] . '": ' .
                $fmt((float) $activas[$idPrima]['porcentaje']) . '% → ' . $fmt($nuevoPct) . '%.'
            );
        }

        $_SESSION['exito'] = count($cambios) === 1
            ? 'Se actualizó el porcentaje de 1 prima.'
            : 'Se actualizó el porcentaje de ' . count($cambios) . ' primas.';
        irA(RUTA_LISTA);
    }

    $_SESSION['errores'] = ['No se pudo actualizar los porcentajes (no se guardó ningún cambio). ' . ($modelo->ultimoError ?? '')];
    irA($destinoPct);
}

/* ---------------------------------------------------------------------------
   GUARDAR / ACTUALIZAR
--------------------------------------------------------------------------- */

if (!in_array($accion, ['guardar', 'actualizar'], true)) {
    $_SESSION['errores'] = ['La acción solicitada no es válida.'];
    irA(RUTA_LISTA);
}

$editando   = $accion === 'actualizar';
$id         = $editando ? (int) filter_input(INPUT_POST, 'id_nueva_prima', FILTER_VALIDATE_INT) : 0;
$nombre     = normalizarNombre((string) ($_POST['nombre'] ?? ''));

// Si escribe una de las primas clásicas con otras mayúsculas/minúsculas, se guarda con el nombre exacto
foreach (['Prima por antigüedad', 'Prima por hijos', 'Prima por profesionalización'] as $clasica) {
    if (mb_strtolower($nombre, 'UTF-8') === mb_strtolower($clasica, 'UTF-8')) {
        $nombre = $clasica;
    }
}
$fecha      = trim((string) ($_POST['fecha'] ?? ''));
$porcentaje = normalizarPorcentaje((string) ($_POST['porcentaje'] ?? ''));

$destinoError = RUTA_REGISTRO . ($editando ? '?editar=' . $id : '');

function volverConErrores(array $errores, string $destino, string $nombre, string $fecha, string $porcentaje): void
{
    $_SESSION['errores'] = $errores;
    $_SESSION['old'] = ['nombre' => $nombre, 'fecha' => $fecha, 'porcentaje' => $porcentaje];
    irA($destino);
}

if ($editando) {
    $actual = $id > 0 ? $modelo->obtenerPorId($id) : null;

    if (!$actual) {
        $_SESSION['errores'] = ['No se encontró la prima que intenta actualizar.'];
        irA(RUTA_LISTA);
    }
}

$errores = validarDatos($nombre, $fecha, $porcentaje);

// No permitir la misma prima dos veces en el mismo día
if (!$errores && $modelo->existeDuplicado($nombre, $fecha, $id)) {
    $errores[] = 'Ya existe la prima "' . $nombre . '" registrada con la fecha ' . fechaLegible($fecha) .
                 '. No se permite registrar la misma prima dos veces el mismo día.';
}

if ($errores) {
    volverConErrores($errores, $destinoError, $nombre, $fecha, $porcentaje);
}

$porcentajeNum = round((float) $porcentaje, 2);

/* ----- Registrar ----- */
if (!$editando) {
    $nuevoId = $modelo->registrar($nombre, $fecha, $porcentajeNum);

    if ($nuevoId > 0) {
        registrarBitacora(
            $conexion,
            'Nuevas primas',
            'Crear',
            "Registró la prima \"$nombre\" con fecha " . fechaLegible($fecha) . " y porcentaje $porcentajeNum%."
        );
        $_SESSION['exito'] = 'La prima se registró correctamente.';
        irA(RUTA_LISTA);
    }

    if ($modelo->esDuplicado) {
        volverConErrores(
            ['Ya existe la prima "' . $nombre . '" registrada con la fecha ' . fechaLegible($fecha) . '. No se guardó nada duplicado.'],
            $destinoError, $nombre, $fecha, $porcentaje
        );
    }

    volverConErrores(
        ['Ocurrió un error al registrar la prima. ' . ($modelo->ultimoError ?? '')],
        $destinoError, $nombre, $fecha, $porcentaje
    );
}

/* ----- Actualizar ----- */
if ($modelo->actualizar($id, $nombre, $fecha, $porcentajeNum)) {
    registrarBitacora(
        $conexion,
        'Nuevas primas',
        'Editar',
        "Actualizó la prima \"{$actual['nombre']}\": nombre \"{$actual['nombre']}\" → \"$nombre\", fecha " .
        fechaLegible($actual['fecha']) . ' → ' . fechaLegible($fecha) .
        ", porcentaje {$actual['porcentaje']}% → $porcentajeNum%."
    );
    $_SESSION['exito'] = 'La prima se actualizó correctamente.';
    irA(RUTA_LISTA);
}

if ($modelo->esDuplicado) {
    volverConErrores(
        ['Ya existe la prima "' . $nombre . '" registrada con la fecha ' . fechaLegible($fecha) . '. No se guardó nada duplicado.'],
        $destinoError, $nombre, $fecha, $porcentaje
    );
}

volverConErrores(
    ['No fue posible actualizar la prima. ' . ($modelo->ultimoError ?? '')],
    $destinoError, $nombre, $fecha, $porcentaje
);