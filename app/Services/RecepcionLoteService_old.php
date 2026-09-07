<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\DetalleLote;
use App\Models\Equipo;
use App\Models\EventoLogisticoLote;
use App\Models\TipoEventoLogistico;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecepcionLoteService
{
    public function __construct(
        private RegistroEquipoService $registroEquipoService
    ) {
    }

    public function recibirEquipo(
        int $usuarioId,
        int $detalleLoteId,
        array $datosEquipo
    ): Equipo {
        return DB::transaction(function () use (
            $usuarioId,
            $detalleLoteId,
            $datosEquipo
        ) {
            $usuario = $this->obtenerUsuarioAutorizado(
                $usuarioId
            );

            /*
            |--------------------------------------------------------------------------
            | Bloqueo del detalle del lote
            |--------------------------------------------------------------------------
            |
            | Impide que dos recepciones simultáneas incrementen incorrectamente
            | la cantidad recibida del mismo producto.
            |
            */

            $detalle = DetalleLote::query()
                ->with([
                    'lote.proveedor',
                    'producto',
                ])
                ->lockForUpdate()
                ->find($detalleLoteId);

            if (!$detalle) {
                throw new ReglaNegocioException(
                    'El detalle de lote solicitado no existe.'
                );
            }

            $lote = $detalle->lote;

            if (!$lote) {
                throw new ReglaNegocioException(
                    'El detalle seleccionado no se encuentra asociado a un lote válido.'
                );
            }

            if (in_array(
                $lote->estado,
                ['CERRADO', 'CANCELADO'],
                true
            )) {
                throw new ReglaNegocioException(
                    'No se pueden recibir equipos en un lote cerrado o cancelado.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Control de cantidad
            |--------------------------------------------------------------------------
            */

            if (
                $detalle->cantidad_recibida
                >= $detalle->cantidad_esperada
            ) {
                throw new ReglaNegocioException(
                    'Ya se recibió la cantidad esperada de este producto.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Almacén de recepción
            |--------------------------------------------------------------------------
            */

            $almacenId = $datosEquipo[
                'almacen_actual_id'
            ] ?? null;

            if (!$almacenId) {
                throw new ReglaNegocioException(
                    'Debe indicar el almacén donde se recibe el equipo.'
                );
            }

            $almacen = Almacen::query()
                ->where('activo', true)
                ->find($almacenId);

            if (!$almacen) {
                throw new ReglaNegocioException(
                    'El almacén de recepción no existe o se encuentra inactivo.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Registro físico del equipo
            |--------------------------------------------------------------------------
            |
            | El producto y el detalle del lote no se reciben desde el formulario.
            | Se fuerzan desde el detalle seleccionado para impedir inconsistencias.
            |
            */

            $datosRegistro = array_merge(
                $datosEquipo,
                [
                    'producto_id' =>
                        $detalle->producto_id,

                    'detalle_lote_id' =>
                        $detalle->id,

                    'almacen_actual_id' =>
                        $almacen->id,
                ]
            );

            $equipo = $this
                ->registroEquipoService
                ->registrar(
                    $usuario->id,
                    $datosRegistro
                );

            /*
            |--------------------------------------------------------------------------
            | Actualizamos cantidad recibida
            |--------------------------------------------------------------------------
            */

            $detalle->cantidad_recibida =
                $detalle->cantidad_recibida + 1;

            $detalle->save();

            /*
            |--------------------------------------------------------------------------
            | Evento logístico
            |--------------------------------------------------------------------------
            */

            $this->registrarEventoRecepcionSiCorresponde(
                $lote->id,
                $almacen,
                $usuario->id
            );

            /*
            |--------------------------------------------------------------------------
            | Estado general del lote
            |--------------------------------------------------------------------------
            */

            $this->actualizarEstadoRecepcion(
                $lote->id
            );

            return $equipo->fresh([
                'producto.marca',
                'producto.categoria',
                'detalleLote.lote.proveedor',
                'almacenActual',
                'estadoActual',
                'condicionFisica',
                'especificacion',
                'historialEstados.estadoDestino',
            ]);
        }, 3);
    }

    private function actualizarEstadoRecepcion(
        int $loteId
    ): void {
        $detalles = DetalleLote::query()
            ->where('lote_id', $loteId)
            ->lockForUpdate()
            ->get();

        $esperadas = $detalles->sum(
            'cantidad_esperada'
        );

        $recibidas = $detalles->sum(
            'cantidad_recibida'
        );

        $estado = (
            $esperadas > 0
            && $recibidas >= $esperadas
        )
            ? 'RECIBIDO'
            : 'RECEPCION_PARCIAL';

        DB::table('lotes')
            ->where('id', $loteId)
            ->update([
                'estado' => $estado,
                'updated_at' => now(),
            ]);
    }

    private function registrarEventoRecepcionSiCorresponde(
        int $loteId,
        Almacen $almacen,
        int $usuarioId
    ): void {
        /*
         * Actualmente OneShop tiene dos puntos operativos
         * relevantes en el recorrido de importación.
         */
        $codigoEvento = match (
            $almacen->codigo
        ) {
            'COCHABAMBA' =>
                'RECEPCION_COCHABAMBA',

            'ORURO_PRINCIPAL' =>
                'RECEPCION_ORURO',

            default => null,
        };

        /*
         * Si posteriormente aparecen nuevos almacenes,
         * su evento logístico se definirá explícitamente.
         */
        if ($codigoEvento === null) {
            return;
        }

        $tipoEvento = TipoEventoLogistico::query()
            ->where('codigo', $codigoEvento)
            ->where('activo', true)
            ->first();

        if (!$tipoEvento) {
            throw new ReglaNegocioException(
                "No se encuentra configurado el evento logístico {$codigoEvento}."
            );
        }

        $yaExiste = EventoLogisticoLote::query()
            ->where('lote_id', $loteId)
            ->where(
                'tipo_evento_logistico_id',
                $tipoEvento->id
            )
            ->exists();

        if ($yaExiste) {
            return;
        }

        EventoLogisticoLote::create([
            'lote_id' => $loteId,
            'tipo_evento_logistico_id' =>
                $tipoEvento->id,

            'usuario_id' => $usuarioId,
            'fecha_evento' => now(),
            'ubicacion' => $almacen->nombre,

            'descripcion' =>
                'Inicio de la recepción física del lote en '
                . $almacen->nombre
                . '.',
        ]);
    }

    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->where('activo', true)
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
                'El usuario no cuenta con permiso para gestionar la recepción de importaciones.'
            );
        }

        /*
         * RegistroEquipoService también comprobará
         * inventario.registrar como segunda capa.
         */

        return $usuario;
    }
}