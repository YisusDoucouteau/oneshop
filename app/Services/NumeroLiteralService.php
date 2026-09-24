<?php

namespace App\Services;

use InvalidArgumentException;

class NumeroLiteralService
{
    /**
     * Convierte un monto BOB al formato usado en la boleta:
     * TRES MIL OCHOCIENTOS CINCUENTA 00/100 BOLIVIANOS
     */
    public function bolivianos(
        string|int|float $monto
    ): string {
        if (!is_numeric($monto)) {
            throw new InvalidArgumentException(
                'El monto debe ser numérico.'
            );
        }

        $normalizado =
            number_format(
                (float) $monto,
                2,
                '.',
                ''
            );

        [
            $entero,
            $centavos,
        ] = explode(
            '.',
            $normalizado
        );

        $numero =
            (int) $entero;

        if (
            $numero < 0
            ||
            $numero > 999999999
        ) {
            throw new InvalidArgumentException(
                'El monto está fuera del rango soportado.'
            );
        }

        $literal =
            $this->convertirEntero(
                $numero
            );

        $literal =
            $this->apocoparUno(
                $literal
            );

        $moneda =
            $numero === 1
                ? 'BOLIVIANO'
                : 'BOLIVIANOS';

        return sprintf(
            '%s %s/100 %s',
            $literal,
            $centavos,
            $moneda
        );
    }

    private function convertirEntero(
        int $numero
    ): string {
        if ($numero === 0) {
            return 'CERO';
        }

        if ($numero < 30) {
            return [
                1 => 'UNO',
                2 => 'DOS',
                3 => 'TRES',
                4 => 'CUATRO',
                5 => 'CINCO',
                6 => 'SEIS',
                7 => 'SIETE',
                8 => 'OCHO',
                9 => 'NUEVE',
                10 => 'DIEZ',
                11 => 'ONCE',
                12 => 'DOCE',
                13 => 'TRECE',
                14 => 'CATORCE',
                15 => 'QUINCE',
                16 => 'DIECISÉIS',
                17 => 'DIECISIETE',
                18 => 'DIECIOCHO',
                19 => 'DIECINUEVE',
                20 => 'VEINTE',
                21 => 'VEINTIUNO',
                22 => 'VEINTIDÓS',
                23 => 'VEINTITRÉS',
                24 => 'VEINTICUATRO',
                25 => 'VEINTICINCO',
                26 => 'VEINTISÉIS',
                27 => 'VEINTISIETE',
                28 => 'VEINTIOCHO',
                29 => 'VEINTINUEVE',
            ][$numero];
        }

        if ($numero < 100) {
            $decenas = [
                3 => 'TREINTA',
                4 => 'CUARENTA',
                5 => 'CINCUENTA',
                6 => 'SESENTA',
                7 => 'SETENTA',
                8 => 'OCHENTA',
                9 => 'NOVENTA',
            ];

            $decena =
                intdiv(
                    $numero,
                    10
                );

            $unidad =
                $numero % 10;

            return $unidad === 0
                ? $decenas[$decena]
                : $decenas[$decena]
                    . ' Y '
                    . $this->convertirEntero(
                        $unidad
                    );
        }

        if ($numero === 100) {
            return 'CIEN';
        }

        if ($numero < 1000) {
            $centenas = [
                1 => 'CIENTO',
                2 => 'DOSCIENTOS',
                3 => 'TRESCIENTOS',
                4 => 'CUATROCIENTOS',
                5 => 'QUINIENTOS',
                6 => 'SEISCIENTOS',
                7 => 'SETECIENTOS',
                8 => 'OCHOCIENTOS',
                9 => 'NOVECIENTOS',
            ];

            $centena =
                intdiv(
                    $numero,
                    100
                );

            $resto =
                $numero % 100;

            return $resto === 0
                ? $centenas[$centena]
                : $centenas[$centena]
                    . ' '
                    . $this->convertirEntero(
                        $resto
                    );
        }

        if ($numero < 1000000) {
            $miles =
                intdiv(
                    $numero,
                    1000
                );

            $resto =
                $numero % 1000;

            $prefijo =
                $miles === 1
                    ? 'MIL'
                    : $this->apocoparUno(
                        $this->convertirEntero(
                            $miles
                        )
                    )
                    . ' MIL';

            return $resto === 0
                ? $prefijo
                : $prefijo
                    . ' '
                    . $this->convertirEntero(
                        $resto
                    );
        }

        $millones =
            intdiv(
                $numero,
                1000000
            );

        $resto =
            $numero % 1000000;

        $prefijo =
            $millones === 1
                ? 'UN MILLÓN'
                : $this->apocoparUno(
                    $this->convertirEntero(
                        $millones
                    )
                )
                . ' MILLONES';

        return $resto === 0
            ? $prefijo
            : $prefijo
                . ' '
                . $this->convertirEntero(
                    $resto
                );
    }

    private function apocoparUno(
        string $texto
    ): string {
        $texto =
            preg_replace(
                '/VEINTIUNO$/u',
                'VEINTIÚN',
                $texto
            )
            ?? $texto;

        $texto =
            preg_replace(
                '/ Y UNO$/u',
                ' Y UN',
                $texto
            )
            ?? $texto;

        $texto =
            preg_replace(
                '/ UNO$/u',
                ' UN',
                $texto
            )
            ?? $texto;

        if ($texto === 'UNO') {
            return 'UN';
        }

        return $texto;
    }
}
