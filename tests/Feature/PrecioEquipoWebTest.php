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

class PrecioEquipoWebTest extends TestCase
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
            $this->crearUsuarioConRol(
                'ADMIN_OPERATIVO'
            );

        $this->vendedor =
            $this->crearUsuarioConRol(
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
                4050,

            'precio_sugerido' =>
                4550,

            'precio_publico' =>
                4550,

            'precio_minimo_autorizado' =>
                4250,

            'vigente_desde' =>
                now()->subDay(),

            'vigente_hasta' =>
                null,

            'vigente' =>
                true,

            'aprobado_por_id' =>
                $this->admin->id,

            'observacion' =>
                'Precio inicial.',
        ]);
    }

    public function test_admin_puede_abrir_gestion_de_precio(): void
    {
        $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'precios.equipos.show',
                    $this->equipo
                )
            )
            ->assertOk()
            ->assertSee('Sugerencia OneShop')
            ->assertSee('Simulador de venta')
            ->assertSee('Administración del precio')
            ->assertSee('Historial de precios')
            // Este fixture no tiene una unidad de origen en moneda extranjera.
            // La configuración global de TC solo debe mostrarse cuando aplica.
            ->assertDontSee('Configuración comercial global');
    }

    public function test_admin_puede_habilitar_equipo_recibido_para_venta(): void
    {
        $estadoRecibido =
            EstadoEquipo::query()
                ->where('codigo', 'RECIBIDO')
                ->firstOrFail();

        $almacenOruro =
            Almacen::query()
                ->where('codigo', 'ORURO_PRINCIPAL')
                ->firstOrFail();

        $this->equipo->update([
            'estado_actual_id' => $estadoRecibido->id,
            'almacen_actual_id' => $almacenOruro->id,
            'fecha_disponible' => null,
        ]);

        $this->mockCostoComercial(3888);

        $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'precios.equipos.habilitar-venta',
                    $this->equipo
                )
            )
            ->assertRedirect(
                route(
                    'precios.equipos.show',
                    $this->equipo
                )
            )
            ->assertSessionHas(
                'success',
                'Equipo habilitado para venta. Ya aparecerá en Nueva venta.'
            );

        $this->equipo->refresh();

        $this->assertSame(
            'DISPONIBLE',
            $this->equipo->estadoActual?->codigo
        );

        $this->assertNotNull(
            $this->equipo->fecha_disponible
        );

        $this->assertDatabaseHas(
            'existencias_productos',
            [
                'producto_id' =>
                    $this->equipo->producto_id,

                'almacen_id' =>
                    $almacenOruro->id,

                'cantidad_disponible' =>
                    1,

                'cantidad_reservada' =>
                    0,
            ]
        );

        $this->assertDatabaseHas(
            'movimientos_inventario',
            [
                'producto_id' =>
                    $this->equipo->producto_id,

                'almacen_id' =>
                    $almacenOruro->id,

                'cambio_disponible' =>
                    1,

                'cambio_reservado' =>
                    0,

                'tipo_referencia' =>
                    'HABILITACION_VENTA',

                'referencia_id' =>
                    $this->equipo->id,
            ]
        );

        /*
         * Repetir la acción no debe duplicar stock.
         */
        $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'precios.equipos.habilitar-venta',
                    $this->equipo
                )
            )
            ->assertRedirect(
                route(
                    'precios.equipos.show',
                    $this->equipo
                )
            );

        $this->assertDatabaseHas(
            'existencias_productos',
            [
                'producto_id' =>
                    $this->equipo->producto_id,

                'almacen_id' =>
                    $almacenOruro->id,

                'cantidad_disponible' =>
                    1,
            ]
        );

        $this->assertSame(
            1,
            \App\Models\MovimientoInventario::query()
                ->where('tipo_referencia', 'HABILITACION_VENTA')
                ->where('referencia_id', $this->equipo->id)
                ->count()
        );
    }

    public function test_no_habilita_para_venta_si_no_hay_precio_vigente(): void
    {
        $estadoRecibido =
            EstadoEquipo::query()
                ->where('codigo', 'RECIBIDO')
                ->firstOrFail();

        $almacenOruro =
            Almacen::query()
                ->where('codigo', 'ORURO_PRINCIPAL')
                ->firstOrFail();

        $this->equipo->update([
            'estado_actual_id' => $estadoRecibido->id,
            'almacen_actual_id' => $almacenOruro->id,
            'fecha_disponible' => null,
        ]);

        PrecioEquipo::query()
            ->where('equipo_id', $this->equipo->id)
            ->delete();

        $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'precios.equipos.habilitar-venta',
                    $this->equipo
                )
            )
            ->assertRedirect(
                route(
                    'precios.equipos.show',
                    $this->equipo
                )
            )
            ->assertSessionHasErrors(
                'habilitacion_venta'
            );

        $this->equipo->refresh();

        $this->assertSame(
            'RECIBIDO',
            $this->equipo->estadoActual?->codigo
        );
    }

    public function test_vendedor_no_puede_abrir_gestion_administrativa_de_precio(): void
    {
        $this
            ->actingAs($this->vendedor)
            ->get(
                route(
                    'precios.equipos.show',
                    $this->equipo
                )
            )
            ->assertForbidden();
    }

    public function test_admin_ve_reparto_hugo_daniel_tienda(): void
    {
        $this->mockCostoComercial(3827);

        $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'precios.equipos.evaluar',
                    $this->equipo
                ),
                [
                    'precio_rebaja' =>
                        5300,
                ]
            )
            ->assertOk()
            ->assertSee('Ganancia')
            ->assertSee('Ver detalle administrativo')
            ->assertSee('Hugo')
            ->assertSee('Daniel')
            ->assertSee('Tienda')
            ->assertSee('Administración del precio')
            ->assertSee('Historial de precios');
    }

    private function mockCostoComercial(
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

    private function crearUsuarioConRol(
        string $codigoRol
    ): User {
        $usuario =
            User::factory()->create([
                'activo' => true,
            ]);

        $rol =
            Rol::query()
                ->where(
                    'codigo',
                    $codigoRol
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
                    'WEB-PRECIO-' . Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Laptops web precio',

                'activo' =>
                    true,
            ]);

        $almacen =
            Almacen::create([
                'codigo' =>
                    'ORURO-WEB-' . Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Almacén web precios',

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
                    'DISP-WEB-' . Str::upper(
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
                    'Laptop gestión precio',

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

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estado->id,

            'condicion_fisica_id' =>
                null,

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
