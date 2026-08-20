<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(
        Request $request,
        Closure $next,
        string $permiso
    ): Response {
        $usuario = $request->user();

        if (
            $usuario === null ||
            !$usuario->tienePermiso($permiso)
        ) {
            abort(403);
        }

        return $next($request);
    }
}