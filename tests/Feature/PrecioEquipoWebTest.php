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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrecioEquipoWebTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario =
            User::factory()->create([
                'activo' => true,
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
            ->attach($rol->id);

        $this->equipo =
            $this->crearEquipo();
    }

    public function test_usuario_con_permiso_puede_abrir_gestion_de_precio(): void
    {
        PrecioEquipo::create([
            'equipo_id' =>
                $this->equipo->id,

            'tipo_cambio_id' =>
                null,

            'costo_total_snapshot' =>
                4300,

            'precio_sugerido' =>
                5200,

            'precio_publico' =>
                5000,

            'precio_minimo_autorizado' =>
                4700,

            'vigente_desde' =>
                now(),

            'vigente_hasta' =>
                null,

            'vigente' =>
                true,

            'aprobado_por_id' =>
                $this->usuario->id,

            'observacion' =>
                'Precio inicial de prueba.',
        ]);

        $this
            ->actingAs($this->usuario)
            ->get(
                route(
                    'precios.equipos.show',
                    $this->equipo
                )
            )
            ->assertOk()
            ->assertSee('Gestión de precio')
            ->assertSee('Costo real actual')
            ->assertSee('Bs 4,300.00')
            ->assertSee('Historial de precios');
    }

    public function test_puede_evaluar_una_propuesta_sin_registrarla(): void
    {
        $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'precios.equipos.evaluar',
                    $this->equipo
                ),
                [
                    'precio_sugerido' =>
                        5200,

                    'precio_publico' =>
                        5000,

                    'precio_minimo_autorizado' =>
                        4700,

                    'observacion' =>
                        'Evaluación previa.',
                ]
            )
            ->assertOk()
            ->assertSee('Evaluación comercial');

        $this->assertDatabaseCount(
            'precios_equipos',
            0
        );
    }

    public function test_registrar_precio_calcula_snapshot_en_servidor(): void
    {
        /*
         * El precio previo sirve como fuente legacy del costo real
         * para este equipo aislado de prueba.
         */
        $anterior =
            PrecioEquipo::create([
                'equipo_id' =>
                    $this->equipo->id,

                'tipo_cambio_id' =>
                    null,

                'costo_total_snapshot' =>
                    4300,

                'precio_sugerido' =>
                    5100,

                'precio_publico' =>
                    5000,

                'precio_minimo_autorizado' =>
                    4600,

                'vigente_desde' =>
                    now()->subDay(),

                'vigente_hasta' =>
                    null,

                'vigente' =>
                    true,

                'aprobado_por_id' =>
                    $this->usuario->id,

                'observacion' =>
                    'Precio anterior.',
            ]);

        $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'precios.equipos.store',
                    $this->equipo
                ),
                [
                    'precio_sugerido' =>
                        5300,

                    'precio_publico' =>
                        5200,

                    'precio_minimo_autorizado' =>
                        4800,

                    'observacion' =>
                        'Actualización web.',
                ]
            )
            ->assertRedirect(
                route(
                    'precios.equipos.show',
                    $this->equipo
                )
            )
            ->assertSessionHas(
                'success'
            );

        $this->assertDatabaseHas(
            'precios_equipos',
            [
                'id' =>
                    $anterior->id,

                'vigente' =>
                    0,
            ]
        );

        $this->assertDatabaseHas(
            'precios_equipos',
            [
                'equipo_id' =>
                    $this->equipo->id,

                'costo_total_snapshot' =>
                    4300,

                'precio_sugerido' =>
                    5300,

                'precio_publico' =>
                    5200,

                'precio_minimo_autorizado' =>
                    4800,

                'vigente' =>
                    1,

                'aprobado_por_id' =>
                    $this->usuario->id,
            ]
        );
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

                'descripcion' =>
                    null,

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

                'direccion' =>
                    null,

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

                'descripcion' =>
                    null,

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

                'descripcion' =>
                    null,

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
}
