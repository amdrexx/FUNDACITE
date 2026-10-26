<?php
// ============================================================================
// ARCHIVO: modelos/exportar.php (Modelo / Procesador)
// OBJETIVO: Crear un respaldo SQL de la base configurada para FUNDACITE,
//           guardarlo en Respaldo_BD/ y volver al dashboard con el resultado.
// NOTA: Este modelo abre una conexión directa a MySQL con los mismos datos
//       configurados en la raíz del proyecto. La vista muestra los avisos.
// ============================================================================

// 1. INICIAR LA SESIÓN Y CARGAR LAS DEPENDENCIAS DEL PROYECTO
// ---------------------------------------------------------------------------
// La sesión permite identificar al usuario y comprobar que esté autenticado.
session_start();

// El guardián detiene la petición si no existe una sesión válida o si expiró.
require_once __DIR__ . '/../vistas/includes/guardian.php';


// 2. ABRIR UNA CONEXIÓN DIRECTA A MYSQL
// ---------------------------------------------------------------------------
// Estos valores son los mismos que usa conexion.php para el resto del sistema.
// Se declaran aquí para que este modelo no dependa de incluir otro archivo.
$dbHost = 'localhost';
$dbUsuario = 'root';
$dbPassword = '';
$dbNombre = 'bd_rrhh_fundacite_yaracuy';

// Evita que MySQLi convierta los errores de conexión en excepciones no tratadas.
mysqli_report(MYSQLI_REPORT_OFF);

// Crea la conexión directamente seleccionando la base de datos del proyecto.
$conexion = new mysqli($dbHost, $dbUsuario, $dbPassword, $dbNombre);

// Si el servidor o la base no están disponibles, vuelve al dashboard con detalle.
if ($conexion->connect_error) {
    header('Location: /FUNDACITE/vistas/dashboard.php?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No se pudo conectar con la base de datos: ' . $conexion->connect_error));
    exit;
}

// Define UTF-8 completo para conservar tildes, eñes y otros caracteres.
$conexion->set_charset('utf8mb4');

// Se carga la función que registra acciones de usuario en la bitácora.
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

// Solo un usuario con rol Administrador puede generar respaldos.
requireAdministrador();

// 3. DEFINIR LA PÁGINA A LA QUE REGRESAR Y AVERIGUAR LA BASE ACTIVA
// ---------------------------------------------------------------------------
// La vista del dashboard lee los parámetros de la URL y muestra el aviso.
$destino = '/FUNDACITE/vistas/dashboard.php';

// SELECT DATABASE() devuelve el nombre de la base actualmente seleccionada.
$nombreBdResultado = $conexion->query('SELECT DATABASE()');

// Si la consulta tuvo respuesta, toma la primera columna de su primera fila.
$nombreBd = $nombreBdResultado ? (string) $nombreBdResultado->fetch_row()[0] : '';

// Un respaldo sin base seleccionada no tendría un origen válido.
if ($nombreBd === '') {
    // rawurlencode permite enviar el mensaje dentro de una URL de forma segura.
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No hay una base de datos seleccionada.'));
    exit; // Detiene el modelo después de ordenar la redirección.
}

// 3. PREPARAR LA CARPETA Y EL NOMBRE DEL ARCHIVO DE RESPALDO
// ---------------------------------------------------------------------------
// __DIR__ apunta a modelos/. Al subir un nivel se llega a la raíz del proyecto.
$directorio = __DIR__ . '/../Respaldo_BD/';

// Si no existe Respaldo_BD/, se crea. El tercer argumento crea también padres.
if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No se pudo crear la carpeta Respaldo_BD.'));
    exit;
}

// La fecha y hora reducen la posibilidad de sobrescribir otro respaldo.
$nombreArchivo = $nombreBd . '_' . date('Y-m-d_His') . '.sql';

// Ruta completa en el sistema de archivos del servidor.
$rutaArchivo = $directorio . $nombreArchivo;

// 'wb' abre el archivo para escritura binaria, creándolo desde cero.
// El @ evita imprimir una advertencia de PHP en la respuesta del navegador.
$archivo = @fopen($rutaArchivo, 'wb');

