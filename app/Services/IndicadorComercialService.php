<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IndicadorComercialService
{
    /**
     * Resumen general de comportamiento comercial.
     *
     * Filtros admitidos:
     * - fecha_desde (Y-m-d)
     * - fecha_hasta (Y-m-d)
     * - vendedor_id
     * - cliente_id
     * - estado_venta (REGISTRADA | ANULADA | TODAS)
     * - q (venta, cliente o vendedor)
     */
    public function resumenGeneral(array $filtros = []): array
    {
        $ventas = $this->consultaVentas($filtros)
            ->with('detalles')
            ->get();

        $totalVendido = $ventas->sum(
            fn ($venta) => (float) $venta->total
        );

        $detalles = $ventas->flatMap(
            fn ($venta) => $venta->detalles
        );

        $costoTotal = $detalles->sum(
            fn ($detalle) =>
                (float) $detalle->costo_unitario_snapshot
                * (int) $detalle->cantidad
        );

        $descuentos = $detalles->sum(
            fn ($detalle) =>
                (float) $detalle->descuento_unitario
                * (int) $detalle->cantidad
        );

        $utilidad = $totalVendido - $costoTotal;

        $margen = $totalVendido > 0
            ? ($utilidad / $totalVendido) * 100
            : 0;

        $cantidadVentas = $ventas->count();

        return [
            'cantidad_ventas' => $cantidadVentas,
            'total_vendido' => round($totalVendido, 2),
            'costo_total' => round($costoTotal, 2),
            'utilidad_total' => round($utilidad, 2),
            'margen_promedio' => round($margen, 2),
            'descuentos_otorgados' => round($descuentos, 2),
            'ticket_promedio' => round(
                $cantidadVentas > 0
                    ? $totalVendido / $cantidadVentas
                    : 0,
                2
            ),
        ];
    }

    /**
     * Resumen comercial por vendedor.
     */
    public function ventasPorVendedor(array $filtros = []): Collection
    {
        return $this->consultaVentas($filtros)
            ->with([
                'vendedor',
                'detalles',
            ])
            ->get()
            ->groupBy('vendedor_id')
            ->map(function ($ventas) {
                $total = (float) $ventas->sum('total');

                $costo = $ventas
                    ->flatMap(fn ($venta) => $venta->detalles)
                    ->sum(
                        fn ($detalle) =>
                            (float) $detalle->costo_unitario_snapshot
                            * (int) $detalle->cantidad
                    );

                $primera = $ventas->first();

                return [
                    'vendedor_id' => $primera->vendedor_id,
                    'vendedor' => $primera->vendedor?->name ?? 'Usuario no disponible',
                    'cantidad_ventas' => $ventas->count(),
                    'total_vendido' => round($total, 2),
                    'utilidad_generada' => round($total - $costo, 2),
                ];
            })
            ->sortByDesc('total_vendido')
            ->values();
    }

    public function consultaVentas(array $filtros = []): Builder
    {
        $estado = $filtros['estado_venta'] ?? 'REGISTRADA';
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Venta::query()
            ->when(
                in_array($estado, ['REGISTRADA', 'ANULADA'], true),
                fn (Builder $query) => $query->where('estado', $estado)
            )
            ->when(
                !in_array($estado, ['REGISTRADA', 'ANULADA', 'TODAS'], true),
                fn (Builder $query) => $query->where('estado', 'REGISTRADA')
            )
            ->when(
                !empty($filtros['fecha_desde']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_venta',
                    '>=',
                    $filtros['fecha_desde']
                )
            )
            ->when(
                !empty($filtros['fecha_hasta']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_venta',
                    '<=',
                    $filtros['fecha_hasta']
                )
            )
            ->when(
                !empty($filtros['vendedor_id']),
                fn (Builder $query) => $query->where(
                    'vendedor_id',
                    (int) $filtros['vendedor_id']
                )
            )
            ->when(
                !empty($filtros['cliente_id']),
                fn (Builder $query) => $query->where(
                    'cliente_id',
                    (int) $filtros['cliente_id']
                )
            )
            ->when(
                $busqueda !== '',
                function (Builder $query) use ($busqueda) {
                    $like = '%' . $busqueda . '%';

                    $query->where(function (Builder $subquery) use ($like) {
                        $subquery
                            ->where('numero', 'like', $like)
                            ->orWhere('cliente_nombre_snapshot', 'like', $like)
                            ->orWhereHas(
                                'cliente',
                                fn (Builder $cliente) => $cliente->where(
                                    'nombre_completo',
                                    'like',
                                    $like
                                )
                            )
                            ->orWhereHas(
                                'vendedor',
                                fn (Builder $vendedor) => $vendedor->where(
                                    'name',
                                    'like',
                                    $like
                                )
                            );
                    });
                }
            );
    }
}
