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
    <button class="filter-chip is-active" data-filter="all" data-label="Todas">Todas</button>
    <button class="filter-chip" data-filter="programada" data-label="Programadas">Programadas</button>
    <button class="filter-chip" data-filter="en_curso" data-label="En curso">En curso</button>
    <button class="filter-chip" data-filter="finalizada" data-label="Finalizadas">Finalizadas</button>
    <button class="filter-chip" data-filter="cancelada" data-label="Canceladas">Canceladas</button>
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

@section('scripts')
<script>
  // El FAB fijo abajo a la derecha (versión "fab", no "bottom-nav-fab") se muestra
  // solo en desktop, donde el bottom-nav ya no aparece (ver media query >=1024px en el CSS).
  if (window.matchMedia('(min-width: 1024px)').matches) {
    document.getElementById('fab-desktop').style.display = 'flex';
  }

  let brigadasCache = [];
  const MESES_ABREV = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

  function partesFecha(fechaIso) {
    const [anio, mes, dia] = fechaIso.split('T')[0].split('-');
    return { dia: parseInt(dia, 10), mes: MESES_ABREV[parseInt(mes, 10) - 1] };
  }

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
    pintarContadoresFiltro();
  }

  function estaActiva(brigada) {
    return brigada.estado === 'programada' || brigada.estado === 'en_curso';
  }

  function pintarContadoresFiltro() {
    document.querySelectorAll('.filter-bar[data-filter-group="brigadas"] .filter-chip').forEach(chip => {
      const filtro = chip.dataset.filter;
      const total = filtro === 'all' ? brigadasCache.length : brigadasCache.filter(b => b.estado === filtro).length;
      chip.innerHTML = `${chip.dataset.label} <span class="filter-chip-count">(${total})</span>`;
    });
  }

  function pintarBrigadas() {
    const contenedor = document.getElementById('lista-brigadas');
    contenedor.innerHTML = brigadasCache.map(b => {
      const fecha = partesFecha(b.fecha);
      const especialidades = b.especialidades || [];
      const chipsVisibles = especialidades.slice(0, 3)
        .map(e => `<span class="chip ${especialidadChipClass(e.nombre)}">${e.nombre}</span>`).join('');
      const restantes = especialidades.length - 3;
      const totalCupos = especialidades.reduce((suma, e) => suma + (e.cupos || 0), 0);
      const enVivo = b.estado === 'en_curso';

      return `
      <article class="campana-card campana-card--${b.estado}" data-filter-target="brigadas" data-status="${b.estado}">
        <div class="campana-card-top">
          <div class="campana-fecha-badge">
            <span class="campana-fecha-dia">${fecha.dia}</span>
            <span class="campana-fecha-mes">${fecha.mes}</span>
          </div>
          <div class="campana-card-heading">
            <h3 class="campana-card-title">${b.nombre}</h3>
            <span class="campana-card-ubicacion"><span class="material-symbols-rounded">location_on</span>${b.ubicacion}</span>
          </div>
          <span class="chip ${estadoBrigadaChipClass(b.estado)} ${enVivo ? 'chip-vivo' : ''}">
            ${enVivo ? '<span class="punto-vivo"></span>' : ''}${estadoBrigadaLabel(b.estado)}
          </span>
        </div>

        <div class="campana-card-especialidades">
          ${chipsVisibles || '<span class="page-subtitle">Sin especialidades asignadas</span>'}
          ${restantes > 0 ? `<span class="chip chip-especialidad-otra">+${restantes}</span>` : ''}
        </div>

        <div class="campana-card-footer">
          <span class="campana-card-capacidad">
            <span class="material-symbols-rounded">groups</span>
            ${especialidades.length} especialidad${especialidades.length === 1 ? '' : 'es'} · ${totalCupos} cupos en total
          </span>
          <div class="campana-card-acciones">
            <a href="/brigadas/${b.id}" class="btn btn-outline btn-sm">Ver detalles</a>
            ${estaActiva(b) ? `<a href="/pacientes?brigada_id=${b.id}&nuevo_turno=1" class="btn btn-primary btn-sm">Registrar turno</a>` : ''}
          </div>
        </div>
      </article>`;
    }).join('');

    // Re-inicializa los filter-chips ya existentes en app.js para que reconozcan
    // las tarjetas recién insertadas (initFilterChips corrió antes de tener datos).
    initFilterChips();
  }

  cargarBrigadas();
</script>
@endsection
