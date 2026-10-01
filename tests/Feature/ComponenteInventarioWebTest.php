<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComponenteInventarioWebTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
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
                ->firstOrFail();

        $this->usuario =
            User::factory()->create([
                'activo' =>
                    true,

                'almacen_operativo_id' =>
                    $this->cochabamba->id,
            ]);

        $rol =
            Rol::query()
                ->where(
                    'codigo',
                    'ADMIN_OPERATIVO'
                )
                ->firstOrFail();

        $this->usuario
            ->roles()
            ->attach(
                $rol->id
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
                    'Marca Componentes Web Test',

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
                    'COMP-WEB-001',

                'nombre' =>
                    'Cargador USB-C Web Test',

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

    public function test_muestra_inventario_de_componentes_del_almacen_operativo(): void
    {
        $this->crearExistencia(
            disponible: 5,
            reservado: 1,
            promedioBob: 80
        );

        $this
            ->actingAs(
                $this->usuario
            )
            ->get(
                route(
                    'inventario.componentes.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'Inventario de componentes'
            )
            ->assertSee(
                'Depósito Cochabamba'
            )
            ->assertSee(
                'Cargador USB-C Web Test'
            )
            ->assertSee(
                'Bs 80.00'
            )
            ->assertSee(
                'Bs 480.00'
            )
            ->assertSee(
                'Valorizado'
            );
    }

    public function test_marca_stock_legacy_como_pendiente_de_valoracion(): void
    {
        $this->crearExistencia(
            disponible: 4,
            reservado: 0,
            promedioBob: null
        );

        $this
            ->actingAs(
                $this->usuario
            )
            ->get(
                route(
                    'inventario.componentes.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'Sin valorar'
            )
            ->assertSee(
                'Pendiente'
            )
            ->assertSee(
                'Requieren regularización'
            );
    }

    public function test_filtro_sin_valorar_oculta_componentes_valorizados(): void
    {
        $this->crearExistencia(
            disponible: 3,
            reservado: 0,
            promedioBob: 50
        );

        $otro =
            Producto::query()->create([
                'categoria_producto_id' =>
                    $this->componente
                        ->categoria_producto_id,

                'marca_id' =>
                    $this->componente
                        ->marca_id,

                'codigo' =>
                    'COMP-WEB-002',

                'nombre' =>
                    'SSD Legacy Web Test',

                'modelo' =>
                    '512GB',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    false,

                'activo' =>
                    true,
            ]);

        DB::table(
            'existencias_productos'
        )
            ->insert([
                'producto_id' =>
                    $otro->id,

                'almacen_id' =>
                    $this->cochabamba->id,

                'cantidad_disponible' =>
                    2,

                'cantidad_reservada' =>
                    0,

                'costo_promedio_bob' =>
                    null,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $this
            ->actingAs(
                $this->usuario
            )
            ->get(
                route(
                    'inventario.componentes.index',
                    [
                        'estado' =>
                            'SIN_VALORAR',
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'SSD Legacy Web Test'
            )
            ->assertViewHas(
                'componentes',
                function ($componentes) use ($otro) {
                    return
                        $componentes
                            ->getCollection()
                            ->contains(
                                'id',
                                $otro->id
                            )
                        &&
                        !$componentes
                            ->getCollection()
                            ->contains(
                                'id',
                                $this->componente->id
                            );
                }
            );
    }

    public function test_pantalla_expone_formulario_de_compra_para_usuario_autorizado(): void
    {
        $this
            ->actingAs(
                $this->usuario
            )
            ->get(
                route(
                    'inventario.componentes.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'Registrar compra'
            )
            ->assertSee(
                'name="producto_id"',
                false
            )
            ->assertSee(
                'name="monto_total_origen"',
                false
            )
            ->assertSee(
                'name="tipo_cambio_aplicado"',
                false
            );
    }


    public function test_usuario_autorizado_puede_registrar_componente_de_catalogo(): void
    {
        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $response =
            $this
                ->actingAs(
                    $this->usuario
                )
                ->post(
                    route(
                        'inventario.componentes.catalogo.store'
                    ),
                    [
                        'nombre' =>
                            'Memoria DDR4 8GB',

                        'categoria_producto_id' =>
                            $categoria->id,

                        'marca_id' =>
                            null,

                        'modelo' =>
                            '3200MHz',

                        'descripcion' =>
                            'Componente de catálogo para stock.',

                        'almacen_contexto' =>
                            $this->cochabamba->id,
                    ]
                );

        $producto =
            Producto::query()
                ->where(
                    'nombre',
                    'Memoria DDR4 8GB'
                )
                ->where(
                    'modelo',
                    '3200MHz'
                )
                ->firstOrFail();

        $response->assertRedirect(
            route(
                'inventario.componentes.index',
                [
                    'almacen' =>
                        $this->cochabamba->id,

                    'compra' =>
                        1,

                    'producto_id' =>
                        $producto->id,
                ]
            )
        );

        $this->assertDatabaseHas(
            'productos',
            [
                'id' =>
                    $producto->id,

                'codigo' =>
                    'CMP-MEMORIA-DDR4-8GB-3200MHZ-001',

                'nombre' =>
                    'Memoria DDR4 8GB',

                'modelo' =>
                    '3200MHz',

                'es_serializado' =>
                    false,

                'activo' =>
                    true,
            ]
        );
    }

    public function test_despues_de_crear_componente_reabre_compra_con_producto_preseleccionado(): void
    {
        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $response =
            $this
                ->actingAs(
                    $this->usuario
                )
                ->post(
                    route(
                        'inventario.componentes.catalogo.store'
                    ),
                    [

                        'nombre' =>
                            'SSD continuidad compra',

                        'categoria_producto_id' =>
                            $categoria->id,

                        'marca_id' =>
                            null,

                        'modelo' =>
                            '1TB',

                        'descripcion' =>
                            null,

                        'almacen_contexto' =>
                            $this->cochabamba->id,
                    ]
                );

        $producto =
            Producto::query()
                ->where(
                    'codigo',
                    'CMP-SSD-CONTINUIDAD-COMPRA-1TB-001'
                )
                ->firstOrFail();

        $response->assertRedirect(
            route(
                'inventario.componentes.index',
                [
                    'almacen' =>
                        $this->cochabamba->id,

                    'compra' =>
                        1,

                    'producto_id' =>
                        $producto->id,
                ]
            )
        );

        $this
            ->actingAs(
                $this->usuario
            )
            ->get(
                route(
                    'inventario.componentes.index',
                    [
                        'almacen' =>
                            $this->cochabamba->id,

                        'compra' =>
                            1,

                        'producto_id' =>
                            $producto->id,
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'Componente creado'
            )
            ->assertSee(
                'SSD continuidad compra'
            )
            ->assertSee(
                'Guardar y continuar compra'
            );
    }

    public function test_codigo_de_componente_se_genera_automaticamente_y_evitar_colisiones(): void
    {
        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        foreach ([1, 2] as $iteracion) {
            $this
                ->actingAs(
                    $this->usuario
                )
                ->post(
                    route(
                        'inventario.componentes.catalogo.store'
                    ),
                    [
                        'nombre' =>
                            'Cargador Dell 65W',

                        'categoria_producto_id' =>
                            $categoria->id,

                        'marca_id' =>
                            null,

                        'modelo' =>
                            'Punta aguja',

                        'descripcion' =>
                            null,

                        'almacen_contexto' =>
                            $this->cochabamba->id,
                    ]
                )
                ->assertRedirect();
        }

        $this->assertDatabaseHas(
            'productos',
            [
                'codigo' =>
                    'CMP-CARGADOR-DELL-65W-PUNTA-AGUJA-001',
            ]
        );

        $this->assertDatabaseHas(
            'productos',
            [
                'codigo' =>
                    'CMP-CARGADOR-DELL-65W-PUNTA-AGUJA-002',
            ]
        );
    }

    private function crearExistencia(
        int $disponible,
        int $reservado,
        ?float $promedioBob
    ): void {
        DB::table(
            'existencias_productos'
        )
            ->insert([
                'producto_id' =>
                    $this->componente->id,

                'almacen_id' =>
                    $this->cochabamba->id,

                'cantidad_disponible' =>
                    $disponible,

                'cantidad_reservada' =>
                    $reservado,

                'costo_promedio_bob' =>
                    $promedioBob,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
    }
}
