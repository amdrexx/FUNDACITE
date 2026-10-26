<?php
// ============================================================================
// ARCHIVO: modelos/importar.php (Modelo / Procesador)
// OBJETIVO: Leer y ejecutar un respaldo SQL subido o guardado en Respaldo_BD/,
//           restaurando la base configurada directamente en este modelo.
// NOTA: La vista existente muestra el resultado. Este modelo procesa datos y
//       abre su conexión directa a MySQL y redirige al dashboard; no contiene HTML.
// ============================================================================

// 1. INICIAR LA SESIÓN, CARGAR SEGURIDAD Y VALIDAR EL ROL
// ---------------------------------------------------------------------------
// Inicia la sesión para que los archivos de seguridad puedan leer al usuario.
session_start();

// El guardián exige sesión válida y controla el tiempo de inactividad.
require_once __DIR__ . '/../vistas/includes/guardian.php';
require_once __DIR__ . '/conexion.php'; // Carga la clase de conexión a la BD.

// Roles define requireAdministrador(). Sesion contiene funciones de actividad.
require_once __DIR__ . '/../vistas/includes/roles.php';
require_once __DIR__ . '/../vistas/includes/sesion.php';

// La función de bitácora deja constancia de la restauración.
require_once __DIR__ . '/../controladores/helpers/bitacora_helper.php';

// La restauración de la BD está limitada al rol Administrador.
requireAdministrador();

// 2. PREPARAR LA SESIÓN PARA UNA OPERACIÓN LARGA
// ---------------------------------------------------------------------------
// Se actualiza la actividad justo antes de comenzar la operación.
sesionRenovar();

// Guarda y libera el bloqueo de sesión. Así los keepalive del navegador pueden
// leer/actualizar la sesión mientras PHP procesa el respaldo largo.
session_write_close();

// Continúa el procesamiento aunque el cliente cierre la página durante la carga.
ignore_user_abort(true);

// Solicita que PHP no corte la importación por su límite de tiempo de ejecución.
@set_time_limit(0);

// 3. DEFINIR EL RETORNO Y UNA FUNCIÓN COMÚN PARA ERRORES
// ---------------------------------------------------------------------------
// La vista del dashboard lee los parámetros y presenta un mensaje visual.
$destino = '/FUNDACITE/vistas/dashboard.php';

// Centraliza los errores: vuelve a abrir/renovar la sesión, redirige y termina.
$volverConError = static function (string $mensaje) use ($destino): void {
    // La sesión se cerró antes del trabajo pesado; se abre para el retorno.
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    // El usuario acaba de realizar una operación, por eso se renueva su sesión.
    sesionRenovar();

    // Pasa el mensaje codificado para que el dashboard lo muestre con seguridad.
    header('Location: ' . $destino . '?respaldo_estado=error&respaldo_mensaje=' . rawurlencode($mensaje));
    exit; // No continuar el importador tras producir una respuesta de error.
};

// 4. SELECCIONAR QUÉ ARCHIVO SQL SE VA A IMPORTAR
// ---------------------------------------------------------------------------
// Carpeta permitida para seleccionar un respaldo que ya está en el servidor.
$directorio = __DIR__ . '/../Respaldo_BD/';
$origen = '';      // Ruta completa del archivo que se leerá.
$nombreOrigen = ''; // Nombre que se usará en el mensaje y la bitácora.

// Opción A: el usuario subió un archivo desde su equipo.
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
    // PHP informa el resultado de la carga en el campo error.
    if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $volverConError('No se pudo subir el archivo SQL. Revise el límite de carga del servidor.');
    }

    // basename elimina cualquier ruta enviada junto con el nombre original.
    $nombreOriginal = basename((string) $_FILES['archivo']['name']);

    // Solo se aceptan archivos .sql.
    if (strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)) !== 'sql') {
        $volverConError('El archivo debe tener extensión .sql.');
    }

    // Límite adicional de aplicación: máximo 100 MiB.
    if ((int) $_FILES['archivo']['size'] > 100 * 1024 * 1024) {
        $volverConError('El archivo supera el máximo permitido de 100 MB.');
    }

    // PHP almacena temporalmente la subida; el modelo lee ese archivo temporal.
    $origen = (string) $_FILES['archivo']['tmp_name'];
    $nombreOrigen = $nombreOriginal;

// Opción B: el usuario eligió un respaldo guardado en Respaldo_BD/.
} elseif (!empty($_POST['respaldo'])) {
    // Se acepta solo el nombre, nunca una ruta proporcionada desde el formulario.
    $nombreOrigen = basename(trim((string) $_POST['respaldo']));

    // Compara el nombre original con basename y valida extensión para bloquear
    // valores como ../../otro_archivo.sql.
    if ($nombreOrigen !== trim((string) $_POST['respaldo']) || strtolower(pathinfo($nombreOrigen, PATHINFO_EXTENSION)) !== 'sql') {
        $volverConError('El nombre del respaldo seleccionado no es válido.');
    }

    // Forma la ruta bajo Respaldo_BD/, que es la carpeta autorizada.
    $origen = $directorio . $nombreOrigen;

// Si el formulario no envió ninguna de las dos opciones, se devuelve un error.
} else {
    $volverConError('Seleccione un respaldo guardado o suba un archivo .sql.');
}

