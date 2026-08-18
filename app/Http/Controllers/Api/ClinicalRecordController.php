<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAtencionRequest;
use App\Http\Requests\StoreSignosVitalesRequest;
use App\Http\Resources\AtencionResource;
use App\Http\Resources\SignosVitalesResource;
use App\Models\Atencion;
use App\Models\Medico;
use App\Models\SignosVitales;
use App\Models\Turno;
use App\Services\SyncOutboxService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicalRecordController extends Controller
{
    public function storeSignosVitales(StoreSignosVitalesRequest $request, Turno $turno, SyncOutboxService $sync)
    {
        $record = SignosVitales::updateOrCreate(
            ['turno_id' => $turno->id],
            $request->validated() + ['registrado_por' => Auth::id()]
        );

        if ($turno->estado === 'pendiente') {
            $turno->update(['estado' => 'en_espera']);
        }
        $sync->queue('turnos_realtime', $turno->id, $this->realtimePayload($turno->fresh()));

        return new SignosVitalesResource($record);
    }

    public function storeAtencion(StoreAtencionRequest $request, Turno $turno, SyncOutboxService $sync)
    {
        $medico = Medico::where('user_id', Auth::id())->first();

        // Si quien registra es un Médico (no un Coordinador/Brigadista actuando en su
        // nombre), su especialidad debe coincidir con la del turno: cada doctor atiende
        // turnos de su propia especialidad, no de cualquiera.
        if ($medico && $medico->especialidad_id !== $turno->especialidad_id) {
            return response()->json([
                'message' => 'Este turno es de otra especialidad; no coincide con la tuya.',
            ], 422);
        }

        $atencion = DB::transaction(function () use ($request, $turno, $sync, $medico) {
            $record = Atencion::updateOrCreate(
                ['turno_id' => $turno->id],
                $request->validated() + [
                    'medico_id' => $medico?->id ?? $turno->medico_id,
                    'registrado_por' => Auth::id(),
                    'fecha_atencion' => now(),
                ]
            );
            $turno->update([
                'estado' => 'atendido',
                'medico_id' => $medico?->id ?? $turno->medico_id,
                'hora_atencion' => now(),
            ]);
            $sync->queue('turnos_realtime', $turno->id, $this->realtimePayload($turno->fresh()));

            return $record;
        });

        return new AtencionResource($atencion);
    }

    private function realtimePayload(Turno $turno): array
    {
        return [
            'turno_id' => $turno->id,
            'numero_turno' => $turno->numero_turno,
            'estado' => $turno->estado,
            'brigada_id' => $turno->brigada_id,
            'especialidad_id' => $turno->especialidad_id,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
