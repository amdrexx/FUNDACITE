<?php
declare(strict_types=1);

/**
 * =============================================================================
 *  REPORTE: CONTRATO INDIVIDUAL DE TRABAJO A TIEMPO DETERMINADO
 *  LA FUNDACIÓN PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGÍA
 *  EN EL ESTADO YARACUY (FUNDACITE YARACUY)
 * -----------------------------------------------------------------------------
 *  Genera el PDF a partir de DOMPDF, replicando el formato exacto del
 *  documento original, pero con todos los datos variables (número de
 *  contrato, datos del trabajador, cargo, fechas, salario, etc.)
 *  cargados automáticamente desde el sistema (base de datos).
 *
 *  Requiere: composer require dompdf/dompdf
 * =============================================================================
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelos/clase_contrato.php';
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/** Convierte una fecha de la base de datos a una fecha escrita en español. */
function fechaEnLetras(?string $fecha): string
{
    if (!$fecha || $fecha === '0000-00-00') {
        return 'no especificada';
    }

    $marcaTiempo = strtotime($fecha);
    if ($marcaTiempo === false) {
        return $fecha;
    }

    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    return (int) date('d', $marcaTiempo) . ' de '
        . $meses[(int) date('n', $marcaTiempo)] . ' de ' . date('Y', $marcaTiempo);
}

function nombreMes(int $mes): string
{
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    return $meses[$mes] ?? '';
}

/** Devuelve una duración legible para la cláusula de vigencia. */
function duracionContrato(string $inicio, ?string $fin): string
{
    if (!$fin || $fin === '0000-00-00') {
        return 'tiempo indeterminado';
    }

    try {
        $intervalo = (new DateTimeImmutable($inicio))->diff(new DateTimeImmutable($fin));
        $meses = ($intervalo->y * 12) + $intervalo->m;
        if ($intervalo->d > 0) {
            $meses++;
        }
        return (string) max(1, $meses);
    } catch (Exception) {
        return 'no especificada';
    }
}

$idContrato = filter_input(INPUT_GET, 'id_contrato', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idContrato || $idContrato <= 0) {
    http_response_code(400);
    exit('Debe indicar un contrato válido.');
}

$modeloContrato = new clase_contrato($conexion);
$contrato = $modeloContrato->obtenerPorId((int) $idContrato);

if (!$contrato) {
    http_response_code(404);
    exit('No se encontró el contrato solicitado.');
}

registrarBitacora($conexion, 'Contratos', 'Generar PDF', "Generó el PDF del contrato ID $idContrato.");

$salario = null;
$consultaSalario = $conexion->prepare(
    "SELECT monto FROM SALARIO
     WHERE (id_trabajador = ? OR id_cargo = ?)
       AND estado = 'Vigente'
     ORDER BY fecha DESC, id_salario DESC
     LIMIT 1"
);
if ($consultaSalario) {
    $consultaSalario->bind_param('ii', $contrato['id_trabajador'], $contrato['id_cargo']);
    $consultaSalario->execute();
    $resultadoSalario = $consultaSalario->get_result()->fetch_assoc();
    $salario = $resultadoSalario['monto'] ?? null;
    $consultaSalario->close();
}

$fechaInicio = $contrato['fecha_contrato'];
$fechaFin = $contrato['fecha_fin'] ?? null;
$marcaFirma = strtotime($fechaInicio) ?: time();
$esIndefinido = stripos($contrato['tipo_contrato'], 'indefinido') !== false
    || stripos($contrato['tipo_contrato'], 'indeterminado') !== false;

