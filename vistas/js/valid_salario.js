
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("formsalario");

    if (!form) return;

    // ==========================
    // SOLO NÚMEROS EN CÉDULA
    // ==========================
    const cedulaInput = document.getElementById("cedula");

    if (cedulaInput) {
        cedulaInput.addEventListener("input", function () {
            this.value = this.value.replace(/\D/g, '');
        });
    }

    // ==========================
    // VALIDACIÓN MONTO
    // ==========================
    const montoInput = document.getElementById("monto");

    if (montoInput) {
        montoInput.addEventListener("input", function () {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    }

    // ==========================
    // EVITAR PRIMAS REPETIDAS
    // ==========================
    const selects = document.querySelectorAll(".prima");

    function actualizarPrimas() {

        // Mostrar todas las opciones
        selects.forEach(select => {
            [...select.options].forEach(option => {
                option.hidden = false;
            });
        });

        // Obtener las primas seleccionadas
        const seleccionadas = [];

        selects.forEach(select => {
            if (select.value !== "") {
                seleccionadas.push(select.value);
            }
        });

        // Ocultar las primas ya elegidas
        selects.forEach(select => {
            [...select.options].forEach(option => {
                if (
                    option.value !== "" &&
                    seleccionadas.includes(option.value) &&
                    option.value !== select.value
                ) {
                    option.hidden = true;
                }
            });
        });
    }

    selects.forEach(select => {
        select.addEventListener("change", actualizarPrimas);
    });

    actualizarPrimas();

});
// Las alertas se muestran mediante el componente global FundaciteAlerta.
function showAlert(message, success = false) {
    window.FundaciteAlerta.mostrar(message, success ? 'success' : 'error');
}