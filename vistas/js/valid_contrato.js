// ARCHIVO: /FUNDACITE/vistas/js/valid_contrato.js

document.addEventListener("DOMContentLoaded", function () {
    const formulario = document.getElementById("formContratos");
    const selectTipoContrato = document.querySelector("[name='tipo_contrato']");
    const inputFechaContrato = document.querySelector("[name='fecha_contrato']");
    const inputFechaFin = document.querySelector("[name='fecha_fin']");
    const inputIdTrabajador = document.getElementById("id_trabajador");

    // Función auxiliar para mostrar alertas sin romper la vista
    function mostrarAlerta(titulo, mensaje, icono = 'warning') {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: titulo,
                text: mensaje,
                icon: icono,
                confirmButtonColor: '#007bff'
            });
        } else {
            alert(`⚠️ ${titulo}: ${mensaje}`);
        }
    }

    // ==========================================
    // 1. GESTIÓN DINÁMICA DE FECHA DE FINALIZACIÓN
    // ==========================================
    function gestionarFechaFin() {
        if (!selectTipoContrato || !inputFechaFin) return;

        const valor = selectTipoContrato.value.toLowerCase().trim();

        if (valor.includes("indeterminado") || valor.includes("indefinido")) {
            inputFechaFin.value = "";
            inputFechaFin.disabled = true;
            inputFechaFin.removeAttribute("required");
            inputFechaFin.style.backgroundColor = "#e9ecef";
        } else {
            inputFechaFin.disabled = false;
            inputFechaFin.style.backgroundColor = "";
            if (valor !== "") {
                inputFechaFin.setAttribute("required", "required");
            }
        }
    }

    if (selectTipoContrato) {
        selectTipoContrato.addEventListener("change", gestionarFechaFin);
        gestionarFechaFin(); // Ejecución inicial para detectar precargas
    }

    // ==========================================
    // 2. VALIDACIONES ANTES DE GUARDAR EL FORMULARIO
    // ==========================================
    if (formulario) {
        formulario.addEventListener("submit", function (evento) {
            const idTrabajador = inputIdTrabajador ? inputIdTrabajador.value.trim() : "";
            const tipoContrato = selectTipoContrato ? selectTipoContrato.value.trim() : "";
            const fechaContrato = inputFechaContrato ? inputFechaContrato.value.trim() : "";
            const fechaFin = inputFechaFin ? inputFechaFin.value.trim() : "";

            const inputLugar = formulario.querySelector("[name='lugar_trabajo']");
            const inputNomPres = formulario.querySelector("[name='nombre_presidente']");
            const inputCedPres = formulario.querySelector("[name='cedula_presidente']");
            const inputGaceta = formulario.querySelector("[name='gaceta_designacion_presidente']");

            const lugarTrabajo = inputLugar ? inputLugar.value.trim() : "";
            const nombrePresidente = inputNomPres ? inputNomPres.value.trim() : "";
            const cedulaPresidente = inputCedPres ? inputCedPres.value.trim() : "";
            const gaceta = inputGaceta ? inputGaceta.value.trim() : "";

            // A. Verificar que se haya seleccionado un trabajador vía AJAX
            if (!idTrabajador) {
                evento.preventDefault();
                mostrarAlerta("Trabajador Requerido", "Debe buscar y seleccionar un trabajador válido con la cédula antes de guardar.");
                return;
            }

            // B. Verificar campos obligatorios generales
            if (!tipoContrato || !fechaContrato || !lugarTrabajo || !nombrePresidente || !cedulaPresidente || !gaceta) {
                evento.preventDefault();
                mostrarAlerta("Campos Incompletos", "Todos los campos marcados son obligatorios. Rellene el formulario por completo.");
                return;
            }

            // C. Validar Fecha Fin si el contrato no es Indeterminado
            const esIndeterminado = tipoContrato.toLowerCase().includes("indeterminado") || tipoContrato.toLowerCase().includes("indefinido");
            if (!esIndeterminado) {
                if (!fechaFin) {
                    evento.preventDefault();
                    mostrarAlerta("Fecha Faltante", "Debe especificar la Fecha de Finalización para contratos determinados.");
                    return;
                }

                if (fechaContrato && new Date(fechaFin) < new Date(fechaContrato)) {
                    evento.preventDefault();
                    mostrarAlerta("Fechas Inválidas", "La Fecha de Finalización no puede ser anterior a la Fecha de Inicio.");
                    return;
                }
            }

            // D. Validar formato de la Cédula del Presidente
            const regexCedula = /^[VEveVEve]-\d{1,2}\.?\d{3}\.?\d{3}$/;
            if (!regexCedula.test(cedulaPresidente)) {
                evento.preventDefault();
                mostrarAlerta("Formato Incorrecto", "La cédula del presidente debe tener el formato válido (Ejemplo: V-19.817.987).");
                return;
            }
        });
    }
});