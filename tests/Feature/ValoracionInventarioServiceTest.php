<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\TipoMovimientoInventario;
use App\Services\ValoracionInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ValoracionInventarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private Almacen $almacen;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->almacen =
            Almacen::query()->create([
                'codigo' =>
                    'CBBA-VAL-TEST',

                'nombre' =>
                    'Cochabamba valoración test',

                'ciudad' =>
                    'Cochabamba',

                'direccion' =>
                    null,

                'principal' =>
                    false,

                'activo' =>
                    true,
            ]);

        $categoria =
            CategoriaProducto::query()
                ->create([
                    'codigo' =>
                        'COMP-VAL-TEST',

                    'nombre' =>
                        'Componentes valoración test',

                    'activo' =>
                        true,
                ]);

        $marca =
            Marca::query()->create([
                'nombre' =>
                    'Marca valoración test',

                'activo' =>
                    true,
            ]);

        $this->producto =
            Producto::query()->create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'SSD-VAL-TEST',

                'nombre' =>
                    'SSD valoración test',

                'modelo' =>
                    '512GB',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    false,

                'activo' =>
                    true,
            ]);

        TipoMovimientoInventario::query()
            ->create([
                'codigo' =>
                    'ENTRADA_VAL_TEST',

                'nombre' =>
                    'Entrada valorizada test',

                'activo' =>
                    true,
            ]);

        TipoMovimientoInventario::query()
            ->create([
                'codigo' =>
                    'SALIDA_VAL_TEST',

                'nombre' =>
                    'Salida valorizada test',

                'activo' =>
                    true,
            ]);
    }

    public function test_primera_entrada_define_costo_promedio(): void
    {
        $movimiento =
            app(
                ValoracionInventarioService::class
            )
                ->registrarEntradaValorizada(
                    productoId:
                        $this->producto->id,

                    almacenId:
                        $this->almacen->id,

                    cantidad:
                        10,

                    costoUnitarioBob:
                        80,

                    tipoMovimientoCodigo:
                        'ENTRADA_VAL_TEST'
                );

        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $this->producto->id
                )
                ->where(
                    'almacen_id',
                    $this->almacen->id
                )
                ->first();

        $this->assertNotNull(
            $existencia
        );

        $this->assertSame(
            10,
            (int)
            $existencia
                ->cantidad_disponible
        );

        $this->assertEqualsWithDelta(
            80,
            (float)
            $existencia
                ->costo_promedio_bob,
            0.000001
        );

        $this->assertEqualsWithDelta(
            80,
            (float)
            $movimiento
                ->costo_unitario_bob,
            0.000001
        );

        $this->assertEqualsWithDelta(
            800,
            (float)
            $movimiento
                ->costo_total_bob,
            0.01
        );
    }

    public function test_segunda_entrada_recalcula_promedio_ponderado(): void
    {
        $service =
            app(
                ValoracionInventarioService::class
            );

        $service
            ->registrarEntradaValorizada(
                $this->producto->id,
                $this->almacen->id,
                5,
                70,
                'ENTRADA_VAL_TEST'
            );

        $movimiento =
            $service
                ->registrarEntradaValorizada(
                    $this->producto->id,
                    $this->almacen->id,
                    5,
                    90,
                    'ENTRADA_VAL_TEST'
                );

        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $this->producto->id
                )
                ->where(
                    'almacen_id',
                    $this->almacen->id
                )
                ->first();

        $this->assertSame(
            10,
            (int)
            $existencia
                ->cantidad_disponible
        );

        $this->assertEqualsWithDelta(
            80,
            (float)
            $existencia
                ->costo_promedio_bob,
            0.000001
        );

        $this->assertEqualsWithDelta(
            80,
            (float)
            $movimiento
                ->costo_promedio_resultante_bob,
            0.000001
        );
    }

    public function test_stock_reservado_tambien_forma_parte_de_la_valoracion_fisica(): void
    {
        DB::table(
            'existencias_productos'
        )
            ->insert([
                'producto_id' =>
                    $this->producto->id,

                'almacen_id' =>
                    $this->almacen->id,

                'cantidad_disponible' =>
                    3,

                'cantidad_reservada' =>
                    2,

                'costo_promedio_bob' =>
                    100,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        app(
            ValoracionInventarioService::class
        )
            ->registrarEntradaValorizada(
                $this->producto->id,
                $this->almacen->id,
                5,
                120,
                'ENTRADA_VAL_TEST'
            );

        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $this->producto->id
                )
                ->where(
                    'almacen_id',
                    $this->almacen->id
                )
                ->first();

        $this->assertSame(
            8,
            (int)
            $existencia
                ->cantidad_disponible
        );

        $this->assertSame(
            2,
            (int)
            $existencia
                ->cantidad_reservada
        );

        $this->assertEqualsWithDelta(
            110,
            (float)
            $existencia
                ->costo_promedio_bob,
            0.000001
        );
    }

    public function test_salida_congela_costo_promedio_sin_recalcularlo(): void
    {
        $service =
            app(
                ValoracionInventarioService::class
            );

        $service
            ->registrarEntradaValorizada(
                $this->producto->id,
                $this->almacen->id,
                10,
                80,
                'ENTRADA_VAL_TEST'
            );

        $movimiento =
            $service
                ->registrarSalidaValorizada(
                    $this->producto->id,
                    $this->almacen->id,
                    2,
                    'SALIDA_VAL_TEST'
                );

        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $this->producto->id
                )
                ->where(
                    'almacen_id',
                    $this->almacen->id
                )
                ->first();

        $this->assertSame(
            8,
            (int)
            $existencia
                ->cantidad_disponible
        );

        $this->assertEqualsWithDelta(
            80,
            (float)
            $existencia
                ->costo_promedio_bob,
            0.000001
        );

        $this->assertSame(
            -2,
            $movimiento
                ->cambio_disponible
        );

        $this->assertEqualsWithDelta(
            160,
            (float)
            $movimiento
                ->costo_total_bob,
            0.01
        );
    }

    public function test_no_inventa_costo_para_stock_legacy_sin_valoracion(): void
    {
        DB::table(
            'existencias_productos'
        )
            ->insert([
                'producto_id' =>
                    $this->producto->id,

                'almacen_id' =>
                    $this->almacen->id,

                'cantidad_disponible' =>
                    4,

                'cantidad_reservada' =>
                    0,

                'costo_promedio_bob' =>
                    null,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no tiene costo promedio'
        );

        app(
            ValoracionInventarioService::class
        )
            ->registrarSalidaValorizada(
                $this->producto->id,
                $this->almacen->id,
                1,
                'SALIDA_VAL_TEST'
            );
    }

    public function test_no_mezcla_stock_legacy_sin_valoracion_con_una_compra_nueva(): void
    {
        DB::table(
            'existencias_productos'
        )
            ->insert([
                'producto_id' =>
                    $this->producto->id,

                'almacen_id' =>
                    $this->almacen->id,

                'cantidad_disponible' =>
                    4,

                'cantidad_reservada' =>
                    0,

                'costo_promedio_bob' =>
                    null,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'unidades sin valoración'
        );

        app(
            ValoracionInventarioService::class
        )
            ->registrarEntradaValorizada(
                $this->producto->id,
                $this->almacen->id,
                2,
                90,
                'ENTRADA_VAL_TEST'
            );
    }
}
