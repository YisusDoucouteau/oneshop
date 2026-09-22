<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaEconomiaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_administrador_ve_snapshot_economico_sin_recalcularlo(): void
    {
        $admin =
            $this->usuarioConRol(
                'ADMIN_OPERATIVO'
            );

        $venta =
            $this->crearVentaConSnapshot();

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'ventas.show',
                    $venta
                )
            )
            ->assertOk()
            ->assertSee('Ganancia de la venta')
            ->assertSee('Bs 491.00')
            ->assertSee('Detalle económico')
            ->assertSee('Costo al vender')
            ->assertSee('Bs 3,827.00')
            ->assertSee('Margen total')
            ->assertSee('Bs 1,473.00')
            ->assertSee('Hugo')
            ->assertSee('Daniel')
            ->assertSee('Tienda')
            ->assertSee('12.000000');
    }

    public function test_vendedor_ve_ganancia_pero_no_costo_tc_ni_reparto(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $venta =
            $this->crearVentaConSnapshot();

        /*
         * Conservamos el middleware real para no desactivar
         * SubstituteBindings. El rol VENDEDOR ya posee ventas.ver
         * y no posee precios.modificar, que es justo el escenario
         * que queremos verificar.
         */
        $this
            ->actingAs($vendedor)
            ->get(
                route(
                    'ventas.show',
                    $venta
                )
            )
            ->assertOk()
            ->assertSee('Ganancia de la venta')
            ->assertSee('Bs 491.00')
            ->assertDontSee('Detalle económico')
            ->assertDontSee('Costo al vender')
            ->assertDontSee('TC utilizado')
            ->assertDontSee('Hugo')
            ->assertDontSee('Daniel');
    }

    public function test_venta_legacy_no_inventa_economia_si_no_tiene_snapshot(): void
    {
        $admin =
            $this->usuarioConRol(
                'ADMIN_OPERATIVO'
            );

        $venta =
            $this->crearVentaLegacy();

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'ventas.show',
                    $venta
                )
            )
            ->assertOk()
            ->assertSee(
                'Información económica histórica no disponible para esta venta.'
            )
            ->assertDontSee(
                'Detalle económico'
            );
    }

    private function crearVentaConSnapshot(): Venta
    {
        $vendedor =
            User::factory()->create([
                'activo' => true,
            ]);

        [, $producto] =
            $this->crearProducto();

        $venta =
            Venta::create([
                'numero' =>
                    'VEN-WEB-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'cliente_id' =>
                    null,

                'vendedor_id' =>
                    $vendedor->id,

                'reserva_id' =>
                    null,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    5900,

                'descuento_total' =>
                    600,

                'total' =>
                    5300,

                'estado' =>
                    'REGISTRADA',

                'observacion' =>
                    null,
            ]);

        DetalleVenta::create([
            'venta_id' =>
                $venta->id,

            'producto_id' =>
                $producto->id,

            'equipo_id' =>
                null,

            'cantidad' =>
                1,

            'precio_lista_snapshot' =>
                5900,

            'descuento_unitario' =>
                600,

            'precio_unitario' =>
                5300,

            'costo_unitario_snapshot' =>
                3827,

            'tipo_cambio_snapshot_id' =>
                null,

            'tipo_cambio_valor_snapshot' =>
                12,

            'moneda_origen_snapshot' =>
                'USD',

            'monto_origen_snapshot' =>
                250,

            'fuente_costo_snapshot' =>
                'TIPO_CAMBIO_COMERCIAL',

            'margen_total_snapshot' =>
                1473,

            'ganancia_snapshot' =>
                491,

            'hugo_snapshot' =>
                491,

            'daniel_snapshot' =>
                491,

            'tienda_snapshot' =>
                491,

            'subtotal' =>
                5300,
        ]);

        return $venta;
    }

    private function crearVentaLegacy(): Venta
    {
        $vendedor =
            User::factory()->create([
                'activo' => true,
            ]);

        [, $producto] =
            $this->crearProducto();

        $venta =
            Venta::create([
                'numero' =>
                    'VEN-LEGACY-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'cliente_id' =>
                    null,

                'vendedor_id' =>
                    $vendedor->id,

                'reserva_id' =>
                    null,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    5000,

                'descuento_total' =>
                    0,

                'total' =>
                    5000,

                'estado' =>
                    'REGISTRADA',
            ]);

        DetalleVenta::create([
            'venta_id' =>
                $venta->id,

            'producto_id' =>
                $producto->id,

            'equipo_id' =>
                null,

            'cantidad' =>
                1,

            'precio_lista_snapshot' =>
                5000,

            'descuento_unitario' =>
                0,

            'precio_unitario' =>
                5000,

            'costo_unitario_snapshot' =>
                3500,

            'margen_total_snapshot' =>
                null,

            'ganancia_snapshot' =>
                null,

            'hugo_snapshot' =>
                null,

            'daniel_snapshot' =>
                null,

            'tienda_snapshot' =>
                null,

            'subtotal' =>
                5000,
        ]);

        return $venta;
    }

    private function crearProducto(): array
    {
        $categoria =
            CategoriaProducto::create([
                'codigo' =>
                    'CAT-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Categoría venta web',

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
                    'PROD-' .
                    Str::upper(
                        Str::random(10)
                    ),

                'nombre' =>
                    'Dell Latitude',

                'modelo' =>
                    '5420',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        return [$categoria, $producto];
    }

    private function usuarioConRol(
        string $codigoRol
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
                    $codigoRol
                )
                ->firstOrFail();

        $usuario
            ->roles()
            ->attach(
                $rol->id
            );

        return $usuario;
    }
}
