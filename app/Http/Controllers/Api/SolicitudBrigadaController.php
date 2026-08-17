<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSolicitudBrigadaRequest;
use App\Http\Requests\UpdateSolicitudBrigadaRequest;
use App\Http\Resources\SolicitudBrigadaResource;
use App\Models\SolicitudBrigada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudBrigadaController extends Controller
{
    // Público: cualquier persona puede solicitar una brigada sin necesidad de una cuenta.
    public function store(StoreSolicitudBrigadaRequest $request)
    {
        $solicitud = SolicitudBrigada::create($request->validated() + ['estado' => 'pendiente']);

        return new SolicitudBrigadaResource($solicitud);
    }

    // Protegido (permission:brigadas.gestionar): el Coordinador revisa las solicitudes.
    public function index(Request $request)
    {
        $solicitudes = SolicitudBrigada::with(['brigada', 'gestionadoPor'])
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return SolicitudBrigadaResource::collection($solicitudes);
    }

    public function update(UpdateSolicitudBrigadaRequest $request, SolicitudBrigada $solicitud)
    {
        $data = $request->validated();

        $solicitud->estado = $data['estado'];
        if (array_key_exists('notas_coordinador', $data)) {
            $solicitud->notas_coordinador = $data['notas_coordinador'];
        }
        if (array_key_exists('brigada_id', $data)) {
            $solicitud->brigada_id = $data['brigada_id'];
        }
        $solicitud->gestionado_por = Auth::id();
        $solicitud->save();

        return new SolicitudBrigadaResource($solicitud->load(['brigada', 'gestionadoPor']));
    }
}
