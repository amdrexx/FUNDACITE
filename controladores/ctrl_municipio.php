<?php
require_once("../modelos/clase_municipio.php");

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
        return $this->modelo->guardar($cod_est, $nombre);
    }

    // Listar municipios
    public function listar()
    {
        return $this->modelo->listar();
    }

    // Buscar municipio
    public function buscar($cod_muni)
    {
        return $this->modelo->buscar($cod_muni);
    }

    // Actualizar municipio
    public function actualizar($cod_muni, $cod_est, $nombre)
    {
        return $this->modelo->actualizar($cod_muni, $cod_est, $nombre);
    }

    // Eliminar municipio
    public function eliminar($cod_muni)
    {
        return $this->modelo->eliminar($cod_muni);
    }
}
?>