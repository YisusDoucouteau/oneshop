<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\ParametroSistema;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\User;
use App\Models\PoliticaGarantia;
use App\Services\ReservaService;
use App\Services\VentaService;

use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendedor;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogoSeeder::class);

        ParametroSistema::create([
            'codigo' => 'RESERVA_DIAS_MAXIMOS_ESTANDAR',
            'nombre' => 'Días máximos estándar de una reserva',
            'modulo' => 'RESERVAS',
            'tipo_dato' => 'ENTERO',
            'valor' => '7',
            'descripcion' => 'Parámetro para pruebas.',
            'editable' => true,
            'activo' => true,
            'modificado_por_id' => null,
        ]);

        $this->vendedor = User::create([
            'name' => 'Vendedor de prueba',
            'email' => Str::uuid() . '@oneshop.test',
            'password' => 'password',
            'activo' => true,
        ]);

        $this->cliente = Cliente::create([
            'nombre_completo' => 'Cliente venta prueba',
            'documento' => null,
            'telefono' => '70000001',
            'correo' => null,
            'direccion' => null,
            'observacion' => null,
            'activo' => true,
        ]);
    }

    public function test_registra_venta_directa_y_marca_equipo_como_vendido(): void
    {
        $equipo = $this->crearEquipoDisponible();

        $servicio = app(VentaService::class);

        $venta = $servicio->registrarVentaDirecta(
            vendedorId: $this->vendedor->id,
            equiposIds: [$equipo->id],
            clienteId: $this->cliente->id,
            observacion: 'Venta directa de prueba.'
        );

        $this->assertNull($venta->reserva_id);
        $this->assertEquals('REGISTRADA', $venta->estado);
        $this->assertEquals('3500.00', $venta->total);

        $this->assertDatabaseHas('detalles_ventas', [
            'venta_id' => $venta->id,
            'producto_id' => $equipo->producto_id,
            'equipo_id' => $equipo->id,
            'cantidad' => 1,
            'precio_lista_snapshot' => '3500.00',
            'descuento_unitario' => '0.00',
            'precio_unitario' => '3500.00',
            'costo_unitario_snapshot' => '2900.00',
            'subtotal' => '3500.00',
        ]);

        $estadoVendido = EstadoEquipo::query()
            ->where('codigo', 'VENDIDO')
            ->firstOrFail();

        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'estado_actual_id' => $estadoVendido->id,
        ]);
    }

    public function test_no_permite_vender_dos_veces_el_mismo_equipo(): void
    {
        $equipo = $this->crearEquipoDisponible();

        $servicio = app(VentaService::class);

        $servicio->registrarVentaDirecta(
            vendedorId: $this->vendedor->id,
            equiposIds: [$equipo->id],
            clienteId: $this->cliente->id
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no está disponible para venta'
        );

        $servicio->registrarVentaDirecta(
            vendedorId: $this->vendedor->id,
            equiposIds: [$equipo->id],
            clienteId: $this->cliente->id
        );
    }

    public function test_convierte_reserva_activa_en_venta(): void
    {
        $equipo = $this->crearEquipoDisponible();

        $reserva = app(ReservaService::class)
            ->crearReserva(
                clienteId: $this->cliente->id,
                usuarioId: $this->vendedor->id,
                equiposIds: [$equipo->id],
                fechaVencimiento: now()->addDays(2)
            );

        $venta = app(VentaService::class)
            ->convertirReservaEnVenta(
                reservaId: $reserva->id,
                vendedorId: $this->vendedor->id
            );

        $this->assertEquals(
            $reserva->id,
            $venta->reserva_id
        );

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => 'CONVERTIDA',
        ]);

        $estadoVendido = EstadoEquipo::query()
            ->where('codigo', 'VENDIDO')
            ->firstOrFail();

        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'estado_actual_id' => $estadoVendido->id,
        ]);
    }

    public function test_no_permite_vender_un_equipo_sin_precio_vigente(): void
    {
        $equipo = $this->crearEquipoDisponible(
            crearPrecio: false
        );

        $servicio = app(VentaService::class);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no posee un precio vigente'
        );

        $servicio->registrarVentaDirecta(
            vendedorId: $this->vendedor->id,
            equiposIds: [$equipo->id],
            clienteId: $this->cliente->id
        );
    }

    public function test_si_un_equipo_no_es_valido_no_se_crea_una_venta_parcial(): void
    {
        $equipoValido = $this->crearEquipoDisponible();
        $equipoInvalido = $this->crearEquipoDisponible();

        $estadoVendido = EstadoEquipo::query()
            ->where('codigo', 'VENDIDO')
            ->firstOrFail();

        $equipoInvalido->update([
            'estado_actual_id' => $estadoVendido->id,
        ]);

        try {
            app(VentaService::class)
                ->registrarVentaDirecta(
                    vendedorId: $this->vendedor->id,
                    equiposIds: [
                        $equipoValido->id,
                        $equipoInvalido->id,
                    ],
                    clienteId: $this->cliente->id
                );

            $this->fail(
                'La venta debía ser rechazada.'
            );
        } catch (ReglaNegocioException) {
            //
        }

        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('detalles_ventas', 0);

        $equipoValido->refresh();

        $estadoDisponible = EstadoEquipo::query()
            ->where('codigo', 'DISPONIBLE')
            ->firstOrFail();

        $this->assertEquals(
            $estadoDisponible->id,
            $equipoValido->estado_actual_id
        );
    }

    private function crearEquipoDisponible(
    bool $crearPrecio = true
): Equipo {

    $categoria = CategoriaProducto::query()
        ->where('codigo', 'LAPTOP')
        ->firstOrFail();


    $almacen = Almacen::query()
        ->where('activo', true)
        ->orderByDesc('principal')
        ->firstOrFail();


    $estadoDisponible = EstadoEquipo::query()
        ->where('codigo', 'DISPONIBLE')
        ->firstOrFail();


    $producto = Producto::create([

        'categoria_producto_id' => $categoria->id,

        'marca_id' => null,

        'codigo' => 'PROD-' . Str::uuid(),

        'nombre' => 'Laptop venta prueba',

        'modelo' => 'TEST',

        'descripcion' => null,

        'es_serializado' => true,

        'activo' => true,

    ]);


    // Política necesaria para generar garantía al vender
    $this->crearPoliticaGarantia(
        $categoria,
        $producto
    );


    $equipo = Equipo::create([

        'producto_id' => $producto->id,

        'detalle_lote_id' => null,

        'almacen_actual_id' => $almacen->id,

        'estado_actual_id' => $estadoDisponible->id,

        'condicion_fisica_id' => null,

        'codigo_interno' => 'EQ-' . Str::uuid(),

        'serial_fabricante' => null,

        'fecha_registro' => now(),

        'fecha_disponible' => now(),

        'observacion' => null,

        'activo' => true,

    ]);


    if ($crearPrecio) {

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

    }


    return $equipo;

}
private function crearPoliticaGarantia(
    CategoriaProducto $categoria,
    Producto $producto
): void {

    PoliticaGarantia::create([

        'codigo' => 'GAR-' . Str::uuid(),

        'nombre' => 'Garantía laptop prueba',

        'categoria_producto_id' => $categoria->id,

        'producto_id' => $producto->id,

        'duracion_meses' => 6,

        'condiciones' =>
            'Garantía estándar para equipo de prueba.',

        'exclusiones' =>
            'Golpes, humedad y daños físicos.',

        'vigente_desde' =>
            now()->subDay(),

        'vigente_hasta' =>
            null,

        'activo' =>
            true,

    ]);

}
}