@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('content')
  <header class="auth-header">
    <div class="auth-brand">
      <img src="{{ asset('images/logo_brigada.jpeg') }}" alt="Logo BrigadaMedica" class="auth-brand-logo">
      <span class="auth-brand-name">BrigadaMedica</span>
    </div>
    <span class="auth-welcome-label">Bienvenido de nuevo</span>
    <h1 class="auth-title">Inicia sesión en tu cuenta</h1>
    <p class="auth-subtitle">Ingresa tus credenciales para continuar al panel de gestión.</p>
  </header>

  <form id="login-form" novalidate>
    <div class="form-group" id="group-login">
      <label class="form-label" for="login">Cédula o correo electrónico</label>
      <div class="auth-input-wrap">
        <span class="material-symbols-rounded" aria-hidden="true">person</span>
        <input type="text" id="login" class="form-control" placeholder="0912345678 o nombre@correo.com" autocomplete="username" required>
      </div>
      <span class="form-error-msg" id="error-login" hidden></span>
    </div>

    <div class="form-group" id="group-pass">
      <label class="form-label" for="password">Contraseña</label>
      <div class="input-with-icon auth-input-wrap">
        <span class="material-symbols-rounded auth-input-leading" aria-hidden="true">lock</span>
        <input type="password" id="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
        <button type="button" class="material-symbols-rounded" data-toggle-password="password" aria-label="Mostrar contraseña">visibility</button>
      </div>
      <span class="form-error-msg" id="error-pass" hidden></span>
    </div>

    <div class="form-group has-error" id="group-credentials" hidden>
      <span class="form-error-msg">
        <span class="material-symbols-rounded" style="font-size:16px">error</span>
        <span id="credentials-msg"></span>
      </span>
    </div>

    <div class="auth-actions">
      <button type="submit" class="btn btn-primary btn-block auth-submit" id="btn-login">
        <span>Iniciar sesión</span>
        <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
      </button>
      <a href="{{ route('password.request') }}" class="auth-link">¿Olvidaste tu contraseña?</a>
    </div>
  </form>
@endsection

@section('scripts')
<script>
  // Si ya hay sesión guardada, no tiene sentido mostrar el login de nuevo.
  if (getToken()) window.location.href = '/dashboard';

  document.getElementById('login-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const login = document.getElementById('login').value.trim();
    const password = document.getElementById('password').value.trim();

    const groupLogin = document.getElementById('group-login');
    const groupPass = document.getElementById('group-pass');
    const groupCred = document.getElementById('group-credentials');
    groupLogin.classList.remove('has-error');
    groupPass.classList.remove('has-error');
    document.getElementById('error-login').hidden = true;
    document.getElementById('error-pass').hidden = true;
    groupCred.hidden = true;

    const btn = document.getElementById('btn-login');
    btn.disabled = true;
    btn.innerHTML = '<span>Verificando acceso...</span><span class="material-symbols-rounded auth-submit-spinner" aria-hidden="true">progress_activity</span>';

    const resultado = await Api.login(login, password);

    btn.disabled = false;
    btn.innerHTML = '<span>Iniciar sesión</span><span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>';

    if (!resultado.ok) {
      // 422: errores de validación por campo (incluye "credenciales incorrectas" bajo "login").
      if (resultado.errors?.login) {
        groupLogin.classList.add('has-error');
        document.getElementById('error-login').hidden = false;
        document.getElementById('error-login').textContent = resultado.errors.login[0];
      }
      if (resultado.errors?.password) {
        groupPass.classList.add('has-error');
        document.getElementById('error-pass').hidden = false;
        document.getElementById('error-pass').textContent = resultado.errors.password[0];
      }
      document.getElementById('credentials-msg').textContent = resultado.message;
      groupCred.hidden = false;
      return;
    }

    showToast('Sesión iniciada correctamente');
    setTimeout(() => { window.location.href = '/dashboard'; }, 500);
  });
</script>
@endsection
