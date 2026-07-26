@extends('layouts.app')

@section('titulo', 'Noticias')

@section('content')
  <header class="page-header flex-between">
    <div>
      <h1>Noticias</h1>
      <p class="page-subtitle">Comunicados y novedades para la comunidad</p>
    </div>
    <a href="{{ route('noticias.crear') }}" class="btn btn-primary">
      <span class="material-symbols-rounded">add</span> Nueva noticia
    </a>
  </header>

  <div class="card-grid" id="lista-noticias">
    <div class="card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div></div>
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
      <h2 class="modal-title">Eliminar noticia</h2>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar <strong id="eliminar-titulo"></strong>? Esta acción no se puede deshacer.</p>
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
  let noticiaAEliminar = null;

  async function cargarNoticias(pagina = 1) {
    paginaActual = pagina;
    const contenedor = document.getElementById('lista-noticias');
    contenedor.innerHTML = '<div class="card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div></div>';

    const resultado = await Api.get(`/noticias?page=${pagina}`);
    if (!resultado.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resultado.message}</p>`;
      return;
    }

    if (!resultado.data.length) {
      contenedor.innerHTML = `<div class="empty-state"><span class="material-symbols-rounded">newspaper</span><p>Todavía no hay noticias registradas.</p></div>`;
      return;
    }

    contenedor.innerHTML = resultado.data.map(n => `
      <article class="noticia-card">
        <div class="noticia-card-img" ${n.imagen_url ? `style="background-image:url('${n.imagen_url}');background-size:cover;background-position:center"` : ''}>
          ${n.imagen_url ? '' : '<span class="material-symbols-rounded">campaign</span>'}
        </div>
        <div class="noticia-card-body">
          <div class="flex-between">
            <time class="noticia-card-date">${formatearFecha(n.fecha_publicacion)}</time>
            <span class="chip ${n.publicada ? 'chip-atendido' : 'chip-pendiente'}">${n.publicada ? 'Publicada' : 'Borrador'}</span>
          </div>
          <h3 class="noticia-card-title">${n.titulo}</h3>
          <p class="noticia-card-desc">${n.resumen}</p>
          <p class="page-subtitle" style="margin-top:4px">Por ${n.autor?.name ?? '—'}</p>
          <div style="display:flex;gap:8px;margin-top:var(--space-sm)">
            <a href="/noticias/${n.id}/editar" class="btn btn-outline btn-sm">Editar</a>
            <button type="button" class="btn btn-outline btn-sm" data-eliminar-id="${n.id}" data-eliminar-titulo="${n.titulo}">Eliminar</button>
          </div>
        </div>
      </article>`).join('');

    contenedor.querySelectorAll('[data-eliminar-id]').forEach(btn => {
      btn.addEventListener('click', () => {
        noticiaAEliminar = parseInt(btn.dataset.eliminarId, 10);
        document.getElementById('eliminar-titulo').textContent = btn.dataset.eliminarTitulo;
        openModal('modal-eliminar');
      });
    });

    pintarPaginacion(resultado.meta);
  }

  function pintarPaginacion(meta) {
    if (!meta) return;
    document.getElementById('paginacion-texto').textContent =
      `Mostrando ${meta.from ?? 0}–${meta.to ?? 0} de ${meta.total} noticias`;
    document.getElementById('btn-anterior').disabled = meta.current_page <= 1;
    document.getElementById('btn-siguiente').disabled = meta.current_page >= meta.last_page;
  }

  document.getElementById('btn-anterior').addEventListener('click', () => cargarNoticias(paginaActual - 1));
  document.getElementById('btn-siguiente').addEventListener('click', () => cargarNoticias(paginaActual + 1));

  document.getElementById('btn-confirmar-eliminar').addEventListener('click', async () => {
    if (!noticiaAEliminar) return;
    const resultado = await Api.delete(`/noticias/${noticiaAEliminar}`);
    closeModal('modal-eliminar');
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Noticia eliminada correctamente.');
    cargarNoticias(paginaActual);
  });

  cargarNoticias();
</script>
@endsection
