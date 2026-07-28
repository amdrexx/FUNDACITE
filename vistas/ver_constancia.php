<?php
session_start();
include_once "includes/guardian.php";

require_once "../conexion.php";
require_once "../modelos/clase_constancia.php";

$id_constancia = intval($_GET['id'] ?? 0);
$autoPrint = isset($_GET['print']);

if ($id_constancia <= 0) {
    header("Location: lista_constancias.php");
    exit;
}

$constanciaObj = new clase_constancia($conexion);
$doc = $constanciaObj->obtenerPorId($id_constancia);

if (!$doc) {
    header("Location: lista_constancias.php");
    exit;
}

function numeroALetras($numero) {
    $entero = intval($numero);
    $decimales = round(($numero - $entero) * 100);

    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TRINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $cientos = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    if ($entero == 0) return 'CERO BOLIVARES';
    if ($entero == 100) return 'CIEN BOLIVARES';

    $resultado = '';
    $centenas = intval($entero / 100);
    $resto = $entero % 100;
    $decenasVal = intval($resto / 10);
    $unidadesVal = $resto % 10;

    if ($centenas > 0) {
        $resultado .= $cientos[$centenas];
        if ($resto > 0) $resultado .= ' ';
    }

    if ($resto >= 10 && $resto <= 19) {
        $resultado .= $especiales[$resto - 10];
    } else {
        if ($decenasVal > 0) {
            $resultado .= $decenas[$decenasVal];
            if ($unidadesVal > 0) $resultado .= ' Y ';
        }
        if ($unidadesVal > 0) {
            $resultado .= $unidades[$unidadesVal];
        }
    }

    $resultado .= ' BOLIVARES';

    if ($decimales > 0) {
        $resultado .= ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . ' CENTIMOS';
    }

    return $resultado;
}

$fecha = new DateTime($doc['fecha_constancia']);
$dia = $fecha->format('d');
$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mesNombre = $meses[(int)$fecha->format('m')];
$anio = $fecha->format('Y');
$salarioLetras = numeroALetras($doc['salario_monto'] ?? 0);
$salarioNumero = number_format($doc['salario_monto'] ?? 0, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Trabajo</title>
    <link rel="stylesheet" href="css/style_dashboard.css">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <script src="js/bootstrap.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .form-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main" style="display: block !important; clear: both !important;">
    <div style="max-width: 650px; width: 100%; margin: 0 auto; display: block; box-sizing: border-box;">
        <div style="width: 100%; display: block; margin-bottom: 30px; box-sizing: border-box;">
            <div class="form-card" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box; margin: 0 !important;">

                <center>
                    <p style="margin:0; font-size:12px;"><strong>RIF: G20009933-0</strong></p>
                    <h2 style="margin:5px 0;">CONSTANCIA DE TRABAJO</h2>
                </center>

                <div style="text-align: justify; line-height: 1.8; margin: 20px 0; font-size: 14px;">
                    <p>
                        Quien suscribe: <strong><?= htmlspecialchars($doc['nombre_director_departamento']) ?></strong>,
                        en mi caracter de Director(a) de Gestion Humana de la
                        <strong>FUNDACION PARA EL DESARROLLO DE LA CIENCIA Y TECNOLOGIA (FUNDACITE YARACUY)</strong>,
                        hago constar por medio de la presente que el(la) ciudadano(a):
                    </p>

                    <p>
                        <strong><?= htmlspecialchars($doc['nombres'] . ' ' . $doc['apellidos']) ?></strong>,
                        Titular de la C.I. <strong><?= htmlspecialchars($doc['cedula']) ?></strong>,
                        presta sus servicios en esta Institucion desde el
                        <strong><?= date('d/m/Y', strtotime($doc['fecha_ingreso'])) ?></strong>,
                        actualmente ocupando el cargo de
                        <strong><?= htmlspecialchars($doc['nombre_cargo'] ?? 'N/A') ?></strong>
                        en calidad de <strong><?= htmlspecialchars($doc['tipo_personal']) ?></strong>,
                        devengando un Salario Mensual de
                        <strong><?= $salarioLetras ?> (<?= $salarioNumero ?>Bs.)
                        </strong> y una Subvencion de Alimentacion que corresponde a lo aprobado por el ejecutivo nacional.
                    </p>

                    <p>
                        Constancia que se expide a solicitud de la parte interesada en
                        Independencia, a los <strong><?= $dia ?></strong> dias del mes de
                        <strong><?= $mesNombre ?></strong> del anio <strong><?= $anio ?></strong>.
                    </p>
                </div>

                <center style="margin-top: 40px;">
                    <p>__________________________</p>
                    <p><strong><?= htmlspecialchars($doc['nombre_director_departamento']) ?></strong></p>
                    <p>Directora de Gestion Humana</p>
                </center>

                <div class="no-print" style="display: flex; gap: 10px; margin-top: 30px;">
                    <button onclick="window.print();" class="btn-guardar full-width"
                            style="background-color: #28a745; border: none; cursor: pointer;">
                        Imprimir / Guardar PDF
                    </button>
                    <a href="lista_constancias.php" class="btn-persona full-width"
                       style="text-align:center; text-decoration:none; display:inline-block; line-height: 2.2;">
                        Volver
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php if ($autoPrint): ?>
<script>window.onload = function() { window.print(); }</script>
<?php endif; ?>

</body>
</html>
