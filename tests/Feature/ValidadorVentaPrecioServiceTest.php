<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\PoliticaDescuento;
use App\Models\User;
use App\Services\ValidadorVentaPrecioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ValidadorVentaPrecioServiceTest extends TestCase
{
    use RefreshDatabase;


    private function crearEquipo(): Equipo
    {

        $categoria = CategoriaProducto::create([
            'codigo'=>'LAPTOP',
            'nombre'=>'Laptop',
            'activo'=>true,
        ]);


        $producto = Producto::create([
            'categoria_producto_id'=>$categoria->id,
            'marca_id'=>null,
            'codigo'=>'PROD-'.Str::uuid(),
            'nombre'=>'Laptop prueba',
            'modelo'=>'TEST',
            'descripcion'=>null,
            'es_serializado'=>true,
            'activo'=>true,
        ]);


        $almacen = Almacen::create([
            'codigo'=>'ORU',
            'nombre'=>'Almacen Oruro',
            'ciudad'=>'Oruro',
            'direccion'=>null,
            'principal'=>true,
            'activo'=>true,
        ]);


        $estado = EstadoEquipo::create([
            'codigo'=>'DISPONIBLE',
            'nombre'=>'Disponible',
            'descripcion'=>null,
            'es_final'=>false,
            'orden'=>1,
            'activo'=>true,
        ]);


        return Equipo::create([

            'producto_id'=>$producto->id,

            'almacen_actual_id'=>$almacen->id,

            'estado_actual_id'=>$estado->id,

            'codigo_interno'=>'EQ-'.Str::uuid(),

            'serial_fabricante'=>null,

            'fecha_registro'=>now(),

            'fecha_disponible'=>now(),

            'observacion'=>null,

            'activo'=>true,
        ]);
    }



    private function crearPrecio(
        Equipo $equipo
    ): PrecioEquipo {

        return PrecioEquipo::create([

            'equipo_id'=>$equipo->id,

            'costo_total_snapshot'=>3500,

            'precio_sugerido'=>5200,

            'precio_publico'=>5000,

            'precio_minimo_autorizado'=>4500,

            'vigente_desde'=>now(),

            'vigente'=>true,
        ]);
    }



    private function crearPolitica(): void
    {
        PoliticaDescuento::create([

            'codigo'=>'GENERAL',

            'nombre'=>'General',

            'categoria_producto_id'=>null,

            'dias_desde'=>0,

            'dias_hasta'=>null,

            'porcentaje_maximo'=>10,

            'utilidad_minima_bob'=>500,

            'permite_precio_costo'=>false,

            'requiere_autorizacion'=>true,

            'vigente_desde'=>now(),

            'activo'=>true,
        ]);
    }



    public function test_permite_precio_publicado()
    {
        $equipo=$this->crearEquipo();

        $this->crearPrecio($equipo);


        $resultado =
            app(ValidadorVentaPrecioService::class)
                ->validar(
                    $equipo->id,
                    5000
                );


        $this->assertTrue(
            $resultado['permitido']
        );
    }



    public function test_permite_descuento_dentro_de_politica()
    {
        $equipo=$this->crearEquipo();

        $this->crearPrecio($equipo);

        $this->crearPolitica();


        $resultado =
            app(ValidadorVentaPrecioService::class)
                ->validar(
                    $equipo->id,
                    4700
                );


        $this->assertTrue(
            $resultado['permitido']
        );
    }



    public function test_detecta_descuento_fuera_de_politica()
    {
        $equipo=$this->crearEquipo();

        $this->crearPrecio($equipo);

        $this->crearPolitica();


        $resultado =
            app(ValidadorVentaPrecioService::class)
                ->validar(
                    $equipo->id,
                    4000
                );


        $this->assertFalse(
            $resultado['permitido']
        );

        $this->assertTrue(
            $resultado['requiere_aprobacion']
        );
    }



    public function test_no_permita_equipo_inexistente()
    {
        $this->expectException(
            \InvalidArgumentException::class
        );


        app(ValidadorVentaPrecioService::class)
            ->validar(
                999999,
                5000
            );
    }
    public function test_crea_solicitud_de_descuento_cuando_requiere_aprobacion()
{
    $equipo =
        $this->crearEquipo();


    $precio =
        $this->crearPrecio($equipo);


    $this->crearPolitica();


    $usuario =
        User::create([
            'name' =>
                'Vendedor prueba',

            'email' =>
                'vendedor@test.com',

            'password' =>
                bcrypt('password'),

            'activo' =>
                true,
        ]);


    $resultado =
        app(ValidadorVentaPrecioService::class)
            ->validar(
                $equipo->id,
                4000,
                null,
                $usuario->id
            );


    $this->assertFalse(
        $resultado['permitido']
    );


    $this->assertTrue(
        $resultado['requiere_aprobacion']
    );


    $this->assertNotNull(
        $resultado['solicitud']
    );


    $this->assertDatabaseHas(
        'solicitudes_descuentos',
        [
            'precio_equipo_id' =>
                $precio->id,

            'estado' =>
                'PENDIENTE',
        ]
    );

}
}