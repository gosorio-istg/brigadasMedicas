<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TurnoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_turno' => $this->numero_turno,
            'estado' => $this->estado,
            'medico' => $this->whenLoaded('medico', fn () => $this->medico ? [
                'id' => $this->medico->id,
                'nombres' => $this->medico->nombres,
            ] : null),
            'hora_registro' => $this->hora_registro,
            'hora_atencion' => $this->hora_atencion,
            'paciente' => $this->whenLoaded('paciente', fn () => [
                'id' => $this->paciente->id,
                'cedula' => $this->paciente->cedula,
                'nombres' => $this->paciente->nombres,
                'apellidos' => $this->paciente->apellidos,
            ]),
            'brigada' => $this->whenLoaded('brigada', fn () => [
                'id' => $this->brigada->id,
                'nombre' => $this->brigada->nombre,
            ]),
            'especialidad' => $this->whenLoaded('especialidad', fn () => [
                'id' => $this->especialidad->id,
                'nombre' => $this->especialidad->nombre,
            ]),
            'registrado_por' => $this->whenLoaded('registrador', fn () => [
                'id' => $this->registrador->id,
                'name' => $this->registrador->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
