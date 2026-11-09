<?php
// ============================================================================
// ARCHIVO: modelos/exportar.php (Modelo / Procesador)
// OBJETIVO: Crear un respaldo SQL de la base configurada para FUNDACITE,
//           guardarlo en Respaldo_BD/ y volver al dashboard con el resultado.
// NOTA: Este modelo abre una conexión directa a MySQL con los mismos datos
//       configurados en la raíz del proyecto. La vista muestra los avisos.
// ============================================================================

// 1. INICIAR SESIÓN Y VALIDAR ADMINISTRADOR
// ---------------------------------------------------------------------------
session_start();

require_once __DIR__ . '/../vistas/includes/guardian.php';

// 2. CONECTAR DIRECTAMENTE CON MYSQL
// ---------------------------------------------------------------------------
$dbHost = 'localhost';
$dbUsuario = 'root';
$dbPassword = '';
$dbNombre = 'bd_rrhh_fundacite_yaracuy';

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = new mysqli($dbHost, $dbUsuario, $dbPassword, $dbNombre);

if ($conexion->connect_error) {
    header('Location: /FUNDACITE/vistas/dashboard.php?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No se pudo conectar con la base de datos: ' . $conexion->connect_error));
    exit;
}

$conexion->set_charset('utf8mb4');

require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

requireAdministrador();

// 3. IDENTIFICAR LA BASE DE DATOS ACTIVA
// ---------------------------------------------------------------------------
$destino = '/FUNDACITE/vistas/dashboard.php';

$nombreBdResultado = $conexion->query('SELECT DATABASE()');

$nombreBd = $nombreBdResultado ? (string) $nombreBdResultado->fetch_row()[0] : '';

if ($nombreBd === '') {
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No hay una base de datos seleccionada.'));
    exit; // Detiene el modelo después de ordenar la redirección.
}

// 4. PREPARAR LA CARPETA Y EL ARCHIVO DE RESPALDO
// ---------------------------------------------------------------------------
$directorio = __DIR__ . '/../Respaldo_BD/';

if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No se pudo crear la carpeta Respaldo_BD.'));
    exit;
}

$nombreArchivo = $nombreBd . '_' . date('Y-m-d_His') . '.sql';

$rutaArchivo = $directorio . $nombreArchivo;

$archivo = @fopen($rutaArchivo, 'wb');

if (!$archivo) {
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No se pudo crear el archivo. Verifique los permisos de Respaldo_BD.'));
    exit;
}

// 5. PREPARAR LA ESCRITURA DEL ARCHIVO SQL
// ---------------------------------------------------------------------------
$escribir = static function (string $texto) use ($archivo): void {
    $longitud = strlen($texto); // Tamaño del texto, medido en bytes.
    $offset = 0;                // Posición desde la que falta escribir.

    while ($offset < $longitud) { // Continúa hasta guardar todos los bytes.
        $bytes = fwrite($archivo, substr($texto, $offset)); // Escribe lo pendiente.

        if ($bytes === false || $bytes === 0) {
            throw new RuntimeException('Falló la escritura del archivo de respaldo.');
        }

        $offset += $bytes; // Avanza tantos bytes como realmente se escribieron.
    }
};

// 6. ESCRIBIR LA CABECERA Y OBTENER TABLAS Y VISTAS
// ---------------------------------------------------------------------------
try {
    $escribir("-- Respaldo FUNDACITE\n-- Base de datos: {$nombreBd}\n-- Fecha: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

    $resultadoTablas = $conexion->query('SHOW FULL TABLES');
    if (!$resultadoTablas) {
        throw new RuntimeException('No se pudieron consultar las tablas: ' . $conexion->error);
    }

    $tablas = [];
    $vistas = [];
    while ($fila = $resultadoTablas->fetch_row()) {
        if (strtoupper((string) ($fila[1] ?? 'BASE TABLE')) === 'VIEW') {
            $vistas[] = $fila[0]; // Guarda el nombre de la vista.
        } else {
            $tablas[] = $fila[0]; // Guarda el nombre de la tabla física.
        }
    }

    if (!$tablas && !$vistas) {
        throw new RuntimeException('La base de datos no contiene tablas ni vistas para respaldar.');
    }

// 7. EXPORTAR ESTRUCTURA Y DATOS DE LAS TABLAS
// ---------------------------------------------------------------------------
    foreach ($tablas as $tabla) {
        $tablaSegura = str_replace('`', '``', $tabla);

        $estructura = $conexion->query("SHOW CREATE TABLE `{$tablaSegura}`");
        if (!$estructura) {
            throw new RuntimeException('No se pudo leer la estructura de ' . $tabla . ': ' . $conexion->error);
        }

        $crear = $estructura->fetch_row();

        $escribir("DROP TABLE IF EXISTS `{$tablaSegura}`;\n" . $crear[1] . ";\n\n");

        $datos = $conexion->query("SELECT * FROM `{$tablaSegura}`", MYSQLI_USE_RESULT);
        if (!$datos) {
            throw new RuntimeException('No se pudieron leer los datos de ' . $tabla . ': ' . $conexion->error);
        }

        while ($registro = $datos->fetch_assoc()) {
            $columnas = array_map(static fn($columna) => '`' . str_replace('`', '``', $columna) . '`', array_keys($registro));

            $valores = array_map(static fn($valor) => $valor === null ? 'NULL' : "'" . $conexion->real_escape_string($valor) . "'", array_values($registro));

            $escribir('INSERT INTO `' . $tablaSegura . '` (' . implode(', ', $columnas) . ') VALUES (' . implode(', ', $valores) . ");\n");
        }

        $datos->free();

        $escribir("\n");
    }

// 8. EXPORTAR LA DEFINICIÓN DE LAS VISTAS
// ---------------------------------------------------------------------------
    foreach ($vistas as $vista) {
        $vistaSegura = str_replace('`', '``', $vista);

        $estructuraVista = $conexion->query("SHOW CREATE VIEW `{$vistaSegura}`");
        if (!$estructuraVista) {
            throw new RuntimeException('No se pudo leer la definición de la vista ' . $vista . ': ' . $conexion->error);
        }
        $crearVista = $estructuraVista->fetch_row();

        $definicion = preg_replace('/\s+DEFINER=`[^`]+`@`[^`]+`/i', '', $crearVista[1]);

        $escribir("DROP VIEW IF EXISTS `{$vistaSegura}`;\n" . $definicion . ";\n\n");
    }

// 9. CERRAR EL RESPALDO Y RECUPERARSE DE ERRORES
// ---------------------------------------------------------------------------
    $escribir("SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($archivo); // Cierra y vacía los datos pendientes al disco.

// 10. REGISTRAR Y MOSTRAR EL RESULTADO
// ---------------------------------------------------------------------------
} catch (Throwable $error) {
    fclose($archivo);       // Libera el recurso abierto.
    @unlink($rutaArchivo);  // No conserva un respaldo parcial como si fuera válido.

    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode($error->getMessage()));
    exit;
}

// ---------------------------------------------------------------------------
registrarBitacora($conexion, 'Base de Datos', 'Exportar BD', 'Creó el respaldo ' . $nombreArchivo . ' (' . count($tablas) . ' tablas y ' . count($vistas) . ' vistas).');

header('Location: ' . $destino . '?respaldo_estado=exportado&respaldo_archivo=' . rawurlencode($nombreArchivo));
exit; // No ejecutar más código después de redirigir.