$datos = [
    'numero_contrato'            => str_pad((string) $contrato['id_contrato'], 3, '0', STR_PAD_LEFT) . '-' . date('y', $marcaFirma),
    'tipo_contrato'              => $contrato['tipo_contrato'],

    // ---- Datos institucionales fijos ----
    'ente_nombre_largo'          => 'LA FUNDACIÓN PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGÍA EN EL ESTADO YARACUY (FUNDACITE YARACUY)',
    'ente_siglas'                => 'FUNDACITE YARACUY',
    'ente_direccion'             => 'Zona Industrial Agustín Rivero, Calle 1 con Avenida 4, Edificio FUNDACITE, de los Municipio Independencia Estado Yaracuy',
    'ente_rif'                   => 'G200099330',

    // ---- Representante legal registrado en el contrato ----
    'representante_tratamiento'  => 'Ing.',
    'representante_nombre'       => $contrato['nombre_presidente'],
    'representante_cedula'       => $contrato['cedula_presidente'],
    'representante_cargo'        => 'PRESIDENTE (A)',
    'representante_cargo_firma'  => 'Presidente de la Fundación para el Desarrollo de Ciencia y Tecnología del Estado Yaracuy',
    'resolucion_numero'          => '077',
    'resolucion_fecha'           => '06 de Febrero de 2020',
    'gaceta_numero'              => $contrato['gaceta_designacion_presidente'],

    // ---- Datos del trabajador (EL/LA CONTRATADO(A)) ----
    'trabajador_nombre'          => trim($contrato['nombre_trabajador']),
    'trabajador_cedula'          => $contrato['cedula_trabajador'],
    'trabajador_nacionalidad'    => $contrato['nacionalidad'] ?: 'venezolano(a)',
    'trabajador_direccion'       => 'este domicilio',

    // ---- Cargo / dependencia ----
    'dependencia_adscripcion'    => $contrato['lugar_trabajo'],
    'cargo_necesidad'            => $contrato['nombre_cargo'] ?: 'las funciones propias del cargo asignado',

    // ---- Actividades asociadas al cargo (dinámicas según el puesto) ----
    'actividades'                => [
        [
            'verbo' => 'Realizar',
            'items' => [
                'Las funciones inherentes al cargo de ' . ($contrato['nombre_cargo'] ?: 'trabajador'),
                'Las actividades que instruya su supervisor inmediato dentro de las necesidades del servicio',
            ],
        ],
    ],

    // ---- Duración del contrato ----
    'duracion_letras'            => $esIndefinido ? 'indeterminada' : duracionContrato($fechaInicio, $fechaFin),
    'fecha_inicio_letras'        => fechaEnLetras($fechaInicio),
    'fecha_fin_letras'           => $esIndefinido ? 'sin fecha de finalización' : fechaEnLetras($fechaFin),

    // ---- Jornada de trabajo ----
    'jornada_manana_inicio'      => '8:00 a.m.',
    'jornada_manana_fin'         => '12:00 m.',
    'jornada_tarde_inicio'       => '1:00 p.m.',
    'jornada_tarde_fin'          => '4:00 p.m.',

    // ---- Salario ----
    'salario_letras'             => $salario === null ? 'NO REGISTRADO' : 'SEGÚN TABULADOR VIGENTE',
    'salario_numero'             => $salario === null ? 'No registrado' : number_format((float) $salario, 2, ',', '.'),

    // ---- Otorgamiento / firma ----
    'lugar_firma'                => 'San Felipe',
    'dia_firma_letras'           => date('d', $marcaFirma),
    'dia_firma_numero'           => date('d', $marcaFirma),
    'mes_firma'                  => nombreMes((int) date('n', $marcaFirma)),
    'anio_firma_letras'          => date('Y', $marcaFirma),
    'anio_firma_numero'          => date('Y', $marcaFirma),
];

/* -----------------------------------------------------------------------------
 * 2) LOGO INSTITUCIONAL EMBEBIDO EN BASE64
 *    (evita problemas de rutas relativas dentro de DOMPDF)
 * ---------------------------------------------------------------------------*/
// Banner con el escudo, "Gobierno Bolivariano de Venezuela", el nombre del
// Ministerio y el sello "2022-2030". Debe colocarse en ../vistas/img/
// (misma carpeta donde ya vive logo_ministerio.png).
$logoPath = __DIR__ . '/../vistas/img/logo_contrato.png';
$logoSrc = is_readable($logoPath)
    ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
    : '';

// Proporción real (alto/ancho) del banner, en porcentaje. Se usa con la
// técnica de "caja de proporción" (padding-bottom en %) para que la imagen
// llene el ancho del encabezado sin deformarse, ya que "height: auto" en
// dompdf no siempre calcula bien el alto de imágenes muy anchas y bajas.
$logoProporcion = 0.0;
if (is_readable($logoPath)) {
    $infoLogo = @getimagesize($logoPath);
    if ($infoLogo !== false && $infoLogo[0] > 0) {
        $logoProporcion = round(($infoLogo[1] / $infoLogo[0]) * 100, 4);
    }
}

/* -----------------------------------------------------------------------------
 * 3) FUNCIONES AUXILIARES DE LA PLANTILLA
 * ---------------------------------------------------------------------------*/

