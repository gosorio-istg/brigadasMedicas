@extends('layouts.app')

@section('titulo', isset($id) ? 'Editar usuario' : 'Nuevo usuario')

@section('content')
  <header class="module-hero">
    <div class="module-hero-copy">
      <span class="module-hero-icon"><span class="material-symbols-rounded">person_add</span></span>
      <div>
    <a href="{{ route('usuarios.index') }}" class="btn btn-ghost btn-sm" style="margin-bottom:var(--space-sm)">
      <span class="material-symbols-rounded">arrow_back</span> Volver
    </a>
    <h1>{{ isset($id) ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
    <p class="page-subtitle">Configura la identidad, credenciales y responsabilidades de acceso.</p>
      </div>
    </div>
  </header>

  <div class="form-workspace">
  <div class="form-panel">
    <form id="form-usuario" novalidate>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="usuario-nombre">Nombres</label>
          <input type="text" id="usuario-nombre" class="form-control" required>
          <span class="form-error-msg" id="error-name" hidden></span>
        </div>
        <div class="form-group">
          <label class="form-label" for="usuario-apellido">Apellidos</label>
          <input type="text" id="usuario-apellido" class="form-control" required>
          <span class="form-error-msg" id="error-apellido" hidden></span>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="usuario-cedula">Cédula</label>
        <input type="text" id="usuario-cedula" class="form-control" maxlength="10" placeholder="10 dígitos" required>
        <span class="form-error-msg" id="error-cedula" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="usuario-email">Correo electrónico</label>
        <input type="email" id="usuario-email" class="form-control" required>
        <span class="form-error-msg" id="error-email" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="usuario-password">
          {{ isset($id) ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
        </label>
        <input type="password" id="usuario-password" class="form-control" placeholder="{{ isset($id) ? 'Dejar en blanco para no cambiarla' : 'Mínimo 8 caracteres' }}" {{ isset($id) ? '' : 'required' }}>
        <span class="form-error-msg" id="error-password" hidden></span>
      </div>
      <div class="form-group">
        <span class="form-label">Roles</span>
        <div id="usuario-roles-checks" style="display:flex;flex-direction:column;gap:6px">
          <p class="page-subtitle">Cargando...</p>
        </div>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" id="usuario-activo" checked style="width:18px;height:18px">
          Cuenta activa
        </label>
      </div>
      <div class="form-actions">
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btn-guardar">{{ isset($id) ? 'Guardar cambios' : 'Crear usuario' }}</button>
      </div>
    </form>
  </div>
  <aside class="form-aside">
    <h3>Asigna solo el acceso necesario</h3>
    <p>Los roles determinan qué módulos y acciones puede utilizar cada persona.</p>
    <div class="form-aside-list">
      <div class="form-aside-item"><span class="material-symbols-rounded">badge</span><span>Comprueba cédula y correo antes de guardar.</span></div>
      <div class="form-aside-item"><span class="material-symbols-rounded">admin_panel_settings</span><span>Selecciona el rol según sus tareas reales.</span></div>
      <div class="form-aside-item"><span class="material-symbols-rounded">toggle_on</span><span>Desactivar conserva el historial sin permitir el ingreso.</span></div>
    </div>
  </aside>
  </div>
@endsection

@section('scripts')
<script>
  const usuarioId = @json($id ?? null);

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

  async function cargarRolesDisponibles(seleccionados = []) {
    const contenedor = document.getElementById('usuario-roles-checks');
    const resultado = await Api.get('/roles');
    if (!resultado.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${resultado.message}</p>`;
      return;
    }
    if (!resultado.data.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Todavía no hay roles creados.</p>';
      return;
    }
    contenedor.innerHTML = resultado.data.map(r => `
      <label style="display:flex;align-items:center;gap:8px;font-size:var(--font-size-small);cursor:pointer">
        <input type="checkbox" value="${r.name}" ${seleccionados.includes(r.name) ? 'checked' : ''}>
        ${r.name}
      </label>`).join('');
  }

  async function cargarUsuario() {
    if (!usuarioId) {
      cargarRolesDisponibles();
      return;
    }
    const resultado = await Api.get(`/users/${usuarioId}`);
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      window.location.href = '{{ route("usuarios.index") }}';
      return;
    }
    const u = resultado.data;
    document.getElementById('usuario-nombre').value = u.name;
    document.getElementById('usuario-apellido').value = u.apellido ?? '';
    document.getElementById('usuario-cedula').value = u.cedula ?? '';
    document.getElementById('usuario-email').value = u.email;
    document.getElementById('usuario-activo').checked = !!u.activo;
    cargarRolesDisponibles(u.roles ?? []);
  }

  document.getElementById('form-usuario').addEventListener('submit', async function (e) {
    e.preventDefault();
    limpiarErrores();

    const roles = [...document.querySelectorAll('#usuario-roles-checks input:checked')].map(c => c.value);
    const cuerpo = {
      name: document.getElementById('usuario-nombre').value.trim(),
      apellido: document.getElementById('usuario-apellido').value.trim(),
      cedula: document.getElementById('usuario-cedula').value.trim(),
      email: document.getElementById('usuario-email').value.trim(),
      activo: document.getElementById('usuario-activo').checked,
      roles,
    };
    const password = document.getElementById('usuario-password').value;
    if (password) cuerpo.password = password;

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const resultado = usuarioId
      ? await Api.put(`/users/${usuarioId}`, cuerpo)
      : await Api.post('/users', cuerpo);

    btn.disabled = false;
    btn.textContent = usuarioId ? 'Guardar cambios' : 'Crear usuario';

    if (!resultado.ok) {
      mostrarErrores(resultado.errors);
      showToast(resultado.message, 'error');
      return;
    }

    showToast(usuarioId ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.');
    setTimeout(() => window.location.href = '{{ route("usuarios.index") }}', 800);
  });

  cargarUsuario();
</script>
@endsection
