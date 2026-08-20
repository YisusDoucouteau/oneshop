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
    private User $usuarioRegistrador;
    private User $usuarioSinPermiso;
    private Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        /*
        |--------------------------------------------------------------------------
        | Vendedor: puede consultar, pero NO registrar
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Administrador operativo: puede consultar y registrar
        |--------------------------------------------------------------------------
        */

        $this->usuarioRegistrador = User::factory()->create([
            'activo' => true,
        ]);

        $rolOperativo = Rol::where(
            'codigo',
            'ADMIN_OPERATIVO'
        )->firstOrFail();

        $this->usuarioRegistrador
            ->roles()
            ->attach($rolOperativo->id);

        /*
        |--------------------------------------------------------------------------
        | Usuario sin permisos
        |--------------------------------------------------------------------------
        */

        $this->usuarioSinPermiso = User::factory()->create([
            'activo' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Datos para pruebas
        |--------------------------------------------------------------------------
        */

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

    /*
    |--------------------------------------------------------------------------
    | Inventario
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Ficha
    |--------------------------------------------------------------------------
    */

    public function test_usuario_con_permiso_puede_ver_ficha_de_equipo(): void
    {
        $response = $this
            ->actingAs($this->usuarioAutorizado)
            ->get(
                route(
                    'inventario.show',
                    $this->equipo->codigo_interno
                )
            );

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
            ->get(
                route(
                    'inventario.show',
                    $this->equipo->codigo_interno
                )
            );

        $response->assertOk();

        $response->assertSee('Equipo registrado');

        $response->assertSee(
            'El equipo fue incorporado al inventario de OneShop.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Formulario de registro
    |--------------------------------------------------------------------------
    */

    public function test_usuario_con_permiso_puede_abrir_formulario_de_registro(): void
    {
        $response = $this
            ->actingAs($this->usuarioRegistrador)
            ->get(route('inventario.create'));

        $response->assertOk();

        $response->assertSee('Registrar nuevo equipo');
    }

    public function test_vendedor_no_puede_abrir_formulario_de_registro(): void
    {
        $response = $this
            ->actingAs($this->usuarioAutorizado)
            ->get(route('inventario.create'));

        $response->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Registro web
    |--------------------------------------------------------------------------
    */

    public function test_registro_web_crea_equipo_y_redirige_a_su_ficha(): void
    {
        $response = $this
            ->actingAs($this->usuarioRegistrador)
            ->post(route('inventario.store'), [
                'producto_id' => $this->equipo->producto_id,
                'almacen_actual_id' => $this->equipo->almacen_actual_id,
                'condicion_fisica_id' => null,
                'detalle_lote_id' => null,

                'codigo_interno' => 'WEB-TEST-002',
                'serial_fabricante' => 'SERIAL-WEB-002',

                'procesador' => 'Intel Core i5',
                'generacion_procesador' => '11',
                'ram_gb' => 16,
                'almacenamiento_gb' => 512,
                'tipo_almacenamiento' => 'SSD',
                'tarjeta_grafica' => null,
                'pantalla_pulgadas' => 14,
                'resolucion' => '1920x1080',
                'sistema_operativo' => 'Windows 11',
                'bateria_porcentaje' => 90,

                'observacion' => 'Registro desde prueba web.',
            ]);

        $response->assertRedirect(
            route(
                'inventario.show',
                'WEB-TEST-002'
            )
        );

        $this->assertDatabaseHas('equipos', [
            'codigo_interno' => 'WEB-TEST-002',
        ]);

        $equipo = Equipo::where(
            'codigo_interno',
            'WEB-TEST-002'
        )->firstOrFail();

        $this->assertSame(
            'RECIBIDO',
            $equipo->estadoActual->codigo
        );

        $this->assertDatabaseHas(
            'especificaciones_equipos',
            [
                'equipo_id' => $equipo->id,
                'ram_gb' => 16,
                'almacenamiento_gb' => 512,
            ]
        );

        $this->assertDatabaseHas(
            'historial_estados_equipos',
            [
                'equipo_id' => $equipo->id,
                'estado_origen_id' => null,
                'estado_destino_id' => $equipo->estado_actual_id,
                'usuario_id' => $this->usuarioRegistrador->id,
            ]
        );
    }

    public function test_registro_web_rechaza_codigo_duplicado(): void
    {
        $response = $this
            ->actingAs($this->usuarioRegistrador)
            ->from(route('inventario.create'))
            ->post(route('inventario.store'), [
                'producto_id' => $this->equipo->producto_id,
                'almacen_actual_id' => $this->equipo->almacen_actual_id,
                'condicion_fisica_id' => null,
                'detalle_lote_id' => null,

                'codigo_interno' => $this->equipo->codigo_interno,
            ]);

        $response->assertRedirect(
            route('inventario.create')
        );

        $response->assertSessionHasErrors(
            'codigo_interno'
        );
    }

    public function test_registro_web_valida_porcentaje_de_bateria(): void
    {
        $response = $this
            ->actingAs($this->usuarioRegistrador)
            ->from(route('inventario.create'))
            ->post(route('inventario.store'), [
                'producto_id' => $this->equipo->producto_id,
                'almacen_actual_id' => $this->equipo->almacen_actual_id,
                'condicion_fisica_id' => null,
                'detalle_lote_id' => null,

                'codigo_interno' => 'WEB-BATERIA-001',
                'bateria_porcentaje' => 150,
            ]);

        $response->assertRedirect(
            route('inventario.create')
        );

        $response->assertSessionHasErrors(
            'bateria_porcentaje'
        );

        $this->assertDatabaseMissing('equipos', [
            'codigo_interno' => 'WEB-BATERIA-001',
        ]);
    }
}