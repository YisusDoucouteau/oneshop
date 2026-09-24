<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\CasoGarantia;
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
                    'garantia' =>
                        $exception->getMessage(),
                ]);
        }

        return back()->with(
            'success',
            "Caso de garantía {$caso->numero} abierto correctamente."
        );
    }

    public function diagnostico(
        Request $request,
        CasoGarantia $caso
    ): RedirectResponse {
        $datos = $request->validate([
            'diagnostico_final' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],

            '_caso_id' => [
                'nullable',
                'integer',
            ],
        ]);

        try {
            $caso = $this
                ->casoGarantiaService
                ->registrarDiagnostico(
                    casoId: $caso->id,
                    usuarioId:
                        (int) $request->user()->id,
                    diagnostico:
                        $datos['diagnostico_final']
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'garantia_gestion' =>
                        $exception->getMessage(),
                ]);
        }

        return back()->with(
            'success',
            "Diagnóstico registrado en {$caso->numero}."
        );
    }

    public function intervencion(
        Request $request,
        CasoGarantia $caso
    ): RedirectResponse {
        $datos = $request->validate([
            'tipo_intervencion' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],

            'descripcion' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],

            'resultado' => [
                'nullable',
                'string',
                'max:5000',
            ],

            '_caso_id' => [
                'nullable',
                'integer',
            ],
        ]);

        try {
            $this
                ->casoGarantiaService
                ->registrarIntervencion(
                    casoId: $caso->id,
                    usuarioId:
                        (int) $request->user()->id,
                    tipo:
                        $datos['tipo_intervencion'],
                    descripcion:
                        $datos['descripcion'],
                    resultado:
                        $datos['resultado'] ?? null
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'garantia_gestion' =>
                        $exception->getMessage(),
                ]);
        }

        return back()->with(
            'success',
            "Intervención registrada en {$caso->numero}."
        );
    }

    public function cerrar(
        Request $request,
        CasoGarantia $caso
    ): RedirectResponse {
        $datos = $request->validate([
            'resolucion' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],

            '_caso_id' => [
                'nullable',
                'integer',
            ],
        ]);

        try {
            $caso = $this
                ->casoGarantiaService
                ->cerrarCaso(
                    casoId: $caso->id,
                    usuarioId:
                        (int) $request->user()->id,
                    resolucion:
                        $datos['resolucion']
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'garantia_gestion' =>
                        $exception->getMessage(),
                ]);
        }

        return back()->with(
            'success',
            "Caso {$caso->numero} cerrado correctamente."
        );
    }
}
