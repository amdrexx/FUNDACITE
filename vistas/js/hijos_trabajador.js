document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('panelHijos');
    const lista = document.getElementById('listaFechasHijos');
    const btn = document.getElementById('agregarHijo');
    const resumen = document.getElementById('resumenHijos');
    const radios = [...document.querySelectorAll('input[name="tiene_hijos"]')];
    if (!panel || !lista || !btn) return;

    const edadDesdeFecha = (valor) => {
        if (!valor) return null;
        const nacimiento = new Date(`${valor}T00:00:00`);
        if (Number.isNaN(nacimiento.getTime())) return null;
        const hoy = new Date();
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
            const input = fila.querySelector('input[type="date"]');
            const edad = edadDesdeFecha(input.value);
            const clasificacion = fila.querySelector('.clasificacion-hijo');
            if (edad !== null && edad >= 0) {
                if (edad < 18) { menores++; clasificacion.textContent = `Menor de edad · ${edad} años`; }
                else { mayores++; clasificacion.textContent = `Mayor de edad · ${edad} años`; }
            } else clasificacion.textContent = 'Selecciona una fecha válida';
        });
        if (resumen) resumen.textContent = `Menores de 18 años: ${menores} | Mayores o iguales a 18 años: ${mayores}`;
    };

    const agregar = (fecha = '') => {
        if (lista.querySelectorAll('.fila-hijo').length >= 30) { alert('Puede registrar como máximo 30 hijos.'); return; }
        const fila = document.createElement('div');
        fila.className = 'field fila-hijo';
        fila.innerHTML = '<label>Fecha de nacimiento del hijo</label><div style="display:flex;gap:8px"><input type="date" name="fechas_nacimiento_hijos[]" max="' + new Date().toISOString().slice(0, 10) + '" required><button type="button" class="btn-eliminar-hijo">Quitar</button></div><small class="clasificacion-hijo"></small>';
        const input = fila.querySelector('input'); input.value = fecha;
        lista.appendChild(fila); actualizar();
    };
    btn.addEventListener('click', () => agregar());
    lista.addEventListener('input', e => { if (e.target.matches('input[name="fechas_nacimiento_hijos[]"]')) actualizar(); });
    lista.addEventListener('change', e => { if (e.target.matches('input[name="fechas_nacimiento_hijos[]"]')) actualizar(); });
    lista.addEventListener('click', e => {
        if (e.target.classList.contains('btn-eliminar-hijo')) { e.target.closest('.fila-hijo').remove(); actualizar(); }
    });
    radios.forEach(r => r.addEventListener('change', actualizar));
    actualizar();
});
