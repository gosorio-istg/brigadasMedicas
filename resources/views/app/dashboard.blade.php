@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('content')
  <header class="page-header">
    <h1>Dashboard</h1>
    <p class="page-subtitle">Resumen general de campañas médicas comunitarias</p>
  </header>

  <section class="section-block">
    <div class="stat-grid" id="stat-grid">
      @for ($i = 0; $i < 6; $i++)
        <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      @endfor
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

    <section class="section-block" id="seccion-chart-campanas">
      <h2 class="section-title">Campañas por estado</h2>
      <div class="card">
        <div class="chart-card-canvas-wrap" id="wrap-chart-campanas">
          <canvas id="chart-campanas"></canvas>
        </div>
      </div>
    </section>
  </div>

  <section class="section-block" id="seccion-charts-reportes">
    <h2 class="section-title">Indicadores de atención</h2>
    <div class="grid-3">
      <div class="card">
        <div class="flex-between"><strong>Atenciones por especialidad</strong></div>
        <div class="chart-card-canvas-wrap" id="wrap-chart-especialidades">
          <canvas id="chart-especialidades"></canvas>
        </div>
      </div>
      <div class="card">
        <div class="flex-between"><strong>Actividad últimos 7 días</strong></div>
        <div class="chart-card-canvas-wrap" id="wrap-chart-tendencia">
          <canvas id="chart-tendencia"></canvas>
        </div>
      </div>
      <div class="card">
        <div class="flex-between"><strong>Turnos por estado</strong></div>
        <div class="chart-card-canvas-wrap" id="wrap-chart-estados">
          <canvas id="chart-estados"></canvas>
        </div>
      </div>
    </div>
  </section>

  <section class="section-block" id="seccion-chart-sectores">
    <h2 class="section-title">Sectores con más turnos</h2>
    <div class="card">
      <div class="chart-card-canvas-wrap" id="wrap-chart-sectores">
        <canvas id="chart-sectores"></canvas>
      </div>
    </div>
  </section>

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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
  // Paleta consistente con las variables CSS de styles.css (--color-primary, etc.), para que
  // los gráficos de Chart.js se vean como parte del mismo sistema visual, no una librería pegada.
  const PALETA = {
    primary: '#1565C0', primaryLight: 'rgba(21, 101, 192, 0.15)',
    secondary: '#43A047', accent: '#42A5F5',
    success: '#22C55E', error: '#EF4444', warning: '#F59E0B', info: '#3B82F6',
    muted: '#607D8B', grid: '#E2E8F0',
  };
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.color = PALETA.muted;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;

  const ESTADO_TURNO_COLOR = {
    pendiente: PALETA.warning, en_espera: PALETA.accent, atendido: PALETA.success,
    cancelado: PALETA.error, no_asistio: '#94A3B8',
  };
  const ESTADO_BRIGADA_COLOR = {
    programada: PALETA.info, en_curso: PALETA.warning, finalizada: PALETA.success, cancelada: PALETA.error,
  };

  async function cargarDashboard() {
    // No se pide (ni se muestra) lo que este usuario no tiene permiso de ver: antes el
    // dashboard llamaba a /medicos, /reportes/resumen y /noticias sin condición, así que
    // a un Brigadista (que no tiene esos permisos) le tiraba tres 403 apenas entraba.
    const puedeMedicos = hasPermission('medicos.gestionar');
    const puedeReportes = hasPermission('reportes.ver');
    const puedeNoticias = hasPermission('noticias.gestionar');

    if (!puedeReportes) {
      document.getElementById('seccion-charts-reportes').remove();
      document.getElementById('seccion-chart-sectores').remove();
    }
    if (!puedeNoticias) document.getElementById('seccion-noticias').remove();

    const [pacientesRes, brigadasRes, medicosRes, reporteRes, sectorRes, noticiasRes] = await Promise.all([
      Api.get('/pacientes?per_page=1'),
      Api.get('/brigadas?per_page=100'),
      puedeMedicos ? Api.get('/medicos?per_page=100') : Promise.resolve(null),
      puedeReportes ? Api.get('/reportes/resumen') : Promise.resolve(null),
      puedeReportes ? Api.get('/reportes/por-sector') : Promise.resolve(null),
      puedeNoticias ? Api.get('/noticias?per_page=2') : Promise.resolve(null),
    ]);

    pintarEstadisticas(pacientesRes, brigadasRes, medicosRes, reporteRes);
    pintarProximasBrigadas(brigadasRes);
    pintarChartCampanas(brigadasRes);
    if (puedeReportes) {
      pintarChartEspecialidades(reporteRes);
      pintarChartTendencia(reporteRes);
      pintarChartEstados(reporteRes);
      pintarChartSectores(sectorRes);
    }
    if (puedeNoticias) pintarNoticias(noticiasRes);
  }

  function pintarEstadisticas(pacientesRes, brigadasRes, medicosRes, reporteRes) {
    const totalPacientes = pacientesRes.ok ? (pacientesRes.meta?.total ?? '—') : '—';
    const brigadas = brigadasRes.ok ? brigadasRes.data : [];
    const brigadasActivas = brigadas.filter(b => b.estado === 'en_curso').length;
    const brigadasProgramadas = brigadas.filter(b => b.estado === 'programada').length;

    const tarjetas = [
      { valor: totalPacientes, etiqueta: 'Pacientes registrados', icono: 'personal_injury', tono: 'primary' },
      { valor: brigadasActivas, etiqueta: 'Campañas en curso', icono: 'medical_services', tono: 'success' },
      { valor: brigadasProgramadas, etiqueta: 'Campañas programadas', icono: 'event_upcoming', tono: 'info' },
    ];

    if (reporteRes) {
      const r = reporteRes.ok ? reporteRes.data : null;
      tarjetas.push({ valor: r ? r.total_atendidos : '—', etiqueta: 'Total atendidos', icono: 'health_and_safety', tono: 'success' });
      const tasa = r && r.total_turnos > 0 ? `${Math.round((r.total_atendidos / r.total_turnos) * 100)}%` : '—';
      tarjetas.push({ valor: tasa, etiqueta: 'Tasa de atención', icono: 'monitoring', tono: 'primary' });
      const espera = r && r.tiempo_promedio_espera_minutos !== null ? `${r.tiempo_promedio_espera_minutos} min` : '—';
      tarjetas.push({ valor: espera, etiqueta: 'Espera promedio', icono: 'avg_time', tono: 'warning' });
    }
    if (medicosRes) {
      const medicosDisponibles = medicosRes.ok ? medicosRes.data.filter(m => m.disponible).length : '—';
      tarjetas.push({ valor: medicosDisponibles, etiqueta: 'Médicos disponibles', icono: 'stethoscope', tono: 'info' });
    }

    document.getElementById('stat-grid').innerHTML = tarjetas.map(t => `
      <div class="stat-card">
        <div class="stat-card-icon stat-card-icon--${t.tono}">
          <span class="material-symbols-rounded">${t.icono}</span>
        </div>
        <div class="stat-card-copy">
          <div class="stat-card-value">${t.valor}</div>
          <div class="stat-card-label">${t.etiqueta}</div>
        </div>
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

  function vaciarSiNoHayDatos(wrapId, canvasId, hayDatos, mensaje) {
    if (hayDatos) return false;
    document.getElementById(wrapId).innerHTML = `<div class="chart-card-empty">${mensaje}</div>`;
    return true;
  }

  function pintarChartCampanas(brigadasRes) {
    if (!brigadasRes.ok) {
      document.getElementById('wrap-chart-campanas').innerHTML = `<div class="chart-card-empty">${brigadasRes.message}</div>`;
      return;
    }
    const estados = ['programada', 'en_curso', 'finalizada', 'cancelada'];
    const conteos = estados.map(e => brigadasRes.data.filter(b => b.estado === e).length);
    if (vaciarSiNoHayDatos('wrap-chart-campanas', 'chart-campanas', conteos.some(c => c > 0), 'Todavía no hay campañas registradas.')) return;

    new Chart(document.getElementById('chart-campanas'), {
      type: 'doughnut',
      data: {
        labels: estados.map(estadoBrigadaLabel),
        datasets: [{ data: conteos, backgroundColor: estados.map(e => ESTADO_BRIGADA_COLOR[e]), borderWidth: 0 }],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
  }

  function pintarChartEspecialidades(reporteRes) {
    if (!reporteRes.ok) {
      document.getElementById('wrap-chart-especialidades').innerHTML = `<div class="chart-card-empty">${reporteRes.message}</div>`;
      return;
    }
    const desglose = reporteRes.data.desglose_por_especialidad || [];
    if (vaciarSiNoHayDatos('wrap-chart-especialidades', 'chart-especialidades', desglose.length > 0, 'Todavía no hay turnos registrados.')) return;

    new Chart(document.getElementById('chart-especialidades'), {
      type: 'bar',
      data: {
        labels: desglose.map(d => d.especialidad),
        datasets: [
          { label: 'Total', data: desglose.map(d => d.total), backgroundColor: PALETA.primaryLight, borderColor: PALETA.primary, borderWidth: 1, borderRadius: 4 },
          { label: 'Atendidos', data: desglose.map(d => d.atendidos), backgroundColor: PALETA.secondary, borderRadius: 4 },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: PALETA.grid } }, x: { grid: { display: false } } },
        plugins: { legend: { position: 'bottom' } },
      },
    });
  }

  function pintarChartTendencia(reporteRes) {
    if (!reporteRes.ok) {
      document.getElementById('wrap-chart-tendencia').innerHTML = `<div class="chart-card-empty">${reporteRes.message}</div>`;
      return;
    }
    const dias = reporteRes.data.tendencia_semanal || [];
    if (vaciarSiNoHayDatos('wrap-chart-tendencia', 'chart-tendencia', dias.length > 0, 'Sin actividad reciente.')) return;

    new Chart(document.getElementById('chart-tendencia'), {
      type: 'bar',
      data: {
        labels: dias.map(d => formatearFechaCorta(d.fecha)),
        datasets: [
          { label: 'Turnos', data: dias.map(d => d.total), backgroundColor: PALETA.primaryLight, borderColor: PALETA.primary, borderWidth: 1, borderRadius: 4 },
          { label: 'Atendidos', data: dias.map(d => d.atendidos), backgroundColor: PALETA.secondary, borderRadius: 4 },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: PALETA.grid } }, x: { grid: { display: false } } },
        plugins: { legend: { position: 'bottom' } },
      },
    });
  }

  function pintarChartEstados(reporteRes) {
    if (!reporteRes.ok) {
      document.getElementById('wrap-chart-estados').innerHTML = `<div class="chart-card-empty">${reporteRes.message}</div>`;
      return;
    }
    const desglose = (reporteRes.data.desglose_por_estado || []).filter(d => d.total > 0);
    if (vaciarSiNoHayDatos('wrap-chart-estados', 'chart-estados', desglose.length > 0, 'Todavía no hay turnos registrados.')) return;

    new Chart(document.getElementById('chart-estados'), {
      type: 'doughnut',
      data: {
        labels: desglose.map(d => estadoTurnoLabel(d.estado)),
        datasets: [{ data: desglose.map(d => d.total), backgroundColor: desglose.map(d => ESTADO_TURNO_COLOR[d.estado]), borderWidth: 0 }],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
  }

  function pintarChartSectores(sectorRes) {
    if (!sectorRes.ok) {
      document.getElementById('wrap-chart-sectores').innerHTML = `<div class="chart-card-empty">${sectorRes.message}</div>`;
      return;
    }
    const top = [...sectorRes.data].sort((a, b) => b.total_turnos - a.total_turnos).slice(0, 8);
    if (vaciarSiNoHayDatos('wrap-chart-sectores', 'chart-sectores', top.length > 0, 'Todavía no hay turnos registrados.')) return;

    new Chart(document.getElementById('chart-sectores'), {
      type: 'bar',
      data: {
        labels: top.map(s => s.sector),
        datasets: [
          { label: 'Turnos totales', data: top.map(s => s.total_turnos), backgroundColor: PALETA.primaryLight, borderColor: PALETA.primary, borderWidth: 1, borderRadius: 4 },
          { label: 'Atendidos', data: top.map(s => s.total_atendidos), backgroundColor: PALETA.secondary, borderRadius: 4 },
        ],
      },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: PALETA.grid } }, y: { grid: { display: false } } },
        plugins: { legend: { position: 'bottom' } },
      },
    });
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

  function formatearFechaCorta(fechaIso) {
    const [, mes, dia] = fechaIso.split('-');
    const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return `${dia} ${meses[parseInt(mes, 10) - 1]}`;
  }

  cargarDashboard();
</script>
@endsection
