document.addEventListener("DOMContentLoaded", function () {

    
    // ==========================
    // FORMULARIO DE REGISTRO
    // ==========================
    const formularioRegistro = document.getElementById("formPersonas");

    if (formularioRegistro) {

        const fechaNacimiento = document.getElementById("fecha");
        const edadInput = document.getElementById("edad");
        const EDAD_MINIMA = 18;
        const EDAD_MAXIMA = 75;

        // El trabajador debe tener entre 18 y 75 años. Se restringe también
        // el calendario para evitar fechas futuras o demasiado antiguas.
        function fechaISO(fecha) {
            return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, "0")}-${String(fecha.getDate()).padStart(2, "0")}`;
        }
        function calcularEdad(valor) {
            const nacimiento = new Date(valor + "T00:00:00");
            const hoy = new Date();
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) edad--;
            return edad;
        }
        if (fechaNacimiento) {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fechaMaxima = new Date(hoy);
            fechaMaxima.setFullYear(fechaMaxima.getFullYear() - EDAD_MINIMA);
            const fechaMinima = new Date(hoy);
            fechaMinima.setFullYear(fechaMinima.getFullYear() - EDAD_MAXIMA);
            fechaNacimiento.min = fechaISO(fechaMinima);
            fechaNacimiento.max = fechaISO(fechaMaxima);

            fechaNacimiento.addEventListener("change", function () {
                if (!this.value) {
                    if (edadInput) edadInput.value = "";
                    this.setCustomValidity("");
                    return;
                }
                const edad = calcularEdad(this.value);
                if (edad < EDAD_MINIMA || edad > EDAD_MAXIMA || this.value > fechaISO(hoy)) {
                    this.setCustomValidity(`La edad debe estar entre ${EDAD_MINIMA} y ${EDAD_MAXIMA} años; la fecha de nacimiento no puede ser futura.`);
                    this.reportValidity();
                    if (edadInput) edadInput.value = "";
                    return;
                }
                this.setCustomValidity("");
                if (edadInput) edadInput.value = edad + " años";
            });
        }

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
        // SOLO NÚMEROS EN TELÉFONO
        // ==========================
        const telefonoInput = document.getElementById("numeroTelefono");
        if (telefonoInput) {
            telefonoInput.addEventListener("input", function () {
                this.value = this.value.replace(/\D/g, '');
            });
        }

        // ==========================
        // SOLO LETRAS EN NOMBRES (sin caracteres repetidos en exceso)
        // ==========================
        const nombresInput = document.getElementById("nombres");
        if (nombresInput) {
            nombresInput.addEventListener("input", function () {
                let valor = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '');
                valor = limitarCaracteresRepetidos(valor, 2);
                this.value = valor;
            });
        }

        // ==========================
        // SOLO LETRAS EN APELLIDOS (sin caracteres repetidos en exceso)
        // ==========================
        const apellidosInput = document.getElementById("apellidos");
        if (apellidosInput) {
            apellidosInput.addEventListener("input", function () {
                let valor = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '');
                valor = limitarCaracteresRepetidos(valor, 2);
                this.value = valor;
            });
        }

        // ==========================
        // VALIDACIÓN GENERAL DE REGISTRO
        // ==========================
        formularioRegistro.addEventListener("submit", function (e) {

            let errores = [];

            const tipoDoc = document.getElementById("tipoDoc").value.trim();
            const cedula = document.getElementById("cedula").value.trim();
            const nombres = document.getElementById("nombres").value.trim();
            const apellidos = document.getElementById("apellidos").value.trim();
            const correo = document.getElementById("correoElectronico").value.trim();
            const fechaNac = document.getElementById("fecha").value;
            const telefono = document.getElementById("numeroTelefono").value.trim();
            const hijos = Number(document.getElementById("hijos").value);
            const anosServicio = Number(document.getElementById("anos_servicio_administracion_publica").value);
            const nivelEducacion = document.getElementById("nivel_educacion").value.trim();
            const estadoCivil = document.getElementById("estadoCivil").value;
            const genero = document.querySelector('input[name="genero"]:checked');
            const estatus = document.querySelector('select[name="estatus_laboral"]').value;

            const idCargo = document.getElementById("id_cargo").value;
            const estado = document.getElementById("selectEstado").value;
            const municipio = document.getElementById("selectMunicipio").value;
            const parroquia = document.getElementById("selectParroquia").value;
            const direccion = document.getElementById("direccion_texto").value.trim();

            if (!tipoDoc) errores.push("Tipo de Documento");
            if (!cedula) errores.push("Cédula");
            if (!nombres) errores.push("Nombres");
            if (!apellidos) errores.push("Apellidos");
            if (!genero) errores.push("Género");
            if (!estadoCivil) errores.push("Estado Civil");
            if (!correo) errores.push("Correo Electrónico");
            if (!fechaNac) errores.push("Fecha de Nacimiento");
            if (!telefono) errores.push("Número de Teléfono");
            if (document.getElementById("hijos").value === "") errores.push("Cantidad de Hijos");
            if (document.getElementById("anos_servicio_administracion_publica").value === "") errores.push("Años de Servicio en la Administración Pública");
            if (!nivelEducacion) errores.push("Nivel de Educación");
            if (!idCargo) errores.push("Cargo a Asignar");
            if (!estatus) errores.push("Estatus Laboral");
            if (!estado) errores.push("Estado");
            if (!municipio) errores.push("Municipio");
            if (!parroquia) errores.push("Parroquia");
            if (!direccion) errores.push("Dirección exacta");

            if (cedula && !/^\d{6,8}$/.test(cedula)) errores.push("Cédula: debe contener entre 6 y 8 dígitos");
            if (telefono && !/^0\d{10}$/.test(telefono)) errores.push("Número de Teléfono: debe tener 11 dígitos y comenzar por 0");

            const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (correo && !regexCorreo.test(correo)) errores.push("Correo Electrónico: formato no válido");

            if (fechaNac) {
                const hoyFecha = new Date();
                hoyFecha.setHours(0, 0, 0, 0);
                const fechaNacimientoDate = new Date(fechaNac + "T00:00:00");
                const edad = calcularEdad(fechaNac);
                if (fechaNacimientoDate > hoyFecha) errores.push("Fecha de Nacimiento: no puede ser futura");
                if (edad < EDAD_MINIMA || edad > EDAD_MAXIMA) errores.push(`Edad: debe estar entre ${EDAD_MINIMA} y ${EDAD_MAXIMA} años`);
            }

            const hijosValor = document.getElementById("hijos").value;
            const anosServicioValor = document.getElementById("anos_servicio_administracion_publica").value;
            if (hijosValor !== "" && (!Number.isInteger(hijos) || hijos < 0 || hijos > 30)) errores.push("Cantidad de Hijos: indique un entero entre 0 y 30");
            if (anosServicioValor !== "" && (!Number.isInteger(anosServicio) || anosServicio < 0 || anosServicio > 70)) errores.push("Años de Servicio: indique un entero entre 0 y 70");


            if (errores.length > 0) {
                e.preventDefault();
                showAlert("Corrige los siguientes campos o datos:\n\n• " + [...new Set(errores)].join("\n• "), false);
                return false;
            }
        });
    }

    // ==========================
    // FORMULARIO DE EDICIÓN
    // ==========================
    const formularioEdicion = document.querySelector('input[name="editar_trabajador"]')?.closest("form");

    if (formularioEdicion) {
        formularioEdicion.addEventListener("submit", function (e) {

            let errores = [];

            const estadoCivil = document.querySelector('select[name="estadoCivil"]')?.value.trim() || '';
            const correo = document.querySelector('input[name="correoElectronico"]')?.value.trim() || '';
            const telefono = document.querySelector('input[name="numeroTelefono"]')?.value.trim() || '';
            const estatus = document.querySelector('select[name="estatus_laboral"]')?.value.trim() || '';
            const hijosInput = document.querySelector('input[name="hijos"]');
            const anosServicioInput = document.querySelector('input[name="anos_servicio_administracion_publica"]');
            const hijos = Number(hijosInput?.value);
            const anosServicio = Number(anosServicioInput?.value);
            const nivelEducacion = document.querySelector('[name="nivel_educacion"]')?.value.trim() || '';
            const direccion = document.querySelector('textarea[name="direccion_texto"]')?.value.trim() || '';
            const parroquia = document.querySelector('select[name="cod_par"]')?.value || '';

            if (!estadoCivil) errores.push("Estado Civil");
            if (!correo) errores.push("Correo Electrónico");
            if (!telefono) errores.push("Número de Teléfono");
            if (!estatus) errores.push("Estatus Laboral");
            if (!hijosInput?.value) errores.push("Cantidad de Hijos");
            if (!anosServicioInput?.value) errores.push("Años de Servicio en la Administración Pública");
            if (!nivelEducacion) errores.push("Nivel de Educación");
            if (!parroquia) errores.push("Parroquia");
            if (!direccion) errores.push("Dirección exacta");

            const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (correo && !regexCorreo.test(correo)) errores.push("Correo Electrónico: formato no válido");
            if (telefono && !/^\d{11}$/.test(telefono)) errores.push("Número de Teléfono: debe tener 11 dígitos");
            if (hijosInput?.value && (!Number.isInteger(hijos) || hijos < 0 || hijos > 30)) errores.push("Cantidad de Hijos: indique un entero entre 0 y 30");
            if (anosServicioInput?.value && (!Number.isInteger(anosServicio) || anosServicio < 0 || anosServicio > 70)) errores.push("Años de Servicio: indique un entero entre 0 y 70");

            if (errores.length > 0) {
                e.preventDefault();
                showAlert("Corrige los siguientes campos o datos:\n\n• " + [...new Set(errores)].join("\n• "), false);
                return false;
            }

            // CAMBIO NECESARIO: evitar envío inmediato para que la alerta se vea
            e.preventDefault();
            showAlert("Guardando cambios...", true);

            setTimeout(() => {
                formularioEdicion.submit();
            }, 1200);
        });
    }
});

// ==========================
// LIMITAR CARACTERES REPETIDOS CONSECUTIVOS
// ==========================
// Evita que un mismo carácter se repita más de "maxRepeticiones" veces seguidas.
// Ejemplo con maxRepeticiones = 2: "Aaaaaron" -> "Aaaron" no, "Aaaron" -> "Aaron"... 
// en general "aaaa" se recorta a "aa".
function limitarCaracteresRepetidos(valor, maxRepeticiones = 2) {
    const patron = new RegExp(`(.)\\1{${maxRepeticiones},}`, 'g');
    return valor.replace(patron, (coincidencia, caracter) => caracter.repeat(maxRepeticiones));
}

// ==========================
// ALERTA PERSONALIZADA
// ==========================
function showAlert(message, success = false) {
    const alerta = document.getElementById("customAlert");
    const mensaje = document.getElementById("alertMessage");

    if (!alerta || !mensaje) return;

    mensaje.innerText = message;
    alerta.classList.remove("hidden");

    const boton = document.querySelector(".alert-box button");
    if (boton) {
        boton.style.background = success ? "#2e7d32" : "#c62828";
    }
}

let redireccionarListado = false;

function closeAlert() {

    document.getElementById("customAlert")?.classList.add("hidden");

    if (redireccionarListado) {
        window.location.href = "/FUNDACITE/vistas/lista_trabajadores.php";
    }
}



// Reglas adicionales por módulo: edad válida (18–75 años), fecha de nacimiento
// no futura y límites de longitud/repetición en datos personales.
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formPersonas") || document.getElementById("formEditarTrabajador");
    const nacimiento = form?.querySelector('#fecha, [name="fecha"], [name="fecha_nacimiento"]');
    const edad = form?.querySelector('#edad, [name="edad"]');
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    const fechaISO = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    const fechaMin = new Date(hoy); fechaMin.setFullYear(fechaMin.getFullYear()-75);
    const fechaMax = new Date(hoy); fechaMax.setFullYear(fechaMax.getFullYear()-18);
    if (nacimiento) {
        nacimiento.max = fechaISO(fechaMax);
        nacimiento.min = fechaISO(fechaMin);
        nacimiento.addEventListener('change', function () {
            if (!this.value) { if (edad) edad.value=''; this.setCustomValidity(''); return; }
            const f = new Date(this.value+'T00:00:00');
            let years = hoy.getFullYear()-f.getFullYear();
            if (hoy.getMonth()<f.getMonth() || (hoy.getMonth()===f.getMonth() && hoy.getDate()<f.getDate())) years--;
            if (f > hoy) this.setCustomValidity('La fecha de nacimiento no puede ser futura.');
            else if (years < 18) this.setCustomValidity('La edad mínima permitida es 18 años.');
            else if (years > 75) this.setCustomValidity('La edad máxima permitida es 75 años.');
            else this.setCustomValidity('');
            if (edad) edad.value = `${years} años`;
        });
    }
    const textos = form?.querySelectorAll('#nombres, #apellidos, [name="nombres"], [name="apellidos"]') || [];
    textos.forEach(el => { el.maxLength=100; el.minLength=2; });
    const correo = form?.querySelector('#correoElectronico, [name="correoElectronico"]');
    if (correo) correo.maxLength=150;
    const direccion = form?.querySelector('#direccion_texto, [name="direccion_texto"]');
    if (direccion) direccion.maxLength=255;
    form?.addEventListener('submit', function(e) {
        if (nacimiento && nacimiento.value) {
            const f = new Date(nacimiento.value+'T00:00:00');
            let years = hoy.getFullYear()-f.getFullYear();
            if (hoy.getMonth()<f.getMonth() || (hoy.getMonth()===f.getMonth() && hoy.getDate()<f.getDate())) years--;
            if (f > hoy || years < 18 || years > 75) {
                e.preventDefault(); nacimiento.reportValidity();
            }
        }
        const invalid = Array.from(textos).find(el => el.value.trim().length < 2 || el.value.trim().length > 100 || /(.)\1{2,}/i.test(el.value.trim()));
        if (invalid) { e.preventDefault(); invalid.setCustomValidity('Use entre 2 y 100 caracteres y no repita el mismo carácter más de 2 veces seguidas.'); invalid.reportValidity(); }
    });
    textos.forEach(el => el.addEventListener('input', function(){ this.setCustomValidity(''); this.value=this.value.slice(0,100).replace(/(.)\1{2,}/gi, '$1$1'); }));
});
