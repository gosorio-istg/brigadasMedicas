<?php

if (! function_exists('safe_route')) {
    /**
     * Devuelve la ruta nombrada si existe; si no, usa una URL directa de respaldo.
     * Evita 500s por route() cuando una vista se renderiza con un nombre desincronizado.
     */
    function safe_route(string $name, string $fallback = '/', bool $absolute = true): string
    {
        if (app()->bound('routes') && app('router')->has($name)) {
            return route($name, [], $absolute);
        }

        return url($fallback, [], $absolute);
    }
}
