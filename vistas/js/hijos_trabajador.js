document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('panelHijos');
    const lista = document.getElementById('listaEdadesHijos');
    const btn = document.getElementById('agregarHijo');
    const resumen = document.getElementById('resumenHijos');
    const radios = [...document.querySelectorAll('input[name="tiene_hijos"]')];
    if (!panel || !lista || !btn) return;

    const EDAD_MIN_HIJO = 0;
    const EDAD_MAX_HIJO = 75;

    const fechaISO = (fecha) =>
        `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const fechaMinimaHijo = new Date(hoy);
    fechaMinimaHijo.setFullYear(fechaMinimaHijo.getFullYear() - EDAD_MAX_HIJO);
    const minHijo = fechaISO(fechaMinimaHijo);
    const maxHijo = fechaISO(hoy);

    const calcularEdad = (valor) => {
        if (!valor) return null;
        const nacimiento = new Date(valor + 'T00:00:00');
        if (Number.isNaN(nacimiento.getTime())) return null;
        let edad = hoy.getFullYear() - nacimiento.getFullYear();
        const mes = hoy.getMonth() - nacimiento.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) edad--;
        return edad;
    };

    const actualizar = () => {
        const tiene = document.querySelector('input[name="tiene_hijos"]:checked')?.value === 'si';
        panel.style.display = tiene ? 'block' : 'none';
        lista.querySelectorAll('input[name="fechas_nacimiento_hijos[]"]').forEach(i => i.disabled = !tiene);
        if (!tiene) lista.innerHTML = '';

        let menores = 0, mayores = 0;
        lista.querySelectorAll('.fila-hijo').forEach(fila => {
            const input = fila.querySelector('input[name="fechas_nacimiento_hijos[]"]');
            const edadCampo = fila.querySelector('.edad-hijo-calculada');
            const clasificacion = fila.querySelector('.clasificacion-hijo');
            const edad = calcularEdad(input.value);

            if (input.value && (input.value > maxHijo || input.value < minHijo)) {
                input.setCustomValidity(`La fecha de nacimiento del hijo debe corresponder a una edad entre ${EDAD_MIN_HIJO} y ${EDAD_MAX_HIJO} años, y no puede ser futura.`);
                if (edadCampo) edadCampo.value = '';
                clasificacion.textContent = 'Fecha fuera de rango';
                return;
            }
            input.setCustomValidity('');

            if (edad !== null) {
                if (edadCampo) edadCampo.value = edad + ' años';
                if (edad < 18) { menores++; clasificacion.textContent = 'Menor de edad'; }
                else { mayores++; clasificacion.textContent = 'Mayor de edad (18 años o más)'; }
            } else {
                if (edadCampo) edadCampo.value = '';
                clasificacion.textContent = 'Ingresa una fecha válida';
            }
        });
        if (resumen) resumen.textContent = `Menores de 18 años: ${menores} | Mayores o iguales a 18 años: ${mayores}`;
    };

    const agregar = (fecha = '') => {
        if (lista.querySelectorAll('.fila-hijo').length >= 30) { alert('Puede registrar como máximo 30 hijos.'); return; }
        const fila = document.createElement('div');
        fila.className = 'field fila-hijo';
        fila.innerHTML = `
           
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <input type="date" name="fechas_nacimiento_hijos[]" min="${minHijo}" max="${maxHijo}" required>
                <input type="text" class="edad-hijo-calculada" placeholder="Edad" readonly style="max-width:110px">
                <button type="button" class="btn-eliminar-hijo">Quitar</button>
            </div>
            <small class="clasificacion-hijo"></small>`;
        const input = fila.querySelector('input[name="fechas_nacimiento_hijos[]"]');
        input.value = fecha;
        lista.appendChild(fila);
        actualizar();
    };

    // Asignar los límites min/max a las filas que ya vienen precargadas (edición o reintento de registro).
    lista.querySelectorAll('input[name="fechas_nacimiento_hijos[]"]').forEach(input => {
        input.min = minHijo;
        input.max = maxHijo;
    });

    btn.addEventListener('click', () => agregar());
    lista.addEventListener('input', e => { if (e.target.matches('input[name="fechas_nacimiento_hijos[]"]')) actualizar(); });
    lista.addEventListener('click', e => {
        if (e.target.classList.contains('btn-eliminar-hijo')) { e.target.closest('.fila-hijo').remove(); actualizar(); }
    });
    radios.forEach(r => r.addEventListener('change', actualizar));
    actualizar();
});
