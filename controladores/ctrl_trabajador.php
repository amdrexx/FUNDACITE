<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vistas/includes/guardian.php';

require_once '../conexion.php';
require_once '../modelos/clase_trabajador.php';
require_once __DIR__ . '/helpers/bitacora_helper.php';

$trabajador = new Trabajador($conexion);

// =====================================
// ELIMINAR LOGICAMENTE TRABAJADOR
// =====================================
if (isset($_POST['eliminar_trabajador'])) {

    $idTrabajador = intval($_POST['id_trabajador'] ?? 0);

    if ($idTrabajador <= 0) {
        $_SESSION['error_eliminacion'] = ["ID de trabajador no válido."];
        header("Location: ../vistas/lista_trabajadores.php");
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'trabajador', $idTrabajador);

    if ($trabajador->eliminarLogicamenteTrabajador($idTrabajador)) {
        registrarBitacora($conexion, 'Trabajadores', 'Eliminar', 'Eliminó lógicamente al trabajador "' . bitacoraNombre($antes, $idTrabajador) . '".');
        $_SESSION['exito_eliminacion'] = "Trabajador eliminado lógicamente correctamente.";
        header("Location: ../vistas/lista_trabajadores.php");
        exit;
    }

    $_SESSION['error_eliminacion'] = ["Error al eliminar lógicamente el trabajador."];
    header("Location: ../vistas/lista_trabajadores.php");
    exit;
}

// =====================================
// LISTAR TRABAJADORES
// =====================================
if (isset($_GET['accion']) && $_GET['accion'] === 'listar') {
    $buscar = trim($_GET['buscar'] ?? '');
    $trabajadores = $trabajador->listarTrabajadores($buscar);

    registrarBitacora($conexion, 'Trabajadores', 'Listar', $buscar !== '' ? "Consultó el listado de trabajadores (búsqueda: \"$buscar\")." : 'Consultó el listado de trabajadores.');

    require_once '../vistas/lista_trabajadores.php';
    exit;
}

// =====================================
// DETECTAR ACCIÓN (GUARDAR O ACTUALIZAR)
// =====================================
$accion = $_POST['accion'] ?? 'guardar';

