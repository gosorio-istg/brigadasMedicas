<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreferenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'notificaciones_email' => $this->notificaciones_email,
            'notificaciones_push' => $this->notificaciones_push,
            'updated_at' => $this->updated_at,
        ];
    }
}
