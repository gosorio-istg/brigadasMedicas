@extends('layouts.app')

@section('titulo', 'Pacientes')

@section('content')
  <header class="page-header flex-between">
    <div>
      <h1>Pacientes</h1>
      <p class="page-subtitle">Cola de turnos y seguimiento de pacientes atendidos</p>
    </div>
  </header>

  <div class="grid-2" style="margin-bottom:var(--space-md)">
    <div class="search-input-wrap">
      <span class="material-symbols-rounded">search</span>
      <input type="search" class="form-control" placeholder="Buscar por cédula o nombre en esta lista..." aria-label="Buscar pacientes" id="search-pacientes">
    </div>
    <select class="form-control" id="filtro-brigada" aria-label="Filtrar por brigada">
      <option value="">Todas las brigadas</option>
    </select>
  </div>

  <nav class="filter-bar" aria-label="Filtrar por estado">
    <button class="filter-chip is-active" data-estado="">Todos</button>
    <button class="filter-chip" data-estado="pendiente">Pendiente</button>
    <button class="filter-chip" data-estado="en_espera">En espera</button>
    <button class="filter-chip" data-estado="atendido">Atendido</button>
    <button class="filter-chip" data-estado="cancelado">Cancelado</button>
  </nav>

  <div class="table-responsive">
    <table class="data-table" id="tabla-turnos">
      <thead>
        <tr>
          <th>Paciente</th>
          <th>Turno</th>
          <th>Especialidad</th>
          <th>Estado</th>
          <th>Hora</th>
        </tr>
      </thead>
      <tbody id="tabla-turnos-body">
        <tr><td colspan="5">Cargando...</td></tr>
      </tbody>
    </table>
  </div>

  <div class="pagination" id="paginacion">
    <span id="paginacion-texto"></span>
    <div class="pagination-btns">
      <button class="btn btn-outline btn-sm" id="btn-anterior" disabled>Anterior</button>
      <button class="btn btn-outline btn-sm" id="btn-siguiente" disabled>Siguiente</button>
    </div>
  </div>

  <a href="#" class="bottom-nav-fab" aria-label="Registrar turno" data-modal-open="modal-nuevo-turno">
    <span class="material-symbols-rounded">add</span>
  </a>
  <a href="#" class="fab" aria-label="Registrar turno" style="display:none" id="fab-desktop" data-modal-open="modal-nuevo-turno">
    <span class="material-symbols-rounded">add</span>
  </a>
@endsection

