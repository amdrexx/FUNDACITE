<?php
session_start();
require_once __DIR__ . '/../vistas/includes/guardian.php';

// Solo Director o Administrador pueden emitir/imprimir la constancia.
requireAdministradorODirector();

require_once '../conexion.php';
require_once '../modelos/clase_constancia.php';
require_once '../vendor/autoload.php';
require_once '../controladores/helpers/bitacora_helper.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id_constancia = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($id_constancia <= 0) {
    die('Constancia no válida.');
}

$constanciaObj = new ConstanciaTrabajo($conexion);
$constancia = $constanciaObj->obtenerPorId($id_constancia);

if (!$constancia) {
    die('No se encontró la constancia solicitada.');
}

$directorActivo = $constanciaObj->obtenerDirectorActivo();
if (!$directorActivo) {
    http_response_code(409);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Director activo requerido</title><link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">'
        . '<style>body{min-height:100vh;display:grid;place-items:center;background:#102f45;color:#fff;font-family:Arial,sans-serif}.alerta{width:min(440px,90vw);padding:28px;border-radius:14px;background:linear-gradient(145deg,#15415f,#1B262C);border:1px solid rgba(255,255,255,.2);border-left:5px solid #e0a800;box-shadow:0 14px 38px rgba(0,0,0,.35)}.alerta h1{font-size:20px;margin:0 0 12px}.alerta p{line-height:1.5;margin:0 0 20px}.alerta a{display:inline-block;padding:10px 18px;border-radius:8px;background:#3282B8;color:#fff;text-decoration:none;font-weight:600}</style>'
        . '</head><body><section class="alerta" role="alert"><h1>Se requiere un director activo</h1>'
        . '<p>No hay ningún trabajador activo con un cargo de Director. Registre o active un director antes de generar la constancia.</p>'
        . '<a href="/FUNDACITE/vistas/lista_trabajadores.php">Ir al módulo de trabajadores</a></section></body></html>';
    exit;
}

registrarBitacora($conexion, 'Constancias', 'Generar PDF', "Generó el PDF de la constancia ID $id_constancia.");

$nombreTrabajador = trim(
    $constancia['apellidos'] . ' ' . $constancia['nombres']
);

$cedulaTrabajador = (string) ($constancia['cedula'] ?? '');
$fechaIngreso = $constancia['fecha_primer_contrato'] ?? '';
$cargo = $constancia['nombre_cargo'] ?? 'No registrado';
$tipoPersonal = $constancia['tipo_personal'] ?? '';
$salario = $constancia['salario_monto'] ?? 0;
$motivo = trim($constancia['motivo'] ?? '');
$tipoConstancia = in_array(($constancia['tipo_constancia'] ?? 'normal'), ['normal', 'primas_detalladas'], true) ? $constancia['tipo_constancia'] : 'normal';
$primasDetalladas = $constanciaObj->listarPrimasActivas((int)($constancia['id_trabajador'] ?? 0));
$totalDevengado = (float)($constancia['total_devengado'] ?? $salario);
$alimentacion = 400000.00; // Beneficio ilustrado en el formato de referencia; no forma parte del total asignado.
$fechaConstancia = $constancia['fecha_constancia'] ?? date('Y-m-d');
$nombreDirector = trim(($directorActivo['apellidos'] ?? '') . ' ' . ($directorActivo['nombres'] ?? ''));

// La firma debe corresponder a la persona activa que ocupa el cargo de Director.
$generoFirmante = $directorActivo['genero'] ?? null;
$prefijo = match ($directorActivo['tipo_documento'] ?? '') {
    'Cédula de Extranjería' => 'E-',
    'Pasaporte'             => 'P-',
    default                 => 'V-',
};
$soloDigitosDirector = preg_replace('/\D/', '', (string) ($directorActivo['cedula'] ?? ''));
$cedulaDirector = $prefijo . ($soloDigitosDirector !== ''
    ? number_format((int) $soloDigitosDirector, 0, '', '.')
    : (string) ($directorActivo['cedula'] ?? ''));

