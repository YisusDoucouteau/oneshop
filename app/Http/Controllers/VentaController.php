<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Venta;
use App\Services\PagoService;
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

    public function show(Venta $venta): View
    {
        $venta->load([
            'cliente',
            'vendedor',
            'reserva',
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

        return view(
            'ventas.show',
            compact('venta', 'metodosPago', 'resumenPago')
        );
    }
}
