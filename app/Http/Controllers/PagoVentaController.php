<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Venta;
use App\Services\PagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PagoVentaController extends Controller
{
    public function __construct(
        private readonly PagoService $pagoService
    ) {
    }

    public function store(
        Request $request,
        Venta $venta
    ): RedirectResponse {
        $datos = $request->validate([
            'metodo_pago_id' => [
                'required',
                'integer',
                'exists:metodos_pago,id',
            ],
            'monto' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'referencia' => [
                'nullable',
                'string',
                'max:150',
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $this->pagoService->registrarPagoVenta(
                ventaId: $venta->id,
                metodoPagoId: (int) $datos['metodo_pago_id'],
                monto: $datos['monto'],
                registradoPorId: (int) $request->user()->id,
                referencia: $datos['referencia'] ?? null,
                observacion: $datos['observacion'] ?? null
            );
        } catch (ReglaNegocioException $exception) {
            return redirect()
                ->route('ventas.show', $venta)
                ->withInput()
                ->withErrors([
                    'pago' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with(
                'success',
                'Pago registrado correctamente.'
            );
    }
}
