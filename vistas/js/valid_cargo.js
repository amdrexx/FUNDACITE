 document.getElementById("formCargos").addEventListener("submit", function(e) {
            e.preventDefault();

            const nombreCargo = document.getElementById("nombreCargo").value.trim();
            let errores = [];

            if (!nombreCargo) errores.push("Nombre del Cargo");

            if (errores.length > 0) {
                showAlert(
                    "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
                    false
                );
                return;
            }

        
            showAlert("Cargo registrado correctamente", true);

            setTimeout(() => {
                window.location.href = "lista_cargos.html";
            }, 1500);
        });

     
        function showAlert(message, success = false) {
            document.getElementById("customAlert").classList.remove("hidden");
            document.getElementById("alertMessage").innerText = message;

            let btn = document.querySelector(".alert-box button");
            btn.style.background = success ? "#2e7d32" : "#c62828";
        }

        function closeAlert() {
            document.getElementById("customAlert").classList.add("hidden");
        }
        
        function editarCargo(nombre) {
            alert("Editar cargo:\n\nNombre: " + nombre);
            // Para redirigir a un formulario real:
            // window.location.href = "editar_cargo.html?nombre=" + encodeURIComponent(nombre);
        }


        function eliminarCargo(nombre) {
            if (confirm("¿Está seguro de eliminar el cargo \"" + nombre + "\"?")) {
                alert("🗑️ Cargo \"" + nombre + "\" eliminado correctamente (simulación)");
                // Aquí iría la petición real al servidor con el nombre
            }
        }