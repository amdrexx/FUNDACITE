<?php
session_start();
include_once "includes/guardian.php";

require_once '../conexion.php';
require_once '../modelos/clase_constancia.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id_constancia = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($id_constancia <= 0) {
    die('Constancia no válida.');
}

$constanciaObj = new clase_constancia($conexion);
$constancia = $constanciaObj->obtenerPorId($id_constancia);

if (!$constancia) {
    die('No se encontró la constancia solicitada.');
}

$nombreTrabajador = trim(
    $constancia['apellidos'] . ' ' . $constancia['nombres']
);

$cedulaTrabajador = (string) ($constancia['cedula'] ?? '');
$fechaIngreso = $constancia['fecha_ingreso'] ?? '';
$cargo = $constancia['nombre_cargo'] ?? 'No registrado';
$tipoPersonal = $constancia['tipo_personal'] ?? '';
$salario = $constancia['salario_monto'] ?? 0;
$motivo = trim($constancia['motivo_solicitud'] ?? '');
$fechaConstancia = $constancia['fecha_constancia'] ?? date('Y-m-d');
$nombreDirector =
    $constancia['nombre_director_departamento']
    ?? 'MSc. KARLA Y. MONTANEZ O';

$cedulaDirector = '19.817.989';
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

$html = '
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
        <strong>V-' . e($cedulaDirector) . '</strong>,
        en mí carácter de Directora de Gestión Humana de la
        <strong>FUNDACIÓN PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGÍA
        (FUNDACITE YARACUY)</strong>,
        hago constar por medio de la presente que el ciudadano(a):
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
            Directora de Gestión Humana.
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