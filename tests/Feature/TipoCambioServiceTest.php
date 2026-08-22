<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Moneda;
use App\Models\TipoCambio;
use App\Models\User;
use App\Services\TipoCambioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TipoCambioServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Cache::flush();

        Http::preventStrayRequests();
    }

    public function test_obtiene_referencia_usd_bob_y_la_guarda(): void
    {
        Http::fake([
            'https://api.frankfurter.dev/v2/rate/USD/BOB*'
                => Http::response([
                    'date' => '2026-08-21',
                    'base' => 'USD',
                    'quote' => 'BOB',
                    'rate' => 11.55,
                ], 200),
        ]);

        $resultado = app(
            TipoCambioService::class
        )->obtenerReferenciaUsdBob();

        $tipoCambio =
            $resultado['tipo_cambio'];

        $this->assertSame(
            '11.550000',
            $tipoCambio->valor
        );

        $this->assertSame(
            'API',
            $resultado['origen']
        );

        $this->assertFalse(
            $resultado['desactualizado']
        );

        $this->assertDatabaseHas(
            'tipos_cambio',
            [
                'id' => $tipoCambio->id,
                'fuente' =>
                    'FRANKFURTER_BCBO',
            ]
        );
    }

    public function test_segunda_consulta_utiliza_cache(): void
    {
        Http::fake([
            'https://api.frankfurter.dev/v2/rate/USD/BOB*'
                => Http::response([
                    'date' => '2026-08-21',
                    'base' => 'USD',
                    'quote' => 'BOB',
                    'rate' => 11.55,
                ], 200),
        ]);

        $servicio = app(
            TipoCambioService::class
        );

        $servicio->obtenerReferenciaUsdBob();

        $servicio->obtenerReferenciaUsdBob();

        Http::assertSentCount(1);

        $this->assertDatabaseCount(
            'tipos_cambio',
            1
        );
    }

    public function test_si_api_falla_usa_ultima_referencia_local(): void
    {
        $usd = Moneda::where(
            'codigo',
            'USD'
        )->firstOrFail();

        $bob = Moneda::where(
            'codigo',
            'BOB'
        )->firstOrFail();

        $tipoCambio = TipoCambio::create([
            'moneda_origen_id' => $usd->id,
            'moneda_destino_id' => $bob->id,
            'valor' => 11.40,
            'fecha_vigencia' =>
                now()->subDay(),
            'fuente' =>
                'FRANKFURTER_BCBO',
            'registrado_por_id' => null,
            'observacion' => null,
        ]);

        Http::fake([
            '*' => Http::response(
                [],
                500
            ),
        ]);

        $resultado = app(
            TipoCambioService::class
        )->obtenerReferenciaUsdBob(
            true
        );

        $this->assertSame(
            $tipoCambio->id,
            $resultado['tipo_cambio']->id
        );

        $this->assertSame(
            'BD',
            $resultado['origen']
        );

        $this->assertTrue(
            $resultado['desactualizado']
        );
    }

    public function test_si_api_falla_y_no_hay_referencia_local_lanza_excepcion(): void
    {
        Http::fake([
            '*' => Http::response(
                [],
                500
            ),
        ]);

        $this->expectException(
            ReglaNegocioException::class
        );

        app(
            TipoCambioService::class
        )->obtenerReferenciaUsdBob(
            true
        );
    }

    public function test_puede_registrar_tipo_de_cambio_realmente_aplicado(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $tipoCambio = app(
            TipoCambioService::class
        )->registrarAplicadoUsdBob(
            $usuario->id,
            11.78,
            'TC utilizado en compra de prueba.'
        );

        $this->assertSame(
            '11.780000',
            $tipoCambio->valor
        );

        $this->assertDatabaseHas(
            'tipos_cambio',
            [
                'id' => $tipoCambio->id,

                'fuente' =>
                    'MANUAL_OPERACION',

                'registrado_por_id' =>
                    $usuario->id,
            ]
        );
    }

    public function test_convierte_usd_a_bob_con_tc_aplicado(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $servicio = app(
            TipoCambioService::class
        );

        $tipoCambio =
            $servicio->registrarAplicadoUsdBob(
                $usuario->id,
                11.78
            );

        $resultado =
            $servicio->convertirABob(
                233,
                'USD',
                $tipoCambio
            );

        $this->assertSame(
            2744.74,
            $resultado
        );
    }

    public function test_bob_no_necesita_tipo_de_cambio(): void
    {
        $resultado = app(
            TipoCambioService::class
        )->convertirABob(
            2800,
            'BOB'
        );

        $this->assertSame(
            2800.00,
            $resultado
        );
    }
}