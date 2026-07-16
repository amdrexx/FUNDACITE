$(document).ready(function () {
    $("#selectEstado").on("change", function () {
        const codEst = $(this).val();

        $("#selectMunicipio").html(
            '<option value="">Seleccione un Municipio</option>'
        );

        if (codEst === "") {
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
            },
            error: function () {
                $("#selectMunicipio").html(
                    '<option value="">Error al cargar municipios</option>'
                );
            }
        });
    });
});