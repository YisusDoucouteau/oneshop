<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
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
use Database\Seeders\CatalogoInventarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DobleLiberacionReservaTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;


   protected function setUp(): void
{
    parent::setUp();

    $this->seed(CatalogoInventarioSeeder::class);

    $this->usuario = User::create([

        'name' => 'Usuario prueba',

        'email' => Str::uuid().'@test.com',

        'password' => bcrypt('123456'),

        'activo' => true,

    ]);


    TipoMovimientoInventario::firstOrCreate(

        [
            'codigo' => 'RESERVA'
        ],

        [
            'nombre' => 'Reserva',
            'activo' => true,
        ]

    );


    TipoMovimientoInventario::firstOrCreate(

        [
            'codigo' => 'LIBERACION_RESERVA'
        ],

        [
            'nombre' => 'Liberación reserva',
            'activo' => true,
        ]

    );


    ParametroSistema::firstOrCreate(

        [
            'codigo' => 'RESERVA_DIAS_MAXIMOS_ESTANDAR'
        ],

        [

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

        ]

    );

}



    public function test_no_permite_liberar_reserva_dos_veces()
    {

        $reserva = $this->crearReserva();



        $service = app(ReservaService::class);



        $service->liberarReserva(

            $reserva->id,

            $this->usuario->id

        );



        $this->expectException(
            ReglaNegocioException::class
        );



        $service->liberarReserva(

            $reserva->id,

            $this->usuario->id

        );

    }





    private function crearReserva()
    {

        $cliente = Cliente::create([

            'nombre_completo' =>
                'Cliente doble liberacion',

            'telefono' =>
                '70000001',

            'activo' =>
                true,

        ]);



        $almacen = Almacen::create([

            'codigo' =>
                'ALM-DOBLE',

            'nombre' =>
                'Almacen prueba',

            'ciudad' =>
                'Oruro',

            'activo' =>
                true,

        ]);



        $categoria = CategoriaProducto::create([

            'codigo' =>
                'CAT-DOBLE',

            'nombre' =>
                'Categoria',

            'activo' =>
                true,

        ]);



        $producto = Producto::create([

            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'PROD-DOBLE',

            'nombre' =>
                'Equipo prueba',

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
                'EQ-DOBLE-' . Str::uuid(),

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