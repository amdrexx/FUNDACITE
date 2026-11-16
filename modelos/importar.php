<?php

// 1. INICIAR SESIÓN Y VALIDAR ADMINISTRADOR
// ---------------------------------------------------------------------------
session_start();

require_once __DIR__ . '/../vistas/includes/guardian.php';

require_once __DIR__ . '/../vistas/includes/roles.php';
require_once __DIR__ . '/../vistas/includes/sesion.php';

require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

requireAdministrador();

// 2. PREPARAR LA SESIÓN PARA UNA OPERACIÓN LARGA
// ---------------------------------------------------------------------------
sesionRenovar();

session_write_close();

ignore_user_abort(true);

@set_time_limit(0);

// 3. DEFINIR EL RETORNO PARA ERRORES
// ---------------------------------------------------------------------------
$destino = '/FUNDACITE/vistas/dashboard.php';

$volverConError = static function (string $mensaje) use ($destino): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    sesionRenovar();

    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode($mensaje));
    exit; // No continuar el importador tras producir una respuesta de error.
};

// 4. VALIDAR Y LOCALIZAR EL ARCHIVO SQL
// ---------------------------------------------------------------------------
$directorio = __DIR__ . '/../Respaldo_BD/';
$origen = '';      // Ruta completa del archivo que se leerá.
$nombreOrigen = ''; // Nombre que se usará en el mensaje y la bitácora.

if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $volverConError('No se pudo subir el archivo SQL. Revise el límite de carga del servidor.');
    }

    $nombreOriginal = basename((string) $_FILES['archivo']['name']);

    if (strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)) !== 'sql') {
        $volverConError('El archivo debe tener extensión .sql.');
    }

    if ((int) $_FILES['archivo']['size'] > 100 * 1024 * 1024) {
        $volverConError('El archivo supera el máximo permitido de 100 MB.');
    }

    $origen = (string) $_FILES['archivo']['tmp_name'];
    $nombreOrigen = $nombreOriginal;

} elseif (!empty($_POST['respaldo'])) {
    $nombreOrigen = basename(trim((string) $_POST['respaldo']));

    if ($nombreOrigen !== trim((string) $_POST['respaldo']) || strtolower(pathinfo($nombreOrigen, PATHINFO_EXTENSION)) !== 'sql') {
        $volverConError('El nombre del respaldo seleccionado no es válido.');
    }

    $origen = $directorio . $nombreOrigen;

} else {
    $volverConError('Seleccione un respaldo guardado o suba un archivo .sql.');
}

if (!is_file($origen) || !is_readable($origen)) {
    $volverConError('No se encontró el archivo o no se puede leer.');
}

// 5. CONECTAR CON MYSQL Y SELECCIONAR LA BASE
// ---------------------------------------------------------------------------
$dbHost = 'localhost';
$dbUsuario = 'root';
$dbPassword = '';
$nombreBd = 'bd_rrhh_fundacite_yaracuy';

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = new mysqli($dbHost, $dbUsuario, $dbPassword);
if ($conexion->connect_error) {
    $volverConError('No se pudo conectar con el servidor MySQL: ' . $conexion->connect_error);
}

if (!$conexion->set_charset('utf8mb4')) {
    $volverConError('No se pudo configurar UTF-8 en la conexión: ' . $conexion->error);
}

$nombreBdSql = '`' . str_replace('`', '``', $nombreBd) . '`';

if (!$conexion->query("CREATE DATABASE IF NOT EXISTS {$nombreBdSql} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") || !$conexion->select_db($nombreBd)) {
    $volverConError('No se pudo crear o seleccionar la base de datos: ' . $conexion->error);
}

// 6. ABRIR EL RESPALDO Y PREPARAR CONTADORES
// ---------------------------------------------------------------------------
$archivoSql = fopen($origen, 'rb');
if (!$archivoSql) {
    $volverConError('No se pudo abrir el archivo SQL.');
}

$consultasOk = 0;      // Sentencias SQL ejecutadas correctamente.
$errores = [];         // Hasta cinco mensajes de error para informar al usuario.
$cantidadErrores = 0;  // Total de consultas fallidas.
$sentencias = 0;       // Total de consultas ejecutables que se intentaron.
$vistas = [];           // Nombres de vistas encontradas en el respaldo.

