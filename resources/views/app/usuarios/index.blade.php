@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('content')
  <header class="module-hero">
    <div class="module-hero-copy">
      <span class="module-hero-icon"><span class="material-symbols-rounded">manage_accounts</span></span>
      <div>
        <span class="module-eyebrow">Acceso y seguridad</span>
        <h1>Usuarios</h1>
        <p class="page-subtitle">Gestiona cuentas, estados de acceso y permisos según la responsabilidad de cada persona.</p>
      </div>
    </div>
    <a href="{{ route('usuarios.crear') }}" class="btn btn-primary module-hero-actions" id="btn-nuevo-usuario-hero">
      <span class="material-symbols-rounded">person_add</span> Nuevo usuario
    </a>
  </header>

  <nav class="filter-bar" aria-label="Secciones">
    <button class="filter-chip is-active" data-tab="usuarios">Usuarios</button>
    <button class="filter-chip" data-tab="roles">Roles y permisos</button>
  </nav>

  <section id="panel-usuarios">
    <div class="module-toolbar">
      <div class="search-input-wrap"><span class="material-symbols-rounded">search</span><input type="search" class="form-control" placeholder="Buscar por nombre, cédula, correo o rol..." aria-label="Buscar usuarios" id="search-usuarios"></div>
      <a href="{{ route('usuarios.crear') }}" class="btn btn-primary module-toolbar-mobile-action" id="btn-nuevo-usuario"><span class="material-symbols-rounded">person_add</span> Crear usuario</a>
    </div>

    <div class="data-panel">
      <div class="data-panel-header"><div><div class="data-panel-title">Directorio de accesos</div><div class="data-panel-caption">Desactiva temporalmente una cuenta sin eliminar su historial.</div></div></div>
      <div class="table-responsive">
      <table class="data-table" id="tabla-usuarios">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Cédula</th>
            <th>Correo</th>
            <th>Roles</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-usuarios-body">
          <tr><td colspan="6">Cargando...</td></tr>
        </tbody>
      </table>
      </div>

      <div class="pagination" id="paginacion-usuarios">
      <span id="paginacion-usuarios-texto"></span>
      <div class="pagination-btns">
        <button class="btn btn-outline btn-sm" id="btn-usuarios-anterior" disabled>Anterior</button>
        <button class="btn btn-outline btn-sm" id="btn-usuarios-siguiente" disabled>Siguiente</button>
      </div>
      </div>
    </div>
  </section>

  <section id="panel-roles" style="display:none">
    <div class="flex-between" style="margin-bottom:var(--space-sm)">
      <h2 style="font-size:var(--font-size-body)">Roles</h2>
      <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-rol">
        <span class="material-symbols-rounded">add</span> Nuevo rol
      </button>
    </div>

    <div id="lista-roles" style="display:flex;flex-direction:column;gap:var(--space-sm);margin-bottom:var(--space-xl)">
      <p class="page-subtitle">Cargando...</p>
    </div>

    <h2 style="font-size:var(--font-size-body);margin-bottom:var(--space-sm)">Catálogo de permisos</h2>
    <div class="card" style="max-width:560px">
      <form id="form-permiso" style="display:flex;gap:8px;margin-bottom:var(--space-md)">
        <input type="text" id="nuevo-permiso" class="form-control" placeholder="Ej. reportes.exportar">
        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">Agregar</button>
      </form>
      <div id="lista-permisos" style="display:flex;flex-wrap:wrap;gap:8px">
        <p class="page-subtitle">Cargando...</p>
      </div>
    </div>
  </section>
@endsection

