<?php
// ============================================================================
// ARCHIVO: ajax/ajax_bitacora.php
// DESCRIPCIÓN: Endpoint JSON que alimenta la campana flotante de la bitácora
//              en el dashboard. ES EXCLUSIVO DEL ADMINISTRADOR: cualquier otro
//              rol (o sin sesión) recibe 401/403 sin ver ningún dato.
//
// ACCIONES (GET):
//   ?accion=poll    → cuántos movimientos sin leer hay y cuáles son los nuevos
//       visto_id    Último id que el administrador ya vio (opcional)
//       desde_id    Último id que el navegador ya recibió (opcional)
//   ?accion=listar  → página de registros para el panel
//       grupo       cambios | accesos | consultas (opcional)
//       q           Texto libre (opcional)
//       antes_id    Cursor: trae los anteriores a este id (opcional)
//
// NOTA: este endpoint NO llama a registrarBitacora(); de lo contrario cada
//       consulta de la campana generaría un registro nuevo y se alimentaría
//       a sí misma.
// ============================================================================

// Suprimimos errores para no contaminar el JSON de respuesta
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../vistas/includes/roles.php'; // inicia sesión + helpers de rol
require_once __DIR__ . '/../vistas/includes/sesion.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderJson(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// --- Control de acceso -------------------------------------------------------
if (!isset($_SESSION['id_usuario'])) {
    responderJson(401, ['ok' => false, 'error' => 'Sesión no válida.']);
}

// El sondeo es pasivo: NO renueva la actividad (si lo hiciera, la sesión del
// administrador no expiraría nunca), pero sí respeta la expiración.
if (sesionInactiva()) {
    responderJson(401, ['ok' => false, 'expirada' => true, 'error' => 'Sesión expirada por inactividad.']);
}

if (!esAdministrador()) {
    responderJson(403, ['ok' => false, 'error' => 'Acceso restringido al administrador.']);
}

$idUsuario = (int)$_SESSION['id_usuario'];

// Liberamos el bloqueo de sesión: el sondeo se ejecuta cada pocos segundos y,
// si no, retendría las demás peticiones del mismo usuario mientras responde.
session_write_close();

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_bitacora.php';

$bitacora = new Bitacora($conexion);

/** Lee un entero >= 0 de $_GET; devuelve $porDefecto si falta o no es válido. */
function enteroGet(string $clave, ?int $porDefecto): ?int
{
    $valor = $_GET[$clave] ?? null;

    return (is_string($valor) && ctype_digit($valor)) ? (int)$valor : $porDefecto;
}

/**
 * Prepara los registros para el navegador:
 *  - "propio": lo hizo el administrador que consulta (no cuenta como novedad).
 *  - "alerta": es de los tipos que hacen sonar la campana.
 * El id_usuario no se expone: el cliente no lo necesita.
 */
function prepararItems(array $items, int $idUsuario): array
{
    foreach ($items as &$it) {
        $it['propio'] = $it['id_usuario'] !== null && $it['id_usuario'] === $idUsuario;
        $it['alerta'] = in_array($it['accion'], Bitacora::ACCIONES_ALERTA, true);
        unset($it['id_usuario']);
    }
    unset($it);

    return $items;
}

$accion = $_GET['accion'] ?? '';

// ============================================================================
// SONDEO: contador de "sin leer" + movimientos nuevos para la notificación
// ============================================================================
if ($accion === 'poll') {

    $ultimoId = $bitacora->ultimoId();

    // Si el cliente aún no tiene referencia (primera vez) arrancamos "al día":
    // no inundamos la campana con todo el historial como si fuera nuevo.
    // min(): si la tabla se vació/reinició, el cursor del cliente no puede
    // quedar por delante del último id real.
    $vistoId = min(enteroGet('visto_id', $ultimoId), $ultimoId);
    $desdeId = min(enteroGet('desde_id', $ultimoId), $ultimoId);

    $nuevos      = [];
    $nuevosTotal = 0;

    if ($ultimoId > $desdeId) {
        $nuevosTotal = $bitacora->contarAlertas($desdeId, $idUsuario);
        $nuevos      = $nuevosTotal > 0
            ? prepararItems($bitacora->alertasDesde($desdeId, $idUsuario, 5), $idUsuario)
            : [];
    }

    responderJson(200, [
        'ok'           => true,
        'max_id'       => $ultimoId,
        'sin_leer'     => $bitacora->contarAlertas($vistoId, $idUsuario),
        'nuevos'       => $nuevos,
        'nuevos_total' => $nuevosTotal,
    ]);
}

// ============================================================================
// LISTADO: página de registros para el panel
// ============================================================================
if ($accion === 'listar') {

    $filtros = [
        'grupo' => (string)($_GET['grupo'] ?? ''),
        'q'     => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80),
    ];

    $pagina = $bitacora->listarPaginado($filtros, (int)enteroGet('antes_id', 0), 30);

    responderJson(200, [
        'ok'      => true,
        'items'   => prepararItems($pagina['items'], $idUsuario),
        'hay_mas' => $pagina['hay_mas'],
        'max_id'  => $bitacora->ultimoId(),
    ]);
}

responderJson(400, ['ok' => false, 'error' => 'Acción no válida.']);
