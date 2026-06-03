// BOTON AÑADIR
    let btnAgregar = document.getElementById("btnAgregar");

    // CONTENEDOR
    let contenedor = document.getElementById("contenedorParroquias");

    // EVENTO CLICK
    btnAgregar.addEventListener("click", function() {

        // CREAR NUEVO DIV
        let nuevoCampo = document.createElement("div");

        nuevoCampo.classList.add("field");

        // CONTENIDO DEL NUEVO CAMPO
        nuevoCampo.innerHTML = `
            <label>Nombre de la parroquia</label>
            <input type="text" name="parroquia[]" placeholder="Ingrese la parroquia">
        `;

        // AGREGAR ABAJO
        contenedor.appendChild(nuevoCampo);

    });
