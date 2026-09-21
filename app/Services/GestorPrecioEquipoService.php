<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\PoliticaDescuento;
use Carbon\Carbon;
use InvalidArgumentException;

class GestorPrecioEquipoService
{
    public function __construct(
        private CostoRealEquipoService $costoRealEquipoService,
        private MotorPrecioEquipoService $motorPrecioEquipoService,
        private EvaluadorPoliticaDescuentoService $evaluadorPoliticaDescuentoService
    ) {
    }

    /**
     * Realiza una evaluación integral de una propuesta de precio.
     *
     * No registra ni modifica precios.
     */
    public function evaluar(
        int $equipoId,
        float $precioPropuesto,
        ?Carbon $fechaReferencia = null
    ): array {
        $equipo = Equipo::query()
            ->with([
                'producto',
                'detalleLote',
                'costos',
                'incorporacionUnidad.unidadAdquirida',
                'precios' => function ($query) {
                    $query
                        ->where('vigente', true)
                        ->latest('vigente_desde');
                },
            ])
            ->find($equipoId);

        if (!$equipo) {
            throw new InvalidArgumentException(
                'El equipo indicado no existe.'
            );
        }

        $fechaReferencia ??= now();

        /*
        |--------------------------------------------------------------------------
        | Costo real actual
        |--------------------------------------------------------------------------
        */

        $desgloseCosto =
            $this->costoRealEquipoService
                ->calcular($equipo);

        $costoReal =
            (float) $desgloseCosto['costo_total'];

        /*
        |--------------------------------------------------------------------------
        | Precio vigente
        |--------------------------------------------------------------------------
        */

        $precioVigente = $equipo->precios->first();

        $precioPublicado = $precioVigente
            ? (float) $precioVigente->precio_publico
            : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Antigüedad comercial
        |--------------------------------------------------------------------------
        */

        $fechaDisponible = $equipo->fecha_disponible
            ? Carbon::parse($equipo->fecha_disponible)
            : $fechaReferencia->copy();

        $diasAntiguedad = (int) max(
            0,
            $fechaDisponible
                ->copy()
                ->startOfDay()
                ->diffInDays(
                    $fechaReferencia
                        ->copy()
                        ->startOfDay()
                )
        );

        /*
        |--------------------------------------------------------------------------
        | Evaluación económica
        |--------------------------------------------------------------------------
        */

        $propuesta =
            $this->motorPrecioEquipoService
                ->evaluarPropuesta(
                    $costoReal,
                    $precioPropuesto
                );

        /*
        |--------------------------------------------------------------------------
        | Política aplicable
        |--------------------------------------------------------------------------
        */

        $politica = $this->buscarPolitica(
            $equipo,
            $diasAntiguedad,
            $fechaReferencia
        );

        /*
        |--------------------------------------------------------------------------
        | Evaluación contra política
        |--------------------------------------------------------------------------
        */

        $evaluacionPolitica = null;

        if ($politica) {
            $evaluacionPolitica =
                $this->evaluadorPoliticaDescuentoService
                    ->evaluarPropuesta(
                        $precioPublicado,
                        $precioPropuesto,
                        $costoReal,
                        $diasAntiguedad,
                        $politica
                    );
        }

        return [
            'equipo_id' =>
                $equipo->id,

            'codigo_interno' =>
                $equipo->codigo_interno,

            'costo_real' =>
                round($costoReal, 2),

            'costo_desglose' =>
                $desgloseCosto,

            'precio_vigente' =>
                $precioVigente
                    ? [
                        'id' =>
                            $precioVigente->id,

                        'precio_publico' =>
                            (float) $precioVigente
                                ->precio_publico,

                        'precio_sugerido' =>
                            $precioVigente
                                ->precio_sugerido !== null
                                ? (float) $precioVigente
                                    ->precio_sugerido
                                : null,

                        'precio_minimo_autorizado' =>
                            $precioVigente
                                ->precio_minimo_autorizado !== null
                                ? (float) $precioVigente
                                    ->precio_minimo_autorizado
                                : null,
                    ]
                    : null,

            'antiguedad' => [
                'dias' =>
                    $diasAntiguedad,

                'fecha_disponible' =>
                    $fechaDisponible->toDateString(),
            ],

            'propuesta' =>
                $propuesta,

            'politica' =>
                $politica
                    ? [
                        'id' =>
                            $politica->id,

                        'codigo' =>
                            $politica->codigo,

                        'nombre' =>
                            $politica->nombre,

                        'dias_desde' =>
                            (int) $politica->dias_desde,

                        'dias_hasta' =>
                            $politica->dias_hasta !== null
                                ? (int) $politica->dias_hasta
                                : null,

                        'porcentaje_maximo' =>
                            $politica
                                ->porcentaje_maximo !== null
                                ? (float) $politica
                                    ->porcentaje_maximo
                                : null,

                        'utilidad_minima_bob' =>
                            $politica
                                ->utilidad_minima_bob !== null
                                ? (float) $politica
                                    ->utilidad_minima_bob
                                : null,

                        'requiere_autorizacion' =>
                            (bool) $politica
                                ->requiere_autorizacion,

                        'permite_precio_costo' =>
                            (bool) $politica
                                ->permite_precio_costo,
                    ]
                    : null,

            'evaluacion_politica' =>
                $evaluacionPolitica,
        ];
    }

    private function buscarPolitica(
        Equipo $equipo,
        int $diasAntiguedad,
        Carbon $fechaReferencia
    ): ?PoliticaDescuento {
        $categoriaId =
            $equipo->producto?->categoria_producto_id;

        $fecha =
            $fechaReferencia->toDateString();

        return PoliticaDescuento::query()
            ->where('activo', true)

            ->where(
                'dias_desde',
                '<=',
                $diasAntiguedad
            )

            ->where(function ($query) use (
                $diasAntiguedad
            ) {
                $query
                    ->whereNull('dias_hasta')
                    ->orWhere(
                        'dias_hasta',
                        '>=',
                        $diasAntiguedad
                    );
            })

            ->whereDate(
                'vigente_desde',
                '<=',
                $fecha
            )

            ->where(function ($query) use (
                $fecha
            ) {
                $query
                    ->whereNull('vigente_hasta')
                    ->orWhereDate(
                        'vigente_hasta',
                        '>=',
                        $fecha
                    );
            })

            ->where(function ($query) use (
                $categoriaId
            ) {
                $query->whereNull(
                    'categoria_producto_id'
                );

                if ($categoriaId !== null) {
                    $query->orWhere(
                        'categoria_producto_id',
                        $categoriaId
                    );
                }
            })

            ->orderByRaw(
                'CASE
                    WHEN categoria_producto_id IS NULL
                    THEN 1
                    ELSE 0
                 END'
            )

            ->orderByDesc('dias_desde')
            ->first();
    }
}
