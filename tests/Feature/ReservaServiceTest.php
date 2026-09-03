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
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\ReservaService;
use Database\Seeders\CatalogoSeeder;
use Database\Seeders\CatalogoInventarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservaServiceTest extends TestCase
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
         $this->seed(CatalogoInventarioSeeder::class);


        ParametroSistema::create([

            'codigo' =>
                'RESERVA_DIAS_MAXIMOS_ESTANDAR',

            'nombre' =>
                'Días máximos estándar de una reserva',

            'modulo' =>
                'RESERVAS',

            'tipo_dato' =>
                'ENTERO',

            'valor' =>
                '7',

            'descripcion' =>
                'Configuración para pruebas.',

            'editable' =>
                true,

            'activo' =>
                true,

            'modificado_por_id' =>
                null,

        ]);



        $this->usuario = User::create([

            'name' =>
                'Usuario de prueba',

            'email' =>
                Str::uuid() . '@oneshop.test',

            'password' =>
                'password',

            'activo' =>
                true,

        ]);



        $this->cliente = Cliente::create([

            'nombre_completo' =>
                'Cliente de prueba',

            'documento' =>
                null,

            'telefono' =>
                '70000000',

            'correo' =>
                null,

            'direccion' =>
                null,

            'observacion' =>
                null,

            'activo' =>
                true,

        ]);
    }



    public function test_crea_reserva_y_cambia_equipo_a_reservado(): void
    {

        $equipo = $this->crearEquipoDisponible();


        $servicio = app(
            ReservaService::class
        );


        $reserva = $servicio->crearReserva(

            clienteId: $this->cliente->id,

            usuarioId: $this->usuario->id,

            equiposIds: [
                $equipo->id
            ],

            fechaVencimiento:
                now()->addDays(2),

            observacion:
                'Reserva creada desde prueba automatizada.'

        );


        $this->assertEquals(
            'ACTIVA',
            $reserva->estado
        );


        $this->assertDatabaseHas(
            'detalles_reservas',
            [

                'reserva_id' =>
                    $reserva->id,

                'equipo_id' =>
                    $equipo->id,

                'precio_acordado' =>
                    '3500.00',

                'descuento_acordado' =>
                    '0.00',

            ]
        );


        $estadoReservado = EstadoEquipo::query()
            ->where('codigo', 'RESERVADO')
            ->firstOrFail();


        $this->assertDatabaseHas(
            'equipos',
            [

                'id' =>
                    $equipo->id,

                'estado_actual_id' =>
                    $estadoReservado->id,

            ]
        );


        $this->assertDatabaseHas(
            'historial_estados_equipos',
            [

                'equipo_id' =>
                    $equipo->id,

                'estado_destino_id' =>
                    $estadoReservado->id,

            ]
        );


        $this->assertDatabaseHas(
            'movimientos_inventario',
            [

                'tipo_referencia' =>
                    'RESERVA',

            ]
        );

    }



    public function test_no_permite_reservar_equipo_que_no_esta_disponible(): void
    {

        $equipo = $this->crearEquipoDisponible();


        $estadoVendido = EstadoEquipo::query()
            ->where('codigo', 'VENDIDO')
            ->firstOrFail();


        $equipo->update([

            'estado_actual_id' =>
                $estadoVendido->id,

        ]);


        $servicio = app(
            ReservaService::class
        );


        $this->expectException(
            ReglaNegocioException::class
        );


        $this->expectExceptionMessage(
            'no está disponible para reserva'
        );


        $servicio->crearReserva(

            clienteId:
                $this->cliente->id,

            usuarioId:
                $this->usuario->id,

            equiposIds:
                [
                    $equipo->id
                ],

            fechaVencimiento:
                now()->addDays(2)

        );

    }



    public function test_no_permite_reserva_mayor_al_plazo_estandar(): void
    {

        $equipo = $this->crearEquipoDisponible();


        $servicio = app(
            ReservaService::class
        );


        $this->expectException(
            ReglaNegocioException::class
        );


        $this->expectExceptionMessage(
            'no puede superar 7 días'
        );


        $servicio->crearReserva(

            clienteId:
                $this->cliente->id,

            usuarioId:
                $this->usuario->id,

            equiposIds:
                [
                    $equipo->id
                ],

            fechaVencimiento:
                now()->addDays(8)

        );

    }



    public function test_si_falla_un_equipo_se_revierte_toda_la_reserva(): void
    {

        $equipoDisponible =
            $this->crearEquipoDisponible();


        $equipoNoDisponible =
            $this->crearEquipoDisponible();


        $estadoVendido = EstadoEquipo::query()
            ->where('codigo', 'VENDIDO')
            ->firstOrFail();


        $equipoNoDisponible->update([

            'estado_actual_id' =>
                $estadoVendido->id,

        ]);



        $servicio = app(
            ReservaService::class
        );


        try {

            $servicio->crearReserva(

                clienteId:
                    $this->cliente->id,

                usuarioId:
                    $this->usuario->id,

                equiposIds:
                    [

                        $equipoDisponible->id,

                        $equipoNoDisponible->id,

                    ],

                fechaVencimiento:
                    now()->addDays(2)

            );


            $this->fail(
                'La reserva debía ser rechazada.'
            );


        } catch (ReglaNegocioException) {

        }



        $this->assertDatabaseCount(
            'reservas',
            0
        );


        $this->assertDatabaseCount(
            'detalles_reservas',
            0
        );


        $equipoDisponible->refresh();


        $estadoDisponible = EstadoEquipo::query()
            ->where('codigo', 'DISPONIBLE')
            ->firstOrFail();


        $this->assertEquals(

            $estadoDisponible->id,

            $equipoDisponible->estado_actual_id

        );

    }



    private function crearEquipoDisponible(): Equipo
    {

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

            'categoria_producto_id' =>
                $categoria->id,

            'marca_id' =>
                null,

            'codigo' =>
                'PROD-' . Str::uuid(),

            'nombre' =>
                'Laptop de prueba',

            'modelo' =>
                'TEST',

            'descripcion' =>
                null,

            'es_serializado' =>
                true,

            'activo' =>
                true,

        ]);



        $equipo = Equipo::create([

            'producto_id' =>
                $producto->id,

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estadoDisponible->id,

            'condicion_fisica_id' =>
                null,

            'codigo_interno' =>
                'EQ-' . Str::uuid(),

            'serial_fabricante' =>
                null,

            'fecha_registro' =>
                now(),

            'fecha_disponible' =>
                now(),

            'observacion' =>
                null,

            'activo' =>
                true,

        ]);



       PrecioEquipo::create([

    'equipo_id' =>
        $equipo->id,

    'tipo_cambio_id' =>
        null,

    'costo_total_snapshot' =>
        2900,

    'precio_sugerido' =>
        3500,

    'precio_publico' =>
        3500,

    'precio_minimo_autorizado' =>
        3200,

    'vigente_desde' =>
        now(),

    'vigente_hasta' =>
        null,

    'vigente' =>
        true,

    'aprobado_por_id' =>
        null,

    'observacion' =>
        'Precio de prueba.',

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


return $equipo;

    }

}