<?php
// ARCHIVO: /FUNDACITE/controladores/ctrl_contrato.php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../vistas/includes/guardian.php";
requireAdministradorODirector();

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../modelos/clase_contrato.php";
require_once __DIR__ . "/../modelos/clase_trabajador.php";
require_once __DIR__ . "/helpers/bitacora_helper.php";

class ContratoControlador {
    private $modelo;

    public function __construct() {
        global $conexion;
        $this->modelo = new clase_contrato($conexion);
    }

    // =========================================================================
    // GUARDAR NUEVO CONTRATO (Paso 2)
    // =========================================================================
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
            global $conexion;
            $borrador = $_SESSION['trabajador_pendiente'] ?? null;
            $tokenSesion = $_SESSION['token_trabajador_pendiente'] ?? '';
            $tokenFormulario = $_POST['token_trabajador_pendiente'] ?? '';
            $esBorrador = is_array($borrador) && $tokenSesion !== '' && hash_equals($tokenSesion, $tokenFormulario);
            $id_trabajador = (int) ($_POST['id_trabajador'] ?? 0);

            if (!$esBorrador && $id_trabajador <= 0) {
                $_SESSION['error_registro'] = ['No se encontró el borrador del trabajador. Vuelva a completar sus datos.'];
                header('Location: ../vistas/registrar_trabajadores.php');
                exit();
            }
            $_SESSION['old_contrato'] = $_POST;

            $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
            $fecha_contrato = trim($_POST['fecha_contrato'] ?? '');
            $fecha_fin = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
            $lugar_trabajo = trim($_POST['lugar_trabajo'] ?? '');
            $nombre_presidente = trim($_POST['nombre_presidente'] ?? '');
            $cedula_presidente = trim($_POST['cedula_presidente'] ?? '');

            $esIndeterminado = stripos($tipo_contrato, 'indefinido') !== false || stripos($tipo_contrato, 'indeterminado') !== false;
            if ($esIndeterminado) {
                $fecha_fin = null;
            }

            if (
                empty($tipo_contrato) ||
                empty($fecha_contrato) || 
                empty($lugar_trabajo) || 
                empty($nombre_presidente) || 
                empty($cedula_presidente)
            ) {
                $_SESSION['error_contrato'] = ['Complete todos los datos requeridos del contrato.'];
                header("Location: ../vistas/registrar_contrato.php?status=error");
                exit();
            }

            if (!$esIndeterminado && $fecha_fin === null) {
                $_SESSION['error_contrato'] = ['La fecha de finalización es obligatoria para los contratos a tiempo determinado.'];
                $destino = $esBorrador
                    ? '../vistas/registrar_contrato.php?status=error'
                    : '../vistas/registrar_contrato.php?id_trabajador=' . $id_trabajador . '&status=error';
                header('Location: ' . $destino);
                exit();
            }

            if ($fecha_fin !== null && $fecha_fin < $fecha_contrato) {
                $_SESSION['error_contrato'] = ['La fecha de finalización no puede ser anterior a la fecha de inicio del contrato.'];
                $destino = $esBorrador
                    ? '../vistas/registrar_contrato.php?status=error'
                    : '../vistas/registrar_contrato.php?id_trabajador=' . $id_trabajador . '&status=error';
                header('Location: ' . $destino);
                exit();
            }

            // Mantener compatible la creación de contratos para trabajadores ya registrados.
            if (!$esBorrador) {
                try {
                    if (!$this->modelo->registrarContrato(
                        $id_trabajador,
                        $tipo_contrato,
                        $fecha_contrato,
                        $fecha_fin,
                        $lugar_trabajo,
                        $nombre_presidente,
                        $cedula_presidente
                    )) {
                        throw new RuntimeException($this->modelo->ultimoError ?: 'No se pudo guardar el contrato.');
                    }
                    unset($_SESSION['old_contrato']);
                    registrarBitacora($conexion, 'Contratos', 'Crear', "Registró un contrato ($tipo_contrato) para el trabajador \"" . bitacoraNombreDe($conexion, 'trabajador', $id_trabajador) . "\".");
                    header('Location: ../vistas/lista_contrato.php?status=success');
                } catch (Throwable $e) {
                    error_log('No se pudo registrar el contrato: ' . $e->getMessage());
                    $_SESSION['error_contrato'] = [$e->getMessage()];
                    header("Location: ../vistas/registrar_contrato.php?id_trabajador={$id_trabajador}&status=error");
                }
                exit();
            }

