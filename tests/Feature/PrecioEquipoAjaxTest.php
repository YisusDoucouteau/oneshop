<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Services\CostoComercialActualService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PrecioEquipoAjaxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendedor;
    private Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin =
            $this->usuarioConRol(
                'ADMIN_OPERATIVO'
            );

        $this->vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $this->equipo =
            $this->crearEquipo();

        PrecioEquipo::create([
            'equipo_id' =>
                $this->equipo->id,

            'tipo_cambio_id' =>
                null,

            'costo_total_snapshot' =>
                3827,

            'precio_sugerido' =>
                5900,

            'precio_publico' =>
                5900,

            'precio_minimo_autorizado' =>
                5200,

            'vigente_desde' =>
                now(),

            'vigente' =>
                true,

            'aprobado_por_id' =>
                $this->admin->id,
        ]);
    }

    public function test_vendedor_recibe_ganancia_pero_no_reparto_en_json(): void
    {
        $this->mockCosto(3827);

        $response =
            $this
                ->actingAs($this->vendedor)
                ->postJson(
                    route(
                        'precios.equipos.evaluar-json',
                        $this->equipo
                    ),
                    [
                        'precio_rebaja' =>
                            5300,
                    ]
                );

        $response
            ->assertOk()
            ->assertJson([
                'ok' =>
                    true,

                'ganancia' =>
                    491.0,
            ]);

        $response
            ->assertJsonMissingPath(
                'reparto'
            )
            ->assertJsonMissingPath(
                'margen_total'
            );
    }

    public function test_admin_recibe_reparto_administrativo(): void
    {
        $this->mockCosto(3827);

        $this
            ->actingAs($this->admin)
            ->postJson(
                route(
                    'precios.equipos.evaluar-json',
                    $this->equipo
                ),
                [
                    'precio_rebaja' =>
                        5300,
                ]
            )
            ->assertOk()
            ->assertJson([
                'ok' =>
                    true,

                'ganancia' =>
                    491.0,

                'margen_total' =>
                    1473.0,

                'reparto' => [
                    'hugo' =>
                        491.0,

                    'daniel' =>
                        491.0,

                    'tienda' =>
                        491.0,
                ],
            ]);
    }

    public function test_no_evalua_si_costo_es_cero(): void
    {
        $this->mockCosto(0);

        $this
            ->actingAs($this->vendedor)
            ->postJson(
                route(
                    'precios.equipos.evaluar-json',
                    $this->equipo
                ),
                [
                    'precio_rebaja' =>
                        5300,
                ]
            )
            ->assertStatus(422)
            ->assertJson([
                'ok' =>
                    false,
            ]);
    }

    private function mockCosto(
        float $costo
    ): void {
        $mock =
            Mockery::mock(
                CostoComercialActualService::class
            );

        $mock
            ->shouldReceive('calcular')
            ->andReturn([
                'equipo_id' =>
                    $this->equipo->id,

                'costo_total' =>
                    $costo,

                'costo_compra_actualizado' =>
                    $costo,

                'costos_lote' =>
                    0.0,

                'intervenciones' =>
                    0.0,

                'costos_posteriores' =>
                    0.0,

                'moneda_origen' =>
                    null,

                'monto_origen' =>
                    null,

                'usa_tipo_cambio' =>
                    false,

                'tipo_cambio_id' =>
                    null,

                'tipo_cambio' =>
                    null,

                'fuente' =>
                    'COSTO_HISTORICO',
            ]);

        $this->app->instance(
            CostoComercialActualService::class,
            $mock
        );
    }

    private function usuarioConRol(
        string $codigo
    ): User {
        $usuario =
            User::factory()->create([
                'activo' =>
                    true,
            ]);

        $rol =
            Rol::query()
                ->where(
                    'codigo',
                    $codigo
                )
                ->firstOrFail();

        $usuario
            ->roles()
            ->attach($rol->id);

        return $usuario;
    }

    private function crearEquipo(): Equipo
    {
        $categoria =
            CategoriaProducto::create([
                'codigo' =>
                    'AJAX-' . Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Laptops AJAX',

                'activo' =>
                    true,
            ]);

        $almacen =
            Almacen::create([
                'codigo' =>
                    'ALM-' . Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Almacén AJAX',

                'ciudad' =>
                    'Oruro',

                'principal' =>
                    true,

                'activo' =>
                    true,
            ]);

        $estado =
            EstadoEquipo::create([
                'codigo' =>
                    'EST-' . Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Disponible',

                'es_final' =>
                    false,

                'orden' =>
                    1,

                'activo' =>
                    true,
            ]);

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    null,

                'codigo' =>
                    'PROD-' . Str::uuid(),

                'nombre' =>
                    'Laptop AJAX',

                'modelo' =>
                    'TEST',

                'es_serializado' =>
                    true,

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

            'fecha_registro' =>
                now(),

            'fecha_disponible' =>
                now(),

            'activo' =>
                true,
        ]);
    }
}
