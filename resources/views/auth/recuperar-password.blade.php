@extends('layouts.auth')

@section('titulo', 'Recuperar contraseña')

@section('content')
  <header class="auth-header">
    <div class="auth-brand">
      <span class="material-symbols-rounded">medical_services</span>
      <span class="auth-brand-name">BrigadaMedica</span>
    </div>
    <h1 class="auth-title">Recuperar contraseña</h1>
    <p class="auth-subtitle">Te enviaremos un enlace para restablecer tu acceso</p>
  </header>

  <form id="recover-form" novalidate>
    <div class="form-group" id="group-email">
      <label class="form-label" for="email">Correo electrónico</label>
      <input type="email" id="email" class="form-control" placeholder="tu@correo.com" required>
      <span class="form-error-msg" id="error-email" hidden>Ingresa un correo válido.</span>
    </div>

    <div class="auth-actions">
      <button type="submit" class="btn btn-primary btn-block">Enviar enlace de recuperación</button>
      <a href="{{ route('login') }}" class="auth-link">Volver al inicio de sesión</a>
    </div>
  </form>
@endsection

@section('scripts')
<script>
  // Nota: el backend todavía no tiene un endpoint de recuperación de contraseña
  // (POST /forgot-password + envío de correo). Por ahora se avisa con honestidad
  // en vez de simular un envío que nunca ocurre.
  document.getElementById('recover-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const group = document.getElementById('group-email');

    if (!email || !email.includes('@')) {
      group.classList.add('has-error');
      document.getElementById('error-email').hidden = false;
      return;
    }
    group.classList.remove('has-error');
    document.getElementById('error-email').hidden = true;

    showToast('La recuperación de contraseña todavía no está disponible. Contacta al administrador.', 'error');
  });
</script>
@endsection