/**
 * Devuelve la forma gramatical según el género registrado.
 * Si el género no está registrado se usa la forma neutra "(a)",
 * igual que el resto del sistema (p. ej. "ciudadano(a)").
 */
function porGenero(?string $genero, string $masc, string $fem, string $neutro): string
{
    return match ($genero) {
        'Masculino' => $masc,
        'Femenino'  => $fem,
        default     => $neutro,
    };
}

// Cargo del firmante (quien imprime) y del trabajador, según su género.
$tituloFirmante = porGenero($generoFirmante, 'Director', 'Directora', 'Director(a)');
$cargoFirmante  = $tituloFirmante . ' de Gestión Humana';

$generoTrabajador = $constancia['genero'] ?? null;
$ciudadanoTexto   = porGenero($generoTrabajador, 'el ciudadano', 'la ciudadana', 'el ciudadano(a)');

$rif = 'G20009933-0';

$logoMinisterioPath = __DIR__ . '/../vistas/img/logo_ministerio.png';
$logoFundacitePath  = __DIR__ . '/../vistas/img/logo_doc.png';
// Imagen de la bandera que reemplaza las franjas CSS del pie de página.
$banderaPath        = __DIR__ . '/../vistas/img/bandera.png';

/**
 * Convierte una imagen local a una URI base64 (data URI).
 *
 * Esto resuelve el problema típico de dompdf con las imágenes locales:
 * dompdf::setChroot() restringe el acceso a archivos fuera de la carpeta
 * indicada, y si el logo vive en otra carpeta (p. ej. ../vistas/img)
 * fuera del chroot de este script (../controladores), la imagen se
 * bloquea silenciosamente y no aparece en el PDF.
 *
 * Incrustando la imagen como base64 directamente en el HTML se evita
 * por completo ese problema: dompdf ya no necesita leer el archivo del
 * disco ni resolver rutas, ni se requiere isRemoteEnabled para esto.
 */
function imagenABase64(string $ruta): ?string
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
 * Obtiene el ancho y alto reales (en píxeles) de una imagen local.
 *
 * Se usa para fijar explícitamente el "width" y "height" del <img>
 * de la bandera con las proporciones originales del archivo, tal como
 * se ve en la vista previa/captura, evitando que dompdf la estire o
 * la deforme al forzar un 100% de ancho con alto automático.
 *
 * @return array{0:int,1:int} [ancho, alto] en píxeles. [0, 0] si falla.
 */
function dimensionesImagen(string $ruta): array
{
    if (!is_file($ruta) || !is_readable($ruta)) {
        return [0, 0];
    }

    $info = @getimagesize($ruta);

    if ($info === false) {
        return [0, 0];
    }

    return [(int) $info[0], (int) $info[1]];
}

$logoMinisterioData = imagenABase64($logoMinisterioPath);
$logoFundaciteData  = imagenABase64($logoFundacitePath);
$banderaData        = imagenABase64($banderaPath);

$logoMinisterioHtml = $logoMinisterioData
    ? '<img src="' . $logoMinisterioData . '" alt="Ministerio del Poder Popular para Ciencia y Tecnología">'
    : '<div class="logo-texto">MINISTERIO DEL PODER POPULAR<br>PARA CIENCIA Y TECNOLOGÍA</div>';

$logoFundaciteHtml = $logoFundaciteData
    ? '<img src="' . $logoFundaciteData . '" alt="Fundacite Yaracuy">'
    : '<div class="logo-texto logo-texto-der">FUNDACITE<br>YARACUY</div>';

// Ancho/alto reales de la bandera para respetar su proporción exacta
// (igual que en la captura), en vez de forzarla con width:100% + height:auto.
[$banderaAncho, $banderaAlto] = dimensionesImagen($banderaPath);

$banderaAtributos = '';
if ($banderaAncho > 0 && $banderaAlto > 0) {
    $banderaAtributos = ' width="' . $banderaAncho . '" height="' . $banderaAlto . '"';
}

