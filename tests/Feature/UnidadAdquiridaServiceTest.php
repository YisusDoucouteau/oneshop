<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\DetalleLote;
use App\Models\EventoLogisticoLote;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\TipoEventoLogistico;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\LoteService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnidadAdquiridaServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioOperativo;
    private User $vendedor;
    private Producto $producto;
    private Proveedor $proveedor;
    private DetalleLote $detalle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        /*
        |--------------------------------------------------------------------------
        | Usuario operativo
        |--------------------------------------------------------------------------
        */

        $this->usuarioOperativo =
            User::factory()->create([
                'activo' => true,
            ]);

        $rolOperativo = Rol::query()
            ->where(
                'codigo',
                'ADMIN_OPERATIVO'
            )
            ->firstOrFail();

        $this->usuarioOperativo
            ->roles()
            ->attach(
                $rolOperativo->id
            );

        /*
        |--------------------------------------------------------------------------
        | Vendedor sin permiso de gestión de importaciones
        |--------------------------------------------------------------------------
        */

        $this->vendedor =
            User::factory()->create([
                'activo' => true,
            ]);

        $rolVendedor = Rol::query()
            ->where(
                'codigo',
                'VENDEDOR'
            )
            ->firstOrFail();

        $this->vendedor
            ->roles()
            ->attach(
                $rolVendedor->id
            );

        /*
        |--------------------------------------------------------------------------
        | Proveedor
        |--------------------------------------------------------------------------
        */

        $this->proveedor =
            Proveedor::create([
                'nombre' =>
                    'Proveedor Unidad Adquirida Test',

                'pais' =>
                    'Estados Unidos',

                'ciudad' =>
                    'Miami',

                'telefono' =>
                    null,

                'correo' =>
                    null,

                'contacto' =>
                    null,

                'observacion' =>
                    null,

                'activo' =>
                    true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Producto serializado
        |--------------------------------------------------------------------------
        */

        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $marca = Marca::create([
            'nombre' =>
                'Dell Unidad Test',

            'descripcion' =>
                null,

            'activo' =>
                true,
        ]);

        $this->producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'UNIDAD-TEST-P001',

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

        /*
        |--------------------------------------------------------------------------
        | Lote de 10 unidades
        |--------------------------------------------------------------------------
        */

        $loteService =
            app(LoteService::class);

        $lote =
            $loteService->crearLote(
                $this->usuarioOperativo->id,
                [
                    'proveedor_id' =>
                        $this->proveedor->id,

                    'codigo' =>
                        'IMP-UNIDAD-TEST-001',

                    'referencia_compra' =>
                        'REF-UNIDAD-001',

                    'origen' =>
                        'Miami, Estados Unidos',

                    'observacion' =>
                        'Lote para pruebas de llegada a Cochabamba.',
                ]
            );

        $this->detalle =
            $loteService->agregarDetalle(
                $this->usuarioOperativo->id,
                $lote->id,
                [
                    'producto_id' =>
                        $this->producto->id,

                    'cantidad_esperada' =>
                        10,
                ]
            );
    }


    public function test_registra_llegada_parcial_en_cochabamba(): void
    {
        $servicio =
            app(
                UnidadAdquiridaService::class
            );

        $unidades =
            $servicio
                ->registrarLlegadaCochabamba(
                    $this->usuarioOperativo->id,
                    $this->detalle->id,
                    3,
                    '2026-08-24 09:30:00',
                    'Primera llegada parcial.'
                );

        $this->assertCount(
            3,
            $unidades
        );

        $almacenCochabamba =
            Almacen::query()
                ->where(
                    'codigo',
                    'COCHABAMBA'
                )
                ->firstOrFail();

        $this->assertSame(
            3,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $this->detalle->id
                )
                ->count()
        );

        foreach ($unidades as $unidad) {

            $this->assertSame(
                UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
                $unidad->estado
            );

            $this->assertSame(
                $almacenCochabamba->id,
                $unidad->almacen_actual_id
            );

            $this->assertSame(
                $this->producto->id,
                $unidad->producto_id
            );

            $this->assertNull(
                $unidad->adquisicion_directa_id
            );

            $this->assertSame(
                '2026-08-24 09:30:00',
                $unidad
                    ->fecha_llegada
                    ->format(
                        'Y-m-d H:i:s'
                    )
            );
        }
    }


    public function test_permite_llegadas_parciales_en_fechas_distintas(): void
    {
        $servicio =
            app(
                UnidadAdquiridaService::class
            );

        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                3,
                '2026-08-20 10:00:00'
            );

        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                4,
                '2026-08-22 15:00:00'
            );

        $this->assertSame(
            7,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $this->detalle->id
                )
                ->count()
        );

        $this->assertSame(
            3,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $this->detalle->id
                )
                ->where(
                    'fecha_llegada',
                    '2026-08-20 10:00:00'
                )
                ->count()
        );

        $this->assertSame(
            4,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $this->detalle->id
                )
                ->where(
                    'fecha_llegada',
                    '2026-08-22 15:00:00'
                )
                ->count()
        );
    }


    public function test_no_permite_superar_cantidad_comprada(): void
    {
        $servicio =
            app(
                UnidadAdquiridaService::class
            );

        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                9,
                '2026-08-24 09:00:00'
            );

        try {

            $servicio
                ->registrarLlegadaCochabamba(
                    $this->usuarioOperativo->id,
                    $this->detalle->id,
                    2,
                    '2026-08-25 09:00:00'
                );

            $this->fail(
                'Se esperaba validación porque solamente queda una unidad pendiente.'
            );

        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'cantidad',
                $exception->errors()
            );
        }

        /*
         * La transacción rechazada no debe crear
         * ninguna unidad adicional.
         */
        $this->assertSame(
            9,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $this->detalle->id
                )
                ->count()
        );
    }


    public function test_llegada_cochabamba_no_incrementa_recepcion_oruro(): void
    {
        $servicio =
            app(
                UnidadAdquiridaService::class
            );

        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                5,
                '2026-08-24 11:00:00'
            );

        $this->detalle->refresh();

        /*
         * Llegaron 5 físicamente a Cochabamba.
         */
        $this->assertSame(
            5,
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $this->detalle->id
                )
                ->count()
        );

        /*
         * Pero ninguna ha sido incorporada todavía
         * por Dani en Oruro.
         */
        $this->assertSame(
            0,
            $this->detalle
                ->cantidad_recibida
        );

        $this->assertDatabaseHas(
            'detalles_lotes',
            [
                'id' =>
                    $this->detalle->id,

                'cantidad_esperada' =>
                    10,

                'cantidad_recibida' =>
                    0,
            ]
        );
    }


    public function test_registra_evento_logistico_de_llegada_a_cochabamba(): void
    {
        $servicio =
            app(
                UnidadAdquiridaService::class
            );

        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                2,
                '2026-08-24 13:30:00',
                'Caja recibida sin daños.'
            );

        $tipoEvento =
            TipoEventoLogistico::query()
                ->where(
                    'codigo',
                    'RECEPCION_COCHABAMBA'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'eventos_logisticos_lotes',
            [
                'lote_id' =>
                    $this->detalle->lote_id,

                'tipo_evento_logistico_id' =>
                    $tipoEvento->id,

                'usuario_id' =>
                    $this->usuarioOperativo->id,

                'ubicacion' =>
                    'Depósito Cochabamba',
            ]
        );

        $evento =
            EventoLogisticoLote::query()
                ->where(
                    'lote_id',
                    $this->detalle->lote_id
                )
                ->where(
                    'tipo_evento_logistico_id',
                    $tipoEvento->id
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertStringContainsString(
            '2 unidad(es)',
            $evento->descripcion
        );

        $this->assertStringContainsString(
            '2/10',
            $evento->descripcion
        );

        $this->assertStringContainsString(
            'Caja recibida sin daños.',
            $evento->descripcion
        );
    }


    public function test_vendedor_no_puede_registrar_llegadas_a_cochabamba(): void
    {
        $servicio =
            app(
                UnidadAdquiridaService::class
            );

        try {

            $servicio
                ->registrarLlegadaCochabamba(
                    $this->vendedor->id,
                    $this->detalle->id,
                    1
                );

            $this->fail(
                'Se esperaba rechazo porque el vendedor no gestiona importaciones.'
            );

        } catch (
            ReglaNegocioException $exception
        ) {
            $this->assertStringContainsString(
                'no tiene permiso',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing(
            'unidades_adquiridas',
            [
                'detalle_lote_id' =>
                    $this->detalle->id,
            ]
        );
    }
    public function test_revision_incompleta_deja_unidad_en_revision(): void
{
    $servicio =
        app(UnidadAdquiridaService::class);

    $unidad =
        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                1,
                '2026-08-24 09:00:00'
            )
            ->firstOrFail();

    $unidad =
        $servicio
            ->registrarRevisionPreliminar(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'procesador' =>
                        'Intel Core i5',

                    'ram_gb' =>
                        16,
                ]
            );

    $this->assertSame(
        UnidadAdquirida::ESTADO_EN_REVISION,
        $unidad->estado
    );

    $this->assertSame(
        'Intel Core i5',
        $unidad->procesador
    );

    $this->assertSame(
        16,
        $unidad->ram_gb
    );

    $this->assertNotNull(
        $unidad->fecha_revision
    );

    $this->assertNull(
        $unidad->fecha_lista_envio
    );

    $this->assertSame(
        $this->usuarioOperativo->id,
        $unidad->revisado_por_id
    );
}


