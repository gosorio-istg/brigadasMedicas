@extends('layouts.app')

@section('titulo', isset($id) ? 'Editar médico' : 'Nuevo médico')

@section('content')
  <header class="page-header">
    <a href="{{ route('medicos.index') }}" class="btn btn-ghost btn-sm" style="margin-bottom:var(--space-sm)">
      <span class="material-symbols-rounded">arrow_back</span> Volver
    </a>
    <h1 id="titulo-form">{{ isset($id) ? 'Editar médico' : 'Nuevo médico' }}</h1>
    <p class="page-subtitle">Datos del profesional de salud</p>
  </header>

  <div class="card" style="max-width:560px">
    <form id="form-medico" novalidate>
      <div class="form-group">
        <label class="form-label" for="nombres">Nombres completos</label>
        <input type="text" id="nombres" class="form-control" placeholder="Ej. Dr. Carlos Mendoza" required autocapitalize="words">
        <span class="form-error-msg" id="error-nombres" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="credencial_cmp">Credencial CMP</label>
        <input type="text" id="credencial_cmp" class="form-control" placeholder="Ej. CMP-1001" required>
        <span class="form-error-msg" id="error-credencial_cmp" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="especialidad_id">Especialidad</label>
        <select id="especialidad_id" class="form-control" required>
          <option value="">Cargando especialidades...</option>
        </select>
        <span class="form-error-msg" id="error-especialidad_id" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="telefono">Teléfono (opcional)</label>
        <input type="tel" id="telefono" class="form-control" placeholder="Ej. 0991234567">
        <span class="form-error-msg" id="error-telefono" hidden></span>
      </div>
      <div class="form-group">
        <label class="switch-label" for="disponible" style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" id="disponible" checked style="width:18px;height:18px">
          Disponible para atender turnos
        </label>
      </div>
      <div style="display:flex;gap:var(--space-sm);margin-top:var(--space-lg)">
        <a href="{{ route('medicos.index') }}" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btn-guardar">{{ isset($id) ? 'Guardar cambios' : 'Registrar médico' }}</button>
      </div>
    </form>
  </div>
@endsection

@section('scripts')
<script>
  const medicoId = @json($id ?? null);

  // Solo letras/espacios/puntos (permite "Dr.", "Lcda.", etc.), igual que valida el backend.
  document.getElementById('nombres').addEventListener('input', function () {
    this.value = this.value.replace(/[^\p{L}\s.'-]/gu, '');
  });
  // Capitaliza cada palabra al salir del campo (Ej. "carlos mendoza" -> "Carlos Mendoza").
  document.getElementById('nombres').addEventListener('blur', function () {
    this.value = this.value.replace(/\S+/g, palabra =>
      palabra.charAt(0).toUpperCase() + palabra.slice(1).toLowerCase());
  });

  // Fuerza el formato exacto CMP-0000 mientras se escribe, en vez de dejar
  // que el usuario mande cualquier cosa y recién avisarle en el servidor.
  document.getElementById('credencial_cmp').addEventListener('input', function () {
    const digitos = this.value.toUpperCase().replace(/[^0-9]/g, '').slice(0, 4);
    this.value = digitos ? `CMP-${digitos}` : '';
  });

  function limpiarErrores() {
    document.querySelectorAll('.form-error-msg').forEach(el => { el.hidden = true; el.textContent = ''; });
  }

  function mostrarErrores(errors) {
    if (!errors) return;
    Object.entries(errors).forEach(([campo, mensajes]) => {
      const el = document.getElementById(`error-${campo}`);
      if (el) { el.hidden = false; el.textContent = mensajes[0]; }
    });
  }

  async function cargarEspecialidades(seleccionActual) {
    const select = document.getElementById('especialidad_id');
    const resultado = await Api.get('/especialidades');
    if (!resultado.ok) {
      select.innerHTML = `<option value="">${resultado.message}</option>`;
      return;
    }
    const activas = resultado.data.filter(e => e.activa);
    select.innerHTML = '<option value="">Selecciona una especialidad</option>' +
      activas.map(e => `<option value="${e.id}">${e.nombre}</option>`).join('');
    if (seleccionActual) select.value = seleccionActual;
  }

  async function cargarMedico() {
    if (!medicoId) {
      cargarEspecialidades();
      return;
    }
    const resultado = await Api.get(`/medicos/${medicoId}`);
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      window.location.href = '{{ route("medicos.index") }}';
      return;
    }
    const m = resultado.data;
    document.getElementById('nombres').value = m.nombres;
    document.getElementById('credencial_cmp').value = m.credencial_cmp;
    document.getElementById('telefono').value = m.telefono ?? '';
    document.getElementById('disponible').checked = !!m.disponible;
    cargarEspecialidades(m.especialidad?.id);
  }

  document.getElementById('form-medico').addEventListener('submit', async function (e) {
    e.preventDefault();
    limpiarErrores();

    const cuerpo = {
      nombres: document.getElementById('nombres').value.trim(),
      credencial_cmp: document.getElementById('credencial_cmp').value.trim(),
      especialidad_id: parseInt(document.getElementById('especialidad_id').value, 10) || null,
      telefono: document.getElementById('telefono').value.trim() || null,
      disponible: document.getElementById('disponible').checked,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const resultado = medicoId
      ? await Api.put(`/medicos/${medicoId}`, cuerpo)
      : await Api.post('/medicos', cuerpo);

    btn.disabled = false;
    btn.textContent = medicoId ? 'Guardar cambios' : 'Registrar médico';

    if (!resultado.ok) {
      mostrarErrores(resultado.errors);
      showToast(resultado.message, 'error');
      return;
    }

    showToast(medicoId ? 'Médico actualizado correctamente.' : 'Médico registrado correctamente.');
    setTimeout(() => window.location.href = '{{ route("medicos.index") }}', 800);
  });

  cargarMedico();
</script>
@endsection
