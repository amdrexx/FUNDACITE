<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function escaparPdf($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function fechaPdf($fecha): array
{
    $fecha = $fecha !== null ? (string) $fecha : null;

    if (!$fecha || $fecha === '0000-00-00') {
        return ['', '', ''];
    }

    $marcaTiempo = strtotime($fecha);
    if ($marcaTiempo === false) {
        return ['', '', ''];
    }

    return [date('d', $marcaTiempo), date('m', $marcaTiempo), date('Y', $marcaTiempo)];
}

function numeroDias($desde, $hasta): string
{
    $desde = $desde !== null ? (string) $desde : null;
    $hasta = $hasta !== null ? (string) $hasta : null;

    if (!$desde || !$hasta) {
        return '';
    }

    try {
        $inicio = new DateTimeImmutable($desde);
        $fin = new DateTimeImmutable($hasta);
        return (string) ($inicio->diff($fin)->days + 1);
    } catch (Exception) {
        return '';
    }
}

$idSolicitud = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idSolicitud || $idSolicitud <= 0) {
    http_response_code(400);
    exit('Debe indicar una solicitud de días de disfrute válida.');
}

$sql = "SELECT
            s.id_solicitud,
            s.codigo_solicitud,
            s.tipo_solicitud,
            s.motivo_solicitud,
            s.fecha_inicio,
            s.fecha_finalizacion,
            dv.nombre_cargo,
            dv.descripcion,
            dv.desde,
            dv.hasta,
            t.cedula,
            t.nombres,
            t.apellidos
        FROM SOLICITUD s
        INNER JOIN DISFRUTE_DE_VACACIONES dv ON dv.id_solicitud = s.id_solicitud
        INNER JOIN TRABAJADOR t ON t.id_trabajador = s.id_trabajador
        WHERE s.id_solicitud = ?
          AND s.tipo_solicitud = 'Vacaciones'
        LIMIT 1";

$consulta = $conexion->prepare($sql);
if (!$consulta) {
    http_response_code(500);
    exit('No se pudo preparar la consulta de la solicitud.');
}

$consulta->bind_param('i', $idSolicitud);
$consulta->execute();
$solicitud = $consulta->get_result()->fetch_assoc();
$consulta->close();

if (!$solicitud) {
    http_response_code(404);
    exit('No se encontró la solicitud de días de disfrute.');
}

registrarBitacora($conexion, 'Días de Disfrute', 'Generar PDF', "Generó el PDF de la solicitud de días de disfrute ID $idSolicitud.");

[$diaSolicitud, $mesSolicitud, $anoSolicitud] = fechaPdf($solicitud['fecha_inicio']);
[$diaInicio, $mesInicio, $anoInicio] = fechaPdf($solicitud['desde']);
[$diaRegreso, $mesRegreso, $anoRegreso] = fechaPdf($solicitud['hasta']);

$nombreTrabajador = trim($solicitud['apellidos'] . ' ' . $solicitud['nombres']);
$logoRuta = __DIR__ . '/../vistas/img/logo_doc.png';
$logo = is_readable($logoRuta)
    ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoRuta))
    : '';

