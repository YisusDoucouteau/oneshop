<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\TipoMovimientoInventario;
use App\Models\ParametroSistema;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\User;
use App\Models\PoliticaGarantia;
use App\Services\PagoService;
use App\Services\ReservaService;
use App\Services\VentaService;
use Database\Seeders\CatalogoSeeder;
use Database\Seeders\ComercialSeeder;
use Database\Seeders\CatalogoInventarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogoSeeder::class);
        $this->seed(ComercialSeeder::class);
        $this->seed(CatalogoInventarioSeeder::class);

     

        ParametroSistema::create([
            'codigo' => 'RESERVA_DIAS_MAXIMOS_ESTANDAR',
            'nombre' => 'Días máximos estándar',
            'modulo' => 'RESERVAS',
            'tipo_dato' => 'ENTERO',
            'valor' => '7',
            'descripcion' => 'Parámetro para pruebas.',
            'editable' => true,
            'activo' => true,
            'modificado_por_id' => null,
        ]);
      
        $this->usuario = User::create([
            'name' => 'Usuario pagos prueba',
            'email' => Str::uuid() . '@oneshop.test',
            'password' => 'password',
            'activo' => true,
        ]);

        $this->cliente = Cliente::create([
            'nombre_completo' => 'Cliente pagos prueba',
            'telefono' => '70000010',
            'activo' => true,
        ]);
    }

    public function test_pago_en_efectivo_se_registra_verificado(): void
    {
        $venta = $this->crearVentaDirecta();

        $efectivo = MetodoPago::where(
            'codigo',
            'EFECTIVO'
        )->firstOrFail();

        $pago = app(PagoService::class)
            ->registrarPagoVenta(
                ventaId: $venta->id,
                metodoPagoId: $efectivo->id,
                monto: '500.00',
                registradoPorId: $this->usuario->id
            );

        $this->assertEquals(
            'VERIFICADO',
            $pago->estado
        );

        $this->assertEquals(
            $this->usuario->id,
            $pago->verificado_por_id
        );

        $this->assertNotNull(
            $pago->fecha_verificacion
        );
    }

    public function test_qr_se_registra_pendiente_y_puede_verificarse(): void
    {
        $venta = $this->crearVentaDirecta();

        $qr = MetodoPago::where(
            'codigo',
            'QR'
        )->firstOrFail();

        $pago = app(PagoService::class)
            ->registrarPagoVenta(
                ventaId: $venta->id,
                metodoPagoId: $qr->id,
                monto: '1000.00',
                registradoPorId: $this->usuario->id,
                referencia: 'QR-TEST-001'
            );

        $this->assertEquals(
            'PENDIENTE',
            $pago->estado
        );

        $pago = app(PagoService::class)
            ->verificarPago(
                pagoId: $pago->id,
                verificadoPorId: $this->usuario->id
            );

        $this->assertEquals(
            'VERIFICADO',
            $pago->estado
        );
    }

    public function test_pago_pendiente_puede_ser_rechazado(): void
    {
        $venta = $this->crearVentaDirecta();

        $qr = MetodoPago::where(
            'codigo',
            'QR'
        )->firstOrFail();

        $pago = app(PagoService::class)
            ->registrarPagoVenta(
                ventaId: $venta->id,
                metodoPagoId: $qr->id,
                monto: '500.00',
                registradoPorId: $this->usuario->id,
                referencia: 'QR-RECHAZO'
            );

        $pago = app(PagoService::class)
            ->rechazarPago(
                pagoId: $pago->id,
                verificadoPorId: $this->usuario->id,
                motivo: 'El pago no fue localizado.'
            );

        $this->assertEquals(
            'RECHAZADO',
            $pago->estado
        );

        $this->assertEquals(
            'El pago no fue localizado.',
            $pago->motivo_rechazo
        );
    }

    public function test_adelanto_de_reserva_se_considera_en_saldo_de_venta_sin_duplicarse(): void
    {
        $equipo = $this->crearEquipoDisponible();
        
        $reserva = app(ReservaService::class)
            ->crearReserva(
                clienteId: $this->cliente->id,
                usuarioId: $this->usuario->id,
                equiposIds: [$equipo->id],
                fechaVencimiento: now()->addDays(2)
            );

        $efectivo = MetodoPago::where(
            'codigo',
            'EFECTIVO'
        )->firstOrFail();

        app(PagoService::class)
            ->registrarPagoReserva(
                reservaId: $reserva->id,
                metodoPagoId: $efectivo->id,
                monto: '100.00',
                registradoPorId: $this->usuario->id
            );

        $this->assertDatabaseCount(
            'pagos',
            1
        );

        $venta = app(VentaService::class)
            ->convertirReservaEnVenta(
                reservaId: $reserva->id,
                vendedorId: $this->usuario->id
            );

        $resumen = app(PagoService::class)
            ->obtenerResumenVenta(
                $venta->id
            );

        $this->assertEquals(
            '3500.00',
            $resumen['total']
        );

        $this->assertEquals(
            '100.00',
            $resumen['pagado_reserva']
        );

        $this->assertEquals(
            '0.00',
            $resumen['pagado_venta']
        );

        $this->assertEquals(
            '100.00',
            $resumen['pagado_total']
        );

        $this->assertEquals(
            '3400.00',
            $resumen['saldo']
        );

        /*
         * El adelanto continúa siendo un único pago.
         */
        $this->assertDatabaseCount(
            'pagos',
            1
        );
    }

    public function test_no_permite_pago_mayor_al_saldo(): void
    {
        $venta = $this->crearVentaDirecta();

        $efectivo = MetodoPago::where(
            'codigo',
            'EFECTIVO'
        )->firstOrFail();

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'supera el saldo pendiente'
        );

        app(PagoService::class)
            ->registrarPagoVenta(
                ventaId: $venta->id,
                metodoPagoId: $efectivo->id,
                monto: '4000.00',
                registradoPorId: $this->usuario->id
            );
    }

    private function crearVentaDirecta()
    {
        $equipo = $this->crearEquipoDisponible();

        return app(VentaService::class)
            ->registrarVentaDirecta(
                vendedorId: $this->usuario->id,
                equiposIds: [$equipo->id],
                clienteId: $this->cliente->id
            );
    }

    private function crearEquipoDisponible(): Equipo
    {
        $categoria = CategoriaProducto::where(
            'codigo',
            'LAPTOP'
        )->firstOrFail();

        $almacen = Almacen::query()
            ->where('activo', true)
            ->orderByDesc('principal')
            ->firstOrFail();

        $estado = EstadoEquipo::where(
            'codigo',
            'DISPONIBLE'
        )->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-' . Str::uuid(),
            'nombre' => 'Laptop pagos prueba',
            'modelo' => 'TEST',
            'es_serializado' => true,
            'activo' => true,
        ]);
        PoliticaGarantia::create([

    'codigo' => 'GAR-' . Str::uuid(),

    'nombre' => 'Garantía laptop prueba',

    'categoria_producto_id' => $categoria->id,

    'producto_id' => $producto->id,

    'duracion_meses' => 6,

    'condiciones' =>
        'Garantía estándar de prueba.',

    'exclusiones' =>
        'Golpes, humedad y daños físicos.',

    'vigente_desde' =>
        now()->subDay(),

    'vigente_hasta' =>
        null,

    'activo' =>
        true,

]);
        $equipo = Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'EQ-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'activo' => true,
        ]);

        PrecioEquipo::create([
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
            'observacion' => 'Precio para prueba.',
        ]);
        DB::table('existencias_productos')
    ->insert([

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
}