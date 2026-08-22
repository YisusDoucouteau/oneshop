<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\User;
use App\Services\LoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioOperativo;
    private User $vendedor;
    private Producto $producto;
    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuarioOperativo = User::factory()->create([
            'activo' => true,
        ]);

        $rolOperativo = Rol::query()
            ->where('codigo', 'ADMIN_OPERATIVO')
            ->firstOrFail();

        $this->usuarioOperativo
            ->roles()
            ->attach($rolOperativo->id);

        $this->vendedor = User::factory()->create([
            'activo' => true,
        ]);

        $rolVendedor = Rol::query()
            ->where('codigo', 'VENDEDOR')
            ->firstOrFail();

        $this->vendedor
            ->roles()
            ->attach($rolVendedor->id);

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor de prueba USA',
            'pais' => 'Estados Unidos',
            'ciudad' => 'Miami',
            'telefono' => null,
            'correo' => null,
            'contacto' => null,
            'observacion' => null,
            'activo' => true,
        ]);

        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $marca = Marca::create([
            'nombre' => 'Dell Lote Test',
            'descripcion' => null,
            'activo' => true,
        ]);

        $this->producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'LOTE-TEST-P001',
            'nombre' => 'Dell Latitude',
            'modelo' => '5420',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);
    }

    private function datosLote(
        string $codigo = 'IMP-TEST-001'
    ): array {
        return [
            'proveedor_id' => $this->proveedor->id,
            'codigo' => $codigo,
            'referencia_compra' => 'REF-TEST-001',
            'origen' => 'Miami, Estados Unidos',
            'observacion' => 'Lote generado para pruebas automatizadas.',
        ];
    }

    public function test_usuario_autorizado_puede_crear_lote(): void
    {
        $lote = app(LoteService::class)->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'codigo' => 'IMP-TEST-001',
            'estado' => 'ABIERTO',
            'proveedor_id' => $this->proveedor->id,
        ]);
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
            app(LoteService::class)->crearLote(
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

        $this->assertDatabaseMissing('lotes', [
            'codigo' => 'IMP-TEST-001',
        ]);
    }

    public function test_vendedor_no_puede_gestionar_importaciones(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        app(LoteService::class)->crearLote(
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
                'producto_id' => $this->producto->id,
                'cantidad_esperada' => 3,
            ]
        );

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $detalle->id,
            'lote_id' => $lote->id,
            'producto_id' => $this->producto->id,
            'cantidad_esperada' => 3,
            'cantidad_recibida' => 0,
        ]);
    }

    public function test_permite_mismo_producto_en_dos_lineas_del_mismo_lote(): void
    {
        $bob = Moneda::query()
            ->where('codigo', 'BOB')
            ->firstOrFail();

        $servicio = app(LoteService::class);

        $lote = $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        $detalleUno = $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' => $this->producto->id,
                'cantidad_esperada' => 2,
                'moneda_id' => $bob->id,
                'costo_unitario_origen' => 2200,
                'observacion' => 'Primera operación de compra.',
            ]
        );

        $detalleDos = $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' => $this->producto->id,
                'cantidad_esperada' => 1,
                'moneda_id' => $bob->id,
                'costo_unitario_origen' => 2050,
                'observacion' => 'Segunda operación de compra.',
            ]
        );

        $this->assertNotSame(
            $detalleUno->id,
            $detalleDos->id
        );

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $detalleUno->id,
            'producto_id' => $this->producto->id,
            'moneda_id' => $bob->id,
            'cantidad_esperada' => 2,
            'costo_unitario_origen' => 2200,
            'costo_unitario_bob' => 2200,
        ]);

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $detalleDos->id,
            'producto_id' => $this->producto->id,
            'moneda_id' => $bob->id,
            'cantidad_esperada' => 1,
            'costo_unitario_origen' => 2050,
            'costo_unitario_bob' => 2050,
        ]);

        $this->assertSame(
            2,
            \App\Models\DetalleLote::query()
                ->where('lote_id', $lote->id)
                ->where('producto_id', $this->producto->id)
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

        $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' => $this->producto->id,
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

        $this->expectException(
            ValidationException::class
        );

        $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' => $this->producto->id,
                'cantidad_esperada' => 0,
            ]
        );
    }

    public function test_precio_unitario_exige_moneda(): void
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
                    'producto_id' => $this->producto->id,
                    'cantidad_esperada' => 1,
                    'costo_unitario_origen' => 233,
                ]
            );

            $this->fail(
                'Se esperaba validación porque el precio no tiene moneda.'
            );

        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'moneda_id',
                $exception->errors()
            );
        }

        $this->assertDatabaseMissing('detalles_lotes', [
            'lote_id' => $lote->id,
            'producto_id' => $this->producto->id,
            'costo_unitario_origen' => 233,
        ]);
    }

    public function test_compra_en_usd_exige_tipo_de_cambio_aplicado(): void
    {
        $usd = Moneda::query()
            ->where('codigo', 'USD')
            ->firstOrFail();

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
                    'producto_id' => $this->producto->id,
                    'cantidad_esperada' => 1,
                    'moneda_id' => $usd->id,
                    'costo_unitario_origen' => 233,
                ]
            );

            $this->fail(
                'Se esperaba validación porque la compra USD no tiene TC aplicado.'
            );

        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'tipo_cambio_aplicado',
                $exception->errors()
            );
        }

        $this->assertDatabaseMissing('detalles_lotes', [
            'lote_id' => $lote->id,
            'producto_id' => $this->producto->id,
            'costo_unitario_origen' => 233,
        ]);
    }

    public function test_compra_usd_calcula_bob_y_guarda_tc_aplicado(): void
    {
        $usd = Moneda::query()
            ->where('codigo', 'USD')
            ->firstOrFail();

        $servicio = app(LoteService::class);

        $lote = $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        $detalle = $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' => $this->producto->id,
                'cantidad_esperada' => 1,
                'moneda_id' => $usd->id,
                'costo_unitario_origen' => 233,
                'tipo_cambio_aplicado' => 11.78,
                'costo_unitario_bob' => 999999,
            ]
        );

        $this->assertNotNull(
            $detalle->tipo_cambio_compra_id
        );

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $detalle->id,
            'moneda_id' => $usd->id,
            'costo_unitario_origen' => 233,
            'costo_unitario_bob' => 2744.74,
        ]);

        $this->assertDatabaseHas('tipos_cambio', [
            'id' => $detalle->tipo_cambio_compra_id,
            'valor' => 11.78,
            'fuente' => 'MANUAL_OPERACION',
            'registrado_por_id' => $this->usuarioOperativo->id,
        ]);

        $this->assertDatabaseMissing('detalles_lotes', [
            'id' => $detalle->id,
            'costo_unitario_bob' => 999999,
        ]);
    }

    public function test_compra_bob_mantiene_mismo_valor_y_no_crea_tc(): void
    {
        $bob = Moneda::query()
            ->where('codigo', 'BOB')
            ->firstOrFail();

        $servicio = app(LoteService::class);

        $lote = $servicio->crearLote(
            $this->usuarioOperativo->id,
            $this->datosLote()
        );

        $cantidadTiposCambioAntes =
            \App\Models\TipoCambio::count();

        $detalle = $servicio->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' => $this->producto->id,
                'cantidad_esperada' => 1,
                'moneda_id' => $bob->id,
                'costo_unitario_origen' => 2700,
            ]
        );

        $this->assertNull(
            $detalle->tipo_cambio_compra_id
        );

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $detalle->id,
            'moneda_id' => $bob->id,
            'costo_unitario_origen' => 2700,
            'costo_unitario_bob' => 2700,
            'tipo_cambio_compra_id' => null,
        ]);

        $this->assertSame(
            $cantidadTiposCambioAntes,
            \App\Models\TipoCambio::count()
        );
    }
}