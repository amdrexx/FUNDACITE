$(document).ready(function () {

    const AJAX_URL = "../ajax/ajax_direccion.php";

    if ($("#selectEstado").length === 0) {
        console.error("editar_direccion.js: NO se encontró #selectEstado en el DOM.");
        return;
    }

    // ── Ayudante: error handler específico para cada SELECT ──
    function makeErrorHandler(idSelector, contexto) {
        return function (jqXHR, textStatus, errorThrown) {
            console.error("Error AJAX [" + contexto + "]:", textStatus, errorThrown);
            if (jqXHR.responseText) {
                console.warn("Respuesta del servidor:", jqXHR.responseText.substring(0, 300));
            }
            $(idSelector).html('<option value="">-- Error al cargar — revisa la consola --</option>').prop("disabled", false);
        };
    }

    /*=========================================
      NOTA IMPORTANTE:
      Estado, Municipio y Parroquia ya vienen
      pre-renderizados y pre-seleccionados desde
      PHP (editar_trabajador.php), consultando
      directamente los valores guardados del
      trabajador. Esto evita que los combos se
      vean "en blanco" si el AJAX tarda, falla,
      o el navegador tiene una version cacheada
      del script.

      Este JS SOLO se encarga de la cascada
      cuando el usuario cambia manualmente el
      Estado o el Municipio.
    =========================================*/

    /*=========================
      CARGAR MUNICIPIOS
    =========================*/
    function cargarMunicipios(cod_est, codMuniPreseleccionar, callback) {
        $("#selectMunicipio").prop("disabled", true).html('<option value="">Cargando municipios...</option>');

        $.ajax({
            url: AJAX_URL,
            type: "POST",
            data: {
                accion: "listarMunicipios",
                cod_est: cod_est
            },
            success: function (respuesta) {
                $("#selectMunicipio").html(respuesta).prop("disabled", false);

                if (codMuniPreseleccionar) {
                    $("#selectMunicipio").val(codMuniPreseleccionar);
                }

                if (typeof callback === "function") callback();
            },
            error: makeErrorHandler("#selectMunicipio", "listarMunicipios")
        });
    }

    /*=========================
      CARGAR PARROQUIAS
    =========================*/
    function cargarParroquias(cod_muni, codParPreseleccionar) {
        $("#selectParroquia").prop("disabled", true).html('<option value="">Cargando parroquias...</option>');

        $.ajax({
            url: AJAX_URL,
            type: "POST",
            data: {
                accion: "listarParroquias",
                cod_muni: cod_muni
            },
            success: function (respuesta) {
                $("#selectParroquia").html(respuesta).prop("disabled", false);

                if (codParPreseleccionar) {
                    $("#selectParroquia").val(codParPreseleccionar);
                }
            },
            error: makeErrorHandler("#selectParroquia", "listarParroquias")
        });
    }

    /*=========================
      EVENTOS: cambio manual del usuario
    =========================*/
    $("#selectEstado").on("change", function () {
        var cod_est = $(this).val();
        $("#selectMunicipio").html('<option value="">Seleccione un Municipio</option>').prop("disabled", true);
        $("#selectParroquia").html('<option value="">Seleccione una Parroquia</option>').prop("disabled", true);

        if (cod_est) {
            cargarMunicipios(cod_est, null);
        }
    });

    $("#selectMunicipio").on("change", function () {
        var cod_muni = $(this).val();
        $("#selectParroquia").html('<option value="">Seleccione una Parroquia</option>').prop("disabled", true);

        if (cod_muni) {
            cargarParroquias(cod_muni, null);
        }
    });

});