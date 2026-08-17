@extends('layouts.app')

@section('titulo', 'Detalle de campaña')

@section('content')
  <header class="page-header">
    <a href="{{ route('brigadas.index') }}" class="btn btn-ghost btn-sm" style="margin-bottom:var(--space-sm)">
      <span class="material-symbols-rounded">arrow_back</span> Volver a Campañas
    </a>
    <div class="flex-between">
      <h1 id="campana-nombre">Cargando...</h1>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="button" class="btn btn-outline" id="btn-editar-campana">
          <span class="material-symbols-rounded">edit</span> Editar
        </button>
        <button type="button" class="btn btn-outline" style="color:var(--color-error);border-color:var(--color-error)" id="btn-eliminar-campana">
          <span class="material-symbols-rounded">delete</span> Eliminar
        </button>
        <button type="button" class="btn btn-outline" id="btn-iniciar-campana" style="display:none">
          <span class="material-symbols-rounded">play_arrow</span> Iniciar campaña
        </button>
        <button type="button" class="btn btn-outline" id="btn-finalizar-campana" style="display:none">
          <span class="material-symbols-rounded">task_alt</span> Finalizar
        </button>
        <a href="#" class="btn btn-primary" id="btn-registrar-turno" style="display:none">
          <span class="material-symbols-rounded">add</span> Registrar turno
        </a>
      </div>
    </div>
  </header>

  <div class="card" style="margin-bottom:var(--space-lg)">
    <div id="campana-info">
      <p class="page-subtitle">Cargando...</p>
    </div>
  </div>

  <section class="section-block">
    <div class="flex-between">
      <h2 class="section-title">Médicos asignados</h2>
      <button type="button" class="btn btn-outline btn-sm" id="btn-asignar-medicos">
        <span class="material-symbols-rounded">person_add</span> Asignar médicos
      </button>
    </div>
    <div class="card" id="medicos-asignados">
      <p class="page-subtitle">Cargando...</p>
    </div>
  </section>

  <section class="section-block">
    <h2 class="section-title">Pacientes asignados</h2>
    <div class="table-responsive">
      <table class="data-table" id="tabla-pacientes-campana">
        <thead>
          <tr>
            <th>Paciente</th>
            <th>Turno</th>
            <th>Especialidad</th>
            <th>Estado</th>
            <th>Hora</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-pacientes-campana-body">
          <tr><td colspan="6">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection

