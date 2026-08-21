@extends('layouts.app')

@section('titulo', 'Mis campañas')

@section('content')
  <header class="module-hero">
    <div class="module-hero-copy">
      <span class="module-hero-icon module-hero-icon--green"><span class="material-symbols-rounded">event_available</span></span>
      <div>
        <span class="module-eyebrow">Mi participación</span>
        <h1>Mis campañas</h1>
        <p class="page-subtitle">Consulta dónde se necesita tu apoyo y mantén actualizada tu confirmación de asistencia.</p>
      </div>
    </div>
  </header>

  <div class="workflow-strip" aria-label="Flujo de confirmación">
    <div class="workflow-step"><span class="workflow-step-number">1</span><div><strong>Revisa la jornada</strong><small>Confirma fecha, ubicación y especialidades.</small></div></div>
    <div class="workflow-step"><span class="workflow-step-number">2</span><div><strong>Responde</strong><small>Indica si asistirás, tal vez o no asistirás.</small></div></div>
    <div class="workflow-step"><span class="workflow-step-number">3</span><div><strong>Mantén informado</strong><small>Puedes cambiar tu respuesta cuando lo necesites.</small></div></div>
  </div>

  <div id="attendance-feedback" class="card" style="display:none;margin-bottom:20px;border-left:4px solid var(--secondary)"></div>

  <section class="section-block">
    <div class="flex-between">
      <div>
        <h2 class="section-title">Confirmadas</h2>
        <p class="page-subtitle">Campañas en las que indicaste que asistirás.</p>
      </div>
      <span class="chip chip-confirmado" id="confirmed-count">0</span>
    </div>
    <div class="card-grid" id="confirmed-campaigns"><div class="card"><div class="skeleton skeleton-text"></div></div></div>
  </section>

  <section class="section-block">
    <div class="flex-between">
      <div>
        <h2 class="section-title">Pendientes de confirmar</h2>
        <p class="page-subtitle">Incluye campañas sin respuesta, marcadas como “Tal vez” o “No asistiré”.</p>
      </div>
      <span class="chip" id="pending-count">0</span>
    </div>
    <div class="card-grid" id="pending-campaigns"><div class="card"><div class="skeleton skeleton-text"></div></div></div>
  </section>
@endsection

@section('scripts')
<script>
  const attendanceLabels = { asistira: 'Asistiré', tal_vez: 'Tal vez', no_asistira: 'No asistiré' };

  function attendanceClass(value) {
    return value === 'asistira' ? 'chip-confirmado' : value === 'no_asistira' ? 'chip-cancelado' : 'chip-pendiente';
  }

  function campaignCard(campaign) {
    const response = campaign.mi_asistencia;
    const specialties = (campaign.especialidades || []).map(item => `<span class="chip">${item.nombre}</span>`).join('');
    return `<article class="brigada-card">
      <div class="brigada-card-header">
        <div><span class="page-subtitle">Campaña médica</span><h3 class="brigada-card-title">${campaign.nombre}</h3></div>
        <span class="chip ${attendanceClass(response)}">${attendanceLabels[response] || 'Por confirmar'}</span>
      </div>
      <div class="brigada-card-meta">
        <span><span class="material-symbols-rounded">event</span> Se realizará: ${formatearFecha(campaign.fecha)}</span>
        <span><span class="material-symbols-rounded">history</span> Creada: ${formatearFecha(campaign.created_at?.slice(0, 10))}</span>
        <span><span class="material-symbols-rounded">location_on</span> ${campaign.ubicacion}</span>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin:12px 0">${specialties}</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" onclick="confirmAttendance(${campaign.id}, 'asistira')">Asistiré</button>
        <button class="btn btn-outline btn-sm" onclick="confirmAttendance(${campaign.id}, 'tal_vez')">Tal vez</button>
        <button class="btn btn-ghost btn-sm" onclick="confirmAttendance(${campaign.id}, 'no_asistira')">No asistiré</button>
      </div>
    </article>`;
  }

  async function loadMyCampaigns() {
    const result = await Api.get('/me/campanas?per_page=100');
    if (!result.ok) {
      document.getElementById('confirmed-campaigns').innerHTML = `<div class="empty-state"><p>${result.message}</p></div>`;
      document.getElementById('pending-campaigns').innerHTML = '';
      return;
    }
    const campaigns = [...result.data].sort((a, b) => b.fecha.localeCompare(a.fecha));
    const confirmed = campaigns.filter(item => item.mi_asistencia === 'asistira');
    const pending = campaigns.filter(item => item.mi_asistencia !== 'asistira');
    document.getElementById('confirmed-count').textContent = confirmed.length;
    document.getElementById('pending-count').textContent = pending.length;
    document.getElementById('confirmed-campaigns').innerHTML = confirmed.length ? confirmed.map(campaignCard).join('') : '<div class="empty-state"><span class="material-symbols-rounded">event_busy</span><p>Aún no confirmas asistencia a ninguna campaña.</p></div>';
    document.getElementById('pending-campaigns').innerHTML = pending.length ? pending.map(campaignCard).join('') : '<div class="empty-state"><span class="material-symbols-rounded">task_alt</span><p>No tienes campañas pendientes.</p></div>';
  }

  async function confirmAttendance(id, state) {
    const result = await Api.put(`/brigadas/${id}/mi-asistencia`, { estado: state });
    if (!result.ok) { showToast(result.message, 'error'); return; }
    const message = result.extra?.message || 'Tu respuesta se guardó correctamente.';
    const feedback = document.getElementById('attendance-feedback');
    feedback.style.display = 'block';
    feedback.innerHTML = `<div style="display:flex;gap:12px;align-items:center"><span class="material-symbols-rounded" style="color:var(--secondary);font-size:32px">check_circle</span><div><strong>${message}</strong><p class="page-subtitle" style="margin:4px 0 0">Puedes cambiar tu respuesta cuando lo necesites. La confirmación no crea un turno de atención.</p></div></div>`;
    showToast(message, 'success');
    await loadMyCampaigns();
    feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  loadMyCampaigns();
</script>
@endsection
