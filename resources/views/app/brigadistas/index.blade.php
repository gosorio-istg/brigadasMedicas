@extends('layouts.app')

@section('titulo', 'Brigadistas')

@section('content')
  <header class="page-header flex-between">
    <div>
      <h1>Brigadistas</h1>
      <p class="page-subtitle">Equipo asignado a cada campaña y control de asistencia</p>
    </div>
  </header>

  <div class="form-group" style="max-width:420px;margin-bottom:var(--space-md)">
    <label class="form-label" for="selector-brigada">Campaña</label>
    <select id="selector-brigada" class="form-control">
      <option value="">Selecciona una campaña...</option>
    </select>
  </div>

  <div id="panel-equipo" style="display:none">
    <div class="flex-between" style="margin-bottom:var(--space-sm)">
      <h2 style="font-size:var(--font-size-body)">Equipo asignado</h2>
      <button type="button" class="btn btn-primary btn-sm" id="btn-agregar-brigadista">
        <span class="material-symbols-rounded">person_add</span> Agregar brigadista
      </button>
    </div>

    <div class="table-responsive">
      <table class="data-table" id="tabla-brigadistas">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol en el equipo</th>
            <th>Asistencia</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-brigadistas-body">
          <tr><td colspan="5">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="empty-state" id="estado-vacio">
    <span class="material-symbols-rounded">groups</span>
    <p>Selecciona una campaña para ver su equipo.</p>
  </div>
@endsection

