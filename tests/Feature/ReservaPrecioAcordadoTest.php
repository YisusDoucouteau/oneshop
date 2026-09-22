<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\ParametroSistema;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\User;
use App\Services\ReservaService;
use App\Services\VentaService;
use Database\Seeders\CatalogoInventarioSeeder;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservaPrecioAcordadoTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            CatalogoSeeder::class
        );

        $this->seed(
            CatalogoInventarioSeeder::class
        );

        ParametroSistema::create([
            'codigo' =>
                'RESERVA_DIAS_MAXIMOS_ESTANDAR',

            'nombre' =>
                'Días máximos estándar',

            'modulo' =>
                'RESERVAS',

            'tipo_dato' =>
                'ENTERO',

            'valor' =>
                '7',

            'activo' =>
                true,
        ]);

        $this->usuario =
            User::factory()->create([
                'activo' => true,
            ]);

        $this->cliente =
            Cliente::create([
                'nombre_completo' =>
                    'Cliente negociación',

                'telefono' =>
                    '70000999',

                'activo' =>
                    true,
            ]);
    }

    public function test_reserva_congela_precio_publicado_y_precio_acordado(): void
    {
        $equipo =
            $this->crearEquipoDisponible();

        $reserva =
            app(ReservaService::class)
                ->crearReserva(
                    clienteId:
                        $this->cliente->id,

                    usuarioId:
                        $this->usuario->id,

                    equiposIds:
                        [$equipo->id],

                    fechaVencimiento:
                        now()->addDay(),

                    preciosAcordados: [
                        $equipo->id =>
                            '5300.00',
                    ]
                );

        $this->assertDatabaseHas(
            'detalles_reservas',
            [
                'reserva_id' =>
                    $reserva->id,

                'equipo_id' =>
                    $equipo->id,

                'precio_acordado' =>
                    '5300.00',

                'descuento_acordado' =>
                    '600.00',
            ]
        );
    }

    public function test_no_permite_precio_acordado_superior_al_publicado(): void
    {
        $equipo =
            $this->crearEquipoDisponible();

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no puede superar el precio publicado'
        );

        app(ReservaService::class)
            ->crearReserva(
                clienteId:
                    $this->cliente->id,

                usuarioId:
                    $this->usuario->id,

                equiposIds:
                    [$equipo->id],

                fechaVencimiento:
                    now()->addDay(),

                preciosAcordados: [
                    $equipo->id =>
                        '6000.00',
                ]
            );
    }

    public function test_conversion_a_venta_respeta_precio_acordado_de_reserva(): void
    {
        $equipo =
            $this->crearEquipoDisponible();

        $reserva =
            app(ReservaService::class)
                ->crearReserva(
                    clienteId:
                        $this->cliente->id,

                    usuarioId:
                        $this->usuario->id,

                    equiposIds:
                        [$equipo->id],

                    fechaVencimiento:
                        now()->addDay(),

                    preciosAcordados: [
                        $equipo->id =>
                            '5300.00',
                    ]
                );

        $venta =
            app(VentaService::class)
                ->convertirReservaEnVenta(
                    reservaId:
                        $reserva->id,

                    vendedorId:
                        $this->usuario->id
                );

        $this->assertSame(
            '5300.00',
            (string) $venta->total
        );

        $this->assertDatabaseHas(
            'detalles_ventas',
            [
                'venta_id' =>
                    $venta->id,

                'equipo_id' =>
                    $equipo->id,

                'precio_lista_snapshot' =>
                    '5900.00',

                'descuento_unitario' =>
                    '600.00',

                'precio_unitario' =>
                    '5300.00',

                'subtotal' =>
                    '5300.00',
            ]
        );
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

                'codigo' =>
                    'PROD-' . Str::uuid(),

                'nombre' =>
                    'Laptop reserva comercial',

                'modelo' =>
                    'TEST',

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-' . Str::uuid(),

            'nombre' =>
                'Garantía prueba',

            'categoria_producto_id' =>
                $categoria->id,

            'producto_id' =>
                $producto->id,

            'duracion_meses' =>
                6,

            'condiciones' =>
                'Condiciones de prueba.',

            'exclusiones' =>
                'Daño físico.',

            'vigente_desde' =>
                now()->subDay(),

            'activo' =>
                true,
        ]);

        $equipo =
            Equipo::create([
                'producto_id' =>
                    $producto->id,

                'almacen_actual_id' =>
                    $almacen->id,

                'estado_actual_id' =>
                    $estadoDisponible->id,

                'codigo_interno' =>
                    'EQ-RES-' . Str::uuid(),

                'fecha_registro' =>
                    now(),

                'fecha_disponible' =>
                    now(),

                'activo' =>
                    true,
            ]);

        PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,

            'costo_total_snapshot' =>
                3827,

            'precio_sugerido' =>
                5900,

            'precio_publico' =>
                5900,

            'precio_minimo_autorizado' =>
                5000,

            'vigente_desde' =>
                now(),

            'vigente' =>
                true,
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
}
