<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Rol;
use App\Models\User;
use App\Services\CatalogoImportacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogoImportacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        /*
        |--------------------------------------------------------------------------
        | Usuario ADMIN_OPERATIVO
        |--------------------------------------------------------------------------
        */

        $this->operativo = User::factory()->create([
            'activo' => true,
        ]);

        $rolOperativo = Rol::query()
            ->where(
                'codigo',
                'ADMIN_OPERATIVO'
            )
            ->firstOrFail();

        $this->operativo
            ->roles()
            ->attach(
                $rolOperativo->id
            );


        /*
        |--------------------------------------------------------------------------
        | Usuario VENDEDOR
        |--------------------------------------------------------------------------
        */

        $this->vendedor = User::factory()->create([
            'activo' => true,
        ]);

        $rolVendedor = Rol::query()
            ->where(
                'codigo',
                'VENDEDOR'
            )
            ->firstOrFail();

        $this->vendedor
            ->roles()
            ->attach(
                $rolVendedor->id
            );
    }


    public function test_operativo_puede_crear_producto_con_catalogos_existentes(): void
    {
        $categoria = CategoriaProducto::query()
            ->where(
                'codigo',
                'LAPTOP'
            )
            ->firstOrFail();

        $marca = Marca::create([
            'nombre' =>
                'Framework Test',

            'descripcion' =>
                null,

            'activo' =>
                true,
        ]);

        $producto = app(
            CatalogoImportacionService::class
        )->crearProductoRapido(
            $this->operativo->id,
            [
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'nombre' =>
                    'Laptop 13',

                'modelo' =>
                    'AMD 7040',

                'es_serializado' =>
                    true,
            ]
        );

        $this->assertDatabaseHas(
            'productos',
            [
                'id' =>
                    $producto->id,

                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'nombre' =>
                    'Laptop 13',

                'modelo' =>
                    'AMD 7040',

                'es_serializado' =>
                    1,

                'activo' =>
                    1,
            ]
        );

        $this->assertNotEmpty(
            $producto->codigo
        );
    }


    public function test_puede_crear_producto_con_categoria_y_marca_nuevas(): void
    {
        $producto = app(
            CatalogoImportacionService::class
        )->crearProductoRapido(
            $this->operativo->id,
            [
                'nueva_categoria_nombre' =>
                    'Consola',

                'nueva_marca_nombre' =>
                    'Sony Test',

                'nombre' =>
                    'PlayStation',

                'modelo' =>
                    '5 Slim',

                'es_serializado' =>
                    true,
            ]
        );

        $this->assertDatabaseHas(
            'categorias_productos',
            [
                'nombre' =>
                    'Consola',

                'activo' =>
                    1,
            ]
        );

        $this->assertDatabaseHas(
            'marcas',
            [
                'nombre' =>
                    'Sony Test',

                'activo' =>
                    1,
            ]
        );

        $this->assertDatabaseHas(
            'productos',
            [
                'id' =>
                    $producto->id,

                'nombre' =>
                    'PlayStation',

                'modelo' =>
                    '5 Slim',
            ]
        );
    }


    public function test_no_permite_crear_producto_duplicado(): void
    {
        $categoria = CategoriaProducto::query()
            ->where(
                'codigo',
                'LAPTOP'
            )
            ->firstOrFail();

        $marca = Marca::create([
            'nombre' =>
                'Acer Test',

            'descripcion' =>
                null,

            'activo' =>
                true,
        ]);

        $service = app(
            CatalogoImportacionService::class
        );

        $datos = [
            'categoria_producto_id' =>
                $categoria->id,

            'marca_id' =>
                $marca->id,

            'nombre' =>
                'TravelMate',

            'modelo' =>
                'P2',

            'es_serializado' =>
                true,
        ];

        $service->crearProductoRapido(
            $this->operativo->id,
            $datos
        );

        try {
            $service->crearProductoRapido(
                $this->operativo->id,
                $datos
            );

            $this->fail(
                'Se esperaba ValidationException por producto duplicado.'
            );

        } catch (ValidationException $exception) {

            $this->assertArrayHasKey(
                'nombre',
                $exception->errors()
            );
        }

       
            $this->assertArrayHasKey(
                'nombre',
                $exception->errors()
            );
    }


    public function test_operativo_puede_crear_proveedor_rapido(): void
    {
        $proveedor = app(
            CatalogoImportacionService::class
        )->crearProveedorRapido(
            $this->operativo->id,
            [
                'nombre' =>
                    'Auction Tech LLC',

                'pais' =>
                    'Estados Unidos',

                'ciudad' =>
                    'Houston',

                'contacto' =>
                    'Departamento de ventas',

                'correo' =>
                    'ventas@auction-test.com',
            ]
        );

        $this->assertDatabaseHas(
            'proveedores',
            [
                'id' =>
                    $proveedor->id,

                'nombre' =>
                    'Auction Tech LLC',

                'pais' =>
                    'Estados Unidos',

                'ciudad' =>
                    'Houston',

                'activo' =>
                    1,
            ]
        );
    }


    public function test_no_permite_proveedor_duplicado_por_nombre_y_pais(): void
    {
        $service = app(
            CatalogoImportacionService::class
        );

        $datos = [
            'nombre' =>
                'Auction USA Test',

            'pais' =>
                'Estados Unidos',
        ];

        $service->crearProveedorRapido(
            $this->operativo->id,
            $datos
        );

        try {
            $service->crearProveedorRapido(
                $this->operativo->id,
                $datos
            );

            $this->fail(
                'Se esperaba ValidationException por proveedor duplicado.'
            );

        } catch (ValidationException $exception) {

            $this->assertArrayHasKey(
                'nombre',
                $exception->errors()
            );
        }
    }


    public function test_vendedor_no_puede_crear_producto_desde_importaciones(): void
    {
        $categoria = CategoriaProducto::query()
            ->where(
                'codigo',
                'LAPTOP'
            )
            ->firstOrFail();

        $this->expectException(
            ReglaNegocioException::class
        );

        app(
            CatalogoImportacionService::class
        )->crearProductoRapido(
            $this->vendedor->id,
            [
                'categoria_producto_id' =>
                    $categoria->id,

                'nombre' =>
                    'Producto bloqueado',

                'modelo' =>
                    'TEST',
            ]
        );
    }


    public function test_vendedor_no_puede_crear_proveedor_desde_importaciones(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        app(
            CatalogoImportacionService::class
        )->crearProveedorRapido(
            $this->vendedor->id,
            [
                'nombre' =>
                    'Proveedor bloqueado',

                'pais' =>
                    'Estados Unidos',
            ]
        );
    }
}