@section('modals')
  <div class="modal-overlay" id="modal-editar-campana">
    <div class="modal" role="dialog" style="max-width:640px">
      <h2 class="modal-title">Editar campaña</h2>
      <form id="form-editar-campana">
        <div class="form-group">
          <label class="form-label" for="editar-nombre">Nombre de la campaña</label>
          <input type="text" id="editar-nombre" class="form-control" minlength="5" required>
          <span class="form-error-msg" id="error-editar-nombre" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label" for="editar-descripcion">Descripción (opcional)</label>
          <textarea id="editar-descripcion" class="form-control"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="editar-fecha">Fecha</label>
          <input type="date" id="editar-fecha" class="form-control" required>
          <span class="form-error-msg" id="error-editar-fecha" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label" for="editar-ubicacion">Ubicación</label>
          <input type="text" id="editar-ubicacion" class="form-control" minlength="5" required>
          <span class="form-error-msg" id="error-editar-ubicacion" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label">Especialidades ofrecidas y cupos</label>
          <div id="editar-especialidades-chips" style="display:flex;flex-wrap:wrap;gap:8px">
            <p class="page-subtitle">Cargando especialidades...</p>
          </div>
          <div id="editar-especialidades-cupos" style="margin-top:var(--space-sm);display:flex;flex-direction:column;gap:8px"></div>
          <span class="form-error-msg" id="error-editar-especialidades" hidden>Selecciona al menos una especialidad y define sus cupos.</span>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-guardar-campana">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="modal-eliminar-campana">
    <div class="modal" role="dialog">
      <h2 class="modal-title">Eliminar campaña</h2>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar <strong id="eliminar-campana-nombre"></strong>? Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
        <button type="button" class="btn btn-primary" style="background:var(--color-error)" id="btn-confirmar-eliminar-campana">Eliminar</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="modal-medicos-campana">
    <div class="modal" role="dialog" style="max-width:480px">
      <h2 class="modal-title">Asignar médicos</h2>
      <div class="form-group">
        <div id="medicos-checks" style="display:flex;flex-direction:column;gap:6px;max-height:320px;overflow-y:auto">
          <p class="page-subtitle">Cargando...</p>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-medicos">Guardar</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  const brigadaId = @json($id);
  let brigadaActual = null;

  function estaActiva(brigada) {
    return brigada.estado === 'programada' || brigada.estado === 'en_curso';
  }

  async function cargarCampana() {
    const resultado = await Api.get(`/brigadas/${brigadaId}`);
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      window.location.href = '{{ route("brigadas.index") }}';
      return;
    }

    brigadaActual = resultado.data;
    pintarCampana();
    pintarPacientes(brigadaActual.especialidades || []);
    cargarMedicosAsignados();
  }

  function pintarCampana() {
    const b = brigadaActual;
    document.getElementById('campana-nombre').textContent = b.nombre;
    document.title = `${b.nombre} — BrigadaMedica`;

    const especialidades = (b.especialidades || [])
      .map(e => `${e.nombre} (${e.cupos_ocupados ?? 0}/${e.cupos} cupos)`)
      .join(', ') || 'Sin especialidades asignadas';

    document.getElementById('campana-info').innerHTML = `
      <div class="detail-row"><span class="detail-label">Estado</span><span class="chip ${estadoBrigadaChipClass(b.estado)}">${estadoBrigadaLabel(b.estado)}</span></div>
      <div class="detail-row"><span class="detail-label">Fecha</span><span>${formatearFecha(b.fecha)}</span></div>
      <div class="detail-row"><span class="detail-label">Ubicación</span><span>${b.ubicacion}</span></div>
      <div class="detail-row"><span class="detail-label">Coordinador</span><span>${b.coordinador?.name ?? '—'}</span></div>
      <div class="detail-row"><span class="detail-label">Confirmaciones desde la app</span><span><strong>${b.confirmaciones?.asistiran ?? 0}</strong> asistirán · ${b.confirmaciones?.tal_vez ?? 0} tal vez · ${b.confirmaciones?.no_asistiran ?? 0} no asistirán</span></div>
      ${b.descripcion ? `<div class="detail-row"><span class="detail-label">Descripción</span><span>${b.descripcion}</span></div>` : ''}
      <h3 style="font-size:var(--font-size-small);margin:var(--space-md) 0 var(--space-sm)">Especialidades</h3>
      <p style="font-size:var(--font-size-small);color:var(--color-text-muted)">${especialidades}</p>`;

    const btnTurno = document.getElementById('btn-registrar-turno');
    if (estaActiva(b)) {
      btnTurno.href = `/pacientes?brigada_id=${b.id}&nuevo_turno=1`;
      btnTurno.style.display = 'inline-flex';
    } else {
      btnTurno.style.display = 'none';
    }

    document.getElementById('btn-iniciar-campana').style.display = b.estado === 'programada' ? 'inline-flex' : 'none';
    document.getElementById('btn-finalizar-campana').style.display = b.estado === 'en_curso' ? 'inline-flex' : 'none';
  }

  async function cambiarEstadoCampana(nuevoEstado) {
    const resultado = await Api.put(`/brigadas/${brigadaId}`, { estado: nuevoEstado });
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast(nuevoEstado === 'en_curso' ? 'Campaña iniciada.' : 'Campaña finalizada.');
    cargarCampana();
  }

  document.getElementById('btn-iniciar-campana').addEventListener('click', () => cambiarEstadoCampana('en_curso'));
  document.getElementById('btn-finalizar-campana').addEventListener('click', () => cambiarEstadoCampana('finalizada'));

  // ---- Editar campaña ----
  let editarEspecialidadesCatalogo = [];
  const editarSeleccionadas = new Map(); // id especialidad -> cupos

  async function cargarEspecialidadesEdicion() {
    const resultado = await Api.get('/especialidades');
    const contenedor = document.getElementById('editar-especialidades-chips');
    if (!resultado.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resultado.message}</p>`;
      return;
    }
    editarEspecialidadesCatalogo = resultado.data.filter(e => e.activa);
    editarSeleccionadas.clear();
    (brigadaActual.especialidades || []).forEach(e => editarSeleccionadas.set(e.id, e.cupos));

    contenedor.innerHTML = editarEspecialidadesCatalogo.map(e => `
      <span class="chip ${especialidadChipClass(e.nombre)} chip-selectable ${editarSeleccionadas.has(e.id) ? 'is-selected' : ''}" data-especialidad-id="${e.id}">${e.nombre}</span>
    `).join('');

    contenedor.querySelectorAll('.chip-selectable').forEach(chip => {
      chip.addEventListener('click', () => {
        chip.classList.toggle('is-selected');
        const id = parseInt(chip.dataset.especialidadId, 10);
        if (chip.classList.contains('is-selected')) editarSeleccionadas.set(id, editarSeleccionadas.get(id) || 10);
        else editarSeleccionadas.delete(id);
        pintarCuposEdicion();
      });
    });
    pintarCuposEdicion();
  }

  function pintarCuposEdicion() {
    const contenedor = document.getElementById('editar-especialidades-cupos');
    if (!editarSeleccionadas.size) { contenedor.innerHTML = ''; return; }
    contenedor.innerHTML = Array.from(editarSeleccionadas.keys()).map(id => {
      const especialidad = editarEspecialidadesCatalogo.find(e => e.id === id);
      return `
        <div style="display:flex;align-items:center;gap:8px">
          <span style="flex:1;font-size:var(--font-size-small)">${especialidad?.nombre ?? ''}</span>
          <input type="number" min="1" class="form-control" style="max-width:110px" placeholder="Cupos"
                 data-cupos-para="${id}" value="${editarSeleccionadas.get(id)}">
        </div>`;
    }).join('');
    contenedor.querySelectorAll('[data-cupos-para]').forEach(input => {
      input.addEventListener('input', () => {
        editarSeleccionadas.set(parseInt(input.dataset.cuposPara, 10), parseInt(input.value, 10) || 1);
      });
    });
  }

  document.getElementById('btn-editar-campana').addEventListener('click', async () => {
    if (!brigadaActual) return;
    document.getElementById('editar-nombre').value = brigadaActual.nombre;
    document.getElementById('editar-descripcion').value = brigadaActual.descripcion || '';
    document.getElementById('editar-fecha').value = (brigadaActual.fecha || '').slice(0, 10);
    document.getElementById('editar-ubicacion').value = brigadaActual.ubicacion;
    document.querySelectorAll('#form-editar-campana .form-error-msg').forEach(el => { el.hidden = true; });
    await cargarEspecialidadesEdicion();
    openModal('modal-editar-campana');
  });

  document.getElementById('form-editar-campana').addEventListener('submit', async function (e) {
    e.preventDefault();
    document.querySelectorAll('#form-editar-campana .form-error-msg').forEach(el => { el.hidden = true; });
    if (!editarSeleccionadas.size) {
      document.getElementById('error-editar-especialidades').hidden = false;
      return;
    }

    const btn = document.getElementById('btn-guardar-campana');
    btn.disabled = true; btn.textContent = 'Guardando...';

    const resultado = await Api.put(`/brigadas/${brigadaId}`, {
      nombre: document.getElementById('editar-nombre').value.trim(),
      descripcion: document.getElementById('editar-descripcion').value.trim() || null,
      fecha: document.getElementById('editar-fecha').value,
      ubicacion: document.getElementById('editar-ubicacion').value.trim(),
      especialidades: Array.from(editarSeleccionadas.entries()).map(([id, cupos]) => ({ id, cupos })),
    });

    btn.disabled = false; btn.textContent = 'Guardar cambios';

    if (!resultado.ok) {
      if (resultado.errors) {
        Object.entries(resultado.errors).forEach(([campo, mensajes]) => {
          const el = document.getElementById(`error-editar-${campo}`);
          if (el) { el.hidden = false; el.textContent = mensajes[0]; }
        });
      }
      showToast(resultado.message, 'error');
      return;
    }

    closeModal('modal-editar-campana');
    showToast('Campaña actualizada correctamente');
    cargarCampana();
  });

  // ---- Eliminar campaña ----
  document.getElementById('btn-eliminar-campana').addEventListener('click', () => {
    if (!brigadaActual) return;
    document.getElementById('eliminar-campana-nombre').textContent = brigadaActual.nombre;
    openModal('modal-eliminar-campana');
  });

  document.getElementById('btn-confirmar-eliminar-campana').addEventListener('click', async () => {
    const resultado = await Api.delete(`/brigadas/${brigadaId}`);
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Campaña eliminada correctamente');
    setTimeout(() => window.location.href = '{{ route("brigadas.index") }}', 600);
  });

  // ---- Médicos asignados ----
  async function cargarMedicosAsignados() {
    const contenedor = document.getElementById('medicos-asignados');
    const resultado = await Api.get(`/brigadas/${brigadaId}/medicos`);
    if (!resultado.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resultado.message}</p>`;
      return;
    }
    if (!resultado.data.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Todavía no hay médicos asignados a esta campaña.</p>';
      return;
    }
    contenedor.innerHTML = `<div style="display:flex;flex-wrap:wrap;gap:8px">${resultado.data.map(m => `
      <span class="chip">${m.nombres}${m.especialidad ? ` · ${m.especialidad.nombre}` : ''}</span>
    `).join('')}</div>`;
  }

  // /medicos pagina de a 15 fijo (no admite per_page), así que se recorren todas las páginas.
  async function obtenerTodosLosMedicos() {
    const medicos = [];
    let pagina = 1;
    let ultimaPagina = 1;
    do {
      const resultado = await Api.get(`/medicos?page=${pagina}`);
      if (!resultado.ok) return { ok: false, message: resultado.message };
      medicos.push(...resultado.data);
      ultimaPagina = resultado.meta?.last_page ?? 1;
      pagina++;
    } while (pagina <= ultimaPagina);
    return { ok: true, data: medicos };
  }

  document.getElementById('btn-asignar-medicos').addEventListener('click', async () => {
    const contenedor = document.getElementById('medicos-checks');
    contenedor.innerHTML = '<p class="page-subtitle">Cargando...</p>';
    openModal('modal-medicos-campana');

    const [catalogoRes, asignadosRes] = await Promise.all([
      obtenerTodosLosMedicos(),
      Api.get(`/brigadas/${brigadaId}/medicos`),
    ]);
    if (!catalogoRes.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${catalogoRes.message}</p>`;
      return;
    }
    const asignadosIds = new Set((asignadosRes.data || []).map(m => m.id));
    if (!catalogoRes.data.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Todavía no hay médicos registrados. Créalos desde el módulo Médicos.</p>';
      return;
    }
    contenedor.innerHTML = catalogoRes.data.map(m => `
      <label style="display:flex;align-items:center;gap:8px">
        <input type="checkbox" value="${m.id}" ${asignadosIds.has(m.id) ? 'checked' : ''}>
        <span>${m.nombres}${m.especialidad ? ` · ${m.especialidad.nombre}` : ''}</span>
      </label>`).join('');
  });

  document.getElementById('btn-guardar-medicos').addEventListener('click', async () => {
    const ids = Array.from(document.querySelectorAll('#medicos-checks input[type=checkbox]:checked')).map(el => parseInt(el.value, 10));
    const btn = document.getElementById('btn-guardar-medicos');
    btn.disabled = true; btn.textContent = 'Guardando...';
    const resultado = await Api.post(`/brigadas/${brigadaId}/medicos`, { medicos: ids });
    btn.disabled = false; btn.textContent = 'Guardar';

    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    closeModal('modal-medicos-campana');
    showToast('Médicos asignados correctamente');
    cargarMedicosAsignados();
  });

  // /turnos pagina de a 20 fijo (no admite per_page), así que se recorren todas
  // las páginas para no mostrar solo una parte de los pacientes de la campaña.
  async function pintarPacientes() {
    const cuerpo = document.getElementById('tabla-pacientes-campana-body');
    cuerpo.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';

    const turnos = [];
    let pagina = 1;
    let ultimaPagina = 1;
    do {
      const resultado = await Api.get(`/turnos?brigada_id=${brigadaId}&page=${pagina}`);
      if (!resultado.ok) {
        cuerpo.innerHTML = `<tr><td colspan="6">${resultado.message}</td></tr>`;
        return;
      }
      turnos.push(...resultado.data);
      ultimaPagina = resultado.meta?.last_page ?? 1;
      pagina++;
    } while (pagina <= ultimaPagina);

    if (!turnos.length) {
      cuerpo.innerHTML = '<tr><td colspan="6">Todavía no hay pacientes registrados en esta campaña.</td></tr>';
      return;
    }

    // Orden natural de la cola: por hora de registro (ya viene así desde la API).
    const enCurso = ['pendiente', 'en_espera'];

    cuerpo.innerHTML = turnos.map(t => `
      <tr>
        <td data-label="Paciente">
          <a href="/pacientes?paciente_id=${t.paciente?.id}" style="color:var(--color-text-main);font-weight:600;text-decoration:none">
            ${t.paciente?.nombres ?? ''} ${t.paciente?.apellidos ?? ''}
          </a>
          <br><small style="color:var(--color-text-muted)">${t.paciente?.cedula ?? ''}</small>
        </td>
        <td data-label="Turno">${t.numero_turno}</td>
        <td data-label="Especialidad"><span class="chip ${especialidadChipClass(t.especialidad?.nombre)}">${t.especialidad?.nombre ?? ''}</span></td>
        <td data-label="Estado"><span class="chip ${estadoTurnoChipClass(t.estado)}">${estadoTurnoLabel(t.estado)}</span></td>
        <td data-label="Hora">${formatearHora(t.hora_registro)}</td>
        <td data-label="Acciones">
          ${enCurso.includes(t.estado) ? `
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <button type="button" class="btn btn-outline btn-sm" data-marcar-turno="${t.id}" data-nuevo-estado="atendido">Atendido</button>
              <button type="button" class="btn btn-outline btn-sm" data-marcar-turno="${t.id}" data-nuevo-estado="no_asistio">No asistió</button>
              <button type="button" class="btn btn-outline btn-sm" data-marcar-turno="${t.id}" data-nuevo-estado="cancelado">Cancelar</button>
            </div>` : '—'}
        </td>
      </tr>`).join('');

    cuerpo.querySelectorAll('[data-marcar-turno]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const nuevoEstado = btn.dataset.nuevoEstado;
        const resultado = await Api.put(`/turnos/${btn.dataset.marcarTurno}`, { estado: nuevoEstado });
        if (!resultado.ok) {
          showToast(resultado.message, 'error');
          return;
        }
        showToast(`Turno marcado como "${estadoTurnoLabel(nuevoEstado)}".`);
        pintarPacientes();
      });
    });
  }

  cargarCampana();
</script>
@endsection
