<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Garantia;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\CasoGarantiaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CasoGarantiaCambioEquipoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $administrador;
    protected User $tecnico;
    protected User $vendedor;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->administrador =
            $this->usuarioConRol(
                'ADMINISTRADOR'
            );

        $this->tecnico =
            $this->usuarioConRol(
                'TECNICO'
            );

        $this->vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $this->cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente cambio garantía',

            'telefono' =>
                '70000994',

            'activo' =>
                true,
        ]);
    }

    public function test_administrador_puede_registrar_cambio_de_equipo(): void
    {
        [
            $caso,
            $equipoSaliente,
            $garantia,
        ] = $this->crearCasoDiagnosticado();

        $equipoEntrante =
            $this->crearEquipoReemplazo(
                $equipoSaliente
            );

        $venta =
            $garantia
                ->load('detalleVenta.venta')
                ->detalleVenta
                ->venta;

        $cambio =
            app(CasoGarantiaService::class)
                ->registrarCambioEquipo(
                    casoId:
                        $caso->id,

                    equipoSalienteId:
                        $equipoSaliente->id,

                    equipoEntranteId:
                        $equipoEntrante->id,

                    usuarioId:
                        $this->administrador->id,

                    motivo:
                        'Se autoriza reemplazo por falla confirmada.',

                    observacion:
                        'Cambio ejecutado como prueba automatizada 10C.'
                );

        $caso->refresh();
        $equipoSaliente->refresh();
        $equipoEntrante->refresh();
        $garantia->refresh();
        $venta->refresh();

        $estadoGarantia =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'GARANTIA'
                )
                ->firstOrFail();

        $estadoVendido =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'VENDIDO'
                )
                ->firstOrFail();

        $this->assertSame(
            $caso->id,
            $cambio->caso_garantia_id
        );

        $this->assertSame(
            $equipoSaliente->id,
            $cambio->equipo_saliente_id
        );

        $this->assertSame(
            $equipoEntrante->id,
            $cambio->equipo_entrante_id
        );

        $this->assertSame(
            $this->administrador->id,
            $cambio->autorizado_por_id
        );

        $this->assertSame(
            'EN_PROCESO',
            $caso->estado
        );

        $this->assertSame(
            $estadoGarantia->id,
            $equipoSaliente->estado_actual_id
        );

        $this->assertSame(
            $estadoVendido->id,
            $equipoEntrante->estado_actual_id
        );

        /*
         * El reemplazo salió del stock disponible.
         */
        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $equipoEntrante->producto_id
                )
                ->where(
                    'almacen_id',
                    $equipoEntrante->almacen_actual_id
                )
                ->first();

        $this->assertNotNull(
            $existencia
        );

        $this->assertSame(
            0,
            (int) $existencia
                ->cantidad_disponible
        );

        /*
         * Debe existir movimiento de inventario.
         */
        $tipoMovimiento =
            TipoMovimientoInventario::query()
                ->where(
                    'codigo',
                    'CAMBIO_GARANTIA'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'movimientos_inventario',
            [
                'producto_id' =>
                    $equipoEntrante->producto_id,

                'almacen_id' =>
                    $equipoEntrante->almacen_actual_id,

                'tipo_movimiento_id' =>
                    $tipoMovimiento->id,

                'cambio_disponible' =>
                    -1,

                'tipo_referencia' =>
                    'CAMBIO_GARANTIA',

                'referencia_id' =>
                    $cambio->id,
            ]
        );

        /*
         * El cambio no modifica la venta ni anula
         * la vigencia comercial de la garantía.
         */
        $this->assertSame(
            'REGISTRADA',
            $venta->estado
        );

        $this->assertSame(
            'VIGENTE',
            $garantia->estado
        );

        /*
         * El detalle de venta conserva el equipo
         * originalmente vendido.
         */
        $garantiaConDetalle =
            Garantia::query()
                ->with('detalleVenta')
                ->findOrFail(
                    $garantia->id
                );

        $this->assertSame(
            $equipoSaliente->id,
            (int) $garantiaConDetalle
                ->detalleVenta
                ->equipo_id
        );
    }

    public function test_vendedor_no_puede_autorizar_cambio_de_equipo(): void
    {
        [
            $caso,
            $equipoSaliente,
        ] = $this->crearCasoDiagnosticado();

        $equipoEntrante =
            $this->crearEquipoReemplazo(
                $equipoSaliente
            );

        try {
            app(CasoGarantiaService::class)
                ->registrarCambioEquipo(
                    casoId:
                        $caso->id,

                    equipoSalienteId:
                        $equipoSaliente->id,

                    equipoEntranteId:
                        $equipoEntrante->id,

                    usuarioId:
                        $this->vendedor->id,

                    motivo:
                        'Intento sin autorización.'
                );

            $this->fail(
                'El vendedor no debe poder autorizar cambios de equipo.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El usuario no cuenta con permiso para autorizar cambios de equipo.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );
    }

    public function test_no_permite_cambio_sin_diagnostico(): void
    {
        [
            $caso,
            $equipoSaliente,
        ] = $this->crearCasoAbierto();

        $equipoEntrante =
            $this->crearEquipoReemplazo(
                $equipoSaliente
            );

        try {
            app(CasoGarantiaService::class)
                ->registrarCambioEquipo(
                    casoId:
                        $caso->id,

                    equipoSalienteId:
                        $equipoSaliente->id,

                    equipoEntranteId:
                        $equipoEntrante->id,

                    usuarioId:
                        $this->administrador->id,

                    motivo:
                        'Cambio prematuro.'
                );

            $this->fail(
                'No debe permitirse un cambio antes del diagnóstico.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El caso debe contar con un diagnóstico antes de autorizar un cambio de equipo.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );

        $this->assertSame(
            'ABIERTO',
            $caso->fresh()->estado
        );
    }

    public function test_no_permite_cambio_en_caso_cerrado(): void
    {
        [
            $caso,
            $equipoSaliente,
        ] = $this->crearCasoDiagnosticado();

        app(CasoGarantiaService::class)
            ->cerrarCaso(
                casoId:
                    $caso->id,

                usuarioId:
                    $this->tecnico->id,

                resolucion:
                    'Caso cerrado para probar bloqueo de cambio.'
            );

        $equipoEntrante =
            $this->crearEquipoReemplazo(
                $equipoSaliente
            );

        try {
            app(CasoGarantiaService::class)
                ->registrarCambioEquipo(
                    casoId:
                        $caso->id,

                    equipoSalienteId:
                        $equipoSaliente->id,

                    equipoEntranteId:
                        $equipoEntrante->id,

                    usuarioId:
                        $this->administrador->id,

                    motivo:
                        'Intento después del cierre.'
                );

            $this->fail(
                'Un caso cerrado no debe admitir cambios.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'No se puede registrar un cambio de equipo en un caso cerrado.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );

        $this->assertSame(
            'CERRADO',
            $caso->fresh()->estado
        );
    }

    public function test_rechaza_equipo_entrante_no_disponible(): void
    {
        [
            $caso,
            $equipoSaliente,
        ] = $this->crearCasoDiagnosticado();

        $equipoEntrante =
            $this->crearEquipoReemplazo(
                equipoBase:
                    $equipoSaliente,

                registrarStock:
                    false,

                producto:
                    null,

                estadoCodigo:
                    'VENDIDO'
            );

        try {
            app(CasoGarantiaService::class)
                ->registrarCambioEquipo(
                    casoId:
                        $caso->id,

                    equipoSalienteId:
                        $equipoSaliente->id,

                    equipoEntranteId:
                        $equipoEntrante->id,

                    usuarioId:
                        $this->administrador->id,

                    motivo:
                        'Intento con equipo no disponible.'
                );

            $this->fail(
                'No debe aceptarse un equipo entrante que no esté disponible.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El equipo seleccionado como reemplazo no está disponible.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );
    }

    public function test_permite_reemplazo_de_producto_distinto(): void
{
    [
        $caso,
        $equipoSaliente,
        $garantia,
    ] = $this->crearCasoDiagnosticado();

    $productoAlternativo =
        $this->crearProductoAlternativo(
            $equipoSaliente
        );

    $equipoEntrante =
        $this->crearEquipoReemplazo(
            equipoBase:
                $equipoSaliente,

            registrarStock:
                true,

            producto:
                $productoAlternativo
        );

    /*
     * Confirmamos que realmente estamos probando
     * un producto distinto.
     */
    $this->assertNotSame(
        (int) $equipoSaliente->producto_id,
        (int) $equipoEntrante->producto_id
    );

    /*
     * El reemplazo debe tener una unidad
     * disponible antes del cambio.
     */
    $stockAntes =
        DB::table(
            'existencias_productos'
        )
            ->where(
                'producto_id',
                $equipoEntrante->producto_id
            )
            ->where(
                'almacen_id',
                $equipoEntrante->almacen_actual_id
            )
            ->value(
                'cantidad_disponible'
            );

    $this->assertSame(
        1,
        (int) $stockAntes
    );

    /*
     * Conservamos referencias históricas
     * antes de ejecutar el cambio.
     */
    $garantia->load(
        'detalleVenta.venta'
    );

    $detalleVentaId =
        $garantia->detalle_venta_id;

    $ventaId =
        $garantia->detalleVenta->venta_id;

    $equipoHistoricoVenta =
        $garantia->detalleVenta->equipo_id;

    /*
     * Ejecutamos el cambio con un producto
     * completamente diferente.
     */
    $cambio =
        app(CasoGarantiaService::class)
            ->registrarCambioEquipo(
                casoId:
                    $caso->id,

                equipoSalienteId:
                    $equipoSaliente->id,

                equipoEntranteId:
                    $equipoEntrante->id,

                usuarioId:
                    $this->administrador->id,

                motivo:
                    'Cliente acepta reemplazo por otro producto.',

                observacion:
                    'Cambio autorizado por garantía con producto alternativo.'
            );

    /*
     * Debe registrarse la trazabilidad
     * del cambio.
     */
    $this->assertDatabaseHas(
        'cambios_equipos',
        [
            'id' =>
                $cambio->id,

            'caso_garantia_id' =>
                $caso->id,

            'equipo_saliente_id' =>
                $equipoSaliente->id,

            'equipo_entrante_id' =>
                $equipoEntrante->id,

            'autorizado_por_id' =>
                $this->administrador->id,
        ]
    );

    /*
     * El caso pasa a EN_PROCESO,
     * pero no se cierra automáticamente.
     */
    $this->assertSame(
        'EN_PROCESO',
        $caso->fresh()->estado
    );

    /*
     * Equipo original:
     * VENDIDO -> GARANTIA.
     */
    $estadoGarantia =
        EstadoEquipo::query()
            ->where(
                'codigo',
                'GARANTIA'
            )
            ->firstOrFail();

    $this->assertSame(
        $estadoGarantia->id,
        $equipoSaliente
            ->fresh()
            ->estado_actual_id
    );

    /*
     * Equipo alternativo:
     * DISPONIBLE -> VENDIDO.
     */
    $estadoVendido =
        EstadoEquipo::query()
            ->where(
                'codigo',
                'VENDIDO'
            )
            ->firstOrFail();

    $this->assertSame(
        $estadoVendido->id,
        $equipoEntrante
            ->fresh()
            ->estado_actual_id
    );

    /*
     * El stock descontado debe corresponder
     * al PRODUCTO DEL REEMPLAZO,
     * no al producto original.
     */
    $stockDespues =
        DB::table(
            'existencias_productos'
        )
            ->where(
                'producto_id',
                $equipoEntrante->producto_id
            )
            ->where(
                'almacen_id',
                $equipoEntrante->almacen_actual_id
            )
            ->value(
                'cantidad_disponible'
            );

    $this->assertSame(
        0,
        (int) $stockDespues
    );

    /*
     * Movimiento de inventario asociado
     * al producto alternativo.
     */
    $this->assertDatabaseHas(
        'movimientos_inventario',
        [
            'producto_id' =>
                $equipoEntrante->producto_id,

            'almacen_id' =>
                $equipoEntrante->almacen_actual_id,

            'cambio_disponible' =>
                -1,

            'tipo_referencia' =>
                'CAMBIO_GARANTIA',

            'referencia_id' =>
                $cambio->id,
        ]
    );

    /*
     * La garantía comercial continúa vigente.
     */
    $this->assertSame(
        'VIGENTE',
        $garantia
            ->fresh()
            ->estado
    );

    /*
     * La venta histórica continúa registrada.
     */
    $this->assertDatabaseHas(
        'ventas',
        [
            'id' =>
                $ventaId,

            'estado' =>
                'REGISTRADA',
        ]
    );

    /*
     * CRÍTICO:
     * el detalle original de venta NO debe
     * reemplazarse por el nuevo equipo.
     */
    $this->assertDatabaseHas(
    'detalles_ventas',
    [
        'id' =>
            $detalleVentaId,

        'equipo_id' =>
            $equipoHistoricoVenta,
    ]
);

    $this->assertSame(
        $equipoSaliente->id,
        (int) $equipoHistoricoVenta
    );

    /*
     * El caso también conserva como afectado
     * al equipo que originalmente fue vendido.
     */
    $this->assertSame(
        $equipoSaliente->id,
        (int) $caso
            ->fresh()
            ->equipo_afectado_id
    );
}

    public function test_rechaza_equipo_saliente_distinto_al_equipo_del_caso(): void
    {
        [
            $caso,
            $equipoSaliente,
        ] = $this->crearCasoDiagnosticado();

        $otroEquipo =
            $this->crearEquipoReemplazo(
                $equipoSaliente
            );

        try {
            app(CasoGarantiaService::class)
                ->registrarCambioEquipo(
                    casoId:
                        $caso->id,

                    equipoSalienteId:
                        $otroEquipo->id,

                    equipoEntranteId:
                        $equipoSaliente->id,

                    usuarioId:
                        $this->administrador->id,

                    motivo:
                        'Intento usando otro equipo como saliente.'
                );

            $this->fail(
                'El equipo saliente debe coincidir con el equipo afectado.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El equipo saliente debe ser el equipo afectado originalmente por el caso.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );
    }

    public function test_no_permite_segundo_cambio_en_el_mismo_caso(): void
    {
        [
            $caso,
            $equipoSaliente,
        ] = $this->crearCasoDiagnosticado();

        $primerEntrante =
            $this->crearEquipoReemplazo(
                $equipoSaliente
            );

        $servicio =
            app(CasoGarantiaService::class);

        $servicio->registrarCambioEquipo(
            casoId:
                $caso->id,

            equipoSalienteId:
                $equipoSaliente->id,

            equipoEntranteId:
                $primerEntrante->id,

            usuarioId:
                $this->administrador->id,

            motivo:
                'Primer cambio autorizado.'
        );

        $segundoEntrante =
            $this->crearEquipoReemplazo(
                $equipoSaliente
            );

        try {
            $servicio->registrarCambioEquipo(
                casoId:
                    $caso->id,

                equipoSalienteId:
                    $equipoSaliente->id,

                equipoEntranteId:
                    $segundoEntrante->id,

                usuarioId:
                    $this->administrador->id,

                motivo:
                    'Segundo cambio que debe rechazarse.'
            );

            $this->fail(
                'No debe existir más de un cambio por caso.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'Este caso ya tiene un cambio de equipo registrado.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'cambios_equipos',
            1
        );
    }

    public function test_fallo_de_inventario_revierte_todo_el_cambio(): void
    {
        [
            $caso,
            $equipoSaliente,
        ] = $this->crearCasoDiagnosticado();

        /*
         * Creamos físicamente un equipo DISPONIBLE,
         * pero NO aumentamos su existencia.
         *
         * Así registrarSalida() fallará después
         * de que CambioEquipo haya sido creado,
         * permitiendo comprobar el rollback real.
         */
        $equipoEntrante =
            $this->crearEquipoReemplazo(
                equipoBase:
                    $equipoSaliente,

                registrarStock:
                    false
            );

        $estadoVendido =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'VENDIDO'
                )
                ->firstOrFail();

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        try {
            app(CasoGarantiaService::class)
                ->registrarCambioEquipo(
                    casoId:
                        $caso->id,

                    equipoSalienteId:
                        $equipoSaliente->id,

                    equipoEntranteId:
                        $equipoEntrante->id,

                    usuarioId:
                        $this->administrador->id,

                    motivo:
                        'Prueba de rollback por falta de stock.'
                );

            $this->fail(
                'El cambio debía fallar por falta de inventario disponible.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'No existe suficiente inventario disponible.',
                $exception->getMessage()
            );
        }

        $caso->refresh();
        $equipoSaliente->refresh();
        $equipoEntrante->refresh();

        /*
         * El CambioEquipo creado antes del fallo
         * debe haber desaparecido por rollback.
         */
        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );

        /*
         * Tampoco debe persistir movimiento.
         */
        $this->assertDatabaseMissing(
            'movimientos_inventario',
            [
                'tipo_referencia' =>
                    'CAMBIO_GARANTIA',
            ]
        );

        /*
         * Ningún estado debe haberse modificado.
         */
        $this->assertSame(
            $estadoVendido->id,
            $equipoSaliente->estado_actual_id
        );

        $this->assertSame(
            $estadoDisponible->id,
            $equipoEntrante->estado_actual_id
        );

        /*
         * El caso continúa diagnosticado.
         */
        $this->assertSame(
            'DIAGNOSTICADO',
            $caso->estado
        );

        /*
         * Stock permanece en cero.
         */
        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $equipoEntrante->producto_id
                )
                ->where(
                    'almacen_id',
                    $equipoEntrante->almacen_actual_id
                )
                ->first();

        $this->assertNotNull(
            $existencia
        );

        $this->assertSame(
            0,
            (int) $existencia
                ->cantidad_disponible
        );
    }

    private function crearCasoDiagnosticado(): array
    {
        [
            $caso,
            $equipo,
            $garantia,
        ] = $this->crearCasoAbierto();

        $caso =
            app(CasoGarantiaService::class)
                ->registrarDiagnostico(
                    casoId:
                        $caso->id,

                    usuarioId:
                        $this->tecnico->id,

                    diagnostico:
                        'Falla confirmada que justifica evaluar reemplazo.'
                );

        return [
            $caso,
            $equipo,
            $garantia,
        ];
    }

    private function crearCasoAbierto(): array
    {
        [
            $equipo,
            $garantia,
        ] = $this->crearVentaConGarantia();

        $caso =
            app(CasoGarantiaService::class)
                ->abrirCaso(
                    garantiaId:
                        $garantia->id,

                    usuarioId:
                        $this->administrador->id,

                    motivoCliente:
                        'Equipo presenta falla que podría requerir cambio.',

                    observacion:
                        'Caso automatizado para fase 10C.'
                );

        return [
            $caso,
            $equipo,
            $garantia,
        ];
    }

    private function crearVentaConGarantia(): array
    {
        $equipo =
            $this->crearEquipoDisponible();

        $venta =
            app(VentaService::class)
                ->registrarVentaDirecta(
                    vendedorId:
                        $this->administrador->id,

                    equiposIds: [
                        $equipo->id,
                    ],

                    clienteId:
                        $this->cliente->id
                );

        $garantia =
            Garantia::query()
                ->whereHas(
                    'detalleVenta',
                    function ($query) use (
                        $venta,
                        $equipo
                    ) {
                        $query
                            ->where(
                                'venta_id',
                                $venta->id
                            )
                            ->where(
                                'equipo_id',
                                $equipo->id
                            );
                    }
                )
                ->firstOrFail();

        return [
            $equipo->fresh(),
            $garantia->fresh(),
        ];
    }

    private function crearEquipoDisponible(): Equipo
    {
        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $almacen =
            Almacen::query()
                ->where(
                    'activo',
                    true
                )
                ->orderByDesc(
                    'principal'
                )
                ->firstOrFail();

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'marca_id' =>
                null,

            'codigo' =>
                'P10C-'
                . Str::ulid(),

            'nombre' =>
                'Laptop cambio garantía',

            'modelo' =>
                'TEST-10C',

            'descripcion' =>
                null,

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);

        PoliticaGarantia::create([
            'codigo' =>
                'G10C-'
                . Str::ulid(),

            'nombre' =>
                'Garantía cambio 10C',

            'categoria_producto_id' =>
                $categoria->id,

            'producto_id' =>
                $producto->id,

            'duracion_meses' =>
                6,

            'condiciones' =>
                'Garantía estándar para pruebas de cambio.',

            'exclusiones' =>
                'Daños físicos.',

            'vigente_desde' =>
                now()->subDay(),

            'vigente_hasta' =>
                null,

            'activo' =>
                true,
        ]);

        $equipo = Equipo::create([
            'producto_id' =>
                $producto->id,

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estadoDisponible->id,

            'condicion_fisica_id' =>
                null,

            'codigo_interno' =>
                'E10O-'
                . Str::ulid(),

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

        PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,

            'tipo_cambio_id' =>
                null,

            'costo_total_snapshot' =>
                2900,

            'precio_sugerido' =>
                3500,

            'precio_publico' =>
                3500,

            'precio_minimo_autorizado' =>
                3200,

            'vigente_desde' =>
                now(),

            'vigente_hasta' =>
                null,

            'vigente' =>
                true,

            'aprobado_por_id' =>
                null,

            'observacion' =>
                'Precio para prueba 10C.',
        ]);

        DB::table(
            'existencias_productos'
        )->insert([
            'producto_id' =>
                $producto->id,

            'almacen_id' =>
                $almacen->id,

            'cantidad_disponible' =>
                1,

            'cantidad_reservada' =>
                0,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        return $equipo;
    }

    private function crearEquipoReemplazo(
        Equipo $equipoBase,
        bool $registrarStock = true,
        ?Producto $producto = null,
        string $estadoCodigo = 'DISPONIBLE'
    ): Equipo {
        $producto =
            $producto
            ?? Producto::query()
                ->findOrFail(
                    $equipoBase->producto_id
                );

        $estado =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    $estadoCodigo
                )
                ->firstOrFail();

        $equipo = Equipo::create([
            'producto_id' =>
                $producto->id,

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $equipoBase->almacen_actual_id,

            'estado_actual_id' =>
                $estado->id,

            'condicion_fisica_id' =>
                null,

            'codigo_interno' =>
                'E10R-'
                . Str::ulid(),

            'serial_fabricante' =>
                null,

            'fecha_registro' =>
                now(),

            'fecha_disponible' =>
                $estadoCodigo === 'DISPONIBLE'
                    ? now()
                    : null,

            'observacion' =>
                'Equipo de reemplazo para prueba 10C.',

            'activo' =>
                true,
        ]);

        if ($registrarStock) {
            $existencia =
                DB::table(
                    'existencias_productos'
                )
                    ->where(
                        'producto_id',
                        $producto->id
                    )
                    ->where(
                        'almacen_id',
                        $equipoBase->almacen_actual_id
                    )
                    ->first();

            if ($existencia) {
                DB::table(
                    'existencias_productos'
                )
                    ->where(
                        'producto_id',
                        $producto->id
                    )
                    ->where(
                        'almacen_id',
                        $equipoBase->almacen_actual_id
                    )
                    ->update([
                        'cantidad_disponible' =>
                            (int) $existencia
                                ->cantidad_disponible
                            + 1,

                        'updated_at' =>
                            now(),
                    ]);
            } else {
                DB::table(
                    'existencias_productos'
                )->insert([
                    'producto_id' =>
                        $producto->id,

                    'almacen_id' =>
                        $equipoBase->almacen_actual_id,

                    'cantidad_disponible' =>
                        1,

                    'cantidad_reservada' =>
                        0,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);
            }
        }

        return $equipo;
    }

    private function crearProductoAlternativo(
        Equipo $equipoBase
    ): Producto {
        $productoBase =
            Producto::query()
                ->findOrFail(
                    $equipoBase->producto_id
                );

        return Producto::create([
            'categoria_producto_id' =>
                $productoBase
                    ->categoria_producto_id,

            'marca_id' =>
                null,

            'codigo' =>
                'P10A-'
                . Str::ulid(),

            'nombre' =>
                'Producto alternativo 10C',

            'modelo' =>
                'OTRO-MODELO',

            'descripcion' =>
                null,

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);
    }

    private function usuarioConRol(
        string $codigoRol
    ): User {
        $usuario =
            User::factory()->create([
                'activo' =>
                    true,
            ]);

        $rol =
            Rol::query()
                ->where(
                    'codigo',
                    $codigoRol
                )
                ->firstOrFail();

        $usuario
            ->roles()
            ->attach(
                $rol->id
            );

        return $usuario;
    }
}