@extends('layouts.app')

@section('titulo', 'Comunidades')

@section('content')
  <header class="page-header flex-between">
    <div>
      <h1>Comunidades</h1>
      <p class="page-subtitle">Catálogo de sectores y comunidades atendidas</p>
    </div>
    <button type="button" class="btn btn-primary" id="btn-nueva-comunidad">
      <span class="material-symbols-rounded">add</span> Nueva comunidad
    </button>
  </header>

  <div class="search-input-wrap" style="margin-bottom:var(--space-md)">
    <span class="material-symbols-rounded">search</span>
    <input type="search" class="form-control" placeholder="Buscar en esta página por nombre o sector..." aria-label="Buscar comunidades" id="search-comunidades">
  </div>

  <div class="table-responsive">
    <table class="data-table" id="tabla-comunidades">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Sector</th>
          <th>Referencia de ubicación</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tabla-comunidades-body">
        <tr><td colspan="4">Cargando...</td></tr>
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
  <div class="modal-overlay" id="modal-comunidad">
    <div class="modal" role="dialog" style="max-width:480px">
      <h2 class="modal-title" id="modal-comunidad-title">Nueva comunidad</h2>
      <form id="form-comunidad">
        <div class="form-group">
          <label class="form-label" for="c-nombre">Nombre</label>
          <input type="text" id="c-nombre" class="form-control" placeholder="Ej. Bastión Popular Bloque 5" required>
          <span class="form-error-msg" id="error-nombre" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label" for="c-sector">Sector</label>
          <input type="text" id="c-sector" class="form-control" placeholder="Ej. Suburbio Oeste" required>
          <span class="form-error-msg" id="error-sector" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label" for="c-referencia">Referencia de ubicación (opcional)</label>
          <input type="text" id="c-referencia" class="form-control" placeholder="Ej. Junto a la iglesia central">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-guardar-comunidad">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="modal-eliminar">
    <div class="modal" role="dialog">
      <h2 class="modal-title">Eliminar comunidad</h2>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar <strong id="eliminar-nombre"></strong>? Esta acción no se puede deshacer.</p>
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
  let comunidadEditando = null;
  let comunidadAEliminar = null;

  async function cargarComunidades(pagina = 1) {
    paginaActual = pagina;
    const cuerpo = document.getElementById('tabla-comunidades-body');
    cuerpo.innerHTML = '<tr><td colspan="4">Cargando...</td></tr>';

    const resultado = await Api.get(`/comunidades?page=${pagina}`);
    if (!resultado.ok) {
      cuerpo.innerHTML = `<tr><td colspan="4">${resultado.message}</td></tr>`;
      return;
    }

    if (!resultado.data.length) {
      cuerpo.innerHTML = '<tr><td colspan="4">No hay comunidades registradas todavía.</td></tr>';
      return;
    }

    cuerpo.innerHTML = resultado.data.map(c => `
      <tr>
        <td data-label="Nombre"><strong>${c.nombre}</strong></td>
        <td data-label="Sector">${c.sector}</td>
        <td data-label="Referencia">${c.referencia_ubicacion ?? '—'}</td>
        <td data-label="Acciones">
          <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-outline btn-sm" data-editar='${JSON.stringify(c)}'>Editar</button>
            <button type="button" class="btn btn-outline btn-sm" data-eliminar-id="${c.id}" data-eliminar-nombre="${c.nombre}">Eliminar</button>
          </div>
        </td>
      </tr>`).join('');

    cuerpo.querySelectorAll('[data-editar]').forEach(btn => {
      btn.addEventListener('click', () => abrirModalEditar(JSON.parse(btn.dataset.editar)));
    });
    cuerpo.querySelectorAll('[data-eliminar-id]').forEach(btn => {
      btn.addEventListener('click', () => {
        comunidadAEliminar = parseInt(btn.dataset.eliminarId, 10);
        document.getElementById('eliminar-nombre').textContent = btn.dataset.eliminarNombre;
        openModal('modal-eliminar');
      });
    });

    pintarPaginacion(resultado.meta);
  }

  function pintarPaginacion(meta) {
    if (!meta) return;
    document.getElementById('paginacion-texto').textContent =
      `Mostrando ${meta.from ?? 0}–${meta.to ?? 0} de ${meta.total} comunidades`;
    document.getElementById('btn-anterior').disabled = meta.current_page <= 1;
    document.getElementById('btn-siguiente').disabled = meta.current_page >= meta.last_page;
  }

  document.getElementById('btn-anterior').addEventListener('click', () => cargarComunidades(paginaActual - 1));
  document.getElementById('btn-siguiente').addEventListener('click', () => cargarComunidades(paginaActual + 1));

  function limpiarFormulario() {
    document.getElementById('form-comunidad').reset();
    document.querySelectorAll('#form-comunidad .form-error-msg').forEach(el => { el.hidden = true; el.textContent = ''; });
    comunidadEditando = null;
  }

  document.getElementById('btn-nueva-comunidad').addEventListener('click', () => {
    limpiarFormulario();
    document.getElementById('modal-comunidad-title').textContent = 'Nueva comunidad';
    openModal('modal-comunidad');
  });

  function abrirModalEditar(comunidad) {
    limpiarFormulario();
    comunidadEditando = comunidad.id;
    document.getElementById('modal-comunidad-title').textContent = 'Editar comunidad';
    document.getElementById('c-nombre').value = comunidad.nombre;
    document.getElementById('c-sector').value = comunidad.sector;
    document.getElementById('c-referencia').value = comunidad.referencia_ubicacion ?? '';
    openModal('modal-comunidad');
  }

  document.getElementById('form-comunidad').addEventListener('submit', async function (e) {
    e.preventDefault();
    document.querySelectorAll('#form-comunidad .form-error-msg').forEach(el => { el.hidden = true; el.textContent = ''; });

    const cuerpo = {
      nombre: document.getElementById('c-nombre').value.trim(),
      sector: document.getElementById('c-sector').value.trim(),
      referencia_ubicacion: document.getElementById('c-referencia').value.trim() || null,
    };

    const btn = document.getElementById('btn-guardar-comunidad');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const resultado = comunidadEditando
      ? await Api.put(`/comunidades/${comunidadEditando}`, cuerpo)
      : await Api.post('/comunidades', cuerpo);

    btn.disabled = false;
    btn.textContent = 'Guardar';

    if (!resultado.ok) {
      if (resultado.errors) {
        Object.entries(resultado.errors).forEach(([campo, mensajes]) => {
          const el = document.getElementById(`error-${campo}`);
          if (el) { el.hidden = false; el.textContent = mensajes[0]; }
        });
      }
      showToast(resultado.message, 'error');
      return;
    }

    closeModal('modal-comunidad');
    showToast(comunidadEditando ? 'Comunidad actualizada correctamente.' : 'Comunidad creada correctamente.');
    cargarComunidades(paginaActual);
  });

  document.getElementById('btn-confirmar-eliminar').addEventListener('click', async () => {
    if (!comunidadAEliminar) return;
    const resultado = await Api.delete(`/comunidades/${comunidadAEliminar}`);
    closeModal('modal-eliminar');
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Comunidad eliminada correctamente.');
    cargarComunidades(paginaActual);
  });

  initTableSearch('#tabla-comunidades', '#search-comunidades');

  cargarComunidades();
</script>
@endsection
