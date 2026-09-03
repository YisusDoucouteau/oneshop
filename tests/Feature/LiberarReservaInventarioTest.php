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

class LiberarReservaInventarioTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;


    protected function setUp(): void
    {
        parent::setUp();


        $this->usuario = User::create([

            'name' =>
                'Usuario liberacion',

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
                'Reserva',

            'activo' =>
                true,

        ]);


        TipoMovimientoInventario::create([

            'codigo' =>
                'LIBERACION_RESERVA',

            'nombre' =>
                'Liberación reserva',

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



    public function test_liberar_reserva_devuelve_stock()
    {

        $reserva = $this->crearReservaPrueba();



        app(ReservaService::class)
            ->liberarReserva(

                $reserva->id,

                $this->usuario->id

            );



        $this->assertDatabaseHas(
            'reservas',
            [

                'id' =>
                    $reserva->id,

                'estado' =>
                    'LIBERADA',

            ]
        );



        $detalle = $reserva->detalles()->first();



        $existencia = \DB::table(
            'existencias_productos'
        )
        ->where(
            'producto_id',
            $detalle->equipo->producto_id
        )
        ->where(
            'almacen_id',
            $detalle->equipo->almacen_actual_id
        )
        ->first();



        $this->assertEquals(

            1,

            $existencia->cantidad_disponible

        );



        $this->assertEquals(

            0,

            $existencia->cantidad_reservada

        );



        $this->assertDatabaseHas(
            'movimientos_inventario',
            [

                'tipo_referencia' =>
                    'LIBERACION_RESERVA',

            ]
        );

    }





    private function crearReservaPrueba()
{
    $cliente = Cliente::create([

        'nombre_completo' =>
            'Cliente liberacion',

        'telefono' =>
            '71111111',

        'activo' =>
            true,

    ]);



    $almacen = Almacen::create([

        'codigo' =>
            'ALM-LIB',

        'nombre' =>
            'Almacen liberacion',

        'ciudad' =>
            'Oruro',

        'activo' =>
            true,

    ]);



    $categoria = CategoriaProducto::create([

        'codigo' =>
            'CAT-LIB',

        'nombre' =>
            'Categoria liberacion',

        'activo' =>
            true,

    ]);



    $producto = Producto::create([

        'categoria_producto_id' =>
            $categoria->id,

        'codigo' =>
            'PROD-LIB',

        'nombre' =>
            'Equipo liberacion',

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
            'EQ-LIB-' . Str::uuid(),

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



    return app(ReservaService::class)
        ->crearReserva(

            $cliente->id,

            $this->usuario->id,

            [
                $equipo->id
            ],

            Carbon::now()->addDays(3)

        );
}

}