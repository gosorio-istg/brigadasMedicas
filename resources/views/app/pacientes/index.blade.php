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
    <select class="form-control" id="filtro-brigada" aria-label="Filtrar por campaña">
      <option value="">Todas las campañas</option>
    </select>
  </div>

  <nav class="filter-bar" aria-label="Filtrar por estado">
    <button class="filter-chip is-active" data-estado="">Todos</button>
    <button class="filter-chip" data-estado="pendiente">Pendiente</button>
    <button class="filter-chip" data-estado="en_espera">En espera</button>
    <button class="filter-chip" data-estado="atendido">Atendido</button>
    <button class="filter-chip" data-estado="cancelado">Cancelado</button>
    <button class="filter-chip" data-estado="no_asistio">No asistió</button>
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
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tabla-turnos-body">
        <tr><td colspan="6">Cargando...</td></tr>
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

  <div class="modal-overlay" id="modal-editar-atencion">
    <div class="modal" role="dialog" style="max-width:520px">
      <h2 class="modal-title">Editar atención</h2>
      <form id="form-editar-atencion">
        <div class="form-group">
          <label class="form-label" for="ea-diagnostico">Diagnóstico</label>
          <textarea id="ea-diagnostico" class="form-control" required></textarea>
          <span class="form-error-msg" id="error-ea-diagnostico" hidden></span>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="ea-motivo">Motivo de consulta</label>
            <select id="ea-motivo" class="form-control">
              <option value="enfermedad_comun">Enfermedad común</option>
              <option value="control">Control</option>
              <option value="chequeo_preventivo">Chequeo preventivo</option>
              <option value="urgencia">Urgencia</option>
              <option value="seguimiento">Seguimiento</option>
              <option value="otro">Otro</option>
            </select>
            <span class="form-error-msg" id="error-ea-motivo_consulta" hidden></span>
          </div>
          <div class="form-group">
            <label class="form-label" for="ea-tipo">Tipo de atención</label>
            <select id="ea-tipo" class="form-control">
              <option value="primera_vez">Primera vez</option>
              <option value="seguimiento">Seguimiento</option>
            </select>
            <span class="form-error-msg" id="error-ea-tipo_atencion" hidden></span>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" id="ea-referencia" style="width:18px;height:18px">
            ¿Requiere referencia a otro nivel de atención?
          </label>
        </div>
        <div class="form-group">
          <label class="form-label" for="ea-receta">Receta (opcional)</label>
          <textarea id="ea-receta" class="form-control"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="ea-observaciones">Observaciones (opcional)</label>
          <textarea id="ea-observaciones" class="form-control"></textarea>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-guardar-atencion">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="modal-nuevo-turno">
    <div class="modal" role="dialog" style="max-width:520px">
      <h2 class="modal-title">Registrar turno</h2>
      <form id="form-turno" novalidate>
        <div class="form-group">
          <label class="form-label" for="turno-brigada">Campaña</label>
          <select id="turno-brigada" class="form-control" required></select>
        </div>
        <div class="form-group">
          <label class="form-label" for="turno-especialidad">Especialidad</label>
          <select id="turno-especialidad" class="form-control" required>
            <option value="">Selecciona primero una campaña</option>
          </select>
        </div>

        <div class="filter-bar" style="margin-bottom:var(--space-sm)">
          <button type="button" class="filter-chip is-active" data-modo-paciente="existente">Paciente existente</button>
          <button type="button" class="filter-chip" data-modo-paciente="nuevo">Registro asistido (nuevo)</button>
        </div>

        <div id="bloque-paciente-existente">
          <div class="form-group">
            <label class="form-label" for="buscar-paciente">Cédula o nombre</label>
            <div class="search-input-wrap">
              <span class="material-symbols-rounded">search</span>
              <input type="text" id="buscar-paciente" class="form-control" placeholder="Escribe al menos 3 caracteres...">
            </div>
          </div>
          <div id="resultados-paciente" style="display:flex;flex-direction:column;gap:6px;margin-bottom:var(--space-sm)"></div>
          <p class="page-subtitle" id="paciente-seleccionado-texto"></p>
        </div>

        <div id="bloque-paciente-nuevo" style="display:none">
          <div class="form-group">
            <label class="form-label" for="np-cedula">Cédula</label>
            <input type="text" id="np-cedula" class="form-control" inputmode="numeric" maxlength="10" placeholder="10 dígitos">
            <span class="form-error-msg" id="error-np-cedula" hidden></span>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="np-nombres">Nombres</label>
              <input type="text" id="np-nombres" class="form-control">
              <span class="form-error-msg" id="error-np-nombres" hidden></span>
            </div>
            <div class="form-group">
              <label class="form-label" for="np-apellidos">Apellidos</label>
              <input type="text" id="np-apellidos" class="form-control">
              <span class="form-error-msg" id="error-np-apellidos" hidden></span>
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="np-fecha-nacimiento">Fecha de nacimiento</label>
              <input type="date" id="np-fecha-nacimiento" class="form-control">
              <span class="form-error-msg" id="error-np-fecha_nacimiento" hidden></span>
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
            <input type="tel" id="np-telefono" class="form-control" inputmode="numeric" maxlength="10">
            <span class="form-error-msg" id="error-np-telefono" hidden></span>
          </div>
          <div class="form-group">
            <label class="form-label" for="np-sector">Sector (opcional)</label>
            <input type="text" id="np-sector" class="form-control">
            <span class="form-error-msg" id="error-np-sector" hidden></span>
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
      // El filtro de arriba sirve para revisar turnos de cualquier campaña, incluidas
      // las ya finalizadas/canceladas. Pero para REGISTRAR un turno nuevo solo tiene
      // sentido elegir entre las que siguen activas (programada o en curso).
      const opcionesFiltro = brigadasCache.map(b => `<option value="${b.id}">${b.nombre}</option>`).join('');
      selectFiltro.insertAdjacentHTML('beforeend', opcionesFiltro);

      const activas = brigadasCache.filter(b => b.estado === 'programada' || b.estado === 'en_curso');
      const selectTurno = document.getElementById('turno-brigada');
      selectTurno.innerHTML = '<option value="">Selecciona una campaña</option>' +
        activas.map(b => `<option value="${b.id}">${b.nombre}</option>`).join('');
    }
    cargarTurnos();

    // Llega desde el buscador global (topbar) con ?paciente_id=... -> abre su ficha directo.
    const params = new URLSearchParams(window.location.search);
    const pacienteIdUrl = params.get('paciente_id');
    if (pacienteIdUrl) mostrarFichaPaciente(parseInt(pacienteIdUrl, 10));

    // Llega desde "Registrar turno" en el detalle de una campaña -> abre el modal
    // con esa campaña ya seleccionada, lista para elegir especialidad y paciente.
    const brigadaIdUrl = params.get('brigada_id');
    if (brigadaIdUrl && params.get('nuevo_turno')) {
      openModal('modal-nuevo-turno');
      document.getElementById('turno-brigada').value = brigadaIdUrl;
      pintarSelectEspecialidades(brigadaIdUrl);
    }
  }

  async function cargarTurnos(pagina = 1) {
    paginaActual = pagina;
    const brigadaId = document.getElementById('filtro-brigada').value;
    let ruta = `/turnos?page=${pagina}`;
    if (brigadaId) ruta += `&brigada_id=${brigadaId}`;
    if (estadoFiltro) ruta += `&estado=${estadoFiltro}`;

    const cuerpo = document.getElementById('tabla-turnos-body');
    cuerpo.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';

    const resultado = await Api.get(ruta);
    if (!resultado.ok) {
      cuerpo.innerHTML = `<tr><td colspan="6">${resultado.message}</td></tr>`;
      return;
    }

    turnosCache = resultado.data;
    pintarTurnos();
    pintarPaginacion(resultado.meta);
  }

  function pintarTurnos() {
    const cuerpo = document.getElementById('tabla-turnos-body');
    if (!turnosCache.length) {
      cuerpo.innerHTML = '<tr><td colspan="6">No hay turnos con estos filtros.</td></tr>';
      return;
    }

    const enCurso = ['pendiente', 'en_espera'];

    cuerpo.innerHTML = turnosCache.map(t => `
      <tr data-paciente-id="${t.paciente?.id ?? ''}" style="cursor:pointer">
        <td data-label="Paciente"><strong>${t.paciente?.nombres ?? ''} ${t.paciente?.apellidos ?? ''}</strong><br><small style="color:var(--color-text-muted)">${t.paciente?.cedula ?? ''}</small></td>
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

    cuerpo.querySelectorAll('tr[data-paciente-id]').forEach(fila => {
      fila.addEventListener('click', () => {
        const id = fila.dataset.pacienteId;
        if (id) mostrarFichaPaciente(parseInt(id, 10));
      });
    });

    cuerpo.querySelectorAll('[data-marcar-turno]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const nuevoEstado = btn.dataset.nuevoEstado;
        const resultado = await Api.put(`/turnos/${btn.dataset.marcarTurno}`, { estado: nuevoEstado });
        if (!resultado.ok) {
          showToast(resultado.message, 'error');
          return;
        }
        showToast(`Turno marcado como "${estadoTurnoLabel(nuevoEstado)}".`);
        cargarTurnos(paginaActual);
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

  let turnosPacienteActual = [];
  let turnoEditandoAtencion = null;

  const MOTIVO_CONSULTA_LABEL = {
    enfermedad_comun: 'Enfermedad común', control: 'Control', chequeo_preventivo: 'Chequeo preventivo',
    urgencia: 'Urgencia', seguimiento: 'Seguimiento', otro: 'Otro',
  };
  const TIPO_ATENCION_LABEL = { primera_vez: 'Primera vez', seguimiento: 'Seguimiento' };

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
    turnosPacienteActual = p.turnos || [];
    const historial = turnosPacienteActual.map(t => `
      <div style="padding:12px 0;border-bottom:1px solid var(--color-border)">
        <div class="detail-row">
          <span>${formatearFecha(t.created_at)} — ${t.especialidad?.nombre ?? ''} (${t.brigada?.nombre ?? ''})</span>
          <span class="chip ${estadoTurnoChipClass(t.estado)}">${estadoTurnoLabel(t.estado)}</span>
        </div>
        ${t.signos_vitales ? `<p class="page-subtitle">Signos: ${t.signos_vitales.presion_arterial} · ${t.signos_vitales.temperatura} °C · FC ${t.signos_vitales.frecuencia_cardiaca ?? '—'} · FR ${t.signos_vitales.frecuencia_respiratoria ?? '—'}</p>` : ''}
        ${t.atencion ? `
          <p class="page-subtitle">${MOTIVO_CONSULTA_LABEL[t.atencion.motivo_consulta] ?? ''} · ${TIPO_ATENCION_LABEL[t.atencion.tipo_atencion] ?? ''}${t.atencion.requiere_referencia ? ' · Requiere referencia' : ''}</p>
          <p><strong>Diagnóstico:</strong> ${t.atencion.diagnostico}</p>
          <p><strong>Receta:</strong> ${t.atencion.receta ?? 'Sin receta'}</p>
          ${t.atencion.observaciones ? `<p><strong>Observaciones:</strong> ${t.atencion.observaciones}</p>` : ''}
          <button type="button" class="btn btn-outline btn-sm" data-editar-atencion="${t.id}">Editar atención</button>
        ` : ''}
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

    cuerpo.querySelectorAll('[data-editar-atencion]').forEach(btn => {
      btn.addEventListener('click', () => abrirEditarAtencion(parseInt(btn.dataset.editarAtencion, 10)));
    });
  }

  function abrirEditarAtencion(turnoId) {
    const turno = turnosPacienteActual.find(t => t.id === turnoId);
    if (!turno?.atencion) return;

    turnoEditandoAtencion = turnoId;
    document.getElementById('ea-diagnostico').value = turno.atencion.diagnostico ?? '';
    document.getElementById('ea-motivo').value = turno.atencion.motivo_consulta ?? 'enfermedad_comun';
    document.getElementById('ea-tipo').value = turno.atencion.tipo_atencion ?? 'primera_vez';
    document.getElementById('ea-referencia').checked = !!turno.atencion.requiere_referencia;
    document.getElementById('ea-receta').value = turno.atencion.receta ?? '';
    document.getElementById('ea-observaciones').value = turno.atencion.observaciones ?? '';
    document.querySelectorAll('#form-editar-atencion .form-error-msg').forEach(el => { el.hidden = true; });
    openModal('modal-editar-atencion');
  }

  document.getElementById('form-editar-atencion').addEventListener('submit', async function (e) {
    e.preventDefault();
    if (!turnoEditandoAtencion) return;

    const btn = document.getElementById('btn-guardar-atencion');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const resultado = await Api.put(`/turnos/${turnoEditandoAtencion}/atencion`, {
      diagnostico: document.getElementById('ea-diagnostico').value.trim(),
      motivo_consulta: document.getElementById('ea-motivo').value,
      tipo_atencion: document.getElementById('ea-tipo').value,
      requiere_referencia: document.getElementById('ea-referencia').checked,
      receta: document.getElementById('ea-receta').value.trim() || null,
      observaciones: document.getElementById('ea-observaciones').value.trim() || null,
    });

    btn.disabled = false;
    btn.textContent = 'Guardar';

    if (!resultado.ok) {
      if (resultado.errors) {
        Object.entries(resultado.errors).forEach(([campo, mensajes]) => {
          const el = document.getElementById(`error-ea-${campo}`);
          if (el) { el.hidden = false; el.textContent = mensajes[0]; }
        });
      }
      showToast(resultado.message, 'error');
      return;
    }

    closeModal('modal-editar-atencion');
    showToast('Atención actualizada correctamente.');

    // Recarga la ficha para reflejar el cambio (el paciente sigue siendo el mismo).
    const pacienteId = turnosPacienteActual.find(t => t.id === turnoEditandoAtencion)?.paciente?.id
      ?? turnosPacienteActual[0]?.paciente?.id;
    turnoEditandoAtencion = null;
    if (pacienteId) mostrarFichaPaciente(pacienteId);
    cargarTurnos(paginaActual);
  });

  // --- Modal: registrar turno ---
  // Los cupos "usados" cambian a cada rato (cada turno nuevo los mueve), así que no se
  // puede confiar en brigadasCache (se cargó una sola vez al abrir la página): cada vez
  // que se elige una brigada se piden los cupos reales y actualizados a la API.
  async function pintarSelectEspecialidades(brigadaId) {
    const select = document.getElementById('turno-especialidad');
    if (!brigadaId) {
      select.innerHTML = '<option value="">Selecciona primero una campaña</option>';
      return;
    }

    select.innerHTML = '<option value="">Cargando especialidades...</option>';
    const resultado = await Api.get(`/brigadas/${brigadaId}`);
    if (!resultado.ok) {
      select.innerHTML = `<option value="">${resultado.message}</option>`;
      return;
    }

    const especialidades = resultado.data.especialidades || [];
    select.innerHTML = especialidades.map(e => {
      const ocupados = e.cupos_ocupados ?? 0;
      const disponibles = e.cupos - ocupados;
      const etiqueta = disponibles > 0
        ? `${e.nombre} (${disponibles} de ${e.cupos} cupos disponibles)`
        : `${e.nombre} (cupo lleno: ${ocupados}/${e.cupos})`;
      return `<option value="${e.id}">${etiqueta}</option>`;
    }).join('') || '<option value="">Esta campaña no tiene especialidades</option>';
  }

  document.getElementById('turno-brigada').addEventListener('change', function () {
    pintarSelectEspecialidades(this.value);
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
      document.getElementById('buscar-paciente').value = '';
      document.getElementById('resultados-paciente').innerHTML = '';
    });
  });

  // Restricciones al escribir, para no depender solo del error del servidor:
  // cédula y teléfono solo dígitos; nombres/apellidos solo letras (igual que valida el backend).
  document.getElementById('np-cedula').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
  });
  document.getElementById('np-telefono').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
  });
  ['np-nombres', 'np-apellidos'].forEach(id => {
    document.getElementById(id).addEventListener('input', function () {
      this.value = this.value.replace(/[^\p{L}\s.'-]/gu, '');
    });
  });

  // Búsqueda dinámica: apenas se escriben 3+ caracteres (con una pequeña pausa para no
  // disparar una petición por cada tecla), se buscan coincidencias automáticamente.
  let temporizadorBusquedaPaciente = null;
  let idBusquedaPaciente = 0;

  document.getElementById('buscar-paciente').addEventListener('input', function () {
    const termino = this.value.trim();
    const contenedor = document.getElementById('resultados-paciente');

    clearTimeout(temporizadorBusquedaPaciente);

    if (termino.length < 3) {
      contenedor.innerHTML = '';
      return;
    }

    contenedor.innerHTML = '<p class="page-subtitle">Buscando...</p>';
    temporizadorBusquedaPaciente = setTimeout(() => buscarPacienteExistente(termino), 300);
  });

  async function buscarPacienteExistente(termino) {
    const miId = ++idBusquedaPaciente;
    const contenedor = document.getElementById('resultados-paciente');

    const resultado = await Api.get(`/pacientes?buscar=${encodeURIComponent(termino)}`);
    if (miId !== idBusquedaPaciente) return; // llegó una búsqueda más nueva mientras esperábamos

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
        document.getElementById('resultados-paciente').innerHTML = '';
        document.getElementById('buscar-paciente').value = b.textContent.trim();
      });
    });
  }

  document.getElementById('form-turno').addEventListener('submit', async function (e) {
    e.preventDefault();

    const brigadaId = document.getElementById('turno-brigada').value;
    const especialidadId = document.getElementById('turno-especialidad').value;
    if (!brigadaId || !especialidadId) {
      showToast('Selecciona campaña y especialidad.', 'error');
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

    document.querySelectorAll('#bloque-paciente-nuevo .form-error-msg').forEach(el => { el.hidden = true; el.textContent = ''; });

    const btn = document.getElementById('btn-registrar-turno');
    btn.disabled = true;
    btn.textContent = 'Registrando...';

    const resultado = await Api.post('/turnos', cuerpo);

    btn.disabled = false;
    btn.textContent = 'Registrar turno';

    if (!resultado.ok) {
      if (resultado.errors) {
        Object.entries(resultado.errors).forEach(([campo, mensajes]) => {
          // "paciente.cedula" -> #error-np-cedula
          const el = document.getElementById(`error-np-${campo.replace('paciente.', '')}`);
          if (el) { el.hidden = false; el.textContent = mensajes[0]; }
        });
      }
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
