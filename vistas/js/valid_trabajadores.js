document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formPersonas");

    const fechaNacimiento = document.getElementById("fecha");
    const edadInput = document.getElementById("edad");

    // ==========================
    // CALCULAR EDAD
    // ==========================
    fechaNacimiento.addEventListener("change", function () {

        if (!this.value) {
            edadInput.value = "";
            return;
        }

        const nacimiento = new Date(this.value + "T00:00:00");
        const hoy = new Date();

        let edad = hoy.getFullYear() - nacimiento.getFullYear();

        const mes = hoy.getMonth() - nacimiento.getMonth();

        if (
            mes < 0 ||
            (mes === 0 && hoy.getDate() < nacimiento.getDate())
        ) {
            edad--;
        }

        edadInput.value = edad + " años";
    });

    // ==========================
    // SOLO NÚMEROS EN CÉDULA
    // ==========================
    document.getElementById("cedula").addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, '');
    });

    // ==========================
    // SOLO NÚMEROS EN TELÉFONO
    // ==========================
    document.getElementById("numeroTelefono").addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, '');
    });

    // ==========================
    // SOLO LETRAS EN NOMBRES
    // ==========================
    document.getElementById("nombres").addEventListener("input", function () {
        this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '');
    });

    // ==========================
    // SOLO LETRAS EN APELLIDOS
    // ==========================
    document.getElementById("apellidos").addEventListener("input", function () {
        this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '');
    });

    // ==========================
    // VALIDACIÓN GENERAL
    // ==========================
    formulario.addEventListener("submit", function (e) {

        let errores = [];

        const tipoDoc = document.getElementById("tipoDoc").value.trim();
        const cedula = document.getElementById("cedula").value.trim();
        const nombres = document.getElementById("nombres").value.trim();
        const apellidos = document.getElementById("apellidos").value.trim();
        const correo = document.getElementById("correoElectronico").value.trim();
        const fechaNac = document.getElementById("fecha").value;
        const telefono = document.getElementById("numeroTelefono").value.trim();
        const fechaIngreso = document.getElementById("fecha_ingreso").value;
        const estadoCivil = document.getElementById("estadoCivil").value;

        const genero = document.querySelector('input[name="genero"]:checked');

        const cargo = document.querySelector('select[name="cargo_id"]').value;
        const estatus = document.querySelector('select[name="estatus_laboral"]').value;

        // ==========================
        // CAMPOS OBLIGATORIOS
        // ==========================

        if (!tipoDoc) errores.push("Tipo de Documento");
        if (!cedula) errores.push("Cédula");
        if (!nombres) errores.push("Nombres");
        if (!apellidos) errores.push("Apellidos");
        if (!genero) errores.push("Género");
        if (!estadoCivil) errores.push("Estado Civil");
        if (!correo) errores.push("Correo Electrónico");
        if (!fechaNac) errores.push("Fecha de Nacimiento");
        if (!telefono) errores.push("Número de Teléfono");
        if (!fechaIngreso) errores.push("Fecha de Ingreso");
        if (!cargo) errores.push("Cargo");
        if (!estatus) errores.push("Estatus Laboral");

        // ==========================
        // VALIDACIÓN DE CAMPOS VACÍOS
        // ==========================

        if (errores.length > 0) {
            e.preventDefault();

            showAlert(
                "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
                false
            );

            return false;
        }

        // ==========================
        // CÉDULA
        // ==========================

        if (!/^\d{6,8}$/.test(cedula)) {
            e.preventDefault();
            showAlert("La cédula debe contener entre 6 y 8 dígitos.", false);
            return false;
        }

        // ==========================
        // TELÉFONO
        // ==========================

        if (!/^0\d{10}$/.test(telefono)) {
            e.preventDefault();
            showAlert("El teléfono debe tener 11 dígitos.", false);
            return false;
        }

        // ==========================
        // CORREO
        // ==========================

        const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!regexCorreo.test(correo)) {
            e.preventDefault();
            showAlert("El correo electrónico no es válido.", false);
            return false;
        }

        // ==========================
        // MAYOR DE EDAD
        // ==========================

        const nacimiento = new Date(fechaNac);
        const hoy = new Date();

        let edad = hoy.getFullYear() - nacimiento.getFullYear();

        const mes = hoy.getMonth() - nacimiento.getMonth();

        if (
            mes < 0 ||
            (mes === 0 && hoy.getDate() < nacimiento.getDate())
        ) {
            edad--;
        }

        if (edad < 18) {
            e.preventDefault();
            showAlert("El trabajador debe ser mayor de edad.", false);
            return false;
        }

        // ==========================
        // FECHA INGRESO
        // ==========================

        const ingreso = new Date(fechaIngreso);

        if (ingreso > hoy) {
            e.preventDefault();
            showAlert("La fecha de ingreso no puede ser futura.", false);
            return false;
        }

    });

});

// ==========================
// ALERTA PERSONALIZADA
// ==========================

function showAlert(message, success = false) {

    const alerta = document.getElementById("customAlert");
    const mensaje = document.getElementById("alertMessage");

    mensaje.innerText = message;

    alerta.classList.remove("hidden");

    const boton = document.querySelector(".alert-box button");

    boton.style.background = success ? "#2e7d32" : "#c62828";
}

function closeAlert() {
    document.getElementById("customAlert").classList.add("hidden");
}

// ==========================
// FECHA DE INGRESO AUTOMÁTICA
// ==========================

document.addEventListener("DOMContentLoaded", function () {
    const fechaIngreso = document.getElementById("fecha_ingreso");

    if (fechaIngreso && !fechaIngreso.value) {
        fechaIngreso.value = new Date().toISOString().split("T")[0];
    }
});