<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Support\Collection;

class ReporteMargenVentaService
{

    /**
     * Obtiene el margen económico de una venta.
     */
    public function calcularMargenVenta(
        int $ventaId
    ): array {

        $venta =
            Venta::query()
                ->with('detalles')
                ->find($ventaId);


        if (!$venta) {
            throw new \InvalidArgumentException(
                'La venta no existe.'
            );
        }



        $costoTotal =
            $venta->detalles
                ->sum(
                    fn ($detalle) =>
                        (float) $detalle->costo_unitario_snapshot
                        *
                        (int) $detalle->cantidad
                );



        $ventaTotal =
            (float) $venta->total;



        $utilidad =
            $ventaTotal - $costoTotal;



        $margen =
            $ventaTotal > 0
                ? ($utilidad / $ventaTotal) * 100
                : 0;



        return [

            'venta_id' =>
                $venta->id,

            'numero' =>
                $venta->numero,

            'total_venta' =>
                round($ventaTotal, 2),

            'costo_total' =>
                round($costoTotal, 2),

            'utilidad' =>
                round($utilidad, 2),

            'porcentaje_margen' =>
                round($margen, 2),

            'tiene_descuento' =>
                $venta->detalles
                    ->contains(
                        fn ($detalle) =>
                            (float) $detalle->descuento_unitario > 0
                    ),
        ];
    }



    /**
     * Obtiene resumen de margen por vendedor.
     */
    public function margenPorVendedor(): Collection
    {

        return Venta::query()
            ->with([
                'vendedor',
                'detalles'
            ])
            ->get()
            ->groupBy(
                'vendedor_id'
            )
            ->map(function ($ventas) {


                $totalVentas =
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
                                $detalle->costo_unitario_snapshot
                        );


                return [

                    'vendedor_id' =>
                        $ventas->first()->vendedor_id,

                    'ventas_realizadas' =>
                        $ventas->count(),

                    'total_vendido' =>
                        round(
                            $totalVentas,
                            2
                        ),

                    'utilidad_generada' =>
                        round(
                            $totalVentas - $costo,
                            2
                        ),
                ];
            });
    }
}