/** Escapa texto para HTML de forma segura. */
function h($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Construye el bloque de "actividades" (a.- Realizar: ..., b.- Elaborar: ...)
 * de forma totalmente dinámica según el cargo del trabajador.
 */
function renderActividades(array $grupos): string
{
    $html  = '';
    $letra = 'a';

    foreach ($grupos as $grupo) {
        $numerados = [];
        $n = 1;
        foreach ($grupo['items'] as $item) {
            $numerados[] = $n . '. ' . h($item);
            $n++;
        }

        $html .= '<p class="parrafo">' . $letra . '.- ' . h($grupo['verbo']) . ': '
               . implode(', ', $numerados) . '</p>';

        $letra++;
    }

    return $html;
}

$actividadesHtml = renderActividades($datos['actividades']);

/* -----------------------------------------------------------------------------
 * 4) PLANTILLA HTML DEL CONTRATO (idéntica al documento original)
 * ---------------------------------------------------------------------------*/
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Contrato - <?= h($datos['trabajador_nombre']) ?></title>
<style>
    @page {
        margin-top: 145px;
        margin-bottom: 70px;
        margin-left: 68px;
        margin-right: 68px;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 12pt;
        line-height: 1.28;
        color: #000000;
    }

    .header {
        position: fixed;
        top: -125px;
        left: 0;
        right: 0;
        text-align: left;
    }

    .header-caja {
        position: relative;
        width: 100%;
        height: 0;
        overflow: hidden;
    }

    .header-caja img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .numero-contrato {
        text-align: right;
        font-size: 12pt;
        margin: 0 0 14px 0;
    }

    p.parrafo {
        text-align: justify;
        margin: 0 0 12px 0;
        text-indent: 0;
    }

    p.parrafo.sin-margen {
        margin-bottom: 4px;
    }

    .clau {
        font-weight: bold;
        text-decoration: underline;
    }

    .center {
        text-align: center;
    }

    table.firmas {
        width: 100%;
        border-collapse: collapse;
        margin-top: 40px;
    }

    table.firmas td {
        width: 50%;
        vertical-align: top;
        text-align: center;
        font-size: 12pt;
        padding: 0 10px;
    }

    table.firmas .titulo-firma {
        font-weight: bold;
        margin-bottom: 70px;
        display: block;
    }

    table.firmas .nombre-firma {
        font-weight: bold;
        font-size: 10pt;
        display: block;
    }

    table.firmas .cargo-firma {
        font-size: 10pt;
        display: block;
    }

    .acuse {
        margin-top: 30px;
    }

    .huellas {
        margin-top: 30px;
    }
</style>
</head>
<body>

<div class="header">
    <div class="header-caja" style="padding-bottom: <?= $logoProporcion ?>%;">
        <img src="<?= $logoSrc ?>" alt="Gobierno Bolivariano de Venezuela - Ministerio del Poder Popular para Ciencia y Tecnología - 2022-2030">
    </div>
</div>

<div class="numero-contrato"><?= h($datos['numero_contrato']) ?></div>

<p class="parrafo center"><strong>TIPO DE CONTRATO: <?= h($datos['tipo_contrato']) ?></strong></p>

<p class="parrafo">
    Entre la República Bolivariana de Venezuela por órgano del
    <strong>&#8220;<?= h($datos['ente_nombre_largo']) ?>&#8221;,</strong>
    ubicado en la <?= h($datos['ente_direccion']) ?>, inscrito bajo el Registro
    Único de Identificación Fiscal N&deg; <?= h($datos['ente_rif']) ?> y
    representado en este acto por el (la) ciudadano (a)
    <strong><?= h($datos['representante_nombre']) ?>,</strong> venezolano (a),
    mayor de edad, titular de la cédula de identidad N&deg; V.-
    <strong><?= h($datos['representante_cedula']) ?>,</strong> en su carácter
    de <?= h($datos['representante_cargo']) ?> de <strong><?= h($datos['ente_nombre_largo']) ?>,</strong>
    designado (a) mediante Resolución N&deg; <?= h($datos['resolucion_numero']) ?> de
    fecha <?= h($datos['resolucion_fecha']) ?>, publicada en Gaceta Oficial de la
    República Bolivariana de Venezuela N&deg; <?= h($datos['gaceta_numero']) ?>,
    debidamente facultado (a) para este acto, y en lo sucesivo y a los efectos
    de este Contrato se denominará <strong><?= h($datos['ente_siglas']) ?></strong>;
    por una parte, y por otra parte el (la) ciudadano (a)
    <strong><?= h($datos['trabajador_nombre']) ?></strong>, de nacionalidad
    <?= h($datos['trabajador_nacionalidad']) ?>, mayor de edad, titular de la
    cédula de identidad N&deg; V.- <strong><?= h($datos['trabajador_cedula']) ?></strong>,
    de este domicilio, quien en lo adelante se denominará &#8220;EL CONTRATADO (A)&#8221;,
    quienes han convenido suscribir el presente Contrato Individual de Trabajo
    bajo la modalidad de <strong><?= h($datos['tipo_contrato']) ?></strong>, conforme a lo dispuesto en los artículos 62 y 64 de
    la Ley Orgánica del Trabajo, las Trabajadoras y los Trabajadores, y que se
    regirá por las cláusulas siguientes:
</p>

<p class="parrafo">
    <span class="clau">PRIMERA.</span> <strong>OBJETO DEL CONTRATO:</strong>
    El contrato tiene por objeto la prestación de servicios temporales,
    personales y subordinados por parte de <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;</strong>
    para <strong>&#8220;<?= h($datos['ente_siglas']) ?>&#8221;,</strong> bajo su
    dependencia, adscrito originalmente a la <?= h($datos['dependencia_adscripcion']) ?>,
    pudiendo designar a <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;</strong> cualquier
    otra tarea, proyecto o dependencia administrativa, mediante comunicación
    escrita, que formará parte integral del presente Contrato, no siendo
    necesaria la modificación de la presente cláusula.
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;</strong> declara haber recibido la
    instrucción para el ejercicio de las tareas asignadas, estar capacitado
    (a), tener las habilidades, conocimientos y experiencias necesarias para
    desempeñarlas, en forma exclusiva, y en las labores complementarias que se
    le imparta. <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> queda obligado a
    no prestar directa ni indirectamente servicios laborales a otros
    empleadores, ni a trabajar por cuenta propia en el área que
    <strong>(<?= h($datos['ente_siglas']) ?>),</strong> destine el servicio
    contratado. Igualmente se obliga a acatar y ejecutar las órdenes directas
    impartidas por su supervisor inmediato designado por
    <strong>(<?= h($datos['ente_siglas']) ?>).</strong> En ejecución del presente
    contrato, quedando convenido que las tareas o actividades descritas en el
    presente Contrato, son meramente enunciativas, por lo que, además,
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> deberá cumplir las órdenes o
    instrucciones que le dicte <strong>(<?= h($datos['ente_siglas']) ?>),</strong> o
    por intermedio de cualquiera de sus representantes, a fin de cumplir y
    ejecutar las labores que, a pesar de no estar expresamente descritas,
    sean de necesaria ejecución en virtud de razones técnicas y de servicio y
    en razón del poder de dirección, organización y disciplina que detenta
    <strong>(<?= h($datos['ente_siglas']) ?>),</strong> de conformidad con lo
    establecido en el artículo 57 de la Ley Orgánica del Trabajo, los
    Trabajadores y las Trabajadoras, por lo que <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong>
    estará obligado a desempeñar los servicios que sean compatibles con sus
    fuerzas, aptitudes, estado o condición y que sean del mismo género de los
    que forman el objeto de la actividad a que se dedica
    <strong>(<?= h($datos['ente_siglas']) ?>),</strong> el cual declara conocer.
    En todo caso, ambas partes convienen y así queda entendido y lo acepta
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> que el presente contrato ha
    sido suscrito para ejercer, entre otros, las siguientes actividades que
    pasamos a describir de manera enunciativa:
</p>

<?= $actividadesHtml ?>

<p class="parrafo">
    <span class="clau">SEGUNDA.</span> <strong>NATURALEZA DEL CONTRATO:</strong>.
    Las partes convienen en que la causa y naturaleza del presente contrato,
    así como de los servicios que prestará <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong>
    se basan en la necesidad que tiene <strong>(<?= h($datos['ente_siglas']) ?>),</strong>
    de: <?= h($datos['cargo_necesidad']) ?>. El presente vínculo se celebra bajo
    la modalidad de <strong><?= h($datos['tipo_contrato']) ?></strong>, conforme a
    las condiciones, fechas y demás datos registrados en este documento.
</p>

<p class="parrafo">
    <span class="clau">TERCERA:</span> <strong>DURACIÓN DEL CONTRATO</strong>.
    El presente contrato tendrá una vigencia <?= h($datos['duracion_letras']) ?><?= $esIndefinido ? '' : ' meses' ?>,
    contado a partir del <?= h($datos['fecha_inicio_letras']) ?>.
    <?php if ($esIndefinido): ?>
        <strong>Se celebra sin fecha de finalización.</strong>
    <?php else: ?>
        <strong>Finaliza el <?= h($datos['fecha_fin_letras']) ?>, fecha en la que el
        mismo dejará de surtir sus efectos, sin necesidad de notificación.</strong>
    <?php endif; ?>
    Las partes dejan constancia de común acuerdo, en forma expresa e
    inequívoca que en virtud de la naturaleza del servicio y de la
    Administración Pública Nacional, que su vínculo laboral se mantendrá a
    <?= h($datos['tipo_contrato']) ?>, independientemente si al vencimiento, aún persiste la
    causa que dio nacimiento a la contratación o existan razones especiales y
    justificables para prorrogarlo, siempre que excluyan la presunta
    intención de transformarla en una relación a tiempo indeterminado. El
    presente contrato podrá darse por terminado de manera anticipada por
    <strong>&#8220;<?= h($datos['ente_siglas']) ?>&#8221;,</strong> por las causas contenidas
    en el Artículo 79 de la Ley Orgánica del Trabajo, las Trabajadoras y los
    Trabajadores, por procesos de reestructuración o reorganización
    administrativa o funcional, o por haber culminado el proyecto relacionado
    con la prestación del servicio.
</p>

<p class="parrafo">
    <span class="clau">CUARTA:</span> <strong>JORNADA DE TRABAJO</strong>.
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> cumplirá con las tareas o
    servicios señalados en este contrato, a tiempo completo, durante una
    jornada de trabajo diaria desde las <?= h($datos['jornada_manana_inicio']) ?>
    a <?= h($datos['jornada_manana_fin']) ?>, con una hora para almorzar y
    descansar, y luego de <?= h($datos['jornada_tarde_inicio']) ?> a
    <?= h($datos['jornada_tarde_fin']) ?>. Adicionalmente tendrá derecho a dos
    (2) días de descanso remunerado semanal, en los términos establecidos en
    la Ley, pudiendo <strong>(<?= h($datos['ente_siglas']) ?>)</strong> hacer ajuste
    o cambio en el horario, cuando así existan razones que lo justifiquen,
    respetando los límites legales. Asimismo, queda convenido que, de
    conformidad con lo establecido en el artículo 119 de la Ley Orgánica del
    Trabajo, las Trabajadoras y los Trabajadores, <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong>
    tendrá derecho al pago de los días de descanso y feriados cuando haya
    prestado servicio durante por lo menos cuatro (04) días hábiles de la
    jornada semanal de trabajo, por lo que, en caso de dos o más
    inasistencias, <strong>&#8220;<?= h($datos['ente_nombre_largo']) ?>&#8221;</strong>
    podrá descontar las inasistencias y los días de descanso o feriados
    pagados indebidamente.
</p>

<p class="parrafo">
    <span class="clau">QUINTA.</span> <strong>OBLIGACIONES DEL TRABAJADOR: &#8220;EL (LA) CONTRATADO (A)&#8221;,</strong>
    prestará sin limitación, sus servicios y desarrollará las actividades
    inherentes, derivadas o relacionadas con el Objeto del presente Contrato,
    cumpliendo sus tareas de conformidad con la Legislación Venezolana, las
    buenas costumbres, la moral, la normativa y políticas de
    <strong>(<?= h($datos['ente_siglas']) ?>)&#8221;</strong>, aceptando y asumiendo
    principalmente las siguientes obligaciones: a) Demostrar puntualidad,
    responsabilidad, rendimiento, capacidad, compromiso y disposición durante
    el desempeño de sus funciones, coadyuvando en las actividades necesarias
    para el mejor cumplimiento de los objetivos y metas; b) Guardar la
    confidencialidad y el secreto ante terceras personas distintas al
    <strong>(<?= h($datos['ente_siglas']) ?>)&#8221;</strong> por la información o
    documentación de la que tuviere conocimiento en razón de sus labores; c)
    Mantener una conducta ética y moral, respetando la integridad y dignidad
    de las personas que laboren en <strong><?= h($datos['ente_nombre_largo']) ?></strong>
    o con las cuales se relacione como consecuencia del vínculo laboral; d)
    Cumplir con lo establecido en la Ley Orgánica del Trabajo, las
    Trabajadoras y los Trabajadores y demás disposiciones legales aplicables.
