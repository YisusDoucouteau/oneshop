<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReporteMargenVentaService
{
    /**
     * Obtiene el margen económico de una venta utilizando los snapshots
     * históricos guardados al momento de registrar la operación.
     */
    public function calcularMargenVenta(int $ventaId): array
    {
        $venta = Venta::query()
            ->with('detalles')
            ->find($ventaId);

        if (!$venta) {
            throw new \InvalidArgumentException(
                'La venta no existe.'
            );
        }

        $costoTotal = $venta->detalles->sum(
            fn ($detalle) =>
                (float) $detalle->costo_unitario_snapshot
                * (int) $detalle->cantidad
        );

        $ventaTotal = (float) $venta->total;
        $utilidad = $ventaTotal - $costoTotal;

        $margen = $ventaTotal > 0
            ? ($utilidad / $ventaTotal) * 100
            : 0;

        return [
            'venta_id' => $venta->id,
            'numero' => $venta->numero,
            'total_venta' => round($ventaTotal, 2),
            'costo_total' => round($costoTotal, 2),
            'utilidad' => round($utilidad, 2),
            'porcentaje_margen' => round($margen, 2),
            'tiene_descuento' => $venta->detalles->contains(
                fn ($detalle) => (float) $detalle->descuento_unitario > 0
            ),
        ];
    }

    /**
     * Obtiene resumen de margen por vendedor.
     * Por defecto solo considera ventas registradas.
     */
    public function margenPorVendedor(array $filtros = []): Collection
    {
        $estado = $filtros['estado_venta'] ?? 'REGISTRADA';

        return Venta::query()
            ->with([
                'vendedor',
                'detalles',
            ])
            ->when(
                in_array($estado, ['REGISTRADA', 'ANULADA'], true),
                fn (Builder $query) => $query->where('estado', $estado),
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
            ->get()
            ->groupBy('vendedor_id')
            ->map(function ($ventas) {
                $totalVentas = (float) $ventas->sum('total');

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
                    'ventas_realizadas' => $ventas->count(),
                    'total_vendido' => round($totalVentas, 2),
                    'utilidad_generada' => round($totalVentas - $costo, 2),
                ];
            })
            ->sortByDesc('total_vendido')
            ->values();
    }
}
