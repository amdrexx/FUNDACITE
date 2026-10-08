<?php
// ============================================================================
// ARCHIVO: pdf/plantilla_reporte.php
// DESCRIPCIÓN: Plantilla institucional compartida para los reportes PDF en
//              formato de listado (trabajadores, usuarios y solicitudes).
//              Cada generador concreto consulta su información y llama a
//              generarReportePdf() con el título, las columnas y las filas.
// ============================================================================

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../vendor/autoload.php';

// Los reportes se emiten siempre con la hora de Venezuela, sin importar
// la zona horaria del servidor.
date_default_timezone_set('America/Caracas');

if (!function_exists('reporteImagenBase64')) {

    /**
     * Convierte una imagen local a una data URI base64.
     * Igual criterio que constancia_trabajo.php: evita el bloqueo silencioso
     * de imágenes de dompdf por el chroot.
     */
    function reporteImagenBase64(string $ruta): ?string
    {
        if (!is_file($ruta) || !is_readable($ruta)) {
            return null;
        }

        $tipo = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = match ($tipo) {
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'svg'         => 'image/svg+xml',
            'webp'        => 'image/webp',
            default       => 'application/octet-stream',
        };

        $contenido = file_get_contents($ruta);
        if ($contenido === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }

    /**
     * Escapa un valor para insertarlo en el HTML del PDF.
     */
    function reporteEscape($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Formatea una fecha Y-m-d a dd/mm/yyyy en huso horario de Venezuela;
     * vacía si no es una fecha válida.
     */
    function reporteFecha($fecha): string
    {
        $fecha = trim((string) $fecha);

        if ($fecha === '' || $fecha === '0000-00-00') {
            return '';
        }

        $marcaTiempo = strtotime($fecha);

        if ($marcaTiempo === false) {
            return '';
        }

        return date('d/m/Y', $marcaTiempo);
    }

    /**
     * Fecha y hora actual de Venezuela (ej: "08/10/2026 03:47 pm"),
     * para la línea "Generado por" de los reportes.
     */
    function reporteFechaHoraVenezuela(): string
    {
        return (new DateTime('now', new DateTimeZone('America/Caracas')))
            ->format('d/m/Y h:i a');
    }

    /**
     * Genera el PDF de un listado con el formato institucional y lo envía
     * al navegador (Attachment=false, se abre en pestaña; target=_blank).
     *
     * @param string   $titulo        Ej: "REPORTE DE TRABAJADORES"
     * @param string[] $columnas      Encabezados de la tabla (N° se agrega solo).
     * @param array    $filas         Arreglo de arreglos (una celda por columna).
     * @param string   $nombreArchivo Nombre del archivo PDF.
     * @param string   $orientacion   'portrait' (por defecto) o 'landscape'.
     */
    function generarReportePdf(string $titulo, array $columnas, array $filas, string $nombreArchivo, string $orientacion = 'portrait'): void
    {
        $logoMinisterio = reporteImagenBase64(__DIR__ . '/../vistas/img/logo_ministerio.png');
        $logoFundacite  = reporteImagenBase64(__DIR__ . '/../vistas/img/logo_doc.png');
       

        $logoMinisterioHtml = $logoMinisterio !== null
            ? '<img src="' . $logoMinisterio . '" alt="Ministerio del Poder Popular para Ciencia y Tecnología">'
            : '<div class="membrete-texto">MINISTERIO DEL PODER POPULAR<br>PARA CIENCIA Y TECNOLOGÍA</div>';

        $logoFundaciteHtml = $logoFundacite !== null
            ? '<img src="' . $logoFundacite . '" alt="Fundacite Yaracuy">'
            : '<div class="membrete-texto">FUNDACITE<br>YARACUY</div>';

       

        $generadoPor = $_SESSION['usuario'] ?? 'Sistema';
        $fechaGenerado = reporteFechaHoraVenezuela();
        $total = count($filas);

        try {
            ob_start();
            ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo reporteEscape($titulo); ?></title>
<style>
    @page {
        margin: 58px 44px 78px 44px;
    }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9pt;
        color: #111;
        margin: 0;
    }
    .encabezado {
        width: 100%;
        border-bottom: 2px solid #093e61;
        padding-bottom: 8px;
    }
    .encabezado table {
        width: 100%;
        border-collapse: collapse;
    }
    .encabezado td {
        vertical-align: middle;
    }
    .encabezado img {
        max-height: 46px;
    }
    .logo-izq {
        width: 30%;
        text-align: left;
    }
    .logo-cen {
        width: 40%;
    }
    .logo-der {
        width: 30%;
        text-align: right;
    }
    .membrete {
        text-align: center;
        font-size: 7.8pt;
        font-weight: bold;
        line-height: 1.4;
        color: #093e61;
    }
    .membrete-texto {
        font-size: 8.5pt;
        font-weight: bold;
        color: #093e61;
        line-height: 1.3;
    }
    .titulo-reporte {
        text-align: center;
        font-size: 13pt;
        font-weight: bold;
        letter-spacing: 1px;
        color: #093e61;
        margin: 16px 0 6px;
    }
    .meta {
        font-size: 8.5pt;
        color: #333;
        margin: 0 0 10px;
    }
    .tabla-reporte {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .tabla-reporte th {
        background: #0F4C75;
        color: #fff;
        border: 1px solid #0F4C75;
        padding: 5px;
        font-size: 8.5pt;
        text-align: left;
    }
    .tabla-reporte th.numero {
        width: 26px;
        text-align: center;
    }
    .tabla-reporte td {
        border: 1px solid #6b7280;
        padding: 4px 5px;
        font-size: 8.5pt;
        overflow-wrap: break-word;
    }
    .tabla-reporte td.numero {
        text-align: center;
    }
    .tabla-reporte tbody tr:nth-child(odd) td {
        background: #eef3f8;
    }
    .vacia td {
        text-align: center;
        color: #555;
        font-style: italic;
        padding: 12px 5px;
    }
    .pie-texto {
        position: fixed;
        bottom: 40px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 7.4pt;
        line-height: 1.3;
        color: #333;
    }
    
</style>
</head>

<body>

    <div class="encabezado">
        <table>
            <tr>
                <td class="logo-izq">
                    <?php echo $logoMinisterioHtml; ?>
                </td>
                <td class="logo-cen">
                    <div class="membrete">
                        REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
                        FUNDACIÓN PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGÍA<br>
                        FUNDACITE YARACUY - RIF: G-20009933-0
                    </div>
                </td>
                <td class="logo-der">
                    <?php echo $logoFundaciteHtml; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="titulo-reporte">
        <?php echo reporteEscape($titulo); ?>
    </div>

    <p class="meta">
        Generado por: <strong><?php echo reporteEscape($generadoPor); ?></strong>
        — San Felipe, <?php echo reporteEscape($fechaGenerado); ?>.
        Total de registros: <strong><?php echo $total; ?></strong>
    </p>

    <table class="tabla-reporte">

        <thead>

            <tr>

                <th class="numero">N°</th>

                <?php foreach ($columnas as $columna) { ?>

                    <th><?php echo reporteEscape($columna); ?></th>

                <?php } ?>

            </tr>

        </thead>

        <tbody>

            <?php if ($total > 0) { ?>

                <?php foreach ($filas as $i => $fila) { ?>

                    <tr>

                        <td class="numero"><?php echo $i + 1; ?></td>

                        <?php foreach ($columnas as $indice => $columna) { ?>

                            <td><?php echo reporteEscape($fila[$indice] ?? ''); ?></td>

                        <?php } ?>

                    </tr>

                <?php } ?>

            <?php } else { ?>

                <tr class="vacia">

                    <td colspan="<?php echo count($columnas) + 1; ?>">

                        No hay registros para mostrar en este reporte.

                    </td>

                </tr>

            <?php } ?>

        </tbody>

    </table>

    <div class="pie-texto">
        Zona Industrial "Agustín Rivero", Calle 1-A con Avenida 4,
        Edificio Fundacite Yaracuy, Independencia, Estado Yaracuy.
        Teléfonos 0424-5447057 / 0254-2315616
    </div>

    <?php if ($banderaHtml !== '') { ?>

        <div class="pie-bandera">
            <?php echo $banderaHtml; ?>
        </div>

    <?php } ?>

</body>
</html>
<?php
            $html = ob_get_clean();

            $opciones = new Options();
            $opciones->set('defaultFont', 'DejaVu Sans');
            $opciones->setChroot(realpath(__DIR__ . '/..'));

            $dompdf = new Dompdf($opciones);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('letter', $orientacion);
            $dompdf->render();

            $nombreArchivo = preg_replace('/[^0-9A-Za-z_.-]/', '', $nombreArchivo);

            $dompdf->stream($nombreArchivo, ['Attachment' => false]);
            exit;
        } catch (\Throwable $e) {
            // Mismo criterio de solicitud_dias_disfrute.php: nunca se muestra
            // HTML a medias; se informa el error real como texto plano.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "No se pudo generar el reporte en PDF.\n\n";
            echo 'Detalle técnico: ' . $e->getMessage() . "\n";
            echo 'Archivo: ' . $e->getFile() . ' (línea ' . $e->getLine() . ')';
            exit;
        }
    }
}
