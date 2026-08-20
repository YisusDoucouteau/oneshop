<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioWebTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioAutorizado;
    private User $usuarioSinPermiso;
    private Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuarioAutorizado = User::factory()->create([
            'activo' => true,
        ]);

        $rolVendedor = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $this->usuarioAutorizado
            ->roles()
            ->attach($rolVendedor->id);

        $this->usuarioSinPermiso = User::factory()->create([
            'activo' => true,
        ]);

        $categoria = CategoriaProducto::where(
            'codigo',
            'LAPTOP'
        )->firstOrFail();

        $marca = Marca::create([
            'nombre' => 'Dell',
            'descripcion' => 'Marca de prueba',
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'TEST-P001',
            'nombre' => 'Dell Latitude',
            'modelo' => '5420',
            'descripcion' => 'Producto de prueba',
            'es_serializado' => true,
            'activo' => true,
        ]);

        $estado = EstadoEquipo::where(
            'codigo',
            'DISPONIBLE'
        )->firstOrFail();

        $almacen = Almacen::where(
            'codigo',
            'ORURO_PRINCIPAL'
        )->firstOrFail();

        $this->equipo = Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'TEST-0001',
            'serial_fabricante' => 'SERIAL-TEST-001',
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'observacion' => 'Equipo creado para prueba.',
            'activo' => true,
        ]);
    }

    public function test_usuario_con_permiso_puede_ver_inventario(): void
    {
        $response = $this
            ->actingAs($this->usuarioAutorizado)
            ->get(route('inventario.index'));

        $response->assertOk();

        $response->assertSee('Inventario de equipos');
        $response->assertSee('TEST-0001');
        $response->assertSee('Dell Latitude');
    }

    public function test_usuario_sin_permiso_no_puede_ver_inventario(): void
    {
        $response = $this
            ->actingAs($this->usuarioSinPermiso)
            ->get(route('inventario.index'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_ver_ficha_de_equipo(): void
    {
        $response = $this
            ->actingAs($this->usuarioAutorizado)
            ->get(route(
                'inventario.show',
                $this->equipo->codigo_interno
            ));

        $response->assertOk();

        $response->assertSee('TEST-0001');
        $response->assertSee('Dell Latitude');
        $response->assertSee('Identificación');
        $response->assertSee('Trazabilidad');
    }

    public function test_codigo_de_equipo_inexistente_devuelve_404(): void
    {
        $response = $this
            ->actingAs($this->usuarioAutorizado)
            ->get('/inventario/NO-EXISTE');

        $response->assertNotFound();
    }

    public function test_trazabilidad_muestra_registro_inicial_del_equipo(): void
    {
        $response = $this
            ->actingAs($this->usuarioAutorizado)
            ->get(route(
                'inventario.show',
                $this->equipo->codigo_interno
            ));

        $response->assertOk();

        $response->assertSee('Equipo registrado');
        $response->assertSee(
            'El equipo fue incorporado al inventario de OneShop.'
        );
    }
}