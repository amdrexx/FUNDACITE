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

require __DIR__ . '/vendor/autoload.php'; // Ruta al autoload de Composer

use Dompdf\Dompdf;
use Dompdf\Options;

/* -----------------------------------------------------------------------------
 * 1) OBTENCIÓN DE DATOS -> EN PRODUCCIÓN ESTO VIENE DE LA BASE DE DATOS
 * ---------------------------------------------------------------------------
 *  Ejemplo real de integración (descomentar y adaptar a tu conexión):
 *
 *  $idContrato = $_GET['id'] ?? null;
 *
 *  $stmt = $pdo->prepare("
 *      SELECT c.numero_contrato, c.fecha_inicio, c.fecha_fin, c.dependencia,
 *             c.cargo, c.salario, c.salario_letras, c.dia_firma, c.mes_firma,
 *             c.anio_firma,
 *             t.nombre AS trabajador_nombre, t.cedula AS trabajador_cedula,
 *             t.nacionalidad AS trabajador_nacionalidad,
 *             t.direccion AS trabajador_direccion,
 *             r.tratamiento, r.nombre AS representante_nombre,
 *             r.cedula AS representante_cedula, r.cargo AS representante_cargo,
 *             r.resolucion_numero, r.resolucion_fecha, r.gaceta_numero
 *      FROM contratos c
 *      INNER JOIN trabajadores t   ON t.id = c.trabajador_id
 *      INNER JOIN representantes r ON r.id = c.representante_id
 *      WHERE c.id = :id
 *  ");
 *  $stmt->execute(['id' => $idContrato]);
 *  $row = $stmt->fetch(PDO::FETCH_ASSOC);
 *
 *  // Las actividades del cargo también podrían venir de una tabla
 *  // "cargos_actividades" relacionada al cargo del trabajador.
 *  $stmtAct = $pdo->prepare("SELECT verbo, descripcion FROM cargo_actividades WHERE cargo_id = :cid ORDER BY orden");
 *  ...
 *
 * ---------------------------------------------------------------------------
 *  Mientras tanto, se deja un arreglo de ejemplo con los datos del
 *  contrato Nº 003-26 tal como venían en el documento original.
 * ---------------------------------------------------------------------------*/

$datos = [

    // ---- Número / identificador del contrato ----
    'numero_contrato'            => '003-26',

    // ---- Datos del Ente (Fundacite Yaracuy) ----
    'ente_nombre_largo'          => 'LA FUNDACIÓN PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGÍA EN EL ESTADO YARACUY (FUNDACITE YARACUY)',
    'ente_siglas'                => 'FUNDACITE YARACUY',
    'ente_direccion'             => 'Zona Industrial Agustín Rivero, Calle 1 con Avenida 4, Edificio FUNDACITE, de los Municipio Independencia Estado Yaracuy',
    'ente_rif'                   => 'G200099330',

    // ---- Representante legal del Ente (Presidente/a) ----
    'representante_tratamiento'  => 'Ing.',
    'representante_nombre'       => 'MIGUEL ÁNGEL SOLORZANO BELIZARIO',
    'representante_cedula'       => '19.817.987',
    'representante_cargo'        => 'PRESIDENTE (A)',
    'representante_cargo_firma'  => 'Presidente de la Fundación para el Desarrollo de Ciencia y Tecnología del Estado Yaracuy',
    'resolucion_numero'          => '077',
    'resolucion_fecha'           => '06 de Febrero de 2020',
    'gaceta_numero'              => '41.823',

    // ---- Datos del trabajador (EL/LA CONTRATADO(A)) ----
    'trabajador_nombre'          => 'ARIANNI CAROLINA QUINTERO GOMEZ',
    'trabajador_cedula'          => '22.309.474',
    'trabajador_nacionalidad'    => 'venezolana',
    'trabajador_direccion'       => 'Las Mercedes, Calle Principal Vía Bernabo Municipio San Felipe Estado Yaracuy',

    // ---- Cargo / dependencia ----
    'dependencia_adscripcion'    => 'DIRECCIÓN DE GESTIÓN DE SERVICIOS GENERALES',
    'cargo_necesidad'            => 'OBRERO DE MANTENIMIENTO',

    // ---- Actividades asociadas al cargo (dinámicas según el puesto) ----
    'actividades'                => [
        [
            'verbo' => 'Realizar',
            'items' => [
                'Limpieza de pasillos',
                'Limpieza de oficinas (incluyendo limpieza de paredes y techos.)',
                'Limpieza de baños y sanitarios los días',
                'Limpieza de los vidrios de las oficinas (parte interna)',
                'Limpieza de papeleras cada 15 días (con la ayuda de un obrero de mantenimiento general)',
                'Realizar mantenimiento a los dispensadores de agua potable cada 15 días',
                'Limpieza de pasamanos y vidrios de las barandas de las escaleras y los vidrios de la puerta principal y de la salida de emergencia una vez a la semana',
                'Apoyar en caso de ser necesario en cualquier otra actividad que el jefe inmediato de su departamento considere.',
            ],
        ],
        [
            'verbo' => 'Elaborar',
            'items' => [
                'Elaboración del café.',
            ],
        ],
    ],

    // ---- Duración del contrato ----
    'duracion_letras'            => 'Doce (12)',
    'fecha_inicio_letras'        => 'Primero (01) de Enero de 2026',
    'fecha_fin_letras'           => 'treinta y uno (31) de Diciembre de 2026',

    // ---- Jornada de trabajo ----
    'jornada_manana_inicio'      => '8:00 a.m.',
    'jornada_manana_fin'         => '12:00 m.',
    'jornada_tarde_inicio'       => '1:00 p.m.',
    'jornada_tarde_fin'          => '4:00 p.m.',

    // ---- Salario ----
    'salario_letras'             => 'CIENTO TREINTA BOLÍVARES SIN CÉNTIMOS',
    'salario_numero'             => '130,00',

    // ---- Otorgamiento / firma ----
    'lugar_firma'                => 'San Felipe',
    'dia_firma_letras'           => 'Primeros (01)',
    'dia_firma_numero'           => '01',
    'mes_firma'                  => 'Enero',
    'anio_firma_letras'          => 'dos mil veintiséis (2026)',
    'anio_firma_numero'          => '2026',
];

