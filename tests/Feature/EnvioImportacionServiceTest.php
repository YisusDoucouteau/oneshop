<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Services\EnvioImportacionService;
use App\Exceptions\ReglaNegocioException;
use App\Models\EnvioImportacion;
use App\Models\UnidadAdquirida;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\EnvioImportacionUnidad;

class EnvioImportacionServiceTest extends TestCase
{
    use RefreshDatabase;


    private User $usuarioOperativo;


    protected function setUp(): void
    {
        parent::setUp();


        /*
        |--------------------------------------------------------------------------
        | Permiso importaciones
        |--------------------------------------------------------------------------
        */

        $permiso = Permiso::create([
            'codigo' =>
                'importacion.gestionar',

            'nombre' =>
                'Gestionar importaciones',

            'activo' =>
                true,
        ]);


        $rolOperativo = Rol::create([
            'codigo' =>
                'ADMIN_OPERATIVO',

            'nombre' =>
                'Administrador operativo',

            'activo' =>
                true,
        ]);


        $rolOperativo
            ->permisos()
            ->attach(
                $permiso->id
            );


        $this->usuarioOperativo =
            User::factory()->create([
                'activo' =>
                    true,
            ]);


        $this->usuarioOperativo
            ->roles()
            ->attach(
                $rolOperativo->id
            );



        /*
        |--------------------------------------------------------------------------
        | Almacenes
        |--------------------------------------------------------------------------
        */

        Almacen::create([
            'codigo' =>
                'ORURO_PRINCIPAL',

            'nombre' =>
                'Tienda Oruro',

            'ciudad' =>
                'Oruro',

            'principal' =>
                true,

            'activo' =>
                true,
        ]);


        Almacen::create([
            'codigo' =>
                'COCHABAMBA',

            'nombre' =>
                'Depósito Cochabamba',

            'ciudad' =>
                'Cochabamba',

            'principal' =>
                false,

            'activo' =>
                true,
        ]);
    }



    public function test_agrega_unidad_lista_a_envio_borrador(): void
    {

        $usuario =
            $this->usuarioOperativo;


        $envioService =
            app(EnvioImportacionService::class);



        /*
        |--------------------------------------------------------------------------
        | Crear envío borrador
        |--------------------------------------------------------------------------
        */


        $envio =
            $envioService->crearBorrador(
                $usuario->id,
                [

                    'codigo' =>
                        'ENV-001',

                    'cantidad_bultos' =>
                        1,
                ]
            );



        /*
        |--------------------------------------------------------------------------
        | Datos maestros producto
        |--------------------------------------------------------------------------
        */


        $categoria =
            CategoriaProducto::create([
                'codigo' =>
                    'COMPUTADORAS',

                'nombre' =>
                    'Computadoras',

                'activo' =>
                    true,
            ]);



        $marca =
            Marca::create([
                'nombre' =>
                    'Dell',

                'activo' =>
                    true,
            ]);



        $producto =
            Producto::create([

                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'LAP-DELL-001',

                'nombre' =>
                    'Laptop Dell prueba',

                'modelo' =>
                    'Latitude 5420',

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);



        /*
        |--------------------------------------------------------------------------
        | Crear unidad adquirida lista para envío
        |--------------------------------------------------------------------------
        */


        $unidad =
            UnidadAdquirida::create([

                'producto_id' =>
                    $producto->id,


                'almacen_actual_id' =>
                    $envio->almacen_origen_id,


                'estado' =>
                    UnidadAdquirida::ESTADO_LISTA_ENVIO,


                'serial_fabricante' =>
                    'TEST-001',


                'enciende' =>
                    true,


                'tiene_sistema_operativo' =>
                    true,


                'tiene_cargador' =>
                    true,


                'requiere_servicio' =>
                    false,
            ]);



        /*
        |--------------------------------------------------------------------------
        | Agregar unidad al envío
        |--------------------------------------------------------------------------
        */


        $detalle =
            $envioService->agregarUnidad(
                $usuario->id,
                $envio->id,
                $unidad->id
            );



        /*
        |--------------------------------------------------------------------------
        | Validaciones
        |--------------------------------------------------------------------------
        */


        $this->assertDatabaseHas(
            'envios_importacion_unidades',
            [

                'envio_importacion_id' =>
                    $envio->id,


                'unidad_adquirida_id' =>
                    $unidad->id,
            ]
        );


        $this->assertEquals(

            $unidad->id,

            $detalle->unidad_adquirida_id
        );
    }
    public function test_no_permite_agregar_unidad_no_lista_a_envio(): void
{
    $usuario =
        $this->usuarioOperativo;


    $envioService =
        app(EnvioImportacionService::class);



    $envio =
        $envioService->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-002',

                'cantidad_bultos' =>
                    1,
            ]
        );



    $categoria =
        CategoriaProducto::create([
            'codigo' =>
                'COMPUTADORAS',

            'nombre' =>
                'Computadoras',

            'activo' =>
                true,
        ]);



