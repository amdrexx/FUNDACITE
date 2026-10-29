
(function () {
    'use strict';

    const DIAS_MAX_DISFRUTE_FUTURO = 365;
    const ANOS_MAX_FIN_CONTRATO_FUTURO = 5;

    const nombresCampo = {
        id_trabajador: 'Trabajador',
        nombre: 'Nombre de usuario o nombre',
        nombre_cargo: 'Nombre del cargo',
        tipoDoc: 'Tipo de documento',
        cedula: 'Cédula',
        nombres: 'Nombres',
        apellidos: 'Apellidos',
        genero: 'Género',
        estadoCivil: 'Estado civil',
        correoElectronico: 'Correo electrónico',
        numeroTelefono: 'Número de teléfono',
        fecha: 'Fecha',
        hijos: 'Cantidad de hijos',
        anos_servicio_administracion_publica: 'Años de servicio en la Administración Pública',
        nivel_educacion: 'Nivel de educación',
        id_cargo: 'Cargo',
        estatus_laboral: 'Estatus laboral',
        cod_est: 'Estado',
        cod_muni: 'Municipio',
        cod_par: 'Parroquia',
        direccion_texto: 'Dirección exacta',
        tipo_usuario: 'Tipo de usuario',
        contrasena: 'Contraseña',
        confirmar_contrasena: 'Confirmación de contraseña',
        tipo_contrato: 'Tipo de contrato',
        lugar_trabajo: 'Lugar de trabajo',
        fecha_contrato: 'Fecha de inicio del contrato',
        fecha_fin: 'Fecha de finalización del contrato',
        nombre_presidente: 'Nombre del presidente',
        cedula_presidente: 'Cédula del presidente',
        gaceta_designacion_presidente: 'Gaceta de designación',
        descripcion: 'Descripción o motivo',
        fecha_inicio: 'Fecha de inicio',
        fecha_finalizacion: 'Fecha de finalización',
        desde: 'Desde',
        hasta: 'Hasta',
        tipo_remuneracion: 'Tipo de permiso',
        requiere_suplente: 'Indicación de suplente',
        tipoprima: 'Nombre de la prima',
        porcentaje: 'Porcentaje',
        tipo_salario: 'Tipo de salario',
        monto: 'Monto del salario',
        estado: 'Estado',
        tipo_personal: 'Tipo de personal',
        nombre_director: 'Nombre del director',
        motivo: 'Motivo',
        parroquia: 'Nombre de la parroquia'
    };

    const requeridosPorFormulario = {
        formCargos: ['nombre_cargo'],
        formEditarCargo: ['nombre_cargo'],
        formUsuarios: ['id_trabajador', 'nombre', 'contrasena', 'confirmar_contrasena', 'tipo_usuario'],
        formEditarUsuario: ['id_trabajador', 'nombre', 'tipo_usuario'],
        formPersonas: [
            'nombres', 'apellidos', 'tipoDoc', 'cedula', 'genero', 'estadoCivil', 'fecha',
            'correoElectronico', 'numeroTelefono', 'hijos',
            'anos_servicio_administracion_publica', 'nivel_educacion', 'id_cargo',
            'estatus_laboral', 'cod_est', 'cod_muni', 'cod_par', 'direccion_texto'
        ],
        formEditarTrabajador: [
            'id_trabajador', 'genero', 'hijos', 'anos_servicio_administracion_publica', 'nivel_educacion',
            'estadoCivil', 'correoElectronico', 'numeroTelefono', 'estatus_laboral',
            'id_cargo', 'cod_est', 'cod_muni', 'cod_par', 'direccion_texto'
        ],
        formContratos: [
            'id_trabajador', 'tipo_contrato', 'lugar_trabajo', 'fecha_contrato',
            'nombre_presidente', 'cedula_presidente', 'gaceta_designacion_presidente'
        ],
        formdisfrute: ['id_trabajador', 'fecha_inicio', 'desde', 'hasta', 'descripcion', 'tipo_remuneracion', 'requiere_suplente'],
        formprimas: ['tipoprima', 'porcentaje', 'fecha', 'estado'],
        formsalario: ['tipo_salario', 'fecha', 'monto'],
        formAumento: ['porcentaje'],
        formconstancia: ['id_trabajador', 'fecha', 'tipo_personal', 'motivo']
    };

    function fechaLocalISO(fecha) {
        const f = fecha || new Date();
        const anio = f.getFullYear();
        const mes = String(f.getMonth() + 1).padStart(2, '0');
        const dia = String(f.getDate()).padStart(2, '0');
        return `${anio}-${mes}-${dia}`;
    }

    function sumarDiasISO(iso, dias) {
        const f = convertirFecha(iso) || new Date();
        f.setDate(f.getDate() + dias);
        return fechaLocalISO(f);
    }

    function sumarAnosISO(iso, anos) {
        const f = convertirFecha(iso) || new Date();
        f.setFullYear(f.getFullYear() + anos);
        return fechaLocalISO(f);
    }

    function convertirFecha(valor) {
        if (!valor || !/^\d{4}-\d{2}-\d{2}$/.test(valor)) return null;
        const [anio, mes, dia] = valor.split('-').map(Number);
        const f = new Date(anio, mes - 1, dia);
        if (f.getFullYear() !== anio || f.getMonth() !== mes - 1 || f.getDate() !== dia) return null;
        return f;
    }

    function obtenerControles(formulario, nombre) {
        return Array.from(formulario.elements).filter(campo => campo.name === nombre);
    }

    function obtenerValor(formulario, nombre) {
        const controles = obtenerControles(formulario, nombre);
        if (!controles.length) return '';
        const radio = controles.filter(c => c.type === 'radio');
        if (radio.length) return radio.find(c => c.checked)?.value || '';
        const campo = controles.find(c => c.type !== 'checkbox') || controles[0];
        return String(campo.value ?? '').trim();
    }

    function estaEnEdicion(formulario) {
        if (/^formEditar/i.test(formulario.id)) return true;
        if (formulario.querySelector('input[name="editar_trabajador"]')) return true;
        if (formulario.querySelector('input[name="id_constancia"], input[name="id_contrato"], input[name="id_salario"], input[name="id_prima"], input[name="id_cargo"], input[name="id_usuario"]')) return true;
        const accion = formulario.querySelector('input[name="accion"]')?.value?.toLowerCase();
        if (accion && ['actualizar', 'editar', 'modificar'].includes(accion)) return true;
        return false;
    }

    function mostrarErrores(errores, primerCampo) {
        const mensaje = 'Corrige los siguientes campos antes de continuar:\n\n• ' + [...new Set(errores)].join('\n• ');
        const alerta = document.getElementById('customAlert');
        const texto = document.getElementById('alertMessage');
        if (alerta && texto) {
            texto.textContent = mensaje;
            alerta.classList.remove('hidden');
            const boton = alerta.querySelector('button');
            if (boton) boton.style.background = '#c62828';
        } else if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({ title: 'Revisa el formulario', text: errores.join('\n'), icon: 'warning', confirmButtonColor: '#007bff' });
        } else {
            alert(mensaje);
        }
        if (primerCampo && typeof primerCampo.focus === 'function') {
            try { primerCampo.focus({ preventScroll: false }); } catch (_) { primerCampo.focus(); }
        }
    }

    function agregarError(errores, nombre) {
        errores.push(nombresCampo[nombre] || nombre.replace(/\[\]/g, ''));
    }

    function reglaFecha(campo, formulario, hoy) {
        const nombre = campo.name || campo.id || '';
        const valor = campo.value;
        const original = campo.dataset.validationOriginal ?? '';
        const edicion = estaEnEdicion(formulario);
        const sinCambiosHistoricos = edicion && valor !== '' && valor === original;
        const idFormulario = formulario.id;

        if (!valor) return null;
        const fecha = convertirFecha(valor);
        if (!fecha) return `${nombresCampo[nombre] || nombre}: indique una fecha válida.`;
        if (sinCambiosHistoricos) return null;

        const hoyISO = fechaLocalISO(hoy);
        const maxDisfrute = sumarDiasISO(hoyISO, DIAS_MAX_DISFRUTE_FUTURO);
        const maxContrato = sumarAnosISO(hoyISO, ANOS_MAX_FIN_CONTRATO_FUTURO);

        if (['fecha_inicio', 'desde', 'fecha_contrato'].includes(nombre) && valor < hoyISO) {
            return `${nombresCampo[nombre] || nombre}: no puede ser anterior a hoy (${hoyISO}).`;
        }

        if (nombre === 'fecha_finalizacion' || nombre === 'hasta') {
            if (valor > maxDisfrute) {
                return `${nombresCampo[nombre]}: no puede superar el ${maxDisfrute} (máximo un año en el futuro).`;
            }
        }

        if (nombre === 'fecha_fin' && valor > maxContrato) {
            return `Fecha de finalización del contrato: no puede superar el ${maxContrato} (máximo ${ANOS_MAX_FIN_CONTRATO_FUTURO} años en el futuro).`;
        }

        if (nombre === 'fecha' && idFormulario === 'formPersonas') {
            if (valor > hoyISO) return 'Fecha de nacimiento: no puede ser futura.';
            const nacimiento = fecha;
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            if (hoy.getMonth() < nacimiento.getMonth() || (hoy.getMonth() === nacimiento.getMonth() && hoy.getDate() < nacimiento.getDate())) edad--;
            if (edad < 18) return 'Fecha de nacimiento: el trabajador debe tener al menos 18 años.';
        }

        if (nombre === 'fecha' && idFormulario === 'formconstancia') {
            if (valor < hoyISO) return 'Fecha de emisión de la constancia: no puede ser anterior a hoy.';
            if (valor > hoyISO) return 'Fecha de emisión de la constancia: no puede ser futura.';
        }

        if (nombre === 'fecha' && (idFormulario === 'formsalario' || idFormulario === 'formprimas') && valor > hoyISO) {
            return 'La fecha de registro no puede ser futura.';
        }

        // Fechas restantes de alta/registro: no permitir valores fuera del día actual,
        // salvo las fechas de inicio y finalización gestionadas por sus formularios.
        if (nombre.startsWith('fecha_') && !['fecha_inicio', 'fecha_finalizacion', 'fecha_contrato', 'fecha_fin'].includes(nombre)) {
            if (valor > hoyISO) return `${nombresCampo[nombre] || nombre}: no puede ser futura.`;
        }

        // El campo genérico "fecha" solo admite el pasado para nacimiento, salario y prima.
        if (nombre === 'fecha' && !['formPersonas', 'formsalario', 'formprimas'].includes(idFormulario) && valor > hoyISO) {
            return `${nombresCampo[nombre] || nombre}: no puede ser futura.`;
        }

        return null;
    }

    function configurarLimitesFecha(formulario) {
        const hoy = fechaLocalISO();
        const edicion = estaEnEdicion(formulario);
        const camposFecha = Array.from(formulario.querySelectorAll('input[type="date"]'));

        camposFecha.forEach(campo => {
            if (campo.dataset.validationOriginal === undefined) {
                campo.dataset.validationOriginal = campo.value || '';
            }
            const nombre = campo.name || campo.id || '';
            let minimo = null;
            let maximo = null;

            if (['fecha_inicio', 'desde', 'fecha_contrato'].includes(nombre)) minimo = hoy;
            if (nombre === 'fecha_finalizacion' || nombre === 'hasta') maximo = sumarDiasISO(hoy, DIAS_MAX_DISFRUTE_FUTURO);
            if (nombre === 'fecha_fin') maximo = sumarAnosISO(hoy, ANOS_MAX_FIN_CONTRATO_FUTURO);
            if (nombre === 'fecha' && formulario.id === 'formPersonas') maximo = hoy;
            if (nombre === 'fecha' && (formulario.id === 'formsalario' || formulario.id === 'formprimas')) maximo = hoy;
            if (nombre === 'fecha' && formulario.id === 'formconstancia') {
                minimo = hoy;
                maximo = hoy;
            }

            // No hacer que el navegador bloquee un registro antiguo que se está editando
            // sin cambiar su fecha; las reglas se revalidan si se modifica el valor.
            const valorInicial = campo.dataset.validationOriginal || '';
            const fechaInicial = convertirFecha(valorInicial);
            if (edicion && fechaInicial) {
                if (minimo && valorInicial < minimo) minimo = null;
                if (maximo && valorInicial > maximo) maximo = null;
            }

            if (minimo) campo.min = minimo;
            else campo.removeAttribute('min');
            if (maximo) campo.max = maximo;
            else campo.removeAttribute('max');

            campo.addEventListener('change', () => {
                // El validador de envío evalúa cambios y pares de fechas con más contexto.
                campo.setCustomValidity('');
            });
        });
    }

    function validarFormulario(formulario, evento) {
        if (formulario.method.toLowerCase() !== 'post') return;
        const submitter = evento.submitter;
        if (submitter && (submitter.type === 'reset' || ['limpiar', 'clear'].includes(String(submitter.value || '').toLowerCase()))) return;

        const errores = [];
        let primerCampo = null;
        const ids = requeridosPorFormulario[formulario.id] || [];

        ids.forEach(nombre => {
            const controles = obtenerControles(formulario, nombre);
            // Algunos campos son condicionales (por ejemplo el cargo para salario base).
            if (!controles.length) return;
            if (nombre === 'id_cargo' && formulario.id === 'formsalario' && obtenerValor(formulario, 'tipo_salario') !== 'cargo') return;
            if (nombre === 'fecha_fin' && formulario.id === 'formContratos') {
                const tipo = obtenerValor(formulario, 'tipo_contrato').toLowerCase();
                if (tipo.includes('indefinido') || tipo.includes('indeterminado')) return;
            }
            if (controles.every(c => c.disabled)) {
                agregarError(errores, nombre);
                primerCampo ||= controles[0];
                return;
            }
            if (['genero', 'tipo_remuneracion', 'requiere_suplente'].includes(nombre)) {
                if (!controles.some(c => c.checked)) {
                    agregarError(errores, nombre);
                    primerCampo ||= controles[0];
                }
                return;
            }
            const valor = obtenerValor(formulario, nombre);
            if (!valor || valor === '0' && ['id_trabajador', 'id_cargo', 'cod_est', 'cod_muni', 'cod_par'].includes(nombre)) {
                agregarError(errores, nombre);
                primerCampo ||= controles.find(c => c.type !== 'hidden') || controles[0];
            }
        });

        // Los formularios de ubicación comparten estructura, por ello se detectan por sus campos.
        if (!formulario.id) {
            const tieneParroquia = obtenerControles(formulario, 'parroquia[]').length > 0;
            const selectEstado = obtenerControles(formulario, 'cod_est').some(c => c.tagName === 'SELECT');
            const tieneMunicipio = obtenerControles(formulario, 'cod_muni').length > 0;
            const tieneNombre = obtenerControles(formulario, 'nombre').length > 0;
            if (tieneParroquia) {
                ['cod_est', 'cod_muni', 'parroquia[]'].forEach(nombre => {
                    obtenerControles(formulario, nombre).forEach(campo => {
                        if (!String(campo.value || '').trim()) {
                            agregarError(errores, nombre);
                            primerCampo ||= campo;
                        }
                    });
                });
            } else if (selectEstado && tieneNombre) {
                ['cod_est', 'nombre'].forEach(nombre => {
                    if (!obtenerValor(formulario, nombre)) {
                        agregarError(errores, nombre);
                        primerCampo ||= obtenerControles(formulario, nombre)[0];
                    }
                });
            } else if (tieneMunicipio && tieneNombre) {
                ['cod_est', 'nombre'].forEach(nombre => {
                    if (!obtenerValor(formulario, nombre)) {
                        agregarError(errores, nombre);
                        primerCampo ||= obtenerControles(formulario, nombre)[0];
                    }
                });
            } else if (tieneNombre && obtenerControles(formulario, 'cod_est').some(c => c.type === 'hidden')) {
                if (!obtenerValor(formulario, 'nombre')) {
                    agregarError(errores, 'nombre');
                    primerCampo ||= obtenerControles(formulario, 'nombre')[0];
                }
            }
        }

        // Validación genérica de todo control marcado como obligatorio, incluso en los formularios
        // que no tienen un identificador propio.
        Array.from(formulario.elements).forEach(campo => {
            if (campo.disabled || campo.type === 'hidden' || campo.type === 'submit' || campo.type === 'button' || campo.type === 'reset') return;
            if (campo.tagName === 'SELECT' && String(campo.value || '').trim()) {
                const opcionValida = Array.from(campo.options || []).some(opcion => opcion.value === campo.value && !opcion.disabled);
                if (!opcionValida) {
                    errores.push(`${nombresCampo[campo.name] || campo.name || campo.id}: seleccione una opción válida.`);
                    primerCampo ||= campo;
                }
            }
            if (campo.required && !String(campo.value || '').trim() && campo.type !== 'radio' && campo.type !== 'checkbox') {
                const nombre = campo.name || campo.id || 'Campo obligatorio';
                agregarError(errores, nombre);
                primerCampo ||= campo;
            }
            if (campo.maxLength > 0 && String(campo.value || '').length > campo.maxLength) {
                errores.push(`${nombresCampo[campo.name] || campo.name || campo.id}: no puede superar ${campo.maxLength} caracteres.`);
                primerCampo ||= campo;
            }
        });

        const esFormularioTrabajador = ['formPersonas', 'formEditarTrabajador'].includes(formulario.id);
        const camposTexto = Array.from(formulario.querySelectorAll('input[type="text"], input[type="email"], textarea'))
            .filter(campo => !campo.disabled && !campo.readOnly && campo.type !== 'hidden');

        camposTexto.forEach(campo => {
            const nombre = campo.name || campo.id || '';
            const valor = String(campo.value || '').trim();
            if (!valor) return;

            if (campo.type === 'email' || nombre === 'correoElectronico') {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valor)) {
                    errores.push('Correo electrónico: indique una dirección válida.');
                    primerCampo ||= campo;
                }
            }

            if (nombre === 'cedula' && !/^\d{6,8}$/.test(valor)) {
                errores.push('Cédula: debe contener entre 6 y 8 dígitos numéricos.');
                primerCampo ||= campo;
            }
            if (nombre === 'numeroTelefono' && !/^0\d{10}$/.test(valor)) {
                errores.push('Número de teléfono: debe tener 11 dígitos y comenzar por 0.');
                primerCampo ||= campo;
            }

            const campoNombreComun = ['nombres', 'apellidos', 'nombre_cargo', 'nombre_director', 'nombre_presidente', 'tipoprima', 'parroquia[]'].includes(nombre)
                || (nombre === 'nombre' && !['formUsuarios', 'formEditarUsuario'].includes(formulario.id));
            if (campoNombreComun) {
                if (valor.length < 2 || valor.length > 100 || !/^[\p{L}\p{M}][\p{L}\p{M}\s'.-]*$/u.test(valor)) {
                    errores.push(`${nombresCampo[nombre] || nombre.replace(/\[\]/g, '')}: use letras, espacios, apóstrofes o guiones y una longitud de 2 a 100 caracteres.`);
                    primerCampo ||= campo;
                }
            }

            if (nombre === 'nombre' && ['formUsuarios', 'formEditarUsuario'].includes(formulario.id)) {
                if (!/^[A-Za-z0-9._-]{4,30}$/.test(valor)) {
                    errores.push('Nombre de usuario: use entre 4 y 30 caracteres, sin espacios (letras, números, punto, guion o guion bajo).');
                    primerCampo ||= campo;
                }
            }

            if (nombre === 'lugar_trabajo' && (valor.length < 3 || valor.length > 200)) {
                errores.push('Lugar de trabajo: debe contener entre 3 y 200 caracteres.');
                primerCampo ||= campo;
            }

            if (['descripcion', 'motivo'].includes(nombre) && (valor.length < 5 || valor.length > 2000)) {
                errores.push(`${nombresCampo[nombre]}: debe contener entre 5 y 2000 caracteres.`);
                primerCampo ||= campo;
            }

            if (nombre === 'direccion_texto' && valor.length > 255) {
                errores.push('Dirección exacta: no puede superar 255 caracteres.');
                primerCampo ||= campo;
            }
            if (nombre === 'observaciones' && valor.length > 2000) {
                errores.push('Observaciones: no puede superar 2000 caracteres.');
                primerCampo ||= campo;
            }

            if (nombre === 'cedula_presidente' && !/^[VE]-?\d{1,2}\.?\d{3}\.?\d{3}$/i.test(valor)) {
                errores.push('Cédula del presidente: utilice un formato como V-19.817.987.');
                primerCampo ||= campo;
            }

            if (nombre === 'gaceta_designacion_presidente' && (valor.length < 1 || valor.length > 120)) {
                errores.push('Gaceta de designación: indique un valor de hasta 120 caracteres.');
                primerCampo ||= campo;
            }

            if (nombre === 'hijos') {
                const num = Number(valor);
                if (!Number.isInteger(num) || num < 0 || num > 30) {
                    errores.push('Cantidad de hijos: indique un entero entre 0 y 30.');
                    primerCampo ||= campo;
                }
            }

            if (nombre === 'anos_servicio_administracion_publica') {
                const num = Number(valor);
                if (!Number.isInteger(num) || num < 0 || num > 70) {
                    errores.push('Años de servicio: indique un entero entre 0 y 70.');
                    primerCampo ||= campo;
                }
            }
        });

        // Contraseña y confirmación: editar permite dejar ambas vacías para conservar la clave actual.
        if (['formUsuarios', 'formEditarUsuario'].includes(formulario.id)) {
            const password = obtenerValor(formulario, 'contrasena');
            const confirmacion = obtenerValor(formulario, 'confirmar_contrasena');
            const esRegistro = formulario.id === 'formUsuarios';
            if (esRegistro && password.length < 8) {
                errores.push('Contraseña: debe tener al menos 8 caracteres.');
                primerCampo ||= obtenerControles(formulario, 'contrasena')[0];
            } else if (!esRegistro && password && password.length < 8) {
                errores.push('Nueva contraseña: debe tener al menos 8 caracteres.');
                primerCampo ||= obtenerControles(formulario, 'contrasena')[0];
            }
            if ((esRegistro || password || confirmacion) && password !== confirmacion) {
                errores.push('La contraseña y su confirmación no coinciden.');
                primerCampo ||= obtenerControles(formulario, 'confirmar_contrasena')[0];
            }
        }

        // Rangos numéricos y reglas condicionales.
        Array.from(formulario.querySelectorAll('input[type="number"]')).forEach(campo => {
            if (campo.disabled || campo.value.trim() === '') return;
            const nombre = campo.name || campo.id;
            const valor = Number(campo.value);
            if (!Number.isFinite(valor)) {
                errores.push(`${nombresCampo[nombre] || nombre}: indique un número válido.`);
                primerCampo ||= campo;
                return;
            }
            if (campo.min !== '' && valor < Number(campo.min)) {
                errores.push(`${nombresCampo[nombre] || nombre}: no puede ser menor que ${campo.min}.`);
                primerCampo ||= campo;
            }
            if (campo.max !== '' && valor > Number(campo.max)) {
                errores.push(`${nombresCampo[nombre] || nombre}: no puede ser mayor que ${campo.max}.`);
                primerCampo ||= campo;
            }
            if ((campo.step === '1' || ['hijos', 'anos_servicio_administracion_publica'].includes(nombre)) && !Number.isInteger(valor)) {
                errores.push(`${nombresCampo[nombre] || nombre}: debe ser un número entero.`);
                primerCampo ||= campo;
            }
            if (nombre === 'monto' && valor <= 0) {
                errores.push('Monto del salario: debe ser mayor que cero.');
                primerCampo ||= campo;
            }
            if (nombre === 'porcentaje' && formulario.id === 'formprimas' && (valor <= 0 || valor > 100)) {
                errores.push('Porcentaje de prima: debe ser mayor que 0 y no superar 100%.');
                primerCampo ||= campo;
            }
        });

        // Salario por cargo exige cargo; salario base no lo requiere.
        if (formulario.id === 'formsalario' && obtenerValor(formulario, 'tipo_salario') === 'cargo' && !obtenerValor(formulario, 'id_cargo')) {
            errores.push('Seleccione el cargo al que corresponde el salario.');
            primerCampo ||= obtenerControles(formulario, 'id_cargo')[0];
        }

        // Validaciones de fecha por contexto.
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        Array.from(formulario.querySelectorAll('input[type="date"]')).forEach(campo => {
            const error = reglaFecha(campo, formulario, hoy);
            if (error) {
                errores.push(error);
                primerCampo ||= campo;
            }
        });

        // Validar rangos desde/hasta y fecha de inicio/finalización.
        [
            ['fecha_inicio', 'fecha_finalizacion'],
            ['desde', 'hasta'],
            ['fecha_contrato', 'fecha_fin']
        ].forEach(([inicioNombre, finNombre]) => {
            const inicio = obtenerValor(formulario, inicioNombre);
            const fin = obtenerValor(formulario, finNombre);
            if (!inicio || !fin) return;
            if (fin < inicio) {
                errores.push(`${nombresCampo[finNombre]} no puede ser anterior a ${nombresCampo[inicioNombre]}.`);
                primerCampo ||= obtenerControles(formulario, finNombre)[0];
            }
        });

        if (errores.length) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            mostrarErrores(errores, primerCampo);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(formulario => {
            if (formulario.method.toLowerCase() !== 'post') return;
            configurarLimitesFecha(formulario);
            formulario.addEventListener('submit', evento => validarFormulario(formulario, evento), true);
        });
    });
})();
