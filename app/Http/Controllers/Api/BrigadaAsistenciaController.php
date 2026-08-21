<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMiAsistenciaRequest;
use App\Http\Resources\CitizenCampaignResource;
use App\Models\Brigada;
use App\Models\BrigadaAsistencia;
use Illuminate\Http\Request;

class BrigadaAsistenciaController extends Controller
{
    /** Campañas visibles para el ciudadano, incluyendo su respuesta personal. */
    public function myCampaigns(Request $request)
    {
        $campaigns = Brigada::with([
            'especialidades',
            'asistencias' => fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->with('especialidad:id,nombre'),
        ])
            ->whereIn('estado', ['programada', 'en_curso'])
            ->orderByDesc('fecha')
            ->paginate($this->perPage($request, 30));

        return CitizenCampaignResource::collection($campaigns);
    }

    public function index(Brigada $brigada)
    {
        $items = $brigada->asistencias()
            ->with(['user:id,name,apellido', 'especialidad:id,nombre'])
            ->latest('updated_at')
            ->get()
            ->map(fn (BrigadaAsistencia $item) => [
                'id' => $item->id,
                'nombre' => trim($item->user->name.' '.($item->user->apellido ?? '')),
                'estado' => $item->estado,
                'especialidad' => $item->especialidad ? [
                    'id' => $item->especialidad->id,
                    'nombre' => $item->especialidad->nombre,
                ] : null,
                'updated_at' => $item->updated_at,
            ]);

        return response()->json(['data' => $items]);
    }

    public function show(Request $request, Brigada $brigada)
    {
        $asistencia = BrigadaAsistencia::whereBelongsTo($brigada)->whereBelongsTo($request->user())->first();

        return response()->json(['data' => $asistencia?->load('especialidad:id,nombre')]);
    }

    public function update(UpdateMiAsistenciaRequest $request, Brigada $brigada)
    {
        $estado = $request->validated('estado');

        $asistencia = BrigadaAsistencia::updateOrCreate(
            ['brigada_id' => $brigada->id, 'user_id' => $request->user()->id],
            [
                'estado' => $estado,
                // Una especialidad solo representa demanda real cuando el ciudadano
                // confirmó que asistirá; las demás respuestas no deben inflar métricas.
                'especialidad_id' => $estado === 'asistira'
                    ? $request->validated('especialidad_id')
                    : null,
            ]
        );

        $asistencia->load('especialidad:id,nombre');

        return response()->json([
            'data' => $asistencia,
            'message' => match ($asistencia->estado) {
                'asistira' => "Preinscripción confirmada para {$asistencia->especialidad->nombre}. El turno se asignará al validar tu llegada.",
                'tal_vez' => 'Guardamos tu respuesta. Puedes confirmarla cuando estés seguro.',
                'no_asistira' => 'Gracias por avisarnos. Puedes cambiar tu respuesta si luego puedes asistir.',
            },
        ]);
    }
}