@section('modals')
  <div class="modal-overlay" id="modal-eliminar-usuario">
    <div class="modal" role="dialog">
      <h2 class="modal-title">Eliminar usuario</h2>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar a <strong id="eliminar-usuario-nombre"></strong>? Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
        <button type="button" class="btn btn-primary" style="background:var(--color-error)" id="btn-confirmar-eliminar-usuario">Eliminar</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="modal-rol">
    <div class="modal" role="dialog" style="max-width:480px">
      <h2 class="modal-title" id="modal-rol-title">Nuevo rol</h2>
      <form id="form-rol">
        <div class="form-group">
          <label class="form-label" for="rol-nombre">Nombre del rol</label>
          <input type="text" id="rol-nombre" class="form-control" placeholder="Ej. Supervisor" required>
          <span class="form-error-msg" id="error-rol-nombre" hidden></span>
        </div>
        <div class="form-group">
          <span class="form-label">Permisos</span>
          <div id="rol-permisos-checks" style="display:flex;flex-direction:column;gap:6px;max-height:280px;overflow-y:auto">
            <p class="page-subtitle">Cargando...</p>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-guardar-rol">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="modal-eliminar-rol">
    <div class="modal" role="dialog">
      <h2 class="modal-title">Eliminar rol</h2>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar el rol <strong id="eliminar-rol-nombre"></strong>? Los usuarios que lo tengan asignado lo perderán.</p>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
        <button type="button" class="btn btn-primary" style="background:var(--color-error)" id="btn-confirmar-eliminar-rol">Eliminar</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  // --- Tabs ---
  document.querySelectorAll('.filter-bar .filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.filter-bar .filter-chip').forEach(c => c.classList.remove('is-active'));
      chip.classList.add('is-active');
      const tab = chip.dataset.tab;
      document.getElementById('panel-usuarios').style.display = tab === 'usuarios' ? 'block' : 'none';
      document.getElementById('panel-roles').style.display = tab === 'roles' ? 'block' : 'none';
      // Al volver a Usuarios se retira el estilo inline para que CSS decida cuál
      // acción mostrar: cabecera en escritorio y barra de trabajo en móvil.
      document.getElementById('btn-nuevo-usuario').style.display = tab === 'usuarios' ? '' : 'none';
      document.getElementById('btn-nuevo-usuario-hero').style.display = tab === 'usuarios' ? '' : 'none';
      if (tab === 'roles' && !rolesCargados) cargarRoles();
    });
  });

  // --- Usuarios ---
  let paginaActual = 1;
  let usuarioAEliminar = null;

  async function cargarUsuarios(pagina = 1) {
    paginaActual = pagina;
    const cuerpo = document.getElementById('tabla-usuarios-body');
    cuerpo.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';

    const resultado = await Api.get(`/users?page=${pagina}`);
    if (!resultado.ok) {
      cuerpo.innerHTML = `<tr><td colspan="6">${resultado.message}</td></tr>`;
      return;
    }

    if (!resultado.data.length) {
      cuerpo.innerHTML = '<tr><td colspan="6">No hay usuarios registrados todavía.</td></tr>';
      return;
    }

    cuerpo.innerHTML = resultado.data.map(u => `
      <tr>
        <td data-label="Nombre"><strong>${u.name} ${u.apellido ?? ''}</strong></td>
        <td data-label="Cédula">${u.cedula ?? '—'}</td>
        <td data-label="Correo">${u.email}</td>
        <td data-label="Roles">${(u.roles || []).map(r => `<span class="chip chip-confirmado">${r}</span>`).join(' ') || '—'}</td>
        <td data-label="Estado">
          <button type="button" class="chip ${u.activo ? 'chip-atendido' : 'chip-espera'}" style="border:none;cursor:pointer" data-toggle-activo="${u.id}" data-activo-actual="${u.activo ? '1' : '0'}">
            ${u.activo ? 'Activo' : 'Inactivo'}
          </button>
        </td>
        <td data-label="Acciones">
          <div style="display:flex;gap:8px">
            <a href="/usuarios/${u.id}/editar" class="btn btn-outline btn-sm">Editar</a>
            <button type="button" class="btn btn-outline btn-sm" data-eliminar-usuario="${u.id}" data-eliminar-nombre="${u.name} ${u.apellido ?? ''}">Eliminar</button>
          </div>
        </td>
      </tr>`).join('');

    cuerpo.querySelectorAll('[data-toggle-activo]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const activo = btn.dataset.activoActual !== '1';
        const resultado = await Api.put(`/users/${btn.dataset.toggleActivo}`, { activo });
        if (!resultado.ok) {
          showToast(resultado.message, 'error');
          return;
        }
        showToast(activo ? 'Usuario activado.' : 'Usuario desactivado.');
        cargarUsuarios(paginaActual);
      });
    });

    cuerpo.querySelectorAll('[data-eliminar-usuario]').forEach(btn => {
      btn.addEventListener('click', () => {
        usuarioAEliminar = parseInt(btn.dataset.eliminarUsuario, 10);
        document.getElementById('eliminar-usuario-nombre').textContent = btn.dataset.eliminarNombre;
        openModal('modal-eliminar-usuario');
      });
    });

    pintarPaginacionUsuarios(resultado.meta);
  }

  function pintarPaginacionUsuarios(meta) {
    if (!meta) return;
    document.getElementById('paginacion-usuarios-texto').textContent =
      `Mostrando ${meta.from ?? 0}–${meta.to ?? 0} de ${meta.total} usuarios`;
    document.getElementById('btn-usuarios-anterior').disabled = meta.current_page <= 1;
    document.getElementById('btn-usuarios-siguiente').disabled = meta.current_page >= meta.last_page;
  }

  document.getElementById('btn-usuarios-anterior').addEventListener('click', () => cargarUsuarios(paginaActual - 1));
  document.getElementById('btn-usuarios-siguiente').addEventListener('click', () => cargarUsuarios(paginaActual + 1));

  document.getElementById('btn-confirmar-eliminar-usuario').addEventListener('click', async () => {
    if (!usuarioAEliminar) return;
    const resultado = await Api.delete(`/users/${usuarioAEliminar}`);
    closeModal('modal-eliminar-usuario');
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Usuario eliminado correctamente.');
    cargarUsuarios(paginaActual);
  });

  initTableSearch('#tabla-usuarios', '#search-usuarios');

  // --- Roles y permisos ---
  let rolesCargados = false;
  let catalogoPermisos = [];
  let rolEditando = null;
  let rolAEliminar = null;

  async function cargarRoles() {
    rolesCargados = true;
    const contenedor = document.getElementById('lista-roles');
    contenedor.innerHTML = '<p class="page-subtitle">Cargando...</p>';

    const [rolesRes, permisosRes] = await Promise.all([
      Api.get('/roles'),
      Api.get('/permissions'),
    ]);

    if (!permisosRes.ok) {
      showToast(permisosRes.message, 'error');
    } else {
      catalogoPermisos = permisosRes.data;
      pintarCatalogoPermisos();
    }

    if (!rolesRes.ok) {
      contenedor.innerHTML = `<p class="page-subtitle">${rolesRes.message}</p>`;
      return;
    }

    if (!rolesRes.data.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Todavía no hay roles registrados.</p>';
      return;
    }

    contenedor.innerHTML = rolesRes.data.map(r => `
      <div class="card">
        <div class="flex-between">
          <strong>${r.name}</strong>
          <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-outline btn-sm" data-editar-rol='${JSON.stringify(r)}'>Editar</button>
            <button type="button" class="btn btn-outline btn-sm" data-eliminar-rol="${r.id}" data-eliminar-rol-nombre="${r.name}">Eliminar</button>
          </div>
        </div>
        <p class="page-subtitle" style="margin-top:8px">
          ${(r.permissions || []).length} permiso${(r.permissions || []).length === 1 ? '' : 's'}:
          ${(r.permissions || []).join(', ') || 'ninguno'}
        </p>
      </div>`).join('');

    contenedor.querySelectorAll('[data-editar-rol]').forEach(btn => {
      btn.addEventListener('click', () => abrirModalRol(JSON.parse(btn.dataset.editarRol)));
    });
    contenedor.querySelectorAll('[data-eliminar-rol]').forEach(btn => {
      btn.addEventListener('click', () => {
        rolAEliminar = parseInt(btn.dataset.eliminarRol, 10);
        document.getElementById('eliminar-rol-nombre').textContent = btn.dataset.eliminarRolNombre;
        openModal('modal-eliminar-rol');
      });
    });
  }

  function pintarChecksPermisos(seleccionados = []) {
    const contenedor = document.getElementById('rol-permisos-checks');
    if (!catalogoPermisos.length) {
      contenedor.innerHTML = '<p class="page-subtitle">No hay permisos en el catálogo.</p>';
      return;
    }
    contenedor.innerHTML = catalogoPermisos.map(p => `
      <label style="display:flex;align-items:center;gap:8px;font-size:var(--font-size-small);cursor:pointer">
        <input type="checkbox" value="${p.name}" ${seleccionados.includes(p.name) ? 'checked' : ''}>
        ${p.name}
      </label>`).join('');
  }

  function abrirModalRol(rol = null) {
    rolEditando = rol?.id ?? null;
    document.getElementById('modal-rol-title').textContent = rol ? 'Editar rol' : 'Nuevo rol';
    document.getElementById('rol-nombre').value = rol?.name ?? '';
    document.getElementById('error-rol-nombre').hidden = true;
    pintarChecksPermisos(rol?.permissions ?? []);
    openModal('modal-rol');
  }

  document.getElementById('btn-nuevo-rol').addEventListener('click', () => abrirModalRol());

  document.getElementById('form-rol').addEventListener('submit', async function (e) {
    e.preventDefault();
    document.getElementById('error-rol-nombre').hidden = true;

    const permisosSeleccionados = [...document.querySelectorAll('#rol-permisos-checks input:checked')].map(c => c.value);
    const cuerpo = {
      name: document.getElementById('rol-nombre').value.trim(),
      permissions: permisosSeleccionados,
    };

    const btn = document.getElementById('btn-guardar-rol');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const resultado = rolEditando
      ? await Api.put(`/roles/${rolEditando}`, cuerpo)
      : await Api.post('/roles', cuerpo);

    btn.disabled = false;
    btn.textContent = 'Guardar';

    if (!resultado.ok) {
      if (resultado.errors?.name) {
        document.getElementById('error-rol-nombre').hidden = false;
        document.getElementById('error-rol-nombre').textContent = resultado.errors.name[0];
      }
      showToast(resultado.message, 'error');
      return;
    }

    closeModal('modal-rol');
    showToast(rolEditando ? 'Rol actualizado correctamente.' : 'Rol creado correctamente.');
    cargarRoles();
  });

  document.getElementById('btn-confirmar-eliminar-rol').addEventListener('click', async () => {
    if (!rolAEliminar) return;
    const resultado = await Api.delete(`/roles/${rolAEliminar}`);
    closeModal('modal-eliminar-rol');
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }
    showToast('Rol eliminado correctamente.');
    cargarRoles();
  });

  function pintarCatalogoPermisos() {
    const contenedor = document.getElementById('lista-permisos');
    if (!catalogoPermisos.length) {
      contenedor.innerHTML = '<p class="page-subtitle">Todavía no hay permisos registrados.</p>';
      return;
    }
    contenedor.innerHTML = catalogoPermisos.map(p => `
      <span class="chip chip-confirmado" style="display:inline-flex;align-items:center;gap:6px">
        ${p.name}
        <button type="button" data-eliminar-permiso="${p.id}" aria-label="Eliminar permiso" style="background:none;border:none;cursor:pointer;color:inherit;display:flex">
          <span class="material-symbols-rounded" style="font-size:14px">close</span>
        </button>
      </span>`).join('');

    contenedor.querySelectorAll('[data-eliminar-permiso]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const resultado = await Api.delete(`/permissions/${btn.dataset.eliminarPermiso}`);
        if (!resultado.ok) {
          showToast(resultado.message, 'error');
          return;
        }
        showToast('Permiso eliminado correctamente.');
        cargarRoles();
      });
    });
  }

  document.getElementById('form-permiso').addEventListener('submit', async function (e) {
    e.preventDefault();
    const input = document.getElementById('nuevo-permiso');
    const nombre = input.value.trim();
    if (!nombre) return;

    const resultado = await Api.post('/permissions', { name: nombre });
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      return;
    }

    input.value = '';
    showToast('Permiso agregado correctamente.');
    cargarRoles();
  });

  cargarUsuarios();
</script>
@endsection
