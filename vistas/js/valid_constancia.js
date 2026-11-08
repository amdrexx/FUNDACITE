// ============================================================================
// ARCHIVO: vistas/js/valid_constancia.js
// DESCRIPCIÓN: Validación del formulario de constancias de trabajo.
//              El salario del trabajador no puede estar vacío.
// ============================================================================
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("formconstancia");
    const salarioInput = document.getElementById("salario_display");

    if (!form || !salarioInput) return;

    function mostrarAlerta(mensaje) { window.FundaciteAlerta.mostrar(mensaje, 'warning'); }

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