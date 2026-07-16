document.addEventListener("DOMContentLoaded", function () {
    const contenedor = document.getElementById("contenedorParroquias");
    const btnAgregar = document.getElementById("btnAgregar");
    const btnEliminar = document.getElementById("btnEliminarParroquia");

    if (!contenedor || !btnAgregar || !btnEliminar) {
        return;
    }

    btnAgregar.addEventListener("click", function () {
        const nuevoCampo = document.createElement("div");

        nuevoCampo.className = "field campo-parroquia";

        nuevoCampo.innerHTML = `
            <label>Nombre de la parroquia</label>
            <input
                type="text"
                name="parroquia[]"
                placeholder="Ingrese la parroquia"
                required
            >
        `;

        contenedor.appendChild(nuevoCampo);
    });

    btnEliminar.addEventListener("click", function () {
        const campos = contenedor.querySelectorAll(".campo-parroquia");

        if (campos.length > 1) {
            campos[campos.length - 1].remove();
        }
    });
});