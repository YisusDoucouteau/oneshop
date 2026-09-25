<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Garantia;
use App\Models\MetodoPago;
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

class AjusteGarantiaServiceTest extends TestCase
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
            $this->usuarioConRol('ADMINISTRADOR');

        $this->tecnico =
            $this->usuarioConRol('TECNICO');

        $this->vendedor =
            $this->usuarioConRol('VENDEDOR');

        $this->cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente ajuste garantía 10D-B',

            'telefono' =>
                '70001004',

            'activo' =>
                true,
        ]);
    }

    public function test_efectivo_registra_cobro_verificado_y_liquida_ajuste_completo(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $efectivo =
            $this->metodoPago('EFECTIVO');

        $movimiento =
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambio->id,

                    metodoPagoId:
                        $efectivo->id,

                    monto:
                        '500.00',

                    registradoPorId:
                        $this->administrador->id,

                    observacion:
                        'Cobro total en efectivo.'
                );

        $this->assertSame(
            'COBRO',
            $movimiento->tipo_movimiento
        );

        $this->assertSame(
            '500.00',
            $movimiento->monto
        );

        $this->assertSame(
            'VERIFICADO',
            $movimiento->estado
        );

        $this->assertSame(
            $this->administrador->id,
            $movimiento->verificado_por_id
        );

        $this->assertNotNull(
            $movimiento->fecha_verificacion
        );

        $this->assertSame(
            'LIQUIDADO',
            $cambio->fresh()->estado_ajuste
        );
    }

    public function test_cobro_parcial_verificado_mantiene_ajuste_pendiente(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $efectivo =
            $this->metodoPago('EFECTIVO');

        app(AjusteGarantiaService::class)
            ->registrarMovimiento(
                cambioEquipoId:
                    $cambio->id,

                metodoPagoId:
                    $efectivo->id,

                monto:
                    '200.00',

                registradoPorId:
                    $this->administrador->id
            );

        $resumen =
            app(AjusteGarantiaService::class)
                ->obtenerResumen(
                    $cambio->id
                );

        $this->assertSame(
            'PENDIENTE',
            $cambio->fresh()->estado_ajuste
        );

        $this->assertSame(
            '500.00',
            $resumen['objetivo']
        );

        $this->assertSame(
            '200.00',
            $resumen['verificado']
        );

        $this->assertSame(
            '300.00',
            $resumen['saldo']
        );

        $this->assertSame(
            '300.00',
            $resumen['saldo_disponible']
        );
    }

    public function test_qr_se_registra_pendiente_y_reserva_el_saldo(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $qr =
            $this->metodoPago('QR');

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
                        'QR-GAR-10DB-001'
                );

        $resumen =
            app(AjusteGarantiaService::class)
                ->obtenerResumen(
                    $cambio->id
                );

        $this->assertSame(
            'PENDIENTE',
            $movimiento->estado
        );

        $this->assertNull(
            $movimiento->verificado_por_id
        );

        $this->assertNull(
            $movimiento->fecha_verificacion
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio->fresh()->estado_ajuste
        );

        $this->assertSame(
            '0.00',
            $resumen['verificado']
        );

        $this->assertSame(
            '500.00',
            $resumen['pendiente_verificacion']
        );

        $this->assertSame(
            '500.00',
            $resumen['comprometido']
        );

        $this->assertSame(
            '500.00',
            $resumen['saldo']
        );

        $this->assertSame(
            '0.00',
            $resumen['saldo_disponible']
        );
    }

    public function test_verificar_qr_liquida_ajuste_cuando_completa_el_total(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $qr =
            $this->metodoPago('QR');

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
                        'QR-GAR-10DB-002'
                );

        $movimiento =
            app(AjusteGarantiaService::class)
                ->verificarMovimiento(
                    movimientoId:
                        $movimiento->id,

                    verificadoPorId:
                        $this->administrador->id
                );

        $this->assertSame(
            'VERIFICADO',
            $movimiento->estado
        );

        $this->assertSame(
            $this->administrador->id,
            $movimiento->verificado_por_id
        );

        $this->assertNotNull(
            $movimiento->fecha_verificacion
        );

        $this->assertSame(
            'LIQUIDADO',
            $cambio->fresh()->estado_ajuste
        );
    }

    public function test_rechazar_movimiento_pendiente_libera_nuevamente_el_saldo(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $qr =
            $this->metodoPago('QR');

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
                        'QR-GAR-RECHAZO'
                );

        $movimiento =
            app(AjusteGarantiaService::class)
                ->rechazarMovimiento(
                    movimientoId:
                        $movimiento->id,

                    verificadoPorId:
                        $this->administrador->id,

                    motivo:
                        'El comprobante no corresponde al cliente.'
                );

        $resumen =
            app(AjusteGarantiaService::class)
                ->obtenerResumen(
                    $cambio->id
                );

        $this->assertSame(
            'RECHAZADO',
            $movimiento->estado
        );

        $this->assertSame(
            'El comprobante no corresponde al cliente.',
            $movimiento->motivo_rechazo
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio->fresh()->estado_ajuste
        );

        $this->assertSame(
            '300.00',
            $resumen['rechazado']
        );

        $this->assertSame(
            '0.00',
            $resumen['comprometido']
        );

        $this->assertSame(
            '500.00',
            $resumen['saldo_disponible']
        );
    }

    public function test_no_permite_superar_saldo_considerando_movimientos_pendientes(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $qr =
            $this->metodoPago('QR');

        $servicio =
            app(AjusteGarantiaService::class);

        $servicio->registrarMovimiento(
            cambioEquipoId:
                $cambio->id,

            metodoPagoId:
                $qr->id,

            monto:
                '300.00',

            registradoPorId:
                $this->administrador->id,

            referencia:
                'QR-GAR-PARCIAL-300'
        );

        try {
            $servicio->registrarMovimiento(
                cambioEquipoId:
                    $cambio->id,

                metodoPagoId:
                    $qr->id,

                monto:
                    '250.00',

                registradoPorId:
                    $this->administrador->id,

                referencia:
                    'QR-GAR-EXCESO-250'
            );

            $this->fail(
                'No debe permitirse comprometer más que el saldo disponible.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El movimiento supera el saldo disponible de Bs 200.00.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'movimientos_ajustes_garantia',
            1
        );
    }

    public function test_saldo_a_favor_genera_devolucion_y_puede_liquidarse(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                -500.00
            );

        $efectivo =
            $this->metodoPago('EFECTIVO');

        $movimiento =
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambio->id,

                    metodoPagoId:
                        $efectivo->id,

                    monto:
                        '500.00',

                    registradoPorId:
                        $this->administrador->id,

                    observacion:
                        'Devolución completa al cliente.'
                );

        $this->assertSame(
            'SALDO_FAVOR_CLIENTE',
            $cambio->tipo_ajuste
        );

        $this->assertSame(
            'DEVOLUCION',
            $movimiento->tipo_movimiento
        );

        $this->assertSame(
            'VERIFICADO',
            $movimiento->estado
        );

        $this->assertSame(
            'LIQUIDADO',
            $cambio->fresh()->estado_ajuste
        );

        $resumen =
            app(AjusteGarantiaService::class)
                ->obtenerResumen(
                    $cambio->id
                );

        $this->assertSame(
            '500.00',
            $resumen['objetivo']
        );

        $this->assertSame(
            '500.00',
            $resumen['verificado']
        );

        $this->assertSame(
            '0.00',
            $resumen['saldo']
        );
    }

    public function test_sin_diferencia_no_admite_movimientos(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                0.00
            );

        $efectivo =
            $this->metodoPago('EFECTIVO');

        $this->assertSame(
            'SIN_DIFERENCIA',
            $cambio->tipo_ajuste
        );

        $this->assertSame(
            'LIQUIDADO',
            $cambio->estado_ajuste
        );

        try {
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambio->id,

                    metodoPagoId:
                        $efectivo->id,

                    monto:
                        '1.00',

                    registradoPorId:
                        $this->administrador->id
                );

            $this->fail(
                'Un cambio sin diferencia económica no debe admitir movimientos.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El cambio de equipo no posee diferencia económica pendiente de liquidación.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'movimientos_ajustes_garantia',
            0
        );
    }

    public function test_vendedor_no_puede_registrar_ajuste_economico(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $efectivo =
            $this->metodoPago('EFECTIVO');

        try {
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambio->id,

                    metodoPagoId:
                        $efectivo->id,

                    monto:
                        '100.00',

                    registradoPorId:
                        $this->vendedor->id
                );

            $this->fail(
                'El vendedor no debe registrar ajustes económicos de garantía.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El usuario no cuenta con permiso para gestionar el ajuste económico de garantía.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'movimientos_ajustes_garantia',
            0
        );
    }

    public function test_vendedor_no_puede_verificar_movimiento_pendiente(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $qr =
            $this->metodoPago('QR');

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
                        'QR-GAR-SIN-PERMISO'
                );

        try {
            app(AjusteGarantiaService::class)
                ->verificarMovimiento(
                    movimientoId:
                        $movimiento->id,

                    verificadoPorId:
                        $this->vendedor->id
                );

            $this->fail(
                'El vendedor no debe verificar ajustes económicos de garantía.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El usuario no cuenta con permiso para gestionar el ajuste económico de garantía.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            'PENDIENTE',
            $movimiento->fresh()->estado
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio->fresh()->estado_ajuste
        );
    }

    public function test_metodo_con_verificacion_exige_referencia_o_comprobante(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $qr =
            $this->metodoPago('QR');

        try {
            app(AjusteGarantiaService::class)
                ->registrarMovimiento(
                    cambioEquipoId:
                        $cambio->id,

                    metodoPagoId:
                        $qr->id,

                    monto:
                        '100.00',

                    registradoPorId:
                        $this->administrador->id
                );

            $this->fail(
                'Un método con verificación debe exigir referencia o comprobante.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertSame(
                'El movimiento requiere una referencia o comprobante para su posterior verificación.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'movimientos_ajustes_garantia',
            0
        );
    }

    public function test_dos_cobros_parciales_verificados_liquidan_exactamente_el_ajuste(): void
    {
        $cambio =
            $this->crearCambioConDiferencia(
                500.00
            );

        $efectivo =
            $this->metodoPago('EFECTIVO');

        $servicio =
            app(AjusteGarantiaService::class);

        $servicio->registrarMovimiento(
            cambioEquipoId:
                $cambio->id,

            metodoPagoId:
                $efectivo->id,

            monto:
                '300.00',

            registradoPorId:
                $this->administrador->id
        );

        $this->assertSame(
            'PENDIENTE',
            $cambio->fresh()->estado_ajuste
        );

        $servicio->registrarMovimiento(
            cambioEquipoId:
                $cambio->id,

            metodoPagoId:
                $efectivo->id,

            monto:
                '200.00',

            registradoPorId:
                $this->administrador->id
        );

        $resumen =
            $servicio->obtenerResumen(
                $cambio->id
            );

        $this->assertSame(
            'LIQUIDADO',
            $cambio->fresh()->estado_ajuste
        );

        $this->assertSame(
            '500.00',
            $resumen['verificado']
        );

        $this->assertSame(
            '0.00',
            $resumen['saldo']
        );

        $this->assertSame(
            '0.00',
            $resumen['saldo_disponible']
        );

        $this->assertDatabaseCount(
            'movimientos_ajustes_garantia',
            2
        );
    }

    private function crearCambioConDiferencia(
        float $diferencia
    ) {
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

        $valorReemplazo =
            $valorOriginal
            + $diferencia;

        $equipoEntrante =
            $this->crearEquipoReemplazo(
                equipoBase:
                    $equipoSaliente,

                precioPublico:
                    $valorReemplazo
            );

        return app(CasoGarantiaService::class)
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
                    'Cambio para prueba de liquidación económica 10D-B.',

                observacion:
                    'Fixture automatizado para AjusteGarantiaServiceTest.'
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
                        'Falla confirmada para prueba económica 10D-B.'
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
                        'Equipo presenta falla para prueba de ajuste económico.',

                    observacion:
                        'Caso automatizado para fase 10D-B.'
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
                    'P10B-'
                    . Str::ulid(),

                'nombre' =>
                    'Laptop ajuste garantía 10D-B',

                'modelo' =>
                    'TEST-10D-B',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'G10B-'
                . Str::ulid(),

            'nombre' =>
                'Garantía ajuste económico 10D-B',

            'categoria_producto_id' =>
                $categoria->id,

            'producto_id' =>
                $producto->id,

            'duracion_meses' =>
                6,

            'condiciones' =>
                'Garantía estándar para prueba económica.',

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
                    'E10B-'
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
                'Precio para prueba económica 10D-B.',
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
                    'R10B-'
                    . Str::ulid(),

                'serial_fabricante' =>
                    null,

                'fecha_registro' =>
                    now(),

                'fecha_disponible' =>
                    now(),

                'observacion' =>
                    'Reemplazo para prueba económica 10D-B.',

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
                'Precio vigente para prueba 10D-B.',
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
