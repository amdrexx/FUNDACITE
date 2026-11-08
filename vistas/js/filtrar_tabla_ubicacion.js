// Buscador + filtro por estado + paginación del lado cliente para los
// módulos de ubicación (Municipios y Parroquias).
document.addEventListener("DOMContentLoaded", function () {

    var contenedor = document.getElementById("moduloUbicacion");

    if (!contenedor) {
        return;
    }

    var buscador = document.getElementById("buscadorRegistros");
    var filtroEstado = document.getElementById("filtroEstado");
    var cuerpo = document.getElementById("listaRegistros");
    var paginacion = document.getElementById("paginacion");
    var info = document.getElementById("infoRegistros");
    var filaVacia = document.getElementById("filaVacia");

    if (!buscador || !filtroEstado || !cuerpo || !paginacion || !info) {
        return;
    }

    var filas = Array.prototype.slice.call(cuerpo.querySelectorAll("tr[data-estado]"));
    var porPagina = parseInt(contenedor.getAttribute("data-por-pagina"), 10) || 10;
    var etiqueta = contenedor.getAttribute("data-etiqueta") || "registros";
    var paginaActual = 1;

    function normalizar(texto) {
        return texto.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
    }

    function filtrar() {
        var consulta = normalizar(buscador.value);
        var estado = filtroEstado.value;

        return filas.filter(function (fila) {
            var coincideTexto = consulta === "" || normalizar(fila.getAttribute("data-busqueda") || "").indexOf(consulta) !== -1;
            var coincideEstado = estado === "" || fila.getAttribute("data-estado") === estado;
            return coincideTexto && coincideEstado;
        });
    }

    function rangoPaginas(totalPaginas) {
        var rango = [];
        var resultado = [];
        var delta = 2;
        var anterior = 0;
        var i;

        for (i = 1; i <= totalPaginas; i++) {
            if (i === 1 || i === totalPaginas || (i >= paginaActual - delta && i <= paginaActual + delta)) {
                rango.push(i);
            }
        }

        rango.forEach(function (pagina) {
            if (anterior) {
                if (pagina - anterior === 2) {
                    resultado.push(anterior + 1);
                } else if (pagina - anterior !== 1) {
                    resultado.push("...");
                }
            }
            resultado.push(pagina);
            anterior = pagina;
        });

        return resultado;
    }

    function boton(texto, pagina, deshabilitado, esActual) {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "pag-btn" + (esActual ? " actual" : "");
        btn.innerHTML = texto;

        if (deshabilitado) {
            btn.disabled = true;
        } else {
            btn.setAttribute("data-pagina", pagina);
        }

        return btn;
    }

    function renderizarPaginacion(totalPaginas) {
        paginacion.innerHTML = "";

        if (totalPaginas <= 1) {
            return;
        }

        paginacion.appendChild(boton("&#8249;", paginaActual - 1, paginaActual === 1, false));

        rangoPaginas(totalPaginas).forEach(function (pagina) {
            if (pagina === "...") {
                var puntos = document.createElement("span");
                puntos.className = "pag-puntos";
                puntos.textContent = "…";
                paginacion.appendChild(puntos);
            } else {
                paginacion.appendChild(boton(String(pagina), pagina, false, pagina === paginaActual));
            }
        });

        paginacion.appendChild(boton("&#8250;", paginaActual + 1, paginaActual === totalPaginas, false));
    }

    function renderizar() {
        var visibles = filtrar();
        var total = visibles.length;
        var totalPaginas = Math.max(1, Math.ceil(total / porPagina));

        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }

        var inicio = (paginaActual - 1) * porPagina;
        var fin = Math.min(inicio + porPagina, total);

        filas.forEach(function (fila) {
            fila.style.display = "none";
        });

        visibles.slice(inicio, fin).forEach(function (fila) {
            fila.style.display = "";
        });

        if (filaVacia) {
            filaVacia.style.display = total === 0 ? "" : "none";
        }

        if (total === 0) {
            info.textContent = "No se encontraron " + etiqueta;
        } else {
            info.textContent = "Mostrando " + (inicio + 1) + " - " + fin + " de " + total.toLocaleString("es-VE") + " " + etiqueta;
        }

        renderizarPaginacion(totalPaginas);
    }

    buscador.addEventListener("input", function () {
        paginaActual = 1;
        renderizar();
    });

    filtroEstado.addEventListener("change", function () {
        paginaActual = 1;
        renderizar();
    });

    paginacion.addEventListener("click", function (e) {
        var btn = e.target.closest(".pag-btn");

        if (!btn || btn.disabled || !btn.getAttribute("data-pagina")) {
            return;
        }

        paginaActual = parseInt(btn.getAttribute("data-pagina"), 10);
        renderizar();

        contenedor.scrollIntoView({ block: "nearest", behavior: "smooth" });
    });

    renderizar();
});
