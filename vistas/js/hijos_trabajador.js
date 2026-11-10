document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('panelHijos');
    const lista = document.getElementById('listaEdadesHijos');
    const btn = document.getElementById('agregarHijo');
    const resumen = document.getElementById('resumenHijos');
    const radios = [...document.querySelectorAll('input[name="tiene_hijos"]')];
    if (!panel || !lista || !btn) return;

    const actualizar = () => {
        const tiene = document.querySelector('input[name="tiene_hijos"]:checked')?.value === 'si';
        panel.style.display = tiene ? 'block' : 'none';
        lista.querySelectorAll('input[name="edades_hijos[]"]').forEach(i => i.disabled = !tiene);
        if (!tiene) lista.innerHTML = '';
        let menores = 0, mayores = 0;
        lista.querySelectorAll('.fila-hijo').forEach(fila => {
            const input = fila.querySelector('input');
            const edad = Number(input.value);
            const clasificacion = fila.querySelector('.clasificacion-hijo');
            if (input.value !== '' && Number.isInteger(edad) && edad >= 0 && edad <= 75) {
                if (edad < 18) { menores++; clasificacion.textContent = 'Menor de edad'; }
                else { mayores++; clasificacion.textContent = 'Mayor de edad (18 años o más)'; }
            } else clasificacion.textContent = 'Ingresa una edad';
        });
        if (resumen) resumen.textContent = `Menores de 18 años: ${menores} | Mayores o iguales a 18 años: ${mayores}`;
    };
    const agregar = (edad = '') => {
        if (lista.querySelectorAll('.fila-hijo').length >= 30) { alert('Puede registrar como máximo 30 hijos.'); return; }
        const fila = document.createElement('div');
        fila.className = 'field fila-hijo';
        fila.innerHTML = '<label>Edad del hijo</label><div style="display:flex;gap:8px"><input type="number" name="edades_hijos[]" min="0" max="75" step="1" required><button type="button" class="btn-eliminar-hijo">Quitar</button></div><small class="clasificacion-hijo"></small>';
        const input = fila.querySelector('input'); input.value = edad;
        lista.appendChild(fila); actualizar();
    };
    btn.addEventListener('click', () => agregar());
    lista.addEventListener('input', e => { if (e.target.matches('input[name="edades_hijos[]"]')) actualizar(); });
    lista.addEventListener('click', e => {
        if (e.target.classList.contains('btn-eliminar-hijo')) { e.target.closest('.fila-hijo').remove(); actualizar(); }
    });
    radios.forEach(r => r.addEventListener('change', actualizar));
    actualizar();
});
