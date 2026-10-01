<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\User;
use App\Services\RegularizacionValoracionInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegularizacionValoracionInventarioServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_regulariza_stock_legacy_sin_modificar_cantidades(): void
    {
        [$usuario, $producto, $almacen] = $this->escenarioLegacy(
            disponible: 8,
            reservado: 2
        );

        $movimientosAntes =
            DB::table('movimientos_inventario')->count();

        $regularizacion =
            app(RegularizacionValoracionInventarioService::class)
                ->regularizar(
                    $usuario->id,
                    $producto->id,
                    $almacen->id,
                    75.00,
                    'INVENTARIO-INICIAL-2026',
                    'Stock existente previo a la implementación del costeo.'
                );

        $this->assertDatabaseHas('existencias_productos', [
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_disponible' => 8,
            'cantidad_reservada' => 2,
            'costo_promedio_bob' => 75.000000,
        ]);

        $this->assertSame(
            $movimientosAntes,
            DB::table('movimientos_inventario')->count()
        );

        $this->assertSame(10, $regularizacion->stock_fisico_snapshot);
        $this->assertSame('750.00', $regularizacion->valor_total_bob);
        $this->assertSame(
            '75.000000',
            $regularizacion->costo_promedio_resultante_bob
        );
    }

    public function test_stock_reservado_forma_parte_del_valor_regularizado(): void
    {
        [$usuario, $producto, $almacen] = $this->escenarioLegacy(
            disponible: 3,
            reservado: 2
        );

        $regularizacion =
            app(RegularizacionValoracionInventarioService::class)
                ->regularizar(
                    $usuario->id,
                    $producto->id,
                    $almacen->id,
                    20.00,
                    'CONTEO-FISICO-001',
                    'Regularización por conteo físico.'
                );

        $this->assertSame(5, $regularizacion->stock_fisico_snapshot);
        $this->assertSame('100.00', $regularizacion->valor_total_bob);
    }

    public function test_no_permite_regularizar_existencia_ya_valorizada(): void
    {
        [$usuario, $producto, $almacen] = $this->escenarioLegacy();

        DB::table('existencias_productos')
            ->where('producto_id', $producto->id)
            ->where('almacen_id', $almacen->id)
            ->update([
                'costo_promedio_bob' => 15.50,
            ]);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage(
            'La existencia ya tiene una valoración registrada.'
        );

        app(RegularizacionValoracionInventarioService::class)
            ->regularizar(
                $usuario->id,
                $producto->id,
                $almacen->id,
                20.00,
                'REF-001',
                'No debería permitirse.'
            );
    }

    public function test_no_permite_regularizar_stock_fisico_en_cero(): void
    {
        [$usuario, $producto, $almacen] = $this->escenarioLegacy(
            disponible: 0,
            reservado: 0
        );

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage(
            'La existencia no tiene stock físico para regularizar.'
        );

        app(RegularizacionValoracionInventarioService::class)
            ->regularizar(
                $usuario->id,
                $producto->id,
                $almacen->id,
                20.00,
                'REF-002',
                'No hay stock físico.'
            );
    }

    public function test_no_permite_regularizar_producto_serializado(): void
    {
        [$usuario, $producto, $almacen] = $this->escenarioLegacy();

        $producto->update([
            'es_serializado' => true,
        ]);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage(
            'Los productos serializados no se regularizan mediante existencias cuantitativas.'
        );

        app(RegularizacionValoracionInventarioService::class)
            ->regularizar(
                $usuario->id,
                $producto->id,
                $almacen->id,
                20.00,
                'REF-003',
                'Producto serializado.'
            );
    }

    public function test_usuario_operativo_no_puede_regularizar_otro_almacen(): void
    {
        [$usuario, $producto, $almacen] = $this->escenarioLegacy();

        $otroAlmacen =
            Almacen::query()
                ->where('activo', true)
                ->whereKeyNot($almacen->id)
                ->firstOrFail();

        DB::table('existencias_productos')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $otroAlmacen->id,
            'cantidad_disponible' => 2,
            'cantidad_reservada' => 0,
            'costo_promedio_bob' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage(
            'No puede regularizar stock de un almacén distinto a su almacén operativo.'
        );

        app(RegularizacionValoracionInventarioService::class)
            ->regularizar(
                $usuario->id,
                $producto->id,
                $otroAlmacen->id,
                20.00,
                'REF-004',
                'Intento fuera del almacén operativo.'
            );
    }

    public function test_no_permite_regularizar_dos_veces(): void
    {
        [$usuario, $producto, $almacen] = $this->escenarioLegacy();

        $service =
            app(RegularizacionValoracionInventarioService::class);

        $service->regularizar(
            $usuario->id,
            $producto->id,
            $almacen->id,
            20.00,
            'REF-005',
            'Primera regularización.'
        );

        $this->expectException(ReglaNegocioException::class);

        $service->regularizar(
            $usuario->id,
            $producto->id,
            $almacen->id,
            25.00,
            'REF-006',
            'Segundo intento.'
        );
    }

    private function escenarioLegacy(
        int $disponible = 4,
        int $reservado = 1
    ): array {
        $almacen =
            Almacen::query()
                ->where('codigo', 'COCHABAMBA')
                ->firstOrFail();

        $usuario =
            User::factory()->create([
                'almacen_operativo_id' => $almacen->id,
            ]);

        $categoria =
            CategoriaProducto::query()
                ->where('codigo', 'COMPONENTE')
                ->firstOrFail();

        $producto =
            Producto::query()->create([
                'categoria_producto_id' => $categoria->id,
                'marca_id' => null,
                'codigo' => 'CMP-LEGACY-' . fake()->unique()->numerify('#####'),
                'nombre' => 'Componente legacy',
                'modelo' => null,
                'descripcion' => null,
                'es_serializado' => false,
                'activo' => true,
            ]);

        DB::table('existencias_productos')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_disponible' => $disponible,
            'cantidad_reservada' => $reservado,
            'costo_promedio_bob' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            $usuario,
            $producto,
            $almacen,
        ];
    }
}
