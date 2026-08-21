<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfiguracionSistemaRequest;
use App\Http\Resources\ConfiguracionSistemaResource;
use App\Models\ConfiguracionSistema;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Response;

class ConfiguracionSistemaController extends Controller
{
    /**
     * Expone únicamente la información necesaria para ofrecer la app Android en el login.
     */
    public function showPublic(): ConfiguracionSistemaResource
    {
        // fresh() evita que JsonResource herede el estado wasRecentlyCreated y responda
        // 201 durante la primera consulta pública; un GET debe devolver siempre 200.
        return new ConfiguracionSistemaResource(ConfiguracionSistema::actual()->fresh());
    }

    /**
     * Guarda las opciones globales. La ruta exige configuracion.gestionar.
     */
    public function update(UpdateConfiguracionSistemaRequest $request): ConfiguracionSistemaResource
    {
        $configuracion = ConfiguracionSistema::actual();
        $configuracion->update($request->validated());

        return new ConfiguracionSistemaResource($configuracion->fresh());
    }

    /**
     * Genera el QR dentro del servidor para no compartir la URL configurada con terceros.
     */
    public function qrAndroid(): Response
    {
        $configuracion = ConfiguracionSistema::actual();

        abort_unless(filled($configuracion->apk_android_url), 404);

        $renderer = new ImageRenderer(
            new RendererStyle(320, 3),
            new SvgImageBackEnd
        );
        // El QR siempre recibe una URL absoluta. Para rutas internas se utiliza
        // automáticamente el dominio desde el que el usuario abrió la plataforma.
        $svg = (new Writer($renderer))->writeString($configuracion->apkAndroidUrlPublica());

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
