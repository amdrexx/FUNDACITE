/*
 * Paginación del lado cliente para todas las tablas de listado (table.tabla).
 *
 * Usa la misma apariencia y comportamiento que la paginación de Parroquias:
 *   - 10 registros por página (cambiable con data-por-pagina en la <table>).
 *   - Texto "Mostrando 1 - 10 de 34 registros" + botones ‹ 1 2 … 5 ›.
 *
 * Atributos opcionales en <table class="tabla">:
 *   data-sin-paginar      → no paginar esta tabla.
 *   data-por-pagina="20"  → registros por página.
 *   data-etiqueta="cargos"→ texto del contador (por defecto "registros").
 *
 * Las tablas de Municipios y Parroquias (#moduloUbicacion) ya tienen su propia
 * paginación con buscador y filtro, por eso se omiten aquí.
 *
 * Todas las filas siguen en el DOM (solo se ocultan), así que los formularios
 * con campos dentro de la tabla envían todos sus valores.
 */
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    var POR_PAGINA = 10;
    var reducirMovimiento = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    function esFilaVacia(fila) {
        // Fila de "No existen registros": una sola celda que abarca varias columnas.
        if (fila.id === "filaVacia" || fila.hasAttribute("data-no-paginar")) {
            return true;
        }
        return fila.cells.length === 1 && fila.cells[0].colSpan > 1;
    }

    function rangoPaginas(actual, total) {
        var delta = 2;
        var rango = [];
        var salida = [];
        var anterior = 0;
        var i;

        for (i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= actual - delta && i <= actual + delta)) {
                rango.push(i);
            }
        }

        rango.forEach(function (pagina) {
            if (anterior) {
                if (pagina - anterior === 2) {
                    salida.push(anterior + 1);
                } else if (pagina - anterior !== 1) {
                    salida.push("...");
                }
            }
            salida.push(pagina);
            anterior = pagina;
        });

        return salida;
    }

    function iniciar(tabla) {
        var cuerpo = tabla.tBodies[0];
        if (!cuerpo) {
            return;
        }

        var filas = Array.prototype.filter.call(cuerpo.rows, function (fila) {
            return !esFilaVacia(fila);
        });

        // Sin registros: no hay nada que paginar (se queda el mensaje de vacío).
        if (filas.length === 0) {
            return;
        }

        var porPagina = parseInt(tabla.getAttribute("data-por-pagina"), 10) || POR_PAGINA;
        var etiqueta = tabla.getAttribute("data-etiqueta") || "registros";
        var paginaActual = 1;

        // Pie con contador y botones (mismas clases que Parroquias).
        var pie = document.createElement("div");
        pie.className = "paginacion-fila";
        pie.style.gridColumn = "1 / -1";

        var info = document.createElement("span");
        info.className = "paginacion-info";
        info.setAttribute("aria-live", "polite");

        var nav = document.createElement("div");
        nav.className = "paginacion";
        nav.setAttribute("role", "navigation");
        nav.setAttribute("aria-label", "Paginación de " + etiqueta);

        pie.appendChild(info);
        pie.appendChild(nav);

        // Se coloca debajo del marco con scroll (si responsive-ui.js ya lo creó).
        var referencia = tabla.closest(".table-scroll") || tabla;
        referencia.parentNode.insertBefore(pie, referencia.nextSibling);

        function boton(texto, pagina, deshabilitado, esActual, aria) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "pag-btn" + (esActual ? " actual" : "");
            btn.innerHTML = texto;
            btn.setAttribute("aria-label", aria);

            if (esActual) {
                btn.setAttribute("aria-current", "page");
            }

            if (deshabilitado) {
                btn.disabled = true;
            } else {
                btn.setAttribute("data-pagina", pagina);
            }

            return btn;
        }

        function dibujarBotones(totalPaginas) {
            nav.innerHTML = "";

            if (totalPaginas <= 1) {
                return;
            }

            nav.appendChild(boton("&#8249;", paginaActual - 1, paginaActual === 1, false, "Página anterior"));

            rangoPaginas(paginaActual, totalPaginas).forEach(function (pagina) {
                if (pagina === "...") {
                    var puntos = document.createElement("span");
                    puntos.className = "pag-puntos";
                    puntos.textContent = "…";
                    nav.appendChild(puntos);
                } else {
                    nav.appendChild(boton(String(pagina), pagina, false, pagina === paginaActual, "Página " + pagina));
                }
            });

            nav.appendChild(boton("&#8250;", paginaActual + 1, paginaActual === totalPaginas, false, "Página siguiente"));
        }

        function mostrar() {
            var total = filas.length;
            var totalPaginas = Math.max(1, Math.ceil(total / porPagina));

            if (paginaActual > totalPaginas) {
                paginaActual = totalPaginas;
            }

            var inicio = (paginaActual - 1) * porPagina;
            var fin = Math.min(inicio + porPagina, total);

            filas.forEach(function (fila, i) {
                fila.style.display = (i >= inicio && i < fin) ? "" : "none";
            });

            info.textContent = "Mostrando " + (inicio + 1) + " - " + fin + " de " +
                total.toLocaleString("es-VE") + " " + etiqueta;

            dibujarBotones(totalPaginas);
        }

        nav.addEventListener("click", function (e) {
            var btn = e.target.closest(".pag-btn");

            if (!btn || btn.disabled || !btn.getAttribute("data-pagina")) {
                return;
            }

            paginaActual = parseInt(btn.getAttribute("data-pagina"), 10);
            mostrar();

            // Solo se desplaza si el inicio de la tabla quedó fuera de la pantalla.
            var marco = tabla.closest(".table-scroll") || tabla;
            if (marco.getBoundingClientRect().top < 0) {
                marco.scrollIntoView({ block: "start", behavior: reducirMovimiento ? "auto" : "smooth" });
            }

            // El botón se vuelve a dibujar: se devuelve el foco al botón de la página actual.
            var actual = nav.querySelector(".pag-btn.actual");
            if (actual && document.activeElement === document.body) {
                actual.focus({ preventScroll: true });
            }
        });

        // Si la tabla está dentro de un formulario con campos validados (por ejemplo
        // los porcentajes), un campo inválido en otra página no puede recibir el foco
        // y el envío quedaría bloqueado sin aviso: se muestra la página de ese campo.
        var formulario = tabla.closest("form");
        if (formulario) {
            formulario.addEventListener("invalid", function (e) {
                var fila = e.target.closest ? e.target.closest("tr") : null;
                var indice = fila ? filas.indexOf(fila) : -1;

                if (indice === -1) {
                    return;
                }

                var pagina = Math.floor(indice / porPagina) + 1;
                if (pagina !== paginaActual) {
                    paginaActual = pagina;
                    mostrar();
                }
            }, true);
        }

        mostrar();
    }

    Array.prototype.forEach.call(document.querySelectorAll("table.tabla"), function (tabla) {
        if (tabla.hasAttribute("data-sin-paginar") || tabla.closest("#moduloUbicacion")) {
            return;
        }
        iniciar(tabla);
    });
});
