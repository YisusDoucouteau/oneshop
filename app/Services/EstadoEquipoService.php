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
            /*
             * El bloqueo evita que otro proceso modifique
             * simultáneamente este mismo equipo.
             */
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
                ->where('estado_origen_id', $equipo->estado_actual_id)
                ->where('estado_destino_id', $estadoDestino->id)
                ->where('activo', true)
                ->first();

            if (!$transicion) {
                $estadoActual = EstadoEquipo::find($equipo->estado_actual_id);

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
                && $autorizadoPorId === null
            ) {
                throw new ReglaNegocioException(
                    'Esta transición requiere autorización.'
                );
            }

            $estadoOrigenId = $equipo->estado_actual_id;

            $equipo->estado_actual_id = $estadoDestino->id;

            /*
             * Conservamos la primera fecha en la que
             * el equipo estuvo disponible para venta.
             */
            if (
                $estadoDestino->codigo === 'DISPONIBLE'
                && $equipo->fecha_disponible === null
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

            return $equipo->fresh([
                'estadoActual',
                'producto',
                'almacenActual',
            ]);
        }, 3);
    }
}