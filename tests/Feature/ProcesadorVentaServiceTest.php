<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\EstadoEquipo;
use App\Models\Equipo;
use App\Models\PoliticaDescuento;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\TransicionEstadoEquipo;
use App\Models\User;
use App\Services\ProcesadorVentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcesadorVentaServiceTest extends TestCase
{
    use RefreshDatabase;


    private function crearEquipo(): Equipo
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'LAP',
            'nombre' => 'Laptop',
            'activo' => true,
        ]);


        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-' . Str::uuid(),
            'nombre' => 'Equipo prueba',
            'modelo' => 'TEST',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);


        $almacen = Almacen::create([
            'codigo' => 'ORU',
            'nombre' => 'Almacen Oruro',
            'ciudad' => 'Oruro',
            'direccion' => null,
            'principal' => true,
            'activo' => true,
        ]);



        $estadoDisponible =
            EstadoEquipo::create([
                'codigo' => 'DISPONIBLE',
                'nombre' => 'Disponible',
                'descripcion' => null,
                'es_final' => false,
                'orden' => 1,
                'activo' => true,
            ]);



        $estadoVendido =
            EstadoEquipo::create([
                'codigo' => 'VENDIDO',
                'nombre' => 'Vendido',
                'descripcion' => null,
                'es_final' => true,
                'orden' => 2,
                'activo' => true,
            ]);



        TransicionEstadoEquipo::create([
            'estado_origen_id' =>
                $estadoDisponible->id,

            'estado_destino_id' =>
                $estadoVendido->id,

            'activo' =>
                true,
        ]);



        return Equipo::create([

            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estadoDisponible->id,

            'codigo_interno' =>
                'EQ-' . Str::uuid(),

            'activo' =>
                true,
        ]);
    }



    private function crearPrecio(
        Equipo $equipo
    ): PrecioEquipo {

        return PrecioEquipo::create([

            'equipo_id' =>
                $equipo->id,

            'costo_total_snapshot' =>
                3500,

            'precio_sugerido' =>
                5200,

            'precio_publico' =>
                5000,

            'precio_minimo_autorizado' =>
                4500,

            'vigente_desde' =>
                now(),

            'vigente' =>
                true,
        ]);
    }



    private function crearUsuario(): User
    {
        return User::create([

            'name' =>
                'Vendedor',

            'email' =>
                'vendedor-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,
        ]);
    }



    public function test_permita_venta_con_precio_publicado()
    {

        $equipo =
            $this->crearEquipo();


        $this->crearPrecio(
            $equipo
        );


        $usuario =
            $this->crearUsuario();



        $venta =
            app(ProcesadorVentaService::class)
                ->procesarVentaDirecta(

                    vendedorId:
                        $usuario->id,

                    equipos: [

                        [
                            'equipo_id' =>
                                $equipo->id,

                            'precio' =>
                                5000,
                        ]

                    ]
                );



        $this->assertNotNull(
            $venta
        );


        $this->assertDatabaseHas(
            'ventas',
            [
                'vendedor_id' =>
                    $usuario->id,

                'estado' =>
                    'REGISTRADA',
            ]
        );
    }




    public function test_bloquea_descuento_fuera_de_politica()
    {

        $this->expectException(
            ReglaNegocioException::class
        );


        $equipo =
            $this->crearEquipo();


        $this->crearPrecio(
            $equipo
        );



        PoliticaDescuento::create([

            'codigo' =>
                'GENERAL',

            'nombre' =>
                'General',

            'base_antiguedad' =>
                'DIAS',

            'dias_desde' =>
                0,

            'dias_hasta' =>
                null,

            'porcentaje_maximo' =>
                10,

            'utilidad_minima_bob' =>
                500,

            'permite_precio_costo' =>
                false,

            'requiere_autorizacion' =>
                false,

            'vigente_desde' =>
                now(),

            'activo' =>
                true,
        ]);



        $usuario =
            $this->crearUsuario();



        app(ProcesadorVentaService::class)
            ->procesarVentaDirecta(

                vendedorId:
                    $usuario->id,

                equipos: [

                    [
                        'equipo_id' =>
                            $equipo->id,

                        'precio' =>
                            3500,
                    ]

                ]
            );
    }
}