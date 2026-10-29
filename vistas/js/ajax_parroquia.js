$(document).ready(function () {

    console.log("ajax_parroquia.js cargado");

    function cargarMunicipios(codEst, seleccionar) {

        $("#selectMunicipio").html(
            '<option value="">Seleccione un Municipio</option>'
        );

        if (!codEst) {
            return;
        }

        $.ajax({
            url: "../controladores/ctrl_parroquia.php",
            type: "POST",
            data: {
                accion: "cargarMunicipios",
                cod_est: codEst
            },
            success: function (respuesta) {
                $("#selectMunicipio").html(respuesta);

                if (seleccionar) {
                    $("#selectMunicipio").val(seleccionar);
                }
            },
            error: function () {
                $("#selectMunicipio").html(
                    '<option value="">Error al cargar municipios</option>'
                );
            }
        });
    }

    // Evento normal: cuando el usuario cambia el Estado manualmente
    $("#selectEstado").on("change", function () {
        cargarMunicipios($(this).val(), null);
    });

    // Al cargar la página: si ya hay un Estado preseleccionado (modo edición),
    // disparamos la carga de Municipios y preseleccionamos el guardado
    const estadoInicial = $("#selectEstado").val();
    const municipioGuardado = $("#selectMunicipio").data("selected");

    console.log("Estado inicial:", estadoInicial, "Municipio guardado:", municipioGuardado);

    if (estadoInicial) {
        cargarMunicipios(estadoInicial, municipioGuardado);
    }

});