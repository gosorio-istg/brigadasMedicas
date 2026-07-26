@extends('layouts.auth')

@section('titulo', 'Selección de rol')

@section('content')
  <header class="auth-header">
    <div class="auth-brand">
      <span class="material-symbols-rounded">medical_services</span>
      <span class="auth-brand-name">BrigadaMedica</span>
    </div>
    <h1 class="auth-title">Selecciona tu rol</h1>
    <p class="auth-subtitle">Tu cuenta tiene acceso a más de un panel</p>
  </header>

  <div class="role-cards" id="role-cards">
    {{-- Se llena dinámicamente con los roles reales del usuario autenticado (ver script). --}}
  </div>
@endsection

@section('scripts')
<script>
  // Esta pantalla solo tiene sentido si el usuario tiene más de un rol asignado
  // (ej. Administrador + Coordinador). Hoy todos los roles comparten el mismo
  // panel (no hay una vista distinta por rol todavía), así que cualquier tarjeta
  // lleva a /dashboard — queda lista para el día que existan paneles separados.
  if (!requireAuth()) { /* redirige a /login */ }

  const iconosPorRol = {
    Administrador: 'admin_panel_settings',
    Coordinador: 'dashboard',
    Brigadista: 'volunteer_activism',
  };
  const descripcionesPorRol = {
    Administrador: 'Configuración general de la plataforma',
    Coordinador: 'Gestión de campañas, pacientes y reportes',
    Brigadista: 'Registro de pacientes y turnos en campo',
  };

  const usuario = getUser();
  const contenedor = document.getElementById('role-cards');

  if (usuario?.roles?.length) {
    usuario.roles.forEach(rol => {
      const tarjeta = document.createElement('a');
      tarjeta.href = '/dashboard';
      tarjeta.className = 'role-card';
      tarjeta.innerHTML = `
        <div class="role-card-icon">
          <span class="material-symbols-rounded">${iconosPorRol[rol] || 'badge'}</span>
        </div>
        <div>
          <strong>${rol}</strong>
          <p style="font-size:var(--font-size-small);color:var(--color-text-muted)">${descripcionesPorRol[rol] || ''}</p>
        </div>`;
      contenedor.appendChild(tarjeta);
    });
  } else {
    window.location.href = '/dashboard';
  }
</script>
@endsection
