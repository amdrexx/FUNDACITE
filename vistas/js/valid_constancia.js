// ============================================================================
// ARCHIVO: vistas/js/valid_constancia.js
// DESCRIPCIÓN: Validación del formulario de constancias de trabajo.
//              El salario del trabajador no puede estar vacío.
// ============================================================================
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("formconstancia");
    const salarioInput = document.getElementById("salario_display");

    if (!form || !salarioInput) return;

    function mostrarAlerta(mensaje) {
        const alerta = document.getElementById("customAlert");
        const texto = document.getElementById("alertMessage");
        if (!alerta || !texto) {
            alert(mensaje);
            return;
        }
        texto.textContent = mensaje;
        alerta.classList.remove("hidden");
    }

    form.addEventListener("submit", function (e) {

        // "Limpiar" también es un botón submit: no se valida.
        const accion = e.submitter ? e.submitter.value : "guardar";
        if (accion === "limpiar") return;

        const salario = salarioInput.value.trim();

        if (salario === "" || salario === "---" || salario === "No registrado") {
            e.preventDefault();
            mostrarAlerta(
                "El trabajador seleccionado no tiene un salario registrado. " +
                "Asígnele un cargo con salario antes de generar la constancia."
            );
        }
    });
});