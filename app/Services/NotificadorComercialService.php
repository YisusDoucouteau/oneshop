<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\User;

class NotificadorComercialService
{

    public function crear(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null
    ): Notificacion {


        return Notificacion::create([

            'usuario_id' =>
                $usuarioId,

            'tipo' =>
                $tipo,

            'titulo' =>
                $titulo,

            'mensaje' =>
                $mensaje,

            'referencia_tipo' =>
                $referenciaTipo,

            'referencia_id' =>
                $referenciaId,

            'canal' =>
                'INTERNO',

            'leido' =>
                false,

            'resultado' =>
                'CREADA',

        ]);

    }



    public function marcarComoLeida(
        Notificacion $notificacion
    ): bool {


        return $notificacion->update([

            'leido' =>
                true,

            'fecha_lectura' =>
                now(),

        ]);

    }

}