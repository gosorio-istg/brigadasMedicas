@extends('layouts.app')

@section('titulo', 'Médicos')

@section('content')
  <header class="page-header flex-between">
    <div>
      <h1>Médicos</h1>
      <p class="page-subtitle">Personal médico registrado en el sistema</p>
    </div>
    <a href="{{ route('medicos.crear') }}" class="btn btn-primary">
      <span class="material-symbols-rounded">add</span> Nuevo médico
    </a>
  </header>

  <div class="search-input-wrap" style="margin-bottom:var(--space-md)">
    <span class="material-symbols-rounded">search</span>
    <input type="search" class="form-control" placeholder="Buscar en esta página por nombre o credencial..." aria-label="Buscar médicos" id="search-medicos">
  </div>

  <div class="table-responsive">
    <table class="data-table" id="tabla-medicos">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Credencial CMP</th>
          <th>Especialidad</th>
          <th>Teléfono</th>
          <th>Disponible</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tabla-medicos-body">
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
@endsection

@section('modals')
  <div class="modal-overlay" id="modal-eliminar">
    <div class="modal" role="dialog">
      <h2 class="modal-title">Eliminar médico</h2>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar a <strong id="eliminar-nombre"></strong>? Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
        <button type="button" class="btn btn-primary" style="background:var(--color-error)" id="btn-confirmar-eliminar">Eliminar</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  let paginaActual = 1;
  let medicoAEliminar = null;

  async function cargarMedicos(pagina = 1) {
    paginaActual = pagina;
    const cuerpo = document.getElementById('tabla-medicos-body');
    cuerpo.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';

    const resultado = await Api.get(`/medicos?page=${pagina}`);
    if (!resultado.ok) {
      cuerpo.innerHTML = `<tr><td colspan="6">${resultado.message}</td></tr>`;
      return;
    }

    if (!resultado.data.length) {
      cuerpo.innerHTML = '<tr><td colspan="6">No hay médicos registrados todavía.</td></tr>';
      return;
    }

    cuerpo.innerHTML = resultado.data.map(m => `
      <tr>
        <td data-label="Nombre"><strong>${m.nombres}</strong></td>
        <td data-label="Credencial CMP">${m.credencial_cmp}</td>
        <td data-label="Especialidad"><span class="chip ${especialidadChipClass(m.especialidad?.nombre)}">${m.especialidad?.nombre ?? '—'}</span></td>
        <td data-label="Teléfono">${m.telefono ?? '—'}</td>
        <td data-label="Disponible"><span class="chip ${m.disponible ? 'chip-atendido' : 'chip-espera'}">${m.disponible ? 'Disponible' : 'No disponible'}</span></td>
        <td data-label="Acciones">
          <div style="display:flex;gap:8px">
            <a href="/medicos/${m.id}/editar" class="btn btn-outline btn-sm">Editar</a>
            <button type="button" class="btn btn-outline btn-sm" data-eliminar-id="${m.id}" data-eliminar-nombre="${m.nombres}">Eliminar</button>
          </div>
        </td>
      </tr>`).join('');

    cuerpo.querySelectorAll('[data-eliminar-id]').forEach(btn => {
      btn.addEventListener('click', () => {
        medicoAEliminar = parseInt(btn.dataset.eliminarId, 10);
        document.getElementById('eliminar-nombre').textContent = btn.dataset.eliminarNombre;
        openModal('modal-eliminar');
      });
    });

    pintarPaginacion(resultado.meta);
  }

  function pintarPaginacion(meta) {
    if (!meta) return;
    document.getElementById('paginacion-texto').textContent =
      `Mostrando ${meta.from ?? 0}–${meta.to ?? 0} de ${meta.total} médicos`;
    document.getElementById('btn-anterior').disabled = meta.current_page <= 1;
    document.getElementById('btn-siguiente').disabled = meta.current_page >= meta.last_page;
  }

  document.getElementById('btn-anterior').addEventListener('click', () => cargarMedicos(paginaActual - 1));
  document.getElementById('btn-siguiente').addEventListener('click', () => cargarMedicos(paginaActual + 1));

  document.getElementById('btn-confirmar-eliminar').addEventListener('click', async () => {
    if (!medicoAEliminar) return;
    const resultado = await Api.delete(`/medicos/${medicoAEliminar}`);
    closeModal('modal-eliminar');
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Médico eliminado correctamente.');
    cargarMedicos(paginaActual);
  });

  initTableSearch('#tabla-medicos', '#search-medicos');

  cargarMedicos();
</script>
@endsection
