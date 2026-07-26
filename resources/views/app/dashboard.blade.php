@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('content')
  <header class="page-header">
    <h1>Dashboard</h1>
    <p class="page-subtitle">Resumen general de brigadas médicas comunitarias</p>
  </header>

  <section class="section-block">
    <div class="stat-grid" id="stat-grid">
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
    </div>
  </section>

  <div class="grid-2">
    <section class="section-block">
      <div class="flex-between">
        <h2 class="section-title">Próximas brigadas</h2>
        <a href="{{ route('brigadas.index') }}" class="btn btn-ghost btn-sm" style="white-space:nowrap">Ver todas</a>
      </div>
      <div class="card-grid" id="proximas-brigadas">
        <div class="card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div></div>
      </div>
    </section>

    <section class="section-block">
      <h2 class="section-title">Atenciones por especialidad</h2>
      <div class="card">
        <div class="chart-bars" id="chart-especialidades">
          <p class="page-subtitle">Cargando...</p>
        </div>
      </div>
    </section>
  </div>

  <section class="section-block">
    <div class="flex-between">
      <h2 class="section-title">Noticias recientes</h2>
      <a href="{{ route('noticias.index') }}" class="btn btn-ghost btn-sm" style="white-space:nowrap">Ver todas</a>
    </div>
    <div class="card-grid" id="noticias-recientes">
      <div class="card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div></div>
    </div>
  </section>
@endsection

@section('scripts')
<script>
  async function cargarDashboard() {
    const [pacientesRes, brigadasRes, medicosRes, reporteRes, noticiasRes] = await Promise.all([
      Api.get('/pacientes?per_page=1'),
      Api.get('/brigadas?per_page=100'),
      Api.get('/medicos?per_page=100'),
      Api.get('/reportes/resumen'),
      Api.get('/noticias?per_page=2'),
    ]);

    pintarEstadisticas(pacientesRes, brigadasRes, medicosRes, reporteRes);
    pintarProximasBrigadas(brigadasRes);
    pintarChartEspecialidades(reporteRes);
    pintarNoticias(noticiasRes);
  }

  function pintarEstadisticas(pacientesRes, brigadasRes, medicosRes, reporteRes) {
    const totalPacientes = pacientesRes.ok ? (pacientesRes.meta?.total ?? '—') : '—';
    const brigadas = brigadasRes.ok ? brigadasRes.data : [];
    const brigadasActivas = brigadas.filter(b => b.estado === 'en_curso').length;
    const medicos = medicosRes.ok ? medicosRes.data : [];
    const medicosDisponibles = medicos.filter(m => m.disponible).length;
    const totalAtendidos = reporteRes.ok ? reporteRes.data.total_atendidos : '—';

    document.getElementById('stat-grid').innerHTML = `
      <div class="stat-card">
        <div class="stat-card-value">${totalPacientes}</div>
        <div class="stat-card-label">Pacientes registrados</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-value">${brigadasActivas}</div>
        <div class="stat-card-label">Brigadas en curso</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-value">${totalAtendidos}</div>
        <div class="stat-card-label">Total atendidos</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-value">${medicosDisponibles}</div>
        <div class="stat-card-label">Médicos disponibles</div>
      </div>`;
  }

  function pintarProximasBrigadas(brigadasRes) {
    const contenedor = document.getElementById('proximas-brigadas');
    if (!brigadasRes.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${brigadasRes.message}</p>`;
      return;
    }

    const hoy = new Date().toISOString().slice(0, 10);
    const proximas = brigadasRes.data
      .filter(b => b.fecha >= hoy && b.estado !== 'cancelada')
      .sort((a, b) => a.fecha.localeCompare(b.fecha))
      .slice(0, 2);

    if (!proximas.length) {
      contenedor.innerHTML = `<div class="empty-state"><span class="material-symbols-rounded">event_busy</span><p>No hay brigadas próximas programadas.</p></div>`;
      return;
    }

    contenedor.innerHTML = proximas.map(b => `
      <article class="brigada-card">
        <div class="brigada-card-header">
          <h3 class="brigada-card-title">${b.nombre}</h3>
          <span class="chip ${estadoBrigadaChipClass(b.estado)}">${estadoBrigadaLabel(b.estado)}</span>
        </div>
        <div class="brigada-card-meta">
          <span><span class="material-symbols-rounded">calendar_month</span> ${formatearFecha(b.fecha)}</span>
          <span><span class="material-symbols-rounded">location_on</span> ${b.ubicacion}</span>
        </div>
        <a href="{{ route('brigadas.index') }}" class="btn btn-outline btn-sm">Ver detalles</a>
      </article>`).join('');
  }

  function pintarChartEspecialidades(reporteRes) {
    const contenedor = document.getElementById('chart-especialidades');
    if (!reporteRes.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${reporteRes.message}</p>`;
      return;
    }

    const desglose = reporteRes.data.desglose_por_especialidad || [];
    if (!desglose.length) {
      contenedor.innerHTML = `<p class="page-subtitle">Todavía no hay turnos registrados.</p>`;
      return;
    }

    const maximo = Math.max(...desglose.map(d => d.total), 1);
    contenedor.innerHTML = desglose.map(d => `
      <div class="chart-bar-item">
        <div class="chart-bar" style="height:${Math.max(8, Math.round((d.total / maximo) * 150))}px" data-value="${d.total}"></div>
        <span class="chart-bar-label">${d.especialidad}</span>
      </div>`).join('');
  }

  function pintarNoticias(noticiasRes) {
    const contenedor = document.getElementById('noticias-recientes');
    if (!noticiasRes.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${noticiasRes.message}</p>`;
      return;
    }

    if (!noticiasRes.data.length) {
      contenedor.innerHTML = `<div class="empty-state"><span class="material-symbols-rounded">newspaper</span><p>Todavía no hay noticias publicadas.</p></div>`;
      return;
    }

    contenedor.innerHTML = noticiasRes.data.map(n => `
      <article class="noticia-card">
        <div class="noticia-card-img"><span class="material-symbols-rounded">campaign</span></div>
        <div class="noticia-card-body">
          <time class="noticia-card-date">${formatearFecha(n.fecha_publicacion)}</time>
          <h3 class="noticia-card-title">${n.titulo}</h3>
          <p class="noticia-card-desc">${n.resumen}</p>
        </div>
      </article>`).join('');
  }

  cargarDashboard();
</script>
@endsection
