<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleLote;
use App\Models\EventoLogisticoLote;
use App\Models\TipoEventoLogistico;
use App\Models\UnidadAdquirida;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecepcionLoteService
{
    public function __construct(
        private UnidadAdquiridaService $unidadAdquiridaService
    ) {
    }


    /**
     * Registra llegada física en Cochabamba.
     *
     * NO crea Equipo.
     * NO realiza revisión técnica.
     *
     * Solo crea UnidadAdquirida
     * con trazabilidad temporal.
     */
    public function recibirUnidad(
        int $usuarioId,
        int $detalleLoteId,
        array $datosUnidad
    ): UnidadAdquirida {


        return DB::transaction(function () use (
            $usuarioId,
            $detalleLoteId,
            $datosUnidad
        ) {


            $usuario =
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );



            $detalle =
                DetalleLote::query()
                    ->with([
                        'lote',
                        'producto',
                    ])
                    ->lockForUpdate()
                    ->find($detalleLoteId);



            if (!$detalle) {

                throw new ReglaNegocioException(
                    'El detalle del lote no existe.'
                );

            }



            $lote = $detalle->lote;



            if (!$lote) {

                throw new ReglaNegocioException(
                    'El detalle no tiene lote asociado.'
                );

            }



            if (
                in_array(
                    $lote->estado,
                    [
                        'CERRADO',
                        'CANCELADO'
                    ],
                    true
                )
            ) {

                throw new ReglaNegocioException(
                    'No se pueden registrar unidades de un lote cerrado o cancelado.'
                );

            }



            if (
                !$detalle->producto->es_serializado
            ) {

                throw new ReglaNegocioException(
                    'El producto no requiere registro individual.'
                );

            }



            /*
            |--------------------------------------------------------------------------
            | Crear unidad física en Cochabamba
            |--------------------------------------------------------------------------
            */

            $unidades =
    $this->unidadAdquiridaService
        ->registrarLlegadaCochabamba(
            $usuario->id,
            $detalle->id,
            $datosUnidad['cantidad'],
            null,
            $datosUnidad['observacion'] ?? null
        );



            $unidad =
                $unidades->first();



            if (!$unidad) {

                throw new ReglaNegocioException(
                    'No fue posible registrar la unidad adquirida.'
                );

            }




            /*
            |--------------------------------------------------------------------------
            | Evento logístico
            |--------------------------------------------------------------------------
            */

            $this->registrarEventoRecepcion(
                $lote->id,
                $usuario->id
            );



            return $unidad->fresh([
                'producto.marca',
                'producto.categoria',
                'detalleLote.lote.proveedor',
                'almacenActual',
            ]);


        },3);

    }




    private function registrarEventoRecepcion(
        int $loteId,
        int $usuarioId
    ): void {


        $tipoEvento =
            TipoEventoLogistico::query()
                ->where(
                    'codigo',
                    'RECEPCION_COCHABAMBA'
                )
                ->where(
                    'activo',
                    true
                )
                ->first();



        if (!$tipoEvento) {

            return;

        }




        $existe =
            EventoLogisticoLote::query()
                ->where(
                    'lote_id',
                    $loteId
                )
                ->where(
                    'tipo_evento_logistico_id',
                    $tipoEvento->id
                )
                ->exists();



        if ($existe) {

            return;

        }




        EventoLogisticoLote::create([

            'lote_id' =>
                $loteId,


            'tipo_evento_logistico_id' =>
                $tipoEvento->id,


            'usuario_id' =>
                $usuarioId,


            'fecha_evento' =>
                now(),


            'ubicacion' =>
                'Depósito Cochabamba',


            'descripcion' =>
                'Inicio de recepción física de unidades adquiridas en Cochabamba.',

        ]);

    }





    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {


        $usuario =
            User::query()
                ->where(
                    'activo',
                    true
                )
                ->find($usuarioId);



        if (!$usuario) {

            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );

        }



        if (
            !$usuario->tienePermiso(
                'importacion.gestionar'
            )
        ) {

            throw new ReglaNegocioException(
                'El usuario no tiene permiso para gestionar importaciones.'
            );

        }



        return $usuario;

    }

}