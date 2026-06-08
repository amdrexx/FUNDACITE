// ---------- Cálculo automático de edad al cambiar la fecha de nacimiento ----------
document.getElementById("fecha").addEventListener("change", function() {
    const fechaNacimiento = this.value;
    const campoEdad = document.getElementById("edad");

    if (!fechaNacimiento) {
        campoEdad.value = "";
        return;
    }

    const fechaNac = new Date(fechaNacimiento + "T00:00:00");
    const hoy = new Date();

    let edad = hoy.getFullYear() - fechaNac.getFullYear();
    const mes = hoy.getMonth() - fechaNac.getMonth();

    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
        edad--;
    }

    campoEdad.value = edad >= 0 ? edad : "";
});

// ---------- Solo dígitos en cédula y teléfono ----------
document.getElementById("cedula").addEventListener("input", function() {
    this.value = this.value.replace(/\D/g, '');
});

document.getElementById("numeroTelefono").addEventListener("input", function() {
    this.value = this.value.replace(/\D/g, '');
});

// ---------- Envío del formulario con validación ----------
document.getElementById("formPersonas").addEventListener("submit", function(e) {
    e.preventDefault();

    let tipoDoc           = document.getElementById("tipoDoc").value;
    let cedula            = document.getElementById("cedula").value.trim();
    let numeroTelefono    = document.getElementById("numeroTelefono").value.trim();
    let nombres           = document.getElementById("nombres").value.trim();
    let apellidos         = document.getElementById("apellidos").value.trim();
    let generoInput       = document.querySelector('input[name="genero"]:checked');
    let genero            = generoInput ? generoInput.value : "";
    let estadoCivil       = document.getElementById("estadoCivil").value;
    let correoElectronico = document.getElementById("correoElectronico").value.trim();
    let cargoId           = (document.getElementsByName("cargo_id")[0] || {}).value || "";
    let estatusLaboral    = (document.getElementsByName("estatus_laboral")[0] || {}).value || "";
    let fecha             = document.getElementById("fecha").value;
    let edad              = document.getElementById("edad").value.trim();

    let errores = [];

    if (!tipoDoc) errores.push("Tipo de Documento");
    if (!cedula) errores.push("Cédula");
    if (!numeroTelefono) errores.push("Número de Teléfono");
    if (!nombres) errores.push("Nombres");
    if (!apellidos) errores.push("Apellidos");
    if (!genero) errores.push("Género");
    if (!estadoCivil) errores.push("Estado Civil");
    if (!correoElectronico) errores.push("Correo Electrónico");
    if (!cargoId) errores.push("Cargo");
    if (!estatusLaboral) errores.push("Estatus Laboral");
    if (!fecha) errores.push("Fecha de Nacimiento");
    if (!edad) errores.push("Edad");

    /* ⚠️ Si en tu código real tienes una línea como esta, elimínala o coméntala:
       if (!tipoContratacion) errores.push("Tipo de Contratación");
    */

    if (errores.length > 0) {
        showAlert(
            "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
            false
        );
        return;
    }

    const edadNumerica = parseInt(edad, 10);
    if (isNaN(edadNumerica) || edadNumerica < 18) {
        showAlert("La persona debe tener 18 años o más.", false);
        return;
    }

    showAlert("Registro guardado correctamente", true);

    setTimeout(() => {
        window.location.href = "lista_personas.html";
    }, 1500);
});

// ---------- Mostrar alerta personalizada ----------
function showAlert(message, success = false) {
    document.getElementById("customAlert").classList.remove("hidden");
    document.getElementById("alertMessage").innerText = message;

    let btn = document.querySelector(".alert-box button");
    btn.style.background = success ? "#2e7d32" : "#c62828";
}

function closeAlert() {
    document.getElementById("customAlert").classList.add("hidden");
}

// ---------- Establecer fecha de ingreso automáticamente ----------
const hoy = new Date().toISOString().split('T')[0];
document.getElementById('fecha_ingreso').value = hoy;