if ($accion === 'actualizar' || isset($_POST['editar_trabajador'])) {
    // =====================================
    // ACTUALIZAR TRABAJADOR
    // =====================================
    $idTrabajador     = intval($_POST['id_trabajador'] ?? 0);
    $estadoCivil      = trim($_POST['estadoCivil'] ?? '');
    $correo           = trim($_POST['correoElectronico'] ?? '');
    $telefono         = trim($_POST['numeroTelefono'] ?? '');
    $status           = trim($_POST['estatus_laboral'] ?? '');
    $idCargo          = ($_POST['id_cargo'] ?? '') === '' ? null : intval($_POST['id_cargo']);
    $hijos            = filter_var($_POST['hijos'] ?? '', FILTER_VALIDATE_INT);
    $anosServicio     = filter_var($_POST['anos_servicio_administracion_publica'] ?? '', FILTER_VALIDATE_INT);
    $nivelEducacion   = trim($_POST['nivel_educacion'] ?? '');
    $origenFlujo      = trim($_POST['origen_flujo'] ?? '');

    if ($status === 'Retirado' || $status === 'Suspendido') {
        $status = 'Inactivo';
    }

    $idDirActual      = intval($_POST['id_dir'] ?? 0);
    $codPar           = intval($_POST['cod_par'] ?? 0);
    $direccionTexto   = trim($_POST['direccion_texto'] ?? '');

    $errores = [];

    if ($idTrabajador <= 0) {
        $errores[] = "ID de trabajador no válido para actualizar.";
    }
    if (empty($estadoCivil)) {
        $errores[] = "Seleccione el estado civil.";
    }
    if (empty($correo)) {
        $errores[] = "Ingrese el correo electrónico.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no tiene un formato válido.";
    }
    if (empty($telefono)) {
        $errores[] = "Ingrese el número de teléfono.";
    } elseif (!preg_match('/^\d{11}$/', $telefono)) {
        $errores[] = "El teléfono debe tener 11 dígitos.";
    }
    if ($hijos === false || $hijos < 0 || $hijos > 30) {
        $errores[] = "Ingrese una cantidad de hijos entre 0 y 30.";
    }
    if ($anosServicio === false || $anosServicio < 0 || $anosServicio > 70) {
        $errores[] = "Ingrese años de servicio entre 0 y 70.";
    }
    if ($nivelEducacion === '') {
        $errores[] = "Seleccione el nivel de educación.";
    }
    if (empty($status)) {
        $errores[] = "Seleccione el estatus laboral.";
    }
    if (empty($codPar)) {
        $errores[] = "Seleccione la parroquia correspondiente a la dirección.";
    }
    if (empty($direccionTexto)) {
        $errores[] = "Ingrese la dirección exacta.";
    }

    if (!empty($errores)) {
        $_SESSION['error_registro'] = $errores;
        $destinoError = ($origenFlujo === 'contrato') 
            ? "../vistas/registrar_trabajadores.php?id=" . $idTrabajador . "&flujo=contrato" 
            : "../vistas/registrar_trabajadores.php?id=" . $idTrabajador;
        header("Location: " . $destinoError);
        exit;
    }

    $antes = bitacoraSnapshot($conexion, 'trabajador', $idTrabajador);

    $infoActual = $trabajador->obtenerTrabajadorPorId($idTrabajador);

    // Evitar que haya más de un trabajador activo con cargo de Presidente.
    if ($status === 'Activo' && $idCargo !== null) {
        $stmtCargoNuevo = $conexion->prepare('SELECT nombre_cargo FROM CARGO WHERE id_cargo = ? LIMIT 1');
        if ($stmtCargoNuevo) {
            $stmtCargoNuevo->bind_param('i', $idCargo);
            $stmtCargoNuevo->execute();
            $cargoNuevo = $stmtCargoNuevo->get_result()->fetch_assoc();
            $stmtCargoNuevo->close();

            if (stripos((string) ($cargoNuevo['nombre_cargo'] ?? ''), 'presidente') !== false) {
                $sqlPresidenteActivo = "SELECT t.id_trabajador
                    FROM TRABAJADOR t
                    INNER JOIN CARGO c ON c.id_cargo = t.id_cargo
                    WHERE t.status = 'Activo'
                      AND LOWER(c.nombre_cargo) LIKE '%presidente%'
                      AND t.id_trabajador <> ?
                    LIMIT 1";
                $stmtPresidenteActivo = $conexion->prepare($sqlPresidenteActivo);

                if ($stmtPresidenteActivo) {
                    $stmtPresidenteActivo->bind_param('i', $idTrabajador);
                    $stmtPresidenteActivo->execute();
                    $yaExistePresidenteActivo = $stmtPresidenteActivo->get_result()->num_rows > 0;
                    $stmtPresidenteActivo->close();

                    if ($yaExistePresidenteActivo) {
                        $_SESSION['errores'] = [
                            'No se puede activar este trabajador porque ya existe otro Presidente activo. Inactive al Presidente actual antes de continuar.'
                        ];
                        header('Location: ../vistas/editar_trabajador.php?id=' . $idTrabajador);
                        exit;
                    }
                }
            }
        }
    }

    if ($idDirActual <= 0) {
        $idDirActual = $infoActual['id_dir'] ?? 0;
    }

    if ($idDirActual > 0) {
        $direccionOk = $trabajador->actualizarDireccion($idDirActual, $codPar, $direccionTexto);
        $idDir = $idDirActual;
    } else {
        $idDir = $trabajador->crearDireccion($codPar, $direccionTexto);
        $direccionOk = ($idDir !== false);
    }

    if (!$direccionOk) {
        $_SESSION['error_registro'] = ["Error al actualizar la dirección."];
        $destinoError = ($origenFlujo === 'contrato') 
            ? "../vistas/registrar_trabajadores.php?id=" . $idTrabajador . "&flujo=contrato" 
            : "../vistas/registrar_trabajadores.php?id=" . $idTrabajador;
        header("Location: " . $destinoError);
        exit;
    }

    $actualizado = false;
    if (method_exists($trabajador, 'actualizarTrabajador')) {
        $actualizado = $trabajador->actualizarTrabajador(
            $idTrabajador,
            $estadoCivil,
            $telefono,
            $correo,
            $status,
            $hijos,
            $anosServicio,
            $nivelEducacion,
            $idDir,
            $idCargo
        );
    }

    if ($actualizado) {
        $despues = bitacoraSnapshot($conexion, 'trabajador', $idTrabajador);
        registrarBitacora($conexion, 'Trabajadores', 'Editar', 'Actualizó los datos del trabajador "' . bitacoraNombre($antes, $idTrabajador) . '".');
        
        if ($origenFlujo === 'contrato') {
            header("Location: ../vistas/registrar_contrato.php?id_trabajador=" . $idTrabajador);
        } else {
            $_SESSION['exito_edicion'] = "Datos actualizados correctamente.";
            header("Location: ../vistas/lista_trabajadores.php");
        }
        exit;
    } else {
        $_SESSION['error_registro'] = ["Error al actualizar los datos del trabajador."];
        $destinoError = ($origenFlujo === 'contrato') 
            ? "../vistas/registrar_trabajadores.php?id=" . $idTrabajador . "&flujo=contrato" 
            : "../vistas/registrar_trabajadores.php?id=" . $idTrabajador;
        header("Location: " . $destinoError);
        exit;
    }
}