// Si no se encuentra el archivo de la bandera, se hace un respaldo
// dibujando las franjas de colores originales, para que el documento
// nunca quede sin pie de página.
$banderaHtml = $banderaData
    ? '<img src="' . $banderaData . '" alt="Bandera"' . $banderaAtributos . '>'
    : '<div class="franja-amarilla"></div><div class="franja-azul"></div><div class="franja-roja"></div>';

function fechaConstanciaLarga($fecha)
{
    if (empty($fecha)) {
        return '';
    }

    $timestamp = strtotime($fecha);

    // Validación para evitar TypeError en date() si la fecha es inválida
    if ($timestamp === false) {
        return '';
    }

    $meses = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    $dia = date('d', $timestamp);
    $mes = $meses[(int) date('m', $timestamp)];
    $anio = date('Y', $timestamp);

    return $dia . ' días del mes de ' . $mes . ' del año ' . $anio;
}

function fechaCorta($fecha)
{
    if (empty($fecha)) {
        return 'No registrada';
    }

    $timestamp = strtotime($fecha);
    return $timestamp !== false ? date('d/m/Y', $timestamp) : 'No registrada';
}

function numeroALetras($numero)
{
    $numero = (float) $numero;
    // Casteo a int para usar como índice de arreglo sin advertencias
    $entero = (int) floor($numero);
    $decimales = (int) round(($numero - $entero) * 100);

    $unidades = [
        0 => 'cero', 1 => 'uno', 2 => 'dos', 3 => 'tres', 4 => 'cuatro', 5 => 'cinco',
        6 => 'seis', 7 => 'siete', 8 => 'ocho', 9 => 'nueve', 10 => 'diez', 11 => 'once',
        12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince', 16 => 'dieciséis',
        17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve', 20 => 'veinte',
        21 => 'veintiuno', 22 => 'veintidós', 23 => 'veintitrés', 24 => 'veinticuatro',
        25 => 'veinticinco', 26 => 'veintiséis', 27 => 'veintisiete', 28 => 'veintiocho',
        29 => 'veintinueve', 30 => 'treinta', 40 => 'cuarenta', 50 => 'cincuenta',
        60 => 'sesenta', 70 => 'setenta', 80 => 'ochenta', 90 => 'noventa'
    ];

    $centenas = [
        100 => 'cien', 200 => 'doscientos', 300 => 'trescientos', 400 => 'cuatrocientos',
        500 => 'quinientos', 600 => 'seiscientos', 700 => 'setecientos', 800 => 'ochocientos',
        900 => 'novecientos'
    ];

    if ($entero <= 29) {
        $texto = $unidades[$entero];
    } elseif ($entero < 100) {

        $decena = (int) (floor($entero / 10) * 10);
        $unidad = $entero % 10;

        $texto = $unidades[$decena];

        if ($unidad > 0) {
            $texto .= ' y ' . $unidades[$unidad];
        }

    } elseif ($entero < 1000) {

        $centena = (int) (floor($entero / 100) * 100);
        $resto = $entero % 100;

        if ($resto > 0 && $centena === 100) {
            $texto = 'ciento';
        } else {
            $texto = $centenas[$centena];
        }

        if ($resto > 0) {
            if ($resto <= 29) {
                $texto .= ' ' . $unidades[$resto];
            } else {
                $decena = (int) (floor($resto / 10) * 10);
                $unidad = $resto % 10;

                $texto .= ' ' . $unidades[$decena];

                if ($unidad > 0) {
                    $texto .= ' y ' . $unidades[$unidad];
                }
            }
        }

    } elseif ($entero < 1000000) {

        $miles = (int) floor($entero / 1000);
        $resto = $entero % 1000;

        if ($miles === 1) {
            $texto = 'mil';
        } else {
            $texto = numeroALetras($miles) . ' mil';
        }

        if ($resto > 0) {
            $texto .= ' ' . numeroALetras($resto);
        }

    } else {

        $millones = (int) floor($entero / 1000000);
        $resto = $entero % 1000000;

        if ($millones === 1) {
            $texto = 'un millón';
        } else {
            $texto = numeroALetras($millones) . ' millones';
        }

        if ($resto > 0) {
            $texto .= ' ' . numeroALetras($resto);
        }
    }

    $texto = mb_strtoupper($texto, 'UTF-8');

    if ($decimales > 0) {
        $texto .= ' CON ' . str_pad((string) $decimales, 2, '0', STR_PAD_LEFT);
        $texto .= ' CÉNTIMOS';
    }

    return $texto;
}

