/* =============================================================================
   Registro de varias primas a la vez (solo modo "registrar").
   - Los selects en cascada (#grupoPrimas) deciden qué primas se registran.
   - Por cada prima elegida se muestra un bloque de campos que se llena solo
     con los datos del trabajador (años de servicio, nivel de educación, hijos).
   - El monto total = salario mensual + suma de las primas elegidas.
   Para editar/consultar se sigue usando valid_primas.js.
============================================================================= */
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formprimas");
    const trabajador = document.getElementById("id_trabajador");
    const grupo = document.getElementById("grupoPrimas");

    if (!formulario || !trabajador || !grupo) return;

    // registrar | editar | consultar
    const modo = formulario.dataset.modo || "registrar";

    // Cada fila: checkbox + panel de campos que se muestra al marcarla
    const filas = Array.from(grupo.querySelectorAll(".prima-row")).map(function (fila) {
        return {
            tipo: fila.dataset.tipo,
            fila: fila,
            check: fila.querySelector(".chk-prima"),
            panel: fila.querySelector(".prima-panel"),
            nota: fila.querySelector(".prima-nota"),
            nueva: fila.dataset.nueva === "1",
            idPrima: parseInt(fila.dataset.idPrima, 10) || 0,
            tipoValor: fila.dataset.tipoValor || "porcentaje",
            valores: (function () {
                try { return JSON.parse(fila.dataset.valores || "[]"); } catch (e) { return []; }
            })()
        };
    });

    const sueldoBase = document.getElementById("sueldo_base");
    const sueldoCargo = document.getElementById("sueldo_cargo");
    const campoSueldoBase = document.getElementById("campoSueldoBase");
    const campoSueldoCargo = document.getElementById("campoSueldoCargo");
    const origenSalario = document.getElementById("origen_salario");
    const tipoPersonal = document.getElementById("tipo_personal");
    const estatusLaboral = document.getElementById("estatus_laboral");
    const montoTotal = document.getElementById("monto_total");

    let consultaSalarioCorrecta = true;
    let enviando = false;
    let tokenConsultaSalario = 0;

    const T_ANT = "Prima por antigüedad";
    const T_PROF = "Prima por profesionalización";
    const T_HIJ = "Prima por hijos";

    const NIVEL_A_CONDICION = {
        "Técnico Superior Universitario": "TSU",
        "Profesional": "Lic. / Ing.",
        "Especialista": "Doc. / Post.",
        "Maestría": "Doc. / Post.",
        "Doctor": "Doc. / Post."
    };

    const NIVELES = [
        { valor: "TSU", texto: "TSU (20%)", pct: 20 },
        { valor: "Lic. / Ing.", texto: "Lic. / Ing. (25%)", pct: 25 },
        { valor: "Doc. / Post.", texto: "Doc. / Post. (35%)", pct: 35 }
    ];

    // Tarifas de la prima por hijos (catálogo, tipo monto fijo):
    // 1.er valor = hijos de 0 a 5 años | 2.º valor = hijos de 6 a 21 años
    // (corte propio de la prima, distinto al menor/mayor de edad legal del trabajador)
    const TARIFAS_HIJO = (function () {
        const fh = filas.find(function (x) { return x.tipo === T_HIJ; });
        if (fh && fh.tipoValor === "monto" && fh.valores.length >= 2) {
            return { menor: Number(fh.valores[0].valor), mayor: Number(fh.valores[1].valor) };
        }
        return null;
    })();

    // Tabla 1 - Prima de antigüedad: años de servicio -> porcentaje (23 años en adelante = 30%)
    const PORCENTAJE_ANTIGUEDAD = {
        1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6.2, 7: 7.4, 8: 8.6, 9: 9.8, 10: 11,
        11: 12.4, 12: 13.8, 13: 15.2, 14: 16.6, 15: 18, 16: 19.6, 17: 21.2,
        18: 22.8, 19: 24.4, 20: 26, 21: 27.8, 22: 29.6
    };
    function porcentajeAntiguedad(anios) {
        if (!Number.isInteger(anios) || anios < 1) return 0;
        return anios >= 23 ? 30 : (PORCENTAJE_ANTIGUEDAD[anios] || 0);
    }

    // ---------------------------------------------------------------------
    // Utilidades
    // ---------------------------------------------------------------------
    function numero(valorTexto) {
        if (valorTexto === null || valorTexto === undefined) return 0;
        const texto = String(valorTexto).trim().replace(/\s/g, "").replace(",", ".");
        if (texto === "") return 0;
        const resultado = Number(texto);
        return Number.isFinite(resultado) ? resultado : 0;
    }

    function moneda(cantidad) {
        return numero(cantidad).toFixed(2);
    }

    function mostrarAlerta(mensaje) {
        if (typeof window.showAlert === "function") {
            window.showAlert(mensaje, false);
        } else {
            alert(mensaje);
        }
    }

    function opcionTrabajador() {
        const opcion = trabajador.options[trabajador.selectedIndex];
        return opcion && opcion.value ? opcion : null;
    }

    function crearCampo(textoEtiqueta, control) {
        const campo = document.createElement("div");
        campo.className = "field";
        campo.style.width = "100%";

        const etiqueta = document.createElement("label");
        etiqueta.htmlFor = control.id;
        etiqueta.textContent = textoEtiqueta;

        campo.appendChild(etiqueta);
        campo.appendChild(control);
        return campo;
    }

    function crearSelect(id, nombre, opciones, textoVacio) {
        const select = document.createElement("select");
        select.id = id;
        select.name = nombre;
        select.required = true;
        if (textoVacio) {
            const vacio = document.createElement("option");
            vacio.value = "";
            vacio.textContent = textoVacio;
            select.appendChild(vacio);
        }
        opciones.forEach(function (o) {
            const op = document.createElement("option");
            op.value = o.valor;
            op.textContent = o.texto;
            select.appendChild(op);
        });
        return select;
    }

    function crearInputLectura(id, tipo) {
        const input = document.createElement("input");
        input.type = tipo || "text";
        input.id = id;
        input.readOnly = true;
        if (tipo === "number") input.step = "0.01";
        return input;
    }

    function crearRaiz(titulo) {
        const raiz = document.createElement("div");
        raiz.style.display = "grid";
        raiz.style.gridTemplateColumns = "repeat(2, 1fr)";
        raiz.style.gap = "15px 25px";

        const encabezado = document.createElement("div");
        encabezado.textContent = titulo;
        encabezado.style.gridColumn = "1 / -1";
        encabezado.style.fontWeight = "600";
        encabezado.style.paddingBottom = "4px";
        encabezado.style.borderBottom = "1px solid rgba(255,255,255,0.25)";
        raiz.appendChild(encabezado);
        return raiz;
    }

    // ---------------------------------------------------------------------
    // Panel: Antigüedad (solo muestra los años ya registrados del trabajador)
    // ---------------------------------------------------------------------
    function crearPanelAntiguedad(host) {
        const raiz = host;
        let anios = 0;

        const vista = crearInputLectura("antiguedad_anios_vista", "text");
        const oculto = document.createElement("input");
        oculto.type = "hidden";
        oculto.name = "antiguedad_anios";

        const pct = crearInputLectura("antiguedad_pct", "number");
        const monto = crearInputLectura("antiguedad_monto", "number");

        raiz.appendChild(crearCampo("Años de antigüedad", vista));
        raiz.appendChild(oculto);
        raiz.appendChild(crearCampo("Porcentaje (%)", pct));
        raiz.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        return {
            raiz: raiz,
            autollenar: function (opcion, avisos) {
                anios = Math.min(30, parseInt(opcion.dataset.anosServicio, 10) || 0);
                oculto.value = anios >= 1 ? String(anios) : "";
                vista.value = anios >= 1 ? anios + (anios === 1 ? " año" : " años") : "";
                if (anios < 1) avisos.push("El trabajador no tiene años de servicio registrados.");
            },
            calcular: function (base) {
                const p = porcentajeAntiguedad(anios);
                const valido = p > 0;
                const m = valido ? (base * p) / 100 : 0;
                pct.value = valido ? p.toFixed(2) : "";
                monto.value = moneda(m);
                return m;
            },
            validar: function () {
                return anios >= 1 ? "" : "El trabajador no tiene años de servicio registrados.";
            }
        };
    }

    // ---------------------------------------------------------------------
    // Panel: Profesionalización (solo muestra el nivel académico registrado)
    // ---------------------------------------------------------------------
    function crearPanelProfesionalizacion(host) {
        const raiz = host;
        let condicion = "";

        const vista = crearInputLectura("profesionalizacion_nivel_vista", "text");
        const oculto = document.createElement("input");
        oculto.type = "hidden";
        oculto.name = "profesionalizacion_nivel";

        const pct = crearInputLectura("profesionalizacion_pct", "number");
        const monto = crearInputLectura("profesionalizacion_monto", "number");

        raiz.appendChild(crearCampo("Nivel académico", vista));
        raiz.appendChild(oculto);
        raiz.appendChild(crearCampo("Porcentaje (%)", pct));
        raiz.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        return {
            raiz: raiz,
            autollenar: function (opcion, avisos) {
                const registrado = (opcion.dataset.nivelEducacion || "").trim();
                condicion = NIVEL_A_CONDICION[registrado] || "";
                oculto.value = condicion;
                vista.value = registrado;
                if (!condicion) {
                    avisos.push("El nivel de educación del trabajador (" +
                        (registrado || "sin registrar") +
                        ") no genera prima por profesionalización.");
                }
            },
            calcular: function (base) {
                const n = NIVELES.find(function (x) { return x.valor === condicion; });
                const p = n ? n.pct : 0;
                const m = p > 0 ? (base * p) / 100 : 0;
                pct.value = p > 0 ? String(p) : "";
                monto.value = moneda(m);
                return m;
            },
            validar: function () {
                return condicion ? "" : "El nivel de educación del trabajador no genera prima por profesionalización.";
            }
        };
    }

    // ---------------------------------------------------------------------
    // Panel: Hijos. Se trae solo del trabajador (hijos de 0-5 años y de 6-21 años,
    // con sus edades) y calcula: cantidad 0-5 x 1.er valor + cantidad 6-21 x 2.º valor.
    // El servidor vuelve a leer los hijos de la base de datos al guardar.
    // ---------------------------------------------------------------------
    function crearPanelHijos(host) {
        const formato = function (v) { return Number(v).toFixed(2).replace(".", ","); };
        let menores = 0;
        let mayores = 0;
        let fuera = 0;

        const menoresIn = crearInputLectura("hijos_menores", "text");
        const mayoresIn = crearInputLectura("hijos_mayores", "text");
        const edadesIn = crearInputLectura("hijos_edades", "text");
        const tarifaMenorIn = crearInputLectura("hijos_tarifa_menor", "text");
        const tarifaMayorIn = crearInputLectura("hijos_tarifa_mayor", "text");
        const monto = crearInputLectura("hijos_monto", "number");

        host.appendChild(crearCampo("Hijos de 0 a 5 años", menoresIn));
        host.appendChild(crearCampo("Hijos de 6 a 21 años", mayoresIn));
        host.appendChild(crearCampo("Edades registradas", edadesIn));
        host.appendChild(crearCampo("Monto por hijo de 0 a 5 años (Bs.)", tarifaMenorIn));
        host.appendChild(crearCampo("Monto por hijo de 6 a 21 años (Bs.)", tarifaMayorIn));
        host.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        return {
            raiz: host,
            autollenar: function (opcion, avisos) {
                menores = parseInt(opcion.dataset.hijosMenores, 10) || 0;
                mayores = parseInt(opcion.dataset.hijosMayores, 10) || 0;
                fuera = parseInt(opcion.dataset.hijosFuera, 10) || 0;

                menoresIn.value = String(menores);
                mayoresIn.value = String(mayores);
                edadesIn.value = opcion.dataset.edadesHijos ? opcion.dataset.edadesHijos + " años" : "Sin edades detalladas";

                if (!TARIFAS_HIJO) {
                    avisos.push('Falta registrar la prima llamada exactamente "Prima por hijos" en "Registrar nueva prima" (monto fijo, con dos valores: primero el de 0 a 5 años y luego el de 6 a 21 años).');
                }

                if (menores + mayores < 1) {
                    avisos.push(fuera > 0
                        ? "Los hijos registrados superan los 21 años: no cuentan para la prima."
                        : "El trabajador no tiene hijos con edad registrada.");
                } else if (fuera > 0) {
                    avisos.push(fuera + (fuera === 1 ? " hijo supera" : " hijos superan") + " los 21 años y no cuenta(n) para la prima.");
                }
            },
            calcular: function () {
                if (!TARIFAS_HIJO) {
                    tarifaMenorIn.value = "";
                    tarifaMayorIn.value = "";
                    monto.value = "";
                    return 0;
                }
                tarifaMenorIn.value = formato(TARIFAS_HIJO.menor);
                tarifaMayorIn.value = formato(TARIFAS_HIJO.mayor);
                const total = menores * TARIFAS_HIJO.menor + mayores * TARIFAS_HIJO.mayor;
                monto.value = moneda(total);
                return total;
            },
            validar: function () {
                if (menores + mayores < 1) return "El trabajador no tiene hijos de 0 a 21 años con edad registrada.";
                if (!TARIFAS_HIJO) return "La prima por hijos del catálogo debe ser de monto fijo con dos valores (0 a 5 años y 6 a 21 años).";
                return "";
            }
        };
    }

    // ---------------------------------------------------------------------
    // Panel: Prima nueva del catálogo (monto = base salarial x porcentaje)
    // ---------------------------------------------------------------------
    let contadorNuevas = 0;

    // Valor elegido antes (al editar o tras un error del servidor): {id_prima: id_prima_valor}
    function valoresPrevios() {
        try { return JSON.parse(formulario.dataset.valoresPrevios || "{}"); } catch (e) { return {}; }
    }

    // $f = fila de la prima: { tipo, idPrima, tipoValor: "porcentaje" | "monto", valores: [{id, valor}] }
    function crearPanelNueva(host, f) {
        const n = ++contadorNuevas;
        const esPct = f.tipoValor === "porcentaje";
        const valores = f.valores || [];
        const formato = function (v) { return Number(v).toFixed(2).replace(".", ","); };
        const monto = crearInputLectura("nueva_monto_" + n, "number");
        let selector = null;
        let pct = null;

        if (valores.length > 1) {
            // La prima tiene varios valores: se elige cuál aplicar a este trabajador
            selector = crearSelect(
                "nueva_valor_" + n,
                "valor_prima[" + f.idPrima + "]",
                valores.map(function (v) {
                    return { valor: String(v.id), texto: formato(v.valor) + (esPct ? " %" : " Bs.") };
                }),
                "Seleccione un valor"
            );
            const previo = valoresPrevios()[f.idPrima];
            if (previo) selector.value = String(previo);
            selector.addEventListener("change", recalcular);
            host.appendChild(crearCampo(esPct ? "Porcentaje a aplicar (%)" : "Monto a aplicar (Bs.)", selector));
        } else if (esPct) {
            pct = crearInputLectura("nueva_pct_" + n, "number");
            host.appendChild(crearCampo("Porcentaje (%)", pct));
        }

        host.appendChild(crearCampo("Monto de la prima (Bs.)", monto));

        function valorActual() {
            if (selector) {
                const v = valores.find(function (x) { return String(x.id) === selector.value; });
                return v ? Number(v.valor) : 0;
            }
            return valores.length ? Number(valores[0].valor) : 0;
        }

        return {
            raiz: host,
            autollenar: function () { /* no depende de datos del trabajador */ },
            calcular: function (base) {
                const v = valorActual();
                // Porcentaje: base salarial x % | Monto fijo: el monto tal cual
                const m = esPct ? (v > 0 ? (base * v) / 100 : 0) : v;
                if (pct) pct.value = v > 0 ? v.toFixed(2) : "";
                monto.value = moneda(m);
                return m;
            },
            validar: function () {
                if (!valores.length) return "La prima \"" + f.tipo + "\" no tiene valores registrados.";
                if (selector && !selector.value) return "Seleccione el valor que desea aplicar en \"" + f.tipo + "\".";
                return valorActual() > 0 ? "" : "La prima \"" + f.tipo + "\" no tiene un valor válido.";
            }
        };
    }

    const FABRICA = {};
    FABRICA[T_ANT] = crearPanelAntiguedad;
    FABRICA[T_PROF] = crearPanelProfesionalizacion;
    FABRICA[T_HIJ] = crearPanelHijos;

    const activos = {}; // tipo -> panel

    // ---------------------------------------------------------------------
    // Cálculo general
    // ---------------------------------------------------------------------
    function recalcular() {
        const opcion = opcionTrabajador();
        const global = numero(sueldoBase ? sueldoBase.value : 0);
        const base = opcion ? numero(opcion.dataset.basePrima) : global;
        const salario = opcion ? numero(opcion.dataset.salarioMensual) : global;

        let suma = 0;
        Object.keys(activos).forEach(function (tipo) {
            suma += activos[tipo].calcular(base);
        });

        if (montoTotal) montoTotal.value = moneda(salario + suma);
    }

    // ---------------------------------------------------------------------
    // Disponibilidad de cada prima según el trabajador
    //  - Obrero: no tiene prima por profesionalización
    //  - Las primas que el trabajador ya tiene registradas se bloquean
    // ---------------------------------------------------------------------
    function actualizarDisponibilidad() {
        if (modo !== "registrar") return; // al editar/consultar la prima ya existe
        const opcion = opcionTrabajador();
        const tipo = ((opcion && opcion.dataset.tipoPersonal) || "").toLowerCase();
        const obrero = tipo.includes("obrero");

        let registradas = [];
        try { registradas = JSON.parse((opcion && opcion.dataset.primasRegistradas) || "[]"); } catch (e) {}

        filas.forEach(function (f) {
            const noAplica = obrero && f.tipo === T_PROF;
            const yaRegistrada = registradas.includes(f.tipo);
            const bloquear = noAplica || yaRegistrada;

            f.fila.style.display = noAplica ? "none" : "";
            if (bloquear) f.check.checked = false;
            f.check.disabled = bloquear;
            f.fila.style.opacity = yaRegistrada ? "0.55" : "";
            if (f.nota) f.nota.textContent = yaRegistrada ? " (ya registrada para este trabajador)" : "";
        });
    }

    // ---------------------------------------------------------------------
    // Mostrar el panel de cada prima marcada
    // ---------------------------------------------------------------------
    function renderPaneles(autollenarTodos) {
        const opcion = opcionTrabajador();
        const avisos = [];

        filas.forEach(function (f) {
            const marcada = f.check.checked && !f.check.disabled;
            let panel = activos[f.tipo];

            if (!marcada) {
                if (panel) {
                    f.panel.innerHTML = "";
                    delete activos[f.tipo];
                }
                f.panel.style.display = "none";
                return;
            }

            const esNuevo = !panel;
            if (esNuevo) {
                f.panel.innerHTML = "";
                panel = f.nueva ? crearPanelNueva(f.panel, f) : FABRICA[f.tipo](f.panel);
                activos[f.tipo] = panel;
            }
            f.panel.style.display = "grid";

            if (opcion && (esNuevo || autollenarTodos)) {
                panel.autollenar(opcion, avisos);
            }

            if (modo === "consultar") {
                f.panel.querySelectorAll("input:not([type=hidden]), select").forEach(function (e) { e.disabled = true; });
            }
        });

        recalcular();
        if (avisos.length) mostrarAlerta(avisos.join("\n"));
    }

    // ---------------------------------------------------------------------
    // Salario vigente del cargo (misma consulta que usaba valid_primas.js)
    // ---------------------------------------------------------------------
    // Obrero: solo se muestra el sueldo base. Resto del personal: solo el sueldo por cargo.
    function actualizarVisibilidadSueldos(tipo) {
        if (!campoSueldoBase || !campoSueldoCargo) return;
        const t = String(tipo || "").toLowerCase();
        if (!t) {
            campoSueldoBase.style.display = "";
            campoSueldoCargo.style.display = "";
            return;
        }
        const obrero = t.includes("obrero");
        campoSueldoBase.style.display = obrero ? "" : "none";
        campoSueldoCargo.style.display = obrero ? "none" : "";
    }

    async function actualizarSueldoCargo() {
        const opcion = opcionTrabajador();

        actualizarVisibilidadSueldos(opcion ? opcion.dataset.tipoPersonal : "");

        if (!opcion) {
            consultaSalarioCorrecta = true;
            if (sueldoCargo) sueldoCargo.value = "0.00";
            if (origenSalario) origenSalario.value = "";
            if (tipoPersonal) tipoPersonal.value = "";
            if (estatusLaboral) estatusLaboral.value = "";
            recalcular();
            return;
        }

        const token = ++tokenConsultaSalario;
        consultaSalarioCorrecta = false;

        try {
            const respuesta = await fetch(
                "/FUNDACITE/controladores/consultar_salario_cargo.php?id_trabajador=" + encodeURIComponent(opcion.value),
                { method: "GET", headers: { "Accept": "application/json" }, cache: "no-store", credentials: "same-origin" }
            );
            const datos = await respuesta.json();
            if (token !== tokenConsultaSalario) return;
            if (!respuesta.ok || !datos.success) throw new Error(datos.message || "No se pudo consultar el salario.");

            if (String(datos.tipo_personal || "").toLowerCase().includes("obrero")) {
                // Obrero: solo sueldo base (sueldo mínimo)
                datos.sueldo_cargo = 0;
                datos.origen_salario = "base";
                datos.salario_mensual = datos.sueldo_base;
                datos.base_prima = datos.sueldo_base;
                datos.salario_cargo_registrado = false;
            } else {
                // Resto del personal: solo el sueldo de su cargo; las primas se calculan sobre ese sueldo
                const cargo = Number(datos.sueldo_cargo) || 0;
                datos.origen_salario = "cargo";
                datos.salario_mensual = cargo;
                datos.base_prima = cargo;
            }

            opcion.dataset.sueldoCargo = String(datos.sueldo_cargo ?? 0);
            opcion.dataset.salarioMensual = String(datos.salario_mensual ?? 0);
            opcion.dataset.basePrima = String(datos.base_prima ?? datos.sueldo_base ?? 0);
            opcion.dataset.origenSalario = String(datos.origen_salario ?? "base");
            opcion.dataset.tipoPersonal = String(datos.tipo_personal ?? "");
            opcion.dataset.estatus = String(datos.status ?? "");
            consultaSalarioCorrecta = true;

            const origen = String(datos.origen_salario || "base");
            const etiquetasOrigen = {
                "cargo": "Sueldo por cargo",
                "base": "Sueldo base",
                "base+cargo": "Sueldo base + sueldo por cargo"
            };

            if (sueldoCargo) sueldoCargo.value = moneda(datos.sueldo_cargo);
            if (origenSalario) origenSalario.value = etiquetasOrigen[origen] || "No determinado";
            if (tipoPersonal) {
                let etiqueta = datos.tipo_personal || "";
                if (etiqueta === "Obrero") {
                    etiqueta += " (aplica sueldo base)";
                }
                tipoPersonal.value = etiqueta;
            }
            if (estatusLaboral) estatusLaboral.value = datos.status || "";
            actualizarVisibilidadSueldos(datos.tipo_personal);

            recalcular();
        } catch (error) {
            if (token !== tokenConsultaSalario) return;
            consultaSalarioCorrecta = false;
            if (origenSalario) origenSalario.value = "No se pudo consultar";
            console.error("Consulta de salario por cargo fallida:", error);
        }
    }

    // ---------------------------------------------------------------------
    // Eventos
    // ---------------------------------------------------------------------
    // (el script de cascada, cargado antes, ya actualizó los selects)
    filas.forEach(function (f) {
        f.check.addEventListener("change", function () {
            if (f.check.checked && !opcionTrabajador()) {
                f.check.checked = false;
                mostrarAlerta("Seleccione primero un trabajador.");
                trabajador.focus();
                return;
            }
            renderPaneles(false);
        });
    });

    trabajador.addEventListener("change", function () {
        actualizarDisponibilidad();
        renderPaneles(true); // vuelve a llenar todas las primas marcadas con los datos del nuevo trabajador
        actualizarSueldoCargo();
    });

    formulario.addEventListener("submit", function (evento) {
        if (enviando) { evento.preventDefault(); evento.stopImmediatePropagation(); return; }
        let mensaje = "";

        if (!trabajador.value) {
            mensaje = "Debe seleccionar un trabajador.";
            trabajador.focus();
        } else if (!consultaSalarioCorrecta) {
            mensaje = "No se ha podido verificar el salario vigente del cargo en la base de datos. Espere la consulta o vuelva a seleccionar el trabajador.";
            trabajador.focus();
        } else if (Object.keys(activos).length === 0) {
            mensaje = "Seleccione al menos una prima.";
            filas[0].check.focus();
        } else {
            const tipos = Object.keys(activos);
            for (let i = 0; i < tipos.length && !mensaje; i++) {
                mensaje = activos[tipos[i]].validar();
            }
        }

        if (mensaje) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            mostrarAlerta(mensaje);
            return;
        }

        recalcular();
        enviando = true; // evita un segundo envío por doble clic
    });

    window.addEventListener("pageshow", function () { enviando = false; });

    formulario.addEventListener("reset", function () {
        setTimeout(function () {
            actualizarDisponibilidad();
            renderPaneles(false);
            actualizarSueldoCargo();
        }, 0);
    });

    // Inicializar (también restaura el estado tras un error al guardar)
    let previas = [];
    try { previas = JSON.parse(grupo.dataset.previas || "[]"); } catch (e) {}
    actualizarDisponibilidad();
    filas.forEach(function (f) {
        if (previas.includes(f.tipo) && !f.check.disabled) f.check.checked = true;
    });
    renderPaneles(false);
    actualizarSueldoCargo();
});

// =====================================================
// ALERTA PERSONALIZADA
// =====================================================
function showAlert(message, success = false) {
    const alerta = document.getElementById("customAlert");
    const mensaje = document.getElementById("alertMessage");

    if (!alerta || !mensaje) {
        alert(message);
        return;
    }

    mensaje.innerText = message;
    alerta.classList.remove("hidden");

    const boton = document.querySelector(".alert-box button");
    if (boton) {
        boton.style.background = success ? "#2e7d32" : "#c62828";
    }
}

function closeAlert() {
    document.getElementById("customAlert")?.classList.add("hidden");
}