document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formprimas");
    const trabajador = document.getElementById("id_trabajador");
    const tipoPrima = document.getElementById("tipo_prima");

    const campoCondicion = document.getElementById("campoCondicion");
    const condicion = document.getElementById("condicion");

    const campoCantidad = document.getElementById("campoCantidadHijos");
    const cantidadHijos = document.getElementById("cantidad_hijos");
    const campoMontoHijo1 = document.getElementById("campoMontoHijo1");
    const contenedorHijos = document.getElementById("contenedorHijos");

    const campoValor = document.getElementById("campoValor");
    const valor = document.getElementById("valor");

    const sueldoBase = document.getElementById("sueldo_base");
    const sueldoCargo = document.getElementById("sueldo_cargo");
    const origenSalario = document.getElementById("origen_salario");
    const detalleOrigenSalario = document.getElementById("detalle_origen_salario");
    const tipoPersonal = document.getElementById("tipo_personal");
    const estatusLaboral = document.getElementById("estatus_laboral");
    const montoTotal = document.getElementById("monto_total");
    let consultaSalarioCorrecta = true;
    let tokenConsultaSalario = 0;

    const labelCondicion = document.getElementById("labelCondicion");
    const labelValor = document.getElementById("labelValor");
    const btnLimpiar = document.getElementById("btnLimpiar");

    if (!formulario || !trabajador || !tipoPrima || !condicion || !valor) {
        console.error("Faltan campos necesarios básicos en el formulario de primas.");
        return;
    }

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

    // =====================================================
    // AUTOCOMPLETAR PRIMA CON LOS DATOS DEL TRABAJADOR
    // (hijos, años de servicio y nivel de educación)
    // =====================================================
    const NIVEL_A_CONDICION = {
        "Técnico Superior Universitario": "TSU",
        "Profesional": "Lic. / Ing.",
        "Especialista": "Doc. / Post.",
        "Maestría": "Doc. / Post.",
        "Doctor": "Doc. / Post."
    };

    function aplicarDatosTrabajador() {
        const opcion = trabajador.options[trabajador.selectedIndex];
        const tipo = tipoPrima.value;

        if (!opcion || !opcion.value || !tipo) {
            return;
        }

        if (tipo === "Prima por antigüedad") {
            const anios = Math.min(30, parseInt(opcion.dataset.anosServicio, 10) || 0);
            condicion.value = anios >= 1 ? String(anios) : "";
            if (anios < 1) {
                mostrarAlerta("El trabajador no tiene años de servicio registrados.");
            }
        } else if (tipo === "Prima por profesionalización") {
            const nivel = NIVEL_A_CONDICION[opcion.dataset.nivelEducacion] || "";
            condicion.value = nivel;
            if (!nivel) {
                mostrarAlerta("El nivel de educación del trabajador (" +
                    (opcion.dataset.nivelEducacion || "sin registrar") +
                    ") no genera prima por profesionalización.");
            }
        } else if (tipo === "Prima por hijos") {
            const hijos = parseInt(opcion.dataset.hijos, 10) || 0;
            if (cantidadHijos) {
                cantidadHijos.value = hijos >= 1 ? String(Math.min(hijos, 20)) : "";
            }
            if (hijos >= 1) {
                generarCamposHijos(Math.min(hijos, 20));
            } else {
                generarCamposHijos(0);
                mostrarAlerta("El trabajador no tiene hijos registrados.");
            }
        }

        calcularTodo();
    }

    // =====================================================
    // OBTENER SUELDO DEL CARGO SEGÚN TRABAJADOR
    // =====================================================
    async function actualizarSueldoCargo() {
        if (!trabajador || !sueldoCargo) return;
        const opcion = trabajador.options[trabajador.selectedIndex];

        if (!opcion || !opcion.value) {
            consultaSalarioCorrecta = true;
            sueldoCargo.value = "0.00";
            if (origenSalario) origenSalario.value = "";
            if (detalleOrigenSalario) detalleOrigenSalario.textContent =
                "Seleccione un trabajador para consultar si su cargo tiene un salario vigente registrado.";
            if (tipoPersonal) tipoPersonal.value = "";
            if (estatusLaboral) estatusLaboral.value = "";
            calcularTodo();
            return;
        }

        const token = ++tokenConsultaSalario;
        consultaSalarioCorrecta = false;
        if (detalleOrigenSalario) detalleOrigenSalario.textContent = "Consultando en la base de datos el salario vigente del cargo...";

        try {
            const respuesta = await fetch("/FUNDACITE/controladores/consultar_salario_cargo.php?id_trabajador=" + encodeURIComponent(opcion.value), {
                method: "GET",
                headers: { "Accept": "application/json" },
                cache: "no-store",
                credentials: "same-origin"
            });
            const datos = await respuesta.json();
            if (token !== tokenConsultaSalario) return;
            if (!respuesta.ok || !datos.success) throw new Error(datos.message || "No se pudo consultar el salario.");

            // Actualizar los datos del selector con la respuesta actual de MySQL.
            opcion.dataset.sueldoCargo = String(datos.sueldo_cargo ?? 0);
            opcion.dataset.salarioMensual = String(datos.salario_mensual ?? 0);
            opcion.dataset.basePrima = String(datos.base_prima ?? datos.sueldo_base ?? 0);
            opcion.dataset.origenSalario = String(datos.origen_salario ?? "base");
            opcion.dataset.tipoPersonal = String(datos.tipo_personal ?? "");
            opcion.dataset.estatus = String(datos.status ?? "");
            opcion.dataset.salarioCargoRegistrado = datos.salario_cargo_registrado ? "1" : "0";
            opcion.dataset.cargo = String(datos.cargo ?? "");
            consultaSalarioCorrecta = true;

            const sueldo = numero(datos.sueldo_cargo);
            const sueldoBaseValor = numero(datos.sueldo_base);
            const origen = String(datos.origen_salario || "base");
            const salarioMensual = numero(datos.salario_mensual);
            sueldoCargo.value = moneda(sueldo);

            const etiquetasOrigen = {
                "cargo": "Sueldo por cargo",
                "base": "Sueldo base",
                "base+cargo": "Sueldo base + sueldo por cargo"
            };
            if (origenSalario) origenSalario.value = etiquetasOrigen[origen] || "No determinado";
            if (detalleOrigenSalario) {
                const nombreCargo = datos.cargo ? " Cargo: " + datos.cargo + "." : " El trabajador no tiene un cargo asignado.";
                if (datos.salario_cargo_registrado) {
                    detalleOrigenSalario.textContent = "Consulta confirmada en la base de datos: existe un salario vigente registrado para este cargo (Bs. " + moneda(sueldo) + ")." + nombreCargo + (origen === "cargo"
                        ? " Se utiliza únicamente el sueldo del cargo."
                        : " Se utiliza la modalidad " + (etiquetasOrigen[origen] || origen) + "; sueldo mensual aplicado: Bs. " + moneda(salarioMensual) + ".");
                } else {
                    detalleOrigenSalario.textContent = "Consulta confirmada en la base de datos: este cargo no tiene un salario vigente registrado. Se utilizará el sueldo base vigente de Bs. " + moneda(sueldoBaseValor) + "." + nombreCargo;
                }
            }
            if (tipoPersonal) {
                let etiqueta = datos.tipo_personal || "";
                if (etiqueta === "Obrero") etiqueta += datos.salario_cargo_registrado ? " (salario por cargo registrado)" : " (sin salario por cargo; aplica sueldo base)";
                tipoPersonal.value = etiqueta;
            }
            if (estatusLaboral) estatusLaboral.value = datos.status || "";
            calcularTodo();
            aplicarDatosTrabajador();
        } catch (error) {
            if (token !== tokenConsultaSalario) return;
            consultaSalarioCorrecta = false;
            if (origenSalario) origenSalario.value = "No se pudo consultar";
            if (detalleOrigenSalario) detalleOrigenSalario.textContent = "Error: no se pudo verificar en la base de datos si el cargo tiene un salario vigente. Recargue o vuelva a seleccionar el trabajador antes de guardar.";
            console.error("Consulta de salario por cargo fallida:", error);
        }
    }

    // =====================================================
    // CARGAR OPCIONES DE ANTIGÜEDAD
    // =====================================================
    function cargarAntiguedad() {
        condicion.innerHTML = '<option value="">Seleccione los años</option>';
        for (let anio = 1; anio <= 30; anio++) {
            const opcion = document.createElement("option");
            opcion.value = String(anio);
            opcion.textContent = anio + (anio === 1 ? " año" : " años");
            condicion.appendChild(opcion);
        }
        if (labelCondicion) labelCondicion.textContent = "Años de antigüedad";
        if (labelValor) labelValor.textContent = "Porcentaje (%)";
        valor.readOnly = true;
    }

    // =====================================================
    // CARGAR OPCIONES DE PROFESIONALIZACIÓN
    // =====================================================
    function cargarProfesionalizacion() {
        condicion.innerHTML = '<option value="">Seleccione el nivel académico</option>';
        const niveles = [
            { valor: "TSU", texto: "TSU (20%)", porcentaje: 20 },
            { valor: "Lic. / Ing.", texto: "Lic. / Ing. (25%)", porcentaje: 25 },
            { valor: "Doc. / Post.", texto: "Doc. / Post. (35%)", porcentaje: 35 }
        ];

        niveles.forEach(function (nivel) {
            const opcion = document.createElement("option");
            opcion.value = nivel.valor;
            opcion.textContent = nivel.texto;
            opcion.dataset.porcentaje = String(nivel.porcentaje);
            condicion.appendChild(opcion);
        });

        if (labelCondicion) labelCondicion.textContent = "Nivel académico";
        if (labelValor) labelValor.textContent = "Porcentaje (%)";
        valor.readOnly = true;
    }

    // =====================================================
    // CALCULAR MONTO TOTAL DE HIJOS
    // =====================================================
    function calcularMontoHijos() {
        const selects = document.querySelectorAll('.monto-hijo-select, select[name="monto_hijo[]"]');
        let total = 0;
        selects.forEach(function (select) {
            const campoContenedor = select.closest('.field');
            if (campoContenedor && window.getComputedStyle(campoContenedor).display !== "none") {
                total += numero(select.value);
            }
        });
        return total;
    }

    // =====================================================
    // GENERAR DINÁMICAMENTE HIJOS
    // =====================================================
    function generarCamposHijos(cantidad) {
        if (!contenedorHijos) return;
        contenedorHijos.innerHTML = "";

        if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 20) {
            if (campoMontoHijo1) campoMontoHijo1.style.display = "none";
            contenedorHijos.style.display = "none";
            calcularTodo();
            return;
        }

        if (campoMontoHijo1) campoMontoHijo1.style.display = "flex";
        
        if (cantidad > 1) {
            contenedorHijos.style.display = "grid";
            contenedorHijos.style.gridColumn = "1 / -1";
            contenedorHijos.style.gridTemplateColumns = "repeat(2, 1fr)";
            contenedorHijos.style.gap = "15px 25px";

            for (let i = 2; i <= cantidad; i++) {
                const campo = document.createElement("div");
                campo.className = "field";
                campo.style.width = "100%";

                const etiqueta = document.createElement("label");
                etiqueta.htmlFor = "monto_hijo_" + (i - 1);
                etiqueta.textContent = "Monto del hijo " + i;

                const select = document.createElement("select");
                select.id = "monto_hijo_" + (i - 1);
                select.name = "monto_hijo[]";
                select.className = "monto-hijo-select";
                select.required = true;

                select.innerHTML = `
                    <option value="56.00">56,00 Bs.</option>
                    <option value="12.50">12,50 Bs.</option>
                `;

                select.addEventListener("change", calcularTodo);

                campo.appendChild(etiqueta);
                campo.appendChild(select);
                contenedorHijos.appendChild(campo);
            }
        } else {
            contenedorHijos.style.display = "none";
        }

        calcularTodo();
    }

    // =====================================================
    // RECALCULAR VALORES
    // =====================================================
    function calcularTodo() {
        // Salario y base de las primas calculados en el servidor (regla de obrero)
        const opcionTrab = trabajador.options[trabajador.selectedIndex];
        const hayTrabajador = opcionTrab && opcionTrab.value;
        const sueldoGlobal = numero(sueldoBase?.value);
        const base = hayTrabajador ? numero(opcionTrab.dataset.basePrima) : sueldoGlobal;
        const salarioMensual = hayTrabajador ? numero(opcionTrab.dataset.salarioMensual) : sueldoGlobal;
        const tipo = tipoPrima.value;
        let montoPrima = 0;
        let porcentaje = 0;

        if (tipo === "Prima por antigüedad") {
            const anios = Number(condicion.value);
            if (Number.isInteger(anios) && anios >= 1 && anios <= 30) {
                porcentaje = anios;
                montoPrima = (base * porcentaje) / 100;
            }
            valor.value = porcentaje > 0 ? String(porcentaje) : "";
        } else if (tipo === "Prima por profesionalización") {
            const opcion = condicion.options[condicion.selectedIndex];
            porcentaje = opcion && opcion.dataset.porcentaje ? Number(opcion.dataset.porcentaje) : 0;
            if (porcentaje > 0) {
                montoPrima = (base * porcentaje) / 100;
            }
            valor.value = porcentaje > 0 ? String(porcentaje) : "";
        } else if (tipo === "Prima por hijos") {
            const cantidad = cantidadHijos ? Number(cantidadHijos.value) : 0;
            if (cantidad > 0) {
                montoPrima = calcularMontoHijos();
            } else {
                montoPrima = 0;
            }
            valor.value = moneda(montoPrima);
        }

        if (montoTotal) {
            montoTotal.value = moneda(salarioMensual + montoPrima);
        }
    }

    // =====================================================
    // CAMBIO DE TIPO DE PRIMA
    // =====================================================
    function actualizarFormulario() {
        const tipo = tipoPrima.value;
        const esHijos = tipo === "Prima por hijos";

        if (campoCantidad) campoCantidad.style.display = esHijos ? "flex" : "none";
        
        const cantidadVal = cantidadHijos ? Number(cantidadHijos.value) : 0;
        if (esHijos && cantidadVal > 0) {
            if (campoMontoHijo1) campoMontoHijo1.style.display = "flex";
            if (contenedorHijos && cantidadVal > 1) contenedorHijos.style.display = "grid";
        } else {
            if (campoMontoHijo1) campoMontoHijo1.style.display = "none";
            if (contenedorHijos) contenedorHijos.style.display = "none";
        }

        if (campoCondicion) campoCondicion.style.display = esHijos ? "none" : "flex";

        // SI NO HA SELECCIONADO NINGUNA PRIMA
        if (!tipo) {
            condicion.disabled = true; // Bloqueado hasta elegir prima
            condicion.innerHTML = '<option value="">Seleccione una prima primero</option>';
            if (labelValor) labelValor.textContent = "Valor";
            valor.value = "";
            valor.readOnly = true; // Bloqueado por defecto
        } else {
            condicion.disabled = false; // Se habilita al elegir prima
        }

        // Etiqueta dinámica y estados según el tipo elegido
        if (tipo === "Prima por antigüedad" || tipo === "Prima por profesionalización") {
            if (labelValor) labelValor.textContent = "Porcentaje (%)";
            valor.readOnly = true; // En estas primas se calcula automático
        } else if (tipo === "Prima por hijos") {
            if (labelValor) labelValor.textContent = "Monto total de la prima";
            valor.readOnly = true; // En hijos se calcula automático con los selects
        }

        if (tipo === "Prima por antigüedad") {
            cargarAntiguedad();
        } else if (tipo === "Prima por profesionalización") {
            cargarProfesionalizacion();
        }

        calcularTodo();
        aplicarDatosTrabajador();
    }
    
    document.querySelectorAll('.monto-hijo-select, select[name="monto_hijo[]"]').forEach(select => {
        select.addEventListener("change", calcularTodo);
    });

    if (cantidadHijos) {
        cantidadHijos.addEventListener("input", function () {
            const texto = cantidadHijos.value.trim();
            const cantidad = Number(texto);

            if (texto === "" || !Number.isInteger(cantidad) || cantidad < 1 || cantidad > 20) {
                if (campoMontoHijo1) campoMontoHijo1.style.display = "none";
                if (contenedorHijos) {
                    contenedorHijos.style.display = "none";
                    contenedorHijos.innerHTML = "";
                }
                calcularTodo();
                return;
            }

            generarCamposHijos(cantidad);
        });
    }

    // =====================================================
    // EVENTOS
    // =====================================================
    trabajador.addEventListener("change", actualizarSueldoCargo);
    tipoPrima.addEventListener("change", actualizarFormulario);
    condicion.addEventListener("change", calcularTodo);

    if (sueldoBase) sueldoBase.addEventListener("input", calcularTodo);
    if (sueldoCargo) sueldoCargo.addEventListener("input", calcularTodo);

    // =====================================================
    // VALIDAR ANTES DE ENVIAR
    // =====================================================
    formulario.addEventListener("submit", function (evento) {
        const tipo = tipoPrima.value;
        if (trabajador.value && !consultaSalarioCorrecta) {
            evento.preventDefault();
            mostrarAlerta("No se ha podido verificar el salario vigente del cargo en la base de datos. Espere la consulta o vuelva a seleccionar el trabajador.");
            trabajador.focus();
            return;
        }

        if (!trabajador.value) {
            evento.preventDefault();
            mostrarAlerta("Debe seleccionar un trabajador.");
            trabajador.focus();
            return;
        }

        if (!tipo) {
            evento.preventDefault();
            mostrarAlerta("Seleccione el tipo de prima.");
            tipoPrima.focus();
            return;
        }

        if (tipo === "Prima por antigüedad") {
            const anios = Number(condicion.value);
            if (!Number.isInteger(anios) || anios < 1 || anios > 30) {
                evento.preventDefault();
                mostrarAlerta("Seleccione los años de antigüedad.");
                condicion.focus();
                return;
            }
        }

        if (tipo === "Prima por profesionalización" && !condicion.value) {
            evento.preventDefault();
            mostrarAlerta("Seleccione el nivel académico.");
            condicion.focus();
            return;
        }

        if (tipo === "Prima por hijos" && cantidadHijos) {
            const cantidad = Number(cantidadHijos.value);
            if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 20) {
                evento.preventDefault();
                mostrarAlerta("Ingrese una cantidad válida de hijos (1 - 20).");
                cantidadHijos.focus();
                return;
            }
        }

        calcularTodo();
    });

    if (btnLimpiar) {
        btnLimpiar.addEventListener("click", function () {
            formulario.reset();
        });
    }

    formulario.addEventListener("reset", function () {
        setTimeout(function () {
            actualizarFormulario();
            actualizarSueldoCargo();
        }, 0);
    });

    // INICIALIZAR
    if (tipoPrima.value === "Prima por antigüedad" && condicion.options.length <= 1) {
        cargarAntiguedad();
    } else if (tipoPrima.value === "Prima por profesionalización" && condicion.options.length <= 1) {
        cargarProfesionalizacion();
    } else {
        actualizarFormulario();
    }
    
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