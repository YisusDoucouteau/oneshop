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
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaService;
use App\Services\VentaService;
use Database\Seeders\CatalogoInventarioSeeder;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaReservaSeguridadTest extends TestCase
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
                    'Cliente seguridad reserva',

                'telefono' =>
                    '70000888',

                'activo' =>
                    true,
            ]);
    }

    public function test_no_convierte_reserva_activa_pero_vencida(): void
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
                        now()->addHour()
                );

        $reserva->update([
            'fecha_expiracion' =>
                now()->subMinute(),
        ]);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'se encuentra vencida'
        );

        app(VentaService::class)
            ->convertirReservaEnVenta(
                reservaId:
                    $reserva->id,

                vendedorId:
                    $this->usuario->id
            );
    }

    public function test_no_convierte_si_equipo_ya_no_esta_reservado(): void
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
                        now()->addDay()
                );

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        Equipo::query()
            ->whereKey($equipo->id)
            ->update([
                'estado_actual_id' =>
                    $estadoDisponible->id,
            ]);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'ya no se encuentra reservado'
        );

        app(VentaService::class)
            ->convertirReservaEnVenta(
                reservaId:
                    $reserva->id,

                vendedorId:
                    $this->usuario->id
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

        $estado =
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
                    'Laptop seguridad',

                'modelo' =>
                    'TEST',

                'es_serializado' =>
                    true,

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
                    $estado->id,

                'codigo_interno' =>
                    'EQ-SEG-' . Str::uuid(),

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
                2900,

            'precio_publico' =>
                3500,

            'vigente' =>
                true,

            'vigente_desde' =>
                now(),
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
