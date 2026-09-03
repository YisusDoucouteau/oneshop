<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\PoliticaGarantia;
use App\Models\Marca;
use App\Services\GarantiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GarantiaServiceTest extends TestCase
{
    use RefreshDatabase;


    public function test_crea_garantia_desde_detalle_de_venta(): void
    {

        $vendedor = User::create([

            'name' => 'Vendedor Test',

            'email' => 'vendedor@test.com',

            'password' => 'password',

            'activo' => true,

        ]);


        $categoria = CategoriaProducto::create([

            'codigo' => 'LAPTOP',

            'nombre' => 'Laptops',

            'descripcion' => 'Equipos portátiles',

            'activo' => true,

        ]);


        $marca = Marca::create([

            'codigo' => 'TEST',

            'nombre' => 'Marca Test',

            'activo' => true,

        ]);


        $producto = Producto::create([

            'categoria_producto_id' => $categoria->id,

            'marca_id' => $marca->id,

            'codigo' => 'LAP-TEST-001',

            'nombre' => 'Laptop Test',

            'modelo' => 'Modelo X',

            'descripcion' => 'Laptop para prueba',

            'es_serializado' => true,

            'activo' => true,

        ]);


        PoliticaGarantia::create([

            'codigo' => 'GARANTIA_LAPTOP_TEST',

            'nombre' => 'Garantía laptops test',

            'categoria_producto_id' => $categoria->id,

            'producto_id' => null,

            'duracion_meses' => 6,

            'condiciones' => 'Garantía limitada',

            'exclusiones' => 'Golpes y humedad',

            'vigente_desde' => now()->subDay(),

            'vigente_hasta' => null,

            'activo' => true,

        ]);


        $venta = Venta::create([

            'numero' => 'VEN-TEST-001',

            'vendedor_id' => $vendedor->id,

            'fecha_venta' => now(),

            'subtotal' => 5000,

            'descuento_total' => 0,

            'total' => 5000,

            'estado' => 'REGISTRADA',

        ]);


        $detalle = DetalleVenta::create([

            'venta_id' => $venta->id,

            'producto_id' => $producto->id,

            'equipo_id' => null,

            'cantidad' => 1,

            'precio_lista_snapshot' => 5000,

            'descuento_unitario' => 0,

            'precio_unitario' => 5000,

            'costo_unitario_snapshot' => 3500,

            'subtotal' => 5000,

        ]);


        $garantia = app(
            GarantiaService::class
        )->crearDesdeDetalleVenta(
            $detalle
        );


        $this->assertDatabaseHas(
            'garantias',
            [

                'detalle_venta_id' => $detalle->id,

                'estado' => 'VIGENTE',

                'duracion_meses_snapshot' => 6,

            ]
        );


        $this->assertEquals(

            6,

            $garantia->duracion_meses_snapshot

        );

    }
}