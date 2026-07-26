@extends('layouts.app')

@section('titulo', 'Campañas')

@section('content')
  <header class="page-header flex-between">
    <div>
      <h1>Campañas</h1>
      <p class="page-subtitle">Campañas médicas comunitarias programadas</p>
    </div>
  </header>

  <nav class="filter-bar" data-filter-group="brigadas" aria-label="Filtrar campañas">
    <button class="filter-chip is-active" data-filter="all">Todas</button>
    <button class="filter-chip" data-filter="programada">Programadas</button>
    <button class="filter-chip" data-filter="en_curso">En curso</button>
    <button class="filter-chip" data-filter="finalizada">Finalizadas</button>
    <button class="filter-chip" data-filter="cancelada">Canceladas</button>
  </nav>

  <div class="card-grid" id="lista-brigadas">
    <div class="card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div></div>
  </div>

  <a href="{{ route('brigadas.crear') }}" class="bottom-nav-fab" aria-label="Nueva campaña">
    <span class="material-symbols-rounded">add</span>
  </a>
  <a href="{{ route('brigadas.crear') }}" class="fab" aria-label="Nueva campaña" style="display:none" id="fab-desktop">
    <span class="material-symbols-rounded">add</span>
  </a>
@endsection

@section('modals')
  <div class="modal-overlay" id="modal-detalle">
    <div class="modal" role="dialog" aria-labelledby="modal-title">
      <h2 class="modal-title" id="modal-title">Detalle de campaña</h2>
      <div class="modal-body" id="modal-detalle-body">Cargando...</div>
      <div class="modal-actions">
        <button class="btn btn-primary" data-modal-close>Cerrar</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  // El FAB fijo abajo a la derecha (versión "fab", no "bottom-nav-fab") se muestra
  // solo en desktop, donde el bottom-nav ya no aparece (ver media query >=1024px en el CSS).
  if (window.matchMedia('(min-width: 1024px)').matches) {
    document.getElementById('fab-desktop').style.display = 'flex';
  }

  let brigadasCache = [];

  async function cargarBrigadas() {
    const resultado = await Api.get('/brigadas?per_page=100');
    const contenedor = document.getElementById('lista-brigadas');

    if (!resultado.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resultado.message}</p>`;
      return;
    }

    brigadasCache = resultado.data;
    if (!brigadasCache.length) {
      contenedor.innerHTML = `<div class="empty-state"><span class="material-symbols-rounded">event_busy</span><p>Todavía no hay campañas registradas.</p></div>`;
      return;
    }

    pintarBrigadas();

    // Llega desde el buscador global (topbar) con ?brigada_id=... -> abre su detalle directo.
    const idDesdeUrl = new URLSearchParams(window.location.search).get('brigada_id');
    if (idDesdeUrl) mostrarDetalle(parseInt(idDesdeUrl, 10));
  }

  function pintarBrigadas() {
    const contenedor = document.getElementById('lista-brigadas');
    contenedor.innerHTML = brigadasCache.map(b => `
      <article class="brigada-card" data-filter-target="brigadas" data-status="${b.estado}">
        <div class="brigada-card-header">
          <h3 class="brigada-card-title">${b.nombre}</h3>
          <span class="chip ${estadoBrigadaChipClass(b.estado)}">${estadoBrigadaLabel(b.estado)}</span>
        </div>
        <div class="brigada-card-meta">
          <span><span class="material-symbols-rounded">calendar_month</span> ${formatearFecha(b.fecha)}</span>
          <span><span class="material-symbols-rounded">location_on</span> ${b.ubicacion}</span>
          <span><span class="material-symbols-rounded">medical_information</span> ${(b.especialidades || []).map(e => e.nombre).join(', ') || 'Sin especialidades'}</span>
        </div>
        <button class="btn btn-outline btn-sm" data-ver-brigada="${b.id}">Ver detalles</button>
      </article>`).join('');

    contenedor.querySelectorAll('[data-ver-brigada]').forEach(btn => {
      btn.addEventListener('click', () => mostrarDetalle(parseInt(btn.dataset.verBrigada, 10)));
    });

    // Re-inicializa los filter-chips ya existentes en app.js para que reconozcan
    // las tarjetas recién insertadas (initFilterChips corrió antes de tener datos).
    initFilterChips();
  }

  function mostrarDetalle(id) {
    const brigada = brigadasCache.find(b => b.id === id);
    if (!brigada) return;

    const especialidades = (brigada.especialidades || [])
      .map(e => `${e.nombre} (${e.cupos} cupos)`)
      .join(', ') || 'Sin especialidades asignadas';

    document.getElementById('modal-detalle-body').innerHTML = `
      <div class="detail-row"><span class="detail-label">Estado</span><span class="chip ${estadoBrigadaChipClass(brigada.estado)}">${estadoBrigadaLabel(brigada.estado)}</span></div>
      <div class="detail-row"><span class="detail-label">Fecha</span><span>${formatearFecha(brigada.fecha)}</span></div>
      <div class="detail-row"><span class="detail-label">Ubicación</span><span>${brigada.ubicacion}</span></div>
      <div class="detail-row"><span class="detail-label">Coordinador</span><span>${brigada.coordinador?.name ?? '—'}</span></div>
      ${brigada.descripcion ? `<div class="detail-row"><span class="detail-label">Descripción</span><span>${brigada.descripcion}</span></div>` : ''}
      <h3 style="font-size:var(--font-size-small);margin:var(--space-md) 0 var(--space-sm)">Especialidades</h3>
      <p style="font-size:var(--font-size-small);color:var(--color-text-muted)">${especialidades}</p>`;
    openModal('modal-detalle');
  }

  cargarBrigadas();
</script>
@endsection
