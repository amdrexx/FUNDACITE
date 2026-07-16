
   document.addEventListener("DOMContentLoaded", function() {
            const btnGuardar = document.getElementById("btnGuardar");

            btnGuardar.addEventListener("click", function(e) {
                e.preventDefault();

                
                const estado    = document.getElementById("estado").value.trim();
                const municipio = document.getElementById("municipio").value.trim();
                const parroquia = document.getElementById("parroquia").value.trim();
                const sector    = document.getElementById("sector").value.trim();

                let errores = [];

                if (!estado) errores.push("Estado");
                if (!municipio) errores.push("Municipio");
                if (!parroquia) errores.push("Parroquia");
                if (!sector) errores.push("Sector");

                if (errores.length > 0) {
                    showAlert(
                        "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
                        false
                    );
                    return;
                }

                
                showAlert("Dirección registrada correctamente ", true);

        
                setTimeout(() => {
                    window.location.href = "lista_direcciones.html";
                }, 1500);
            });
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
       function editarDireccion(estado, municipio, parroquia, sector) {
    // Muestra los datos en un alert (simulación)
    alert("Editar dirección:\n\n" +
          "Estado: " + estado + "\n" +
          "Municipio: " + municipio + "\n" +
          "Parroquia: " + parroquia + "\n" +
          "Sector: " + sector);
                 "&sector=" + encodeURIComponent(sector);
}

function eliminarDireccion(estado, municipio, parroquia, sector) {
    const mensaje = "¿Está seguro de eliminar la dirección?\n\n" +
                    "• Estado: " + estado + "\n" +
                    "• Municipio: " + municipio + "\n" +
                    "• Parroquia: " + parroquia + "\n" +
                    "• Sector: " + sector;
                    
    if (confirm(mensaje)) {
  
        alert(" Dirección eliminada correctamente (simulación)");
        // Aquí podrías hacer una petición AJAX para eliminar por esos campos
        // o recargar la tabla.
    }
}