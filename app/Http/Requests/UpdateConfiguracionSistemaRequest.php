<?php

namespace App\Http\Requests;

use App\Models\ConfiguracionSistema;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionSistemaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso configuracion.gestionar se valida en la ruta API.
        return true;
    }

    public function rules(): array
    {
        return [
            // Se admite la ruta pública interna de la plataforma o un alojamiento
            // HTTPS externo. No se aceptan rutas físicas locales ni HTTP externo.
            'apk_android_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $esRutaInterna = $value === ConfiguracionSistema::RUTA_APK_ANDROID_LOCAL;
                    $esUrlHttps = filter_var($value, FILTER_VALIDATE_URL)
                        && str_starts_with($value, 'https://');

                    if (! $esRutaInterna && ! $esUrlHttps) {
                        $fail('La URL del APK debe ser HTTPS o la ruta interna de descarga de la plataforma.');
                    }
                },
            ],
        ];
    }
}
