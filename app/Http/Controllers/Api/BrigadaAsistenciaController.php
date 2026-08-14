<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMiAsistenciaRequest;
use App\Models\Brigada;
use App\Models\BrigadaAsistencia;
use App\Http\Resources\CitizenCampaignResource;
use Illuminate\Http\Request;

class BrigadaAsistenciaController extends Controller
{
    /** Campañas visibles para el ciudadano, incluyendo su respuesta personal. */
    public function myCampaigns(Request $request)
    {
        $campaigns = Brigada::with([
            'especialidades',
            'asistencias' => fn ($query) => $query->where('user_id', $request->user()->id),
        ])
            ->whereIn('estado', ['programada', 'en_curso'])
            ->orderByDesc('fecha')
            ->paginate(30);

        return CitizenCampaignResource::collection($campaigns);
    }

    public function index(Brigada $brigada)
    {
        $items = $brigada->asistencias()->with('user:id,name,apellido')->latest('updated_at')->get()
            ->map(fn (BrigadaAsistencia $item) => [
                'id' => $item->id,
                'nombre' => trim($item->user->name.' '.($item->user->apellido ?? '')),
                'estado' => $item->estado,
                'updated_at' => $item->updated_at,
            ]);

        return response()->json(['data' => $items]);
    }

    public function show(Request $request, Brigada $brigada)
    {
        $asistencia = BrigadaAsistencia::whereBelongsTo($brigada)->whereBelongsTo($request->user())->first();

        return response()->json(['data' => $asistencia]);
    }

    public function update(UpdateMiAsistenciaRequest $request, Brigada $brigada)
    {
        $asistencia = BrigadaAsistencia::updateOrCreate(
            ['brigada_id' => $brigada->id, 'user_id' => $request->user()->id],
            ['estado' => $request->validated('estado')]
        );

        return response()->json([
            'data' => $asistencia,
            'message' => match ($asistencia->estado) {
                'asistira' => 'Gracias por confirmar. Te esperamos en la campaña.',
                'tal_vez' => 'Guardamos tu respuesta. Puedes confirmarla cuando estés seguro.',
                'no_asistira' => 'Gracias por avisarnos. Puedes cambiar tu respuesta si luego puedes asistir.',
            },
        ]);
    }
}