    $producto =
        Producto::create([

            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'LAP-TEST-002',

            'nombre' =>
                'Laptop prueba',

            'modelo' =>
                'Modelo X',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);



    $unidad =
        UnidadAdquirida::create([

            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_EN_REVISION,

            'serial_fabricante' =>
                'TEST-002',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);



    $this->expectException(
    \Illuminate\Validation\ValidationException::class
);


    $envioService->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidad->id
    );
}
public function test_marca_envio_como_despachado_y_envia_unidades(): void
{
    $usuario =
        $this->usuarioOperativo;


    $envioService =
        app(EnvioImportacionService::class);


    $envio =
        $envioService->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-DESP-001',

                'cantidad_bultos' =>
                    1,
            ]
        );


    $producto =
        Producto::create([
            'categoria_producto_id' =>
                CategoriaProducto::create([
                    'codigo' =>
                        'LAPTOP',
                    'nombre' =>
                        'Laptops',
                    'activo' =>
                        true,
                ])->id,

            'codigo' =>
                'LAP-001',

            'nombre' =>
                'Laptop Dell prueba',

            'modelo' =>
                'Latitude 5420',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);


    $unidad =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'SERIAL-DESP-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);


    $envioService->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidad->id
    );


    $envioService->marcarPreparado(
        $usuario->id,
        $envio->id
    );


    $envioDespachado =
        $envioService->marcarDespachado(
            $usuario->id,
            $envio->id,
            [
                'transportista' =>
                    'Transporte prueba',

                'numero_guia' =>
                    'GUIA-001',
            ]
        );


    $this->assertEquals(
        EnvioImportacion::ESTADO_DESPACHADO,
        $envioDespachado->estado
    );


    $this->assertDatabaseHas(
        'envios_importacion',
        [
            'id' =>
                $envio->id,

            'estado' =>
                EnvioImportacion::ESTADO_DESPACHADO,

            'transportista' =>
                'Transporte prueba',

            'numero_guia' =>
                'GUIA-001',
        ]
    );


    $this->assertDatabaseHas(
        'unidades_adquiridas',
        [
            'id' =>
                $unidad->id,

            'estado' =>
                UnidadAdquirida::ESTADO_ENVIADA,
        ]
    );
}
public function test_recibe_unidad_enviada_y_actualiza_estado(): void
{
    $usuario =
        $this->usuarioOperativo;


    $envioService =
        app(EnvioImportacionService::class);


    $envio =
        $envioService->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-REC-001',

                'cantidad_bultos' =>
                    1,
            ]
        );


    $categoria =
        CategoriaProducto::create([
            'codigo' =>
                'LAPTOP',

            'nombre' =>
                'Laptops',

            'activo' =>
                true,
        ]);


    $producto =
        Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'LAP-REC-001',

            'nombre' =>
                'Laptop Dell recepción',

            'modelo' =>
                'Latitude 5420',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);


    $unidad =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'SERIAL-REC-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);


    $envioService->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidad->id
    );


    $envioService->marcarPreparado(
        $usuario->id,
        $envio->id
    );


    $envioService->marcarDespachado(
        $usuario->id,
        $envio->id
    );


    $detalle =
        $envioService->recibirUnidad(
            $usuario->id,
            $envio->id,
            $unidad->id,
            'Recibido sin novedades'
        );


    $this->assertEquals(
        EnvioImportacionUnidad::ESTADO_RECIBIDA,
        $detalle->estado_recepcion
    );


    $this->assertDatabaseHas(
        'unidades_adquiridas',
        [
            'id' =>
                $unidad->id,

            'estado' =>
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO,

            'almacen_actual_id' =>
                $envio->almacen_destino_id,
        ]
    );


    $this->assertDatabaseHas(
        'envios_importacion_unidades',
        [
            'envio_importacion_id' =>
                $envio->id,

            'unidad_adquirida_id' =>
                $unidad->id,

            'estado_recepcion' =>
                EnvioImportacionUnidad::ESTADO_RECIBIDA,
        ]
    );
}
public function test_cierra_envio_como_recibido_cuando_todas_las_unidades_llegan(): void
{
    $usuario =
        $this->usuarioOperativo;

    $servicio =
        app(EnvioImportacionService::class);

    $envio =
        $servicio->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-CIERRE-001',

                'cantidad_bultos' =>
                    1,
            ]
        );

    $categoria =
        CategoriaProducto::create([
            'codigo' =>
                'LAPTOP-CIERRE',

            'nombre' =>
                'Laptops cierre',

            'activo' =>
                true,
        ]);

    $producto =
        Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'LAP-CIERRE-001',

            'nombre' =>
                'Laptop prueba cierre',

            'modelo' =>
                'Cierre Test',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);

    $unidadUno =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'CIERRE-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);

    $unidadDos =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'CIERRE-002',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);

    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadUno->id
    );

    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadDos->id
    );

    $servicio->marcarPreparado(
        $usuario->id,
        $envio->id
    );

    $servicio->marcarDespachado(
        $usuario->id,
        $envio->id
    );

    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadUno->id
    );

    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadDos->id
    );

    $envioCerrado =
        $servicio->cerrarRecepcion(
            $usuario->id,
            $envio->id
        );

    $this->assertEquals(
        EnvioImportacion::ESTADO_RECIBIDO,
        $envioCerrado->estado
    );

    $this->assertEquals(
        $usuario->id,
        $envioCerrado->recibido_por_id
    );

    $this->assertNotNull(
        $envioCerrado->fecha_recepcion
    );

    $this->assertDatabaseHas(
        'envios_importacion',
        [
            'id' =>
                $envio->id,

            'estado' =>
                EnvioImportacion::ESTADO_RECIBIDO,

            'recibido_por_id' =>
                $usuario->id,
        ]
    );
}
public function test_no_permite_cerrar_recepcion_con_unidades_pendientes(): void
{
    $usuario =
        $this->usuarioOperativo;

    $servicio =
        app(EnvioImportacionService::class);

    $envio =
        $servicio->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-PENDIENTE-001',
            ]
        );

    $categoria =
        CategoriaProducto::create([
            'codigo' =>
                'LAPTOP-PENDIENTE',

            'nombre' =>
                'Laptops pendiente',

            'activo' =>
                true,
        ]);

    $producto =
        Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'LAP-PENDIENTE-001',

            'nombre' =>
                'Laptop pendiente recepción',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);

    $unidad =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'PENDIENTE-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);

    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidad->id
    );

    $servicio->marcarPreparado(
        $usuario->id,
        $envio->id
    );

    $servicio->marcarDespachado(
        $usuario->id,
        $envio->id
    );

    try {
        $servicio->cerrarRecepcion(
            $usuario->id,
            $envio->id
        );

        $this->fail(
            'Se esperaba rechazo porque existen unidades pendientes de recepción.'
        );
    } catch (
        ReglaNegocioException $exception
    ) {
        $this->assertStringContainsString(
            'pendientes',
            $exception->getMessage()
        );
    }

    $envio->refresh();

    $this->assertEquals(
        EnvioImportacion::ESTADO_DESPACHADO,
        $envio->estado
    );

    $this->assertNull(
        $envio->fecha_recepcion
    );
}
public function test_cierra_envio_como_recibido_parcial_cuando_existe_unidad_faltante(): void
{
    $usuario =
        $this->usuarioOperativo;

    $servicio =
        app(EnvioImportacionService::class);


    $envio =
        $servicio->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-PARCIAL-001',

                'cantidad_bultos' =>
                    1,
            ]
        );


    $categoria =
        CategoriaProducto::create([
            'codigo' =>
                'LAPTOP-PARCIAL',

            'nombre' =>
                'Laptops recepción parcial',

            'activo' =>
                true,
        ]);


    $producto =
        Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'LAP-PARCIAL-001',

            'nombre' =>
                'Laptop prueba recepción parcial',

            'modelo' =>
                'Latitude Test',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);


    $unidadRecibida =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'PARCIAL-RECIBIDA-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);


    $unidadFaltante =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'PARCIAL-FALTANTE-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);


    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadRecibida->id
    );

    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadFaltante->id
    );


    $servicio->marcarPreparado(
        $usuario->id,
        $envio->id
    );


    $servicio->marcarDespachado(
        $usuario->id,
        $envio->id
    );


    /*
     * La primera unidad llega físicamente a Oruro.
     */
    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadRecibida->id,
        'Unidad recibida correctamente.'
    );


    /*
     * La segunda unidad no llegó dentro del envío.
     */
    $detalleFaltante =
        $servicio->marcarUnidadFaltante(
            $usuario->id,
            $envio->id,
            $unidadFaltante->id,
            'La unidad no fue encontrada al verificar el contenido del envío.'
        );


    /*
     * Una vez revisadas todas las unidades,
     * se cierra la recepción.
     */
    $envioCerrado =
        $servicio->cerrarRecepcion(
            $usuario->id,
            $envio->id
        );


    $this->assertEquals(
        EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
        $envioCerrado->estado
    );


    $this->assertNull(
        $envioCerrado->fecha_recepcion
    );


    $this->assertNull(
        $envioCerrado->recibido_por_id
    );


    $this->assertEquals(
        EnvioImportacionUnidad::ESTADO_FALTANTE,
        $detalleFaltante->estado_recepcion
    );


    $this->assertNull(
        $detalleFaltante->fecha_recepcion
    );


    $this->assertDatabaseHas(
        'unidades_adquiridas',
        [
            'id' =>
                $unidadRecibida->id,

            'estado' =>
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO,

            'almacen_actual_id' =>
                $envio->almacen_destino_id,
        ]
    );


    /*
     * La faltante NO debe aparecer físicamente
     * en Oruro.
     */
    $this->assertDatabaseHas(
        'unidades_adquiridas',
        [
            'id' =>
                $unidadFaltante->id,

            'estado' =>
                UnidadAdquirida::ESTADO_ENVIADA,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,
        ]
    );


    $this->assertDatabaseHas(
        'envios_importacion_unidades',
        [
            'envio_importacion_id' =>
                $envio->id,

            'unidad_adquirida_id' =>
                $unidadFaltante->id,

            'estado_recepcion' =>
                EnvioImportacionUnidad::ESTADO_FALTANTE,
        ]
    );
}
}