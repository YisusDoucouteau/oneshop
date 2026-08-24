<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\DetalleLote;
use App\Models\IntervencionUnidadAdquirida;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\TipoCambio;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\IntervencionUnidadAdquiridaService;
use App\Services\LoteService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class IntervencionUnidadAdquiridaServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioOperativo;

    private User $vendedor;

    private Producto $productoEquipo;

    private Producto $componente;

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

        $rolOperativo =
            Rol::query()
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
        | Vendedor sin permiso
        |--------------------------------------------------------------------------
        */

        $this->vendedor =
            User::factory()->create([
                'activo' => true,
            ]);

        $rolVendedor =
            Rol::query()
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
        | Catálogo
        |--------------------------------------------------------------------------
        */

        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $marca =
            Marca::create([
                'nombre' =>
                    'Marca Intervencion Test',

                'descripcion' =>
                    null,

                'activo' =>
                    true,
            ]);

        $this->productoEquipo =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'INT-TEST-EQUIPO',

                'nombre' =>
                    'Dell Latitude Test',

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
         * Para estas pruebas lo importante es que exista
         * un producto activo que represente el componente.
         *
         * La clasificación definitiva del catálogo de
         * componentes se trabajará posteriormente.
         */
        $this->componente =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'INT-TEST-COMPONENTE',

                'nombre' =>
                    'Cargador compatible de prueba',

                'modelo' =>
                    null,

                'descripcion' =>
                    'Componente utilizado únicamente en pruebas.',

                'es_serializado' =>
                    false,

                'activo' =>
                    true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Proveedor y lote
        |--------------------------------------------------------------------------
        */

        $proveedor =
            Proveedor::create([
                'nombre' =>
                    'Proveedor Intervencion Test',

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

        $loteService =
            app(LoteService::class);

        $lote =
            $loteService->crearLote(
                $this->usuarioOperativo->id,
                [
                    'proveedor_id' =>
                        $proveedor->id,

                    'codigo' =>
                        'IMP-INTERVENCION-TEST-001',

                    'referencia_compra' =>
                        'REF-INTERVENCION-001',

                    'origen' =>
                        'Miami, Estados Unidos',

                    'observacion' =>
                        'Lote para pruebas de intervenciones de preinventario.',
                ]
            );

        $this->detalle =
            $loteService->agregarDetalle(
                $this->usuarioOperativo->id,
                $lote->id,
                [
                    'producto_id' =>
                        $this->productoEquipo->id,

                    'cantidad_esperada' =>
                        10,
                ]
            );
    }


    public function test_registra_componente_externo_en_bob(): void
    {
        $unidad =
            $this->crearUnidad();

        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        $intervencion =
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarComponenteExterno(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'cantidad' =>
                        1,

                    'moneda_id' =>
                        $bob->id,

                    'monto_origen' =>
                        180,

                    'fecha' =>
                        '2026-08-24 12:00:00',

                    'descripcion' =>
                        'Compra de cargador compatible.',
                ]
            );

        $this->assertSame(
            IntervencionUnidadAdquirida::TIPO_COMPONENTE,
            $intervencion->tipo
        );

        $this->assertSame(
            IntervencionUnidadAdquirida::ORIGEN_COMPRA_EXTERNA,
            $intervencion->origen_componente
        );

        $this->assertSame(
            $this->componente->id,
            $intervencion->producto_id
        );

        $this->assertSame(
            '180.00',
            $intervencion->monto_origen
        );

        $this->assertSame(
            '180.00',
            $intervencion->monto_bob
        );

        $this->assertNull(
            $intervencion->tipo_cambio_id
        );

        $this->assertSame(
            'REPUESTO_EXTERNO',
            $intervencion
                ->tipoCosto
                ->codigo
        );

        /*
         * Registrar el costo no equivale a confirmar
         * físicamente que la unidad ya tiene cargador.
         */
        $unidad->refresh();

        $this->assertSame(
            UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
            $unidad->estado
        );
    }


    public function test_registra_componente_externo_en_usd_con_tipo_cambio_historico(): void
    {
        $unidad =
            $this->crearUnidad();

        $usd =
            Moneda::query()
                ->where(
                    'codigo',
                    'USD'
                )
                ->firstOrFail();

        $intervencion =
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarComponenteExterno(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'moneda_id' =>
                        $usd->id,

                    'monto_origen' =>
                        25,

                    'tipo_cambio_aplicado' =>
                        11.50,

                    'descripcion' =>
                        'SSD comprado externamente.',
                ]
            );

        $this->assertSame(
            '25.00',
            $intervencion->monto_origen
        );

        $this->assertSame(
            '287.50',
            $intervencion->monto_bob
        );

        $this->assertNotNull(
            $intervencion->tipo_cambio_id
        );

        $tipoCambio =
            TipoCambio::findOrFail(
                $intervencion->tipo_cambio_id
            );

        $this->assertSame(
            $usd->id,
            $tipoCambio->moneda_origen_id
        );

        $this->assertSame(
            '11.500000',
            $tipoCambio->valor
        );

        $this->assertSame(
            'MANUAL_OPERACION',
            $tipoCambio->fuente
        );
    }


    public function test_usdt_conserva_su_propio_tipo_de_cambio(): void
    {
        $unidad =
            $this->crearUnidad();

        $usdt =
            Moneda::query()
                ->where(
                    'codigo',
                    'USDT'
                )
                ->firstOrFail();

        $usd =
            Moneda::query()
                ->where(
                    'codigo',
                    'USD'
                )
                ->firstOrFail();

        $intervencion =
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarComponenteExterno(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'moneda_id' =>
                        $usdt->id,

                    'monto_origen' =>
                        20,

                    'tipo_cambio_aplicado' =>
                        11.72,

                    'descripcion' =>
                        'Componente pagado mediante USDT.',
                ]
            );

        $this->assertSame(
            '234.40',
            $intervencion->monto_bob
        );

        $tipoCambio =
            TipoCambio::findOrFail(
                $intervencion->tipo_cambio_id
            );

        $this->assertSame(
            $usdt->id,
            $tipoCambio->moneda_origen_id
        );

        $this->assertNotSame(
            $usd->id,
            $tipoCambio->moneda_origen_id
        );

        $this->assertSame(
            '11.720000',
            $tipoCambio->valor
        );
    }


    public function test_registra_servicio_y_unidad_vuelve_a_preparacion(): void
    {
        $unidad =
            $this->crearUnidad();

        /*
         * Primero confirmamos que la unidad estaba
         * funcional y lista para despacho.
         */
        $unidad =
            app(
                UnidadAdquiridaService::class
            )->registrarRevisionPreliminar(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
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

        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        $intervencion =
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarServicio(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'descripcion' =>
                        'Reprogramación de BIOS.',

                    'fecha_inicio' =>
                        '2026-08-24 14:00:00',

                    'fecha_fin' =>
                        '2026-08-24 16:30:00',

                    'moneda_id' =>
                        $bob->id,

                    'monto_origen' =>
                        80,

                    'resultado' =>
                        'BIOS reprogramada correctamente.',
                ]
            );

        $this->assertSame(
            IntervencionUnidadAdquirida::TIPO_SERVICIO,
            $intervencion->tipo
        );

        $this->assertSame(
            'SERVICIO_EXTERNO',
            $intervencion
                ->tipoCosto
                ->codigo
        );

        $this->assertSame(
            '80.00',
            $intervencion->monto_bob
        );

        $this->assertSame(
            '2026-08-24 14:00:00',
            $intervencion
                ->fecha_inicio
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-08-24 16:30:00',
            $intervencion
                ->fecha_fin
                ->format('Y-m-d H:i:s')
        );

        $unidad->refresh();

        $this->assertSame(
            UnidadAdquirida::ESTADO_EN_PREPARACION,
            $unidad->estado
        );

        $this->assertTrue(
            $unidad->requiere_servicio
        );

        $this->assertSame(
            'Reprogramación de BIOS.',
            $unidad->servicio_requerido
        );

        $this->assertNull(
            $unidad->fecha_lista_envio
        );
    }


    public function test_no_permite_fecha_fin_anterior_al_inicio(): void
    {
        $unidad =
            $this->crearUnidad();

        try {
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarServicio(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'descripcion' =>
                        'Servicio con fechas inválidas.',

                    'fecha_inicio' =>
                        '2026-08-24 16:00:00',

                    'fecha_fin' =>
                        '2026-08-24 15:00:00',
                ]
            );

            $this->fail(
                'Se esperaba validación por fecha de finalización inválida.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'fecha_fin',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'intervenciones_unidades_adquiridas',
            0
        );
    }


    public function test_vendedor_no_puede_registrar_intervenciones(): void
    {
        $unidad =
            $this->crearUnidad();

        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        try {
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarComponenteExterno(
                $this->vendedor->id,
                $unidad->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'moneda_id' =>
                        $bob->id,

                    'monto_origen' =>
                        100,
                ]
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

        $this->assertDatabaseCount(
            'intervenciones_unidades_adquiridas',
            0
        );
    }


    public function test_no_permite_intervenir_unidad_que_ya_fue_enviada(): void
    {
        $unidad =
            $this->crearUnidad();

        $unidad->update([
            'estado' =>
                UnidadAdquirida::ESTADO_ENVIADA,
        ]);

        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        try {
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarComponenteExterno(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'producto_id' =>
                        $this->componente->id,

                    'moneda_id' =>
                        $bob->id,

                    'monto_origen' =>
                        100,
                ]
            );

            $this->fail(
                'Se esperaba rechazo porque la unidad ya salió de la etapa de preparación.'
            );
        } catch (
            ReglaNegocioException $exception
        ) {
            $this->assertStringContainsString(
                'salió de la etapa',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'intervenciones_unidades_adquiridas',
            0
        );
    }


    public function test_no_permite_moneda_sin_monto_de_costo(): void
    {
        $unidad =
            $this->crearUnidad();

        $bob =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        try {
            app(
                IntervencionUnidadAdquiridaService::class
            )->registrarServicio(
                $this->usuarioOperativo->id,
                $unidad->id,
                [
                    'descripcion' =>
                        'Servicio sin costo definido.',

                    'moneda_id' =>
                        $bob->id,
                ]
            );

            $this->fail(
                'Se esperaba validación porque se indicó moneda sin monto.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'monto_origen',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'intervenciones_unidades_adquiridas',
            0
        );
    }
public function test_asigna_componente_desde_stock_y_descuenta_existencia(): void
{
    $unidad =
        $this->crearUnidad();

    DB::table(
        'existencias_productos'
    )->insert([
        'producto_id' =>
            $this->componente->id,

        'almacen_id' =>
            $unidad->almacen_actual_id,

        'cantidad_disponible' =>
            5,

        'cantidad_reservada' =>
            1,

        'created_at' =>
            now(),

        'updated_at' =>
            now(),
    ]);

    $intervencion =
        app(
            IntervencionUnidadAdquiridaService::class
        )->asignarComponenteDesdeStock(
            $this->usuarioOperativo->id,
            $unidad->id,
            [
                'producto_id' =>
                    $this->componente->id,

                'cantidad' =>
                    2,

                'fecha' =>
                    '2026-08-24 17:00:00',

                'descripcion' =>
                    'Asignación de cargadores desde stock.',
            ]
        );

    $this->assertSame(
        IntervencionUnidadAdquirida::TIPO_COMPONENTE,
        $intervencion->tipo
    );

    $this->assertSame(
        IntervencionUnidadAdquirida::ORIGEN_STOCK,
        $intervencion->origen_componente
    );

    $this->assertSame(
        2,
        $intervencion->cantidad
    );

    $this->assertSame(
        $unidad->almacen_actual_id,
        $intervencion->almacen_id
    );

    $this->assertNotNull(
        $intervencion->movimiento_inventario_id
    );

    /*
     * Todavía no inventamos una valoración
     * monetaria del componente tomado de stock.
     */
    $this->assertNull(
        $intervencion->monto_origen
    );

    $this->assertNull(
        $intervencion->monto_bob
    );

    $this->assertDatabaseHas(
        'existencias_productos',
        [
            'producto_id' =>
                $this->componente->id,

            'almacen_id' =>
                $unidad->almacen_actual_id,

            'cantidad_disponible' =>
                3,

            /*
             * La asignación no debe alterar
             * las reservas existentes.
             */
            'cantidad_reservada' =>
                1,
        ]
    );

    $movimiento =
        MovimientoInventario::findOrFail(
            $intervencion
                ->movimiento_inventario_id
        );

    $this->assertSame(
        -2,
        $movimiento->cambio_disponible
    );

    $this->assertSame(
        0,
        $movimiento->cambio_reservado
    );

    $this->assertSame(
        3,
        $movimiento
            ->saldo_disponible_resultante
    );

    $this->assertSame(
        1,
        $movimiento
            ->saldo_reservado_resultante
    );

    $this->assertSame(
        'ASIGNACION_COMPONENTE',
        $movimiento
            ->tipoMovimiento
            ->codigo
    );

    $this->assertSame(
        'INTERVENCION_UNIDAD_ADQUIRIDA',
        $movimiento->tipo_referencia
    );

    $this->assertSame(
        $intervencion->id,
        $movimiento->referencia_id
    );

    $this->assertSame(
        '2026-08-24 17:00:00',
        $movimiento
            ->fecha_movimiento
            ->format('Y-m-d H:i:s')
    );
}


public function test_no_permite_asignar_mas_componentes_que_el_stock_disponible(): void
{
    $unidad =
        $this->crearUnidad();

    DB::table(
        'existencias_productos'
    )->insert([
        'producto_id' =>
            $this->componente->id,

        'almacen_id' =>
            $unidad->almacen_actual_id,

        'cantidad_disponible' =>
            1,

        'cantidad_reservada' =>
            0,

        'created_at' =>
            now(),

        'updated_at' =>
            now(),
    ]);

    try {
        app(
            IntervencionUnidadAdquiridaService::class
        )->asignarComponenteDesdeStock(
            $this->usuarioOperativo->id,
            $unidad->id,
            [
                'producto_id' =>
                    $this->componente->id,

                'cantidad' =>
                    2,
            ]
        );

        $this->fail(
            'Se esperaba rechazo por stock insuficiente.'
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
     * El intento rechazado no debe modificar
     * la existencia.
     */
    $this->assertDatabaseHas(
        'existencias_productos',
        [
            'producto_id' =>
                $this->componente->id,

            'almacen_id' =>
                $unidad->almacen_actual_id,

            'cantidad_disponible' =>
                1,

            'cantidad_reservada' =>
                0,
        ]
    );

    $this->assertDatabaseCount(
        'intervenciones_unidades_adquiridas',
        0
    );

    $this->assertDatabaseCount(
        'movimientos_inventario',
        0
    );
}


public function test_no_permite_asignar_componente_si_no_existe_stock_en_el_almacen(): void
{
    $unidad =
        $this->crearUnidad();

    try {
        app(
            IntervencionUnidadAdquiridaService::class
        )->asignarComponenteDesdeStock(
            $this->usuarioOperativo->id,
            $unidad->id,
            [
                'producto_id' =>
                    $this->componente->id,

                'cantidad' =>
                    1,
            ]
        );

        $this->fail(
            'Se esperaba rechazo porque no existe stock registrado en el almacén.'
        );
    } catch (
        ValidationException $exception
    ) {
        $this->assertArrayHasKey(
            'producto_id',
            $exception->errors()
        );
    }

    $this->assertDatabaseCount(
        'intervenciones_unidades_adquiridas',
        0
    );

    $this->assertDatabaseCount(
        'movimientos_inventario',
        0
    );
}


public function test_no_permite_usar_producto_serializado_como_componente_de_stock(): void
{
    $unidad =
        $this->crearUnidad();

    /*
     * El propio producto principal de la laptop
     * es serializado, por lo que no debe consumirse
     * mediante existencias cuantitativas.
     */
    DB::table(
        'existencias_productos'
    )->insert([
        'producto_id' =>
            $this->productoEquipo->id,

        'almacen_id' =>
            $unidad->almacen_actual_id,

        'cantidad_disponible' =>
            2,

        'cantidad_reservada' =>
            0,

        'created_at' =>
            now(),

        'updated_at' =>
            now(),
    ]);

    try {
        app(
            IntervencionUnidadAdquiridaService::class
        )->asignarComponenteDesdeStock(
            $this->usuarioOperativo->id,
            $unidad->id,
            [
                'producto_id' =>
                    $this->productoEquipo->id,

                'cantidad' =>
                    1,
            ]
        );

        $this->fail(
            'Se esperaba rechazo porque el producto es serializado.'
        );
    } catch (
        ReglaNegocioException $exception
    ) {
        $this->assertStringContainsString(
            'serializado',
            $exception->getMessage()
        );
    }

    $this->assertDatabaseHas(
        'existencias_productos',
        [
            'producto_id' =>
                $this->productoEquipo->id,

            'almacen_id' =>
                $unidad->almacen_actual_id,

            'cantidad_disponible' =>
                2,
        ]
    );

    $this->assertDatabaseCount(
        'intervenciones_unidades_adquiridas',
        0
    );

    $this->assertDatabaseCount(
        'movimientos_inventario',
        0
    );
}
public function test_si_falla_movimiento_se_revierte_toda_la_asignacion_desde_stock(): void
{
    $unidad =
        $this->crearUnidad();

    DB::table(
        'existencias_productos'
    )->insert([
        'producto_id' =>
            $this->componente->id,

        'almacen_id' =>
            $unidad->almacen_actual_id,

        'cantidad_disponible' =>
            5,

        'cantidad_reservada' =>
            0,

        'created_at' =>
            now(),

        'updated_at' =>
            now(),
    ]);

    /*
     * Provocamos un fallo exactamente cuando Eloquent
     * intenta crear MovimientoInventario.
     *
     * Para ese punto, el servicio ya creó la intervención
     * y actualizó existencias dentro de la transacción.
     */
    $evento =
        'eloquent.creating: '
        . MovimientoInventario::class;

    Event::listen(
        $evento,
        function () {
            throw new RuntimeException(
                'Fallo provocado para comprobar rollback.'
            );
        }
    );

    try {
        app(
            IntervencionUnidadAdquiridaService::class
        )->asignarComponenteDesdeStock(
            $this->usuarioOperativo->id,
            $unidad->id,
            [
                'producto_id' =>
                    $this->componente->id,

                'cantidad' =>
                    2,

                'descripcion' =>
                    'Asignación que debe revertirse.',
            ]
        );

        $this->fail(
            'Se esperaba el fallo provocado al crear el movimiento.'
        );
    } catch (
        RuntimeException $exception
    ) {
        $this->assertSame(
            'Fallo provocado para comprobar rollback.',
            $exception->getMessage()
        );
    } finally {
        /*
         * Quitamos únicamente el listener temporal
         * utilizado para esta prueba.
         */
        Event::forget(
            $evento
        );
    }

    /*
     * El stock debe quedar exactamente como estaba.
     */
    $this->assertDatabaseHas(
        'existencias_productos',
        [
            'producto_id' =>
                $this->componente->id,

            'almacen_id' =>
                $unidad->almacen_actual_id,

            'cantidad_disponible' =>
                5,

            'cantidad_reservada' =>
                0,
        ]
    );

    /*
     * La intervención creada antes del fallo
     * también debe haberse revertido.
     */
    $this->assertDatabaseCount(
        'intervenciones_unidades_adquiridas',
        0
    );

    /*
     * Tampoco debe quedar un movimiento parcial.
     */
    $this->assertDatabaseCount(
        'movimientos_inventario',
        0
    );
}

    private function crearUnidad(): UnidadAdquirida
    {
        return app(
            UnidadAdquiridaService::class
        )
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $this->detalle->id,
                1,
                '2026-08-24 09:00:00'
            )
            ->firstOrFail();
    }
}