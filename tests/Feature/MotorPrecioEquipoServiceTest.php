<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Services\MotorPrecioEquipoService;
use Tests\TestCase;

class MotorPrecioEquipoServiceTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Primera versión del motor comercial
    |--------------------------------------------------------------------------
    |
    | En esta etapa no guardamos ningún precio.
    |
    | El motor solamente evalúa una propuesta realizada
    | por Daniel utilizando el costo real del equipo.
    |
    */

    public function test_calcula_utilidad_de_una_propuesta(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $resultado =
            $servicio->evaluarPropuesta(
                4300,
                5200
            );

        $this->assertSame(
            4300.00,
            $resultado['costo_total']
        );

        $this->assertSame(
            5200.00,
            $resultado['precio_propuesto']
        );

        $this->assertSame(
            900.00,
            $resultado['utilidad']
        );
    }


    public function test_calcula_porcentaje_de_utilidad(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $resultado =
            $servicio->evaluarPropuesta(
                4300,
                5200
            );

        /*
         * Utilidad:
         *
         * 5200 - 4300 = 900
         *
         * Porcentaje sobre costo:
         *
         * 900 / 4300 * 100
         * = 20.9302...
         */

        $this->assertEqualsWithDelta(
            20.9302,
            $resultado['porcentaje_utilidad'],
            0.0001
        );
    }


    public function test_identifica_propuesta_por_encima_del_costo(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $resultado =
            $servicio->evaluarPropuesta(
                4300,
                5200
            );

        $this->assertTrue(
            $resultado['es_rentable']
        );

        $this->assertFalse(
            $resultado['vende_a_costo']
        );
    }


    public function test_identifica_propuesta_al_costo(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $resultado =
            $servicio->evaluarPropuesta(
                4300,
                4300
            );

        $this->assertSame(
            0.00,
            $resultado['utilidad']
        );

        $this->assertFalse(
            $resultado['es_rentable']
        );

        $this->assertTrue(
            $resultado['vende_a_costo']
        );
    }


    public function test_identifica_propuesta_por_debajo_del_costo(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $resultado =
            $servicio->evaluarPropuesta(
                4300,
                4000
            );

        $this->assertSame(
            -300.00,
            $resultado['utilidad']
        );

        $this->assertFalse(
            $resultado['es_rentable']
        );

        $this->assertFalse(
            $resultado['vende_a_costo']
        );

        $this->assertTrue(
            $resultado['genera_perdida']
        );
    }


    public function test_no_permite_costo_negativo(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $servicio->evaluarPropuesta(
            -100,
            500
        );
    }


    public function test_no_permite_precio_propuesto_negativo(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $servicio->evaluarPropuesta(
            4300,
            -100
        );
    }


    public function test_no_confunde_porcentaje_de_utilidad_con_porcentaje_de_descuento(): void
    {
        $servicio =
            app(
                MotorPrecioEquipoService::class
            );

        $resultado =
            $servicio->evaluarPropuesta(
                4300,
                5200
            );

        /*
         * Aquí todavía NO aplicamos el límite de descuento
         * del 20 %.
         *
         * Esta prueba deja claro que el motor de precio
         * primero calcula la rentabilidad de la propuesta.
         */

        $this->assertArrayHasKey(
            'porcentaje_utilidad',
            $resultado
        );

        $this->assertArrayNotHasKey(
            'porcentaje_descuento',
            $resultado
        );
    }
}