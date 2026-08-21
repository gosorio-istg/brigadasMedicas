@extends('layouts.app')

@section('titulo', isset($id) ? 'Editar noticia' : 'Nueva noticia')

@section('content')
  <header class="module-hero">
    <div class="module-hero-copy">
      <span class="module-hero-icon"><span class="material-symbols-rounded">edit_note</span></span>
      <div>
    <a href="{{ route('noticias.index') }}" class="btn btn-ghost btn-sm" style="margin-bottom:var(--space-sm)">
      <span class="material-symbols-rounded">arrow_back</span> Volver
    </a>
    <h1>{{ isset($id) ? 'Editar noticia' : 'Nueva noticia' }}</h1>
    <p class="page-subtitle">Redacta un comunicado claro y decide si se publica ahora o queda como borrador.</p>
      </div>
    </div>
  </header>

  <div class="form-workspace">
  <div class="form-panel">
    <form id="form-noticia" novalidate>
      <div class="form-group">
        <label class="form-label" for="titulo">Título</label>
        <input type="text" id="titulo" class="form-control" placeholder="Ej. Nueva jornada de vacunación en Bastión Popular" required>
        <span class="form-error-msg" id="error-titulo" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="resumen">Resumen breve</label>
        <input type="text" id="resumen" class="form-control" placeholder="Se muestra en las tarjetas de noticias" required>
        <span class="form-error-msg" id="error-resumen" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="contenido">Contenido completo</label>
        <textarea id="contenido" class="form-control" rows="6" placeholder="Redacta el comunicado completo..." required></textarea>
        <span class="form-error-msg" id="error-contenido" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="imagen_url">URL de imagen (opcional)</label>
        <input type="url" id="imagen_url" class="form-control" placeholder="https://...">
        <span class="form-error-msg" id="error-imagen_url" hidden></span>
      </div>
      <div class="form-group">
        <label class="form-label" for="fecha_publicacion">Fecha de publicación</label>
        <input type="date" id="fecha_publicacion" class="form-control" required>
        <span class="form-error-msg" id="error-fecha_publicacion" hidden></span>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" id="publicada" style="width:18px;height:18px">
          Publicar de inmediato
        </label>
      </div>
      <div class="form-actions">
        <a href="{{ route('noticias.index') }}" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btn-guardar">{{ isset($id) ? 'Guardar cambios' : 'Publicar noticia' }}</button>
      </div>
    </form>
  </div>
  <aside class="form-aside">
    <h3>Un mensaje fácil de entender</h3>
    <p>La comunidad debe reconocer rápidamente qué ocurrirá, dónde y cuándo.</p>
    <div class="form-aside-list">
      <div class="form-aside-item"><span class="material-symbols-rounded">title</span><span>Usa un título corto y específico.</span></div>
      <div class="form-aside-item"><span class="material-symbols-rounded">image</span><span>La imagen es opcional, pero debe provenir de una URL segura.</span></div>
      <div class="form-aside-item"><span class="material-symbols-rounded">draft</span><span>Desmarca “Publicar” si todavía necesitas revisar el texto.</span></div>
    </div>
  </aside>
  </div>
@endsection

@section('scripts')
<script>
  const noticiaId = @json($id ?? null);

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

  async function cargarNoticia() {
    if (!noticiaId) {
      document.getElementById('fecha_publicacion').value = new Date().toISOString().slice(0, 10);
      return;
    }
    const resultado = await Api.get(`/noticias/${noticiaId}`);
    if (!resultado.ok) {
      showToast(resultado.message, 'error');
      window.location.href = '{{ route("noticias.index") }}';
      return;
    }
    const n = resultado.data;
    document.getElementById('titulo').value = n.titulo;
    document.getElementById('resumen').value = n.resumen;
    document.getElementById('contenido').value = n.contenido;
    document.getElementById('imagen_url').value = n.imagen_url ?? '';
    document.getElementById('fecha_publicacion').value = n.fecha_publicacion;
    document.getElementById('publicada').checked = !!n.publicada;
  }

  document.getElementById('form-noticia').addEventListener('submit', async function (e) {
    e.preventDefault();
    limpiarErrores();

    const cuerpo = {
      titulo: document.getElementById('titulo').value.trim(),
      resumen: document.getElementById('resumen').value.trim(),
      contenido: document.getElementById('contenido').value.trim(),
      imagen_url: document.getElementById('imagen_url').value.trim() || null,
      fecha_publicacion: document.getElementById('fecha_publicacion').value,
      publicada: document.getElementById('publicada').checked,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const resultado = noticiaId
      ? await Api.put(`/noticias/${noticiaId}`, cuerpo)
      : await Api.post('/noticias', cuerpo);

    btn.disabled = false;
    btn.textContent = noticiaId ? 'Guardar cambios' : 'Publicar noticia';

    if (!resultado.ok) {
      mostrarErrores(resultado.errors);
      showToast(resultado.message, 'error');
      return;
    }

    showToast(noticiaId ? 'Noticia actualizada correctamente.' : 'Noticia creada correctamente.');
    setTimeout(() => window.location.href = '{{ route("noticias.index") }}', 800);
  });

  cargarNoticia();
</script>
@endsection
