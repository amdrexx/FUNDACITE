
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
            window.FundaciteAlerta.mostrar(message, success ? 'success' : 'error');
        }

        function closeAlert() { window.FundaciteAlerta.cerrar(false); }
       function editarDireccion(estado, municipio, parroquia, sector) {
    // Muestra la información con el componente personalizado del sistema.
    window.FundaciteAlerta.mostrar(
        "Editar dirección:\n\n" +
        "Estado: " + estado + "\n" +
        "Municipio: " + municipio + "\n" +
        "Parroquia: " + parroquia + "\n" +
        "Sector: " + sector,
        'info',
        { titulo: 'Información de la dirección' }
    );
}

async function eliminarDireccion(estado, municipio, parroquia, sector) {
    const mensaje = "¿Está seguro de eliminar la dirección?\n\n" +
                    "• Estado: " + estado + "\n" +
                    "• Municipio: " + municipio + "\n" +
                    "• Parroquia: " + parroquia + "\n" +
                    "• Sector: " + sector;
                    
    if (await window.FundaciteAlerta.confirmar(mensaje)) {
        window.FundaciteAlerta.mostrar("Dirección eliminada correctamente (simulación).", "success");
        // Aquí podrías hacer una petición AJAX para eliminar por esos campos
        // o recargar la tabla.
    }
}