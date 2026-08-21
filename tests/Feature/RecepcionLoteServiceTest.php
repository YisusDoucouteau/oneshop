<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\User;
use App\Services\LoteService;
use App\Services\RecepcionLoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecepcionLoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private User $vendedor;
    private Producto $producto;
    private Proveedor $proveedor;
    private Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        /*
        |--------------------------------------------------------------------------
        | Administrador operativo
        |--------------------------------------------------------------------------
        */

        $this->operativo = User::factory()->create([
            'activo' => true,
        ]);

        $rolOperativo = Rol::where(
            'codigo',
            'ADMIN_OPERATIVO'
        )->firstOrFail();

        $this->operativo
            ->roles()
            ->attach($rolOperativo->id);

        /*
        |--------------------------------------------------------------------------
        | Vendedor
        |--------------------------------------------------------------------------
        */

        $this->vendedor = User::factory()->create([
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
            'nombre' => 'Proveedor USA Recepción',
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
            'nombre' => 'Dell Recepción',
            'descripcion' => null,
            'activo' => true,
        ]);

        $this->producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'REC-P001',
            'nombre' => 'Dell Latitude',
            'modelo' => '5420',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        $this->almacen = Almacen::where(
            'codigo',
            'COCHABAMBA'
        )->firstOrFail();
    }

    private function crearDetalle(
        int $cantidadEsperada = 2
    ) {
        $loteService = app(
            LoteService::class
        );

        $lote = $loteService->crearLote(
            $this->operativo->id,
            [
                'proveedor_id' =>
                    $this->proveedor->id,

                'codigo' =>
                    'IMP-RECEPCION-001',

                'referencia_compra' =>
                    'REF-REC-001',

                'origen' =>
                    'Miami, Estados Unidos',
            ]
        );

        return $loteService->agregarDetalle(
            $this->operativo->id,
            $lote->id,
            [
                'producto_id' =>
                    $this->producto->id,

                'cantidad_esperada' =>
                    $cantidadEsperada,
            ]
        );
    }

    private function datosEquipo(
        string $codigo
    ): array {
        return [
            'almacen_actual_id' =>
                $this->almacen->id,

            'codigo_interno' =>
                $codigo,

            'serial_fabricante' =>
                'SER-' . $codigo,

            'procesador' =>
                'Intel Core i5',

            'ram_gb' => 16,

            'almacenamiento_gb' => 512,

            'tipo_almacenamiento' =>
                'SSD',

            'bateria_porcentaje' => 90,
        ];
    }

    public function test_recibir_equipo_lo_asocia_al_lote_y_actualiza_cantidad(): void
    {
        $detalle = $this->crearDetalle(2);

        $equipo = app(
            RecepcionLoteService::class
        )->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-0001')
        );

        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'codigo_interno' => 'REC-0001',
            'detalle_lote_id' => $detalle->id,
            'producto_id' =>
                $this->producto->id,
        ]);

        $this->assertSame(
            'RECIBIDO',
            $equipo->estadoActual->codigo
        );

        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' => $detalle->id,
                'cantidad_esperada' => 2,
                'cantidad_recibida' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'lotes',
            [
                'id' => $detalle->lote_id,
                'estado' =>
                    'RECEPCION_PARCIAL',
            ]
        );
    }

    public function test_ultima_unidad_marca_lote_como_recibido(): void
    {
        $detalle = $this->crearDetalle(2);

        $servicio = app(
            RecepcionLoteService::class
        );

        $servicio->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-0010')
        );

        $servicio->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-0011')
        );

        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' => $detalle->id,
                'cantidad_recibida' => 2,
            ]
        );

        $this->assertDatabaseHas(
            'lotes',
            [
                'id' => $detalle->lote_id,
                'estado' => 'RECIBIDO',
            ]
        );
    }

    public function test_no_permite_recibir_mas_unidades_de_las_esperadas(): void
    {
        $detalle = $this->crearDetalle(1);

        $servicio = app(
            RecepcionLoteService::class
        );

        $servicio->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-0020')
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'cantidad esperada'
        );

        $servicio->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-0021')
        );
    }

    public function test_evento_de_recepcion_del_lote_no_se_duplica_por_equipo(): void
    {
        $detalle = $this->crearDetalle(2);

        $servicio = app(
            RecepcionLoteService::class
        );

        $servicio->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-0030')
        );

        $servicio->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-0031')
        );

        $this->assertSame(
            1,
            \App\Models\EventoLogisticoLote::query()
                ->where(
                    'lote_id',
                    $detalle->lote_id
                )
                ->whereHas(
                    'tipoEvento',
                    fn ($query) =>
                        $query->where(
                            'codigo',
                            'RECEPCION_COCHABAMBA'
                        )
                )
                ->count()
        );
    }

    public function test_si_falla_registro_del_equipo_no_incrementa_recepcion(): void
    {
        $detalle = $this->crearDetalle(2);

        $servicio = app(
            RecepcionLoteService::class
        );

        $servicio->recibirEquipo(
            $this->operativo->id,
            $detalle->id,
            $this->datosEquipo('REC-DUPLICADO')
        );

        try {
            $servicio->recibirEquipo(
                $this->operativo->id,
                $detalle->id,
                $this->datosEquipo('REC-DUPLICADO')
            );

            $this->fail(
                'Se esperaba una excepción de validación.'
            );

        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'codigo_interno',
                $exception->errors()
            );
        }

        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' => $detalle->id,
                'cantidad_recibida' => 1,
            ]
        );

        $this->assertSame(
            1,
            \App\Models\Equipo::where(
                'codigo_interno',
                'REC-DUPLICADO'
            )->count()
        );
    }

    public function test_vendedor_no_puede_recibir_equipos_de_importacion(): void
    {
        $detalle = $this->crearDetalle(1);

        $this->expectException(
            ReglaNegocioException::class
        );

        app(
            RecepcionLoteService::class
        )->recibirEquipo(
            $this->vendedor->id,
            $detalle->id,
            $this->datosEquipo('REC-0040')
        );
    }
}