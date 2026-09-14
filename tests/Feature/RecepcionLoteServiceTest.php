<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\LoteService;
use App\Services\RecepcionLoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'categoria_producto_id' => $categoria->id,
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
                'proveedor_id' => $this->proveedor->id,
                'codigo' => 'IMP-RECEPCION-001',
                'referencia_compra' => 'REF-REC-001',
                'origen' => 'Miami, Estados Unidos',
            ]
        );

        return $loteService->agregarDetalle(
            $this->operativo->id,
            $lote->id,
            [
                'producto_id' => $this->producto->id,
                'cantidad_esperada' => $cantidadEsperada,
            ]
        );
    }

    private function datosUnidad(
        int $cantidad = 1,
        ?string $observacion = null
    ): array {
        return [
            'cantidad' => $cantidad,
            'observacion' => $observacion,
        ];
    }

    public function test_recibir_unidad_crea_unidad_adquirida_en_cochabamba(): void
    {
        $detalle = $this->crearDetalle(2);

        $unidad = app(
            RecepcionLoteService::class
        )->recibirUnidad(
            $this->operativo->id,
            $detalle->id,
            $this->datosUnidad(
                1,
                'Primera unidad recibida en Cochabamba.'
            )
        );

        $this->assertDatabaseHas(
            'unidades_adquiridas',
            [
                'id' => $unidad->id,
                'detalle_lote_id' => $detalle->id,
                'producto_id' => $this->producto->id,
                'almacen_actual_id' => $this->almacen->id,
                'estado' => UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
            ]
        );

        $this->assertNotNull(
            $unidad->codigo_trazabilidad
        );

        /*
         * En Cochabamba todavía NO existe Equipo.
         */
        $this->assertNull(
            $unidad->equipo_id
        );

        /*
         * cantidad_recibida queda reservada
         * para la etapa posterior en Oruro.
         */
        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' => $detalle->id,
                'cantidad_esperada' => 2,
                'cantidad_recibida' => 0,
            ]
        );
    }

    public function test_puede_recibir_varias_unidades_del_mismo_detalle(): void
    {
        $detalle = $this->crearDetalle(2);

        $servicio = app(
            RecepcionLoteService::class
        );

        $servicio->recibirUnidad(
            $this->operativo->id,
            $detalle->id,
            $this->datosUnidad(1)
        );

        $servicio->recibirUnidad(
            $this->operativo->id,
            $detalle->id,
            $this->datosUnidad(1)
        );

        $this->assertSame(
            2,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $detalle->id
                )
                ->where(
                    'estado',
                    '!=',
                    UnidadAdquirida::ESTADO_ANULADA
                )
                ->count()
        );

        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' => $detalle->id,
                'cantidad_recibida' => 0,
            ]
        );
    }

    public function test_no_permite_recibir_mas_unidades_de_las_compradas(): void
    {
        $detalle = $this->crearDetalle(1);

        $servicio = app(
            RecepcionLoteService::class
        );

        $servicio->recibirUnidad(
            $this->operativo->id,
            $detalle->id,
            $this->datosUnidad(1)
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $servicio->recibirUnidad(
            $this->operativo->id,
            $detalle->id,
            $this->datosUnidad(1)
        );
    }

    public function test_una_llegada_de_varias_unidades_genera_un_solo_evento(): void
{
    $detalle = $this->crearDetalle(2);

    $servicio = app(
        RecepcionLoteService::class
    );

    /*
     * Llegan físicamente dos unidades en una sola recepción.
     *
     * Deben crearse dos UnidadAdquirida,
     * pero solamente un evento logístico de llegada.
     */
    $servicio->recibirUnidad(
        $this->operativo->id,
        $detalle->id,
        $this->datosUnidad(2)
    );

    $this->assertSame(
        2,
        UnidadAdquirida::query()
            ->where(
                'detalle_lote_id',
                $detalle->id
            )
            ->count()
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

    public function test_si_falla_la_llegada_no_crea_unidades_adicionales(): void
    {
        $detalle = $this->crearDetalle(1);

        $servicio = app(
            RecepcionLoteService::class
        );

        $servicio->recibirUnidad(
            $this->operativo->id,
            $detalle->id,
            $this->datosUnidad(1)
        );

        try {
            $servicio->recibirUnidad(
                $this->operativo->id,
                $detalle->id,
                $this->datosUnidad(1)
            );

            $this->fail(
                'Se esperaba una excepción por superar la cantidad comprada.'
            );

        } catch (ReglaNegocioException $exception) {
            $this->assertNotEmpty(
                $exception->getMessage()
            );
        }

        $this->assertSame(
            1,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $detalle->id
                )
                ->where(
                    'estado',
                    '!=',
                    UnidadAdquirida::ESTADO_ANULADA
                )
                ->count()
        );

        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' => $detalle->id,
                'cantidad_recibida' => 0,
            ]
        );
    }

    public function test_vendedor_no_puede_recibir_unidades_de_importacion(): void
    {
        $detalle = $this->crearDetalle(1);

        $this->expectException(
            ReglaNegocioException::class
        );

        app(
            RecepcionLoteService::class
        )->recibirUnidad(
            $this->vendedor->id,
            $detalle->id,
            $this->datosUnidad(1)
        );
    }
}