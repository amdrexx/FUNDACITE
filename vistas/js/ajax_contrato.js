// ARCHIVO: /FUNDACITE/vistas/js/validar_contrato.js

document.addEventListener("DOMContentLoaded", function () {
    const formulario = document.getElementById("formContratos");
    const btnBuscar = document.getElementById("btn_buscar_trabajador");
    const inputCedula = document.getElementById("cedula_trabajador");
    const inputIdTrabajador = document.getElementById("id_trabajador");
    const inputNombreTrabajador = document.getElementById("nombre_trabajador");

    // ==========================================
    // 1. LÓGICA DEL BUSCADOR EN TIEMPO REAL (AJAX)
    // ==========================================
    function realizarBusqueda() {
        const cedula = inputCedula.value.trim();

        if (cedula === "") {
            alert("⚠️ Por favor, ingrese una cédula primero.");
            return;
        }

        console.log("Iniciando búsqueda AJAX para la cédula:", cedula);

        // Usamos una ruta relativa directa para evitar problemas de carpetas locales
        fetch(`../controladores/buscar_trabajador.php?cedula=${encodeURIComponent(cedula)}`)
            .then(response => {
                console.log("Respuesta del servidor recibida:", response);
                if (!response.ok) {
                    throw new Error(`Error HTTP! Estado: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Datos JSON procesados:", data);
                if (data.success) {
                    // Si lo encuentra, asignamos los valores
                    inputIdTrabajador.value = data.id_trabajador;
                    inputNombreTrabajador.value = data.nombre_completo;
                    inputNombreTrabajador.style.color = "#ccffcc"; // Nombre en verde
                    console.log("Trabajador cargado con éxito:", data.nombre_completo);
                } else {
                    // Si no existe en la BD
                    inputIdTrabajador.value = "";
                    inputNombreTrabajador.value = "❌ Trabajador no registrado";
                    inputNombreTrabajador.style.color = "#ffcccc"; // Mensaje en rojo
                    alert("⚠️ La cédula ingresada no coincide con ningún trabajador registrado.");
                }
            })
            .catch(error => {
                console.error("Error detallado en la petición AJAX:", error);
                alert("⚠️ Ocurrió un error al conectar con el controlador de búsqueda. Revisa la consola (F12).");
            });
    }

    // Escuchar clic en el botón "Buscar"
    if (btnBuscar) {
        btnBuscar.addEventListener("click", function(e) {
            e.preventDefault(); // Evitamos cualquier comportamiento extraño
            realizarBusqueda();
        });
    } else {
        console.error("No se encontró el botón con ID 'btn_buscar_trabajador' en el HTML.");
    }

    // Escuchar tecla Enter en el input de la cédula
    if (inputCedula) {
        inputCedula.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                e.preventDefault(); // Evita que se intente enviar el formulario completo
                realizarBusqueda();
            }
        });
    } else {
        console.error("No se encontró el campo con ID 'cedula_trabajador' en el HTML.");
    }

    // ==========================================
    // 2. VALIDACIONES ANTES DEL ENVÍO DEL FORMULARIO
    // ==========================================
    if (formulario) {
        formulario.addEventListener("submit", function (evento) {
            const idTrabajador = inputIdTrabajador.value.trim();
            const tipoContrato = formulario.querySelector("[name='tipo_contrato']").value.trim();
            const fechaContrato = formulario.querySelector("[name='fecha_contrato']").value.trim();
            const lugarTrabajo = formulario.querySelector("[name='lugar_trabajo']").value.trim();
            const nombrePresidente = formulario.querySelector("[name='nombre_presidente']").value.trim();
            const cedulaPresidente = formulario.querySelector("[name='cedula_presidente']").value.trim();
            const gaceta = formulario.querySelector("[name='gaceta_designacion_presidente']").value.trim();

            // Validar que se haya buscado y seleccionado un trabajador real
            if (!idTrabajador) {
                evento.preventDefault();
                alert("⚠️ Debe buscar y seleccionar un trabajador válido usando el campo de cédula antes de guardar.");
                return;
            }

            // Validar campos vacíos en general
            if (!tipoContrato || !fechaContrato || !lugarTrabajo || !nombrePresidente || !cedulaPresidente || !gaceta) {
                evento.preventDefault();
                alert("⚠️ Todos los campos son obligatorios. Rellene el formulario por completo.");
                return;
            }

            // Validar formato de Cédula del Presidente
            const regexCedula = /^[VEveVEve]-\d{1,2}\.?\d{3}\.?\d{3}$/;
            if (!regexCedula.test(cedulaPresidente)) {
                evento.preventDefault();
                alert("⚠️ El formato de la Cédula del Presidente debe ser válido (Ejemplo: V-19.817.987).");
                return;
            }
        });
    }
});