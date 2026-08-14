<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignosVitalesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'turno_id' => $this->turno_id,
            'presion_arterial' => $this->presion_arterial,
            'temperatura' => (float) $this->temperatura,
            'frecuencia_cardiaca' => $this->frecuencia_cardiaca,
            'frecuencia_respiratoria' => $this->frecuencia_respiratoria,
            'updated_at' => $this->updated_at,
        ];
    }
}
