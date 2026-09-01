<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\CategoriaProducto;
use App\Services\ReporteMargenVentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteMargenVentaServiceTest extends TestCase
{
    use RefreshDatabase;


    public function test_calcula_utilidad_de_una_venta()
    {

        /*
        |--------------------------------------------------------------------------
        | Usuario vendedor
        |--------------------------------------------------------------------------
        */

        $usuario = User::create([

            'name' =>
                'Vendedor Test',

            'email' =>
                'vendedor-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,
        ]);



        /*
        |--------------------------------------------------------------------------
        | Categoría producto
        |--------------------------------------------------------------------------
        */

        $categoria =
            CategoriaProducto::create([

                'codigo' =>
                    'COMP',

                'nombre' =>
                    'Computación',

                'activo' =>
                    true,
            ]);



        /*
        |--------------------------------------------------------------------------
        | Producto
        |--------------------------------------------------------------------------
        */

        $producto =
            \App\Models\Producto::create([

                'categoria_producto_id' =>
                    $categoria->id,

                'codigo' =>
                    'PROD-TEST-001',

                'nombre' =>
                    'Laptop Test',

                'modelo' =>
                    'Modelo Test',

                'descripcion' =>
                    'Equipo para prueba de margen',

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);



        /*
        |--------------------------------------------------------------------------
        | Venta
        |--------------------------------------------------------------------------
        */

        $venta =
            Venta::create([

                'numero' =>
                    'VEN-TEST-001',

                'cliente_id' =>
                    null,

                'vendedor_id' =>
                    $usuario->id,

                'reserva_id' =>
                    null,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    5000,

                'descuento_total' =>
                    0,

                'total' =>
                    5000,

                'estado' =>
                    'REGISTRADA',

                'anulado_por_id' =>
                    null,

                'fecha_anulacion' =>
                    null,

                'motivo_anulacion' =>
                    null,

                'observacion' =>
                    null,
            ]);



        /*
        |--------------------------------------------------------------------------
        | Detalle venta
        |--------------------------------------------------------------------------
        */

        DetalleVenta::create([

            'venta_id' =>
                $venta->id,

            'producto_id' =>
                $producto->id,

            'equipo_id' =>
                null,

            'cantidad' =>
                1,

            'precio_lista_snapshot' =>
                5000,

            'descuento_unitario' =>
                0,

            'precio_unitario' =>
                5000,

            'costo_unitario_snapshot' =>
                3500,

            'subtotal' =>
                5000,

            'observacion' =>
                null,
        ]);



        /*
        |--------------------------------------------------------------------------
        | Ejecutar servicio
        |--------------------------------------------------------------------------
        */

        $resultado =
            app(ReporteMargenVentaService::class)
                ->calcularMargenVenta(
                    $venta->id
                );



        /*
        |--------------------------------------------------------------------------
        | Validaciones
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            5000.0,
            $resultado['total_venta']
        );


        $this->assertSame(
            3500.0,
            $resultado['costo_total']
        );


        $this->assertSame(
            1500.0,
            $resultado['utilidad']
        );


        $this->assertSame(
            30.0,
            $resultado['porcentaje_margen']
        );


        $this->assertFalse(
            $resultado['tiene_descuento']
        );
    }



    public function test_no_permite_venta_inexistente()
    {

        $this->expectException(
            \InvalidArgumentException::class
        );


        app(ReporteMargenVentaService::class)
            ->calcularMargenVenta(
                999999
            );
    }
}