$salarioNumero = number_format(
    (float) $salario,
    2,
    ',',
    '.'
);

$salarioLetras = numeroALetras($salario);

function e($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

$filasPrimasDetalladas = '<tr><td>Salario aplicado (' . e($constancia['origen_salario'] ?? 'salario vigente') . ')</td><td style="text-align:right">' . e(number_format((float)$salario, 2, ',', '.')) . ' Bs.</td></tr>';
foreach ($primasDetalladas as $prima) {
    $etiquetaPrima = trim((string)($prima['tipo_prima'] ?? 'Prima'));
    if (trim((string)($prima['condicion'] ?? '')) !== '') $etiquetaPrima .= ' - ' . trim((string)$prima['condicion']);
    $filasPrimasDetalladas .= '<tr><td>' . e($etiquetaPrima) . '</td><td style="text-align:right">' . e(number_format((float)($prima['monto'] ?? 0), 2, ',', '.')) . ' Bs.</td></tr>';
}
$filasPrimasDetalladas .= '<tr><th>Total mensual devengado</th><th style="text-align:right">' . e(number_format($totalDevengado, 2, ',', '.')) . ' Bs.</th></tr>';
$tablaPrimasDetalladasHtml = '<table style="width:100%;border-collapse:collapse;margin:14px 0 20px;font-size:10pt"><thead><tr><th style="text-align:left;border-bottom:1px solid #1b3d6d;padding:6px">Concepto de remuneración</th><th style="text-align:right;border-bottom:1px solid #1b3d6d;padding:6px">Mensual</th></tr></thead><tbody>' . $filasPrimasDetalladas . '</tbody></table>';

$htmlDetallada = '
<!DOCTYPE html>
<title>Constancia de Trabajo</title>
<html lang="es">

<head>
<meta charset="UTF-8">
<style>
@page {
    margin: 0px 65px 130px 65px;
}
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11pt;
    line-height: 1.45;
    color: #000;
}
.contenedor {
    width: 100%;
    margin-top: 18px;
}
.encabezado {
    width: 100%;
    border-bottom: 2px solid #1b3d6d;
    padding-bottom: 10px;
    margin-bottom: 22px;
}
.encabezado table {
    width: 100%;
    border-collapse: collapse;
}
.encabezado td {
    vertical-align: middle;
}
.encabezado .logo-izq {
    text-align: left;
    width: 45%;
}
.encabezado .logo-der {
    text-align: right;
    width: 55%;
}
.encabezado img {
    max-height: 55px;
}
.logo-texto {
    font-size: 9.5pt;
    font-weight: bold;
    line-height: 1.3;
    color: #1b3d6d;
}
.logo-texto-der {
    color: #7a1f2b;
}
.rif {
    font-size: 10pt;
    font-weight: bold;
    margin-bottom: 20px;
    text-align: right;
}
.titulo {
    text-align: center;
    font-size: 14pt;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 28px;
}
.parrafo {
    text-align: justify;
    margin-bottom: 16px;
}
.firma {
    margin-top: 55px;
    text-align: center;
    line-height: 1;
    font-weight: bold;

}
.linea-firma {
    width: 260px;
    border-top: 1px solid #000;
    margin: 0 auto 6px auto;
}
.director {
    font-weight: bold;
    text-align: center;
}
.cargo-firma {
    text-align: center;
    font-weight: bold;
    font-size: 9pt;
    
}
.gaceta-firma {
    text-align: center;
    font-size: 8.5pt;
    margin-top: 2px;
}
.pie-texto {
    position: fixed;
    bottom: 95px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 7.7pt;
    line-height: 1.25;
    color: #000;
}
.pie-franja {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    line-height: 0;
    text-align: center;
}
.pie-franja img {
    display: inline-block;
    max-width: 150%;
    max-height: 250px;
    padding: 0;
    margin: 0;
    transform: translateY(150px); /* baja un poco más */
}
</style>
</head>