public function test_unidad_con_problema_queda_en_preparacion(): void
{
    $servicio =
        app(UnidadAdquiridaService::class);

    $unidad =
        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                1,
                '2026-08-24 10:00:00'
            )
            ->firstOrFail();

    $unidad =
        $servicio
            ->registrarRevisionPreliminar(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'procesador' =>
                        'Intel Core i5',

                    'ram_gb' =>
                        16,

                    'almacenamiento_gb' =>
                        512,

                    'tipo_almacenamiento' =>
                        'SSD',

                    'enciende' =>
                        true,

                    'tiene_sistema_operativo' =>
                        true,

                    'tiene_cargador' =>
                        false,

                    'requiere_servicio' =>
                        true,

                    'servicio_requerido' =>
                        'Comprar cargador compatible.',

                    'observacion_revision' =>
                        'Equipo funcional, llegó sin cargador.',
                ]
            );

    $this->assertSame(
        UnidadAdquirida::ESTADO_EN_PREPARACION,
        $unidad->estado
    );

    $this->assertTrue(
        $unidad->enciende
    );

    $this->assertTrue(
        $unidad->tiene_sistema_operativo
    );

    $this->assertFalse(
        $unidad->tiene_cargador
    );

    $this->assertTrue(
        $unidad->requiere_servicio
    );

    $this->assertSame(
        'Comprar cargador compatible.',
        $unidad->servicio_requerido
    );

    $this->assertNull(
        $unidad->fecha_lista_envio
    );
}


