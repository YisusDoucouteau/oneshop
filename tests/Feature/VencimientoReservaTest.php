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
use App\Models\Reserva;
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VencimientoReservaTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;



    protected function setUp(): void
    {
        parent::setUp();


        $this->usuario = User::create([

            'name' =>
                'Usuario vencimiento',

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
                'Liberacion reserva',

            'activo' =>
                true,

        ]);



        ParametroSistema::create([

            'codigo' =>
                'RESERVA_DIAS_MAXIMOS_ESTANDAR',

            'nombre' =>
                'Dias maximos reserva',

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




    public function test_comando_vencer_reservas_libera_stock()
    {

        $reserva = $this->crearReservaVencida();



        $this->artisan(
            'reservas:vencer'
        )
        ->assertExitCode(0);



        $reserva->refresh();



        $this->assertEquals(

            'VENCIDA',

            $reserva->estado

        );



        $detalle = $reserva
            ->detalles()
            ->first();



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





    private function crearReservaVencida(): Reserva
    {

        $cliente = Cliente::create([

            'nombre_completo' =>
                'Cliente vencida',

            'telefono' =>
                '70000002',

            'activo' =>
                true,

        ]);



        $almacen = Almacen::create([

            'codigo' =>
                'ALM-VENC',

            'nombre' =>
                'Almacen vencimiento',

            'ciudad' =>
                'Oruro',

            'activo' =>
                true,

        ]);



        $categoria = CategoriaProducto::create([

            'codigo' =>
                'CAT-VENC',

            'nombre' =>
                'Categoria',

            'activo' =>
                true,

        ]);



        $producto = Producto::create([

            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'PROD-VENC',

            'nombre' =>
                'Equipo vencimiento',

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
                'EQ-VENC-' . Str::uuid(),

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
                'RES-VENC-' . Str::uuid(),

            'cliente_id' =>
                $cliente->id,

            'registrado_por_id' =>
                $this->usuario->id,

            'estado' =>
                'ACTIVA',

            'fecha_reserva' =>
                now()->subDays(10),

            'fecha_expiracion' =>
                now()->subDay(),

            'fecha_cierre' =>
                null,

            'observacion' =>
                'Reserva creada para prueba de vencimiento',

        ]);



        $reserva->detalles()->create([

            'equipo_id' =>
                $equipo->id,

            'precio_acordado' =>
                3500,

            'descuento_acordado' =>
                0,

            'observacion' =>
                null,

        ]);



        return $reserva;

    }

}