<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\HistorialEstadoEquipo;
use App\Models\TransicionEstadoEquipo;
use Illuminate\Support\Facades\DB;

class EstadoEquipoService
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {
    }


    public function cambiarEstado(
        int $equipoId,
        string $codigoEstadoDestino,
        ?int $usuarioId = null,
        ?int $autorizadoPorId = null,
        ?string $motivo = null,
        ?string $observacion = null
    ): Equipo {

        return DB::transaction(function () use (
            $equipoId,
            $codigoEstadoDestino,
            $usuarioId,
            $autorizadoPorId,
            $motivo,
            $observacion
        ) {

            $equipo = Equipo::query()
                ->lockForUpdate()
                ->findOrFail($equipoId);


            if (!$equipo->activo) {

                throw new ReglaNegocioException(
                    'No se puede cambiar el estado de un equipo inactivo.'
                );

            }


            $estadoDestino = EstadoEquipo::query()
                ->where('codigo', $codigoEstadoDestino)
                ->where('activo', true)
                ->first();


            if (!$estadoDestino) {

                throw new ReglaNegocioException(
                    "El estado {$codigoEstadoDestino} no existe o está inactivo."
                );

            }


            if ($equipo->estado_actual_id === $estadoDestino->id) {

                throw new ReglaNegocioException(
                    'El equipo ya se encuentra en el estado solicitado.'
                );

            }



            $transicion = TransicionEstadoEquipo::query()
                ->where(
                    'estado_origen_id',
                    $equipo->estado_actual_id
                )
                ->where(
                    'estado_destino_id',
                    $estadoDestino->id
                )
                ->where('activo', true)
                ->first();


            if (!$transicion) {

                $estadoActual = EstadoEquipo::find(
                    $equipo->estado_actual_id
                );


                throw new ReglaNegocioException(
                    sprintf(
                        'No está permitida la transición de %s a %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO',
                        $estadoDestino->codigo
                    )
                );

            }



            if (
                $transicion->requiere_autorizacion
                &&
                $autorizadoPorId === null
            ) {

                throw new ReglaNegocioException(
                    'Esta transición requiere autorización.'
                );

            }



            $estadoOrigenId = $equipo->estado_actual_id;


            $estadoOrigen = EstadoEquipo::find(
                $estadoOrigenId
            );



            $equipo->estado_actual_id = $estadoDestino->id;



            if (
                $estadoDestino->codigo === 'DISPONIBLE'
                &&
                $equipo->fecha_disponible === null
            ) {

                $equipo->fecha_disponible = now();

            }



            $equipo->save();



            HistorialEstadoEquipo::create([

                'equipo_id' => $equipo->id,

                'estado_origen_id' => $estadoOrigenId,

                'estado_destino_id' => $estadoDestino->id,

                'usuario_id' => $usuarioId,

                'autorizado_por_id' => $autorizadoPorId,

                'fecha_cambio' => now(),

                'motivo' => $motivo,

                'observacion' => $observacion,

            ]);



            /*
            |--------------------------------------------------------------------------
            | Auditoría del cambio de estado
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService->registrar(

                $usuarioId,

                'CAMBIO_ESTADO_EQUIPO',

                'Equipo',

                $equipo->id,

                [

                    'estado_anterior_id' =>
                        $estadoOrigenId,

                    'estado_anterior' =>
                        $estadoOrigen?->codigo,

                ],

                [

                    'estado_nuevo_id' =>
                        $estadoDestino->id,

                    'estado_nuevo' =>
                        $estadoDestino->codigo,

                ]

            );



            return $equipo->fresh([

                'estadoActual',

                'producto',

                'almacenActual',

            ]);

        }, 3);

    }
}