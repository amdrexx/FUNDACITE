document.addEventListener("DOMContentLoaded", function () {

    // ==========================
    // FORMULARIO DE REGISTRO
    // ==========================
    const formularioRegistro = document.getElementById("formPersonas");

    if (formularioRegistro) {

        const fechaNacimiento = document.getElementById("fecha");
        const edadInput = document.getElementById("edad");

        // ==========================
        // CALCULAR EDAD
        // ==========================
        if (fechaNacimiento && edadInput) {
            fechaNacimiento.addEventListener("change", function () {

                if (!this.value) {
                    edadInput.value = "";
                    return;
                }

                const nacimiento = new Date(this.value + "T00:00:00");
                const hoy = new Date();

                let edad = hoy.getFullYear() - nacimiento.getFullYear();
                const mes = hoy.getMonth() - nacimiento.getMonth();

                if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                    edad--;
                }

                edadInput.value = edad + " años";
            });
        }

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
        // SOLO NÚMEROS EN TELÉFONO
        // ==========================
        const telefonoInput = document.getElementById("numeroTelefono");
        if (telefonoInput) {
            telefonoInput.addEventListener("input", function () {
                this.value = this.value.replace(/\D/g, '');
            });
        }

        // ==========================
        // SOLO LETRAS EN NOMBRES
        // ==========================
        const nombresInput = document.getElementById("nombres");
        if (nombresInput) {
            nombresInput.addEventListener("input", function () {
                this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '');
            });
        }

        // ==========================
        // SOLO LETRAS EN APELLIDOS
        // ==========================
        const apellidosInput = document.getElementById("apellidos");
        if (apellidosInput) {
            apellidosInput.addEventListener("input", function () {
                this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '');
            });
        }

        // ==========================
        // VALIDACIÓN GENERAL DE REGISTRO
        // ==========================
        formularioRegistro.addEventListener("submit", function (e) {

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

            if (errores.length > 0) {
                e.preventDefault();
                showAlert(
                    "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
                    false
                );
                return false;
            }

            if (!/^\d{6,8}$/.test(cedula)) {
                e.preventDefault();
                showAlert("La cédula debe contener entre 6 y 8 dígitos.", false);
                return false;
            }

            if (!/^0\d{10}$/.test(telefono)) {
                e.preventDefault();
                showAlert("El teléfono debe tener 11 dígitos.", false);
                return false;
            }

            const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regexCorreo.test(correo)) {
                e.preventDefault();
                showAlert("El correo electrónico no es válido.", false);
                return false;
            }

            const nacimiento = new Date(fechaNac);
            const hoy = new Date();

            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();

            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }

            if (edad < 18) {
                e.preventDefault();
                showAlert("El trabajador debe ser mayor de edad.", false);
                return false;
            }

            const ingreso = new Date(fechaIngreso);
            if (ingreso > hoy) {
                e.preventDefault();
                showAlert("La fecha de ingreso no puede ser futura.", false);
                return false;
            }
        });
    }

    // ==========================
    // FORMULARIO DE EDICIÓN
    // ==========================
    const formularioEdicion = document.querySelector('input[name="editar_trabajador"]')?.closest("form");

    if (formularioEdicion) {
        formularioEdicion.addEventListener("submit", function (e) {

            let errores = [];

            const estadoCivil = document.querySelector('select[name="estadoCivil"]')?.value.trim() || '';
            const correo = document.querySelector('input[name="correoElectronico"]')?.value.trim() || '';
            const telefono = document.querySelector('input[name="numeroTelefono"]')?.value.trim() || '';
            const cargo = document.querySelector('select[name="cargo_id"]')?.value || '';
            const estatus = document.querySelector('select[name="estatus_laboral"]')?.value.trim() || '';

            if (!estadoCivil) errores.push("Estado Civil");
            if (!correo) errores.push("Correo Electrónico");
            if (!telefono) errores.push("Número de Teléfono");
            if (!cargo) errores.push("Cargo");
            if (!estatus) errores.push("Estatus Laboral");

            if (errores.length > 0) {
                e.preventDefault();
                showAlert(
                    "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
                    false
                );
                return false;
            }

            const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regexCorreo.test(correo)) {
                e.preventDefault();
                showAlert("El correo electrónico no es válido.", false);
                return false;
            }

            if (!/^\d{11}$/.test(telefono)) {
                e.preventDefault();
                showAlert("El teléfono debe tener 11 dígitos.", false);
                return false;
            }

            // CAMBIO NECESARIO: evitar envío inmediato para que la alerta se vea
            e.preventDefault();
            showAlert("Guardando cambios...", true);

            setTimeout(() => {
                formularioEdicion.submit();
            }, 1200);
        });
    }
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

let redireccionarListado = false;

function closeAlert() {

    document.getElementById("customAlert")?.classList.add("hidden");

    if (redireccionarListado) {
        window.location.href = "/FUNDACITE/vistas/lista_trabajadores.php";
    }
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