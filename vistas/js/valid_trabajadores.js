document.addEventListener("DOMContentLoaded", function () {

    
    // ==========================
    // FORMULARIO DE REGISTRO
    // ==========================
    const formularioRegistro = document.getElementById("formPersonas");

    if (formularioRegistro) {

        const fechaNacimiento = document.getElementById("fecha");
        const edadInput = document.getElementById("edad");

        // ==========================
        // CALCULAR EDAD
        // ==========================
        if (fechaNacimiento && edadInput) {
            fechaNacimiento.addEventListener("change", function () {

                if (!this.value) {
                    edadInput.value = "";
                    return;
                }

                const nacimiento = new Date(this.value + "T00:00:00");
                const hoy = new Date();

                let edad = hoy.getFullYear() - nacimiento.getFullYear();
                const mes = hoy.getMonth() - nacimiento.getMonth();

                if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                    edad--;
                }

                edadInput.value = edad + " años";
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

            const nacimiento = new Date(fechaNac);
            const hoy = new Date();

            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();

            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }

            if (fechaNac && edad < 18) errores.push("Fecha de Nacimiento: el trabajador debe tener al menos 18 años");

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

