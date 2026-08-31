<?php

namespace App\Services;

use App\Models\PoliticaDescuento;
use InvalidArgumentException;

class EvaluadorPoliticaDescuentoService
{
    /**
     * Evalúa una propuesta comercial contra una política de descuento.
     *
     * Parámetros:
     *
     * @param float $precioPublicado
     * @param float $precioPropuesto
     * @param float $costoReal
     * @param int $diasAntiguedad
     * @param PoliticaDescuento $politica
     *
     * @return array
     */
    public function evaluarPropuesta(
        float $precioPublicado,
        float $precioPropuesto,
        float $costoReal,
        int $diasAntiguedad,
        PoliticaDescuento $politica
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Validaciones básicas
        |--------------------------------------------------------------------------
        */

        if ($precioPublicado < 0) {
            throw new InvalidArgumentException(
                'El precio publicado no puede ser negativo.'
            );
        }

        if ($precioPropuesto < 0) {
            throw new InvalidArgumentException(
                'El precio propuesto no puede ser negativo.'
            );
        }

        if ($costoReal < 0) {
            throw new InvalidArgumentException(
                'El costo real no puede ser negativo.'
            );
        }

        if ($diasAntiguedad < 0) {
            throw new InvalidArgumentException(
                'Los días de antigüedad no pueden ser negativos.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Descuento
        |--------------------------------------------------------------------------
        |
        | El precio propuesto puede:
        |
        | - ser menor al publicado  -> existe descuento
        | - ser igual al publicado  -> no existe descuento
        | - ser mayor al publicado  -> caso excepcional permitido
        |
        | En este último caso no consideramos un "descuento negativo".
        |
        */

        $descuento = max(
            0,
            $precioPublicado - $precioPropuesto
        );

        $porcentajeDescuento = 0.0;

        if ($precioPublicado > 0) {
            $porcentajeDescuento =
                (
                    $descuento
                    /
                    $precioPublicado
                )
                * 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Utilidad real
        |--------------------------------------------------------------------------
        |
        | La utilidad se determina sobre el costo real de la unidad.
        |
        | No se confunde:
        |
        | porcentaje de descuento
        | con
        | porcentaje de utilidad.
        |
        */

        $utilidad =
            $precioPropuesto
            -
            $costoReal;

        $porcentajeUtilidad = 0.0;

        if ($costoReal > 0) {
            $porcentajeUtilidad =
                (
                    $utilidad
                    /
                    $costoReal
                )
                * 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Situación económica de la propuesta
        |--------------------------------------------------------------------------
        */

        $vendeACosto =
            $utilidad == 0.0;

        $generaPerdida =
            $utilidad < 0;

        /*
        |--------------------------------------------------------------------------
        | Verificación del rango de antigüedad
        |--------------------------------------------------------------------------
        */

        $politicaAplicada =
            $this->estaDentroDelRango(
                $diasAntiguedad,
                $politica
            );

        /*
        |--------------------------------------------------------------------------
        | Valores de la política
        |--------------------------------------------------------------------------
        */

        $porcentajeMaximo =
            $politica->porcentaje_maximo !== null
                ? (float) $politica->porcentaje_maximo
                : null;

        $utilidadMinima =
            $politica->utilidad_minima_bob !== null
                ? (float) $politica->utilidad_minima_bob
                : null;

        /*
        |--------------------------------------------------------------------------
        | Evaluación de límites
        |--------------------------------------------------------------------------
        */

        $superaDescuentoMaximo = false;

        if (
            $porcentajeMaximo !== null
            && $porcentajeDescuento > $porcentajeMaximo
        ) {
            $superaDescuentoMaximo = true;
        }

        $utilidadInferiorMinima = false;

        if (
            $utilidadMinima !== null
            && $utilidad < $utilidadMinima
        ) {
            $utilidadInferiorMinima = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Autorización
        |--------------------------------------------------------------------------
        */

        $requiereAutorizacion = false;

        /*
         * Si la política exige autorización,
         * toda propuesta queda sujeta a autorización.
         */
        if ($politica->requiere_autorizacion) {
            $requiereAutorizacion = true;
        }

        /*
         * Descuento superior al permitido.
         */
        if ($superaDescuentoMaximo) {
            $requiereAutorizacion = true;
        }

        /*
         * Venta al costo.
         */
        if (
            $vendeACosto
            && !$politica->permite_precio_costo
        ) {
            $requiereAutorizacion = true;
        }

        /*
         * Venta por debajo del costo.
         */
        if ($generaPerdida) {
            $requiereAutorizacion = true;
        }

        /*
         * No existe política aplicable para
         * la antigüedad indicada.
         */
        if (!$politicaAplicada) {
            $requiereAutorizacion = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Advertencia por utilidad inferior
        |--------------------------------------------------------------------------
        |
        | Una utilidad menor a la recomendada no necesariamente
        | bloquea la venta.
        |
        | Esto permite que Daniel pueda autorizar casos especiales,
        | por ejemplo equipos antiguos que deben salir de inventario.
        |
        */

        $esAdvertencia =
            $utilidadInferiorMinima
            && !$generaPerdida
            && !$vendeACosto;

        /*
        |--------------------------------------------------------------------------
        | Determinación final
        |--------------------------------------------------------------------------
        |
        | Una propuesta es permitida automáticamente cuando:
        |
        | - existe una política aplicable
        | - no supera el descuento máximo
        | - no vende por debajo del costo
        | - no vende al costo cuando la política no lo permite
        | - la política no exige autorización
        |
        | Una utilidad inferior al mínimo recomendado puede generar
        | advertencia sin bloquear automáticamente.
        |
        */

        $permitido =
            $politicaAplicada
            && !$superaDescuentoMaximo
            && !$generaPerdida
            && !(
                $vendeACosto
                && !$politica->permite_precio_costo
            )
            && !$politica->requiere_autorizacion;

        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        return [

            'permitido' =>
                $permitido,

            'requiere_autorizacion' =>
                $requiereAutorizacion,

            'politica_aplicada' =>
                $politicaAplicada,

            'precio_publicado' =>
                round(
                    $precioPublicado,
                    2
                ),

            'precio_propuesto' =>
                round(
                    $precioPropuesto,
                    2
                ),

            'costo_real' =>
                round(
                    $costoReal,
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
                    2
                ),

            'utilidad' =>
                round(
                    $utilidad,
                    2
                ),

            'porcentaje_utilidad' =>
                round(
                    $porcentajeUtilidad,
                    2
                ),

            'vende_a_costo' =>
                $vendeACosto,

            'genera_perdida' =>
                $generaPerdida,

            'supera_descuento_maximo' =>
                $superaDescuentoMaximo,

            'utilidad_inferior_minima' =>
                $utilidadInferiorMinima,

            'es_advertencia' =>
                $esAdvertencia,

            'dias_antiguedad' =>
                $diasAntiguedad,

            'porcentaje_maximo_politica' =>
                $porcentajeMaximo,

            'utilidad_minima_politica' =>
                $utilidadMinima,
        ];
    }

    /**
     * Determina si los días de antigüedad
     * se encuentran dentro del rango de la política.
     */
    private function estaDentroDelRango(
        int $diasAntiguedad,
        PoliticaDescuento $politica
    ): bool {
        if (
            $diasAntiguedad
            < (int) $politica->dias_desde
        ) {
            return false;
        }

        if (
            $politica->dias_hasta !== null
            && $diasAntiguedad
            > (int) $politica->dias_hasta
        ) {
            return false;
        }

        return true;
    }
}