@extends('layouts.app')

@section('titulo', 'Configuración')

@section('content')
  <header class="page-header">
    <h1>Configuración</h1>
    <p class="page-subtitle">Tu cuenta, catálogo de especialidades y preferencias de notificación</p>
  </header>

  <section class="section-block">
    <h2 class="section-title">Mi cuenta</h2>
    <div class="card" style="max-width:560px">
      <form id="form-cuenta">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="cuenta-nombre">Nombre</label>
            <input type="text" id="cuenta-nombre" class="form-control" required>
            <span class="form-error-msg" id="error-cuenta-name" hidden></span>
          </div>
          <div class="form-group">
            <label class="form-label" for="cuenta-apellido">Apellido</label>
            <input type="text" id="cuenta-apellido" class="form-control" required>
            <span class="form-error-msg" id="error-cuenta-apellido" hidden></span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="cuenta-cedula">Cédula</label>
          <input type="text" id="cuenta-cedula" class="form-control" maxlength="10" placeholder="10 dígitos" required>
          <span class="form-error-msg" id="error-cuenta-cedula" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label" for="cuenta-email">Correo electrónico</label>
          <input type="email" id="cuenta-email" class="form-control" required>
          <span class="form-error-msg" id="error-cuenta-email" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label" for="cuenta-password">Nueva contraseña (opcional)</label>
          <input type="password" id="cuenta-password" class="form-control" placeholder="Dejar en blanco para no cambiarla">
          <span class="form-error-msg" id="error-cuenta-password" hidden></span>
        </div>
        <div class="form-group">
          <span class="form-label">Roles asignados</span>
          <div id="cuenta-roles" style="display:flex;gap:6px;flex-wrap:wrap"></div>
        </div>
        <button type="submit" class="btn btn-primary" id="btn-guardar-cuenta" style="margin-top:var(--space-sm)">Guardar cambios</button>
      </form>
    </div>
  </section>

  <section class="section-block" id="seccion-especialidades" style="display:none">
    <h2 class="section-title">Catálogo de especialidades</h2>
    <div class="card" style="max-width:560px">
      <form id="form-especialidad" style="display:flex;gap:8px;margin-bottom:var(--space-md)">
        <input type="text" id="nueva-especialidad" class="form-control" placeholder="Ej. Oftalmología">
        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">Agregar</button>
      </form>
      <div id="lista-especialidades" style="display:flex;flex-direction:column;gap:8px">
        <p class="page-subtitle">Cargando...</p>
      </div>
    </div>
  </section>

  <section class="section-block">
    <h2 class="section-title">Preferencias de notificación</h2>
    <div class="card" style="max-width:560px">
      <div class="detail-row">
        <span>Notificaciones por correo electrónico</span>
        <label style="cursor:pointer">
          <input type="checkbox" id="pref-email" style="width:18px;height:18px">
        </label>
      </div>
      <div class="detail-row">
        <span>Notificaciones push</span>
        <label style="cursor:pointer">
          <input type="checkbox" id="pref-push" style="width:18px;height:18px">
        </label>
      </div>
    </div>
  </section>
@endsection

@section('scripts')
<script>
  const usuarioActual = getUser();
  const puedeGestionarEspecialidades = hasPermission('brigadas.gestionar');

  function cargarCuenta() {
    document.getElementById('cuenta-nombre').value = usuarioActual?.name ?? '';
    document.getElementById('cuenta-apellido').value = usuarioActual?.apellido ?? '';
    document.getElementById('cuenta-cedula').value = usuarioActual?.cedula ?? '';
    document.getElementById('cuenta-email').value = usuarioActual?.email ?? '';
    document.getElementById('cuenta-roles').innerHTML = (usuarioActual?.roles || [])
      .map(r => `<span class="chip chip-confirmado">${r}</span>`).join('') || '<span class="page-subtitle">Sin roles asignados</span>';
  }

  document.getElementById('form-cuenta').addEventListener('submit', async function (e) {
    e.preventDefault();
    document.querySelectorAll('#form-cuenta .form-error-msg').forEach(el => { el.hidden = true; el.textContent = ''; });

    const cuerpo = {
      name: document.getElementById('cuenta-nombre').value.trim(),
      apellido: document.getElementById('cuenta-apellido').value.trim(),
      cedula: document.getElementById('cuenta-cedula').value.trim(),
      email: document.getElementById('cuenta-email').value.trim(),
    };
    const password = document.getElementById('cuenta-password').value;
    if (password) cuerpo.password = password;

    const btn = document.getElementById('btn-guardar-cuenta');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const resultado = await Api.put('/me', cuerpo);

    btn.disabled = false;
    btn.textContent = 'Guardar cambios';

    if (!resultado.ok) {
      if (resultado.errors) {
        Object.entries(resultado.errors).forEach(([campo, mensajes]) => {
          const el = document.getElementById(`error-cuenta-${campo}`);
          if (el) { el.hidden = false; el.textContent = mensajes[0]; }
        });
      }
      showToast(resultado.message, 'error');
      return;
    }

    setSession(getToken(), resultado.data);
    document.getElementById('cuenta-password').value = '';
    showToast('Datos actualizados correctamente.');
  });

  async function cargarEspecialidades() {
    if (!puedeGestionarEspecialidades) return;
    document.getElementById('seccion-especialidades').style.display = 'block';

    const contenedor = document.getElementById('lista-especialidades');
    const resultado = await Api.get('/especialidades');
    if (!resultado.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resultado.message}</p>`;
      return;
    }

    if (!resultado.data.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Todavía no hay especialidades registradas.</p>';
      return;
    }

    contenedor.innerHTML = resultado.data.map(e => `
      <div class="detail-row">
        <span class="chip ${especialidadChipClass(e.nombre)}">${e.nombre}</span>
        <div style="display:flex;align-items:center;gap:10px">
          <span class="page-subtitle">${e.activa ? 'Activa' : 'Inactiva'}</span>
          <button type="button" class="btn btn-outline btn-sm" data-eliminar-especialidad="${e.id}">Eliminar</button>
        </div>
      </div>`).join('');

    contenedor.querySelectorAll('[data-eliminar-especialidad]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const resultado = await Api.delete(`/especialidades/${btn.dataset.eliminarEspecialidad}`);
        if (!resultado.ok) {
          showToast(resultado.message, 'error');
          return;
        }
        showToast('Especialidad eliminada correctamente.');
        cargarEspecialidades();
      });
    });
  }

  document.getElementById('form-especialidad').addEventListener('submit', async function (e) {
    e.preventDefault();
    const input = document.getElementById('nueva-especialidad');
    const nombre = input.value.trim();
    if (!nombre) return;

    const resultado = await Api.post('/especialidades', { nombre, activa: true });
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }

    input.value = '';
    showToast('Especialidad agregada correctamente.');
    cargarEspecialidades();
  });

  async function cargarPreferencias() {
    const resultado = await Api.get('/preferencias');
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    document.getElementById('pref-email').checked = !!resultado.data.notificaciones_email;
    document.getElementById('pref-push').checked = !!resultado.data.notificaciones_push;
  }

  async function guardarPreferencias() {
    const resultado = await Api.put('/preferencias', {
      notificaciones_email: document.getElementById('pref-email').checked,
      notificaciones_push: document.getElementById('pref-push').checked,
    });
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Preferencias actualizadas.');
  }

  document.getElementById('pref-email').addEventListener('change', guardarPreferencias);
  document.getElementById('pref-push').addEventListener('change', guardarPreferencias);

  cargarCuenta();
  cargarEspecialidades();
  cargarPreferencias();
</script>
@endsection
