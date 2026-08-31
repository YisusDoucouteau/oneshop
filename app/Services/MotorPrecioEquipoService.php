<?php

namespace App\Services;

use InvalidArgumentException;

class MotorPrecioEquipoService
{
    /**
     * Evalúa una propuesta de precio realizada
     * sobre el costo real de un equipo.
     *
     * Este servicio no registra ni modifica precios.
     * Su responsabilidad inicial es proporcionar
     * información objetiva para apoyar la decisión comercial.
     */
    public function evaluarPropuesta(
        float $costoTotal,
        float $precioPropuesto
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Validaciones básicas
        |--------------------------------------------------------------------------
        */

        if ($costoTotal < 0) {
            throw new InvalidArgumentException(
                'El costo total no puede ser negativo.'
            );
        }

        if ($precioPropuesto < 0) {
            throw new InvalidArgumentException(
                'El precio propuesto no puede ser negativo.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Utilidad
        |--------------------------------------------------------------------------
        |
        | Utilidad = precio propuesto - costo real
        |
        */

        $utilidad =
            $precioPropuesto
            -
            $costoTotal;

        /*
        |--------------------------------------------------------------------------
        | Porcentaje de utilidad
        |--------------------------------------------------------------------------
        |
        | La utilidad se expresa sobre el costo.
        |
        | Ejemplo:
        |
        | Costo:       4.300 Bs
        | Precio:      5.200 Bs
        | Utilidad:      900 Bs
        |
        | 900 / 4.300 * 100 = 20,93 %
        |
        */

        $porcentajeUtilidad = 0.0;

        if ($costoTotal > 0) {
            $porcentajeUtilidad =
                (
                    $utilidad
                    /
                    $costoTotal
                )
                * 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Clasificación comercial básica
        |--------------------------------------------------------------------------
        */

        $vendeACosto =
            $precioPropuesto === $costoTotal;

        $esRentable =
            $utilidad > 0;

        $generaPerdida =
            $utilidad < 0;

        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        return [
            'costo_total' =>
                round(
                    $costoTotal,
                    2
                ),

            'precio_propuesto' =>
                round(
                    $precioPropuesto,
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
                    4
                ),

            'es_rentable' =>
                $esRentable,

            'vende_a_costo' =>
                $vendeACosto,

            'genera_perdida' =>
                $generaPerdida,
        ];
    }
}