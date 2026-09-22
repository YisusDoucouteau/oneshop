<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Equipo;
use App\Models\Moneda;
use App\Services\CostoComercialActualService;
use App\Services\CostoRealEquipoService;
use App\Services\EvaluacionPropuestaPrecioService;
use App\Services\RegistroPrecioEquipoService;
use App\Services\RentabilidadRebajaService;
use App\Services\TipoCambioComercialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PrecioEquipoController extends Controller
{
    public function show(
        Request $request,
        Equipo $equipo,
        CostoRealEquipoService $costoRealEquipoService,
        CostoComercialActualService $costoComercialActualService,
        TipoCambioComercialService $tipoCambioComercialService
    ): View {
        return $this->render(
            $request,
            $equipo,
            $costoRealEquipoService,
            $costoComercialActualService,
            $tipoCambioComercialService
        );
    }

    /**
     * Evaluación rápida para la negociación.
     *
     * La fórmula SIEMPRE se calcula en backend.
     * El vendedor no recibe en JSON el reparto administrativo.
     */
    public function evaluarAjax(
        Request $request,
        Equipo $equipo,
        RentabilidadRebajaService $rentabilidadRebajaService
    ): JsonResponse {
        $datos = $request->validate([
            'precio_rebaja' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        try {
            $resultado =
                $rentabilidadRebajaService
                    ->evaluar(
                        $equipo,
                        (float) $datos['precio_rebaja']
                    );

            $esAdministrador =
                $request
                    ->user()
                    ?->tienePermiso('precios.modificar')
                ?? false;

            $respuesta = [
                'ok' => true,
                'precio_publicado' =>
                    $resultado['precio_publicado'],

                'precio_rebaja' =>
                    $resultado['precio_rebaja'],

                'costo_actualizado' =>
                    $resultado['costo_actualizado'],

                'tipo_cambio' =>
                    $resultado['tipo_cambio'],

                'moneda_origen' =>
                    $resultado['moneda_origen'],

                'ganancia' =>
                    $resultado['ganancia'],
            ];

            if ($esAdministrador) {
                $respuesta['margen_total'] =
                    $resultado['margen_total'];

                $respuesta['reparto'] =
                    $resultado['reparto'];
            }

            return response()->json(
                $respuesta
            );
        } catch (
            ReglaNegocioException
            | InvalidArgumentException $exception
        ) {
            return response()->json(
                [
                    'ok' => false,
                    'message' =>
                        $exception->getMessage(),
                ],
                422
            );
        }
    }

    /**
     * Se conserva como fallback sin JavaScript.
     */
    public function evaluar(
        Request $request,
        Equipo $equipo,
        CostoRealEquipoService $costoRealEquipoService,
        CostoComercialActualService $costoComercialActualService,
        TipoCambioComercialService $tipoCambioComercialService,
        RentabilidadRebajaService $rentabilidadRebajaService
    ): View {
        $datos = $request->validate([
            'precio_rebaja' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $evaluacion = null;
        $errorEvaluacion = null;

        try {
            $evaluacion =
                $rentabilidadRebajaService
                    ->evaluar(
                        $equipo,
                        (float) $datos['precio_rebaja']
                    );
        } catch (
            ReglaNegocioException
            | InvalidArgumentException $exception
        ) {
            $errorEvaluacion =
                $exception->getMessage();
        }

        return $this->render(
            $request,
            $equipo,
            $costoRealEquipoService,
            $costoComercialActualService,
            $tipoCambioComercialService,
            $evaluacion,
            [
                'precio_rebaja' =>
                    $datos['precio_rebaja'],
            ],
            $errorEvaluacion
        );
    }

    public function store(
        Request $request,
        Equipo $equipo,
        EvaluacionPropuestaPrecioService $evaluacionPropuestaPrecioService,
        RegistroPrecioEquipoService $registroPrecioEquipoService
    ): RedirectResponse {
        $datos = $request->validate([
            'precio_sugerido' => [
                'required',
                'numeric',
                'min:0',
            ],
            'precio_publico' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'precio_minimo_autorizado' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:precio_publico',
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        try {
            $evaluacion =
                $evaluacionPropuestaPrecioService
                    ->evaluar(
                        $equipo->id,
                        (float) $datos['precio_publico']
                    );

            if (in_array(
                $evaluacion['estado'] ?? null,
                [
                    'NO_RECOMENDADA',
                    'NO_CUMPLE_POLITICA',
                    'REQUIERE_AUTORIZACION',
                ],
                true
            )) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'precio_publico' =>
                            $this->mensajeBloqueo(
                                $evaluacion['estado']
                            ),
                    ]);
            }

            $registroPrecioEquipoService
                ->registrar(
                    $equipo->id,
                    (float) $datos['precio_sugerido'],
                    (float) $datos['precio_publico'],
                    isset($datos['precio_minimo_autorizado'])
                        && $datos['precio_minimo_autorizado'] !== ''
                            ? (float) $datos['precio_minimo_autorizado']
                            : null,
                    null,
                    $request->user()->id,
                    $datos['observacion'] ?? null
                );

            return redirect()
                ->route(
                    'precios.equipos.show',
                    $equipo
                )
                ->with(
                    'success',
                    'Precio registrado correctamente.'
                );
        } catch (
            ReglaNegocioException
            | InvalidArgumentException $exception
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'precio' =>
                        $exception->getMessage(),
                ]);
        }
    }

    /**
     * Tipo de cambio COMERCIAL global.
     *
     * No pertenece a un equipo. Una actualización USD/USDT → BOB
     * afecta las futuras evaluaciones de todos los equipos que usen
     * esa moneda.
     */
    public function actualizarTipoCambioGlobal(
        Request $request,
        TipoCambioComercialService $tipoCambioComercialService
    ): RedirectResponse {
        $datos = $request->validate([
            'moneda_origen_id' => [
                'required',
                'integer',
                'exists:monedas,id',
            ],
            'valor_tipo_cambio' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'return_to' => [
                'nullable',
                'string',
            ],
        ]);

        try {
            $tipoCambioComercialService
                ->registrar(
                    $request->user()->id,
                    (int) $datos['moneda_origen_id'],
                    (float) $datos['valor_tipo_cambio']
                );

            $destino =
                isset($datos['return_to'])
                && str_starts_with(
                    $datos['return_to'],
                    url('/')
                )
                    ? $datos['return_to']
                    : route('inventario.index');

            return redirect()
                ->to($destino)
                ->with(
                    'success',
                    'Tipo de cambio comercial actualizado correctamente.'
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'tipo_cambio' =>
                        $exception->getMessage(),
                ]);
        }
    }

    private function render(
        Request $request,
        Equipo $equipo,
        CostoRealEquipoService $costoRealEquipoService,
        CostoComercialActualService $costoComercialActualService,
        TipoCambioComercialService $tipoCambioComercialService,
        ?array $evaluacion = null,
        ?array $formularioRebaja = null,
        ?string $errorEvaluacion = null
    ): View {
        $equipo->load([
            'producto.marca',
            'producto.categoria',
            'almacenActual',
            'estadoActual',
            'precioVigente',
            'incorporacionUnidad.unidadAdquirida.moneda',
        ]);

        $costoHistorico =
            $costoRealEquipoService
                ->calcular($equipo);

        $costoComercial = null;
        $errorCostoComercial = null;

        try {
            $costoComercial =
                $costoComercialActualService
                    ->calcular($equipo);

            if (
                (float) $costoComercial['costo_total']
                <= 0
            ) {
                $errorCostoComercial =
                    'Este equipo todavía no tiene un costo válido para evaluar una rebaja.';

                $costoComercial = null;
            }
        } catch (
            ReglaNegocioException
            | InvalidArgumentException $exception
        ) {
            $errorCostoComercial =
                $exception->getMessage();
        }

        $historial =
            $equipo
                ->precios()
                ->with('aprobadoPor')
                ->orderByDesc('vigente_desde')
                ->orderByDesc('id')
                ->get();

        $esAdministrador =
            $request
                ->user()
                ?->tienePermiso('precios.modificar')
            ?? false;

        $monedaOrigen =
            $equipo
                ->incorporacionUnidad
                ?->unidadAdquirida
                ?->moneda;

        $tipoCambioVigente = null;

        if (
            $monedaOrigen
            && $monedaOrigen->codigo !== 'BOB'
        ) {
            $tipoCambioVigente =
                $tipoCambioComercialService
                    ->vigentePara($monedaOrigen);
        }

        return view(
            'precios.equipos.show',
            compact(
                'equipo',
                'costoHistorico',
                'costoComercial',
                'errorCostoComercial',
                'historial',
                'evaluacion',
                'formularioRebaja',
                'errorEvaluacion',
                'esAdministrador',
                'monedaOrigen',
                'tipoCambioVigente'
            )
        );
    }

    private function mensajeBloqueo(
        ?string $estado
    ): string {
        return match ($estado) {
            'NO_RECOMENDADA' =>
                'El precio propuesto genera una pérdida y no puede publicarse directamente.',

            'NO_CUMPLE_POLITICA' =>
                'El precio propuesto se encuentra fuera de la política comercial vigente.',

            'REQUIERE_AUTORIZACION' =>
                'El precio propuesto requiere autorización antes de ser publicado.',

            default =>
                'El precio propuesto no puede publicarse directamente.',
        };
    }
}
