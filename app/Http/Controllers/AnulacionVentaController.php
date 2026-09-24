<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Venta;
use App\Services\AnulacionVentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnulacionVentaController extends Controller
{
    public function __construct(
        private readonly AnulacionVentaService $anulacionVentaService
    ) {
    }

    public function store(
        Request $request,
        Venta $venta
    ): RedirectResponse {
        $datos = $request->validate([
            'motivo_anulacion' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ]);

        try {
            $venta = $this->anulacionVentaService
                ->anular(
                    ventaId:
                        $venta->id,

                    usuarioId:
                        (int) $request->user()->id,

                    motivo:
                        $datos['motivo_anulacion']
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'anulacion' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'ventas.show',
                $venta
            )
            ->with(
                'success',
                'Venta anulada correctamente. Los equipos regresaron a inventario.'
            );
    }
}