</p>

<p class="parrafo">
    <span class="clau">SEXTA.</span> <strong>DEL SALARIO: <?= h($datos['ente_nombre_largo']) ?>&#8221;</strong>
    pagará a <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> como
    contraprestación por los servicios prestados, un remuneración mensual por
    la cantidad de <strong><?= h($datos['salario_letras']) ?> (<?= h($datos['salario_numero']) ?>),</strong>
    pagaderos en dos (2) cuotas quincenales, mediante depósitos efectuados,
    previa deducción de los conceptos que, legal o convencionalmente, sean
    procedentes efectuarle a <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> y que en
    este acto expresamente autoriza a <strong>(<?= h($datos['ente_siglas']) ?>)&#8221;</strong>
    realizarlos. Los depósitos serán abonados mediante depósito o
    transferencia bancaria en la cuenta del Banco de Venezuela, a nombre de
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> quedando entendido que el
    comprobante de la transferencia o el depósito liberará a
    <strong>(<?= h($datos['ente_siglas']) ?>)</strong> de tales obligaciones. En este
    sentido <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> se obliga a indicarle a
    <strong>(<?= h($datos['ente_siglas']) ?>)</strong> cualquier cambio, modificación
    o cancelación de la cuenta, no corriendo mora alguna para
    <strong>(<?= h($datos['ente_siglas']) ?>)</strong> por la omisión de esta
    indicación.
