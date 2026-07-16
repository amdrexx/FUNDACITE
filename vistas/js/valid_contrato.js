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

        fetch(`/FUNDACITE/controladores/buscar_trabajador.php?cedula=${encodeURIComponent(cedula)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    inputIdTrabajador.value = data.id_trabajador;
                    inputNombreTrabajador.value = data.nombre_completo;
                    inputNombreTrabajador.style.color = "#ccffcc"; // Nombre en verde
                } else {
                    inputIdTrabajador.value = "";
                    inputNombreTrabajador.value = "❌ Trabajador no registrado";
                    inputNombreTrabajador.style.color = "#ffcccc"; // Mensaje en rojo
                    alert("⚠️ La cédula ingresada no coincide con ningún trabajador registrado.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("⚠️ Ocurrió un error al conectar con el servidor.");
            });
    }

    if (btnBuscar) {
        btnBuscar.addEventListener("click", realizarBusqueda);
    }

    if (inputCedula) {
        inputCedula.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                e.preventDefault(); // Evita que envíe el formulario antes de tiempo
                realizarBusqueda();
            }
        });
    }

    // ==========================================
    // 2. VALIDACIONES ANTES DEL ENVÍO
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