<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    public const RUTA_APK_ANDROID_LOCAL = '/descargas/brigadas-medicas-android.apk';

    // El nombre se declara porque el pluralizador inglés de Eloquent no produciría
    // correctamente el nombre español elegido para esta tabla de configuración global.
    protected $table = 'configuracion_sistema';

    protected $fillable = ['apk_android_url'];

    /**
     * Devuelve la única configuración global y la crea vacía cuando todavía no existe.
     */
    public static function actual(): self
    {
        return static::query()->firstOrCreate([], [
            // Se pasa el valor explícito para que el modelo recién creado represente
            // exactamente lo que quedó guardado en la base de datos.
            'apk_android_url' => self::RUTA_APK_ANDROID_LOCAL,
        ]);
    }

    /**
     * Convierte una ruta interna en la URL completa del dominio desde el cual
     * se está consultando la plataforma; las URL HTTPS externas se conservan.
     */
    public function apkAndroidUrlPublica(): ?string
    {
        if (blank($this->apk_android_url)) {
            return null;
        }

        return str_starts_with($this->apk_android_url, '/')
            ? url($this->apk_android_url)
            : $this->apk_android_url;
    }
}
