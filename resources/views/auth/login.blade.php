@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('content')
  <header class="auth-header">
    <div class="auth-brand">
      <img src="{{ asset('images/logo_brigada.jpeg') }}" alt="Logo BrigadaMedica" class="auth-brand-logo">
      <span class="auth-brand-name">BrigadaMedica</span>
    </div>
    <h1 class="auth-title">Iniciar sesión</h1>
    <p class="auth-subtitle">Panel del Coordinador — Campañas médicas comunitarias</p>
  </header>

  <form id="login-form" novalidate>
    <div class="form-group" id="group-login">
      <label class="form-label" for="login">Cédula o correo electrónico</label>
      <input type="text" id="login" class="form-control" placeholder="0912345678" autocomplete="username" required>
      <span class="form-error-msg" id="error-login" hidden></span>
    </div>

    <div class="form-group" id="group-pass">
      <label class="form-label" for="password">Contraseña</label>
      <div class="input-with-icon">
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
      <button type="submit" class="btn btn-primary btn-block" id="btn-login">Iniciar sesión</button>
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
    btn.textContent = 'Ingresando...';

    const resultado = await Api.login(login, password);

    btn.disabled = false;
    btn.textContent = 'Iniciar sesión';

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