// 7. DETECTAR VISTAS DEL RESPALDO
// ---------------------------------------------------------------------------
while (($lineaAnalisis = fgets($archivoSql)) !== false) {
    if (preg_match('/\bVIEW\s+`([^`]+)`/i', $lineaAnalisis, $coincidencia)) {
        $vistas[] = $coincidencia[1]; // Guarda el nombre capturado.
    }
}
rewind($archivoSql); // Vuelve al inicio para la lectura que sí ejecutará SQL.

// 8. PREPARAR EL EJECUTOR DE SENTENCIAS SQL
// ---------------------------------------------------------------------------
$comentarioMultilinea = false; // Marca si se está dentro de /* comentario */.
$temp = '';                    // Acumula líneas hasta terminar una sentencia.

$ejecutar = static function (string $sql) use ($conexion, &$consultasOk, &$errores, &$sentencias, &$cantidadErrores, $vistas): void {
    $sql = trim($sql); // Quita saltos y espacios alrededor de la sentencia.
    if ($sql === '') return; // No enviar sentencias vacías a MySQL.

    if (preg_match('/^(CREATE\\s+DATABASE|DROP\\s+DATABASE|USE)\\b/i', $sql)) return;

    foreach ($vistas as $vista) {
        $vistaSql = preg_quote($vista, '/'); // Escapa caracteres para regex.

        $sql = preg_replace('/^DROP\\s+TABLE\\s+IF\\s+EXISTS\\s+`' . $vistaSql . '`/i', 'DROP VIEW IF EXISTS `' . $vista . '`', $sql);

        if (preg_match('/^INSERT\\s+INTO\\s+`' . $vistaSql . '`/i', $sql)) return;
    }

    $sql = preg_replace('/\\s+DEFINER=`[^`]+`@`[^`]+`/i', '', $sql);

    $sql = preg_replace('/^INSERT\\s+INTO\\b/i', 'REPLACE INTO', $sql);

    $sentencias++; // Cuenta esta consulta como intento de ejecución.
    if ($conexion->query($sql)) {
        $consultasOk++; // Suma una ejecución exitosa.
    } else {
        $cantidadErrores++; // Cuenta el error aunque ya se guarden cinco detalles.

        if (count($errores) < 5) $errores[] = $conexion->error;
    }
};

// 9. LEER Y EJECUTAR EL ARCHIVO SQL
// ---------------------------------------------------------------------------
while (($linea = fgets($archivoSql)) !== false) {
    $linea = trim($linea); // Elimina espacios al inicio/final de esta línea.

    if ($linea === '' || str_starts_with($linea, '--') || str_starts_with($linea, '#')) continue;

    if (str_starts_with($linea, '/*')) $comentarioMultilinea = true;

    if ($comentarioMultilinea) {
        if (str_ends_with($linea, '*/') || str_ends_with($linea, '*/;')) $comentarioMultilinea = false;
        continue;
    }

    $temp .= $linea . "\n";

    if (str_ends_with($linea, ';')) {
        $ejecutar($temp); // Envía la sentencia acumulada a MySQL.
        $temp = '';        // Prepara el acumulador para la siguiente sentencia.
    }
}

if (trim($temp) !== '') $ejecutar($temp);

fclose($archivoSql); // Cierra el archivo de respaldo.

$conexion->query('SET FOREIGN_KEY_CHECKS = 1');

// 10. REGISTRAR Y EVALUAR LA IMPORTACIÓN
// ---------------------------------------------------------------------------
$ok = $sentencias > 0 && $cantidadErrores === 0;

$descripcion = 'Intentó importar "' . $nombreOrigen . '": ' . $consultasOk . ' sentencias correctas';
if ($cantidadErrores > 0) $descripcion .= ', con errores SQL.';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
sesionRenovar();
registrarBitacora($conexion, 'Base de Datos', 'Importar BD', $descripcion);

if ($sentencias === 0) {
    $volverConError('El archivo no contiene sentencias SQL ejecutables.');
}

if (!$ok) {
    $mensaje = 'Importación incompleta: ' . $consultasOk . ' sentencias correctas. ' . ($errores[0] ?? 'Hubo errores SQL.');
    $volverConError($mensaje);
}

// 11. MOSTRAR EL RESULTADO EN EL DASHBOARD
// ---------------------------------------------------------------------------
header('Location: ' . $destino . '?respaldo_estado=importado&respaldo_archivo=' . rawurlencode($nombreOrigen) . '&sentencias=' . $consultasOk);
session_write_close(); // Guarda y libera la sesión antes de terminar la respuesta.
exit;                  // Detiene el modelo después de redirigir.
