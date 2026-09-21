<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Services\CostoRealEquipoService;
use App\Services\RegistroPrecioEquipoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class RegistroPrecioEquipoServiceTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Equipo auxiliar
    |--------------------------------------------------------------------------
    */

    private function crearEquipo(): Equipo
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'LAPTOP-' . Str::uuid(),
            'nombre' => 'Laptops',
            'descripcion' => 'Categoría para pruebas de precios.',
            'activo' => true,
        ]);

        $almacen = Almacen::create([
            'codigo' => 'ORURO-TEST-' . Str::uuid(),
            'nombre' => 'Almacén Oruro Prueba',
            'ciudad' => 'Oruro',
            'direccion' => 'Dirección de prueba',
            'principal' => true,
            'activo' => true,
        ]);

        $estadoDisponible = EstadoEquipo::create([
            'codigo' => 'DISPONIBLE-' . Str::uuid(),
            'nombre' => 'Disponible',
            'descripcion' => 'Estado disponible para pruebas.',
            'es_final' => false,
            'orden' => 1,
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-' . Str::uuid(),
            'nombre' => 'Laptop prueba precio',
            'modelo' => 'TEST',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        return Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estadoDisponible->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'EQ-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'observacion' => null,
            'activo' => true,
        ]);
    }

    private function desgloseCosto(
        float $costo
    ): array {
        return [
            'equipo_id' => 1,
            'costo_base' => $costo,
            'costos_posteriores' => 0.0,
            'costo_total' => $costo,
            'fuente_base' => 'TEST',
            'completo' => true,
            'advertencias' => [],
        ];
    }

    private function servicioConCosto(
        float $costo
    ): RegistroPrecioEquipoService {
        $mock =
            Mockery::mock(
                CostoRealEquipoService::class
            );

        $mock
            ->shouldReceive('calcular')
            ->once()
            ->andReturn(
                $this->desgloseCosto($costo)
            );

        $this->app->instance(
            CostoRealEquipoService::class,
            $mock
        );

        return app(
            RegistroPrecioEquipoService::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Registro básico
    |--------------------------------------------------------------------------
    */

    public function test_registra_precio_para_un_equipo_usando_el_costo_calculado_por_el_sistema(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $precio = $servicio->registrar(
            $equipo->id,
            5200,
            5000,
            4700
        );

        $this->assertInstanceOf(
            PrecioEquipo::class,
            $precio
        );

        $this->assertDatabaseHas(
            'precios_equipos',
            [
                'equipo_id' => $equipo->id,
                'costo_total_snapshot' => 4300,
                'precio_sugerido' => 5200,
                'precio_publico' => 5000,
                'precio_minimo_autorizado' => 4700,
                'vigente' => 1,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Precio vigente
    |--------------------------------------------------------------------------
    */

    public function test_nuevo_precio_cierra_el_anterior_y_toma_un_nuevo_snapshot_de_costo(): void
    {
        $equipo = $this->crearEquipo();

        $mock =
            Mockery::mock(
                CostoRealEquipoService::class
            );

        $mock
            ->shouldReceive('calcular')
            ->twice()
            ->andReturn(
                $this->desgloseCosto(4000),
                $this->desgloseCosto(4100)
            );

        $this->app->instance(
            CostoRealEquipoService::class,
            $mock
        );

        $servicio = app(
            RegistroPrecioEquipoService::class
        );

        $primero = $servicio->registrar(
            $equipo->id,
            5000,
            4800
        );

        $segundo = $servicio->registrar(
            $equipo->id,
            5200,
            5000
        );

        $this->assertDatabaseHas(
            'precios_equipos',
            [
                'id' => $primero->id,
                'costo_total_snapshot' => 4000,
                'vigente' => 0,
            ]
        );

        $this->assertDatabaseHas(
            'precios_equipos',
            [
                'id' => $segundo->id,
                'costo_total_snapshot' => 4100,
                'vigente' => 1,
            ]
        );

        $vigente = $servicio->obtenerVigente(
            $equipo->id
        );

        $this->assertNotNull($vigente);

        $this->assertEquals(
            $segundo->id,
            $vigente->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Precio sugerido independiente del precio público
    |--------------------------------------------------------------------------
    */

    public function test_precio_sugerido_no_obliga_a_usar_el_mismo_precio_publico(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $precio = $servicio->registrar(
            $equipo->id,
            5200,
            5650
        );

        $this->assertSame(
            5200.0,
            (float) $precio->precio_sugerido
        );

        $this->assertSame(
            5650.0,
            (float) $precio->precio_publico
        );
    }

    public function test_permite_precio_publico_superior_al_sugerido(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(2200);

        $precio = $servicio->registrar(
            $equipo->id,
            2500,
            2900
        );

        $this->assertSame(
            2500.0,
            (float) $precio->precio_sugerido
        );

        $this->assertSame(
            2900.0,
            (float) $precio->precio_publico
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public function test_no_permite_precio_minimo_superior_al_publico(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->registrar(
            $equipo->id,
            5200,
            5000,
            5100
        );
    }

    public function test_no_permite_precio_publico_cero(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->registrar(
            $equipo->id,
            5200,
            0
        );
    }

    public function test_no_permite_precio_publico_negativo(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->registrar(
            $equipo->id,
            5200,
            -100
        );
    }

    public function test_no_permite_costo_calculado_negativo(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(-1);

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->registrar(
            $equipo->id,
            5200,
            5000
        );
    }

    public function test_no_permite_precio_sugerido_negativo(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->registrar(
            $equipo->id,
            -1,
            5000
        );
    }

    public function test_no_permite_precio_minimo_negativo(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->registrar(
            $equipo->id,
            5200,
            5000,
            -100
        );
    }

    public function test_no_permite_registrar_precio_para_equipo_inexistente(): void
    {
        $servicio = app(
            RegistroPrecioEquipoService::class
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->registrar(
            999999,
            5200,
            5000
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cerrar precio vigente
    |--------------------------------------------------------------------------
    */

    public function test_puede_cerrar_precio_vigente(): void
    {
        $equipo = $this->crearEquipo();

        $servicio =
            $this->servicioConCosto(4300);

        $precio = $servicio->registrar(
            $equipo->id,
            5200,
            5000
        );

        $cerrado = $servicio->cerrarVigente(
            $equipo->id
        );

        $this->assertNotNull($cerrado);

        $this->assertSame(
            $precio->id,
            $cerrado->id
        );

        $this->assertDatabaseHas(
            'precios_equipos',
            [
                'id' => $precio->id,
                'vigente' => 0,
            ]
        );

        $this->assertNull(
            $servicio->obtenerVigente(
                $equipo->id
            )
        );
    }

    public function test_cerrar_precio_vigente_devuelve_null_si_no_existe(): void
    {
        $equipo = $this->crearEquipo();

        $servicio = app(
            RegistroPrecioEquipoService::class
        );

        $resultado = $servicio->cerrarVigente(
            $equipo->id
        );

        $this->assertNull($resultado);
    }
}
