<?php

namespace Tests\Unit;

use App\Services\NumeroLiteralService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NumeroLiteralServiceTest extends TestCase
{
    public function test_convierte_montos_bob_a_literal(): void
    {
        $servicio =
            new NumeroLiteralService();

        $this->assertSame(
            'CERO 00/100 BOLIVIANOS',
            $servicio->bolivianos('0.00')
        );

        $this->assertSame(
            'UN 00/100 BOLIVIANO',
            $servicio->bolivianos('1.00')
        );

        $this->assertSame(
            'TRES MIL OCHOCIENTOS CINCUENTA 00/100 BOLIVIANOS',
            $servicio->bolivianos('3850.00')
        );

        $this->assertSame(
            'SIETE MIL CINCUENTA 50/100 BOLIVIANOS',
            $servicio->bolivianos('7050.50')
        );

        $this->assertSame(
            'VEINTIÚN MIL CIEN 25/100 BOLIVIANOS',
            $servicio->bolivianos('21100.25')
        );
    }

    public function test_rechaza_monto_no_numerico(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new NumeroLiteralService())
            ->bolivianos('abc');
    }
}
