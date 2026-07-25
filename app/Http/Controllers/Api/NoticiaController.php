<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoticiaRequest;
use App\Http\Requests\UpdateNoticiaRequest;
use App\Http\Resources\NoticiaResource;
use App\Models\Noticia;
use Illuminate\Support\Facades\Auth;

class NoticiaController extends Controller
{
    public function index()
    {
        $noticias = Noticia::with('autor')
            ->orderByDesc('fecha_publicacion')
            ->paginate(15);

        return NoticiaResource::collection($noticias);
    }

    public function store(StoreNoticiaRequest $request)
    {
        $data = $request->validated();

        $noticia = Noticia::create([
            'titulo' => $data['titulo'],
            'resumen' => $data['resumen'],
            'contenido' => $data['contenido'],
            'imagen_url' => $data['imagen_url'] ?? null,
            'fecha_publicacion' => $data['fecha_publicacion'],
            'publicada' => $data['publicada'] ?? false,
            'autor_id' => Auth::id(),
        ]);

        return new NoticiaResource($noticia->load('autor'));
    }

    public function show(Noticia $noticia)
    {
        return new NoticiaResource($noticia->load('autor'));
    }

    public function update(UpdateNoticiaRequest $request, Noticia $noticia)
    {
        $noticia->update($request->validated());

        return new NoticiaResource($noticia->load('autor'));
    }

    public function destroy(Noticia $noticia)
    {
        $noticia->delete();

        return response()->json(['message' => 'Noticia eliminada correctamente.']);
    }
}
