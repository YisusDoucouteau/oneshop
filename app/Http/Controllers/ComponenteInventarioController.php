<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Services\CompraComponenteStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComponenteInventarioController extends Controller
{
    public function storeCompra(
        Request $request,
        CompraComponenteStockService $service
    ): RedirectResponse {
        try {
            $movimiento =
                $service->registrarCompra(
                    $request->user()->id,
                    $request->all()
                );

            return back()->with(
                'success',
                'Compra de componente registrada correctamente. '
                .'Nuevo saldo disponible: '
                .$movimiento->saldo_disponible_resultante
                .'.'
            );
        } catch (
            ReglaNegocioException $exception
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'compra_componente' =>
                        $exception->getMessage(),
                ]);
        }
    }
}