@section('modals')
  <div class="modal-overlay" id="modal-agregar">
    <div class="modal" role="dialog" style="max-width:480px">
      <h2 class="modal-title">Agregar brigadista</h2>
      <form id="form-agregar">
        <div class="form-group">
          <label class="form-label" for="select-usuario">Usuario (rol Brigadista)</label>
          <select id="select-usuario" class="form-control" required>
            <option value="">Cargando usuarios...</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="select-rol-equipo">Rol en el equipo</label>
          <select id="select-rol-equipo" class="form-control" required>
            <option value="registro">Registro</option>
            <option value="apoyo_logistico">Apoyo logístico</option>
            <option value="atencion_medica">Atención médica</option>
            <option value="coordinacion">Coordinación</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-guardar-brigadista">Agregar</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  let brigadaSeleccionada = null;
  let usuariosBrigadistas = [];

  const ROL_EQUIPO_LABEL = {
    registro: 'Registro',
    apoyo_logistico: 'Apoyo logístico',
    atencion_medica: 'Atención médica',
    coordinacion: 'Coordinación',
  };

  function asistioChip(asistio) {
    if (asistio === true) return { clase: 'chip-atendido', texto: 'Asistió' };
    if (asistio === false) return { clase: 'chip-cancelado', texto: 'No asistió' };
    return { clase: 'chip-pendiente', texto: 'Pendiente' };
  }

  async function cargarBrigadas() {
    const resultado = await Api.get('/brigadas?per_page=100');
    const select = document.getElementById('selector-brigada');
    if (!resultado.ok) {
      select.innerHTML = `<option value="">${resultado.message}</option>`;
      return;
    }
    select.innerHTML = '<option value="">Selecciona una campaña...</option>' +
      resultado.data.map(b => `<option value="${b.id}">${b.nombre} (${formatearFecha(b.fecha)})</option>`).join('');
  }

  document.getElementById('selector-brigada').addEventListener('change', function () {
    brigadaSeleccionada = parseInt(this.value, 10) || null;
    document.getElementById('panel-equipo').style.display = brigadaSeleccionada ? 'block' : 'none';
    document.getElementById('estado-vacio').style.display = brigadaSeleccionada ? 'none' : 'flex';
    if (brigadaSeleccionada) cargarEquipo();
  });

  async function cargarEquipo() {
    const cuerpo = document.getElementById('tabla-brigadistas-body');
    cuerpo.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';

    const resultado = await Api.get(`/brigadas/${brigadaSeleccionada}/brigadistas`);
    if (!resultado.ok) {
      cuerpo.innerHTML = `<tr><td colspan="5">${resultado.message}</td></tr>`;
      return;
    }

    if (!resultado.data.length) {
      cuerpo.innerHTML = '<tr><td colspan="5">Todavía no hay brigadistas asignados a esta campaña.</td></tr>';
      return;
    }

    cuerpo.innerHTML = resultado.data.map(u => {
      const chip = asistioChip(u.asistio);
      return `
        <tr>
          <td data-label="Nombre"><strong>${u.name}</strong></td>
          <td data-label="Email">${u.email}</td>
          <td data-label="Rol">${ROL_EQUIPO_LABEL[u.rol_equipo] ?? u.rol_equipo}</td>
          <td data-label="Asistencia"><span class="chip ${chip.clase}">${chip.texto}</span></td>
          <td data-label="Acciones">
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <button type="button" class="btn btn-outline btn-sm" data-asistio="1" data-user-id="${u.id}">Marcar asistió</button>
              <button type="button" class="btn btn-outline btn-sm" data-asistio="0" data-user-id="${u.id}">Marcar falta</button>
              <button type="button" class="btn btn-outline btn-sm" data-quitar="${u.id}">Quitar</button>
            </div>
          </td>
        </tr>`;
    }).join('');

    cuerpo.querySelectorAll('[data-asistio]').forEach(btn => {
      btn.addEventListener('click', () => marcarAsistencia(btn.dataset.userId, btn.dataset.asistio === '1'));
    });
    cuerpo.querySelectorAll('[data-quitar]').forEach(btn => {
      btn.addEventListener('click', () => quitarBrigadista(btn.dataset.quitar));
    });
  }

  async function marcarAsistencia(userId, asistio) {
    const resultado = await Api.put(`/brigadas/${brigadaSeleccionada}/brigadistas/${userId}`, { asistio });
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Asistencia actualizada.');
    cargarEquipo();
  }

  async function quitarBrigadista(userId) {
    const resultado = await Api.delete(`/brigadas/${brigadaSeleccionada}/brigadistas/${userId}`);
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Brigadista removido del equipo.');
    cargarEquipo();
  }

  async function cargarUsuariosBrigadistas() {
    usuariosBrigadistas = [];
    let pagina = 1;
    let ultimaPagina = 1;
    do {
      const resultado = await Api.get(`/users?page=${pagina}`);
      if (!resultado.ok) break;
      usuariosBrigadistas.push(...resultado.data.filter(u => (u.roles || []).includes('Brigadista')));
      ultimaPagina = resultado.meta?.last_page ?? 1;
      pagina++;
    } while (pagina <= ultimaPagina);
  }

  document.getElementById('btn-agregar-brigadista').addEventListener('click', async () => {
    if (!brigadaSeleccionada) return;
    const select = document.getElementById('select-usuario');
    select.innerHTML = '<option value="">Cargando usuarios...</option>';
    openModal('modal-agregar');

    await cargarUsuariosBrigadistas();
    const asignadosResp = await Api.get(`/brigadas/${brigadaSeleccionada}/brigadistas`);
    const idsAsignados = asignadosResp.ok ? asignadosResp.data.map(u => u.id) : [];
    const disponibles = usuariosBrigadistas.filter(u => !idsAsignados.includes(u.id));

    select.innerHTML = disponibles.length
      ? disponibles.map(u => `<option value="${u.id}">${u.name} ${u.apellido ?? ''} — ${u.email}</option>`).join('')
      : '<option value="">No hay brigadistas disponibles para asignar</option>';
  });

  document.getElementById('form-agregar').addEventListener('submit', async function (e) {
    e.preventDefault();
    const userId = document.getElementById('select-usuario').value;
    if (!userId) {
      showToast('Selecciona un usuario.', 'error');
      return;
    }

    const btn = document.getElementById('btn-guardar-brigadista');
    btn.disabled = true;
    btn.textContent = 'Agregando...';

    const resultado = await Api.post(`/brigadas/${brigadaSeleccionada}/brigadistas`, {
      user_id: parseInt(userId, 10),
      rol_equipo: document.getElementById('select-rol-equipo').value,
    });

    btn.disabled = false;
    btn.textContent = 'Agregar';

    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }

    closeModal('modal-agregar');
    showToast('Brigadista agregado al equipo.');
    cargarEquipo();
  });

  cargarBrigadas();
</script>
@endsection
