<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\DetalleVenta;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\CostoComercialActualService;
use App\Services\SugerenciaPrecioEquipoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class SugerenciaPrecioEquipoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_genera_referencia_inicial_si_aun_no_hay_ventas_comparables(): void
    {
        $equipo = $this->crearEquipo();

        $mock = Mockery::mock(
            CostoComercialActualService::class
        );

        $mock
            ->shouldReceive('calcular')
            ->once()
            ->with(Mockery::type(Equipo::class))
            ->andReturn([
                'equipo_id' => $equipo->id,
                'costo_total' => 3888.0,
                'costo_compra_actualizado' => 3888.0,
                'costos_lote' => 0.0,
                'intervenciones' => 0.0,
                'costos_posteriores' => 0.0,
                'moneda_origen' => 'USD',
                'monto_origen' => 324.0,
                'usa_tipo_cambio' => true,
                'tipo_cambio_id' => 1,
                'tipo_cambio' => 12.0,
                'fuente' => 'TIPO_CAMBIO_COMERCIAL',
            ]);

        $service = new SugerenciaPrecioEquipoService(
            $mock
        );

        $resultado = $service->sugerir($equipo);

        $this->assertSame(
            'REFERENCIA_INICIAL_ONESHOP',
            $resultado['fuente']
        );

        $this->assertSame(
            'INICIAL',
            $resultado['confianza']
        );

        $this->assertSame(
            6250.0,
            $resultado['precio_sugerido']
        );

        $this->assertSame(
            5650.0,
            $resultado['precio_minimo_sugerido']
        );

        $this->assertSame(
            0,
            $resultado['comparables']
        );

        $this->assertSame(
            'REFERENCIA_BASE',
            $resultado['origen_rango_negociacion']
        );

        $this->assertLessThan(
            $resultado['precio_sugerido'],
            $resultado['precio_minimo_sugerido']
        );

        $this->assertTrue(
            $resultado['requiere_revision']
        );
    }

    public function test_ventas_sin_rebaja_no_colapsan_el_rango_sugerido(): void
    {
        $equipo = $this->crearEquipo();

        $vendedor = User::factory()->create([
            'activo' => true,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $producto = Producto::create([
                'categoria_producto_id' =>
                    $equipo->producto->categoria_producto_id,
                'marca_id' => null,
                'codigo' => 'COMP-' . Str::uuid(),
                'nombre' => 'Laptop comparable ' . $i,
                'modelo' => 'TEST-' . $i,
                'es_serializado' => true,
                'activo' => true,
            ]);

            $comparable = Equipo::create([
                'producto_id' => $producto->id,
                'almacen_actual_id' => $equipo->almacen_actual_id,
                'estado_actual_id' => $equipo->estado_actual_id,
                'codigo_interno' => 'EQ-COMP-' . Str::uuid(),
                'fecha_registro' => now()->subDays(10 + $i),
                'fecha_disponible' => now()->subDays(10 + $i),
                'activo' => true,
            ]);

            $venta = Venta::create([
                'numero' => 'VEN-SUG-' . Str::uuid(),
                'cliente_id' => null,
                'vendedor_id' => $vendedor->id,
                'reserva_id' => null,
                'fecha_venta' => now()->subDays($i),
                'subtotal' => 4600,
                'descuento_total' => 0,
                'total' => 4600,
                'estado' => 'REGISTRADA',
                'observacion' => null,
            ]);

            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'equipo_id' => $comparable->id,
                'cantidad' => 1,
                'precio_lista_snapshot' => 4600,
                'descuento_unitario' => 0,
                'precio_unitario' => 4600,
                'costo_unitario_snapshot' => 3900,
                'subtotal' => 4600,
                'observacion' => null,
            ]);
        }

        $mock = Mockery::mock(
            CostoComercialActualService::class
        );

        $mock
            ->shouldReceive('calcular')
            ->once()
            ->andReturn([
                'equipo_id' => $equipo->id,
                'costo_total' => 3888.0,
                'costo_compra_actualizado' => 3888.0,
                'costos_lote' => 0.0,
                'intervenciones' => 0.0,
                'costos_posteriores' => 0.0,
                'moneda_origen' => 'USD',
                'monto_origen' => 324.0,
                'usa_tipo_cambio' => true,
                'tipo_cambio_id' => 1,
                'tipo_cambio' => 12.0,
                'fuente' => 'TIPO_CAMBIO_COMERCIAL',
            ]);

        $resultado = (new SugerenciaPrecioEquipoService($mock))
            ->sugerir($equipo);

        $this->assertSame(
            'HISTORIAL_CATEGORIA',
            $resultado['fuente']
        );

        $this->assertSame(3, $resultado['comparables']);
        $this->assertSame(0, $resultado['comparables_con_rebaja']);
        $this->assertSame(
            'REFERENCIA_BASE',
            $resultado['origen_rango_negociacion']
        );

        $this->assertLessThan(
            $resultado['precio_sugerido'],
            $resultado['precio_minimo_sugerido']
        );

        $this->assertGreaterThan(
            $resultado['costo_comercial'],
            $resultado['precio_minimo_sugerido']
        );
    }

    private function crearEquipo(): Equipo
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'SUG-' . Str::upper(Str::random(8)),
            'nombre' => 'Laptops sugerencia',
            'activo' => true,
        ]);

        $almacen = Almacen::create([
            'codigo' => 'ALM-SUG-' . Str::upper(Str::random(8)),
            'nombre' => 'Almacén sugerencia',
            'ciudad' => 'Oruro',
            'principal' => true,
            'activo' => true,
        ]);

        $estado = EstadoEquipo::create([
            'codigo' => 'DISP-SUG-' . Str::upper(Str::random(8)),
            'nombre' => 'Disponible',
            'es_final' => false,
            'orden' => 1,
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-' . Str::uuid(),
            'nombre' => 'Laptop sugerencia',
            'modelo' => 'TEST',
            'es_serializado' => true,
            'activo' => true,
        ]);

        return Equipo::create([
            'producto_id' => $producto->id,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'codigo_interno' => 'EQ-' . Str::uuid(),
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'activo' => true,
        ]);
    }
}
