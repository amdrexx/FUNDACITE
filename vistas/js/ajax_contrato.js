// ARCHIVO: /FUNDACITE/vistas/js/ajax_contrato.js

document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "btn_buscar_trabajador") {
        e.preventDefault();
        e.stopPropagation();
        realizarBusqueda();
    }
});

document.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && e.target && e.target.id === "cedula_trabajador") {
        e.preventDefault();
        e.stopPropagation();
        realizarBusqueda();
    }
});

function realizarBusqueda() {
    const campoCedula = document.getElementById("cedula_trabajador");

    if (!campoCedula) {
        console.error("No se encontró el campo cedula_trabajador");
        return;
    }

    const cedulaVal = campoCedula.value.trim();

    if (cedulaVal === "") {
        if (typeof Swal !== "undefined") {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Ingresa una cédula.', confirmButtonColor: '#007bff' });
        } else {
            alert("Ingresa una cédula.");
        }
        return;
    }

    fetch(`../controladores/buscar_trabajador.php?cedula=${encodeURIComponent(cedulaVal)}`)
        .then(res => res.json())
        .then(data => {
            const inputId = document.getElementById("id_trabajador");
            const inputNombre = document.getElementById("nombre_trabajador");
            const inputCargo = document.getElementById("cargo_trabajador");

            if (data.success || data.exito) {
                if (inputId) {
                    inputId.value = data.id_trabajador || '';
                }
                
                if (inputNombre) {
                    inputNombre.value = data.nombre_completo || '';
                    inputNombre.style.color = "#155724";
                    inputNombre.style.backgroundColor = "rgba(212, 237, 218, 0.4)";
                }

                if (inputCargo) {
                    inputCargo.value = data.cargo || 'Sin cargo asignado';
                    inputCargo.style.color = "#155724";
                    inputCargo.style.backgroundColor = "rgba(212, 237, 218, 0.4)";
                }

                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Trabajador cargado',
                        text: `${data.nombre_completo} - ${data.cargo}`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            } else {
                if (inputId) inputId.value = '';
                if (inputNombre) {
                    inputNombre.value = '❌ No registrado';
                    inputNombre.style.color = '#721c24';
                    inputNombre.style.backgroundColor = '';
                }
                if (inputCargo) {
                    inputCargo.value = '';
                    inputCargo.style.color = '';
                    inputCargo.style.backgroundColor = '';
                }

                if (typeof Swal !== "undefined") {
                    Swal.fire({ icon: 'error', title: 'No encontrado', text: data.message || 'Cédula no registrada.', confirmButtonColor: '#007bff' });
                }
            }
        })
        .catch(err => {
            console.error("Error crítico en fetch de búsqueda:", err);
        });
}