<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\UnidadAdquirida;
use App\Services\IntervencionUnidadAdquiridaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntervencionUnidadAdquiridaController extends Controller
{
    public function __construct(
        private readonly IntervencionUnidadAdquiridaService $intervencionService
    ) {
    }


    /**
     * Registra un componente comprado específicamente
     * para una unidad adquirida.
     */
    public function componenteExterno(
        Request $request,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        try {
            $intervencion =
                $this->intervencionService
                    ->registrarComponenteExterno(
                        $request->user()->id,
                        $unidad->id,
                        $request->except([
                            '_token',
                        ])
                    );
        } catch (ReglaNegocioException $exception) {
            return $this->respuestaReglaNegocio(
                $request,
                $exception
            );
        }


        $mensaje =
            'El componente externo fue registrado correctamente.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'intervencion' => [
                    'id' =>
                        $intervencion->id,

                    'tipo' =>
                        $intervencion->tipo,

                    'descripcion' =>
                        $intervencion->descripcion,

                    'monto_bob' =>
                        $intervencion->monto_bob,
                ],

                'unidad' => [
                    'id' =>
                        $unidad->id,

                    'estado' =>
                        $unidad
                            ->fresh()
                            ->estado,
                ],
            ]);
        }


        return redirect()
            ->route(
                'unidades-adquiridas.show',
                $unidad
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /**
     * Asigna un componente existente en stock
     * a una unidad adquirida.
     */
    public function componenteStock(
        Request $request,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        try {
            $intervencion =
                $this->intervencionService
                    ->asignarComponenteDesdeStock(
                        $request->user()->id,
                        $unidad->id,
                        $request->except([
                            '_token',
                        ])
                    );
        } catch (ReglaNegocioException $exception) {
            return $this->respuestaReglaNegocio(
                $request,
                $exception
            );
        }


        $mensaje =
            'El componente de stock fue asignado correctamente.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'intervencion' => [
                    'id' =>
                        $intervencion->id,

                    'tipo' =>
                        $intervencion->tipo,

                    'descripcion' =>
                        $intervencion->descripcion,

                    'movimiento_inventario_id' =>
                        $intervencion
                            ->movimiento_inventario_id,
                ],

                'unidad' => [
                    'id' =>
                        $unidad->id,

                    'estado' =>
                        $unidad
                            ->fresh()
                            ->estado,
                ],
            ]);
        }


        return redirect()
            ->route(
                'unidades-adquiridas.show',
                $unidad
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /**
     * Registra un servicio o trabajo técnico
     * realizado sobre la unidad.
     */
    public function servicio(
        Request $request,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        try {
            $intervencion =
                $this->intervencionService
                    ->registrarServicio(
                        $request->user()->id,
                        $unidad->id,
                        $request->except([
                            '_token',
                        ])
                    );
        } catch (ReglaNegocioException $exception) {
            return $this->respuestaReglaNegocio(
                $request,
                $exception
            );
        }


        $mensaje =
            'El servicio técnico fue registrado correctamente.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'intervencion' => [
                    'id' =>
                        $intervencion->id,

                    'tipo' =>
                        $intervencion->tipo,

                    'descripcion' =>
                        $intervencion->descripcion,

                    'resultado' =>
                        $intervencion->resultado,

                    'monto_bob' =>
                        $intervencion->monto_bob,
                ],

                'unidad' => [
                    'id' =>
                        $unidad->id,

                    'estado' =>
                        $unidad
                            ->fresh()
                            ->estado,
                ],
            ]);
        }


        return redirect()
            ->route(
                'unidades-adquiridas.show',
                $unidad
            )
            ->with(
                'success',
                $mensaje
            );
    }

    private function respuestaReglaNegocio(
        Request $request,
        ReglaNegocioException $exception
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'intervencion' => [
                        $exception->getMessage(),
                    ],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'intervencion' => $exception->getMessage(),
            ])
            ->withInput();
    }

}