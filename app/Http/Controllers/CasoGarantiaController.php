<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Garantia;
use App\Services\CasoGarantiaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CasoGarantiaController extends Controller
{
    public function __construct(
        private readonly CasoGarantiaService $casoGarantiaService
    ) {
    }

    public function store(
        Request $request,
        Garantia $garantia
    ): RedirectResponse {
        $datos = $request->validate([
            'motivo_cliente' => [
                'required',
                'string',
                'min:5',
                'max:3000',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        try {
            $caso = $this->casoGarantiaService->abrirCaso(
                garantiaId: $garantia->id,
                usuarioId: (int) $request->user()->id,
                motivoCliente: $datos['motivo_cliente'],
                observacion: $datos['observacion'] ?? null
            );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'garantia' => $exception->getMessage(),
                ]);
        }

        return back()->with(
            'success',
            "Caso de garantía {$caso->numero} abierto correctamente."
        );
    }
}
