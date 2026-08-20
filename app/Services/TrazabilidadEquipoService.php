<?php

namespace App\Services;

use App\Models\Equipo;
use Illuminate\Support\Collection;

class TrazabilidadEquipoService
{
    public function obtener(Equipo $equipo): Collection
    {
        $equipo->loadMissing([
            'historialEstados.estadoOrigen',
            'historialEstados.estadoDestino',
            'historialEstados.usuario',
            'historialEstados.autorizadoPor',

            'transferencias.almacenOrigen',
            'transferencias.almacenDestino',
            'transferencias.solicitadoPor',
            'transferencias.despachadoPor',
            'transferencias.recibidoPor',

            'revisionesTecnicas.tecnico',

            'diagnosticos.tecnico',

            'reparaciones.tecnico',
            'reparaciones.autorizadoPor',
        ]);

        $eventos = collect();

        /*
        |--------------------------------------------------------------------------
        | Registro inicial
        |--------------------------------------------------------------------------
        */

        if ($equipo->fecha_registro) {
            $eventos->push([
                'fecha' => $equipo->fecha_registro,
                'tipo' => 'registro',
                'titulo' => 'Equipo registrado',
                'detalle' => 'El equipo fue incorporado al inventario de OneShop.',
                'usuario' => null,
                'observacion' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Cambios de estado
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->historialEstados as $historial) {
            $origen = $historial->estadoOrigen?->nombre;
            $destino = $historial->estadoDestino?->nombre;

            $eventos->push([
                'fecha' => $historial->fecha_cambio,
                'tipo' => 'estado',
                'titulo' => $destino ?? 'Cambio de estado',
                'detalle' => $origen
                    ? "{$origen} → {$destino}"
                    : "Estado actualizado a {$destino}",
                'usuario' => $historial->usuario?->name,
                'observacion' =>
                    $historial->motivo
                    ?: $historial->observacion,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Transferencias
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->transferencias as $transferencia) {

            if ($transferencia->fecha_solicitud) {
                $eventos->push([
                    'fecha' => $transferencia->fecha_solicitud,
                    'tipo' => 'transferencia',
                    'titulo' => 'Transferencia solicitada',
                    'detalle' =>
                        ($transferencia->almacenOrigen?->nombre ?? 'Origen')
                        . ' → '
                        . ($transferencia->almacenDestino?->nombre ?? 'Destino'),
                    'usuario' => $transferencia->solicitadoPor?->name,
                    'observacion' => $transferencia->observacion,
                ]);
            }

            if ($transferencia->fecha_despacho) {
                $eventos->push([
                    'fecha' => $transferencia->fecha_despacho,
                    'tipo' => 'transferencia',
                    'titulo' => 'Equipo despachado',
                    'detalle' =>
                        'Salida desde '
                        . ($transferencia->almacenOrigen?->nombre ?? 'almacén de origen'),
                    'usuario' => $transferencia->despachadoPor?->name,
                    'observacion' => null,
                ]);
            }

            if ($transferencia->fecha_recepcion) {
                $eventos->push([
                    'fecha' => $transferencia->fecha_recepcion,
                    'tipo' => 'transferencia',
                    'titulo' => 'Equipo recibido',
                    'detalle' =>
                        'Recepción en '
                        . ($transferencia->almacenDestino?->nombre ?? 'almacén de destino'),
                    'usuario' => $transferencia->recibidoPor?->name,
                    'observacion' => null,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Revisiones técnicas
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->revisionesTecnicas as $revision) {

            if ($revision->fecha_inicio) {
                $eventos->push([
                    'fecha' => $revision->fecha_inicio,
                    'tipo' => 'tecnico',
                    'titulo' => 'Revisión técnica iniciada',
                    'detalle' =>
                        $revision->tipo_revision
                        ?: 'Revisión técnica del equipo',
                    'usuario' => $revision->tecnico?->name,
                    'observacion' => $revision->observacion,
                ]);
            }

            if ($revision->fecha_fin) {
                $eventos->push([
                    'fecha' => $revision->fecha_fin,
                    'tipo' => 'tecnico',
                    'titulo' => 'Revisión técnica finalizada',
                    'detalle' =>
                        $revision->resultado_general
                        ?: 'Revisión finalizada',
                    'usuario' => $revision->tecnico?->name,
                    'observacion' => null,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Diagnósticos
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->diagnosticos as $diagnostico) {

            if (!$diagnostico->fecha_diagnostico) {
                continue;
            }

            $eventos->push([
                'fecha' => $diagnostico->fecha_diagnostico,
                'tipo' => 'diagnostico',
                'titulo' => 'Diagnóstico registrado',
                'detalle' => $diagnostico->descripcion,
                'usuario' => $diagnostico->tecnico?->name,
                'observacion' =>
                    $diagnostico->requiere_reparacion
                        ? 'Requiere reparación.'
                        : 'No requiere reparación.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Reparaciones
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->reparaciones as $reparacion) {

            if ($reparacion->fecha_inicio) {
                $eventos->push([
                    'fecha' => $reparacion->fecha_inicio,
                    'tipo' => 'reparacion',
                    'titulo' => 'Reparación iniciada',
                    'detalle' =>
                        $reparacion->trabajo_realizado
                        ?: 'Intervención técnica iniciada',
                    'usuario' => $reparacion->tecnico?->name,
                    'observacion' => $reparacion->observacion,
                ]);
            }

            if ($reparacion->fecha_fin) {
                $eventos->push([
                    'fecha' => $reparacion->fecha_fin,
                    'tipo' => 'reparacion',
                    'titulo' => 'Reparación finalizada',
                    'detalle' =>
                        $reparacion->resultado
                        ?: 'Intervención finalizada',
                    'usuario' => $reparacion->tecnico?->name,
                    'observacion' => null,
                ]);
            }
        }

        return $eventos
            ->filter(fn ($evento) => $evento['fecha'] !== null)
            ->sortByDesc(
                fn ($evento) => $evento['fecha']->timestamp
            )
            ->values();
    }
}