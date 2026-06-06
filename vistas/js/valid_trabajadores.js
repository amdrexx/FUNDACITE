 $(document).ready(function() {
         
            $('#persona_id').select2({
                placeholder: "Escriba la cédula o nombre...",
                allowClear: true,
                width: '100%'
            });

            // Colocar la fecha de hoy en "Fecha de Ingreso"
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_ingreso').value = hoy;
        });

        document.addEventListener("DOMContentLoaded", function() {
            const btn = document.getElementById("btnRegistrar");

            btn.addEventListener("click", function(e) {
                e.preventDefault();

                // Obtener valores
                const persona = document.getElementById("persona_id").value;
                const cargo = document.querySelector("select[name='cargo_id']").value;
                const fecha = document.getElementById("fecha_ingreso").value;
                const contrato = document.querySelector("select[name='tipo_contrato']").value;
                const estatus = document.querySelector("select[name='estatus_laboral']").value;

                let errores = [];

                if (!persona) errores.push("Persona (Cédula)");
                if (!cargo) errores.push("Cargo");
                if (!fecha) errores.push("Fecha de Ingreso");
                if (!contrato) errores.push("Tipo de Contratación");
                if (!estatus) errores.push("Estatus Laboral");

              
                if (errores.length > 0) {
                    showAlert(
                        "Debes completar los siguientes campos:\n\n• " + errores.join("\n• "),
                        false
                    );
                    return;
                }

                
                showAlert("Trabajador registrado correctamente", true);

               
                setTimeout(() => {
                    window.location.href = "lista_trabajadores.html";
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
         
     function editarPersona(cedula) {
            // Aquí redirigirías a la página de edición o abrirías un modal
            alert("Editar persona con cédula: " + cedula);
            // Ejemplo: window.location.href = "editar_persona.html?cedula=" + cedula;
        }

        function eliminarPersona(cedula) {
            if (confirm("¿Está seguro de eliminar a la persona con cédula " + cedula + "?")) {
                // Lógica para eliminar
                alert("Persona eliminada (simulación)");
                // Aquí podrías recargar la tabla o hacer una petición al servidor
            }
        }