<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticiaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'resumen' => $this->resumen,
            'contenido' => $this->contenido,
            'imagen_url' => $this->imagen_url,
            'fecha_publicacion' => $this->fecha_publicacion?->format('Y-m-d'),
            'publicada' => $this->publicada,
            'autor' => $this->whenLoaded('autor', fn () => [
                'id' => $this->autor->id,
                'name' => $this->autor->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
