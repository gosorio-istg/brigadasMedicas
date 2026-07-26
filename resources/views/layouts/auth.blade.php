<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BrigadaMedica — @yield('titulo')</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>

  <main class="auth-wrapper">
    <div class="auth-card">
      @yield('content')
    </div>
  </main>

  <script src="{{ asset('js/api.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  @yield('scripts')
</body>
</html>
