/**
 * Validación en el navegador para los nombres de Estados, Municipios y Parroquias.
 * Ubicación: vistas/js/validar_ubicacion.js
 *
 * Se aplica a: input[data-validar-ubicacion] e input[name="parroquia[]"]
 * (este último también cubre los campos que se agregan dinámicamente).
 *
 * Reglas (las mismas del servidor, includes/validar_ubicacion.php):
 *  - Obligatorio, de 3 a 50 caracteres.
 *  - NO se pueden escribir números: solo letras (con tildes y ñ), espacios, punto, guion y apóstrofe.
 *  - Debe comenzar con una letra y contener al menos 3 letras.
 *  - No se pueden escribir más de 2 caracteres iguales seguidos: el tercero no se deja escribir.
 *  - En parroquias: no repetir el mismo nombre dentro del formulario.
 */
(function () {
    "use strict";

    var MIN = 3;
    var MAX = 50;

    var SELECTOR      = 'input[data-validar-ubicacion], input[name="parroquia[]"]';
    var NO_PERMITIDOS = /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ .'\-]/g;
    var PERMITIDOS    = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ .'\-]+$/;
    var INICIO_VALIDO = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/;
    var TRES_IGUALES  = /(.)\1{2,}/gi;
    var LETRAS        = /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g;
    var REPETIDOS     = /(.)\1{2,}/i;

    function normalizar(valor) {
        return valor.replace(/\s+/g, " ").trim();
    }

    function etiquetaDe(input) {
        if (input.dataset.etiqueta) return " " + input.dataset.etiqueta;
        if (input.name === "parroquia[]") return " de la parroquia";
        return "";
    }

    function esDeLista(input) {
        return input.name && input.name.slice(-2) === "[]" && input.form;
    }

    function mensajeError(input) {
        var sujeto = "El nombre" + etiquetaDe(input);
        var v = normalizar(input.value);

        if (v === "") {
            return input.required ? sujeto + " es obligatorio." : "";
        }
        if (!PERMITIDOS.test(v)) {
            return sujeto + " solo puede contener letras, espacios, punto, guion y apóstrofe (no números).";
        }
        if (!INICIO_VALIDO.test(v)) {
            return sujeto + " debe comenzar con una letra.";
        }
        if (v.length < MIN) {
            return sujeto + " debe tener al menos " + MIN + " caracteres.";
        }
        if (v.length > MAX) {
            return sujeto + " no puede tener más de " + MAX + " caracteres.";
        }
        if ((v.match(LETRAS) || []).length < 3) {
            return sujeto + " debe contener al menos 3 letras.";
        }
        if (REPETIDOS.test(v)) {
            return sujeto + " no puede tener el mismo carácter repetido 3 o más veces seguidas.";
        }
        if (esDeLista(input)) {
            var repetido = Array.prototype.some.call(
                input.form.querySelectorAll(SELECTOR),
                function (otro) {
                    return otro !== input &&
                        normalizar(otro.value).toLowerCase() === v.toLowerCase();
                }
            );
            if (repetido) {
                return "Ese nombre ya está escrito en otro campo del formulario.";
            }
        }
        return "";
    }

    function aviso(input) {
        var el = input.nextElementSibling;
        if (!el || !el.classList.contains("aviso-validacion")) {
            el = document.createElement("small");
            el.className = "aviso-validacion";
            el.style.cssText = "display:block;margin-top:4px;color:#ffb3b3;font-size:13px;";
            input.parentNode.insertBefore(el, input.nextSibling);
        }
        return el;
    }

    function validar(input, forzar) {
        var msg = mensajeError(input);
        input.setCustomValidity(msg);

        var el = aviso(input);
        if (msg && (forzar || input.value !== "")) {
            el.textContent = msg;
            el.style.display = "block";
            input.style.borderColor = "#ff6b6b";
        } else {
            el.textContent = "";
            el.style.display = "none";
            input.style.borderColor = "";
        }
    }

    function revalidarGrupo(input) {
        if (!esDeLista(input)) return;
        Array.prototype.forEach.call(input.form.querySelectorAll(SELECTOR), function (otro) {
            if (otro !== input) validar(otro, false);
        });
    }

    // Mientras escribe: quita caracteres no permitidos y valida
    document.addEventListener("input", function (e) {
        var input = e.target;
        if (!input.matches || !input.matches(SELECTOR)) return;

        // Quita números y símbolos, y no deja pasar más de 2 caracteres iguales seguidos
        var limpio = input.value
            .replace(NO_PERMITIDOS, "")
            .replace(/^\s+/, "")
            .replace(TRES_IGUALES, function (m) { return m.slice(0, 2); });
        if (limpio !== input.value) {
            var pos = input.selectionStart;
            var dif = input.value.length - limpio.length;
            input.value = limpio;
            try { input.setSelectionRange(pos - dif, pos - dif); } catch (err) {}
        }

        validar(input, false);
        revalidarGrupo(input);
    });

    // Al salir del campo: ordena los espacios y valida
    document.addEventListener("focusout", function (e) {
        var input = e.target;
        if (!input.matches || !input.matches(SELECTOR)) return;

        input.value = normalizar(input.value);
        validar(input, false);
        revalidarGrupo(input);
    });

    // Si el navegador bloquea el envío, muestra el mensaje del campo inválido
    document.addEventListener("invalid", function (e) {
        var input = e.target;
        if (!input.matches || !input.matches(SELECTOR)) return;
        validar(input, true);
    }, true);

    // Al enviar: deja los valores sin espacios sobrantes
    document.addEventListener("submit", function (e) {
        Array.prototype.forEach.call(e.target.querySelectorAll(SELECTOR), function (input) {
            input.value = normalizar(input.value);
        });
    });

    // Al cargar: valida lo que ya viene escrito (por ejemplo al editar)
    document.addEventListener("DOMContentLoaded", function () {
        Array.prototype.forEach.call(document.querySelectorAll(SELECTOR), function (input) {
            validar(input, false);
        });
    });
})();
