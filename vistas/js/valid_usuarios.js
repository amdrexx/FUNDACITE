document.getElementById("formUsuarios").addEventListener("submit", function (e) {

    const trabajador       = document.getElementById("trabajadorAsociado").value;
    const usuario           = document.getElementById("usuario").value.trim();
    const contrasenaInput   = document.getElementById("contrasena");
    const contrasena        = contrasenaInput.value.trim();
    const tipo               = document.getElementById("tipoUsuario").value;
    const status             = document.getElementById("status").value;

    // En modo edición el campo contraseña no tiene el atributo "required"
    // (se puede dejar en blanco para mantener la actual).
    const esEdicion = !contrasenaInput.hasAttribute("required");

    let errores = [];

    if (!trabajador) errores.push("Trabajador Asociado");
    if (!usuario) errores.push("Usuario");
    if (!esEdicion && !contrasena) errores.push("Contraseña");
    if (!tipo) errores.push("Tipo de Usuario");
    if (!status) errores.push("Estado");

    if (errores.length > 0) {
        // Solo cancelamos el envío cuando realmente falta algo.
        e.preventDefault();
        showAlert(
            "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
            false
        );
        return;
    }

    // Todo válido: NO se cancela el evento.
    // El formulario se envía de verdad (POST) a ../controladores/ctrl_usuario.php.
    // El mensaje de éxito o error real lo mostrará el servidor después de
    // procesar la petición (registro_usuario.php ya lee mensaje_exito / mensaje_error
    // de la sesión y llama a showAlert() automáticamente al cargar la página).
});

function showAlert(message, success = false) {
    document.getElementById("customAlert").classList.remove("hidden");
    document.getElementById("alertMessage").innerText = message;

    const btn = document.querySelector(".alert-box button");
    btn.style.background = success ? "#2e7d32" : "#c62828";
}

function closeAlert() {
    document.getElementById("customAlert").classList.add("hidden");
}