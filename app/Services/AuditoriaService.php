<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Request;

class AuditoriaService
{

    public function registrar(
        ?int $usuarioId,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): Auditoria {

        return Auditoria::create([

            'usuario_id' =>
                $usuarioId,

            'accion' =>
                $accion,

            'entidad' =>
                $entidad,

            'entidad_id' =>
                $entidadId,

            'datos_anteriores' =>
                $datosAnteriores,

            'datos_nuevos' =>
                $datosNuevos,

            'direccion_ip' =>
                Request::ip(),

            'agente_usuario' =>
                Request::userAgent(),

            'ruta' =>
                Request::path(),

            'metodo_http' =>
                Request::method(),

            'fecha_evento' =>
                now(),

        ]);

    }
}