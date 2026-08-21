<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DescargaAndroidController extends Controller
{
    /**
     * Entrega la APK Android desde una URL pública estable de la plataforma.
     */
    public function __invoke(): BinaryFileResponse
    {
        $rutaApk = public_path('images/BrigadasMedicasV3.apk');

        // Si el archivo no fue incluido en el despliegue se responde 404 en lugar
        // de entregar una página o una descarga vacía que confunda al usuario.
        abort_unless(is_file($rutaApk), 404, 'La aplicación Android no está disponible en este momento.');

        return response()->download(
            $rutaApk,
            'BrigadasMedicasV3.apk',
            [
                'Content-Type' => 'application/vnd.android.package-archive',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
