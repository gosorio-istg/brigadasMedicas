@extends('layouts.app')

@section('titulo', 'Solicitudes de brigada')

@section('content')
  <header class="module-hero">
    <div class="module-hero-copy">
      <span class="module-hero-icon module-hero-icon--orange"><span class="material-symbols-rounded">inbox</span></span>
      <div>
        <span class="module-eyebrow">Demanda comunitaria</span>
        <h1>Solicitudes de brigada</h1>
        <p class="page-subtitle">Prioriza necesidades, registra el seguimiento y vincula las solicitudes aprobadas con una campaña.</p>
      </div>
    </div>
  </header>

  <div class="workflow-strip" aria-label="Flujo de gestión de solicitudes">
    <div class="workflow-step"><span class="workflow-step-number">1</span><div><strong>Revisa</strong><small>Valida sector, contacto y necesidad.</small></div></div>
    <div class="workflow-step"><span class="workflow-step-number">2</span><div><strong>Gestiona</strong><small>Documenta el análisis del coordinador.</small></div></div>
    <div class="workflow-step"><span class="workflow-step-number">3</span><div><strong>Vincula</strong><small>Asocia una campaña cuando sea aprobada.</small></div></div>
  </div>

  <div class="module-toolbar">
    <div class="form-group" style="min-width:min(100%,260px)">
      <label class="module-toolbar-label" for="filtro-estado">Estado de la solicitud</label>
      <select id="filtro-estado" class="form-control" aria-label="Filtrar por estado">
      <option value="">Todos los estados</option>
      <option value="pendiente">Pendientes</option>
      <option value="en_revision">En revisión</option>
      <option value="aprobada">Aprobadas</option>
      <option value="rechazada">Rechazadas</option>
      </select>
    </div>
    <p class="page-subtitle" style="margin-left:auto">Empieza por las pendientes y deja una nota antes de cambiar su estado.</p>
  </div>

  <section class="data-panel">
    <div class="data-panel-header"><div><div class="data-panel-title">Bandeja de solicitudes</div><div class="data-panel-caption" id="resumen-solicitudes">Cargando solicitudes...</div></div></div>
    <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Solicitante</th>
          <th>Sector</th>
          <th>Especialidades</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tabla-solicitudes-body">
        <tr><td colspan="6">Cargando...</td></tr>
      </tbody>
    </table>
    </div>

    <div class="pagination">
    <span id="paginacion-texto"></span>
    <div class="pagination-btns">
      <button class="btn btn-outline btn-sm" id="btn-anterior" disabled>Anterior</button>
      <button class="btn btn-outline btn-sm" id="btn-siguiente" disabled>Siguiente</button>
    </div>
    </div>
  </section>
@endsection