</p>

<p class="parrafo">
    <span class="clau">SEXTA:</span> <strong>OTROS BENEFICIOS A PERCIBIR</strong>.
    <strong><?= h($datos['ente_nombre_largo']) ?></strong> se obliga a pagar
    anualmente a <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> por concepto de:
</p>

<p class="parrafo">
    <em><strong>A) Bonificación de Fin de Año (Aguinaldos)</strong></em>: Un
    total de ciento veinte (120) días anuales calculados con base al promedio
    del salarios devengados por <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong>
    durante el año calendario transcurrido entre el día 1&deg; de enero hasta
    el 31 de diciembre de cada año, en proporción a los meses completos de
    servicios prestados, de conformidad con lo previsto en cláusula N.&#186; 38
    de la Convención Colectiva Marco Socialista de Trabajo de la
    Administración Pública Nacional 2016-2018, publicada en la Gaceta Oficial
    de la República Bolivariana de Venezuela N&deg; 6.268 Extraordinario de
    fecha 26 de octubre de 2016.
</p>

<p class="parrafo">
    <em><strong>B) Régimen de Vacaciones</strong></em>: Las partes convienen la
    aplicación del régimen de vacaciones establecido en la Ley Orgánica del
    Trabajo, de las Trabajadoras y los Trabajadores vigente.
