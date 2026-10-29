<?php
/**
 * Validación compartida para los nombres de Estados, Municipios y Parroquias.
 * Ubicación: vistas/includes/validar_ubicacion.php
 *
 * Reglas:
 *  - Obligatorio.
 *  - Entre 3 y 50 caracteres (después de quitar espacios sobrantes).
 *  - Sin números: solo letras (con tildes y ñ), espacios, punto, guion y apóstrofe.
 *  - Debe comenzar con una letra y contener al menos 3 letras.
 *  - No puede repetir el mismo carácter 3 o más veces seguidas (aaa, ---, etc.).
 */

if (!function_exists('normalizarNombreUbicacion')) {

    /** Quita espacios al inicio/fin y reduce los espacios múltiples a uno solo. */
    function normalizarNombreUbicacion($nombre)
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $nombre));
    }

    /**
     * Devuelve '' si el nombre es válido, o el mensaje de error si no lo es.
     * $etiqueta completa el mensaje: "del estado", "del municipio", "de la parroquia".
     */
    function validarNombreUbicacion($nombre, $etiqueta = '')
    {
        $min = 3;
        $max = 50;

        $nombre = normalizarNombreUbicacion($nombre);
        $sujeto = 'El nombre' . ($etiqueta !== '' ? ' ' . $etiqueta : '');
        $letras = 'A-Za-zÁÉÍÓÚÜÑáéíóúüñ';

        if ($nombre === '') {
            return $sujeto . ' es obligatorio.';
        }

        if (!preg_match('/^[' . $letras . ' .\'\-]+$/u', $nombre)) {
            return $sujeto . ' solo puede contener letras, espacios, punto, guion y apóstrofe (no números).';
        }

        if (!preg_match('/^[' . $letras . ']/u', $nombre)) {
            return $sujeto . ' debe comenzar con una letra.';
        }

        $largo = mb_strlen($nombre, 'UTF-8');

        if ($largo < $min) {
            return $sujeto . ' debe tener al menos ' . $min . ' caracteres.';
        }

        if ($largo > $max) {
            return $sujeto . ' no puede tener más de ' . $max . ' caracteres.';
        }

        if (preg_match_all('/[' . $letras . ']/u', $nombre) < 3) {
            return $sujeto . ' debe contener al menos 3 letras.';
        }

        if (preg_match('/(.)\1{2,}/iu', $nombre)) {
            return $sujeto . ' no puede tener el mismo carácter repetido 3 o más veces seguidas.';
        }

        return '';
    }
}
