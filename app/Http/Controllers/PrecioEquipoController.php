<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Services\CostoRealEquipoService;
use App\Services\EvaluacionPropuestaPrecioService;
use App\Services\RegistroPrecioEquipoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PrecioEquipoController extends Controller
{
    public function show(
        Equipo $equipo,
        CostoRealEquipoService $costoRealEquipoService
    ): View {
        return $this->render(
            $equipo,
            $costoRealEquipoService
        );
    }

    public function evaluar(
        Request $request,
        Equipo $equipo,
        CostoRealEquipoService $costoRealEquipoService,
        EvaluacionPropuestaPrecioService $evaluacionPropuestaPrecioService
    ): View {
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

        $evaluacion =
            $evaluacionPropuestaPrecioService
                ->evaluar(
                    $equipo->id,
                    (float) $datos['precio_publico']
                );

        return $this->render(
            $equipo,
            $costoRealEquipoService,
            $evaluacion,
            $datos
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

            /*
            |--------------------------------------------------------------------------
            | No permitir saltarse el flujo de autorización
            |--------------------------------------------------------------------------
            |
            | Fase 7.3B registra precios directamente únicamente cuando la
            | evaluación no detecta pérdida, incumplimiento ni autorización
            | pendiente. El flujo formal de aprobación se conectará después.
            |
            */

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
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'precio' =>
                        $exception->getMessage(),
                ]);
        }
    }

    private function render(
        Equipo $equipo,
        CostoRealEquipoService $costoRealEquipoService,
        ?array $evaluacion = null,
        ?array $formulario = null
    ): View {
        $equipo->load([
            'producto.marca',
            'producto.categoria',
            'almacenActual',
            'estadoActual',
            'precioVigente',
        ]);

        $costo =
            $costoRealEquipoService
                ->calcular($equipo);

        $historial =
            $equipo
                ->precios()
                ->with('aprobadoPor')
                ->orderByDesc('vigente_desde')
                ->orderByDesc('id')
                ->get();

        return view(
            'precios.equipos.show',
            compact(
                'equipo',
                'costo',
                'historial',
                'evaluacion',
                'formulario'
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
