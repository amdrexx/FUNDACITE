/* Validaciones del módulo Convenio (mismo estilo que valid_trabajadores.js). */
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formConvenios") || document.getElementById("formEditarConvenio");
    if (!formulario) return;

    const gremioInput = document.getElementById("nombre_gremio");
    const diasInput = document.getElementById("dias_disfrute");
    const inicioInput = document.getElementById("fecha_inicio");
    const vencInput = document.getElementById("fecha_vencimiento");

    function showAlert(message, success) {
        if (window.FundaciteAlerta) {
            window.FundaciteAlerta.mostrar(message, success ? "success" : "error");
        } else {
            alert(message);
        }
    }

    // ==========================
    // SOLO LETRAS, NÚMEROS Y SIGNOS BÁSICOS EN EL GREMIO
    // ==========================
    gremioInput.addEventListener("input", function () {
        this.value = this.value
            .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 .,'()&\/-]/g, "")
            .replace(/(.)\1{2,}/gi, "$1$1");
    });

    // ==========================
    // SOLO NÚMEROS ENTEROS EN DÍAS DE DISFRUTE
    // ==========================
    diasInput.addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "").slice(0, 3);
    });

    // ==========================
    // RANGO DE FECHAS (el vencimiento no puede ser anterior al inicio)
    // ==========================
    function actualizarRango() {
        vencInput.min = inicioInput.value || "";
    }
    inicioInput.addEventListener("change", actualizarRango);
    actualizarRango();

    // ==========================
    // VALIDACIÓN GENERAL
    // ==========================
    formulario.addEventListener("submit", function (e) {

        let errores = [];

        const gremio = gremioInput.value.trim();
        const dias = diasInput.value.trim();
        const inicio = inicioInput.value;
        const venc = vencInput.value;

        if (!gremio) errores.push("Nombre del Gremio");
        if (!dias) errores.push("Días de Disfrute");
        if (!inicio) errores.push("Fecha de Inicio");
        if (!venc) errores.push("Fecha de Vencimiento");

        if (gremio && gremio.length < 3) errores.push("Nombre del Gremio: debe tener al menos 3 caracteres");
        if (dias && (!/^\d+$/.test(dias) || Number(dias) < 1 || Number(dias) > 255)) {
            errores.push("Días de Disfrute: indique un entero entre 1 y 255");
        }
        if (inicio && venc && venc < inicio) {
            errores.push("Fecha de Vencimiento: no puede ser anterior a la fecha de inicio");
        }

        if (errores.length > 0) {
            e.preventDefault();
            showAlert("Corrige los siguientes campos o datos:\n\n• " + [...new Set(errores)].join("\n• "), false);
            return false;
        }
    });
});
