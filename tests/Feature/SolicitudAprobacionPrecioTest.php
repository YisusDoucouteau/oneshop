<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\SolicitudAprobacionPrecio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SolicitudAprobacionPrecioTest extends TestCase
{
    use RefreshDatabase;


    public function test_registra_medio_de_aprobacion_por_llamada(): void
    {

        /*
        |--------------------------------------------------------------------------
        | Usuario solicitante
        |--------------------------------------------------------------------------
        */

        $vendedor = User::create([

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
        | Usuario aprobador (Daniel)
        |--------------------------------------------------------------------------
        */

        $daniel = User::create([

            'name' =>
                'Daniel',

            'email' =>
                'daniel-' . Str::uuid() . '@test.com',

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

        $categoria = CategoriaProducto::create([

            'codigo' =>
                'CAT-TEST',

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

        $producto = Producto::create([

            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'PROD-TEST-' . Str::uuid(),

            'nombre' =>
                'Laptop Test',

            'modelo' =>
                'Modelo X',

            'descripcion' =>
                'Equipo para prueba de aprobación',

            'activo' =>
                true,
        ]);



        /*
        |--------------------------------------------------------------------------
        | Almacén
        |--------------------------------------------------------------------------
        */

        $almacen = Almacen::create([

            'codigo' =>
                'ALM-TEST',

            'nombre' =>
                'Almacén Principal',

            'ubicacion' =>
                'Oruro',

            'activo' =>
                true,
        ]);



        /*
        |--------------------------------------------------------------------------
        | Estado disponible
        |--------------------------------------------------------------------------
        */

        $estadoDisponible = EstadoEquipo::firstOrCreate(

            [
                'codigo' =>
                    'DISPONIBLE',
            ],

            [
                'nombre' =>
                    'Disponible',

                'activo' =>
                    true,
            ]

        );



        /*
        |--------------------------------------------------------------------------
        | Equipo
        |--------------------------------------------------------------------------
        */

        $equipo = Equipo::create([

            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estadoDisponible->id,

            'codigo_interno' =>
                'EQ-TEST-' . Str::uuid(),

            'activo' =>
                true,
        ]);



        /*
        |--------------------------------------------------------------------------
        | Crear solicitud pendiente
        |--------------------------------------------------------------------------
        */

        $solicitud = SolicitudAprobacionPrecio::create([

            'equipo_id' =>
                $equipo->id,

            'usuario_solicitante_id' =>
                $vendedor->id,

            'precio_publicado' =>
                5000,

            'precio_propuesto' =>
                3700,

            'descuento_solicitado' =>
                1300,

            'ganancia_estimada' =>
                200,

            'motivo' =>
                'Cliente recurrente solicita descuento especial.',

            'estado' =>
                'PENDIENTE',
        ]);



        /*
        |--------------------------------------------------------------------------
        | Daniel aprueba mediante llamada
        |--------------------------------------------------------------------------
        */

        $solicitud->update([

            'estado' =>
                'APROBADA',

            'usuario_aprobador_id' =>
                $daniel->id,

            'fecha_aprobacion' =>
                now(),

            'medio_aprobacion' =>
                'LLAMADA',

            'observacion_aprobacion' =>
                'Autorizado por Daniel mediante llamada telefónica.',
        ]);



        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'solicitud_aprobacion_precios',
            [

                'id' =>
                    $solicitud->id,

                'estado' =>
                    'APROBADA',

                'medio_aprobacion' =>
                    'LLAMADA',

                'usuario_aprobador_id' =>
                    $daniel->id,

            ]
        );

    }
}