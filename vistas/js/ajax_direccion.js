
$(document).ready(function(){

    /*=========================
      CARGAR ESTADOS
    =========================*/
    $.ajax({

        url: "../ajax/ajax_direccion.php",
        type: "POST",

        data: {
            accion: "listarEstados"
        },

        success: function(respuesta){

            $("#selectEstado").html(respuesta);
            const codEstadoGuardado = $("#selectEstado").data("selected");
            if (codEstadoGuardado) {
                $("#selectEstado").val(String(codEstadoGuardado)).trigger("change");
            }

        }

    });

    /*=========================
      ESTADO -> MUNICIPIOS
    =========================*/
    $("#selectEstado").change(function(){

        let cod_est = $(this).val();

        $("#selectMunicipio").html(
            '<option value="">Cargando...</option>'
        );

        $("#selectParroquia").html(
            '<option value="">Seleccione una Parroquia</option>'
        );

        $.ajax({

            url: "../ajax/ajax_direccion.php",
            type: "POST",

            data: {
                accion: "listarMunicipios",
                cod_est: cod_est
            },

            success: function(respuesta){

                $("#selectMunicipio").html(respuesta);
                const codMunicipioGuardado = $("#selectMunicipio").data("selected");
                if (codMunicipioGuardado) {
                    $("#selectMunicipio").val(String(codMunicipioGuardado)).trigger("change");
                }

            }

        });

    });

    /*=========================
      MUNICIPIO -> PARROQUIAS
    =========================*/
    $("#selectMunicipio").change(function(){

        let cod_muni = $(this).val();

        $("#selectParroquia").html(
            '<option value="">Cargando...</option>'
        );

        $.ajax({

            url: "../ajax/ajax_direccion.php",
            type: "POST",

            data: {
                accion: "listarParroquias",
                cod_muni: cod_muni
            },

            success: function(respuesta){

                $("#selectParroquia").html(respuesta);
                const codParroquiaGuardado = $("#selectParroquia").data("selected");
                if (codParroquiaGuardado) {
                    $("#selectParroquia").val(String(codParroquiaGuardado));
                }

            }

        });

    });

});
