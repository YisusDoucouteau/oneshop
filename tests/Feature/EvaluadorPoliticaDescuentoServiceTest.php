<?php

namespace Tests\Feature;

use App\Models\PoliticaDescuento;
use App\Services\EvaluadorPoliticaDescuentoService;
use PHPUnit\Framework\TestCase;

class EvaluadorPoliticaDescuentoServiceTest extends TestCase
{
    private EvaluadorPoliticaDescuentoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio =
            new EvaluadorPoliticaDescuentoService();
    }

    private function crearPolitica(
        float $porcentajeMaximo = 10,
        float $utilidadMinima = 300,
        bool $permitePrecioCosto = false,
        bool $requiereAutorizacion = false
    ): PoliticaDescuento {
        $politica = new PoliticaDescuento();

        $politica->id = 1;

        $politica->codigo =
            'POLITICA_TEST';

        $politica->nombre =
            'Política de prueba';

        $politica->dias_desde =
            0;

        $politica->dias_hasta =
            30;

        $politica->porcentaje_maximo =
            $porcentajeMaximo;

        $politica->utilidad_minima_bob =
            $utilidadMinima;

        $politica->permite_precio_costo =
            $permitePrecioCosto;

        $politica->requiere_autorizacion =
            $requiereAutorizacion;

        $politica->activo =
            true;

        return $politica;
    }

    public function test_acepta_descuento_dentro_del_limite(): void
    {
        $politica =
            $this->crearPolitica(
                10,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4800,
                4300,
                10,
                $politica
            );

        $this->assertTrue(
            $resultado['politica_aplicada']
        );

        $this->assertTrue(
            $resultado['permitido']
        );

        $this->assertFalse(
            $resultado['requiere_autorizacion']
        );

        $this->assertSame(
            200.0,
            $resultado['descuento']
        );

        $this->assertSame(
            500.0,
            $resultado['utilidad']
        );

        $this->assertSame(
            4.0,
            $resultado['porcentaje_descuento']
        );
    }

    public function test_detecta_descuento_superior_al_limite(): void
    {
        $politica =
            $this->crearPolitica(
                10,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4400,
                4000,
                10,
                $politica
            );

        $this->assertSame(
            600.0,
            $resultado['descuento']
        );

        $this->assertSame(
            12.0,
            $resultado['porcentaje_descuento']
        );

        $this->assertTrue(
            $resultado['requiere_autorizacion']
        );

        $this->assertFalse(
            $resultado['permitido']
        );
    }

    public function test_detecta_utilidad_inferior_a_la_minima(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4800,
                4600,
                10,
                $politica
            );

        $this->assertSame(
            200.0,
            $resultado['utilidad']
        );

        $this->assertTrue(
            $resultado['es_advertencia']
        );

        $this->assertTrue(
            $resultado['permitido']
        );
    }

    public function test_acepta_utilidad_igual_o_superior_al_minimo(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                5000,
                4700,
                10,
                $politica
            );

        $this->assertSame(
            300.0,
            $resultado['utilidad']
        );

        $this->assertFalse(
            $resultado['es_advertencia']
        );

        $this->assertFalse(
            $resultado['requiere_autorizacion']
        );

        $this->assertTrue(
            $resultado['permitido']
        );
    }

    public function test_identifica_venta_al_costo(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300,
                false
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4300,
                4300,
                20,
                $politica
            );

        $this->assertSame(
            0.0,
            $resultado['utilidad']
        );

        $this->assertTrue(
            $resultado['vende_a_costo']
        );

        $this->assertTrue(
            $resultado['requiere_autorizacion']
        );

        $this->assertFalse(
            $resultado['permitido']
        );
    }

    public function test_identifica_venta_por_debajo_del_costo(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4000,
                4300,
                20,
                $politica
            );

        $this->assertSame(
            -300.0,
            $resultado['utilidad']
        );

        $this->assertTrue(
            $resultado['genera_perdida']
        );

        $this->assertTrue(
            $resultado['requiere_autorizacion']
        );

        $this->assertFalse(
            $resultado['permitido']
        );
    }

    public function test_permite_precio_propuesto_superior_al_publicado(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                2500,
                2900,
                2200,
                20,
                $politica
            );

        $this->assertSame(
            0.0,
            $resultado['descuento']
        );

        $this->assertSame(
            700.0,
            $resultado['utilidad']
        );

        $this->assertFalse(
            $resultado['requiere_autorizacion']
        );

        $this->assertTrue(
            $resultado['permitido']
        );
    }

    public function test_calcula_correctamente_un_descuento_pequeno(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4850,
                4300,
                10,
                $politica
            );

        $this->assertSame(
            150.0,
            $resultado['descuento']
        );

        $this->assertSame(
            550.0,
            $resultado['utilidad']
        );

        $this->assertTrue(
            $resultado['permitido']
        );
    }

    public function test_no_permite_costo_negativo(): void
    {
        $politica =
            $this->crearPolitica();

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->servicio->evaluarPropuesta(
            5000,
            4800,
            -1,
            10,
            $politica
        );
    }

    public function test_no_permite_precio_propuesto_negativo(): void
    {
        $politica =
            $this->crearPolitica();

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->servicio->evaluarPropuesta(
            5000,
            -100,
            4300,
            10,
            $politica
        );
    }

    public function test_no_permite_antiguedad_negativa(): void
    {
        $politica =
            $this->crearPolitica();

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->servicio->evaluarPropuesta(
            5000,
            4800,
            4300,
            -1,
            $politica
        );
    }

    public function test_no_permite_precio_publicado_negativo(): void
    {
        $politica =
            $this->crearPolitica();

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->servicio->evaluarPropuesta(
            -5000,
            4800,
            4300,
            10,
            $politica
        );
    }

    public function test_no_confunde_utilidad_con_descuento(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                5200,
                4300,
                20,
                $politica
            );

        $this->assertSame(
            0.0,
            $resultado['descuento']
        );

        $this->assertSame(
            900.0,
            $resultado['utilidad']
        );

        $this->assertFalse(
            $resultado['genera_perdida']
        );

        $this->assertTrue(
            $resultado['permitido']
        );
    }

    public function test_no_permite_antiguedad_fuera_de_la_politica(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4800,
                4300,
                45,
                $politica
            );

        $this->assertFalse(
            $resultado['politica_aplicada']
        );

        $this->assertFalse(
            $resultado['permitido']
        );

        $this->assertTrue(
            $resultado['requiere_autorizacion']
        );
    }

    public function test_politica_puede_exigir_autorizacion(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                300,
                false,
                true
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4800,
                4300,
                10,
                $politica
            );

        $this->assertTrue(
            $resultado['requiere_autorizacion']
        );

        $this->assertFalse(
            $resultado['permitido']
        );
    }

    public function test_politica_puede_permitir_venta_al_costo(): void
    {
        $politica =
            $this->crearPolitica(
                20,
                0,
                true
            );

        $resultado =
            $this->servicio->evaluarPropuesta(
                5000,
                4300,
                4300,
                20,
                $politica
            );

        $this->assertTrue(
            $resultado['vende_a_costo']
        );

        $this->assertFalse(
            $resultado['requiere_autorizacion']
        );

        $this->assertTrue(
            $resultado['permitido']
        );
    }
}