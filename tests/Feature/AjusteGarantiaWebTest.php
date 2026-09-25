<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Garantia;
use App\Models\MetodoPago;
use App\Models\MovimientoAjusteGarantia;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Services\AjusteGarantiaService;
use App\Services\CasoGarantiaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AjusteGarantiaWebTest extends TestCase
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

        $this->cliente =
            Cliente::create([
                'nombre_completo' =>
                    'Cliente ajuste garantía web 10D-B',

                'telefono' =>
                    '70001005',

                'activo' =>
                    true,
            ]);
    }

    public function test_administrador_puede_registrar_cobro_en_efectivo_por_web_y_liquidar(): void
    {
        [
            $cambio,
            $equipoOriginal,
        ] = $this->crearCambioConDiferencia(
            500.00
        );

        $efectivo =
            $this->metodoPago(
                'EFECTIVO'
            );

        $origen =
            '/inventario/'
            . $equipoOriginal->codigo_interno;

        $this
            ->actingAs(
                $this->administrador
            )
            ->from($origen)
            ->post(
                route(
                    'garantias.ajustes.store',
                    $cambio
                ),
                [
                    'metodo_pago_id' =>
                        $efectivo->id,

                    'monto' =>
                        '500.00',

                    'observacion' =>
                        'Cobro total desde flujo web 10D-B.',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHas(
                'success',
                'Cobro registrado correctamente.'
            );

        $this->assertDatabaseHas(
            'movimientos_ajustes_garantia',
            [
                'cambio_equipo_id' =>
                    $cambio->id,

                'tipo_movimiento' =>
                    'COBRO',

                'metodo_pago_id' =>
                    $efectivo->id,

                'monto' =>
                    '500.00',

                'estado' =>
                    'VERIFICADO',

                'registrado_por_id' =>
                    $this->administrador->id,

                'verificado_por_id' =>
                    $this->administrador->id,
            ]
        );

        $this->assertSame(
            'LIQUIDADO',
            $cambio
                ->fresh()
                ->estado_ajuste
        );
    }

    public function test_qr_se_registra_pendiente_y_puede_verificarse_por_web(): void
    {
        [
            $cambio,
            $equipoOriginal,
        ] = $this->crearCambioConDiferencia(
            500.00
        );

        $qr =
            $this->metodoPago(
                'QR'
            );

        $origen =
            '/inventario/'
            . $equipoOriginal->codigo_interno;

        $this
            ->actingAs(
                $this->administrador
            )
            ->from($origen)
            ->post(
                route(
                    'garantias.ajustes.store',
                    $cambio
                ),
                [
                    'metodo_pago_id' =>
                        $qr->id,

                    'monto' =>
                        '500.00',

                    'referencia' =>
                        'QR-WEB-10DB-001',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHas(
                'success',
                'Cobro registrado correctamente.'
            );

        $movimiento =
            MovimientoAjusteGarantia::query()
                ->where(
                    'cambio_equipo_id',
                    $cambio->id
                )
                ->firstOrFail();

        $this->assertSame(
            'PENDIENTE',
            $movimiento->estado
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio
                ->fresh()
                ->estado_ajuste
        );

        $this
            ->actingAs(
                $this->administrador
            )
            ->from($origen)
            ->post(
                route(
                    'garantias.ajustes.verificar',
                    [
                        'cambio' =>
                            $cambio,

                        'movimiento' =>
                            $movimiento,
                    ]
                )
            )
            ->assertRedirect($origen)
            ->assertSessionHas(
                'success',
                'Movimiento económico verificado correctamente.'
            );

        $this->assertSame(
            'VERIFICADO',
            $movimiento
                ->fresh()
                ->estado
        );

        $this->assertSame(
            $this->administrador->id,
            $movimiento
                ->fresh()
                ->verificado_por_id
        );

        $this->assertSame(
            'LIQUIDADO',
            $cambio
                ->fresh()
                ->estado_ajuste
        );
    }

    public function test_movimiento_pendiente_puede_rechazarse_por_web_y_libera_saldo(): void
    {
        [
            $cambio,
            $equipoOriginal,
        ] = $this->crearCambioConDiferencia(
            500.00
        );

        $qr =
            $this->metodoPago(
                'QR'
            );

        $movimiento =
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambio->id,

                    metodoPagoId:
                        $qr->id,

                    monto:
                        '300.00',

                    registradoPorId:
                        $this->administrador->id,

                    referencia:
                        'QR-WEB-RECHAZO'
                );

        $origen =
            '/inventario/'
            . $equipoOriginal->codigo_interno;

        $this
            ->actingAs(
                $this->administrador
            )
            ->from($origen)
            ->post(
                route(
                    'garantias.ajustes.rechazar',
                    [
                        'cambio' =>
                            $cambio,

                        'movimiento' =>
                            $movimiento,
                    ]
                ),
                [
                    'motivo_rechazo' =>
                        'Comprobante no localizado en la cuenta.',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHas(
                'success',
                'Movimiento económico rechazado correctamente. El importe volvió a quedar disponible.'
            );

        $movimiento->refresh();

        $this->assertSame(
            'RECHAZADO',
            $movimiento->estado
        );

        $this->assertSame(
            'Comprobante no localizado en la cuenta.',
            $movimiento->motivo_rechazo
        );

        $this->assertSame(
            $this->administrador->id,
            $movimiento->verificado_por_id
        );

        $resumen =
            app(AjusteGarantiaService::class)
                ->obtenerResumen(
                    $cambio->id
                );

        $this->assertSame(
            '500.00',
            $resumen['saldo_disponible']
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio
                ->fresh()
                ->estado_ajuste
        );
    }

    public function test_vendedor_no_puede_registrar_ajuste_por_web(): void
    {
        [
            $cambio,
        ] = $this->crearCambioConDiferencia(
            500.00
        );

        $efectivo =
            $this->metodoPago(
                'EFECTIVO'
            );

        $this
            ->actingAs(
                $this->vendedor
            )
            ->post(
                route(
                    'garantias.ajustes.store',
                    $cambio
                ),
                [
                    'metodo_pago_id' =>
                        $efectivo->id,

                    'monto' =>
                        '100.00',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'movimientos_ajustes_garantia',
            0
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio
                ->fresh()
                ->estado_ajuste
        );
    }

    public function test_vendedor_no_puede_verificar_ajuste_por_web(): void
    {
        [
            $cambio,
        ] = $this->crearCambioConDiferencia(
            500.00
        );

        $qr =
            $this->metodoPago(
                'QR'
            );

        $movimiento =
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambio->id,

                    metodoPagoId:
                        $qr->id,

                    monto:
                        '500.00',

                    registradoPorId:
                        $this->administrador->id,

                    referencia:
                        'QR-WEB-PERMISO'
                );

        $this
            ->actingAs(
                $this->vendedor
            )
            ->post(
                route(
                    'garantias.ajustes.verificar',
                    [
                        'cambio' =>
                            $cambio,

                        'movimiento' =>
                            $movimiento,
                    ]
                )
            )
            ->assertForbidden();

        $this->assertSame(
            'PENDIENTE',
            $movimiento
                ->fresh()
                ->estado
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio
                ->fresh()
                ->estado_ajuste
        );
    }

    public function test_web_valida_datos_de_registro_del_ajuste(): void
    {
        [
            $cambio,
            $equipoOriginal,
        ] = $this->crearCambioConDiferencia(
            500.00
        );

        $origen =
            '/inventario/'
            . $equipoOriginal->codigo_interno;

        $this
            ->actingAs(
                $this->administrador
            )
            ->from($origen)
            ->post(
                route(
                    'garantias.ajustes.store',
                    $cambio
                ),
                [
                    'metodo_pago_id' =>
                        999999999,

                    'monto' =>
                        '0',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHasErrors([
                'metodo_pago_id',
                'monto',
            ]);

        $this->assertDatabaseCount(
            'movimientos_ajustes_garantia',
            0
        );
    }

    public function test_movimiento_de_otro_cambio_devuelve_404(): void
    {
        [
            $cambioA,
        ] = $this->crearCambioConDiferencia(
            500.00
        );

        [
            $cambioB,
        ] = $this->crearCambioConDiferencia(
            400.00
        );

        $qr =
            $this->metodoPago(
                'QR'
            );

        $movimientoB =
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambioB->id,

                    metodoPagoId:
                        $qr->id,

                    monto:
                        '100.00',

                    registradoPorId:
                        $this->administrador->id,

                    referencia:
                        'QR-OTRO-CAMBIO'
                );

        $this
            ->actingAs(
                $this->administrador
            )
            ->post(
                route(
                    'garantias.ajustes.verificar',
                    [
                        'cambio' =>
                            $cambioA,

                        'movimiento' =>
                            $movimientoB,
                    ]
                )
            )
            ->assertNotFound();

        $this->assertSame(
            'PENDIENTE',
            $movimientoB
                ->fresh()
                ->estado
        );

        $this->assertSame(
            'PENDIENTE',
            $cambioB
                ->fresh()
                ->estado_ajuste
        );
    }

    private function crearCambioConDiferencia(
        float $diferencia
    ): array {
        [
            $caso,
            $equipoSaliente,
            $garantia,
        ] = $this->crearCasoDiagnosticado();

        $garantia->load(
            'detalleVenta'
        );

        $valorOriginal =
            (float) $garantia
                ->detalleVenta
                ->precio_unitario;

        $equipoEntrante =
            $this->crearEquipoReemplazo(
                equipoBase:
                    $equipoSaliente,

                precioPublico:
                    $valorOriginal
                    + $diferencia
            );

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
                        'Cambio para prueba web de ajuste económico 10D-B.',

                    observacion:
                        'Fixture web 10D-B.'
                );

        return [
            $cambio,
            $equipoSaliente,
            $garantia,
            $equipoEntrante,
        ];
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
                        'Falla confirmada para ajuste económico web 10D-B.'
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
                        'Equipo con falla para prueba web 10D-B.',

                    observacion:
                        'Caso automatizado para ajuste económico.'
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

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    null,

                'codigo' =>
                    'P10BW-'
                    . Str::ulid(),

                'nombre' =>
                    'Laptop ajuste garantía web 10D-B',

                'modelo' =>
                    'WEB-10D-B',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'G10BW-'
                . Str::ulid(),

            'nombre' =>
                'Garantía ajuste web 10D-B',

            'categoria_producto_id' =>
                $categoria->id,

            'producto_id' =>
                $producto->id,

            'duracion_meses' =>
                6,

            'condiciones' =>
                'Garantía estándar para prueba web.',

            'exclusiones' =>
                'Daños físicos.',

            'vigente_desde' =>
                now()->subDay(),

            'vigente_hasta' =>
                null,

            'activo' =>
                true,
        ]);

        $equipo =
            Equipo::create([
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
                    'E10BW-'
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
                'Precio para prueba web 10D-B.',
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
        float $precioPublico
    ): Equipo {
        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $equipo =
            Equipo::create([
                'producto_id' =>
                    $equipoBase->producto_id,

                'detalle_lote_id' =>
                    null,

                'almacen_actual_id' =>
                    $equipoBase->almacen_actual_id,

                'estado_actual_id' =>
                    $estadoDisponible->id,

                'condicion_fisica_id' =>
                    null,

                'codigo_interno' =>
                    'R10BW-'
                    . Str::ulid(),

                'serial_fabricante' =>
                    null,

                'fecha_registro' =>
                    now(),

                'fecha_disponible' =>
                    now(),

                'observacion' =>
                    'Reemplazo para prueba web 10D-B.',

                'activo' =>
                    true,
            ]);

        PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,

            'tipo_cambio_id' =>
                null,

            'costo_total_snapshot' =>
                max(
                    0,
                    $precioPublico - 600
                ),

            'precio_sugerido' =>
                $precioPublico,

            'precio_publico' =>
                $precioPublico,

            'precio_minimo_autorizado' =>
                max(
                    0,
                    $precioPublico - 300
                ),

            'vigente_desde' =>
                now(),

            'vigente_hasta' =>
                null,

            'vigente' =>
                true,

            'aprobado_por_id' =>
                null,

            'observacion' =>
                'Precio vigente para prueba web 10D-B.',
        ]);

        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $equipo->producto_id
                )
                ->where(
                    'almacen_id',
                    $equipo->almacen_actual_id
                )
                ->first();

        if ($existencia) {
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $equipo->producto_id
                )
                ->where(
                    'almacen_id',
                    $equipo->almacen_actual_id
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
                    $equipo->producto_id,

                'almacen_id' =>
                    $equipo->almacen_actual_id,

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

        return $equipo;
    }

    private function metodoPago(
        string $codigo
    ): MetodoPago {
        return MetodoPago::query()
            ->where(
                'codigo',
                $codigo
            )
            ->where(
                'activo',
                true
            )
            ->firstOrFail();
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
