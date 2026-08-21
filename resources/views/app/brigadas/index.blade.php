@extends('layouts.app')

@section('titulo', 'Campañas')

@section('content')
  <header class="page-header campanas-page-header">
    <div class="campanas-page-heading">
      <span class="campanas-page-icon" aria-hidden="true">
        <span class="material-symbols-rounded">campaign</span>
      </span>
      <div>
        <span class="page-eyebrow">Gestión operativa</span>
        <h1>Campañas médicas</h1>
        <p class="page-subtitle" id="resumen-campanas">Organiza y supervisa las jornadas de atención comunitaria.</p>
      </div>
    </div>

    {{-- En escritorio la acción principal debe formar parte de la cabecera. En móvil
         se conserva el FAB porque queda al alcance natural del pulgar. --}}
    <a href="{{ route('brigadas.crear') }}" class="btn btn-primary campanas-header-action">
      <span class="material-symbols-rounded">add</span>
      Nueva campaña
    </a>
  </header>

  <nav class="filter-bar campanas-filter-bar" data-filter-group="brigadas" aria-label="Filtrar campañas">
    <button class="filter-chip is-active" data-filter="all" data-label="Todas">Todas</button>
    <button class="filter-chip" data-filter="programada" data-label="Programadas">Programadas</button>
    <button class="filter-chip" data-filter="en_curso" data-label="En curso">En curso</button>
    <button class="filter-chip" data-filter="finalizada" data-label="Finalizadas">Finalizadas</button>
    <button class="filter-chip" data-filter="cancelada" data-label="Canceladas">Canceladas</button>
  </nav>

  <div class="campanas-grid" id="lista-brigadas" aria-live="polite">
    @for ($i = 0; $i < 3; $i++)
      <div class="card campana-card-skeleton"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div></div>
    @endfor
  </div>

  <a href="{{ route('brigadas.crear') }}" class="bottom-nav-fab" aria-label="Nueva campaña">
    <span class="material-symbols-rounded">add</span>
  </a>
@endsection

@section('scripts')
<script>
  let brigadasCache = [];
  const MESES_ABREV = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

  function partesFecha(fechaIso) {
    const [anio, mes, dia] = fechaIso.split('T')[0].split('-');
    return { dia: parseInt(dia, 10), mes: MESES_ABREV[parseInt(mes, 10) - 1], anio };
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
    pintarResumenCampanas();
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

  // Resume la operación en una frase breve sin agregar otra fila de tarjetas estadísticas.
  function pintarResumenCampanas() {
    const activas = brigadasCache.filter(b => b.estado === 'programada' || b.estado === 'en_curso').length;
    const total = brigadasCache.length;
    document.getElementById('resumen-campanas').textContent =
      `${total} campaña${total === 1 ? '' : 's'} registrada${total === 1 ? '' : 's'} · ${activas} activa${activas === 1 ? '' : 's'} o próxima${activas === 1 ? '' : 's'}`;
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
            <span class="campana-fecha-anio">${fecha.anio}</span>
          </div>
          <div class="campana-card-heading">
            <span class="campana-card-kicker">Jornada comunitaria</span>
            <h3 class="campana-card-title">${b.nombre}</h3>
          </div>
          <span class="chip ${estadoBrigadaChipClass(b.estado)} ${enVivo ? 'chip-vivo' : ''}">
            ${enVivo ? '<span class="punto-vivo"></span>' : ''}${estadoBrigadaLabel(b.estado)}
          </span>
        </div>

        <div class="campana-card-ubicacion">
          <span class="campana-meta-icon"><span class="material-symbols-rounded">location_on</span></span>
          <span><small>Ubicación</small><strong>${b.ubicacion}</strong></span>
        </div>

        <span class="campana-card-section-label">Especialidades disponibles</span>
        <div class="campana-card-especialidades">
          ${chipsVisibles || '<span class="page-subtitle">Sin especialidades asignadas</span>'}
          ${restantes > 0 ? `<span class="chip chip-especialidad-otra">+${restantes}</span>` : ''}
        </div>

        <div class="campana-card-footer">
          <div class="campana-card-metricas">
            <span class="campana-card-metrica">
              <span class="material-symbols-rounded">medical_services</span>
              <span><strong>${especialidades.length}</strong><small>Especialidades</small></span>
            </span>
            <span class="campana-card-metrica">
              <span class="material-symbols-rounded">groups</span>
              <span><strong>${totalCupos}</strong><small>Cupos totales</small></span>
            </span>
          </div>
          <div class="campana-card-acciones">
            <a href="/brigadas/${b.id}" class="btn btn-outline btn-sm">
              Ver detalles <span class="material-symbols-rounded">arrow_outward</span>
            </a>
            ${estaActiva(b) ? `<a href="/pacientes?brigada_id=${b.id}&nuevo_turno=1" class="btn btn-primary btn-sm"><span class="material-symbols-rounded">add_circle</span> Registrar turno</a>` : ''}
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
