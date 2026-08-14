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