// =====================================
// VALIDAR Y CONSERVAR EL BORRADOR DEL TRABAJADOR PARA EL PASO DEL CONTRATO
// =====================================
$_SESSION['old_input'] = $_POST;

$errores = [];

$tipoDoc         = trim($_POST['tipoDoc'] ?? '');
$cedula          = trim($_POST['cedula'] ?? '');
$nombres         = trim($_POST['nombres'] ?? '');
$apellidos       = trim($_POST['apellidos'] ?? '');
$fechaNacimiento = trim($_POST['fecha'] ?? '');
$genero          = trim($_POST['genero'] ?? '');
$estadoCivil     = trim($_POST['estadoCivil'] ?? '');
$correo          = trim($_POST['correoElectronico'] ?? '');
$telefono        = trim($_POST['numeroTelefono'] ?? '');
$hijos           = filter_var($_POST['hijos'] ?? '', FILTER_VALIDATE_INT);
$anosServicio    = filter_var($_POST['anos_servicio_administracion_publica'] ?? '', FILTER_VALIDATE_INT);
$nivelEducacion  = trim($_POST['nivel_educacion'] ?? '');
$status          = trim($_POST['estatus_laboral'] ?? '');
$idCargo         = intval($_POST['id_cargo'] ?? 0);

if ($status === 'Retirado' || $status === 'Suspendido') {
    $status = 'Inactivo';
}

$codEst          = intval($_POST['cod_est'] ?? 0);
$codMuni         = intval($_POST['cod_muni'] ?? 0);
$codPar          = intval($_POST['cod_par'] ?? 0);
$direccionTexto  = trim($_POST['direccion_texto'] ?? '');

if ($tipoDoc == 'V') {
    $tipoDocumento = 'Cédula';
} elseif ($tipoDoc == 'E') {
    $tipoDocumento = 'Cédula de Extranjería';
} else {
    $tipoDocumento = '';
}

if (empty($tipoDocumento)) $errores[] = "Seleccione el tipo de documento.";
if (empty($cedula)) $errores[] = "Ingrese la cédula.";
elseif (!preg_match('/^\d{6,8}$/', $cedula)) $errores[] = "La cédula debe contener entre 6 y 8 dígitos.";
if (empty($nombres)) $errores[] = "Ingrese los nombres.";
if (empty($apellidos)) $errores[] = "Ingrese los apellidos.";
if (empty($fechaNacimiento)) $errores[] = "Ingrese la fecha de nacimiento.";
if (empty($genero)) $errores[] = "Seleccione el género.";
if (empty($estadoCivil)) $errores[] = "Seleccione el estado civil.";
if (empty($telefono)) $errores[] = "Ingrese el teléfono.";
elseif (!preg_match('/^0\d{10}$/', $telefono)) $errores[] = "El teléfono debe tener 11 dígitos y comenzar por 0.";
if (empty($correo)) $errores[] = "Ingrese el correo.";
elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo electrónico no tiene un formato válido.";
if ($hijos === false || $hijos < 0 || $hijos > 30) $errores[] = "Ingrese una cantidad de hijos entre 0 y 30.";
if ($anosServicio === false || $anosServicio < 0 || $anosServicio > 70) $errores[] = "Ingrese años de servicio entre 0 y 70.";

if (empty($nivelEducacion)) $errores[] = "Seleccione el nivel de educación.";
if (empty($status)) $errores[] = "Seleccione el estatus laboral.";
if ($idCargo <= 0) $errores[] = "Seleccione el cargo a asignar.";
if (empty($codEst)) $errores[] = "Seleccione el estado.";
if (empty($codMuni)) $errores[] = "Seleccione el municipio.";
if (empty($codPar)) $errores[] = "Seleccione la parroquia.";
if (empty($direccionTexto)) $errores[] = "Ingrese la dirección exacta.";

if ($trabajador->existeCedula($cedula)) {
    $errores[] = "La cédula ya se encuentra registrada.";
}

if (!empty($errores)) {
    $_SESSION['error_registro'] = $errores;
    header("Location: ../vistas/registrar_trabajadores.php");
    exit;
}

// Mantener el trabajador como borrador hasta que también se registre su contrato.
$_SESSION['trabajador_pendiente'] = $_POST;
$_SESSION['old_input'] = $_POST;
$_SESSION['token_trabajador_pendiente'] = bin2hex(random_bytes(32));

header("Location: ../vistas/registrar_contrato.php");
exit;
