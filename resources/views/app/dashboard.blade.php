@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('content')
  <header class="page-header">
    <h1>Dashboard</h1>
    <p class="page-subtitle">Resumen general de campañas médicas comunitarias</p>
  </header>

  <section class="section-block">
    <div class="stat-grid" id="stat-grid">
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
    </div>
  </section>

  <div class="grid-2" id="grid-resumen">
    <section class="section-block">
      <div class="flex-between">
        <h2 class="section-title">Próximas campañas</h2>
        <a href="{{ route('brigadas.index') }}" class="btn btn-ghost btn-sm" style="white-space:nowrap">Ver todas</a>
      </div>
      <div class="card-grid" id="proximas-brigadas">
        <div class="card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div></div>
      </div>
    </section>

    <section class="section-block" id="seccion-chart-especialidades">
      <h2 class="section-title">Atenciones por especialidad</h2>
      <div class="card">
        <div class="chart-bars" id="chart-especialidades">
          <p class="page-subtitle">Cargando...</p>
        </div>
      </div>
    </section>
  </div>

  <section class="section-block" id="seccion-noticias">
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
    // No se pide (ni se muestra) lo que este usuario no tiene permiso de ver: antes el
    // dashboard llamaba a /medicos, /reportes/resumen y /noticias sin condición, así que
    // a un Brigadista (que no tiene esos permisos) le tiraba tres 403 apenas entraba.
    const puedeMedicos = hasPermission('medicos.gestionar');
    const puedeReportes = hasPermission('reportes.ver');
    const puedeNoticias = hasPermission('noticias.gestionar');

    if (!puedeReportes) document.getElementById('seccion-chart-especialidades').remove();
    if (!puedeNoticias) document.getElementById('seccion-noticias').remove();
    if (!puedeReportes) document.getElementById('grid-resumen').style.gridTemplateColumns = '1fr';

    const [pacientesRes, brigadasRes, medicosRes, reporteRes, noticiasRes] = await Promise.all([
      Api.get('/pacientes?per_page=1'),
      Api.get('/brigadas?per_page=100'),
      puedeMedicos ? Api.get('/medicos?per_page=100') : Promise.resolve(null),
      puedeReportes ? Api.get('/reportes/resumen') : Promise.resolve(null),
      puedeNoticias ? Api.get('/noticias?per_page=2') : Promise.resolve(null),
    ]);

    pintarEstadisticas(pacientesRes, brigadasRes, medicosRes, reporteRes);
    pintarProximasBrigadas(brigadasRes);
    if (puedeReportes) pintarChartEspecialidades(reporteRes);
    if (puedeNoticias) pintarNoticias(noticiasRes);
  }

  function pintarEstadisticas(pacientesRes, brigadasRes, medicosRes, reporteRes) {
    const totalPacientes = pacientesRes.ok ? (pacientesRes.meta?.total ?? '—') : '—';
    const brigadas = brigadasRes.ok ? brigadasRes.data : [];
    const brigadasActivas = brigadas.filter(b => b.estado === 'en_curso').length;

    const tarjetas = [
      { valor: totalPacientes, etiqueta: 'Pacientes registrados' },
      { valor: brigadasActivas, etiqueta: 'Campañas en curso' },
    ];

    if (reporteRes) {
      tarjetas.push({ valor: reporteRes.ok ? reporteRes.data.total_atendidos : '—', etiqueta: 'Total atendidos' });
    }
    if (medicosRes) {
      const medicosDisponibles = medicosRes.ok ? medicosRes.data.filter(m => m.disponible).length : '—';
      tarjetas.push({ valor: medicosDisponibles, etiqueta: 'Médicos disponibles' });
    }

    document.getElementById('stat-grid').innerHTML = tarjetas.map(t => `
      <div class="stat-card">
        <div class="stat-card-value">${t.valor}</div>
        <div class="stat-card-label">${t.etiqueta}</div>
      </div>`).join('');
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
      contenedor.innerHTML = `<div class="empty-state"><span class="material-symbols-rounded">event_busy</span><p>No hay campañas próximas programadas.</p></div>`;
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
