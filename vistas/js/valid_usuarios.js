 
        document.getElementById("formUsuarios").addEventListener("submit", function(e) {
            e.preventDefault();

            // Obtener valores
            const trabajador = document.getElementById("trabajadorAsociado").value;
            const usuario    = document.getElementById("usuario").value.trim();
            const contrasena = document.getElementById("contrasena").value.trim();
            const tipo       = document.getElementById("tipoUsuario").value;

            let errores = [];

            if (!trabajador) errores.push("Trabajador Asociado");
            if (!usuario) errores.push("Usuario");
            if (!contrasena) errores.push("Contraseña");
            if (!tipo) errores.push("Tipo de Usuario");

            if (errores.length > 0) {
                showAlert(
                    "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
                    false
                );
                return;
            }

    
            showAlert("Usuario registrado correctamente", true);

            // Redirección a la lista de usuarios
            setTimeout(() => {
                window.location.href = "lista_usuarios.html";
            }, 1500);
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
        function editarUsuario(usuario) {
    alert(" Editar usuario:\n\nUsuario: " + usuario);
    // Redirigir a la página de edición (ejemplo):
    // window.location.href = "editar_usuario.html?usuario=" + encodeURIComponent(usuario);
        }

        // Eliminar usuario (simulación con confirmación)
        function eliminarUsuario(usuario) {
            if (confirm("¿Está seguro de eliminar el usuario \"" + usuario + "\"?")) {
                alert("Usuario \"" + usuario + "\" eliminado correctamente (simulación)");
                // Aquí iría la petición real al servidor para eliminar
            }
        }