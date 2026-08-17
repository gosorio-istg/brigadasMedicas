<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrigadaResource;
use App\Http\Resources\NoticiaResource;
use App\Models\Brigada;
use App\Models\Noticia;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    public function brigadas(Request $request)
    {
        return BrigadaResource::collection(
            Brigada::with(['coordinador', 'especialidades'])
                ->whereIn('estado', ['programada', 'en_curso'])
                // La app y la web muestran primero la campaña cuya fecha de ejecución es más reciente.
                ->orderByDesc('fecha')->paginate($this->perPage($request, 20))
        );
    }

    public function noticias(Request $request)
    {
        return NoticiaResource::collection(
            Noticia::with('autor')->where('publicada', true)
                ->whereDate('fecha_publicacion', '<=', today())
                ->orderByDesc('fecha_publicacion')->paginate($this->perPage($request, 20))
        );
    }
}