</p>

<p class="parrafo">
    <em><strong>C) Prestaciones Sociales</strong></em>: <strong><?= h($datos['ente_nombre_largo']) ?>,</strong>
    manifiesta su voluntad, declarando y solicitando en este acto que sea
    acreditada trimestralmente su garantía de prestaciones sociales, en la
    contabilidad de <strong>(<?= h($datos['ente_siglas']) ?>),</strong> de
    conformidad con lo referido en los artículos 142 y 143 de la Ley Orgánica
    del Trabajo, las Trabajadoras y los Trabajadores.
</p>

<p class="parrafo">
    <em><strong>D) Beneficio de Alimentación</strong></em>: <strong><?= h($datos['ente_nombre_largo']) ?></strong>
    se obliga a cumplir con su obligación contendida e obliga a cumplir con
    lo dispuesto en el Artículo 7 de Decreto con Rango, Valor y Fuerza de Ley
    del Cesta Ticket Socialista para los Trabajadores y Trabajadoras,
    publicado en Gaceta Oficial de la República Bolivariana de Venezuela
    N&#186; 40.773 del 23 de octubre de 2015, así como 1 y 5 del Decreto N.&#186;
    4.805 publicado en la Gaceta Oficial Extraordinaria N&deg; 6.746 del 1&deg;
    de mayo de 2023. Queda entendido entre las partes que, dicho beneficio no
    reviste carácter remunerativo por ser excepcional, de acuerdo con lo
    previsto en el numeral 2, del Artículo 105 de la Ley Orgánica del
    Trabajo, las Trabajadoras y los Trabajadores.
</p>

<p class="parrafo">
    <span class="clau">SÉPTIMA.</span> <strong>CESIÓN DEL CONTRATO:</strong> El
    presente contrato es celebrado <em>intuitu personae</em>, para con la
    persona de <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> en consecuencia,
    está obligado (a) a prestar sus servicios de manera eficiente, diligente
    y responsable, sin poder traspasar o ceder total o parcialmente a
    terceros, ni tampoco subcontratar ayudantes. No teniendo autorización
    alguna para comprometer patrimonialmente ni en ninguna otra forma a
    <strong>(<?= h($datos['ente_siglas']) ?>).</strong> Toda acción o hecho
    realizado en ese sentido será nulo en forma absoluta, no se transmitirá
    ninguna responsabilidad a <strong>(<?= h($datos['ente_siglas']) ?>)</strong> y
    será considerado como una falta grave a las obligaciones que impone la
    relación de trabajo, por lo que podrá tomar las medidas legales que
    correspondan.
