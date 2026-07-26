@extends('layouts.app')

@section('titulo', 'Reportes')

@section('content')
  <header class="page-header flex-between">
    <div>
      <h1>Reportes</h1>
      <p class="page-subtitle">Indicadores de atención de las brigadas médicas</p>
    </div>
    <select class="form-control" id="filtro-brigada" style="max-width:280px">
      <option value="">Todas las brigadas</option>
    </select>
  </header>

  <section class="section-block">
    <div class="stat-grid" id="stat-grid">
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
      <div class="stat-card"><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>
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

  <section class="section-block">
    <h2 class="section-title">Distribución por sector</h2>
    <div class="table-responsive">
      <table class="data-table" id="tabla-sectores">
        <thead>
          <tr>
            <th>Sector</th>
            <th>Turnos totales</th>
            <th>Atendidos</th>
            <th>Pacientes únicos</th>
          </tr>
        </thead>
        <tbody id="tabla-sectores-body">
          <tr><td colspan="4">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection

@section('scripts')
<script>
  async function cargarBrigadasFiltro() {
    const resultado = await Api.get('/brigadas?per_page=100');
    if (!resultado.ok) return;
    const select = document.getElementById('filtro-brigada');
    select.insertAdjacentHTML('beforeend',
      resultado.data.map(b => `<option value="${b.id}">${b.nombre}</option>`).join(''));
  }

  async function cargarReportes() {
    const brigadaId = document.getElementById('filtro-brigada').value;
    const sufijo = brigadaId ? `?brigada_id=${brigadaId}` : '';

    const [resumenRes, sectorRes] = await Promise.all([
      Api.get(`/reportes/resumen${sufijo}`),
      Api.get(`/reportes/por-sector${sufijo}`),
    ]);

    pintarEstadisticas(resumenRes);
    pintarChart(resumenRes);
    pintarSectores(sectorRes);
  }

  function pintarEstadisticas(resumenRes) {
    const grid = document.getElementById('stat-grid');
    if (!resumenRes.ok) {
      grid.innerHTML = `<p class="page-subtitle">${resumenRes.message}</p>`;
      return;
    }
    const r = resumenRes.data;
    const tiempo = r.tiempo_promedio_espera_minutos !== null
      ? `${r.tiempo_promedio_espera_minutos} min` : '—';

    grid.innerHTML = `
      <div class="stat-card">
        <div class="stat-card-value">${r.total_turnos}</div>
        <div class="stat-card-label">Turnos totales</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-value">${r.total_atendidos}</div>
        <div class="stat-card-label">Total atendidos</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-value">${tiempo}</div>
        <div class="stat-card-label">Espera promedio</div>
      </div>`;
  }

  function pintarChart(resumenRes) {
    const contenedor = document.getElementById('chart-especialidades');
    if (!resumenRes.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resumenRes.message}</p>`;
      return;
    }
    const desglose = resumenRes.data.desglose_por_especialidad;
    if (!desglose.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Todavía no hay turnos registrados.</p>';
      return;
    }
    const maximo = Math.max(...desglose.map(d => d.total), 1);
    contenedor.innerHTML = desglose.map(d => `
      <div class="chart-bar-item">
        <div class="chart-bar" style="height:${Math.max(8, Math.round((d.total / maximo) * 150))}px" data-value="${d.total}"></div>
        <span class="chart-bar-label">${d.especialidad}</span>
      </div>`).join('');
  }

  function pintarSectores(sectorRes) {
    const cuerpo = document.getElementById('tabla-sectores-body');
    if (!sectorRes.ok) {
      cuerpo.innerHTML = `<tr><td colspan="4">${sectorRes.message}</td></tr>`;
      return;
    }
    if (!sectorRes.data.length) {
      cuerpo.innerHTML = '<tr><td colspan="4">Todavía no hay turnos registrados.</td></tr>';
      return;
    }
    cuerpo.innerHTML = sectorRes.data.map(s => `
      <tr>
        <td data-label="Sector"><strong>${s.sector}</strong></td>
        <td data-label="Turnos totales">${s.total_turnos}</td>
        <td data-label="Atendidos">${s.total_atendidos}</td>
        <td data-label="Pacientes únicos">${s.pacientes_unicos}</td>
      </tr>`).join('');
  }

  document.getElementById('filtro-brigada').addEventListener('change', cargarReportes);

  cargarBrigadasFiltro();
  cargarReportes();
</script>
@endsection
