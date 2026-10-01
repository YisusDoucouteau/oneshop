<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\TipoCambio;
use App\Models\User;
use App\Services\CompraComponenteStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompraComponenteStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioOperativo;
    private Almacen $cochabamba;
    private Producto $componente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->cochabamba =
            Almacen::query()
                ->where(
                    'codigo',
                    'COCHABAMBA'
                )
                ->where(
                    'activo',
                    true
                )
                ->firstOrFail();

        $this->usuarioOperativo =
            User::factory()->create([
                'activo' =>
                    true,

                'almacen_operativo_id' =>
                    $this->cochabamba->id,
            ]);

        $rolOperativo =
            Rol::query()
                ->where(
                    'codigo',
                    'ADMIN_OPERATIVO'
                )
                ->firstOrFail();

        $this->usuarioOperativo
            ->roles()
            ->attach(
                $rolOperativo->id
            );

        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $marca =
            Marca::query()->create([
                'nombre' =>
                    'Marca Compra Stock Test',

                'descripcion' =>
                    null,

                'activo' =>
                    true,
            ]);

        $this->componente =
            Producto::query()->create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'COMP-STOCK-TEST',

                'nombre' =>
                    'Cargador compra stock test',

                'modelo' =>
                    '65W',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    false,

                'activo' =>
                    true,
            ]);
    }

    public function test_registra_compra_en_bob_y_define_promedio(): void
    {
        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        $movimiento =
            app(
                CompraComponenteStockService::class
            )
                ->registrarCompra(
                    $this->usuarioOperativo->id,
                    [
                        'producto_id' =>
                            $this->componente->id,

                        'almacen_id' =>
                            $this->cochabamba->id,

                        'cantidad' =>
                            10,

                        'moneda_id' =>
                            $bob->id,

                        'monto_total_origen' =>
                            847,

                        'fecha_compra' =>
                            '2026-10-01 10:30:00',

                        'referencia' =>
                            'FAC-CBBA-001',
                    ]
                );

        $this->assertSame(
            'COMPRA_LOCAL',
            $movimiento
                ->tipoMovimiento
                ->codigo
        );

        $this->assertSame(
            10,
            $movimiento
                ->cambio_disponible
        );

        $this->assertSame(
            10,
            $movimiento
                ->saldo_disponible_resultante
        );

        $this->assertSame(
            '84.700000',
            $movimiento
                ->costo_unitario_bob
        );

        $this->assertSame(
            '847.00',
            $movimiento
                ->costo_total_bob
        );

        $this->assertSame(
            '84.700000',
            $movimiento
                ->costo_promedio_resultante_bob
        );

        $this->assertSame(
            'COMPRA_STOCK_COMPONENTE',
            $movimiento
                ->tipo_referencia
        );

        $this->assertNull(
            $movimiento
                ->tipo_cambio_id
        );

        $this->assertSame(
            '2026-10-01 10:30:00',
            $movimiento
                ->fecha_movimiento
                ->format(
                    'Y-m-d H:i:s'
                )
        );

        $this->assertDatabaseHas(
            'existencias_productos',
            [
                'producto_id' =>
                    $this->componente->id,

                'almacen_id' =>
                    $this->cochabamba->id,

                'cantidad_disponible' =>
                    10,

                'cantidad_reservada' =>
                    0,
            ]
        );

        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $this->componente->id
                )
                ->where(
                    'almacen_id',
                    $this->cochabamba->id
                )
                ->firstOrFail();

        $this->assertEqualsWithDelta(
            84.7,
            (float)
            $existencia
                ->costo_promedio_bob,
            0.000001
        );
    }

    public function test_registra_compra_en_usd_con_tipo_cambio_historico(): void
    {
        $usd =
            Moneda::query()
                ->where(
                    'codigo',
                    'USD'
                )
                ->firstOrFail();

        $movimiento =
            app(
                CompraComponenteStockService::class
            )
                ->registrarCompra(
                    $this->usuarioOperativo->id,
                    [
                        'producto_id' =>
                            $this->componente->id,

                        'almacen_id' =>
                            $this->cochabamba->id,

                        'cantidad' =>
                            10,

                        'moneda_id' =>
                            $usd->id,

                        'monto_total_origen' =>
                            70,

                        'tipo_cambio_aplicado' =>
                            12.10,

                        'referencia' =>
                            'INV-MIA-777',
                    ]
                );

        $this->assertSame(
            '7.000000',
            $movimiento
                ->costo_unitario_origen
        );

        $this->assertSame(
            '70.00',
            $movimiento
                ->costo_total_origen
        );

        $this->assertSame(
            '84.700000',
            $movimiento
                ->costo_unitario_bob
        );

        $this->assertSame(
            '847.00',
            $movimiento
                ->costo_total_bob
        );

        $this->assertNotNull(
            $movimiento
                ->tipo_cambio_id
        );

        $tipoCambio =
            TipoCambio::query()
                ->findOrFail(
                    $movimiento
                        ->tipo_cambio_id
                );

        $this->assertSame(
            $usd->id,
            $tipoCambio
                ->moneda_origen_id
        );

        $this->assertSame(
            '12.100000',
            $tipoCambio
                ->valor
        );

        $this->assertSame(
            'MANUAL_OPERACION',
            $tipoCambio
                ->fuente
        );
    }

    public function test_segunda_compra_recalcula_promedio_ponderado(): void
    {
        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        $service =
            app(
                CompraComponenteStockService::class
            );

        $service->registrarCompra(
            $this->usuarioOperativo->id,
            [
                'producto_id' =>
                    $this->componente->id,

                'almacen_id' =>
                    $this->cochabamba->id,

                'cantidad' =>
                    5,

                'moneda_id' =>
                    $bob->id,

                'monto_total_origen' =>
                    350,
            ]
        );

        $movimiento =
            $service->registrarCompra(
                $this->usuarioOperativo->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'almacen_id' =>
                        $this->cochabamba->id,

                    'cantidad' =>
                        5,

                    'moneda_id' =>
                        $bob->id,

                    'monto_total_origen' =>
                        450,
                ]
            );

        /*
         * 5 × 70 + 5 × 90 = 800
         * 800 / 10 = 80
         */
        $this->assertSame(
            10,
            $movimiento
                ->saldo_disponible_resultante
        );

        $this->assertSame(
            '80.000000',
            $movimiento
                ->costo_promedio_resultante_bob
        );

        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $this->componente->id
                )
                ->where(
                    'almacen_id',
                    $this->cochabamba->id
                )
                ->firstOrFail();

        $this->assertEqualsWithDelta(
            80,
            (float)
            $existencia
                ->costo_promedio_bob,
            0.000001
        );
    }

    public function test_exige_tipo_cambio_para_usdt(): void
    {
        $usdt =
            Moneda::query()
                ->where(
                    'codigo',
                    'USDT'
                )
                ->firstOrFail();

        try {
            app(
                CompraComponenteStockService::class
            )
                ->registrarCompra(
                    $this->usuarioOperativo->id,
                    [
                        'producto_id' =>
                            $this->componente->id,

                        'almacen_id' =>
                            $this->cochabamba->id,

                        'cantidad' =>
                            4,

                        'moneda_id' =>
                            $usdt->id,

                        'monto_total_origen' =>
                            40,
                    ]
                );

            $this->fail(
                'Se esperaba validación porque USDT requiere tipo de cambio aplicado.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'tipo_cambio_aplicado',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'movimientos_inventario',
            0
        );

        $this->assertDatabaseMissing(
            'existencias_productos',
            [
                'producto_id' =>
                    $this->componente->id,

                'almacen_id' =>
                    $this->cochabamba->id,
            ]
        );
    }

    public function test_no_permite_comprar_producto_serializado_como_stock_cuantitativo(): void
    {
        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        $serializado =
            Producto::query()
                ->create([
                    'categoria_producto_id' =>
                        $this->componente
                            ->categoria_producto_id,

                    'marca_id' =>
                        $this->componente
                            ->marca_id,

                    'codigo' =>
                        'SERIAL-STOCK-TEST',

                    'nombre' =>
                        'Producto serializado test',

                    'modelo' =>
                        null,

                    'descripcion' =>
                        null,

                    'es_serializado' =>
                        true,

                    'activo' =>
                        true,
                ]);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'serializados'
        );

        app(
            CompraComponenteStockService::class
        )
            ->registrarCompra(
                $this->usuarioOperativo->id,
                [
                    'producto_id' =>
                        $serializado->id,

                    'almacen_id' =>
                        $this->cochabamba->id,

                    'cantidad' =>
                        1,

                    'moneda_id' =>
                        $bob->id,

                    'monto_total_origen' =>
                        100,
                ]
            );
    }

    public function test_usuario_no_puede_comprar_para_otro_almacen_operativo(): void
    {
        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        $oruro =
            Almacen::query()
                ->where(
                    'codigo',
                    'ORURO_PRINCIPAL'
                )
                ->where(
                    'activo',
                    true
                )
                ->firstOrFail();

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'almacén operativo'
        );

        app(
            CompraComponenteStockService::class
        )
            ->registrarCompra(
                $this->usuarioOperativo->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'almacen_id' =>
                        $oruro->id,

                    'cantidad' =>
                        3,

                    'moneda_id' =>
                        $bob->id,

                    'monto_total_origen' =>
                        240,
                ]
            );
    }

    public function test_usuario_sin_permiso_no_puede_registrar_compra_de_stock(): void
    {
        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        $vendedor =
            User::factory()->create([
                'activo' =>
                    true,

                'almacen_operativo_id' =>
                    $this->cochabamba->id,
            ]);

        $rolVendedor =
            Rol::query()
                ->where(
                    'codigo',
                    'VENDEDOR'
                )
                ->firstOrFail();

        $vendedor
            ->roles()
            ->attach(
                $rolVendedor->id
            );

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no tiene permiso'
        );

        app(
            CompraComponenteStockService::class
        )
            ->registrarCompra(
                $vendedor->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'almacen_id' =>
                        $this->cochabamba->id,

                    'cantidad' =>
                        3,

                    'moneda_id' =>
                        $bob->id,

                    'monto_total_origen' =>
                        240,
                ]
            );
    }
}
