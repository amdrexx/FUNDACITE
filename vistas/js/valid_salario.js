/*
 * Requiere que la vista defina ANTES de cargar este archivo:
 *   const ULTIMAS    = { base: 'YYYY-MM-DD'|null, cargo: { "id_cargo": 'YYYY-MM-DD' } };
 *   const PLAZO_DIAS = 20;
 */

// =====================================================
// CAMPOS DINÁMICOS SEGÚN EL TIPO DE SALARIO
// (global: la usa el onchange del select en la vista)
// =====================================================
function cambiarTipoSalario() {
    const tipo       = document.getElementById("tipo_salario");
    const campoCargo = document.getElementById("campo-cargo");
    const cargo      = document.getElementById("id_cargo");
    const campoFecha = document.getElementById("campo-fecha");
    const fecha      = document.getElementById("fecha");
    const campoMonto = document.getElementById("campo-monto");
    const monto      = document.getElementById("monto");

    if (!tipo || !campoCargo || !cargo || !campoFecha || !fecha || !campoMonto || !monto) return;

    const hayTipo    = tipo.value === "base" || tipo.value === "cargo";
    const esPorCargo = tipo.value === "cargo";

    // Cargo: solo si es "Sueldo por cargo"
    campoCargo.style.display = esPorCargo ? "flex" : "none";
    cargo.disabled = !esPorCargo;
    cargo.required = esPorCargo;
    if (!esPorCargo) cargo.value = "";

    // Fecha y monto: solo si hay un tipo seleccionado
    campoFecha.style.display = hayTipo ? "flex" : "none";
    fecha.disabled = !hayTipo;
    fecha.required = hayTipo;

    campoMonto.style.display = hayTipo ? "flex" : "none";
    monto.disabled = !hayTipo;
    monto.required = hayTipo;

    if (!hayTipo) {
        fecha.value = "";
        monto.value = "";
    }

    actualizarPlazo();
}

// =====================================================
// VALIDACIÓN DEL PLAZO ENTRE REGISTROS
//  - Salario mínimo: contra el último salario mínimo
//  - Sueldo por cargo: contra el último salario de ESE cargo
// =====================================================
function ultimaFechaSeleccionada() {
    if (typeof ULTIMAS === "undefined") return null;

    const tipo  = document.getElementById("tipo_salario").value;
    const cargo = document.getElementById("id_cargo").value;

    if (tipo === "base") return ULTIMAS.base || null;
    if (tipo === "cargo" && cargo) return (ULTIMAS.cargo && ULTIMAS.cargo[cargo]) || null;
    return null;
}

function fechaMinimaPermitida(ultima) {
    if (!ultima) return null;
    const [y, m, d] = ultima.split("-").map(Number);
    // Date.UTC evita problemas de zona horaria al sumar días
    return new Date(Date.UTC(y, m - 1, d + PLAZO_DIAS)).toISOString().slice(0, 10);
}

function formatoDMY(iso) {
    const [y, m, d] = iso.split("-");
    return d + "/" + m + "/" + y;
}

// Devuelve "" si la fecha cumple el plazo, o el mensaje de error si no
function mensajePlazo() {
    const fecha  = document.getElementById("fecha");
    const ultima = ultimaFechaSeleccionada();
    const minima = fechaMinimaPermitida(ultima);

    if (!fecha || !minima || fecha.disabled || !fecha.value) return "";

    if (fecha.value < minima) {
        const tipo = document.getElementById("tipo_salario").value;
        const de   = (tipo === "base") ? "el salario mínimo" : "este cargo";
        return "Debe esperar " + PLAZO_DIAS + " días desde el último registro de " + de + " ("
            + formatoDMY(ultima) + "). Podrá registrar uno nuevo a partir del "
            + formatoDMY(minima) + ".";
    }
    return "";
}

// Aplica el resultado en el campo y en el aviso. Devuelve true si es válido.
function validarPlazo() {
    const fecha = document.getElementById("fecha");
    const aviso = document.getElementById("aviso-plazo");
    if (!fecha) return true;

    const msg = mensajePlazo();
    fecha.setCustomValidity(msg);

    if (aviso) {
        aviso.textContent = msg;
        aviso.style.display = msg ? "block" : "none";
    }
    return msg === "";
}

// Recalcula la fecha mínima del calendario según el tipo/cargo elegido
function actualizarPlazo() {
    const fecha  = document.getElementById("fecha");
    const minima = fechaMinimaPermitida(ultimaFechaSeleccionada());

    if (fecha) {
        if (minima) fecha.min = minima;
        else fecha.removeAttribute("min");
    }
    validarPlazo();
}

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("formsalario");

    if (!form) return;

    // ==========================
    // CAMPOS DINÁMICOS Y PLAZO
    // ==========================
    const fechaInput = document.getElementById("fecha");
    const cargoSel   = document.getElementById("id_cargo");

    cambiarTipoSalario();

    // Cada cargo tiene su propio plazo: recalcular al cambiar de cargo
    if (cargoSel) cargoSel.addEventListener("change", actualizarPlazo);

    if (fechaInput) {
        fechaInput.addEventListener("change", validarPlazo);
        fechaInput.addEventListener("input", validarPlazo);
    }

    // Al registrar (Guardar), bloquear el envío si no se cumple el plazo
    form.addEventListener("submit", function (e) {
        if (!validarPlazo()) {
            e.preventDefault();
            showAlert(mensajePlazo());
            if (fechaInput) fechaInput.reportValidity();
        }
    });

    form.addEventListener("reset", function () {
        setTimeout(cambiarTipoSalario, 0);
    });

    // ==========================
    // SOLO NÚMEROS EN CÉDULA
    // ==========================
    const cedulaInput = document.getElementById("cedula");

    if (cedulaInput) {
        cedulaInput.addEventListener("input", function () {
            this.value = this.value.replace(/\D/g, '');
        });
    }

    // ==========================
    // VALIDACIÓN MONTO
    // ==========================
    const montoInput = document.getElementById("monto");

    if (montoInput) {
        montoInput.addEventListener("input", function () {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    }

    // ==========================
    // EVITAR PRIMAS REPETIDAS
    // ==========================
    const selects = document.querySelectorAll(".prima");

    function actualizarPrimas() {

        // Mostrar todas las opciones
        selects.forEach(select => {
            [...select.options].forEach(option => {
                option.hidden = false;
            });
        });

        // Obtener las primas seleccionadas
        const seleccionadas = [];

        selects.forEach(select => {
            if (select.value !== "") {
                seleccionadas.push(select.value);
            }
        });

        // Ocultar las primas ya elegidas
        selects.forEach(select => {
            [...select.options].forEach(option => {
                if (
                    option.value !== "" &&
                    seleccionadas.includes(option.value) &&
                    option.value !== select.value
                ) {
                    option.hidden = true;
                }
            });
        });
    }

    selects.forEach(select => {
        select.addEventListener("change", actualizarPrimas);
    });

    actualizarPrimas();

});
// Las alertas se muestran mediante el componente global FundaciteAlerta.
function showAlert(message, success = false) {
    window.FundaciteAlerta.mostrar(message, success ? 'success' : 'error');
}