            $transaccionIniciada = false;
            $trabajadoresDesasignados = [];
            try {
                $tipoDoc = trim($borrador['tipoDoc'] ?? '');
                $tipoDocumento = $tipoDoc === 'V' ? 'Cédula' : ($tipoDoc === 'E' ? 'Cédula de Extranjería' : '');
                if ($tipoDocumento === '') {
                    throw new RuntimeException('El tipo de documento del trabajador no es válido.');
                }

                $conexion->begin_transaction();
                $transaccionIniciada = true;
                $modeloTrabajador = new Trabajador($conexion);

                if ($modeloTrabajador->existeCedula(trim($borrador['cedula'] ?? ''))) {
                    throw new RuntimeException('La cédula ya se encuentra registrada.');
                }

                $idDir = $modeloTrabajador->crearDireccion(
                    (int) ($borrador['cod_par'] ?? 0),
                    trim($borrador['direccion_texto'] ?? '')
                );
                if (!$idDir) {
                    throw new RuntimeException('No se pudo guardar la dirección del trabajador.');
                }

                $id_trabajador = $modeloTrabajador->registrarTrabajador(
                    $tipoDocumento,
                    trim($borrador['cedula'] ?? ''),
                    trim($borrador['nombres'] ?? ''),
                    trim($borrador['apellidos'] ?? ''),
                    trim($borrador['fecha'] ?? ''),
                    (int) ($borrador['hijos'] ?? 0),
                    (int) ($borrador['anos_servicio_administracion_publica'] ?? 0),
                    trim($borrador['nivel_educacion'] ?? ''),
                    trim($borrador['genero'] ?? ''),
                    trim($borrador['estadoCivil'] ?? ''),
                    trim($borrador['numeroTelefono'] ?? ''),
                    trim($borrador['correoElectronico'] ?? ''),
                    trim($borrador['tipo_personal'] ?? ''),
                    trim($borrador['estatus_laboral'] ?? 'Activo'),
                    $idDir,
                    (int) ($borrador['id_cargo'] ?? 0)
                );
                if (!$id_trabajador) {
                    throw new RuntimeException('No se pudo guardar el trabajador.');
                }

                // Presidente y Director tienen un solo titular: se desasigna el cargo
                // anterior, sin cambiar el estatus laboral ni eliminar al trabajador.
                $idCargoNuevo = (int) ($borrador['id_cargo'] ?? 0);
                $trabajadoresDesasignados = $modeloTrabajador->desasignarOcupantesPreviosCargoUnico(
                    $idCargoNuevo,
                    $id_trabajador,
                    isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null
                );

                if (!$modeloTrabajador->sincronizarHistorialAsignacionCargo(
                    $id_trabajador,
                    $idCargoNuevo > 0 ? $idCargoNuevo : null,
                    isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null
                )) {
                    throw new RuntimeException('No se pudo guardar el historial de asignación del cargo.');
                }

                if (!$this->modelo->registrarContrato(
                    $id_trabajador,
                    $tipo_contrato,
                    $fecha_contrato,
                    $fecha_fin,
                    $lugar_trabajo,
                    $nombre_presidente,
                    $cedula_presidente
                )) {
                    throw new RuntimeException($this->modelo->ultimoError ?: 'No se pudo guardar el contrato.');
                }

                $conexion->commit();
                $transaccionIniciada = false;

                unset($_SESSION['trabajador_pendiente'], $_SESSION['token_trabajador_pendiente'], $_SESSION['old_input'], $_SESSION['old_contrato']);
                try {
                    registrarBitacora($conexion, 'Trabajadores', 'Crear', 'Registró al trabajador ' . trim(($borrador['nombres'] ?? '') . ' ' . ($borrador['apellidos'] ?? '')) . ' (CI ' . ($borrador['cedula'] ?? '') . ').');
                    foreach ($trabajadoresDesasignados as $trabajadorAnterior) {
                        $nombreAnterior = trim(($trabajadorAnterior['nombres'] ?? '') . ' ' . ($trabajadorAnterior['apellidos'] ?? ''));
                        registrarBitacora(
                            $conexion,
                            'Trabajadores',
                            'Desasignar cargo',
                            'Desasignó el cargo a ' . $nombreAnterior . ' (CI ' . ($trabajadorAnterior['cedula'] ?? '') . ') al registrar un nuevo titular; se conservó su estatus laboral.'
                        );
                    }
                    registrarBitacora($conexion, 'Contratos', 'Crear', "Registró un contrato ($tipo_contrato) para el trabajador \"" . bitacoraNombreDe($conexion, 'trabajador', $id_trabajador) . "\".");
                } catch (Throwable $eBitacora) {
                    error_log('No se pudo registrar la bitácora de trabajador y contrato: ' . $eBitacora->getMessage());
                }
                $_SESSION['exito_registro'] = 'Trabajador registrado con éxito.';
                header('Location: ../vistas/lista_trabajadores.php?status=success');
            } catch (Throwable $e) {
                if ($transaccionIniciada) {
                    $conexion->rollback();
                }
                error_log('No se pudo completar el registro del trabajador y contrato: ' . $e->getMessage());
                $_SESSION['error_contrato'] = [$e->getMessage()];
                header('Location: ../vistas/registrar_contrato.php?status=error');
            }
            exit();
        }
    }

    // =========================================================================
    // ACTUALIZAR CONTRATO EXISTENTE
    // =========================================================================
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
            $id_contrato = intval($_POST['id_contrato'] ?? 0);
            $id_trabajador = intval($_POST['id_trabajador'] ?? 0);
            $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
            $fecha_contrato = trim($_POST['fecha_contrato'] ?? '');
            $fecha_fin = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
            $lugar_trabajo = trim($_POST['lugar_trabajo'] ?? '');
            $nombre_presidente = trim($_POST['nombre_presidente'] ?? '');
            $cedula_presidente = trim($_POST['cedula_presidente'] ?? '');

            $esIndeterminado = stripos($tipo_contrato, 'indefinido') !== false || stripos($tipo_contrato, 'indeterminado') !== false;
            if ($esIndeterminado) {
                $fecha_fin = null;
            }

            if (!$esIndeterminado && $fecha_fin === null) {
                $_SESSION['old_contrato'] = $_POST;
                $_SESSION['error_contrato'] = ['La fecha de finalización es obligatoria para los contratos a tiempo determinado.'];
                header("Location: ../vistas/editar_contrato.php?id={$id_contrato}&status=error");
                exit();
            }

            if ($fecha_fin !== null && $fecha_contrato !== '' && $fecha_fin < $fecha_contrato) {
                $_SESSION['old_contrato'] = $_POST;
                $_SESSION['error_contrato'] = ['La fecha de finalización no puede ser anterior a la fecha de inicio del contrato.'];
                header("Location: ../vistas/editar_contrato.php?id={$id_contrato}&status=error");
                exit();
            }

            if ($id_contrato <= 0 || $id_trabajador <= 0) {
                header("Location: ../vistas/lista_contrato.php?status=error");
                exit();
            }

            global $conexion;
            $antes = bitacoraSnapshot($conexion, 'contrato', $id_contrato);

            $resultado = $this->modelo->actualizarContrato(
                $id_contrato,
                $id_trabajador,
                $tipo_contrato,
                $fecha_contrato, 
                $fecha_fin, 
                $lugar_trabajo, 
                $nombre_presidente, 
                $cedula_presidente
            );

            if ($resultado) {
                $despues = bitacoraSnapshot($conexion, 'contrato', $id_contrato);
                registrarBitacora($conexion, 'Contratos', 'Editar', 'Actualizó el contrato "' . bitacoraNombre($antes, $id_contrato) . '": ' . bitacoraCambios($antes, $despues));
                header("Location: ../vistas/lista_contrato.php?status=updated");
            } else {
                header("Location: ../vistas/editar_contrato.php?id={$id_contrato}&status=error");
            }
            exit();
        }
    }

    // =========================================================================
    // ELIMINAR CONTRATO
    // =========================================================================
    public function borrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
            $id_contrato = intval($_GET['id'] ?? 0);
            if ($id_contrato > 0) {
                global $conexion;
                $antes = bitacoraSnapshot($conexion, 'contrato', $id_contrato);
                $this->modelo->eliminarContrato($id_contrato);

                if (!empty($antes) && empty(bitacoraSnapshot($conexion, 'contrato', $id_contrato))) {
                    registrarBitacora($conexion, 'Contratos', 'Eliminar', 'Eliminó el contrato "' . bitacoraNombre($antes, $id_contrato) . '".');
                }
            }
            header("Location: ../vistas/lista_contrato.php?status=deleted");
            exit();
        }
    }

    // =========================================================================
    // DESCARTAR EL BORRADOR DEL REGISTRO SECUENCIAL
    // =========================================================================
    public function cancelarRegistro() {
        unset(
            $_SESSION['trabajador_pendiente'],
            $_SESSION['token_trabajador_pendiente'],
            $_SESSION['old_input'],
            $_SESSION['old_contrato']
        );
        header("Location: ../vistas/registrar_trabajadores.php");
        exit();
    }

    // =========================================================================
    // MÉTODOS DE CONSULTA
    // =========================================================================
    public function mostrarContratos() {
        global $conexion;
        registrarBitacora($conexion, 'Contratos', 'Listar', 'Consultó el listado de contratos.');

        if (method_exists($this->modelo, 'obtenerContratos')) {
            return $this->modelo->obtenerContratos();
        } elseif (method_exists($this->modelo, 'listarContratos')) {
            return $this->modelo->listarContratos();
        }
        return [];
    }

    public function buscarPorId($id) {
        if (method_exists($this->modelo, 'obtenerPorId')) {
            $resultado = $this->modelo->obtenerPorId($id);
        } elseif (method_exists($this->modelo, 'buscarPorId')) {
            $resultado = $this->modelo->buscarPorId($id);
        } else {
            $resultado = false;
        }
        global $conexion;
        registrarBitacora($conexion, 'Contratos', 'Consultar', "Consultó el contrato ID $id.");
        return $resultado;
    }
}

// INSTANCIACIÓN GLOBAL
$controlador = new ContratoControlador();
$controladorContrato = $controlador;

// ENRUTADOR
if (isset($_POST['accion'])) {
    if ($_POST['accion'] === 'guardar') {
        $controlador->guardar();
    } elseif ($_POST['accion'] === 'actualizar') {
        $controlador->actualizar();
    }
} elseif (isset($_GET['accion'])) {
    if ($_GET['accion'] === 'eliminar') {
        $controlador->borrar();
    } elseif ($_GET['accion'] === 'cancelar_registro') {
        $controlador->cancelarRegistro();
    }
}
?>
