<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\CondicionFisica;
use App\Models\DetalleLote;
use App\Models\Lote;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Services\RegistroEquipoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use App\Services\LoteService;
class RegistroEquipoServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;
    private User $sinPermiso;
    private Producto $producto;
    private Almacen $almacen;
    private CondicionFisica $condicion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->administrador = User::factory()->create([
            'activo' => true,
        ]);

        $rolAdministrador = Rol::where(
            'codigo',
            'ADMINISTRADOR'
        )->firstOrFail();

        $this->administrador
            ->roles()
            ->attach($rolAdministrador->id);

        $this->sinPermiso = User::factory()->create([
            'activo' => true,
        ]);

        $categoria = CategoriaProducto::where(
            'codigo',
            'LAPTOP'
        )->firstOrFail();

        $marca = Marca::create([
            'nombre' => 'Dell',
            'descripcion' => null,
            'activo' => true,
        ]);

        $this->producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'TEST-LAT-5420',
            'nombre' => 'Dell Latitude',
            'modelo' => '5420',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        $this->almacen = Almacen::where(
            'codigo',
            'ORURO_PRINCIPAL'
        )->firstOrFail();

        $this->condicion = CondicionFisica::where(
            'codigo',
            'A'
        )->firstOrFail();
    }

    private function datosValidos(): array
    {
        return [
            'producto_id' => $this->producto->id,
            'almacen_actual_id' => $this->almacen->id,
            'condicion_fisica_id' => $this->condicion->id,
            'detalle_lote_id' => null,

            'codigo_interno' => 'OS-TEST-001',
            'serial_fabricante' => 'SERIAL-001',

            'procesador' => 'Intel Core i5-1145G7',
            'generacion_procesador' => '11',
            'ram_gb' => 16,
            'almacenamiento_gb' => 512,
            'tipo_almacenamiento' => 'SSD',
            'tarjeta_grafica' => null,
            'pantalla_pulgadas' => 14.0,
            'resolucion' => '1920x1080',
            'sistema_operativo' => 'Windows 11',
            'bateria_porcentaje' => 90,

            'observacion' => 'Equipo de prueba.',
        ];
    }

    public function test_registra_equipo_con_estado_inicial_y_trazabilidad(): void
    {
        $equipo = app(RegistroEquipoService::class)
            ->registrar(
                $this->administrador->id,
                $this->datosValidos()
            );

        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'codigo_interno' => 'OS-TEST-001',
            'producto_id' => $this->producto->id,
        ]);

        $this->assertSame(
            'RECIBIDO',
            $equipo->estadoActual->codigo
        );

        $this->assertNotNull(
            $equipo->especificacion
        );

        $this->assertSame(
            16,
            $equipo->especificacion->ram_gb
        );

        $this->assertDatabaseHas(
            'historial_estados_equipos',
            [
                'equipo_id' => $equipo->id,
                'estado_origen_id' => null,
                'estado_destino_id' => $equipo->estado_actual_id,
                'usuario_id' => $this->administrador->id,
            ]
        );
    }

    public function test_usuario_sin_permiso_no_puede_registrar_equipo(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no cuenta con permiso'
        );

        app(RegistroEquipoService::class)
            ->registrar(
                $this->sinPermiso->id,
                $this->datosValidos()
            );
    }

    public function test_no_permite_codigo_interno_duplicado(): void
    {
        $servicio = app(RegistroEquipoService::class);

        $servicio->registrar(
            $this->administrador->id,
            $this->datosValidos()
        );

        $this->expectException(
            ValidationException::class
        );

        $servicio->registrar(
            $this->administrador->id,
            $this->datosValidos()
        );
    }

    public function test_no_permite_producto_no_serializado(): void
    {
        $categoria = CategoriaProducto::where(
            'codigo',
            'ACCESORIO'
        )->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'TEST-MOUSE',
            'nombre' => 'Mouse',
            'modelo' => null,
            'descripcion' => null,
            'es_serializado' => false,
            'activo' => true,
        ]);

        $datos = $this->datosValidos();
        $datos['producto_id'] = $producto->id;

        $this->expectException(
            ReglaNegocioException::class
        );

        app(RegistroEquipoService::class)
            ->registrar(
                $this->administrador->id,
                $datos
            );
    }

    public function test_rechaza_bateria_fuera_de_rango_y_no_crea_equipo(): void
    {
        $datos = $this->datosValidos();
        $datos['bateria_porcentaje'] = 150;

        try {
            app(RegistroEquipoService::class)
                ->registrar(
                    $this->administrador->id,
                    $datos
                );

            $this->fail(
                'Se esperaba una excepción de validación.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'bateria_porcentaje',
                $exception->errors()
            );
        }

        $this->assertDatabaseMissing('equipos', [
            'codigo_interno' => 'OS-TEST-001',
        ]);
    }

    public function test_rechaza_lote_con_producto_distinto(): void
{
    /*
    |--------------------------------------------------------------------------
    | Creamos otro producto
    |--------------------------------------------------------------------------
    |
    | El lote pertenecerá a este producto, mientras que intentaremos
    | registrar el equipo usando el producto original de la prueba.
    |
    */

    $otroProducto = Producto::create([
        'categoria_producto_id' =>
            $this->producto->categoria_producto_id,

        'marca_id' => null,

        'codigo' => 'OTRO-PRODUCTO-LOTE',

        'nombre' => 'Otro equipo de prueba',

        'modelo' => 'OTRO-001',

        'descripcion' => null,

        'es_serializado' => true,

        'activo' => true,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Creamos un lote válido mediante la lógica de negocio
    |--------------------------------------------------------------------------
    */

    $loteService = app(LoteService::class);

    $lote = $loteService->crearLote(
        $this->administrador->id,
        [
            'proveedor_id' => null,

            'codigo' =>
                'IMP-TEST-INTEGRIDAD-001',

            'referencia_compra' =>
                'REF-INTEGRIDAD-001',

            'origen' =>
                'Estados Unidos',

            'observacion' =>
                'Lote creado para prueba de integridad.',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | El detalle pertenece al OTRO producto
    |--------------------------------------------------------------------------
    */

    $detalle = $loteService->agregarDetalle(
        $this->administrador->id,
        $lote->id,
        [
            'producto_id' =>
                $otroProducto->id,

            'cantidad_esperada' => 1,

            'observacion' =>
                'Detalle utilizado para probar producto incorrecto.',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | Intentamos registrar el producto original usando ese detalle
    |--------------------------------------------------------------------------
    */

    $datos = $this->datosValidos();

    $datos['detalle_lote_id'] =
        $detalle->id;

    try {
        app(RegistroEquipoService::class)
            ->registrar(
                $this->administrador->id,
                $datos
            );

        $this->fail(
            'Se esperaba una excepción porque el producto no coincide con el detalle del lote.'
        );

    } catch (ReglaNegocioException $exception) {

        $this->assertStringContainsString(
            'no coincide',
            $exception->getMessage()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | No debe haberse creado ningún equipo parcial
    |--------------------------------------------------------------------------
    */

    $this->assertDatabaseMissing(
        'equipos',
        [
            'codigo_interno' =>
                'OS-TEST-001',
        ]
    );
}
}