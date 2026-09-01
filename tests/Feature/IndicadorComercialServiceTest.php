<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Services\IndicadorComercialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IndicadorComercialServiceTest extends TestCase
{
    use RefreshDatabase;


    public function test_resumen_general_calcula_indicadores()
    {

        $usuario = User::create([

            'name' =>
                'Vendedor Test',

            'email' =>
                'indicador-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,
        ]);



        $categoria =
            CategoriaProducto::create([

                'codigo' =>
                    'COMP',

                'nombre' =>
                    'Computacion',

                'activo' =>
                    true,
            ]);



        $producto =
            Producto::create([

                'categoria_producto_id' =>
                    $categoria->id,

                'codigo' =>
                    'PROD-001',

                'nombre' =>
                    'Laptop',

                'modelo' =>
                    'TEST',

                'descripcion' =>
                    'Equipo prueba',

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);



        $venta =
            Venta::create([

                'numero' =>
                    'VEN-IND-001',

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
                    200,

                'total' =>
                    4800,

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
                200,

            'precio_unitario' =>
                4800,

            'costo_unitario_snapshot' =>
                3500,

            'subtotal' =>
                4800,

            'observacion' =>
                null,
        ]);



        $resultado =
            app(IndicadorComercialService::class)
                ->resumenGeneral();



        $this->assertSame(
            1,
            $resultado['cantidad_ventas']
        );


        $this->assertSame(
            4800.0,
            $resultado['total_vendido']
        );


        $this->assertSame(
            3500.0,
            $resultado['costo_total']
        );


        $this->assertSame(
            1300.0,
            $resultado['utilidad_total']
        );


        $this->assertSame(
            200.0,
            $resultado['descuentos_otorgados']
        );
    }



    public function test_ventas_por_vendedor()
    {

        $resultado =
            app(IndicadorComercialService::class)
                ->ventasPorVendedor();


        $this->assertNotNull(
            $resultado
        );
    }
}