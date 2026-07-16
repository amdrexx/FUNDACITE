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
    // SOLO LETRAS EN PRIMA
    // ==========================
    const tipoprimaInput = document.getElementById("prima");

    if (tipoprimaInput) {

        tipoprimaInput.addEventListener("input", function () {
            // elimina números
            this.value = this.value.replace(/[0-9]/g, '');
        });

    }

    // ==========================
    // VALIDACIÓN DEL FORMULARIO
    // ==========================
    form.addEventListener("submit", function (e) {

        let errores = [];

        const cedula = cedulaInput?.value.trim();

        if (!cedula) {
            errores.push("La cédula es obligatoria");
        }
        else if (!/^\d{6,8}$/.test(cedula)) {
            errores.push("La cédula debe tener entre 6 y 8 dígitos");
        }

        if (errores.length > 0) {
            e.preventDefault();
            showAlert(errores.join("\n"));
        }

    });

});

// ==========================
// ALERTA PERSONALIZADA
// ==========================
function showAlert(message, success = false) {
    const alerta = document.getElementById("customAlert");
    const mensaje = document.getElementById("alertMessage");

    if (!alerta || !mensaje) return;

    mensaje.innerText = message;
    alerta.classList.remove("hidden");

    const boton = document.querySelector(".alert-box button");

    if (boton) {
        boton.style.background = success ? "#2e7d32" : "#c62828";
    }
}

function closeAlert() {
    document.getElementById("customAlert")?.classList.add("hidden");
}