@extends('layouts.app')

@section('titulo', 'Nueva campaña')

@section('content')
  <header class="page-header">
    <a href="{{ route('brigadas.index') }}" class="btn btn-ghost btn-sm" style="margin-bottom:var(--space-sm)">
      <span class="material-symbols-rounded">arrow_back</span> Volver
    </a>
    <h1>Nueva campaña</h1>
    <p class="page-subtitle">Programa una nueva campaña médica comunitaria</p>
  </header>

  <div class="card" style="max-width:640px">
    <form id="form-brigada" novalidate>
      <div class="form-group">
        <label class="form-label" for="nombre">Nombre de la campaña</label>
        <input type="text" id="nombre" class="form-control" placeholder="Ej. Jornada de salud Bastión Popular" minlength="5" required>
        <span class="form-error-msg" id="error-nombre" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="descripcion">Descripción (opcional)</label>
        <textarea id="descripcion" class="form-control" placeholder="Detalle breve de la jornada..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label" for="fecha">Fecha</label>
        <input type="date" id="fecha" class="form-control" required>
        <span class="form-error-msg" id="error-fecha" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="ubicacion">Ubicación</label>
        <input type="text" id="ubicacion" class="form-control" placeholder="Sector, referencia o dirección" minlength="5" required>
        <span class="form-error-msg" id="error-ubicacion" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label">Especialidades ofrecidas y cupos</label>
        <div id="especialidades-chips" style="display:flex;flex-wrap:wrap;gap:8px">
          <p class="page-subtitle">Cargando especialidades...</p>
        </div>
        <div id="especialidades-cupos" style="margin-top:var(--space-sm);display:flex;flex-direction:column;gap:8px"></div>
        <span class="form-error-msg" id="error-especialidades" hidden>Selecciona al menos una especialidad y define sus cupos.</span>
      </div>
      <div style="display:flex;gap:var(--space-sm);margin-top:var(--space-lg)">
        <a href="{{ route('brigadas.index') }}" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btn-crear">Crear campaña</button>
      </div>
    </form>
  </div>
@endsection

@section('scripts')
<script>
  let especialidadesCatalogo = [];
  const seleccionadas = new Map(); // id especialidad -> cupos

  // No se puede programar una campaña en el pasado ni demasiado lejos en el futuro.
  document.getElementById('fecha').min = new Date().toISOString().slice(0, 10);
  const fechaMaxima = new Date();
  fechaMaxima.setFullYear(fechaMaxima.getFullYear() + 2);
  document.getElementById('fecha').max = fechaMaxima.toISOString().slice(0, 10);

  // Mismo set de caracteres que valida el backend: letras, números, espacios y . , # -
  ['nombre', 'ubicacion'].forEach(id => {
    document.getElementById(id).addEventListener('input', function () {
      this.value = this.value.replace(/[^\p{L}\d\s.,#-]/gu, '');
    });
  });

  async function cargarEspecialidades() {
    const resultado = await Api.get('/especialidades');
    const contenedor = document.getElementById('especialidades-chips');

    if (!resultado.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resultado.message}</p>`;
      return;
    }

    especialidadesCatalogo = resultado.data.filter(e => e.activa);
    contenedor.innerHTML = especialidadesCatalogo.map(e => `
      <span class="chip ${especialidadChipClass(e.nombre)} chip-selectable" data-especialidad-id="${e.id}" data-especialidad-nombre="${e.nombre}">${e.nombre}</span>
    `).join('');

    contenedor.querySelectorAll('.chip-selectable').forEach(chip => {
      chip.addEventListener('click', () => {
        chip.classList.toggle('is-selected');
        const id = parseInt(chip.dataset.especialidadId, 10);
        if (chip.classList.contains('is-selected')) {
          seleccionadas.set(id, seleccionadas.get(id) || 10);
        } else {
          seleccionadas.delete(id);
        }
        pintarCupos();
      });
    });
  }

  function pintarCupos() {
    const contenedor = document.getElementById('especialidades-cupos');
    if (!seleccionadas.size) {
      contenedor.innerHTML = '';
      return;
    }

    contenedor.innerHTML = Array.from(seleccionadas.keys()).map(id => {
      const especialidad = especialidadesCatalogo.find(e => e.id === id);
      return `
        <div style="display:flex;align-items:center;gap:8px">
          <span style="flex:1;font-size:var(--font-size-small)">${especialidad.nombre}</span>
          <input type="number" min="1" class="form-control" style="max-width:110px" placeholder="Cupos"
                 data-cupos-para="${id}" value="${seleccionadas.get(id)}">
        </div>`;
    }).join('');

    contenedor.querySelectorAll('[data-cupos-para]').forEach(input => {
      input.addEventListener('input', () => {
        seleccionadas.set(parseInt(input.dataset.cuposPara, 10), parseInt(input.value, 10) || 1);
      });
    });
  }

  document.getElementById('form-brigada').addEventListener('submit', async function (e) {
    e.preventDefault();

    document.querySelectorAll('#form-brigada .form-error-msg').forEach(el => { el.hidden = true; });
    if (!seleccionadas.size) {
      document.getElementById('error-especialidades').hidden = false;
      return;
    }

    const btn = document.getElementById('btn-crear');
    btn.disabled = true;
    btn.textContent = 'Creando...';

    const resultado = await Api.post('/brigadas', {
      nombre: document.getElementById('nombre').value.trim(),
      descripcion: document.getElementById('descripcion').value.trim() || null,
      fecha: document.getElementById('fecha').value,
      ubicacion: document.getElementById('ubicacion').value.trim(),
      especialidades: Array.from(seleccionadas.entries()).map(([id, cupos]) => ({ id, cupos })),
    });

    btn.disabled = false;
    btn.textContent = 'Crear campaña';

    if (!resultado.ok) {
      if (resultado.errors) {
        Object.entries(resultado.errors).forEach(([campo, mensajes]) => {
          const el = document.getElementById(`error-${campo}`);
          if (el) { el.hidden = false; el.textContent = mensajes[0]; }
        });
      }
      showToast(resultado.message, 'error');
      return;
    }

    showToast('Campaña creada correctamente');
    setTimeout(() => window.location.href = '{{ route('brigadas.index') }}', 800);
  });

  cargarEspecialidades();
</script>
@endsection