<body>
 
<div class="contenedor">
    <div class="encabezado">
        <table>
            <tr>
                <td class="logo-izq">
                    ' . $logoMinisterioHtml . '
                </td>
                <td class="logo-der">
                    ' . $logoFundaciteHtml . '
                </td>
            </tr>
        </table>
    </div>
    <div class="rif">
        RIF: ' . e($rif) . '
    </div>
    <div class="titulo">
        CONSTANCIA DE TRABAJO
    </div>
    <div class="parrafo">
        Quien suscribe:
        <strong>' . e($nombreDirector) . '</strong>,
        Titular de la cédula de identidad
        <strong>' . e($cedulaDirector) . '</strong>,
        en mi carácter de ' . e($cargoFirmante) . ' de la
        <strong>FUNDACIÓN PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGÍA
        (FUNDACITE YARACUY)</strong>,
        hago constar por medio de la presente que ' . e($ciudadanoTexto) . ':
        <strong>' . e($nombreTrabajador) . '</strong>,
        Titular de la C.I.
        <strong>' . e($cedulaTrabajador) . '</strong>,
        presta sus servicios en esta Institución desde el
        <strong>' . e(fechaCorta($fechaIngreso)) . '</strong>,
        actualmente ocupando el cargo de
        <strong>' . e($cargo) . '</strong>
        en calidad de
        <strong>' . e($tipoPersonal) . '</strong>,
        devengando un Salario Mensual de
        <strong>' . e($salarioLetras) . ' (' . e($salarioNumero) . ' Bs.)</strong>
        y una Subvención de Alimentación que corresponde a lo aprobado
        por el Ejecutivo Nacional.
    </div>
    ' . $tablaPrimasDetalladasHtml . '
       <div class="parrafo">
        Constancia que se expide a solicitud de la parte interesada' . (!empty($motivo) ? ', ' . e($motivo) : '') . '
        en Independencia, a los
        <strong>' . e(fechaConstanciaLarga($fechaConstancia)) . '</strong>.
    </div>
    <div class="firma">
        <div class="linea-firma"></div>
        <div class="director">
            ' . e($nombreDirector) . '
        </div>
        <div class="cargo-firma">
            ' . e($cargoFirmante) . '.
        </div>
        <div class="cargo-firma">
               Providencia Administrativa FY-004 de fecha  15/09/2025
        </div>
        <div class="gaceta-firma">
            Gaceta Oficial N.º 43.254 de Fecha 12/11/2025
        </div>
    </div>
</div>
 
<div class="pie-texto">
    Providencia Administrativa FY-004 de fecha 15/09/2025<br>
    Zona Industrial "Agustín Rivero", Calle 1-A con Avenida 4,
    Edificio Fundacite Yaracuy, Municipio Independencia,
    Estado Yaracuy,
    Teléfonos 0424-5447057 (Activo) / 0254-2315616
</div>


<div class="pie-franja">
    ' . $banderaHtml . '
</div>

</body>
</html>
';

