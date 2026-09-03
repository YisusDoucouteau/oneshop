<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\ParametroSistema;
use App\Models\Producto;
use App\Models\PrecioEquipo;
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservaServiceInventarioTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;



    protected function setUp(): void
    {
        parent::setUp();


        $this->usuario = User::create([

            'name' =>
                'Usuario reserva',

            'email' =>
                Str::uuid().'@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,

        ]);



        TipoMovimientoInventario::create([

            'codigo' =>
                'RESERVA',

            'nombre' =>
                'Reserva inventario',

            'descripcion' =>
                'Movimiento generado por reserva',

            'activo' =>
                true,

        ]);



        ParametroSistema::create([

            'codigo' =>
                'RESERVA_DIAS_MAXIMOS_ESTANDAR',

            'nombre' =>
                'Días máximos reserva',

            'modulo' =>
                'RESERVAS',

            'tipo_dato' =>
                'ENTERO',

            'valor' =>
                '7',

            'activo' =>
                true,

        ]);

    }




    public function test_crear_reserva_genera_movimiento_inventario()
    {

        $cliente = Cliente::create([

            'nombre_completo' =>
                'Cliente prueba Reserva',

            'telefono' =>
                '70000000',

            'activo' =>
                true,

        ]);



        $almacen = Almacen::create([

            'codigo' =>
                'ALM-TEST',

            'nombre' =>
                'Almacen prueba',

            'ciudad' =>
                'Oruro',

            'activo' =>
                true,

        ]);



        $categoria = CategoriaProducto::create([

            'codigo' =>
                'CAT-TEST',

            'nombre' =>
                'Categoria prueba',

            'activo' =>
                true,

        ]);



        $producto = Producto::create([

            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'PROD-TEST',

            'nombre' =>
                'Laptop prueba',

            'activo' =>
                true,

        ]);



        $estado = EstadoEquipo::where(
            'codigo',
            'DISPONIBLE'
        )->first();



        if (!$estado) {

            $estado = EstadoEquipo::create([

                'codigo' =>
                    'DISPONIBLE',

                'nombre' =>
                    'Disponible',

                'activo' =>
                    true,

            ]);

        }



        $equipo = Equipo::create([

            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estado->id,

            'codigo_interno' =>
                'EQ-' . Str::uuid(),

            'fecha_registro' =>
                now(),

            'activo' =>
                true,

        ]);



        PrecioEquipo::create([

            'equipo_id' =>
                $equipo->id,

            'precio_publico' =>
                3500,

            'vigente' =>
                true,

            'vigente_desde' =>
                now(),

        ]);



        \DB::table('existencias_productos')
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



        $reserva = app(ReservaService::class)
            ->crearReserva(

                $cliente->id,

                $this->usuario->id,

                [
                    $equipo->id
                ],

                Carbon::now()->addDays(3)

            );



        $this->assertDatabaseHas(
            'reservas',
            [

                'id' =>
                    $reserva->id,

                'estado' =>
                    'ACTIVA',

            ]
        );



        $this->assertDatabaseHas(
            'detalles_reservas',
            [

                'reserva_id' =>
                    $reserva->id,

                'equipo_id' =>
                    $equipo->id,

            ]
        );



        $existencia = \DB::table(
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



        $this->assertEquals(

            0,

            $existencia->cantidad_disponible

        );



        $this->assertEquals(

            1,

            $existencia->cantidad_reservada

        );



        $this->assertDatabaseHas(
            'movimientos_inventario',
            [

                'tipo_referencia' =>
                    'RESERVA',

            ]
        );


    }

}