</p>

<p class="parrafo">
    <span class="clau">OCTAVA.</span> <strong>VERACIDAD DE DATOS SUMINISTRADOS:</strong>
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> declara que los datos e
    informaciones que ha indicado a <strong>(<?= h($datos['ente_siglas']) ?>)</strong>,
    bien sea en la Planilla de Solicitud de Empleo o en cualquier otro que se
    le soliciten, son ciertos, así como la destreza que ha manifestado tener
    para el desempeño de las tareas asignadas, de tal manera que queda
    autorizado <strong>(<?= h($datos['ente_siglas']) ?>)</strong> para investigar y
    corroborar toda esa información, en el entendido de que cualquier
    falsedad en tales datos, será considerado como falta de probidad por
    parte de <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> lo que configuraría
    causa justificada de despido, de conformidad con lo previsto en el
    literal &#8220;a&#8221; del artículo 79 de la Ley Orgánica del Trabajo, las
    Trabajadoras y los Trabajadores.
</p>

<p class="parrafo">
    <span class="clau">NOVENA.</span> <strong>CONFIDENCIALIDAD</strong>:
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> se compromete durante y
    después de la vigencia del presente Contrato, a guardar confidencialidad
    sobre toda la información a la que tenga acceso, por razón de los
    servicios prestados, reconociendo que estará en contacto y manejará
    información privada y de carácter confidencial propiedad de
    <strong>&#8220;EL (LA) (<?= h($datos['ente_siglas']) ?>)&#8221;</strong>, por lo cual
    se obliga a mantener en la más estricta reserva y a no divulgarla a
    ninguna persona, natural o jurídica, pública o privada, ni a ningún ente
    gubernamental, excepto cuando sea necesario por razones legales y, en
    todos los casos, con la previa autorización de
    <strong>(<?= h($datos['ente_siglas']) ?>).</strong> La violación del deber de
    confidencialidad y de no concurrencia desleal, se considerará como una
    falta de probidad y falta grave a las obligaciones prevista en la
    relación de trabajo, sin perjuicio del ejercicio de las demás acciones
    que puedan resultar procedentes, especialmente para lograr el
    resarcimiento de los daños y perjuicios causados por el incumplimiento.
    <strong>EL (LA) CONTRATADO (A)&#8221;</strong> queda obligado a devolver a
    <strong><?= h($datos['ente_nombre_largo']) ?></strong> al término de la
    relación de trabajo, por cualquier causa, todas las herramientas de
    trabajo que se le hayan entregado para la prestación del servicio
    contratado, así como todos los documentos, archivos y carnets de
    identificación, papeles que se hubiesen utilizado para la prestación del
    servicio, considerándose los mismos como propiedad única y exclusiva de
    <strong>(<?= h($datos['ente_siglas']) ?>),</strong> constituyendo falta grave a
    las obligaciones impuestas por la relación de trabajo, siendo así una
    causa de despido justificado, la destrucción o eliminación de los
    documentos o archivos, incluyendo los digitales o computarizados,
    quedando prohibida su extracción, reproducción, edición, divulgación,
    adición sin el permiso dado por escrito por <strong>c</strong>. Queda
    convenido que la propiedad de las invenciones o mejoras corresponderán a
    <strong>(<?= h($datos['ente_siglas']) ?>),</strong> quedando incluida dentro del
    salario convenido cualquier retribución a que tenga derecho
    <strong>EL (LA) CONTRATADO (A)&#8221;</strong> por el trabajo intelectual
    prestado.
</p>

<p class="parrafo">
    <span class="clau">DÉCIMA.</span> <strong>MATERIALES Y HERRAMIENTAS DE TRABAJO</strong>:
    <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;</strong> se obliga a resguardar y
    mantener en buen estado de conservación los materiales y herramientas de
    trabajo que le sean suministradas por <strong>(<?= h($datos['ente_siglas']) ?>),</strong>
    para la consecución de sus actividades en función de este contrato,
    siendo responsable de los daños y perjuicios que pudieran ocasionar su
    pérdida o deterioro, asumiendo toda la responsabilidad por el daño
    ocasionado debiendo indemnizarla, a excepción del caso fortuito o de
    fuerza mayor debidamente comprobados, autorizando a
    <strong>(<?= h($datos['ente_siglas']) ?>)</strong> a descontarlo como
    corresponda de su remuneración.
</p>