// Comprueba existencia, tipo de archivo y permiso de lectura.
if (!is_file($origen) || !is_readable($origen)) {
    $volverConError('No se encontró el archivo o no se puede leer.');
}

// 5. ABRIR CONEXIÓN DIRECTA A MYSQL Y CREAR LA BASE OBJETIVO SI FALTA
// ---------------------------------------------------------------------------
// Usa los mismos parámetros configurados en conexion.php para el proyecto.
// De esta manera el modelo conecta por sí mismo y no necesita incluir ese archivo.
$dbHost = 'localhost';
$dbUsuario = 'root';
$dbPassword = '';
$nombreBd = 'bd_rrhh_fundacite_yaracuy';

// Desactiva excepciones automáticas para poder devolver errores al dashboard.
mysqli_report(MYSQLI_REPORT_OFF);

// Conecta primero al servidor sin elegir una base: esta puede haber sido borrada.
$conexion = new mysqli($dbHost, $dbUsuario, $dbPassword);
if ($conexion->connect_error) {
    $volverConError('No se pudo conectar con el servidor MySQL: ' . $conexion->connect_error);
}

// Configura UTF-8 completo para el respaldo que se va a restaurar.
if (!$conexion->set_charset('utf8mb4')) {
    $volverConError('No se pudo configurar UTF-8 en la conexión: ' . $conexion->error);
}

// Escapa el identificador de la base para usarlo con seguridad en CREATE DATABASE.
$nombreBdSql = '`' . str_replace('`', '``', $nombreBd) . '`';

// La base puede no existir porque se eliminó desde el administrador de MySQL.
// Se crea vacía y luego se selecciona antes de ejecutar el archivo SQL.
if (!$conexion->query("CREATE DATABASE IF NOT EXISTS {$nombreBdSql} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") || !$conexion->select_db($nombreBd)) {
    $volverConError('No se pudo crear o seleccionar la base de datos: ' . $conexion->error);
}

// 6. ABRIR EL ARCHIVO SQL Y PREPARAR LOS CONTADORES
// ---------------------------------------------------------------------------
// 'rb' abre el archivo para lectura binaria, línea por línea.
$archivoSql = fopen($origen, 'rb');
if (!$archivoSql) {
    $volverConError('No se pudo abrir el archivo SQL.');
}

$consultasOk = 0;      // Sentencias SQL ejecutadas correctamente.
$errores = [];         // Hasta cinco mensajes de error para informar al usuario.
$cantidadErrores = 0;  // Total de consultas fallidas.
$sentencias = 0;       // Total de consultas ejecutables que se intentaron.
$vistas = [];           // Nombres de vistas encontradas en el respaldo.

// 7. DETECTAR VISTAS PARA COMPATIBILIDAD CON RESPALDOS ANTIGUOS
// ---------------------------------------------------------------------------
// Se hace una primera lectura rápida para saber qué nombres son vistas antes
// de llegar a sus INSERT. Los respaldos viejos exportaban las vistas como tablas.
while (($lineaAnalisis = fgets($archivoSql)) !== false) {
    // Busca la parte VIEW `nombre` dentro de una sentencia CREATE VIEW.
    if (preg_match('/\bVIEW\s+`([^`]+)`/i', $lineaAnalisis, $coincidencia)) {
        $vistas[] = $coincidencia[1]; // Guarda el nombre capturado.
    }
}
rewind($archivoSql); // Vuelve al inicio para la lectura que sí ejecutará SQL.

// 8. DEFINIR LA FUNCIÓN QUE FILTRA Y EJECUTA UNA SENTENCIA SQL
// ---------------------------------------------------------------------------
$comentarioMultilinea = false; // Marca si se está dentro de /* comentario */.
$temp = '';                    // Acumula líneas hasta terminar una sentencia.

