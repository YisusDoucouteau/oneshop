<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Lote;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\User;
use App\Services\LoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use App\Models\Moneda;
class LoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioOperativo;
    private User $vendedor;
    private Proveedor $proveedor;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        /*
        |--------------------------------------------------------------------------
        | Administrador operativo
        |--------------------------------------------------------------------------
        */

        $this->usuarioOperativo =
            User::factory()->create([
                'activo' => true,
            ]);

        $rolOperativo = Rol::where(
            'codigo',
            'ADMIN_OPERATIVO'
        )->firstOrFail();

        $this->usuarioOperativo
            ->roles()
            ->attach($rolOperativo->id);

        /*
        |--------------------------------------------------------------------------
        | Vendedor
        |--------------------------------------------------------------------------
        */

        $this->vendedor =
            User::factory()->create([
                'activo' => true,
            ]);

        $rolVendedor = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $this->vendedor
            ->roles()
            ->attach($rolVendedor->id);

        /*
        |--------------------------------------------------------------------------
        | Proveedor
        |--------------------------------------------------------------------------
        */

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor USA Test',
            'pais' => 'Estados Unidos',
            'ciudad' => 'Miami',
            'telefono' => null,
            'correo' => null,
            'contacto' => null,
            'observacion' => null,
            'activo' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Producto
        |--------------------------------------------------------------------------
        */

        $categoria = CategoriaProducto::where(
            'codigo',
            'LAPTOP'
        )->firstOrFail();

        $marca = Marca::create([
            'nombre' => 'Dell Test',
            'descripcion' => null,
            'activo' => true,
        ]);

        $this->producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'LOT-TEST-P001',
            'nombre' => 'Dell Latitude',
            'modelo' => '5420',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);
    }

    private function datosLote(): array
    {
        return [
            'proveedor_id' =>
                $this->proveedor->id,

            'codigo' => 'IMP-TEST-001',

            'referencia_compra' =>
                'COMPRA-TEST-001',

            'origen' =>
                'Miami, Estados Unidos',

            'observacion' =>
                'Lote utilizado en pruebas.',
        ];
    }

    public function test_usuario_autorizado_puede_crear_lote(): void
    {
        $lote = app(LoteService::class)
            ->crearLote(
                $this->usuarioOperativo->id,
                $this->datosLote()
            );

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'codigo' => 'IMP-TEST-001',
            'proveedor_id' =>
                $this->proveedor->id,
            'estado' => 'ABIERTO',
        ]);

        $this->assertSame(
            'ABIERTO',
            $lote->estado
        );
    }

    public function test_codigo_de_lote_no_puede_repetirse(): void
    {
        $servicio = app(LoteService::class);

        $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        $this->expectException(
            ValidationException::class
        );

        $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );
    }

    public function test_proveedor_inactivo_no_puede_usarse(): void
{
    $this->proveedor->update([
        'activo' => false,
    ]);

    try {
        app(LoteService::class)
            ->crearLote(
                $this->usuarioOperativo->id,
                $this->datosLote()
            );

        $this->fail(
            'Se esperaba una excepción por proveedor inactivo.'
        );

    } catch (ReglaNegocioException $exception) {

        $this->assertStringContainsString(
            'se encuentra inactivo',
            $exception->getMessage()
        );
    }

    $this->assertDatabaseMissing(
        'lotes',
        [
            'codigo' => 'IMP-TEST-001',
        ]
    );
}

    public function test_vendedor_no_puede_gestionar_importaciones(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no cuenta con permiso'
        );

        app(LoteService::class)
            ->crearLote(
                $this->vendedor->id,
                $this->datosLote()
            );
    }

    public function test_puede_agregar_producto_a_lote_abierto(): void
    {
        $servicio = app(LoteService::class);

        $lote = $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        $detalle = $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' =>
                    $this->producto->id,

                'cantidad_esperada' => 3,

                'observacion' =>
                    'Tres unidades esperadas.',
            ]
        );

        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' => $detalle->id,
                'lote_id' => $lote->id,
                'producto_id' =>
                    $this->producto->id,
                'cantidad_esperada' => 3,
                'cantidad_recibida' => 0,
            ]
        );
    }

    public function test_permite_mismo_producto_en_dos_lineas_del_mismo_lote(): void
{
    $bob = Moneda::query()
        ->where(
            'codigo',
            'BOB'
        )
        ->firstOrFail();

    $servicio = app(
        LoteService::class
    );

    $lote = $servicio->crearLote(
        $this->usuarioOperativo->id,
        $this->datosLote()
    );

    $detalleUno = $servicio->agregarDetalle(
        $this->usuarioOperativo->id,
        $lote->id,
        [
            'producto_id' =>
                $this->producto->id,

            'cantidad_esperada' =>
                2,

            'moneda_id' =>
                $bob->id,

            'costo_unitario_origen' =>
                2200,

            'observacion' =>
                'Primera operación de compra.',
        ]
    );

    $detalleDos = $servicio->agregarDetalle(
        $this->usuarioOperativo->id,
        $lote->id,
        [
            'producto_id' =>
                $this->producto->id,

            'cantidad_esperada' =>
                1,

            'moneda_id' =>
                $bob->id,

            'costo_unitario_origen' =>
                2050,

            'observacion' =>
                'Segunda operación de compra.',
        ]
    );

    $this->assertNotSame(
        $detalleUno->id,
        $detalleDos->id
    );

    $this->assertDatabaseHas(
        'detalles_lotes',
        [
            'id' =>
                $detalleUno->id,

            'producto_id' =>
                $this->producto->id,

            'moneda_id' =>
                $bob->id,

            'cantidad_esperada' =>
                2,

            'costo_unitario_origen' =>
                2200,

            'costo_unitario_bob' =>
                2200,
        ]
    );

    $this->assertDatabaseHas(
        'detalles_lotes',
        [
            'id' =>
                $detalleDos->id,

            'producto_id' =>
                $this->producto->id,

            'moneda_id' =>
                $bob->id,

            'cantidad_esperada' =>
                1,

            'costo_unitario_origen' =>
                2050,

            'costo_unitario_bob' =>
                2050,
        ]
    );

    $this->assertSame(
        2,
        \App\Models\DetalleLote::query()
            ->where(
                'lote_id',
                $lote->id
            )
            ->where(
                'producto_id',
                $this->producto->id
            )
            ->count()
    );
}

    public function test_lote_cerrado_no_admite_nuevos_productos(): void
    {
        $servicio = app(LoteService::class);

        $lote = $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        $lote->update([
            'estado' => 'CERRADO',
        ]);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'lote abierto'
        );

        $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' =>
                    $this->producto->id,

                'cantidad_esperada' => 1,
            ]
        );
    }

    public function test_cantidad_esperada_debe_ser_mayor_a_cero(): void
    {
        $servicio = app(LoteService::class);

        $lote = $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        try {
            $servicio->agregarDetalle(
                $this->usuarioOperativo->id,
                $lote->id,
                [
                    'producto_id' =>
                        $this->producto->id,

                    'cantidad_esperada' => 0,
                ]
            );

            $this->fail(
                'Se esperaba una excepción de validación.'
            );

        } catch (ValidationException $exception) {

            $this->assertArrayHasKey(
                'cantidad_esperada',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'detalles_lotes',
            0
        );
    }
}