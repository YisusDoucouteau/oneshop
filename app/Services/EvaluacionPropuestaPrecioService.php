<?php

namespace App\Services;

use App\Models\Equipo;
use InvalidArgumentException;

class EvaluacionPropuestaPrecioService
{
    public function __construct(
        private GestorPrecioEquipoService $gestorPrecioEquipoService
    ) {
    }

    /**
     * Evalúa una propuesta comercial para un equipo.
     */
    public function evaluar(
        int $equipoId,
        float $precioPropuesto
    ): array {
        if ($precioPropuesto < 0) {
            throw new InvalidArgumentException(
                'El precio propuesto no puede ser negativo.'
            );
        }

        $equipo = Equipo::query()
            ->find($equipoId);

        if (!$equipo) {
            throw new InvalidArgumentException(
                'El equipo indicado no existe.'
            );
        }

        $resultado =
            $this->gestorPrecioEquipoService
                ->evaluar(
                    $equipoId,
                    $precioPropuesto
                );

        /*
        |--------------------------------------------------------------------------
        | Precio publicado
        |--------------------------------------------------------------------------
        */

        $precioPublicado =
            $resultado['precio_vigente']['precio_publico']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Costo real
        |--------------------------------------------------------------------------
        */

        $costoReal =
            (float) (
                $resultado['costo_real']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Descuento o incremento
        |--------------------------------------------------------------------------
        |
        | Si el precio propuesto es menor al publicado:
        |
        |     publicado - propuesto = descuento
        |
        | Si el precio propuesto es mayor al publicado:
        |
        |     publicado - propuesto = valor negativo
        |
        | El valor negativo representa un incremento.
        */

        $descuento = 0.0;
        $porcentajeDescuento = 0.0;

        if ($precioPublicado !== null) {
            $descuento =
                $precioPublicado
                - $precioPropuesto;

            if ($precioPublicado > 0) {
                $porcentajeDescuento =
                    (
                        $descuento
                        / $precioPublicado
                    ) * 100;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Ganancia
        |--------------------------------------------------------------------------
        */

        $ganancia =
            $precioPropuesto
            - $costoReal;

        $porcentajeUtilidad = 0.0;

        if ($costoReal > 0) {
            $porcentajeUtilidad =
                (
                    $ganancia
                    / $costoReal
                ) * 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Resultado económico
        |--------------------------------------------------------------------------
        */

        $resultadoEconomico =
            $this->determinarResultadoEconomico(
                $ganancia
            );

        /*
        |--------------------------------------------------------------------------
        | Evaluación de política
        |--------------------------------------------------------------------------
        */

        $evaluacionPolitica =
            $resultado['evaluacion_politica']
            ?? null;

        $requiereAutorizacion =
            $evaluacionPolitica['requiere_autorizacion']
            ?? false;

        /*
        |--------------------------------------------------------------------------
        | Cumplimiento de política
        |--------------------------------------------------------------------------
        |
        | Debemos distinguir entre:
        |
        | - incumplimiento real;
        | - propuesta que puede aprobarse;
        | - propuesta que requiere autorización;
        | - ausencia de política.
        */

        $cumplePolitica = null;

        if ($evaluacionPolitica !== null) {

            $permitido =
                (bool) (
                    $evaluacionPolitica['permitido']
                    ?? false
                );

            $generaPerdida =
                (bool) (
                    $evaluacionPolitica['genera_perdida']
                    ?? false
                );

            $superaDescuentoMaximo =
                (bool) (
                    $evaluacionPolitica[
                        'supera_descuento_maximo'
                    ]
                    ?? false
                );

            $vendeACosto =
                (bool) (
                    $evaluacionPolitica[
                        'vende_a_costo'
                    ]
                    ?? false
                );

            $politicaAplicada =
                (bool) (
                    $evaluacionPolitica[
                        'politica_aplicada'
                    ]
                    ?? false
                );

            $permitePrecioCosto =
                (bool) (
                    $resultado['politica'][
                        'permite_precio_costo'
                    ]
                    ?? false
                );

            /*
            |--------------------------------------------------------------------------
            | Sin política
            |--------------------------------------------------------------------------
            */

            if (!$politicaAplicada) {

                $cumplePolitica = null;
            }

            /*
            |--------------------------------------------------------------------------
            | Pérdida
            |--------------------------------------------------------------------------
            */

            elseif ($generaPerdida) {

                /*
                 * Una pérdida nunca se considera cumplimiento
                 * de la política.
                 */

                $cumplePolitica = false;
            }

            /*
            |--------------------------------------------------------------------------
            | Descuento superior al permitido
            |--------------------------------------------------------------------------
            */

            elseif ($superaDescuentoMaximo) {

                /*
                 * Superar el porcentaje máximo de descuento
                 * constituye un incumplimiento real.
                 *
                 * La autorización administrativa puede existir
                 * como mecanismo posterior, pero la propuesta
                 * sigue estando fuera de la política.
                 */

                $cumplePolitica = false;
            }

            /*
            |--------------------------------------------------------------------------
            | Venta al costo no permitida
            |--------------------------------------------------------------------------
            */

            elseif (
                $vendeACosto
                && !$permitePrecioCosto
            ) {

                $cumplePolitica = false;
            }

            /*
            |--------------------------------------------------------------------------
            | Propuesta permitida
            |--------------------------------------------------------------------------
            */

            elseif ($permitido) {

                $cumplePolitica = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Requiere autorización
            |--------------------------------------------------------------------------
            |
            | La propuesta no tiene una violación económica grave,
            | pero la política exige revisión/aprobación.
            */

            elseif (
                $requiereAutorizacion
                && !$generaPerdida
            ) {

                $cumplePolitica = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Caso no contemplado
            |--------------------------------------------------------------------------
            */

            else {

                $cumplePolitica = false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Estado final
        |--------------------------------------------------------------------------
        */

        $estado =
            $this->determinarEstado(
                $ganancia,
                $cumplePolitica,
                $requiereAutorizacion
            );

        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        return [

            'equipo_id' =>
                $equipo->id,

            'codigo_interno' =>
                $equipo->codigo_interno,

            'precio_publicado' =>
                $precioPublicado !== null
                    ? round(
                        (float) $precioPublicado,
                        2
                    )
                    : null,

            'precio_propuesto' =>
                round(
                    $precioPropuesto,
                    2
                ),

            'descuento' =>
                round(
                    $descuento,
                    2
                ),

            'porcentaje_descuento' =>
                round(
                    $porcentajeDescuento,
                    4
                ),

            'costo_real' =>
                round(
                    $costoReal,
                    2
                ),

            'ganancia' =>
                round(
                    $ganancia,
                    2
                ),

            'porcentaje_utilidad' =>
                round(
                    $porcentajeUtilidad,
                    4
                ),

            'resultado_economico' =>
                $resultadoEconomico,

            'antiguedad' =>
                $resultado['antiguedad'],

            'politica' =>
                $resultado['politica'],

            'evaluacion_politica' =>
                $evaluacionPolitica,

            'requiere_autorizacion' =>
                (bool) $requiereAutorizacion,

            'cumple_politica' =>
                $cumplePolitica,

            'estado' =>
                $estado,
        ];
    }

    /**
     * Determina la situación económica de la propuesta.
     */
    private function determinarResultadoEconomico(
        float $ganancia
    ): string {
        if ($ganancia > 0) {
            return 'GANANCIA';
        }

        if ($ganancia == 0.0) {
            return 'AL_COSTO';
        }

        return 'PERDIDA';
    }

    /**
     * Determina el estado comercial final.
     */
    private function determinarEstado(
        float $ganancia,
        ?bool $cumplePolitica,
        bool $requiereAutorizacion
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Pérdida
        |--------------------------------------------------------------------------
        */

        if ($ganancia < 0) {
            return 'NO_RECOMENDADA';
        }

        /*
        |--------------------------------------------------------------------------
        | Incumplimiento real
        |--------------------------------------------------------------------------
        */

        if ($cumplePolitica === false) {
            return 'NO_CUMPLE_POLITICA';
        }

        /*
        |--------------------------------------------------------------------------
        | Requiere autorización
        |--------------------------------------------------------------------------
        */

        if ($requiereAutorizacion) {
            return 'REQUIERE_AUTORIZACION';
        }

        /*
        |--------------------------------------------------------------------------
        | Sin política
        |--------------------------------------------------------------------------
        */

        if ($cumplePolitica === null) {
            return 'REQUIERE_REVISION';
        }

        /*
        |--------------------------------------------------------------------------
        | Aprobable
        |--------------------------------------------------------------------------
        */

        return 'APROBABLE';
    }
}