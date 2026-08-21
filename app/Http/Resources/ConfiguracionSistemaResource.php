<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionSistemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tieneDescargaAndroid = filled($this->apk_android_url);

        return [
            // Se conserva el valor editable (ruta interna o URL externa) para el panel.
            'apk_android_url' => $this->apk_android_url,
            // Esta es la dirección completa que deben abrir el QR y el botón de descarga.
            'apk_android_download_url' => $tieneDescargaAndroid
                ? $this->resource->apkAndroidUrlPublica()
                : null,
            // Se devuelve una ruta relativa para que funcione tanto en Laragon como
            // en producción y no dependa de que APP_URL coincida con el dominio abierto.
            'qr_android_url' => $tieneDescargaAndroid
                ? route('public.configuracion.android-qr', [
                    'v' => optional($this->updated_at)->timestamp,
                ], false)
                : null,
            'updated_at' => $this->updated_at,
        ];
    }
}
