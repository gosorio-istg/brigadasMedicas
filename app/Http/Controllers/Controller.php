<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // Los clientes (app móvil y web) pueden pedir más resultados por página para evitar
    // tener que recorrer decenas de páginas secuenciales en listas grandes; se limita a
    // $max para no permitir que un cliente pida la tabla completa de un solo golpe.
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min($max, max(1, $request->integer('per_page', $default)));
    }
}
