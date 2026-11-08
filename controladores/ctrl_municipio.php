<?php
require_once __DIR__ . '/../vistas/includes/guardian.php';
require_once("../modelos/clase_municipio.php");
require_once __DIR__ . "/helpers/bitacora_helper.php";

class MunicipioController
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new MunicipioModel();
    }

    // Obtener todos los estados
    public function obtenerEstados()
    {
        return $this->modelo->obtenerEstados();
    }

    // Guardar municipio
    public function guardar($cod_est, $nombre)
    {
        $resultado = $this->modelo->guardar($cod_est, $nombre);
        if ($resultado) {
            global $conexion;
            registrarBitacora($conexion, 'Municipios', 'Crear', "Registró el municipio \"$nombre\" en el estado \"" . bitacoraNombreDe($conexion, 'estado', $cod_est) . "\".");
        }
        return $resultado;
    }

    // Listar municipios
    public function listar()
    {
        $resultado = $this->modelo->listar();
        global $conexion;
        registrarBitacora($conexion, 'Municipios', 'Consultar', 'Consultó el listado de municipios.');
        return $resultado;
    }

    // Buscar municipio
    public function buscar($cod_muni)
    {
        $resultado = $this->modelo->buscar($cod_muni);
        global $conexion;
        registrarBitacora($conexion, 'Municipios', 'Consultar', "Consultó el municipio ID $cod_muni.");
        return $resultado;
    }

    // Actualizar municipio
    public function actualizar($cod_muni, $cod_est, $nombre)
    {
        global $conexion;
        $antes = bitacoraSnapshot($conexion, 'municipio', $cod_muni);
        $resultado = $this->modelo->actualizar($cod_muni, $cod_est, $nombre);
        if ($resultado) {
            $despues = bitacoraSnapshot($conexion, 'municipio', $cod_muni);
            registrarBitacora($conexion, 'Municipios', 'Editar', 'Actualizó el municipio "' . bitacoraNombre($antes, $cod_muni) . '": ' . bitacoraCambios($antes, $despues));
        }
        return $resultado;
    }

    // Eliminar municipio
    public function eliminar($cod_muni)
    {
        global $conexion;
        $antes = bitacoraSnapshot($conexion, 'municipio', $cod_muni);
        $resultado = $this->modelo->eliminar($cod_muni);
        if ($resultado) {
            registrarBitacora($conexion, 'Municipios', 'Eliminar', 'Eliminó el municipio "' . bitacoraNombre($antes, $cod_muni) . '" (estado "' . ($antes['Estado'] ?? 'desconocido') . '").');
        }
        return $resultado;
    }
}
?>