// La función usa la conexión y modifica los contadores definidos arriba.
$ejecutar = static function (string $sql) use ($conexion, &$consultasOk, &$errores, &$sentencias, &$cantidadErrores, $vistas): void {
    $sql = trim($sql); // Quita saltos y espacios alrededor de la sentencia.
    if ($sql === '') return; // No enviar sentencias vacías a MySQL.

    // El respaldo no puede cambiar ni borrar la base activa del sistema.
    if (preg_match('/^(CREATE\\s+DATABASE|DROP\\s+DATABASE|USE)\\b/i', $sql)) return;

    // Convierte dos errores de formato de respaldos antiguos relacionados con
    // vistas en instrucciones compatibles con MySQL.
    foreach ($vistas as $vista) {
        $vistaSql = preg_quote($vista, '/'); // Escapa caracteres para regex.

        // Al repetir una restauración, los dumps antiguos usan DROP TABLE en
        // vistas. Se cambia por DROP VIEW para que la vista previa se elimine.
        $sql = preg_replace('/^DROP\\s+TABLE\\s+IF\\s+EXISTS\\s+`' . $vistaSql . '`/i', 'DROP VIEW IF EXISTS `' . $vista . '`', $sql);

        // Los dumps anteriores incluyen filas ficticias/consultadas de la vista.
        // Una vista se reconstruye con CREATE VIEW, así que este INSERT se omite.
        if (preg_match('/^INSERT\\s+INTO\\s+`' . $vistaSql . '`/i', $sql)) return;
    }

    // Quita el DEFINER del archivo para que MySQL use el usuario conectado.
    $sql = preg_replace('/\\s+DEFINER=`[^`]+`@`[^`]+`/i', '', $sql);

    $sentencias++; // Cuenta esta consulta como intento de ejecución.
    if ($conexion->query($sql)) {
        $consultasOk++; // Suma una ejecución exitosa.
    } else {
        $cantidadErrores++; // Cuenta el error aunque ya se guarden cinco detalles.

        // Limita los mensajes almacenados, pero conserva el total de errores.
        if (count($errores) < 5) $errores[] = $conexion->error;
    }
};

// 9. LEER EL ARCHIVO Y EJECUTAR SUS SENTENCIAS EN ORDEN
// ---------------------------------------------------------------------------
while (($linea = fgets($archivoSql)) !== false) {
    $linea = trim($linea); // Elimina espacios al inicio/final de esta línea.

    // Ignora líneas vacías y comentarios SQL de una sola línea.
    if ($linea === '' || str_starts_with($linea, '--') || str_starts_with($linea, '#')) continue;

    // Detecta el inicio de un comentario multilínea.
    if (str_starts_with($linea, '/*')) $comentarioMultilinea = true;

    // Mientras dure el comentario, no se acumula ni se ejecuta su contenido.
    if ($comentarioMultilinea) {
        // Al encontrar el cierre se permite procesar la próxima línea.
        if (str_ends_with($linea, '*/') || str_ends_with($linea, '*/;')) $comentarioMultilinea = false;
        continue;
    }

    // Agrega la línea a la sentencia actual; esto permite CREATE TABLE multilínea.
    $temp .= $linea . "\n";

    // En el formato generado por el exportador del proyecto, las sentencias
    // terminan en una línea cuyo último carácter es punto y coma.
    if (str_ends_with($linea, ';')) {
        $ejecutar($temp); // Envía la sentencia acumulada a MySQL.
        $temp = '';        // Prepara el acumulador para la siguiente sentencia.
    }
}

// Si el archivo terminó sin punto y coma, se intenta ejecutar lo acumulado.
if (trim($temp) !== '') $ejecutar($temp);

fclose($archivoSql); // Cierra el archivo de respaldo.

// Rehabilita claves foráneas al terminar, incluso si hubo errores SQL.
$conexion->query('SET FOREIGN_KEY_CHECKS = 1');

// 10. DETERMINAR EL RESULTADO Y REGISTRAR LA OPERACIÓN
// ---------------------------------------------------------------------------
// Solo se considera éxito si hubo sentencias y ninguna falló.
$ok = $sentencias > 0 && $cantidadErrores === 0;

// Prepara una descripción legible para la bitácora.
$descripcion = 'Intentó importar "' . $nombreOrigen . '": ' . $consultasOk . ' sentencias correctas';
if ($cantidadErrores > 0) $descripcion .= ', con errores SQL.';

// La sesión se cerró antes de procesar para no bloquear keepalive; se reabre
// ahora para actualizar la actividad y registrar el usuario que hizo la acción.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
sesionRenovar();
registrarBitacora($conexion, 'Base de Datos', 'Importar BD', $descripcion);

// Si no se encontró ninguna sentencia para ejecutar, no es una importación válida.
if ($sentencias === 0) {
    $volverConError('El archivo no contiene sentencias SQL ejecutables.');
}

// Si al menos una sentencia falló, se muestra incompleta y el primer detalle.
if (!$ok) {
    $mensaje = 'Importación incompleta: ' . $consultasOk . ' sentencias correctas. ' . ($errores[0] ?? 'Hubo errores SQL.');
    $volverConError($mensaje);
}

// 11. MOSTRAR EL RESULTADO EXITOSO EN LA VISTA EXISTENTE
// ---------------------------------------------------------------------------
// La Pantalla de inicio o dashboard recibe el archivo y el total de consultas para mostrar el aviso.
header('Location: ' . $destino . '?respaldo_estado=importado&respaldo_archivo=' . rawurlencode($nombreOrigen) . '&sentencias=' . $consultasOk);
session_write_close(); // Guarda y libera la sesión antes de terminar la respuesta.
exit;                  // Detiene el modelo después de redirigir.
