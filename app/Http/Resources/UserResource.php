<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'apellido' => $this->apellido,
            'email' => $this->email,
            'activo' => $this->activo,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('roles', fn () => $this->getAllPermissions()->pluck('name')),
            'created_at' => $this->created_at,
        ];
    }
}
