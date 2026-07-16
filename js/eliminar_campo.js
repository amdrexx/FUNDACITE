$(document).ready(function () {
    const contenedor = $("#contenedorParroquias");

    /*
        .off("click") elimina cualquier evento anterior
        que otro archivo haya colocado sobre #btnAgregar.
    */
    $("#btnAgregar")
        .off("click")
        .on("click", function (e) {
            e.preventDefault();

            const nuevoCampo = `
                <div class="field campo-parroquia">
                    <label>Nombre de la parroquia</label>
                    <input
                        type="text"
                        name="parroquia[]"
                        placeholder="Ingrese la parroquia"
                        required
                    >
                </div>
            `;

            contenedor.append(nuevoCampo);
        });

    $("#btnEliminarParroquia")
        .off("click")
        .on("click", function (e) {
            e.preventDefault();

            const campos = contenedor.find(".campo-parroquia");

            if (campos.length > 1) {
                campos.last().remove();
            }
        });
});