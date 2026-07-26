<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BrigadaMedica — @yield('titulo', 'Panel')</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body class="app-layout">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><span class="material-symbols-rounded">medical_services</span> BrigadaMedica</div>
    <nav class="sidebar-nav" aria-label="Navegación principal">
      <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"><span class="material-symbols-rounded">home</span> Dashboard</a>
      <a href="{{ route('brigadas.index') }}" class="sidebar-link {{ request()->routeIs('brigadas.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">groups</span> Brigadas</a>
      <a href="{{ route('pacientes.index') }}" class="sidebar-link {{ request()->routeIs('pacientes.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">personal_injury</span> Pacientes</a>
      <a href="{{ route('medicos.index') }}" class="sidebar-link {{ request()->routeIs('medicos.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">stethoscope</span> Médicos</a>
      <a href="{{ route('brigadistas.index') }}" class="sidebar-link {{ request()->routeIs('brigadistas.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">volunteer_activism</span> Brigadistas</a>
      <a href="{{ route('comunidades.index') }}" class="sidebar-link {{ request()->routeIs('comunidades.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">location_city</span> Comunidades</a>
      <a href="{{ route('reportes.index') }}" class="sidebar-link {{ request()->routeIs('reportes.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">bar_chart</span> Reportes</a>
      <a href="{{ route('noticias.index') }}" class="sidebar-link {{ request()->routeIs('noticias.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">newspaper</span> Noticias</a>
      <a href="{{ route('configuracion.index') }}" class="sidebar-link {{ request()->routeIs('configuracion.*') ? 'is-active' : '' }}"><span class="material-symbols-rounded">settings</span> Configuración</a>
    </nav>
    <div class="sidebar-footer">
      <a href="#" id="btn-logout" class="sidebar-link sidebar-link--logout"><span class="material-symbols-rounded">logout</span> Cerrar sesión</a>
    </div>
  </aside>
  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <div class="app-main">
    <header class="topbar">
      <button class="topbar-toggle icon-btn" id="sidebar-toggle" aria-label="Abrir menú"><span class="material-symbols-rounded">menu</span></button>
      <div class="topbar-search" id="global-search">
        <span class="material-symbols-rounded">search</span>
        <input type="search" id="global-search-input" placeholder="Buscar pacientes, brigadas, médicos..." aria-label="Buscar" autocomplete="off">
        <div class="global-search-results" id="global-search-results"></div>
      </div>
      <div class="topbar-actions">
        <div class="connection-status connection-status--online" id="connection-badge">
          <span class="connection-dot"></span> <span id="connection-text">Conectado</span>
        </div>
        <button class="icon-btn" aria-label="Notificaciones"><span class="material-symbols-rounded">notifications</span></button>

        <div class="user-menu" id="user-menu">
          <button class="user-menu-trigger" id="user-menu-trigger" aria-haspopup="true" aria-expanded="false" aria-label="Abrir menú de usuario">
            <div class="avatar avatar-sm" id="user-avatar">--</div>
            <span class="material-symbols-rounded user-menu-caret">expand_more</span>
          </button>

          <div class="user-menu-dropdown" id="user-menu-dropdown">
            <div class="user-menu-header">
              <div class="avatar avatar-md" id="user-menu-avatar">--</div>
              <div class="user-menu-identity">
                <strong id="user-menu-name">—</strong>
                <span id="user-menu-email">—</span>
                <div id="user-menu-roles" class="user-menu-roles"></div>
              </div>
            </div>
            <div class="user-menu-divider"></div>
            <a href="{{ route('configuracion.index') }}" class="user-menu-item">
              <span class="material-symbols-rounded">person</span> Ver mi perfil
            </a>
            <div class="user-menu-divider"></div>
            <a href="#" class="user-menu-item user-menu-item--danger" id="user-menu-logout">
              <span class="material-symbols-rounded">logout</span> Cerrar sesión
            </a>
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      @yield('content')
    </main>
  </div>

  <nav class="bottom-nav" aria-label="Navegación inferior">
    <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" aria-label="Inicio"><span class="material-symbols-rounded">home</span>Inicio</a>
    <a href="{{ route('brigadas.index') }}" class="bottom-nav-item {{ request()->routeIs('brigadas.*') ? 'is-active' : '' }}" aria-label="Brigadas"><span class="material-symbols-rounded">groups</span>Brigadas</a>
    <a href="{{ route('pacientes.index') }}" class="bottom-nav-item {{ request()->routeIs('pacientes.*') ? 'is-active' : '' }}" aria-label="Pacientes"><span class="material-symbols-rounded">personal_injury</span>Pacientes</a>
    <a href="{{ route('noticias.index') }}" class="bottom-nav-item {{ request()->routeIs('noticias.*') ? 'is-active' : '' }}" aria-label="Notificaciones"><span class="material-symbols-rounded">notifications</span>Alertas</a>
    <a href="{{ route('configuracion.index') }}" class="bottom-nav-item {{ request()->routeIs('configuracion.*') ? 'is-active' : '' }}" aria-label="Perfil"><span class="material-symbols-rounded">person</span>Perfil</a>
  </nav>

  @yield('modals')

  <script src="{{ asset('js/api.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    requireAuth();
    const __usuarioActual = getUser();
    if (__usuarioActual) {
      const iniciasUsuario = iniciales(__usuarioActual.name);
      document.getElementById('user-avatar').textContent = iniciasUsuario;
      document.getElementById('user-menu-avatar').textContent = iniciasUsuario;
      document.getElementById('user-menu-name').textContent =
        [__usuarioActual.name, __usuarioActual.apellido].filter(Boolean).join(' ');
      document.getElementById('user-menu-email').textContent = __usuarioActual.email;
      document.getElementById('user-menu-roles').innerHTML = (__usuarioActual.roles || [])
        .map(r => `<span class="chip chip-confirmado">${r}</span>`).join('');
    }

    initUserMenu();
    initGlobalSearch();

    document.getElementById('btn-logout').addEventListener('click', (e) => {
      e.preventDefault();
      Api.logout();
    });
    document.getElementById('user-menu-logout').addEventListener('click', (e) => {
      e.preventDefault();
      Api.logout();
    });
  </script>
  @yield('scripts')
</body>
</html>
