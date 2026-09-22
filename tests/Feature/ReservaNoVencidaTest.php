<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\User;
use Database\Seeders\CatalogoInventarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservaNoVencidaTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_no_vence_reserva_que_aun_esta_vigente(): void
    {
        $this->seed(
            CatalogoInventarioSeeder::class
        );

        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente reserva vigente',

            'telefono' =>
                '70000010',

            'activo' =>
                true,
        ]);

        $almacen = Almacen::create([
            'codigo' =>
                'ALM-VIG-' . Str::upper(
                    Str::random(6)
                ),

            'nombre' =>
                'Almacén reserva vigente',

            'ciudad' =>
                'Oruro',

            'activo' =>
                true,
        ]);

        $categoria = CategoriaProducto::create([
            'codigo' =>
                'CAT-VIG-' . Str::upper(
                    Str::random(6)
                ),

            'nombre' =>
                'Categoría vigente',

            'activo' =>
                true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'PROD-' . Str::uuid(),

            'nombre' =>
                'Equipo reserva vigente',

            'activo' =>
                true,
        ]);

        $estadoReservado =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'RESERVADO'
                )
                ->firstOrFail();

        $equipo = Equipo::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estadoReservado->id,

            'codigo_interno' =>
                'EQ-VIG-' . Str::uuid(),

            'fecha_registro' =>
                now(),

            'activo' =>
                true,
        ]);

        \DB::table(
            'existencias_productos'
        )->insert([
            'producto_id' =>
                $producto->id,

            'almacen_id' =>
                $almacen->id,

            'cantidad_disponible' =>
                0,

            'cantidad_reservada' =>
                1,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        $reserva = Reserva::create([
            'numero' =>
                'RES-VIG-' . Str::uuid(),

            'cliente_id' =>
                $cliente->id,

            'registrado_por_id' =>
                $usuario->id,

            'estado' =>
                'ACTIVA',

            'fecha_reserva' =>
                now(),

            'fecha_expiracion' =>
                now()->addDay(),

            'fecha_cierre' =>
                null,
        ]);

        $reserva->detalles()->create([
            'equipo_id' =>
                $equipo->id,

            'precio_acordado' =>
                3500,

            'descuento_acordado' =>
                0,
        ]);

        $this->artisan(
            'reservas:vencer'
        )->assertExitCode(0);

        $reserva->refresh();
        $equipo->refresh();

        $this->assertSame(
            'ACTIVA',
            $reserva->estado
        );

        $this->assertNull(
            $reserva->fecha_cierre
        );

        $this->assertSame(
            $estadoReservado->id,
            $equipo->estado_actual_id
        );

        $existencia =
            \DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $producto->id
                )
                ->where(
                    'almacen_id',
                    $almacen->id
                )
                ->first();

        $this->assertSame(
            0,
            (int) $existencia
                ->cantidad_disponible
        );

        $this->assertSame(
            1,
            (int) $existencia
                ->cantidad_reservada
        );
    }
}