@section('modals')
  <div class="modal-overlay" id="modal-paciente">
    <div class="modal" role="dialog" aria-labelledby="paciente-title">
      <h2 class="modal-title" id="paciente-title">Ficha del paciente</h2>
      <div class="modal-body" id="modal-paciente-body">Cargando...</div>
      <div class="modal-actions">
        <button class="btn btn-primary" data-modal-close>Cerrar</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="modal-nuevo-turno">
    <div class="modal" role="dialog" style="max-width:520px">
      <h2 class="modal-title">Registrar turno</h2>
      <form id="form-turno" novalidate>
        <div class="form-group">
          <label class="form-label" for="turno-brigada">Brigada</label>
          <select id="turno-brigada" class="form-control" required></select>
        </div>
        <div class="form-group">
          <label class="form-label" for="turno-especialidad">Especialidad</label>
          <select id="turno-especialidad" class="form-control" required>
            <option value="">Selecciona primero una brigada</option>
          </select>
        </div>

        <div class="filter-bar" style="margin-bottom:var(--space-sm)">
          <button type="button" class="filter-chip is-active" data-modo-paciente="existente">Paciente existente</button>
          <button type="button" class="filter-chip" data-modo-paciente="nuevo">Registro asistido (nuevo)</button>
        </div>

        <div id="bloque-paciente-existente">
          <div class="form-group">
            <label class="form-label" for="buscar-paciente">Cédula o nombre</label>
            <div style="display:flex;gap:8px">
              <input type="text" id="buscar-paciente" class="form-control" placeholder="Ej. 0912345678">
              <button type="button" class="btn btn-outline btn-sm" id="btn-buscar-paciente">Buscar</button>
            </div>
          </div>
          <div id="resultados-paciente" style="display:flex;flex-direction:column;gap:6px;margin-bottom:var(--space-sm)"></div>
          <p class="page-subtitle" id="paciente-seleccionado-texto"></p>
        </div>

        <div id="bloque-paciente-nuevo" style="display:none">
          <div class="form-group">
            <label class="form-label" for="np-cedula">Cédula</label>
            <input type="text" id="np-cedula" class="form-control" maxlength="10">
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="np-nombres">Nombres</label>
              <input type="text" id="np-nombres" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label" for="np-apellidos">Apellidos</label>
              <input type="text" id="np-apellidos" class="form-control">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="np-fecha-nacimiento">Fecha de nacimiento</label>
              <input type="date" id="np-fecha-nacimiento" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label" for="np-sexo">Sexo</label>
              <select id="np-sexo" class="form-control">
                <option value="femenino">Femenino</option>
                <option value="masculino">Masculino</option>
                <option value="otro">Otro</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="np-telefono">Teléfono (opcional)</label>
            <input type="tel" id="np-telefono" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label" for="np-sector">Sector (opcional)</label>
            <input type="text" id="np-sector" class="form-control">
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-registrar-turno">Registrar turno</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  if (window.matchMedia('(min-width: 1024px)').matches) {
    document.getElementById('fab-desktop').style.display = 'flex';
  }

  let brigadasCache = [];
  let turnosCache = [];
  let paginaActual = 1;
  let estadoFiltro = '';
  let pacienteSeleccionadoId = null;

  async function init() {
    const brigadasRes = await Api.get('/brigadas?per_page=100');
    if (brigadasRes.ok) {
      brigadasCache = brigadasRes.data;
      const selectFiltro = document.getElementById('filtro-brigada');
      const selectTurno = document.getElementById('turno-brigada');
      const opciones = brigadasCache.map(b => `<option value="${b.id}">${b.nombre}</option>`).join('');
      selectFiltro.insertAdjacentHTML('beforeend', opciones);
      selectTurno.innerHTML = '<option value="">Selecciona una brigada</option>' + opciones;
    }
    cargarTurnos();

    // Llega desde el buscador global (topbar) con ?paciente_id=... -> abre su ficha directo.
    const idDesdeUrl = new URLSearchParams(window.location.search).get('paciente_id');
    if (idDesdeUrl) mostrarFichaPaciente(parseInt(idDesdeUrl, 10));
  }

  async function cargarTurnos(pagina = 1) {
    paginaActual = pagina;
    const brigadaId = document.getElementById('filtro-brigada').value;
    let ruta = `/turnos?page=${pagina}`;
    if (brigadaId) ruta += `&brigada_id=${brigadaId}`;
    if (estadoFiltro) ruta += `&estado=${estadoFiltro}`;

    const cuerpo = document.getElementById('tabla-turnos-body');
    cuerpo.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';

    const resultado = await Api.get(ruta);
    if (!resultado.ok) {
      cuerpo.innerHTML = `<tr><td colspan="5">${resultado.message}</td></tr>`;
      return;
    }

    turnosCache = resultado.data;
    pintarTurnos();
    pintarPaginacion(resultado.meta);
  }

  function pintarTurnos() {
    const cuerpo = document.getElementById('tabla-turnos-body');
    if (!turnosCache.length) {
      cuerpo.innerHTML = '<tr><td colspan="5">No hay turnos con estos filtros.</td></tr>';
      return;
    }

    cuerpo.innerHTML = turnosCache.map(t => `
      <tr data-paciente-id="${t.paciente?.id ?? ''}" style="cursor:pointer">
        <td data-label="Paciente"><strong>${t.paciente?.nombres ?? ''} ${t.paciente?.apellidos ?? ''}</strong><br><small style="color:var(--color-text-muted)">${t.paciente?.cedula ?? ''}</small></td>
        <td data-label="Turno">${t.numero_turno}</td>
        <td data-label="Especialidad"><span class="chip ${especialidadChipClass(t.especialidad?.nombre)}">${t.especialidad?.nombre ?? ''}</span></td>
        <td data-label="Estado"><span class="chip ${estadoTurnoChipClass(t.estado)}">${estadoTurnoLabel(t.estado)}</span></td>
        <td data-label="Hora">${formatearHora(t.hora_registro)}</td>
      </tr>`).join('');

    cuerpo.querySelectorAll('tr[data-paciente-id]').forEach(fila => {
      fila.addEventListener('click', () => {
        const id = fila.dataset.pacienteId;
        if (id) mostrarFichaPaciente(parseInt(id, 10));
      });
    });
  }

  function pintarPaginacion(meta) {
    if (!meta) return;
    document.getElementById('paginacion-texto').textContent =
      `Mostrando ${meta.from ?? 0}–${meta.to ?? 0} de ${meta.total} turnos`;
    document.getElementById('btn-anterior').disabled = meta.current_page <= 1;
    document.getElementById('btn-siguiente').disabled = meta.current_page >= meta.last_page;
  }

  document.getElementById('btn-anterior').addEventListener('click', () => cargarTurnos(paginaActual - 1));
  document.getElementById('btn-siguiente').addEventListener('click', () => cargarTurnos(paginaActual + 1));
  document.getElementById('filtro-brigada').addEventListener('change', () => cargarTurnos(1));

  document.querySelectorAll('.filter-bar[aria-label="Filtrar por estado"] .filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.filter-bar[aria-label="Filtrar por estado"] .filter-chip').forEach(c => c.classList.remove('is-active'));
      chip.classList.add('is-active');
      estadoFiltro = chip.dataset.estado;
      cargarTurnos(1);
    });
  });

  initTableSearch('#tabla-turnos', '#search-pacientes');

  async function mostrarFichaPaciente(id) {
    const cuerpo = document.getElementById('modal-paciente-body');
    cuerpo.innerHTML = 'Cargando...';
    openModal('modal-paciente');

    const resultado = await Api.get(`/pacientes/${id}`);
    if (!resultado.ok) {
      cuerpo.innerHTML = `<p>${resultado.message}</p>`;
      return;
    }

    const p = resultado.data;
    const historial = (p.turnos || []).map(t => `
      <div class="detail-row">
        <span>${formatearFecha(t.created_at)} — ${t.especialidad?.nombre ?? ''} (${t.brigada?.nombre ?? ''})</span>
        <span class="chip ${estadoTurnoChipClass(t.estado)}">${estadoTurnoLabel(t.estado)}</span>
      </div>`).join('') || '<p class="page-subtitle">Sin turnos registrados todavía.</p>';

    cuerpo.innerHTML = `
      <div class="detail-row"><span class="detail-label">Nombre</span><span>${p.nombres} ${p.apellidos}</span></div>
      <div class="detail-row"><span class="detail-label">Cédula</span><span>${p.cedula}</span></div>
      <div class="detail-row"><span class="detail-label">Edad</span><span>${p.edad ?? '—'} años</span></div>
      <div class="detail-row"><span class="detail-label">Sexo</span><span>${p.sexo}</span></div>
      <div class="detail-row"><span class="detail-label">Teléfono</span><span>${p.telefono ?? '—'}</span></div>
      <div class="detail-row"><span class="detail-label">Sector</span><span>${p.sector ?? '—'}</span></div>
      <h3 style="font-size:var(--font-size-small);margin:var(--space-md) 0 var(--space-sm)">Historial de atenciones</h3>
      ${historial}`;
  }

  // --- Modal: registrar turno ---
  document.getElementById('turno-brigada').addEventListener('change', function () {
    const brigada = brigadasCache.find(b => b.id === parseInt(this.value, 10));
    const select = document.getElementById('turno-especialidad');
    if (!brigada) {
      select.innerHTML = '<option value="">Selecciona primero una brigada</option>';
      return;
    }
    select.innerHTML = (brigada.especialidades || [])
      .map(e => `<option value="${e.id}">${e.nombre} (${e.cupos} cupos)</option>`).join('')
      || '<option value="">Esta brigada no tiene especialidades</option>';
  });

  document.querySelectorAll('[data-modo-paciente]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-modo-paciente]').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      const esExistente = btn.dataset.modoPaciente === 'existente';
      document.getElementById('bloque-paciente-existente').style.display = esExistente ? 'block' : 'none';
      document.getElementById('bloque-paciente-nuevo').style.display = esExistente ? 'none' : 'block';
      pacienteSeleccionadoId = null;
      document.getElementById('paciente-seleccionado-texto').textContent = '';
    });
  });

  document.getElementById('btn-buscar-paciente').addEventListener('click', async () => {
    const termino = document.getElementById('buscar-paciente').value.trim();
    const contenedor = document.getElementById('resultados-paciente');
    if (!termino) { contenedor.innerHTML = ''; return; }

    contenedor.innerHTML = 'Buscando...';
    const resultado = await Api.get(`/pacientes?buscar=${encodeURIComponent(termino)}`);
    if (!resultado.ok || !resultado.data.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Sin resultados. Usa "Registro asistido" para crearlo.</p>';
      return;
    }

    contenedor.innerHTML = resultado.data.map(p => `
      <button type="button" class="btn btn-outline btn-sm" data-elegir-paciente="${p.id}" style="justify-content:flex-start">
        ${p.nombres} ${p.apellidos} — ${p.cedula}
      </button>`).join('');

    contenedor.querySelectorAll('[data-elegir-paciente]').forEach(b => {
      b.addEventListener('click', () => {
        pacienteSeleccionadoId = parseInt(b.dataset.elegirPaciente, 10);
        document.getElementById('paciente-seleccionado-texto').textContent = `Seleccionado: ${b.textContent.trim()}`;
      });
    });
  });

  document.getElementById('form-turno').addEventListener('submit', async function (e) {
    e.preventDefault();

    const brigadaId = document.getElementById('turno-brigada').value;
    const especialidadId = document.getElementById('turno-especialidad').value;
    if (!brigadaId || !especialidadId) {
      showToast('Selecciona brigada y especialidad.', 'error');
      return;
    }

    const modoExistente = document.querySelector('[data-modo-paciente].is-active').dataset.modoPaciente === 'existente';
    const cuerpo = { brigada_id: parseInt(brigadaId, 10), especialidad_id: parseInt(especialidadId, 10) };

    if (modoExistente) {
      if (!pacienteSeleccionadoId) {
        showToast('Busca y selecciona un paciente de la lista.', 'error');
        return;
      }
      cuerpo.paciente_id = pacienteSeleccionadoId;
    } else {
      cuerpo.paciente = {
        cedula: document.getElementById('np-cedula').value.trim(),
        nombres: document.getElementById('np-nombres').value.trim(),
        apellidos: document.getElementById('np-apellidos').value.trim(),
        fecha_nacimiento: document.getElementById('np-fecha-nacimiento').value,
        sexo: document.getElementById('np-sexo').value,
        telefono: document.getElementById('np-telefono').value.trim() || null,
        sector: document.getElementById('np-sector').value.trim() || null,
      };
    }

    const btn = document.getElementById('btn-registrar-turno');
    btn.disabled = true;
    btn.textContent = 'Registrando...';

    const resultado = await Api.post('/turnos', cuerpo);

    btn.disabled = false;
    btn.textContent = 'Registrar turno';

    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }

    closeModal('modal-nuevo-turno');
    this.reset();
    pacienteSeleccionadoId = null;
    document.getElementById('paciente-seleccionado-texto').textContent = '';
    document.getElementById('resultados-paciente').innerHTML = '';

    showToast(resultado.extra?.advertencia
      ? `Turno ${resultado.data.numero_turno} registrado. ${resultado.extra.advertencia}`
      : `Turno ${resultado.data.numero_turno} registrado correctamente.`,
      resultado.extra?.advertencia ? 'error' : 'success');

    cargarTurnos(paginaActual);
  });

  init();
</script>
@endsection
