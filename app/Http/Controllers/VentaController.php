<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Venta;
use App\Services\PagoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function __construct(
        private readonly PagoService $pagoService
    ) {
    }

    public function index(): View
    {
        $ventas = Venta::query()
            ->with([
                'cliente',
                'vendedor',
                'detalles.equipo.producto',
            ])
            ->latest('fecha_venta')
            ->paginate(15);

        return view('ventas.index', compact('ventas'));
    }

    public function show(
        Request $request,
        Venta $venta
    ): View {
        $venta->load([
            'cliente',
            'vendedor',
            'reserva',
            'detalles.producto',
            'detalles.equipo.producto',
            'detalles.garantia',
            'pagos.metodoPago',
            'pagos.registradoPor',
        ]);

        $metodosPago = MetodoPago::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $resumenPago = $this->pagoService
            ->obtenerResumenVenta($venta->id);

        /*
         * No se recalcula rentabilidad aquí.
         * Solo se agregan snapshots económicos ya congelados.
         */
        $economiaCompleta =
            $venta->detalles->isNotEmpty()
            &&
            $venta->detalles->every(
                fn ($detalle) =>
                    $detalle->ganancia_snapshot !== null
                    &&
                    $detalle->margen_total_snapshot !== null
            );

        $gananciaVenta =
            $economiaCompleta
                ? round(
                    $venta->detalles->sum(
                        fn ($detalle) =>
                            (float) $detalle->ganancia_snapshot
                            * (int) $detalle->cantidad
                    ),
                    2
                )
                : null;

        /*
         * El detalle interno de costo/reparto se reserva para
         * quienes administran precios. El vendedor puede ver
         * GANANCIA, pero no costo, TC ni reparto.
         */
        $puedeVerDetalleEconomico =
            (bool) $request->user()
                ?->tienePermiso('precios.modificar');

        $resumenEconomicoAdmin = null;

        if (
            $economiaCompleta
            &&
            $puedeVerDetalleEconomico
        ) {
            $resumenEconomicoAdmin = [
                'costo_total' => round(
                    $venta->detalles->sum(
                        fn ($detalle) =>
                            (float) $detalle->costo_unitario_snapshot
                            * (int) $detalle->cantidad
                    ),
                    2
                ),

                'margen_total' => round(
                    $venta->detalles->sum(
                        fn ($detalle) =>
                            (float) $detalle->margen_total_snapshot
                            * (int) $detalle->cantidad
                    ),
                    2
                ),

                'hugo' => round(
                    $venta->detalles->sum(
                        fn ($detalle) =>
                            (float) $detalle->hugo_snapshot
                            * (int) $detalle->cantidad
                    ),
                    2
                ),

                'daniel' => round(
                    $venta->detalles->sum(
                        fn ($detalle) =>
                            (float) $detalle->daniel_snapshot
                            * (int) $detalle->cantidad
                    ),
                    2
                ),

                'tienda' => round(
                    $venta->detalles->sum(
                        fn ($detalle) =>
                            (float) $detalle->tienda_snapshot
                            * (int) $detalle->cantidad
                    ),
                    2
                ),
            ];
        }

        return view(
            'ventas.show',
            compact(
                'venta',
                'metodosPago',
                'resumenPago',
                'economiaCompleta',
                'gananciaVenta',
                'puedeVerDetalleEconomico',
                'resumenEconomicoAdmin'
            )
        );
    }
}
