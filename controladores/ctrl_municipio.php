<?php
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
            registrarBitacora($conexion, 'Municipios', 'Crear', "Registró el municipio \"$nombre\" (estado ID $cod_est).");
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
        $resultado = $this->modelo->actualizar($cod_muni, $cod_est, $nombre);
        if ($resultado) {
            global $conexion;
            registrarBitacora($conexion, 'Municipios', 'Editar', "Actualizó el municipio ID $cod_muni a \"$nombre\".");
        }
        return $resultado;
    }

    // Eliminar municipio
    public function eliminar($cod_muni)
    {
        $resultado = $this->modelo->eliminar($cod_muni);
        if ($resultado) {
            global $conexion;
            registrarBitacora($conexion, 'Municipios', 'Eliminar', "Eliminó el municipio ID $cod_muni.");
        }
        return $resultado;
    }
}
?>