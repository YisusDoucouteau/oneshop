<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Support\Collection;

class IndicadorComercialService
{

    /**
     * Resumen general de comportamiento comercial.
     */
    public function resumenGeneral(): array
    {

        $ventas =
            Venta::query()
                ->with('detalles')
                ->where(
                    'estado',
                    'REGISTRADA'
                )
                ->get();



        $totalVendido =
            $ventas->sum(
                fn ($venta) =>
                    (float) $venta->total
            );



        $costoTotal =
            $ventas
                ->flatMap(
                    fn ($venta) =>
                        $venta->detalles
                )
                ->sum(
                    fn ($detalle) =>
                        (float) $detalle->costo_unitario_snapshot
                        *
                        (int) $detalle->cantidad
                );



        $utilidad =
            $totalVendido - $costoTotal;



        $margen =
            $totalVendido > 0
                ? ($utilidad / $totalVendido) * 100
                : 0;



        $descuentos =
            $ventas
                ->flatMap(
                    fn ($venta) =>
                        $venta->detalles
                )
                ->sum(
                    fn ($detalle) =>
                        (float) $detalle->descuento_unitario
                );



        return [

            'cantidad_ventas' =>
                $ventas->count(),


            'total_vendido' =>
                round(
                    $totalVendido,
                    2
                ),


            'costo_total' =>
                round(
                    $costoTotal,
                    2
                ),


            'utilidad_total' =>
                round(
                    $utilidad,
                    2
                ),


            'margen_promedio' =>
                round(
                    $margen,
                    2
                ),


            'descuentos_otorgados' =>
                round(
                    $descuentos,
                    2
                ),
        ];
    }



    /**
     * Resumen comercial por vendedor.
     */
    public function ventasPorVendedor(): Collection
    {

        return Venta::query()
            ->with([
                'vendedor',
                'detalles'
            ])
            ->where(
                'estado',
                'REGISTRADA'
            )
            ->get()
            ->groupBy(
                'vendedor_id'
            )
            ->map(function ($ventas) {


                $total =
                    $ventas->sum(
                        'total'
                    );


                $costo =
                    $ventas
                        ->flatMap(
                            fn ($venta) =>
                                $venta->detalles
                        )
                        ->sum(
                            fn ($detalle) =>
                                (float) $detalle->costo_unitario_snapshot
                                *
                                (int) $detalle->cantidad
                        );



                return [

                    'vendedor_id' =>
                        $ventas->first()->vendedor_id,


                    'cantidad_ventas' =>
                        $ventas->count(),


                    'total_vendido' =>
                        round(
                            $total,
                            2
                        ),


                    'utilidad_generada' =>
                        round(
                            $total - $costo,
                            2
                        ),
                ];
            });
    }
}