<p class="parrafo">
    <span class="clau">DÉCIMA PRIMERA.</span> <strong>NORMATIVA APLICABLE</strong>:
    Todo lo no previsto en el presente contrato se regirá por las
    estipulaciones de la Ley Orgánica del Trabajo, las Trabajadoras y los
    Trabajadores; y del Reglamento de la Ley Orgánica del Trabajo, así como
    por cualquier otra normativa laboral positiva y vigente que resulte
    aplicable. Las partes acuerdan que, en caso de existir dudas o
    controversias que se originen con ocasión a la ejecución o interpretación
    del presente contrato, serán presentadas, tratadas y resueltas por las
    partes, principalmente ante la Oficina de Gestión Humana, acogiéndose a
    la implementación de los Medios Alternativos de Resolución de Conflictos,
    de no ser resuelto, en su defecto ante las Inspectoría del Trabajo
    correspondiente o los Tribunales de la Jurisdicción Ordinaria en materia
    del Trabajo de la Circunscripción Judicial del Estado Yaracuy.
</p>

<p class="parrafo">
    <span class="clau">DÉCIMA</span>: <strong>DOMICILIO</strong>: Ambas partes
    fijan como domicilio único y especial a la ciudad de <?= h($datos['lugar_firma']) ?>,
    a la Jurisdicción de cuyos Tribunales expresamente declaran someterse. En
    caso de notificación las partes señalan las siguientes direcciones:
</p>

<p class="parrafo sin-margen">
    a) DE <strong><?= h($datos['ente_nombre_largo']) ?></strong>: <?= h($datos['ente_direccion']) ?>
</p>

<p class="parrafo">
    b) De <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;</strong>: <?= h($datos['trabajador_direccion']) ?>
</p>

<p class="parrafo">
    Asimismo, podrá realizarse la notificación por medios electrónicos, tales
    como correo electrónico e incluso por medio de la red social <em>WhatsApp</em>,
    de conformidad con la Ley Sobre Mensajes de Datos y Firmas Electrónicas.
</p>

<p class="parrafo">
    <span class="clau">DÉCIMA PRIMERA.</span> <strong>EJEMPLARES</strong>: Se
    hacen dos (2) ejemplares del presente contrato, a un mismo tenor y a un
    solo efecto, en la ciudad de <?= h($datos['lugar_firma']) ?> a los
    <?= h($datos['dia_firma_letras']) ?> días del mes <?= h($datos['mes_firma']) ?>
    del año <?= h($datos['anio_firma_letras']) ?>.
</p>

<p class="parrafo">
    <?= h($datos['lugar_firma']) ?>, a los días <?= h($datos['dia_firma_numero']) ?>
    días del mes de <?= h($datos['mes_firma']) ?> de <?= h($datos['anio_firma_numero']) ?>.
</p>

<table class="firmas">
    <tr>
        <td>
            <span class="titulo-firma">Por EL ENTE</span>
            <span class="nombre-firma"><?= h($datos['representante_tratamiento']) ?> <?= h(ucwords(mb_strtolower($datos['representante_nombre'], 'UTF-8'))) ?></span>
            <span class="cargo-firma"><?= h($datos['representante_cargo_firma']) ?></span>
        </td>
        <td>
            <span class="titulo-firma">EL(LA) TRABAJADOR(A)</span>
        </td>
    </tr>
</table>

<p class="parrafo acuse">
    Y, yo___________________________, de nacionalidad ______________, mayor
    de edad y titular de la Cédula de Identidad N&deg; V.-_________, en mi
    condición de <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;</strong>, doy acuse de
    recibo de un (1) ejemplar original del documento contentivo de mi
    contrato individual de trabajo bajo la modalidad de <?= h($datos['tipo_contrato']) ?>, que he suscrito con
    <strong>(<?= h($datos['ente_siglas']) ?></strong> y que recibo el día ___ del
    mes de _____________ del año <?= h($datos['anio_firma_numero']) ?> siendo la
    hora __________.
</p>

<p class="parrafo huellas">HUELLAS DEL TRABAJADOR O TRABAJADORA</p>

</body>
</html>
<?php
$html = ob_get_clean();

/* -----------------------------------------------------------------------------
 * 5) GENERACIÓN DEL PDF CON DOMPDF
 * ---------------------------------------------------------------------------*/
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Times New Roman');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();


$nombreArchivo = 'Contrato_' . str_replace(['/', ' '], '-', $datos['numero_contrato'])
    . '_' . str_replace(' ', '_', $datos['trabajador_nombre']) . '.pdf';

$dompdf->stream($nombreArchivo, ['Attachment' => false]);