// Si Apache/PHP no tiene permisos de escritura, se informa a la vista.
if (!$archivo) {
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode('No se pudo crear el archivo. Verifique los permisos de Respaldo_BD.'));
    exit;
}

// 4. DEFINIR UNA FUNCIÓN QUE ESCRIBE TODO EL CONTENIDO RECIBIDO
// ---------------------------------------------------------------------------
// fwrite puede escribir menos bytes de los solicitados. Este ciclo continúa
// desde el punto donde quedó hasta completar el texto entero.
$escribir = static function (string $texto) use ($archivo): void {
    $longitud = strlen($texto); // Tamaño del texto, medido en bytes.
    $offset = 0;                // Posición desde la que falta escribir.

    while ($offset < $longitud) { // Continúa hasta guardar todos los bytes.
        $bytes = fwrite($archivo, substr($texto, $offset)); // Escribe lo pendiente.

        // false significa error; cero significa que no hubo avance posible.
        if ($bytes === false || $bytes === 0) {
            throw new RuntimeException('Falló la escritura del archivo de respaldo.');
        }

        $offset += $bytes; // Avanza tantos bytes como realmente se escribieron.
    }
};

// 5. ESCRIBIR CABECERA Y CLASIFICAR LOS OBJETOS DE LA BASE DE DATOS
// ---------------------------------------------------------------------------
// try permite borrar el archivo parcial y devolver un mensaje si falla cualquier
// consulta o escritura de los pasos siguientes.
try {
    // La cabecera documenta la BD y la fecha. Se desactivan claves foráneas
    // durante la restauración para que el orden de carga no bloquee relaciones.
    $escribir("-- Respaldo FUNDACITE\n-- Base de datos: {$nombreBd}\n-- Fecha: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

    // SHOW FULL TABLES informa tanto tablas como vistas y su tipo.
    $resultadoTablas = $conexion->query('SHOW FULL TABLES');
    if (!$resultadoTablas) {
        throw new RuntimeException('No se pudieron consultar las tablas: ' . $conexion->error);
    }

    // Separamos tablas de vistas porque solo las tablas aceptan INSERT normal.
    $tablas = [];
    $vistas = [];
    while ($fila = $resultadoTablas->fetch_row()) {
        // La segunda columna suele ser BASE TABLE o VIEW.
        if (strtoupper((string) ($fila[1] ?? 'BASE TABLE')) === 'VIEW') {
            $vistas[] = $fila[0]; // Guarda el nombre de la vista.
        } else {
            $tablas[] = $fila[0]; // Guarda el nombre de la tabla física.
        }
    }

    // Si no hay ningún objeto, el archivo no tendría nada que respaldar.
    if (!$tablas && !$vistas) {
        throw new RuntimeException('La base de datos no contiene tablas ni vistas para respaldar.');
    }

// 6. EXPORTAR LA ESTRUCTURA Y LOS DATOS DE CADA TABLA
// ---------------------------------------------------------------------------
    // Cada tabla se procesa antes que las vistas para que las tablas base
    // existan cuando el respaldo vuelva a crear las vistas.
    foreach ($tablas as $tabla) {
        // Los nombres de tabla son identificadores SQL, no valores de texto.
        // Duplicar backticks evita cerrar accidentalmente el identificador.
        $tablaSegura = str_replace('`', '``', $tabla);

        // Recupera el CREATE TABLE original desde MySQL.
        $estructura = $conexion->query("SHOW CREATE TABLE `{$tablaSegura}`");
        if (!$estructura) {
            throw new RuntimeException('No se pudo leer la estructura de ' . $tabla . ': ' . $conexion->error);
        }

        // La segunda columna del resultado contiene la sentencia CREATE TABLE.
        $crear = $estructura->fetch_row();

        // Al restaurar, DROP elimina la tabla anterior si ya existía; luego se
        // crea de nuevo con la estructura original.
        $escribir("DROP TABLE IF EXISTS `{$tablaSegura}`;\n" . $crear[1] . ";\n\n");

        // MYSQLI_USE_RESULT entrega filas gradualmente para reducir memoria.
        $datos = $conexion->query("SELECT * FROM `{$tablaSegura}`", MYSQLI_USE_RESULT);
        if (!$datos) {
            throw new RuntimeException('No se pudieron leer los datos de ' . $tabla . ': ' . $conexion->error);
        }

        // Cada registro de la tabla se convierte en una sentencia INSERT.
        while ($registro = $datos->fetch_assoc()) {
            // array_keys devuelve columnas. Cada nombre se delimita con backticks.
            $columnas = array_map(static fn($columna) => '`' . str_replace('`', '``', $columna) . '`', array_keys($registro));

            // NULL SQL se escribe sin comillas. Los demás valores se escapan
            // con la conexión y se rodean de comillas para proteger el SQL.
            $valores = array_map(static fn($valor) => $valor === null ? 'NULL' : "'" . $conexion->real_escape_string($valor) . "'", array_values($registro));

            // Escribe el INSERT completo para este registro.
            $escribir('INSERT INTO `' . $tablaSegura . '` (' . implode(', ', $columnas) . ') VALUES (' . implode(', ', $valores) . ");\n");
        }

        // Libera el resultado MySQL antes de continuar a otra tabla.
        $datos->free();

        // Separa visualmente el contenido de cada tabla en el archivo.
        $escribir("\n");
    }

// 7. EXPORTAR LA DEFINICIÓN DE LAS VISTAS, SIN INSERTAR FILAS EN ELLAS
// ---------------------------------------------------------------------------
    // Las vistas son consultas guardadas. Se reconstruyen con CREATE VIEW;
    // no se exportan sus resultados como si fueran registros almacenados.
    foreach ($vistas as $vista) {
        $vistaSegura = str_replace('`', '``', $vista);

        // Recupera la sentencia CREATE VIEW generada por MySQL.
        $estructuraVista = $conexion->query("SHOW CREATE VIEW `{$vistaSegura}`");
        if (!$estructuraVista) {
            throw new RuntimeException('No se pudo leer la definición de la vista ' . $vista . ': ' . $conexion->error);
        }
        $crearVista = $estructuraVista->fetch_row();

        // Quita un DEFINER específico, como root@localhost. Al restaurar,
        // MySQL usará la cuenta que ejecuta la importación.
        $definicion = preg_replace('/\s+DEFINER=`[^`]+`@`[^`]+`/i', '', $crearVista[1]);

        // Al importar se elimina la vista anterior y se crea con su definición.
        $escribir("DROP VIEW IF EXISTS `{$vistaSegura}`;\n" . $definicion . ";\n\n");
    }

// 8. RESTABLECER CONFIGURACIÓN Y CERRAR EL ARCHIVO
// ---------------------------------------------------------------------------
    // Deja habilitadas las claves foráneas después de completar el respaldo.
    $escribir("SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($archivo); // Cierra y vacía los datos pendientes al disco.

// 9. SI ALGO FALLA, BORRAR EL RESPALDO INCOMPLETO Y MOSTRAR EL ERROR
// ---------------------------------------------------------------------------
} catch (Throwable $error) {
    fclose($archivo);       // Libera el recurso abierto.
    @unlink($rutaArchivo);  // No conserva un respaldo parcial como si fuera válido.

    // Codifica el detalle para transportarlo de forma segura en la URL.
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode($error->getMessage()));
    exit;
}

// 10. REGISTRAR LA EXPORTACIÓN Y MOSTRAR EL AVISO DE ÉXITO EN LA VISTA
// ---------------------------------------------------------------------------
// Se registra el nombre del archivo y las cantidades de tablas/vistas creadas.
registrarBitacora($conexion, 'Base de Datos', 'Exportar BD', 'Creó el respaldo ' . $nombreArchivo . ' (' . count($tablas) . ' tablas y ' . count($vistas) . ' vistas).');

// El dashboard interpreta los parámetros y muestra el resultado gráfico.
header('Location: ' . $destino . '?respaldo_estado=exportado&respaldo_archivo=' . rawurlencode($nombreArchivo));
exit; // No ejecutar más código después de redirigir.
