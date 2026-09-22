<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PoliticaDescuento;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\User;
use App\Services\CostoComercialActualService;
use App\Services\ValidadorVentaPrecioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ValidadorVentaPrecioServiceTest extends TestCase
{
    use RefreshDatabase;

    private function crearEquipo(): Equipo
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'LAPTOP',
            'nombre' => 'Laptop',
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'marca_id' =>
                null,

            'codigo' =>
                'PROD-' . Str::uuid(),

            'nombre' =>
                'Laptop prueba',

            'modelo' =>
                'TEST',

            'descripcion' =>
                null,

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);

        $almacen = Almacen::create([
            'codigo' =>
                'ORU',

            'nombre' =>
                'Almacen Oruro',

            'ciudad' =>
                'Oruro',

            'direccion' =>
                null,

            'principal' =>
                true,

            'activo' =>
                true,
        ]);

        $estado = EstadoEquipo::create([
            'codigo' =>
                'DISPONIBLE',

            'nombre' =>
                'Disponible',

            'descripcion' =>
                null,

            'es_final' =>
                false,

            'orden' =>
                1,

            'activo' =>
                true,
        ]);

        return Equipo::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estado->id,

            'codigo_interno' =>
                'EQ-' . Str::uuid(),

            'serial_fabricante' =>
                null,

            'fecha_registro' =>
                now(),

            'fecha_disponible' =>
                now(),

            'observacion' =>
                null,

            'activo' =>
                true,
        ]);
    }

    private function crearPrecio(
        Equipo $equipo
    ): PrecioEquipo {
        return PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,

            'costo_total_snapshot' =>
                3500,

            'precio_sugerido' =>
                5200,

            'precio_publico' =>
                5000,

            'precio_minimo_autorizado' =>
                4500,

            'vigente_desde' =>
                now(),

            'vigente' =>
                true,
        ]);
    }

    private function crearPolitica(): PoliticaDescuento
    {
        return PoliticaDescuento::create([
            'codigo' =>
                'GENERAL',

            'nombre' =>
                'General',

            'categoria_producto_id' =>
                null,

            'dias_desde' =>
                0,

            'dias_hasta' =>
                null,

            'porcentaje_maximo' =>
                10,

            'utilidad_minima_bob' =>
                500,

            'permite_precio_costo' =>
                false,

            'requiere_autorizacion' =>
                true,

            'vigente_desde' =>
                now(),

            'activo' =>
                true,
        ]);
    }

    public function test_permite_precio_publicado(): void
    {
        $equipo =
            $this->crearEquipo();

        $this->crearPrecio(
            $equipo
        );

        $resultado =
            app(
                ValidadorVentaPrecioService::class
            )->validar(
                $equipo->id,
                5000
            );

        $this->assertTrue(
            $resultado['permitido']
        );

        $this->assertSame(
            3500.0,
            $resultado['costo_actual']
        );
    }

    public function test_permite_descuento_dentro_de_politica(): void
    {
        $equipo =
            $this->crearEquipo();

        $this->crearPrecio(
            $equipo
        );

        $this->crearPolitica();

        $resultado =
            app(
                ValidadorVentaPrecioService::class
            )->validar(
                $equipo->id,
                4700
            );

        $this->assertTrue(
            $resultado['permitido']
        );
    }

    public function test_detecta_descuento_fuera_de_politica(): void
    {
        $equipo =
            $this->crearEquipo();

        $this->crearPrecio(
            $equipo
        );

        $this->crearPolitica();

        $resultado =
            app(
                ValidadorVentaPrecioService::class
            )->validar(
                $equipo->id,
                4000
            );

        $this->assertFalse(
            $resultado['permitido']
        );

        $this->assertTrue(
            $resultado['requiere_aprobacion']
        );
    }

    public function test_no_permita_equipo_inexistente(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        app(
            ValidadorVentaPrecioService::class
        )->validar(
            999999,
            5000
        );
    }

    public function test_crea_solicitud_de_descuento_cuando_requiere_aprobacion(): void
    {
        $equipo =
            $this->crearEquipo();

        $precio =
            $this->crearPrecio(
                $equipo
            );

        $this->crearPolitica();

        $usuario =
            User::create([
                'name' =>
                    'Vendedor prueba',

                'email' =>
                    'vendedor@test.com',

                'password' =>
                    bcrypt('password'),

                'activo' =>
                    true,
            ]);

        $resultado =
            app(
                ValidadorVentaPrecioService::class
            )->validar(
                $equipo->id,
                4000,
                null,
                $usuario->id
            );

        $this->assertFalse(
            $resultado['permitido']
        );

        $this->assertTrue(
            $resultado['requiere_aprobacion']
        );

        $this->assertNotNull(
            $resultado['solicitud']
        );

        $this->assertDatabaseHas(
            'solicitudes_descuentos',
            [
                'precio_equipo_id' =>
                    $precio->id,

                'estado' =>
                    'PENDIENTE',

                'costo_total_snapshot' =>
                    '3500.00',

                'utilidad_proyectada' =>
                    '500.00',
            ]
        );
    }

    public function test_usa_costo_comercial_actual_y_no_snapshot_antiguo_del_precio(): void
    {
        $equipo =
            $this->crearEquipo();

        $precio =
            $this->crearPrecio(
                $equipo
            );

        /*
         * El precio fue registrado cuando el costo era Bs 3.500.
         */
        $this->assertSame(
            '3500.00',
            (string)
            $precio->costo_total_snapshot
        );

        $this->crearPolitica();

        /*
         * Simulamos que hoy el costo comercial es Bs 4.600.
         * Con el costo viejo la utilidad sería Bs 1.500 y aprobaría.
         * Con el costo vigente la utilidad real es Bs 400,
         * por lo que debe quedar fuera de política.
         */
        $costoActual =
            Mockery::mock(
                CostoComercialActualService::class
            );

        $costoActual
            ->shouldReceive('calcular')
            ->once()
            ->withArgs(
                fn ($equipoRecibido) =>
                    $equipoRecibido->id
                    ===
                    $equipo->id
            )
            ->andReturn([
                'equipo_id' =>
                    $equipo->id,

                'costo_total' =>
                    4600.0,

                'costo_compra_actualizado' =>
                    4200.0,

                'costos_lote' =>
                    200.0,

                'intervenciones' =>
                    100.0,

                'costos_posteriores' =>
                    100.0,

                'moneda_origen' =>
                    'USD',

                'monto_origen' =>
                    350.0,

                'usa_tipo_cambio' =>
                    true,

                'tipo_cambio_id' =>
                    88,

                'tipo_cambio' =>
                    12.0,

                'fuente' =>
                    'TIPO_CAMBIO_COMERCIAL',
            ]);

        $this->app->instance(
            CostoComercialActualService::class,
            $costoActual
        );

        $resultado =
            app(
                ValidadorVentaPrecioService::class
            )->validar(
                $equipo->id,
                5000
            );

        $this->assertFalse(
            $resultado['permitido']
        );

        $this->assertTrue(
            $resultado['requiere_aprobacion']
        );

        $this->assertSame(
            4600.0,
            $resultado['costo_actual']
        );

        $this->assertSame(
            400.0,
            $resultado['utilidad']
        );

        $this->assertSame(
            88,
            $resultado['tipo_cambio_id']
        );

        $this->assertSame(
            12.0,
            $resultado['tipo_cambio']
        );

        $this->assertSame(
            'USD',
            $resultado['moneda_origen']
        );

        $this->assertSame(
            'TIPO_CAMBIO_COMERCIAL',
            $resultado['fuente_costo']
        );
    }

    public function test_solicitud_congela_costo_comercial_actual_usado_en_la_decision(): void
    {
        $equipo =
            $this->crearEquipo();

        $precio =
            $this->crearPrecio(
                $equipo
            );

        $this->crearPolitica();

        $usuario =
            User::create([
                'name' =>
                    'Vendedor costo actual',

                'email' =>
                    'vendedor-costo-' .
                    Str::uuid() .
                    '@test.com',

                'password' =>
                    bcrypt('password'),

                'activo' =>
                    true,
            ]);

        $costoActual =
            Mockery::mock(
                CostoComercialActualService::class
            );

        $costoActual
            ->shouldReceive('calcular')
            ->once()
            ->andReturn([
                'equipo_id' =>
                    $equipo->id,

                'costo_total' =>
                    4600.0,

                'costo_compra_actualizado' =>
                    4600.0,

                'costos_lote' =>
                    0.0,

                'intervenciones' =>
                    0.0,

                'costos_posteriores' =>
                    0.0,

                'moneda_origen' =>
                    'USD',

                'monto_origen' =>
                    383.33,

                'usa_tipo_cambio' =>
                    true,

                'tipo_cambio_id' =>
                    99,

                'tipo_cambio' =>
                    12.0,

                'fuente' =>
                    'TIPO_CAMBIO_COMERCIAL',
            ]);

        $this->app->instance(
            CostoComercialActualService::class,
            $costoActual
        );

        $resultado =
            app(
                ValidadorVentaPrecioService::class
            )->validar(
                equipoId:
                    $equipo->id,

                precioPropuesto:
                    5000,

                vendedorId:
                    $usuario->id
            );

        $this->assertFalse(
            $resultado['permitido']
        );

        $this->assertNotNull(
            $resultado['solicitud']
        );

        $this->assertDatabaseHas(
            'solicitudes_descuentos',
            [
                'precio_equipo_id' =>
                    $precio->id,

                'costo_total_snapshot' =>
                    '4600.00',

                'utilidad_proyectada' =>
                    '400.00',

                'estado' =>
                    'PENDIENTE',
            ]
        );
    }
}
