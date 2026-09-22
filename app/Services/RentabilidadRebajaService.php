<?php

namespace App\Services;

use App\Models\Equipo;
use InvalidArgumentException;

class RentabilidadRebajaService
{
    public function __construct(
        private CostoComercialActualService $costoComercialActualService
    ) {
    }

    public function evaluar(
        Equipo|int $equipo,
        float $precioRebaja
    ): array {
        if ($precioRebaja <= 0) {
            throw new InvalidArgumentException(
                'El precio de rebaja debe ser mayor que cero.'
            );
        }

        if (is_int($equipo)) {
            $equipo = Equipo::query()
                ->with('precioVigente')
                ->findOrFail($equipo);
        } else {
            $equipo->loadMissing('precioVigente');
        }

        $costo = $this
            ->costoComercialActualService
            ->calcular($equipo);

        $costoActualizado =
            (float) $costo['costo_total'];

        if ($costoActualizado <= 0) {
            throw new InvalidArgumentException(
                'No existe un costo válido para evaluar la rebaja de este equipo.'
            );
        }

        $margenTotal = round(
            $precioRebaja
            - $costoActualizado,
            2
        );

        $ganancia = round(
            $margenTotal / 3,
            2
        );

        return [
            'equipo_id' =>
                $equipo->id,

            'precio_publicado' =>
                $equipo
                    ->precioVigente
                    ?->precio_publico !== null
                    ? (float) $equipo
                        ->precioVigente
                        ->precio_publico
                    : null,

            'precio_rebaja' =>
                round(
                    $precioRebaja,
                    2
                ),

            'costo_actualizado' =>
                $costoActualizado,

            'tipo_cambio' =>
                $costo['tipo_cambio'],

            'moneda_origen' =>
                $costo['moneda_origen'],

            /*
             * Indicador principal utilizado por OneShop.
             */
            'ganancia' =>
                $ganancia,

            /*
             * Solo para detalle administrativo.
             */
            'margen_total' =>
                $margenTotal,

            'reparto' =>
                $this->repartirExacto(
                    $margenTotal
                ),
        ];
    }

    private function repartirExacto(
        float $margenTotal
    ): array {
        $centavos =
            (int) round(
                $margenTotal * 100
            );

        $signo =
            $centavos < 0
                ? -1
                : 1;

        $absoluto =
            abs($centavos);

        $base =
            intdiv(
                $absoluto,
                3
            );

        $resto =
            $absoluto % 3;

        $partes = [
            $base,
            $base,
            $base,
        ];

        for (
            $i = 0;
            $i < $resto;
            $i++
        ) {
            $partes[$i]++;
        }

        $partes =
            array_map(
                fn (int $valor) =>
                    round(
                        ($valor * $signo) / 100,
                        2
                    ),
                $partes
            );

        return [
            'hugo' =>
                $partes[0],

            'daniel' =>
                $partes[1],

            'tienda' =>
                $partes[2],
        ];
    }
}
