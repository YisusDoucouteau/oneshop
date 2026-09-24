<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\MetodoPago;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use App\Services\PagoService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagoVentaWebTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->administrador = $this->usuarioConRol(
            'ADMINISTRADOR'
        );

        $this->cliente = Cliente::query()->create([
            'nombre_completo' => 'Cliente pagos web prueba',
            'telefono' => '70000020',
            'correo' => 'pagos-web@oneshop.test',
            'activo' => true,
        ]);
    }

    public function test_administrador_puede_verificar_pago_pendiente(): void
    {
        $venta = $this->crearVentaDirecta();

        $pago = $this->crearQrPendiente(
            venta: $venta,
            monto: '1000.00',
            referencia: 'QR-WEB-VERIFICAR'
        );

        $this
            ->actingAs($this->administrador)
            ->post(
                route(
                    'ventas.pagos.verificar',
                    [$venta, $pago]
                )
            )
            ->assertRedirect(
                route('ventas.show', $venta)
            )
            ->assertSessionHas(
                'success',
                'Pago verificado correctamente.'
            );

        $pago->refresh();

        $this->assertSame(
            'VERIFICADO',
            $pago->estado
        );

        $this->assertSame(
            $this->administrador->id,
            $pago->verificado_por_id
        );

        $this->assertNotNull(
            $pago->fecha_verificacion
        );

        $this->assertNull(
            $pago->motivo_rechazo
        );

        $resumen = app(PagoService::class)
            ->obtenerResumenVenta(
                $venta->id
            );

        $this->assertSame(
            '1000.00',
            $resumen['pagado_total']
        );

        $this->assertSame(
            '0.00',
            $resumen['pendiente_total']
        );

        $this->assertSame(
            '2500.00',
            $resumen['saldo']
        );

        $this->assertSame(
            '2500.00',
            $resumen['saldo_disponible']
        );
    }

    public function test_administrador_puede_rechazar_pago_y_liberar_saldo(): void
    {
        $venta = $this->crearVentaDirecta();

        $pago = $this->crearQrPendiente(
            venta: $venta,
            monto: '3500.00',
            referencia: 'QR-WEB-RECHAZAR'
        );

        $this
            ->actingAs($this->administrador)
            ->post(
                route(
                    'ventas.pagos.rechazar',
                    [$venta, $pago]
                ),
                [
                    'motivo_rechazo' =>
                        'Transferencia no localizada durante la verificación.',
                ]
            )
            ->assertRedirect(
                route('ventas.show', $venta)
            )
            ->assertSessionHas(
                'success',
                'Pago rechazado correctamente. El importe volvió a quedar disponible.'
            );

        $pago->refresh();

        $this->assertSame(
            'RECHAZADO',
            $pago->estado
        );

        $this->assertSame(
            $this->administrador->id,
            $pago->verificado_por_id
        );

        $this->assertNotNull(
            $pago->fecha_verificacion
        );

        $this->assertSame(
            'Transferencia no localizada durante la verificación.',
            $pago->motivo_rechazo
        );

        $resumen = app(PagoService::class)
            ->obtenerResumenVenta(
                $venta->id
            );

        $this->assertSame(
            '0.00',
            $resumen['pagado_total']
        );

        $this->assertSame(
            '0.00',
            $resumen['pendiente_total']
        );

        $this->assertSame(
            '3500.00',
            $resumen['saldo']
        );

        $this->assertSame(
            '3500.00',
            $resumen['saldo_disponible']
        );
    }

    public function test_no_permite_gestionar_pago_que_pertenece_a_otra_venta(): void
    {
        $ventaOrigen = $this->crearVentaDirecta();
        $otraVenta = $this->crearVentaDirecta();

        $pago = $this->crearQrPendiente(
            venta: $ventaOrigen,
            monto: '500.00',
            referencia: 'QR-AJENO'
        );

        $this
            ->actingAs($this->administrador)
            ->post(
                route(
                    'ventas.pagos.verificar',
                    [$otraVenta, $pago]
                )
            )
            ->assertNotFound();

        $pago->refresh();

        $this->assertSame(
            'PENDIENTE',
            $pago->estado
        );

        $this->assertNull(
            $pago->verificado_por_id
        );

        $this->assertNull(
            $pago->fecha_verificacion
        );
    }

    public function test_pago_ya_resuelto_no_puede_verificarse_de_nuevo(): void
    {
        $venta = $this->crearVentaDirecta();

        $pago = $this->crearQrPendiente(
            venta: $venta,
            monto: '500.00',
            referencia: 'QR-DOBLE-VERIFICACION'
        );

        app(PagoService::class)
            ->verificarPago(
                pagoId: $pago->id,
                verificadoPorId: $this->administrador->id
            );

        $this
            ->actingAs($this->administrador)
            ->post(
                route(
                    'ventas.pagos.verificar',
                    [$venta, $pago]
                )
            )
            ->assertRedirect(
                route('ventas.show', $venta)
            )
            ->assertSessionHasErrors(
                'gestion_pago'
            );

        $pago->refresh();

        $this->assertSame(
            'VERIFICADO',
            $pago->estado
        );
    }

    public function test_vendedor_sin_permiso_no_puede_verificar_ni_rechazar_pagos(): void
    {
        $venta = $this->crearVentaDirecta();

        $pago = $this->crearQrPendiente(
            venta: $venta,
            monto: '500.00',
            referencia: 'QR-SIN-PERMISO'
        );

        $vendedor = $this->usuarioConRol(
            'VENDEDOR'
        );

        $this
            ->actingAs($vendedor)
            ->post(
                route(
                    'ventas.pagos.verificar',
                    [$venta, $pago]
                )
            )
            ->assertForbidden();

        $this
            ->actingAs($vendedor)
            ->post(
                route(
                    'ventas.pagos.rechazar',
                    [$venta, $pago]
                ),
                [
                    'motivo_rechazo' =>
                        'Intento sin permiso administrativo.',
                ]
            )
            ->assertForbidden();

        $pago->refresh();

        $this->assertSame(
            'PENDIENTE',
            $pago->estado
        );

        $this->assertNull(
            $pago->verificado_por_id
        );
    }

    private function crearQrPendiente(
        Venta $venta,
        string $monto,
        string $referencia
    ) {
        $qr = MetodoPago::query()
            ->where('codigo', 'QR')
            ->firstOrFail();

        $pago = app(PagoService::class)
            ->registrarPagoVenta(
                ventaId: $venta->id,
                metodoPagoId: $qr->id,
                monto: $monto,
                registradoPorId: $this->administrador->id,
                referencia: $referencia
            );

        $this->assertSame(
            'PENDIENTE',
            $pago->estado
        );

        return $pago;
    }

    private function crearVentaDirecta(): Venta
    {
        $equipo = $this->crearEquipoDisponible();

        return app(VentaService::class)
            ->registrarVentaDirecta(
                vendedorId: $this->administrador->id,
                equiposIds: [$equipo->id],
                clienteId: $this->cliente->id
            );
    }

    private function crearEquipoDisponible(): Equipo
    {
        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $almacen = Almacen::query()
            ->where('activo', true)
            ->orderByDesc('principal')
            ->firstOrFail();

        $estadoDisponible = EstadoEquipo::query()
            ->where('codigo', 'DISPONIBLE')
            ->where('activo', true)
            ->firstOrFail();

        $producto = Producto::query()->create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-PAGO-WEB-' . Str::uuid(),
            'nombre' => 'Laptop pago web prueba',
            'modelo' => 'WEB-TEST',
            'es_serializado' => true,
            'activo' => true,
        ]);

        PoliticaGarantia::query()->create([
            'codigo' => 'GAR-PAGO-WEB-' . Str::uuid(),
            'nombre' => 'Garantía pago web prueba',
            'categoria_producto_id' => $categoria->id,
            'producto_id' => $producto->id,
            'duracion_meses' => 6,
            'condiciones' => 'Garantía estándar de prueba.',
            'exclusiones' => 'Golpes, humedad y daños físicos.',
            'vigente_desde' => now()->subDay(),
            'vigente_hasta' => null,
            'activo' => true,
        ]);

        $equipo = Equipo::query()->create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estadoDisponible->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'EQ-PAGO-WEB-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'activo' => true,
        ]);

        PrecioEquipo::query()->create([
            'equipo_id' => $equipo->id,
            'tipo_cambio_id' => null,
            'costo_total_snapshot' => 2900,
            'precio_sugerido' => 3500,
            'precio_publico' => 3500,
            'precio_minimo_autorizado' => 3200,
            'vigente_desde' => now(),
            'vigente_hasta' => null,
            'vigente' => true,
            'aprobado_por_id' => null,
            'observacion' => 'Precio para prueba web de pagos.',
        ]);

        DB::table('existencias_productos')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_disponible' => 1,
            'cantidad_reservada' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $equipo;
    }

    private function usuarioConRol(
        string $codigoRol
    ): User {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::query()
            ->where('codigo', $codigoRol)
            ->firstOrFail();

        $usuario->roles()->attach(
            $rol->id
        );

        return $usuario;
    }
}