public function test_unidad_funcional_queda_lista_para_envio(): void
{
    $servicio =
        app(UnidadAdquiridaService::class);

    $unidad =
        $servicio
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                1,
                '2026-08-24 11:00:00'
            )
            ->firstOrFail();

    $unidad =
        $servicio
            ->registrarRevisionPreliminar(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'procesador' =>
                        'Intel Core i5-1145G7',

                    'generacion_procesador' =>
                        '11',

                    'ram_gb' =>
                        16,

                    'almacenamiento_gb' =>
                        512,

                    'tipo_almacenamiento' =>
                        'SSD',

                    'sistema_operativo' =>
                        'Windows 11 Pro',

                    'enciende' =>
                        true,

                    'tiene_sistema_operativo' =>
                        true,

                    'tiene_cargador' =>
                        true,

                    'requiere_servicio' =>
                        false,
                ]
            );

    $this->assertSame(
        UnidadAdquirida::ESTADO_LISTA_ENVIO,
        $unidad->estado
    );

    $this->assertTrue(
        $unidad->enciende
    );

    $this->assertTrue(
        $unidad->tiene_sistema_operativo
    );

    $this->assertTrue(
        $unidad->tiene_cargador
    );

    $this->assertFalse(
        $unidad->requiere_servicio
    );

    $this->assertNull(
        $unidad->servicio_requerido
    );

    $this->assertNotNull(
        $unidad->fecha_revision
    );

    $this->assertNotNull(
        $unidad->fecha_lista_envio
    );
}
}