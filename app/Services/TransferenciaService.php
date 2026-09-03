<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Transferencia;
use App\Models\User;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\AuditoriaService;
class TransferenciaService
{
      public function __construct(
        private AuditoriaService $auditoriaService
    ) {
    }
    public function crearTransferencia(
        int $usuarioId,
        int $almacenOrigenId,
        int $almacenDestinoId,
        array $equiposIds,
        ?string $observacion = null
    ): Transferencia {

        return DB::transaction(function () use (
            $usuarioId,
            $almacenOrigenId,
            $almacenDestinoId,
            $equiposIds,
            $observacion
        ) {

            $usuario = $this->obtenerUsuarioAutorizado(
                $usuarioId
            );

            if ($almacenOrigenId === $almacenDestinoId) {
                throw new ReglaNegocioException(
                    'El almacén origen y destino no pueden ser iguales.'
                );
            }


            if (empty($equiposIds)) {
                throw new ReglaNegocioException(
                    'Debe seleccionar al menos un equipo para transferir.'
                );
            }


            $equipos = Equipo::query()
                ->with([
                    'estadoActual',
                    'almacenActual',
                ])
                ->whereIn('id', $equiposIds)
                ->lockForUpdate()
                ->get();


            if ($equipos->count() !== count($equiposIds)) {
                throw new ReglaNegocioException(
                    'Uno o más equipos no existen.'
                );
            }


            foreach ($equipos as $equipo) {

                if (
                    $equipo->almacen_actual_id
                    !== $almacenOrigenId
                ) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no pertenece al almacén origen."
                    );
                }


                if (
                    !$equipo->estadoActual
                    || $equipo->estadoActual->codigo !== 'DISPONIBLE'
                ) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no está disponible para transferencia."
                    );
                }
            }


            $transferencia = Transferencia::create([

                'codigo' =>
                    'TR-' . strtoupper(Str::uuid()),

                'almacen_origen_id' =>
                    $almacenOrigenId,

                'almacen_destino_id' =>
                    $almacenDestinoId,

                'solicitado_por_id' =>
                    $usuario->id,

                'estado' =>
                    'SOLICITADA',

                'fecha_solicitud' =>
                    now(),

                'observacion' =>
                    $observacion,

            ]);


            $transferencia->equipos()->attach(
                $equiposIds
            );
            $this->auditoriaService->registrar(

    usuarioId: $usuario->id,

    accion: 'CREAR_TRANSFERENCIA',

    entidad: 'Transferencia',

    entidadId: $transferencia->id,

    datosNuevos: [

        'codigo' =>
            $transferencia->codigo,

        'estado' =>
            $transferencia->estado,

        'almacen_origen_id' =>
            $transferencia->almacen_origen_id,

        'almacen_destino_id' =>
            $transferencia->almacen_destino_id,

        'equipos' =>
            $equiposIds,

    ]

);

            return $transferencia->fresh([
                'equipos',
                'almacenOrigen',
                'almacenDestino',
            ]);
        });
    }



    public function despacharTransferencia(
        int $transferenciaId,
        int $usuarioId
    ): Transferencia {

        return DB::transaction(function () use (
            $transferenciaId,
            $usuarioId
        ) {

            $usuario = $this->obtenerUsuarioAutorizado(
                $usuarioId
            );


            $transferencia = Transferencia::query()
                ->lockForUpdate()
                ->find($transferenciaId);


            if (!$transferencia) {
                throw new ReglaNegocioException(
                    'La transferencia no existe.'
                );
            }


            if ($transferencia->estado !== 'SOLICITADA') {

                throw new ReglaNegocioException(
                    'Solo se pueden despachar transferencias solicitadas.'
                );

            }


            $transferencia->update([

                'estado' =>
                    'DESPACHADA',

                'despachado_por_id' =>
                    $usuario->id,

                'fecha_despacho' =>
                    now(),

            ]);
            $this->auditoriaService->registrar(

    usuarioId: $usuario->id,

    accion: 'DESPACHAR_TRANSFERENCIA',

    entidad: 'Transferencia',

    entidadId: $transferencia->id,

    datosAnteriores: [

        'estado' =>
            'SOLICITADA',

    ],

    datosNuevos: [

        'estado' =>
            'DESPACHADA',

        'fecha_despacho' =>
            $transferencia->fecha_despacho,

    ]

);

            return $transferencia->fresh();
        });
    }





    public function recibirTransferencia(
        int $transferenciaId,
        int $usuarioId
    ): Transferencia {


        return DB::transaction(function () use (
            $transferenciaId,
            $usuarioId
        ) {


            $usuario = $this->obtenerUsuarioAutorizado(
                $usuarioId
            );


            $transferencia = Transferencia::query()
                ->with('equipos')
                ->lockForUpdate()
                ->find($transferenciaId);


            if (!$transferencia) {

                throw new ReglaNegocioException(
                    'La transferencia no existe.'
                );

            }


            if ($transferencia->estado !== 'DESPACHADA') {

                throw new ReglaNegocioException(
                    'Solo se pueden recibir transferencias despachadas.'
                );

            }


            foreach ($transferencia->equipos as $equipo) {

                $equipo->update([

                    'almacen_actual_id' =>
                        $transferencia->almacen_destino_id,

                ]);

            }


            $transferencia->update([

                'estado' =>
                    'RECIBIDA',

                'recibido_por_id' =>
                    $usuario->id,

                'fecha_recepcion' =>
                    now(),

            ]);
                $this->auditoriaService->registrar(

    usuarioId: $usuario->id,

    accion: 'RECIBIR_TRANSFERENCIA',

    entidad: 'Transferencia',

    entidadId: $transferencia->id,

    datosAnteriores: [

        'estado' =>
            'DESPACHADA',

    ],

    datosNuevos: [

        'estado' =>
            'RECIBIDA',

        'fecha_recepcion' =>
            $transferencia->fecha_recepcion,

        'almacen_destino_id' =>
            $transferencia->almacen_destino_id,

    ]

);   

            return $transferencia->fresh([
                'equipos',
                'almacenDestino',
            ]);

        });

    }




    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {


        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);


        if (!$usuario) {

            throw new ReglaNegocioException(
                'El usuario no existe o está inactivo.'
            );

        }


        if (
    !$usuario->tienePermiso(
        'transferencias.gestionar'
    )
) {

            throw new ReglaNegocioException(
                'El usuario no cuenta con permisos para gestionar transferencias.'
            );

        }


        return $usuario;
    }
}