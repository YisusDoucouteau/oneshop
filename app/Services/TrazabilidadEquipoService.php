<?php

namespace App\Services;

use App\Models\Equipo;
use Illuminate\Support\Collection;

class TrazabilidadEquipoService
{
    public function obtener(Equipo $equipo): Collection
    {
        $equipo->loadMissing([

            'detalleLote.lote.proveedor',

            'historialEstados.estadoOrigen',
            'historialEstados.estadoDestino',
            'historialEstados.usuario',

            'transferencias.almacenOrigen',
            'transferencias.almacenDestino',
            'transferencias.solicitadoPor',
            'transferencias.despachadoPor',
            'transferencias.recibidoPor',

            'detallesVentas.venta.cliente',
            'detallesVentas.venta.vendedor',

            'detallesVentas.garantia.casosGarantia.recibidoPor',
            'detallesVentas.garantia.casosGarantia.intervenciones.usuario',

            'casosGarantia.recibidoPor',
            'casosGarantia.intervenciones.usuario',

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

                'orden' => 2,

                'titulo' => 'Equipo registrado',

                'detalle' =>
                    'El equipo fue incorporado al inventario de OneShop.',

                'usuario' => null,

                'observacion' => null,

            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | Importación
        |--------------------------------------------------------------------------
        */

        if ($equipo->detalleLote?->lote) {

            $lote = $equipo->detalleLote->lote;


            $eventos->push([

                'fecha' => $lote->created_at,

                'tipo' => 'importacion',

                'orden' => 1,

                'titulo' => 'Equipo asociado a lote de importación',

                'detalle' =>
                    'Lote: '
                    . $lote->codigo
                    . ' | Proveedor: '
                    . (
                        $lote->proveedor?->nombre
                        ?? 'No registrado'
                    ),

                'usuario' => null,

                'observacion' => $lote->observacion,

            ]);

        }



        /*
        |--------------------------------------------------------------------------
        | Historial de estados
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->historialEstados as $historial) {

            $eventos->push([

                'fecha' =>
                    $historial->fecha_cambio,

                'tipo' =>
                    'estado',

                'orden' => 3,

                'titulo' =>
                    $historial->estadoDestino?->nombre
                    ?? 'Cambio de estado',

                'detalle' =>
                    (
                        $historial->estadoOrigen?->nombre
                        ?? ''
                    )
                    .
                    ' → '
                    .
                    (
                        $historial->estadoDestino?->nombre
                        ?? ''
                    ),

                'usuario' =>
                    $historial->usuario?->name,

                'observacion' =>
                    $historial->observacion,

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

                    'orden' => 4,

                    'titulo' =>
                        'Transferencia solicitada',

                    'detalle' =>
                        (
                            $transferencia->almacenOrigen?->nombre
                            ?? 'Origen'
                        )
                        .
                        ' → '
                        .
                        (
                            $transferencia->almacenDestino?->nombre
                            ?? 'Destino'
                        ),

                    'usuario' =>
                        $transferencia->solicitadoPor?->name,

                    'observacion' => null,

                ]);

            }


            if ($transferencia->fecha_recepcion) {

                $eventos->push([

                    'fecha' =>
                        $transferencia->fecha_recepcion,

                    'tipo' =>
                        'transferencia',

                    'orden' => 4,

                    'titulo' =>
                        'Equipo recibido',

                    'detalle' =>
                        'Recepción completada en almacén destino.',

                    'usuario' =>
                        $transferencia->recibidoPor?->name,

                    'observacion' => null,

                ]);

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Ventas
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->detallesVentas as $detalleVenta) {


            $venta = $detalleVenta->venta;


            if ($venta) {

                $eventos->push([

                    'fecha' =>
                        $venta->created_at,

                    'tipo' =>
                        'venta',

                    'orden' => 5,

                    'titulo' =>
                        'Equipo vendido',

                    'detalle' =>
                        'Cliente: '
                        .
                        (
                            $venta->cliente?->nombre_completo
                            ?? 'No registrado'
                        ),

                    'usuario' =>
                        $venta->vendedor?->name,

                    'observacion' =>
                        $venta->observacion,

                ]);

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Garantías vigentes asociadas a venta
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->detallesVentas as $detalleVenta) {


            $garantia = $detalleVenta->garantia;


            if (!$garantia) {
                continue;
            }


            $eventos->push([

                'fecha' =>
                    $garantia->fecha_inicio,

                'tipo' =>
                    'garantia',

                'orden' => 6,

                'titulo' =>
                    'Garantía registrada',

                'detalle' =>
                    'Cobertura vigente hasta '
                    .
                    (
                        $garantia->fecha_fin
                        ? $garantia->fecha_fin->format('d/m/Y')
                        : 'Sin fecha'
                    ),

                'usuario' =>
                    null,

                'observacion' =>
                    $garantia->condiciones_snapshot,

            ]);

        }



        /*
        |--------------------------------------------------------------------------
        | Casos garantía
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->casosGarantia as $caso) {


            $eventos->push([

                'fecha' =>
                    $caso->fecha_apertura,

                'tipo' =>
                    'garantia',

                'orden' => 6,

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

                    'orden' => 6,

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



        /*
        |--------------------------------------------------------------------------
        | Iconos timeline
        |--------------------------------------------------------------------------
        */

        $iconos = [

            'registro' =>
                'package',

            'importacion' =>
                'truck',

            'estado' =>
                'settings',

            'transferencia' =>
                'truck',

            'venta' =>
                'chart',

            'garantia' =>
                'shield',

        ];



        $eventos = $eventos

            ->filter(
                fn ($evento) =>
                    $evento['fecha'] !== null
            )

            ->sortBy(function ($evento) {

                return [

                    $evento['orden'] ?? 99,

                    $evento['fecha']->timestamp,

                ];

            })

            ->values();



        $ultimoIndice =
            $eventos->count() - 1;



        return $eventos

            ->map(function ($evento, $index) use ($iconos, $ultimoIndice) {


                $evento['icono'] =
                    $iconos[$evento['tipo']]
                    ?? 'package';


                $evento['activo'] =
                    $index === $ultimoIndice;


                return $evento;


            })

            ->values();

    }
}