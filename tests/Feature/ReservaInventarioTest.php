<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservaInventarioTest extends TestCase
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
                'RECEPCION_LOTE',

            'nombre' =>
                'Recepción lote',

            'activo' =>
                true,

        ]);


        TipoMovimientoInventario::create([

            'codigo' =>
                'RESERVA',

            'nombre' =>
                'Reserva de inventario',

            'activo' =>
                true,

        ]);

    }



    public function test_registra_reserva_y_actualiza_stock()
    {

        $producto = $this->crearProducto();

        $almacen = $this->crearAlmacen();


        app(MovimientoInventarioService::class)
            ->registrarEntrada(

                $producto->id,

                $almacen->id,

                5,

                'RECEPCION_LOTE',

                $this->usuario->id

            );



        $movimiento = app(MovimientoInventarioService::class)
            ->registrarReserva(

                $producto->id,

                $almacen->id,

                1,

                $this->usuario->id,

                'RESERVA',

                1

            );



        $this->assertEquals(

            -1,

            $movimiento->cambio_disponible

        );


        $this->assertEquals(

            1,

            $movimiento->cambio_reservado

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

            4,

            $existencia->cantidad_disponible

        );


        $this->assertEquals(

            1,

            $existencia->cantidad_reservada

        );

    }





    public function test_no_permite_reservar_sin_stock()
    {

        $producto = $this->crearProducto();

        $almacen = $this->crearAlmacen();



        $this->expectException(
            ReglaNegocioException::class
        );



        app(MovimientoInventarioService::class)
            ->registrarReserva(

                $producto->id,

                $almacen->id,

                1,

                $this->usuario->id,

                'RESERVA'

            );

    }





    private function crearProducto(): Producto
    {

        $categoria = CategoriaProducto::create([

            'codigo' =>
                'CAT-' . Str::uuid(),

            'nombre' =>
                'Categoria prueba',

            'activo' =>
                true,

        ]);



        return Producto::create([

            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'PROD-' . Str::uuid(),

            'nombre' =>
                'Laptop prueba',

            'activo' =>
                true,

        ]);

    }





    private function crearAlmacen(): Almacen
    {

        return Almacen::create([

            'codigo' =>
                'ALM-' . Str::uuid(),

            'nombre' =>
                'Almacen prueba',

            'ciudad' =>
                'Oruro',

            'activo' =>
                true,

        ]);

    }

}