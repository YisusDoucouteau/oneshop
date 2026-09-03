<?php

namespace App\Services;

use App\Models\Equipo;
use Illuminate\Support\Collection;

class TrazabilidadEquipoService
{
    public function obtener(Equipo $equipo): Collection
    {
        $equipo->loadMissing([

            /*
            |--------------------------------------------------------------------------
            | Inventario y trazabilidad base
            |--------------------------------------------------------------------------
            */

            'detalleLote.lote.proveedor',

            'historialEstados.estadoOrigen',
            'historialEstados.estadoDestino',
            'historialEstados.usuario',
            'historialEstados.autorizadoPor',


            /*
            |--------------------------------------------------------------------------
            | Transferencias internas
            |--------------------------------------------------------------------------
            */

            'transferencias.almacenOrigen',
            'transferencias.almacenDestino',
            'transferencias.solicitadoPor',
            'transferencias.despachadoPor',
            'transferencias.recibidoPor',


            /*
            |--------------------------------------------------------------------------
            | Área técnica
            |--------------------------------------------------------------------------
            */

            'revisionesTecnicas.tecnico',

            'diagnosticos.tecnico',

            'reparaciones.tecnico',
            'reparaciones.autorizadoPor',


            /*
            |--------------------------------------------------------------------------
            | Venta final
            |--------------------------------------------------------------------------
            */

            'detallesVentas.venta.cliente',
            'detallesVentas.garantia.casosGarantia.intervenciones',
            'detallesVentas.garantia.casosGarantia.cambioEquipo',

            /*
            |--------------------------------------------------------------------------
            | Garantías
            |--------------------------------------------------------------------------
            */

            'casosGarantia.garantia',
            'casosGarantia.intervenciones.usuario',
            'casosGarantia.cambioEquipo.equipoSaliente',
            'casosGarantia.cambioEquipo.equipoEntrante',

        ]);


        $eventos = collect();



        /*
        |--------------------------------------------------------------------------
        | Registro inicial
        |--------------------------------------------------------------------------
        */

        if ($equipo->fecha_registro) {

            $eventos->push([

                'fecha' =>
                    $equipo->fecha_registro,

                'tipo' =>
                    'registro',

                'titulo' =>
                    'Equipo registrado',

                'detalle' =>
                    'El equipo fue incorporado al inventario de OneShop.',

                'usuario' =>
                    null,

                'observacion' =>
                    null,

            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | Origen del equipo - lote
        |--------------------------------------------------------------------------
        */

        if ($equipo->detalleLote?->lote) {

            $lote =
                $equipo->detalleLote->lote;


            $eventos->push([

                'fecha' =>
                    $lote->created_at,

                'tipo' =>
                    'importacion',

                'titulo' =>
                    'Equipo asociado a lote de importación',

                'detalle' =>
                    'Lote: '
                    . $lote->codigo
                    . ' | Proveedor: '
                    . (
                        $lote->proveedor?->nombre
                        ?? 'No registrado'
                    ),

                'usuario' =>
                    null,

                'observacion' =>
                    $lote->observacion,

            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | Cambios de estado
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->historialEstados as $historial) {


            $origen =
                $historial->estadoOrigen?->nombre;


            $destino =
                $historial->estadoDestino?->nombre;



            $eventos->push([

                'fecha' =>
                    $historial->fecha_cambio,

                'tipo' =>
                    'estado',

                'titulo' =>
                    $destino ?? 'Cambio de estado',

                'detalle' =>
                    $origen
                        ? "{$origen} → {$destino}"
                        : "Estado actualizado a {$destino}",

                'usuario' =>
                    $historial->usuario?->name,

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

                    'fecha' =>
                        $transferencia->fecha_solicitud,

                    'tipo' =>
                        'transferencia',

                    'titulo' =>
                        'Transferencia solicitada',

                    'detalle' =>
                        ($transferencia->almacenOrigen?->nombre ?? 'Origen')
                        .' → '.
                        ($transferencia->almacenDestino?->nombre ?? 'Destino'),

                    'usuario' =>
                        $transferencia->solicitadoPor?->name,

                    'observacion' =>
                        $transferencia->observacion,

                ]);
            }


            if ($transferencia->fecha_despacho) {

                $eventos->push([

                    'fecha' =>
                        $transferencia->fecha_despacho,

                    'tipo' =>
                        'transferencia',

                    'titulo' =>
                        'Equipo despachado',

                    'detalle' =>
                        'Salida desde '
                        .
                        ($transferencia->almacenOrigen?->nombre
                        ?? 'almacén origen'),

                    'usuario' =>
                        $transferencia->despachadoPor?->name,

                    'observacion' =>
                        null,

                ]);
            }


            if ($transferencia->fecha_recepcion) {

                $eventos->push([

                    'fecha' =>
                        $transferencia->fecha_recepcion,

                    'tipo' =>
                        'transferencia',

                    'titulo' =>
                        'Equipo recibido',

                    'detalle' =>
                        'Recepción en '
                        .
                        ($transferencia->almacenDestino?->nombre
                        ?? 'almacén destino'),

                    'usuario' =>
                        $transferencia->recibidoPor?->name,

                    'observacion' =>
                        null,

                ]);
            }

        }




        /*
        |--------------------------------------------------------------------------
        | Venta final
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->detallesVentas as $detalleVenta) {


            $venta =
                $detalleVenta->venta;


            if ($venta) {


                $eventos->push([

                    'fecha' =>
                        $venta->created_at,

                    'tipo' =>
                        'venta',

                    'titulo' =>
                        'Equipo vendido',

                    'detalle' =>
                        'Cliente: '
                        .
                        (
                            $venta->cliente?->nombre_completo
                            ?? 'Cliente no registrado'
                        )
                        .
                        ' | Venta N° '
                        .
                        $venta->id,

                    'usuario' =>
                        $venta->vendedor?->name
                        ?? null,

                    'observacion' =>
                        $venta->observacion,

                ]);
            }
        }




        /*
        |--------------------------------------------------------------------------
        | Garantías
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->casosGarantia as $caso) {


            $eventos->push([

                'fecha' =>
                    $caso->fecha_apertura,

                'tipo' =>
                    'garantia',

                'titulo' =>
                    'Caso de garantía abierto',

                'detalle' =>
                    'Motivo: '
                    .
                    $caso->motivo_cliente
                    .
                    ' | Estado: '
                    .
                    $caso->estado,

                'usuario' =>
                    $caso->recibidoPor?->name,

                'observacion' =>
                    $caso->observacion,

            ]);



            foreach ($caso->intervenciones as $intervencion) {


                $eventos->push([

                    'fecha' =>
                        $intervencion->fecha_intervencion,

                    'tipo' =>
                        'garantia',

                    'titulo' =>
                        'Intervención de garantía',

                    'detalle' =>
                        $intervencion->descripcion,

                    'usuario' =>
                        $intervencion->usuario?->name,

                    'observacion' =>
                        $intervencion->resultado,

                ]);
            }



            if ($caso->cambioEquipo) {


                $eventos->push([

                    'fecha' =>
                        $caso->cambioEquipo->fecha_cambio,

                    'tipo' =>
                        'garantia',

                    'titulo' =>
                        'Cambio de equipo por garantía',

                    'detalle' =>
                        'Equipo anterior: '
                        .
                        $caso->cambioEquipo->equipoSaliente?->codigo_interno
                        .
                        ' | Equipo nuevo: '
                        .
                        $caso->cambioEquipo->equipoEntrante?->codigo_interno,

                    'usuario' =>
                        $caso->cambioEquipo->autorizadoPor?->name,

                    'observacion' =>
                        $caso->cambioEquipo->observacion,

                ]);
            }
        }

        /*
|--------------------------------------------------------------------------
| Garantías
|--------------------------------------------------------------------------
*/

foreach ($equipo->detallesVentas as $detalleVenta) {

    $garantia = $detalleVenta->garantia;

if ($garantia) {

        $eventos->push([

            'fecha' =>
                $garantia->fecha_inicio,

            'tipo' =>
                'garantia',

            'titulo' =>
                'Garantía generada',

            'detalle' =>
                'Garantía vigente hasta '
                . $garantia->fecha_fin
                    ?->format('d/m/Y'),

            'usuario' =>
                null,

            'observacion' =>
                $garantia->condiciones_snapshot,

        ]);


        foreach ($garantia->casosGarantia as $caso) {

            $eventos->push([

                'fecha' =>
                    $caso->fecha_apertura,

                'tipo' =>
                    'garantia',

                'titulo' =>
                    'Caso de garantía abierto',

                'detalle' =>
                    $caso->motivo_cliente,

                'usuario' =>
                    $caso->recibidoPor?->name,

                'observacion' =>
                    $caso->estado,

            ]);


            foreach ($caso->intervenciones as $intervencion) {

                $eventos->push([

                    'fecha' =>
                        $intervencion->fecha_intervencion,

                    'tipo' =>
                        'garantia',

                    'titulo' =>
                        'Intervención de garantía',

                    'detalle' =>
                        $intervencion->descripcion,

                    'usuario' =>
                        $intervencion->usuario?->name,

                    'observacion' =>
                        $intervencion->resultado,

                ]);

            }
        }
    }
}


        return $eventos

            ->filter(
                fn ($evento) =>
                    $evento['fecha'] !== null
            )

            ->sortByDesc(
                fn ($evento) =>
                    $evento['fecha']->timestamp
            )

            ->values();
    }
}