/* -----------------------------------------------------------------------------
 * 2) LOGO INSTITUCIONAL EMBEBIDO EN BASE64
 *    (evita problemas de rutas relativas dentro de DOMPDF)
 * ---------------------------------------------------------------------------*/
$logoPath   = __DIR__ . '/assets/logo_fundacite.png';
$logoSrc    = 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));

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
<title>Contrato <?= h($datos['numero_contrato']) ?> - <?= h($datos['trabajador_nombre']) ?></title>
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
        height: 90px;
        text-align: left;
    }

    .header img {
        width: 100%;
        height: auto;
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
    <img src="<?= $logoSrc ?>" alt="Gobierno Bolivariano de Venezuela - Ministerio del Poder Popular para Ciencia y Tecnología">
</div>

<div class="numero-contrato"><?= h($datos['numero_contrato']) ?></div>

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
    a Tiempo Determinado, conforme a lo dispuesto en los artículos 62 y 64 de
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
    <span class="clau">SEGUNDA.</span> <strong>NATURALEZA DEL TIEMPO DETERMINADO:</strong>.
    Las partes convienen en que la causa y naturaleza del presente contrato,
    así como de los servicios que prestará <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong>
    se basan en la necesidad que tiene <strong>(<?= h($datos['ente_siglas']) ?>),</strong>
    de: <?= h($datos['cargo_necesidad']) ?>, y en todo caso, por tratarse de un
    órgano de la Administración Pública, no puede comprometer recursos
    presupuestarios de ejercicios fiscales o años futuros, lo que implica que,
    por mandato legal, el contrato no puede exceder del 31 de Diciembre del
    mismo año en que se firma el Contrato, lo cual encuadra dentro del
    supuesto legal establecido en el literal &#8220;a&#8221; del artículo 64 de la Ley
    Orgánica del Trabajo, las Trabajadoras y los Trabajadores, debido a que
    por exigencia de la naturaleza del servicio, necesariamente tiene un
    tiempo finito, razón por la cual el presente contrato se celebra de una
    manera circunstancial y, en consecuencia, queda entendido que las
    obligaciones asumidas por <strong>&#8220;EL (LA) CONTRATADO (A)&#8221;,</strong> también
    participan de una naturaleza de tiempo determinado.
</p>

<p class="parrafo">
    <span class="clau">TERCERA:</span> <strong>DURACIÓN DEL CONTRATO</strong>.
    El presente contrato tendrá una vigencia de <?= h($datos['duracion_letras']) ?>
    meses contado a partir del <?= h($datos['fecha_inicio_letras']) ?>
    <strong>hasta el día <?= h($datos['fecha_fin_letras']) ?>, fecha en la que el
    mismo dejará de surtir sus efectos, sin necesidad de notificación.</strong>
    Las partes dejan constancia de común acuerdo, en forma expresa e
    inequívoca que en virtud de la naturaleza del servicio y de la
    Administración Pública Nacional, que su vínculo laboral se mantendrá a
    tiempo determinado, independientemente si al vencimiento, aún persiste la
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
    contrato individual de trabajo a tiempo determinado, que he suscrito con
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

/* -----------------------------------------------------------------------------
 * 6) SALIDA DEL PDF
 * ---------------------------------------------------------------------------
 *  'I' -> abre el PDF en el navegador (visualización / impresión)
 *  'D' -> fuerza la descarga
 *  'F' -> guarda el archivo en el servidor (útil para adjuntarlo a un correo,
 *         guardarlo en el expediente digital del trabajador, etc.)
 * ---------------------------------------------------------------------------*/
$nombreArchivo = 'Contrato_' . str_replace(['/', ' '], '-', $datos['numero_contrato'])
    . '_' . str_replace(' ', '_', $datos['trabajador_nombre']) . '.pdf';

$dompdf->stream($nombreArchivo, ['Attachment' => false]);