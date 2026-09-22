<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\PrecioEquipo;
use App\Services\CostoComercialActualService;
use App\Services\RentabilidadRebajaService;
use Mockery;
use Tests\TestCase;

class RentabilidadRebajaServiceTest extends TestCase
{
    public function test_ganancia_es_el_margen_dividido_entre_tres(): void
    {
        $equipo = Mockery::mock(Equipo::class)->makePartial();
        $equipo->id = 10;

        $precio = new PrecioEquipo();
        $precio->precio_publico = 5900;

        $equipo->setRelation('precioVigente', $precio);

        $costo = Mockery::mock(CostoComercialActualService::class);
        $costo->shouldReceive('calcular')
            ->once()
            ->andReturn([
                'costo_total' => 3827.0,
                'tipo_cambio' => 12.0,
                'moneda_origen' => 'USDT',
            ]);

        $servicio = new RentabilidadRebajaService($costo);

        $resultado = $servicio->evaluar($equipo, 5300);

        $this->assertSame(1473.0, $resultado['margen_total']);
        $this->assertSame(491.0, $resultado['ganancia']);
        $this->assertSame(491.0, $resultado['reparto']['hugo']);
        $this->assertSame(491.0, $resultado['reparto']['daniel']);
        $this->assertSame(491.0, $resultado['reparto']['tienda']);
        $this->assertSame(12.0, $resultado['tipo_cambio']);
    }

    public function test_reparto_con_centavos_suma_exactamente_el_margen(): void
    {
        $equipo = Mockery::mock(Equipo::class)->makePartial();
        $equipo->id = 11;
        $equipo->setRelation('precioVigente', null);

        $costo = Mockery::mock(CostoComercialActualService::class);
        $costo->shouldReceive('calcular')->andReturn([
            'costo_total' => 4050.0,
            'tipo_cambio' => null,
            'moneda_origen' => null,
        ]);

        $resultado = (new RentabilidadRebajaService($costo))
            ->evaluar($equipo, 5000);

        $total = array_sum($resultado['reparto']);

        $this->assertEqualsWithDelta(950.0, $total, 0.001);
        $this->assertSame(316.67, $resultado['ganancia']);
    }
}