try {
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Días de Disfrute - <?= escaparPdf($nombreTrabajador) ?></title>
    <style>
        @page { margin: 14px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: Arial, sans-serif; font-size: 8px; }
        .encabezado { height: 60px; position: relative; }
        .logo { position: absolute; top: 0; right: 5px; height: 42px; max-width: 190px; }
        h1 { margin: 0; padding-top: 30px; text-align: center; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid #555; padding: 3px; vertical-align: top; }
        .etiqueta { display: block; font-weight: bold; font-size: 8px; margin-bottom: 4px; }
        .valor { min-height: 17px; font-size: 9px; overflow-wrap: break-word; }
        .alto-50 { height: 58px; }
        .alto-42 { height: 50px; }
        .alto-76 { height: 88px; }
        .alto-84 { height: 96px; }
        .centro { text-align: center; }
        .seccion { padding: 4px; background: #e5e5e5; font-size: 9px; font-weight: bold; text-align: center; }
        .fecha-celda { padding: 0; text-align: center; }
        .fecha-titulo { display: block; padding: 4px 2px; border-bottom: 1px solid #555; font-weight: bold; }
        .fecha-partes { width: 100%; height: 30px; border-collapse: collapse; }
        .fecha-partes td { width: 33.33%; border: 0; border-right: 1px solid #555; padding: 3px 2px; }
        .fecha-partes td:last-child { border-right: 0; }
        .fecha-partes strong { display: block; font-size: 7px; font-weight: normal; }
        .cuadro { display: inline-block; width: 10px; height: 10px; margin: 0 3px 2px 0; border: 1px solid #555; vertical-align: middle; }
        .firma { height: 68px; }
        .pie { margin-top: 5px; color: #444; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <div class="encabezado">
        <?php if ($logo !== ''): ?><img class="logo" src="<?= $logo ?>" alt="Fundacite Yaracuy"><?php endif; ?>
        <h1>SOLICITUD DE DÍAS DE DISFRUTE</h1>
    </div>

    <table>
        <tr>
            <td class="alto-50" colspan="8"><span class="etiqueta">2. Apellido y Nombres</span><div class="valor"><?= escaparPdf($nombreTrabajador) ?></div></td>
            <td class="fecha-celda" colspan="2">
                <span class="fecha-titulo">1. Fecha</span>
                <table class="fecha-partes"><tr><td><strong>Día</strong><?= escaparPdf($diaSolicitud) ?></td><td><strong>Mes</strong><?= escaparPdf($mesSolicitud) ?></td><td><strong>Año</strong><?= escaparPdf($anoSolicitud) ?></td></tr></table>
            </td>
        </tr>
        <tr>
            <td class="alto-42" colspan="8"><span class="etiqueta">4. Motivo de los días de disfrute</span><div class="valor"><?= escaparPdf($solicitud['motivo_solicitud'] ?: $solicitud['descripcion']) ?></div></td>
            <td colspan="2"><span class="etiqueta">3. N° Cédula de identidad</span><div class="valor"><?= escaparPdf($solicitud['cedula']) ?></div></td>
        </tr>
        <tr>
            <td class="alto-42" colspan="8"><span class="etiqueta">6. Cargo del solicitante</span><div class="valor"><?= escaparPdf($solicitud['nombre_cargo']) ?></div></td>
            <td colspan="2"><span class="etiqueta">5. Código de solicitud</span><div class="valor"><?= escaparPdf($solicitud['codigo_solicitud']) ?></div></td>
        </tr>
        <tr>
            <td class="alto-76" colspan="2"><span class="etiqueta">7. Duración del disfrute</span><div class="valor">N° de días: <?= escaparPdf(numeroDias($solicitud['desde'], $solicitud['hasta'])) ?></div></td>
            <td class="fecha-celda" colspan="3"><span class="fecha-titulo">Iniciación</span><table class="fecha-partes"><tr><td><strong>Día</strong><?= escaparPdf($diaInicio) ?></td><td><strong>Mes</strong><?= escaparPdf($mesInicio) ?></td><td><strong>Año</strong><?= escaparPdf($anoInicio) ?></td></tr></table></td>
            <td class="fecha-celda" colspan="3"><span class="fecha-titulo">Regreso</span><table class="fecha-partes"><tr><td><strong>Día</strong><?= escaparPdf($diaRegreso) ?></td><td><strong>Mes</strong><?= escaparPdf($mesRegreso) ?></td><td><strong>Año</strong><?= escaparPdf($anoRegreso) ?></td></tr></table></td>
            <td><span class="etiqueta">8. Tipo de disfrute</span><div class="valor"><span class="cuadro"></span>Vacaciones</div></td>
            <td><span class="etiqueta">9. Firma del trabajador(a)</span><div class="firma"></div></td>
        </tr>
        <tr><td class="seccion" colspan="10">APROBACIÓN Y AUTORIZACIÓN (PARA SER LLENADO POR EL SUPERVISOR INMEDIATO)</td></tr>
        <tr>
            <td class="alto-76" colspan="2"><span class="etiqueta">10. Disfrute</span><div class="valor"><span class="cuadro"></span>Remunerado<br><span class="cuadro"></span>No remunerado</div></td>
            <td colspan="3"><span class="etiqueta">11. Se requiere suplente</span><div class="valor"><span class="cuadro"></span>Sí&nbsp;&nbsp;&nbsp; <span class="cuadro"></span>No<br><br>De ser afirmativo, favor anexar perfil requerido</div></td>
            <td colspan="5"><span class="etiqueta">12. Firma del supervisor inmediato</span><div class="firma"></div></td>
        </tr>
        <tr><td class="alto-84" colspan="10"><span class="etiqueta">13. Observaciones</span></td></tr>
        <tr><td class="seccion" colspan="10">COORDINACIÓN DE RECURSOS HUMANOS</td></tr>
        <tr>
            <td class="alto-50" colspan="3"><span class="etiqueta">14. Días disponibles</span></td>
            <td colspan="3"><span class="etiqueta">15. Recibido por</span></td>
            <td colspan="4"><span class="etiqueta">16. Procesado por</span></td>
        </tr>
    </table>
    <div class="pie">Solicitud N° <?= escaparPdf($solicitud['id_solicitud']) ?></div>
</body>
</html>
<?php
    $html = ob_get_clean();

    $opciones = new Options();
    $opciones->set('isHtml5ParserEnabled', true);
    $opciones->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($opciones);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $nombreArchivo = 'Solicitud de ' . $nombreTrabajador . '.pdf';
    // Se eliminan caracteres no permitidos en nombres de archivo en la
    // mayoría de sistemas operativos, conservando espacios y acentos.
    $nombreArchivo = preg_replace('/[\/\\\\:*?"<>|]/', '', $nombreArchivo);

    $dompdf->stream(
        $nombreArchivo,
        ['Attachment' => false]
    );
} catch (\Throwable $e) {
    // Si algo falla en cualquier punto (armado del HTML o Dompdf), se
    // descarta cualquier buffer de salida pendiente (para que nunca se
    // muestre "a medias" como si fuera una página web) y se informa el
    // error real ocurrido, en vez de dejar que el navegador reciba HTML
    // sin convertir o una página cortada a mitad.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "No se pudo generar el PDF de la solicitud de días de disfrute.\n\n";
    echo 'Detalle técnico: ' . $e->getMessage() . "\n";
    echo 'Archivo: ' . $e->getFile() . ' (línea ' . $e->getLine() . ')';
    exit;
}