// El formato normal reproduce la distribución de la referencia: título espaciado,
// párrafo introductorio, conceptos mensuales, total de asignación y alimentación.
$filasConceptos = '<tr><td>SUELDO BÁSICO</td><td class="monto">' . e(number_format((float)$salario, 2, ',', '.')) . '</td></tr>';
foreach ($primasDetalladas as $prima) {
    $nombrePrima = mb_strtoupper(trim((string)($prima['tipo_prima'] ?? 'PRIMA')), 'UTF-8');
    if (!empty($prima['porcentaje']) && (float)$prima['porcentaje'] > 0) {
        $nombrePrima .= ' (' . rtrim(rtrim(number_format((float)$prima['porcentaje'], 2, ',', '.'), '0'), ',') . '%)';
    }
    $filasConceptos .= '<tr><td>' . e($nombrePrima) . '</td><td class="monto">' . e(number_format((float)($prima['monto'] ?? 0), 2, ',', '.')) . '</td></tr>';
}
$alimentacionTexto = numeroALetras($alimentacion);
$htmlNormal = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
@page{margin:24px 24px 28px}*{box-sizing:border-box}body{font-family:DejaVu Sans,sans-serif;background:#1d1d1b;color:#f8f8f8;font-size:11pt;line-height:1.18;margin:0;padding:0}.titulo{text-align:center;font-size:24pt;font-weight:bold;letter-spacing:7px;margin:0 0 35px;padding-top:0}.intro{text-align:justify;margin:0 0 35px}.conceptos{width:82%;margin:0 auto 18px;border-collapse:collapse}.conceptos th{font-weight:bold;text-align:center;padding:0 4px 3px}.conceptos td{padding:2px 4px;vertical-align:top}.conceptos .monto{text-align:right;white-space:nowrap;width:32%}.total td{font-weight:bold;padding-top:10px}.total .linea{border-top:1px solid #eee}.alimentacion{text-align:justify;margin:20px 0 34px}.cierre{text-align:justify;margin-top:0}
</style></head><body><h1 class="titulo">CONSTANCIA</h1><p class="intro">Quien suscribe, <strong>' . e($cargoFirmante) . '</strong> de la <strong>FUNDACIÓN PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGÍA (FUNDACITE YARACUY)</strong>, hace constar que ' . e($ciudadanoTexto) . ' <strong>' . e($nombreTrabajador) . '</strong>, titular de la cédula de identidad <strong>' . e($prefijo . $cedulaTrabajador) . '</strong>, presta sus servicios en este organismo desde el <strong>' . e(fechaCorta($fechaIngreso)) . '</strong>, en calidad de <strong>' . e($tipoPersonal) . '</strong>, con una remuneración mensual discriminada de la siguiente manera:</p><table class="conceptos"><thead><tr><th>CONCEPTOS</th><th>MENSUAL Bs.</th></tr></thead><tbody>' . $filasConceptos . '<tr class="total"><td>TOTAL ASIGNACIÓN</td><td class="monto linea">' . e(number_format($totalDevengado, 2, ',', '.')) . '</td></tr></tbody></table><p class="alimentacion">Percibe por beneficio de alimentación la cantidad de ' . e(mb_strtolower($alimentacionTexto, 'UTF-8')) . ' bolívares exactos (Bs. ' . e(number_format($alimentacion, 2, ',', '.')) . ') mensuales.</p><p class="cierre">Constancia que se expide a solicitud de la parte interesada' . (!empty($motivo) ? ', ' . e($motivo) : '') . ', en Independencia, a los ' . e(fechaConstanciaLarga($fechaConstancia)) . '.</p></body></html>';

$html = ($tipoConstancia === 'normal') ? $htmlNormal : $htmlDetallada;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
// Chroot ampliado a la raíz del proyecto (padre de "controladores") por si
// en algún punto se vuelve a referenciar una imagen por ruta de archivo
// en vez de base64; así no queda bloqueada por estar fuera del chroot.
$options->setChroot(realpath(__DIR__ . '/..'));

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$nombreArchivo =
    'Constancia_' .
    preg_replace('/[^0-9A-Za-z_-]/', '', $cedulaTrabajador) .
    '.pdf';

$dompdf->stream(
    $nombreArchivo,
    [
        'Attachment' => false
    ]
);
exit;
