<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Services\RegularizacionValoracionInventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegularizacionValoracionInventarioController extends Controller
{
    public function store(
        Request $request,
        RegularizacionValoracionInventarioService $service
    ): RedirectResponse {
        $data = $request->validate([
            'producto_id' => [
                'required',
                'integer',
                'exists:productos,id',
            ],
            'almacen_id' => [
                'required',
                'integer',
                'exists:almacenes,id',
            ],
            'costo_unitario_bob' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'referencia' => [
                'required',
                'string',
                'max:120',
            ],
            'motivo' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        try {
            $regularizacion = $service->regularizar(
                (int) $request->user()->id,
                (int) $data['producto_id'],
                (int) $data['almacen_id'],
                (float) $data['costo_unitario_bob'],
                (string) $data['referencia'],
                (string) $data['motivo'],
            );

            return redirect()
                ->route(
                    'inventario.componentes.index',
                    [
                        'almacen' => $regularizacion->almacen_id,
                        'estado' => 'todos',
                    ]
                )
                ->with(
                    'success',
                    'Stock legacy valorizado correctamente sin modificar sus cantidades.'
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'regularizacion' => $exception->getMessage(),
                ]);
        }
    }
}
