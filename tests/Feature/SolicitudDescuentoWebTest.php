<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\SolicitudDescuento;
use App\Models\User;
use App\Services\CostoComercialActualService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class SolicitudDescuentoWebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendedor;
    private Equipo $equipo;
    private PrecioEquipo $precio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = $this->crearUsuarioConRol('ADMINISTRADOR');
        $this->vendedor = $this->crearUsuarioConRol('VENDEDOR');
        [$this->equipo, $this->precio] = $this->crearEquipoConPrecio();
    }

    public function test_vendedor_solicita_autorizacion_debajo_del_minimo(): void
    {
        $this->mockCostoComercial(3500);

        $this
            ->actingAs($this->vendedor)
            ->post(
                route(
                    'precios.autorizaciones.store',
                    $this->equipo
                ),
                [
                    'precio_propuesto' => 4400,
                    'motivo' => 'Cliente confirma compra inmediata.',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'solicitudes_descuentos',
            [
                'precio_equipo_id' => $this->precio->id,
                'solicitado_por_id' => $this->vendedor->id,
                'precio_solicitado' => '4400.00',
                'estado' => 'PENDIENTE',
                'motivo' => 'Cliente confirma compra inmediata.',
            ]
        );
    }

    public function test_vendedor_puede_solicitar_excepcion_por_ajax_sin_salir_de_la_venta(): void
    {
        $this->mockCostoComercial(3500);

        $this
            ->actingAs($this->vendedor)
            ->postJson(
                route(
                    'precios.autorizaciones.store',
                    $this->equipo
                ),
                [
                    'precio_propuesto' => 4400,
                    'motivo' => 'Cliente confirma compra inmediata.',
                ]
            )
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('estado', 'PENDIENTE')
            ->assertJsonStructure([
                'solicitud_id',
                'message',
            ]);

        $this->assertDatabaseHas(
            'solicitudes_descuentos',
            [
                'precio_equipo_id' => $this->precio->id,
                'solicitado_por_id' => $this->vendedor->id,
                'precio_solicitado' => '4400.00',
                'estado' => 'PENDIENTE',
                'motivo' => 'Cliente confirma compra inmediata.',
            ]
        );
    }

    public function test_admin_ve_bandeja_y_aprueba_solicitud(): void
    {
        $solicitud = $this->crearSolicitudPendiente();

        $this
            ->actingAs($this->admin)
            ->get(route('precios.autorizaciones.index'))
            ->assertOk()
            ->assertSee('Solicitudes de descuento')
            ->assertSee($this->equipo->codigo_interno)
            ->assertSee('Bs 4,400.00');

        $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'precios.autorizaciones.aprobar',
                    $solicitud
                ),
                [
                    'medio_respuesta' => 'SISTEMA',
                    'motivo_respuesta' => 'Margen aceptable.',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'solicitudes_descuentos',
            [
                'id' => $solicitud->id,
                'estado' => 'APROBADA',
                'respondido_por_id' => $this->admin->id,
                'medio_respuesta' => 'SISTEMA',
            ]
        );
    }

    public function test_autorizacion_aprobada_habilita_mismo_precio_para_el_vendedor(): void
    {
        $solicitud = $this->crearSolicitudPendiente();

        $solicitud->update([
            'estado' => 'APROBADA',
            'respondido_por_id' => $this->admin->id,
            'fecha_respuesta' => now(),
            'medio_respuesta' => 'SISTEMA',
        ]);

        $this->mockCostoComercial(3500);

        $resultado = app(
            \App\Services\ValidadorVentaPrecioService::class
        )->validar(
            equipoId: $this->equipo->id,
            precioPropuesto: 4400,
            clienteId: null,
            vendedorId: $this->vendedor->id
        );

        $this->assertTrue($resultado['permitido']);
        $this->assertFalse($resultado['requiere_aprobacion']);
        $this->assertSame('AUTORIZADO', $resultado['estado']);
        $this->assertSame(
            $solicitud->id,
            $resultado['solicitud_aprobada']?->id
        );
    }

    private function crearSolicitudPendiente(): SolicitudDescuento
    {
        return SolicitudDescuento::create([
            'precio_equipo_id' => $this->precio->id,
            'politica_descuento_id' => null,
            'cliente_id' => null,
            'solicitado_por_id' => $this->vendedor->id,
            'precio_publico_snapshot' => 5000,
            'precio_solicitado' => 4400,
            'descuento_solicitado' => 600,
            'porcentaje_descuento' => 12,
            'costo_total_snapshot' => 3500,
            'utilidad_proyectada' => 900,
            'estado' => 'PENDIENTE',
            'motivo' => 'Cliente confirma compra inmediata.',
        ]);
    }

    private function mockCostoComercial(float $costo): void
    {
        $mock = Mockery::mock(
            CostoComercialActualService::class
        );

        $mock
            ->shouldReceive('calcular')
            ->andReturn([
                'equipo_id' => $this->equipo->id,
                'costo_total' => $costo,
                'costo_compra_actualizado' => $costo,
                'costos_lote' => 0.0,
                'intervenciones' => 0.0,
                'costos_posteriores' => 0.0,
                'moneda_origen' => null,
                'monto_origen' => null,
                'usa_tipo_cambio' => false,
                'tipo_cambio_id' => null,
                'tipo_cambio' => null,
                'fuente' => 'COSTO_HISTORICO',
            ]);

        $this->app->instance(
            CostoComercialActualService::class,
            $mock
        );
    }

    private function crearUsuarioConRol(string $codigoRol): User
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::query()
            ->where('codigo', $codigoRol)
            ->firstOrFail();

        $usuario->roles()->attach($rol->id);

        return $usuario;
    }

    private function crearEquipoConPrecio(): array
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'AUTH-WEB-' . Str::upper(Str::random(8)),
            'nombre' => 'Laptops autorización web',
            'activo' => true,
        ]);

        $almacen = Almacen::create([
            'codigo' => 'AUTH-ALM-' . Str::upper(Str::random(8)),
            'nombre' => 'Almacén autorización web',
            'ciudad' => 'Oruro',
            'principal' => true,
            'activo' => true,
        ]);

        $estado = EstadoEquipo::create([
            'codigo' => 'AUTH-DISP-' . Str::upper(Str::random(8)),
            'nombre' => 'Disponible',
            'es_final' => true,
            'orden' => 1,
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'AUTH-PROD-' . Str::uuid(),
            'nombre' => 'Laptop autorización',
            'modelo' => 'WEB',
            'es_serializado' => true,
            'activo' => true,
        ]);

        $equipo = Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'AUTH-EQ-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'observacion' => null,
            'activo' => true,
        ]);

        $precio = PrecioEquipo::create([
            'equipo_id' => $equipo->id,
            'tipo_cambio_id' => null,
            'costo_total_snapshot' => 3500,
            'precio_sugerido' => 5000,
            'precio_publico' => 5000,
            'precio_minimo_autorizado' => 4600,
            'vigente_desde' => now(),
            'vigente_hasta' => null,
            'vigente' => true,
            'aprobado_por_id' => $this->admin->id,
            'observacion' => null,
        ]);

        return [$equipo, $precio];
    }
}
