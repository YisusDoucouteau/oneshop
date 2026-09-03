<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MovimientoInventarioServiceTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;


    protected function setUp(): void
    {
        parent::setUp();


        $this->usuario = User::create([

            'name' =>
                'Usuario inventario',

            'email' =>
                Str::uuid().'@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,

        ]);
            TipoMovimientoInventario::create([
    'codigo' => 'VENTA_DIRECTA',
    'nombre' => 'Venta directa',
    'activo' => true,
]);

        TipoMovimientoInventario::create([

            'codigo' =>
                'RECEPCION_LOTE',

            'nombre' =>
                'Recepción lote',

            'descripcion' =>
                'Ingreso por recepción',

            'activo' =>
                true,

        ]);

    }



    public function test_registra_entrada_y_actualiza_existencia()
    {

        $producto = $this->crearProducto();

        $almacen = $this->crearAlmacen();


        $movimiento = app(
            MovimientoInventarioService::class
        )->registrarEntrada(

            $producto->id,

            $almacen->id,

            10,

            'RECEPCION_LOTE',

            $this->usuario->id

        );


        $this->assertEquals(

            10,

            $movimiento->cambio_disponible

        );


        $this->assertEquals(

            10,

            $movimiento->saldo_disponible_resultante

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


        $this->assertNotNull(
            $existencia
        );


        $this->assertEquals(

            10,

            $existencia->cantidad_disponible

        );

    }



    public function test_no_permite_entrada_con_cantidad_negativa()
    {

        $this->expectException(
            \App\Exceptions\ReglaNegocioException::class
        );


        app(
            MovimientoInventarioService::class
        )->registrarEntrada(

            1,

            1,

            0,

            'RECEPCION_LOTE'

        );

    }
public function test_registra_salida_y_reduce_existencia()
{

    $producto = $this->crearProducto();

    $almacen = $this->crearAlmacen();


    app(MovimientoInventarioService::class)
        ->registrarEntrada(
            $producto->id,
            $almacen->id,
            10,
            'RECEPCION_LOTE',
            $this->usuario->id
        );


    $movimiento = app(MovimientoInventarioService::class)
        ->registrarSalida(
            $producto->id,
            $almacen->id,
            3,
            'VENTA_DIRECTA',
            $this->usuario->id
        );


    $this->assertEquals(
        -3,
        $movimiento->cambio_disponible
    );


    $this->assertEquals(
        7,
        $movimiento->saldo_disponible_resultante
    );


}



public function test_no_permite_salida_mayor_al_stock()
{

    $producto = $this->crearProducto();

    $almacen = $this->crearAlmacen();


    app(MovimientoInventarioService::class)
        ->registrarEntrada(
            $producto->id,
            $almacen->id,
            2,
            'RECEPCION_LOTE'
        );


    $this->expectException(
        \App\Exceptions\ReglaNegocioException::class
    );


    app(MovimientoInventarioService::class)
        ->registrarSalida(
            $producto->id,
            $almacen->id,
            5,
            'VENTA_DIRECTA'
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