@section('modals')
  <div class="modal-overlay" id="modal-gestionar">
    <div class="modal" role="dialog" style="max-width:620px">
      <h2 class="modal-title">Gestionar solicitud</h2>
      <div class="modal-body">
        <p><strong id="detalle-solicitante"></strong></p>
        <p class="page-subtitle" id="detalle-contacto"></p>
        <p id="detalle-motivo" style="margin-top:var(--space-sm)"></p>

        <div class="form-group" style="margin-top:var(--space-md)">
          <label class="form-label" for="gestion-estado">Estado</label>
          <select id="gestion-estado" class="form-control">
            <option value="pendiente">Pendiente</option>
            <option value="en_revision">En revisión</option>
            <option value="aprobada">Aprobada</option>
            <option value="rechazada">Rechazada</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="gestion-brigada">Campaña vinculada (opcional)</label>
          <select id="gestion-brigada" class="form-control"><option value="">Sin vincular</option></select>
        </div>

        <div class="form-group">
          <label class="form-label" for="gestion-notas">Notas del coordinador</label>
          <textarea id="gestion-notas" class="form-control" rows="4"></textarea>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar">Guardar cambios</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  requirePermission('brigadas.gestionar');

  let paginaActual = 1;
  let solicitudActual = null;
  let brigadas = [];

  function textoSeguro(valor) {
    const elemento = document.createElement('span');
    elemento.textContent = valor ?? '';
    return elemento.innerHTML;
  }

  function etiquetaEstado(estado) {
    return ({ pendiente: 'Pendiente', en_revision: 'En revisión', aprobada: 'Aprobada', rechazada: 'Rechazada' })[estado] ?? estado;
  }

  function claseEstado(estado) {
    return ({ pendiente: 'chip-pendiente', en_revision: 'chip-espera', aprobada: 'chip-atendido', rechazada: 'chip-cancelado' })[estado] ?? 'chip-pendiente';
  }

  async function cargarBrigadas() {
    const resultado = await Api.get('/brigadas');
    if (!resultado.ok) return;
    brigadas = resultado.data;
    document.getElementById('gestion-brigada').innerHTML = '<option value="">Sin vincular</option>' +
      brigadas.map(b => `<option value="${b.id}">${textoSeguro(b.nombre)}</option>`).join('');
  }

  async function cargarSolicitudes(pagina = 1) {
    paginaActual = pagina;
    const cuerpo = document.getElementById('tabla-solicitudes-body');
    cuerpo.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';
    const estado = document.getElementById('filtro-estado').value;
    const query = `/solicitudes-brigada?page=${pagina}${estado ? `&estado=${encodeURIComponent(estado)}` : ''}`;
    const resultado = await Api.get(query);

    if (!resultado.ok) {
      cuerpo.innerHTML = `<tr><td colspan="6">${textoSeguro(resultado.message)}</td></tr>`;
      return;
    }
    if (!resultado.data.length) {
      cuerpo.innerHTML = '<tr><td colspan="6">No hay solicitudes con este filtro.</td></tr>';
      document.getElementById('resumen-solicitudes').textContent = 'No hay solicitudes con el estado seleccionado.';
      pintarPaginacion(resultado.meta);
      return;
    }

    document.getElementById('resumen-solicitudes').textContent = `${resultado.meta?.total ?? resultado.data.length} solicitudes encontradas`;

    cuerpo.innerHTML = resultado.data.map(s => `
      <tr>
        <td data-label="Solicitante"><strong>${textoSeguro(s.nombre_solicitante)}</strong><br><small>${textoSeguro(s.telefono_contacto)}</small></td>
        <td data-label="Sector">${textoSeguro(s.sector)}</td>
        <td data-label="Especialidades">${textoSeguro(s.especialidades_solicitadas || 'No especificadas')}</td>
        <td data-label="Estado"><span class="chip ${claseEstado(s.estado)}">${etiquetaEstado(s.estado)}</span></td>
        <td data-label="Fecha">${formatearFecha(s.created_at)}</td>
        <td data-label="Acciones"><button type="button" class="btn btn-outline btn-sm" data-gestionar="${s.id}">Gestionar</button></td>
      </tr>`).join('');

    cuerpo.querySelectorAll('[data-gestionar]').forEach(btn => {
      btn.addEventListener('click', () => abrirGestion(resultado.data.find(s => s.id === Number(btn.dataset.gestionar))));
    });
    pintarPaginacion(resultado.meta);
  }

  function pintarPaginacion(meta) {
    if (!meta) return;
    document.getElementById('paginacion-texto').textContent = `Mostrando ${meta.from ?? 0}–${meta.to ?? 0} de ${meta.total} solicitudes`;
    document.getElementById('btn-anterior').disabled = meta.current_page <= 1;
    document.getElementById('btn-siguiente').disabled = meta.current_page >= meta.last_page;
  }

  function abrirGestion(solicitud) {
    solicitudActual = solicitud;
    document.getElementById('detalle-solicitante').textContent = solicitud.nombre_solicitante;
    document.getElementById('detalle-contacto').textContent = `${solicitud.telefono_contacto} · ${solicitud.sector}`;
    document.getElementById('detalle-motivo').textContent = solicitud.motivo || 'Sin descripción adicional.';
    document.getElementById('gestion-estado').value = solicitud.estado;
    document.getElementById('gestion-brigada').value = solicitud.brigada?.id ?? '';
    document.getElementById('gestion-notas').value = solicitud.notas_coordinador ?? '';
    openModal('modal-gestionar');
  }

  document.getElementById('btn-guardar').addEventListener('click', async () => {
    if (!solicitudActual) return;
    const boton = document.getElementById('btn-guardar');
    boton.disabled = true;
    const resultado = await Api.put(`/solicitudes-brigada/${solicitudActual.id}`, {
      estado: document.getElementById('gestion-estado').value,
      brigada_id: document.getElementById('gestion-brigada').value || null,
      notas_coordinador: document.getElementById('gestion-notas').value.trim() || null,
    });
    boton.disabled = false;
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    closeModal('modal-gestionar');
    showToast('Solicitud actualizada correctamente.');
    cargarSolicitudes(paginaActual);
  });

  document.getElementById('filtro-estado').addEventListener('change', () => cargarSolicitudes(1));
  document.getElementById('btn-anterior').addEventListener('click', () => cargarSolicitudes(paginaActual - 1));
  document.getElementById('btn-siguiente').addEventListener('click', () => cargarSolicitudes(paginaActual + 1));

  Promise.all([cargarBrigadas(), cargarSolicitudes()]);
</script>
@endsection
