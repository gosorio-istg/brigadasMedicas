<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BrigadaMedica — @yield('titulo')</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body class="auth-page">
  <main class="auth-wrapper">
    {{-- El bloque institucional acompaña todas las pantallas de acceso y comunica
         el propósito del sistema antes de pedir credenciales al usuario. --}}
    <section class="auth-hero" aria-label="Presentación de BrigadaMedica">
      <div class="auth-hero-decoration auth-hero-decoration--one" aria-hidden="true"></div>
      <div class="auth-hero-decoration auth-hero-decoration--two" aria-hidden="true"></div>

      <div class="auth-hero-content">
        <div class="auth-hero-brand">
          <img src="{{ asset('images/logo_brigada.jpeg') }}" alt="" class="auth-hero-logo">
          <span>BrigadaMedica</span>
        </div>

        <div class="auth-hero-message">
          <span class="auth-eyebrow">Gestión médica comunitaria</span>
          <h2>Coordina cada brigada con claridad, rapidez y mejor atención.</h2>
          <p>Planifica campañas, organiza al equipo médico y acompaña cada turno desde el registro del paciente hasta su atención.</p>

          <div class="auth-benefits" aria-label="Beneficios de la plataforma">
            <span><span class="material-symbols-rounded">calendar_month</span> Planifica campañas</span>
            <span><span class="material-symbols-rounded">groups</span> Coordina equipos</span>
            <span><span class="material-symbols-rounded">clinical_notes</span> Controla la atención</span>
          </div>
        </div>

        <div class="auth-hero-bottom">
          <p class="auth-hero-footer">Tecnología al servicio de una atención médica más humana.</p>

          {{-- Se muestra únicamente cuando el Administrador publicó una URL Android.
               En escritorio se escanea el QR; en móvil el mismo enlace funciona como botón. --}}
          <aside class="auth-download" id="auth-download" hidden>
            <a href="#" class="auth-download-qr" id="auth-download-link" target="_blank" rel="noopener noreferrer" aria-label="Descargar BrigadaMedica para Android">
              <img src="" alt="Código QR para descargar BrigadaMedica en Android" id="auth-download-qr">
            </a>
            <div class="auth-download-copy">
              <span class="auth-download-platform"><span class="material-symbols-rounded">android</span> Disponible para Android</span>
              <strong>Lleva la brigada contigo</strong>
              <span>Escanea el código para instalar la aplicación.</span>
              <a href="#" id="auth-download-button" target="_blank" rel="noopener noreferrer">Descargar APK <span class="material-symbols-rounded">download</span></a>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        @yield('content')
      </div>
      <p class="auth-panel-footer">Acceso exclusivo para personal autorizado</p>
    </section>
  </main>

  <script src="{{ asset('js/api.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    // El login no tiene token todavía, por eso consume únicamente el endpoint público
    // que expone la URL Android y la ruta del QR, sin revelar otras configuraciones.
    (async function cargarDescargaAndroid() {
      const resultado = await Api.get('/public/configuracion');
      const urlDescarga = resultado.data?.apk_android_download_url || resultado.data?.apk_android_url;
      if (!resultado.ok || !urlDescarga || !resultado.data?.qr_android_url) return;

      const bloque = document.getElementById('auth-download');
      const enlaceQr = document.getElementById('auth-download-link');
      const enlaceBoton = document.getElementById('auth-download-button');
      const imagenQr = document.getElementById('auth-download-qr');

      enlaceQr.href = urlDescarga;
      enlaceBoton.href = urlDescarga;
      imagenQr.src = resultado.data.qr_android_url;
      bloque.hidden = false;
    })();
  </script>
  @yield('scripts')
</body>
</html>
