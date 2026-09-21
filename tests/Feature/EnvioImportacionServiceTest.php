<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\EventoLogisticoLote;
use App\Models\TipoEventoLogistico;
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

    $this->seed();

    /*
    |--------------------------------------------------------------------------
    | Asegurar catálogo de eventos logísticos
    |--------------------------------------------------------------------------
    */

    $this->seed(
        \Database\Seeders\TipoEventoLogisticoSeeder::class
    );


    /*
    |--------------------------------------------------------------------------
    | Permiso importaciones
    |--------------------------------------------------------------------------
    |
    | Ya existe desde SeguridadSeeder.
    | No volver a crear porque provoca duplicados.
    |
    */

    $permiso = Permiso::where(
        'codigo',
        'importacion.gestionar'
    )->firstOrFail();



    /*
    |--------------------------------------------------------------------------
    | Rol operativo
    |--------------------------------------------------------------------------
    |
    | Ya existe desde SeguridadSeeder.
    |
    */

    $rolOperativo = Rol::where(
        'codigo',
        'ADMINISTRADOR'
    )->firstOrFail();



    /*
    |--------------------------------------------------------------------------
    | Relacionar permiso si todavía no existe
    |--------------------------------------------------------------------------
    */

    $rolOperativo
        ->permisos()
        ->syncWithoutDetaching([
            $permiso->id
        ]);



    /*
    |--------------------------------------------------------------------------
    | Usuario operativo de prueba
    |--------------------------------------------------------------------------
    */

    $this->usuarioOperativo =
        User::factory()->create([
            'activo' => true,
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

    Almacen::firstOrCreate(
    [
        'codigo' => 'ORURO_PRINCIPAL'
    ],
    [
        'nombre' => 'Tienda Oruro',
        'ciudad' => 'Oruro',
        'principal' => true,
        'activo' => true,
    ]
);


Almacen::firstOrCreate(
    [
        'codigo' => 'COCHABAMBA'
    ],
    [
        'nombre' => 'Depósito Cochabamba',
        'ciudad' => 'Cochabamba',
        'principal' => false,
        'activo' => true,
    ]
);
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


    $categoria =
        CategoriaProducto::firstOrCreate(
            [
                'codigo' =>
                    'LAPTOP',
            ],
            [
                'nombre' =>
                    'Laptops',

                'activo' =>
                    true,
            ]
        );


    $producto =
        Producto::create([

            'categoria_producto_id' =>
                $categoria->id,

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
        CategoriaProducto::firstOrCreate(
            [
                'codigo' =>
                    'LAPTOP',
            ],
            [
                'nombre' =>
                    'Laptops',

                'activo' =>
                    true,
            ]
        );


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
            'Recibido sin novedades',
            true
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

    /*
     * Fase 5.2: antes de cerrar la recepción debe existir
     * una verificación física general del manifiesto.
     */
    $envio->refresh();

    $servicio->registrarVerificacionRecepcion(
        $usuario->id,
        $envio->id,
        [
            'cantidad_bultos_recibidos' =>
                (int) ($envio->cantidad_bultos ?? 0),

            'cantidad_cargadores_adicionales_recibidos' =>
                (int) ($envio->cantidad_cargadores ?? 0),

            'cantidad_accesorios_recibidos' =>
                (int) ($envio->cantidad_accesorios ?? 0),

            'observacion_recepcion_general' =>
                null,
        ]
    );


    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadUno->id,
        null,
        true
    );

    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadDos->id,
        null,
        true
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

    /*
     * Fase 5.2: antes de cerrar la recepción debe existir
     * una verificación física general del manifiesto.
     */
    $envio->refresh();

    $servicio->registrarVerificacionRecepcion(
        $usuario->id,
        $envio->id,
        [
            'cantidad_bultos_recibidos' =>
                (int) ($envio->cantidad_bultos ?? 0),

            'cantidad_cargadores_adicionales_recibidos' =>
                (int) ($envio->cantidad_cargadores ?? 0),

            'cantidad_accesorios_recibidos' =>
                (int) ($envio->cantidad_accesorios ?? 0),

            'observacion_recepcion_general' =>
                null,
        ]
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
     * Fase 5.2: antes de cerrar la recepción debe existir
     * una verificación física general del manifiesto.
     */
    $envio->refresh();

    $servicio->registrarVerificacionRecepcion(
        $usuario->id,
        $envio->id,
        [
            'cantidad_bultos_recibidos' =>
                (int) ($envio->cantidad_bultos ?? 0),

            'cantidad_cargadores_adicionales_recibidos' =>
                (int) ($envio->cantidad_cargadores ?? 0),

            'cantidad_accesorios_recibidos' =>
                (int) ($envio->cantidad_accesorios ?? 0),

            'observacion_recepcion_general' =>
                null,
        ]
    );



    /*
     * La primera unidad llega físicamente a Oruro.
     */
    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadRecibida->id,
        'Unidad recibida correctamente.',
        true
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
public function test_recibe_unidad_faltante_tardiamente_y_completa_el_envio(): void
{
    $usuario =
        $this->usuarioOperativo;

    $servicio =
        app(EnvioImportacionService::class);


    /*
     * ---------------------------------------------------------
     * 1. Crear envío
     * ---------------------------------------------------------
     */
    $envio =
        $servicio->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-TARDIO-001',

                'cantidad_bultos' =>
                    1,
            ]
        );


    /*
     * ---------------------------------------------------------
     * 2. Crear producto de prueba
     * ---------------------------------------------------------
     */
    $categoria =
        CategoriaProducto::create([
            'codigo' =>
                'LAPTOP-TARDIO',

            'nombre' =>
                'Laptops recepción tardía',

            'activo' =>
                true,
        ]);


    $producto =
        Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'LAP-TARDIO-001',

            'nombre' =>
                'Laptop prueba recepción tardía',

            'modelo' =>
                'Latitude Tardio Test',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);


    /*
     * ---------------------------------------------------------
     * 3. Crear dos unidades físicas
     * ---------------------------------------------------------
     */
    $unidadRecibida =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'TARDIO-RECIBIDA-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);


    $unidadTardia =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'TARDIO-FALTANTE-001',

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
     * ---------------------------------------------------------
     * 4. Agregar ambas unidades al envío
     * ---------------------------------------------------------
     */
    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadRecibida->id
    );

    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadTardia->id
    );


    /*
     * ---------------------------------------------------------
     * 5. Preparar y despachar
     * ---------------------------------------------------------
     */
    $servicio->marcarPreparado(
        $usuario->id,
        $envio->id
    );

    $servicio->marcarDespachado(
        $usuario->id,
        $envio->id
    );

    /*
     * Fase 5.2: antes de cerrar la recepción debe existir
     * una verificación física general del manifiesto.
     */
    $envio->refresh();

    $servicio->registrarVerificacionRecepcion(
        $usuario->id,
        $envio->id,
        [
            'cantidad_bultos_recibidos' =>
                (int) ($envio->cantidad_bultos ?? 0),

            'cantidad_cargadores_adicionales_recibidos' =>
                (int) ($envio->cantidad_cargadores ?? 0),

            'cantidad_accesorios_recibidos' =>
                (int) ($envio->cantidad_accesorios ?? 0),

            'observacion_recepcion_general' =>
                null,
        ]
    );



    /*
     * ---------------------------------------------------------
     * 6. Una unidad llega normalmente
     * ---------------------------------------------------------
     */
    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadRecibida->id,
        'Unidad recibida correctamente.',
        true
    );


    /*
     * ---------------------------------------------------------
     * 7. La segunda unidad se declara faltante
     * ---------------------------------------------------------
     */
    $servicio->marcarUnidadFaltante(
        $usuario->id,
        $envio->id,
        $unidadTardia->id,
        'La unidad no fue encontrada durante la recepción inicial.'
    );


    /*
     * ---------------------------------------------------------
     * 8. Cerrar inicialmente como RECIBIDO_PARCIAL
     * ---------------------------------------------------------
     */
    $envioParcial =
        $servicio->cerrarRecepcion(
            $usuario->id,
            $envio->id
        );


    $this->assertEquals(
        EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
        $envioParcial->estado
    );


    /*
     * ---------------------------------------------------------
     * 9. La unidad faltante aparece posteriormente
     * ---------------------------------------------------------
     */
    $detalleTardio =
        $servicio->recibirUnidad(
            $usuario->id,
            $envio->id,
            $unidadTardia->id,
            'La transportadora entregó la unidad posteriormente.',
            true
        );


    /*
     * El detalle debe pasar:
     *
     * FALTANTE -> RECIBIDA
     */
    $this->assertEquals(
        EnvioImportacionUnidad::ESTADO_RECIBIDA,
        $detalleTardio->estado_recepcion
    );


    $this->assertNotNull(
        $detalleTardio->fecha_recepcion
    );


    $this->assertEquals(
        $usuario->id,
        $detalleTardio->recibido_por_id
    );


    /*
     * ---------------------------------------------------------
     * 10. Debe conservarse la historia del faltante
     * ---------------------------------------------------------
     */
    $this->assertStringContainsString(
        'La unidad no fue encontrada durante la recepción inicial.',
        $detalleTardio->observacion_recepcion
    );


    $this->assertStringContainsString(
        '[RECEPCIÓN TARDÍA]',
        $detalleTardio->observacion_recepcion
    );


    $this->assertStringContainsString(
        'La transportadora entregó la unidad posteriormente.',
        $detalleTardio->observacion_recepcion
    );


    /*
     * ---------------------------------------------------------
     * 11. La unidad debe estar físicamente en Oruro
     * ---------------------------------------------------------
     */
    $this->assertDatabaseHas(
        'unidades_adquiridas',
        [
            'id' =>
                $unidadTardia->id,

            'estado' =>
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO,

            'almacen_actual_id' =>
                $envio->almacen_destino_id,
        ]
    );


    /*
     * ---------------------------------------------------------
     * 12. Cerrar nuevamente la recepción
     * ---------------------------------------------------------
     */
    $envioCompleto =
        $servicio->cerrarRecepcion(
            $usuario->id,
            $envio->id
        );


    /*
     * Ahora las dos unidades llegaron.
     *
     * RECIBIDO_PARCIAL -> RECIBIDO
     */
    $this->assertEquals(
        EnvioImportacion::ESTADO_RECIBIDO,
        $envioCompleto->estado
    );


    $this->assertEquals(
        $usuario->id,
        $envioCompleto->recibido_por_id
    );


    $this->assertNotNull(
        $envioCompleto->fecha_recepcion
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


    $this->assertDatabaseHas(
        'envios_importacion_unidades',
        [
            'envio_importacion_id' =>
                $envio->id,

            'unidad_adquirida_id' =>
                $unidadTardia->id,

            'estado_recepcion' =>
                EnvioImportacionUnidad::ESTADO_RECIBIDA,
        ]
    );
}
public function test_registra_incidencia_de_recepcion_y_cierra_envio_como_parcial(): void
{
    $usuario =
        $this->usuarioOperativo;

    $servicio =
        app(EnvioImportacionService::class);


    /*
     * ---------------------------------------------------------
     * 1. Crear envío
     * ---------------------------------------------------------
     */
    $envio =
        $servicio->crearBorrador(
            $usuario->id,
            [
                'codigo' =>
                    'ENV-INCIDENCIA-001',

                'cantidad_bultos' =>
                    1,
            ]
        );


    /*
     * ---------------------------------------------------------
     * 2. Crear producto
     * ---------------------------------------------------------
     */
    $categoria =
        CategoriaProducto::create([
            'codigo' =>
                'LAPTOP-INCIDENCIA',

            'nombre' =>
                'Laptops incidencia recepción',

            'activo' =>
                true,
        ]);


    $producto =
        Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'codigo' =>
                'LAP-INCIDENCIA-001',

            'nombre' =>
                'Laptop prueba incidencia',

            'modelo' =>
                'Latitude Incidencia Test',

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);


    /*
     * ---------------------------------------------------------
     * 3. Crear dos unidades
     * ---------------------------------------------------------
     */
    $unidadNormal =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'INC-NORMAL-001',

            'enciende' =>
                true,

            'tiene_sistema_operativo' =>
                true,

            'tiene_cargador' =>
                true,

            'requiere_servicio' =>
                false,
        ]);


    $unidadConIncidencia =
        UnidadAdquirida::create([
            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $envio->almacen_origen_id,

            'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

            'serial_fabricante' =>
                'INC-GOLPE-001',

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
     * ---------------------------------------------------------
     * 4. Agregar unidades al envío
     * ---------------------------------------------------------
     */
    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadNormal->id
    );

    $servicio->agregarUnidad(
        $usuario->id,
        $envio->id,
        $unidadConIncidencia->id
    );


    /*
     * ---------------------------------------------------------
     * 5. Preparar y despachar
     * ---------------------------------------------------------
     */
    $servicio->marcarPreparado(
        $usuario->id,
        $envio->id
    );

    $servicio->marcarDespachado(
        $usuario->id,
        $envio->id
    );

    /*
     * Fase 5.2: antes de cerrar la recepción debe existir
     * una verificación física general del manifiesto.
     */
    $envio->refresh();

    $servicio->registrarVerificacionRecepcion(
        $usuario->id,
        $envio->id,
        [
            'cantidad_bultos_recibidos' =>
                (int) ($envio->cantidad_bultos ?? 0),

            'cantidad_cargadores_adicionales_recibidos' =>
                (int) ($envio->cantidad_cargadores ?? 0),

            'cantidad_accesorios_recibidos' =>
                (int) ($envio->cantidad_accesorios ?? 0),

            'observacion_recepcion_general' =>
                null,
        ]
    );



    /*
     * ---------------------------------------------------------
     * 6. Primera unidad: recepción normal
     * ---------------------------------------------------------
     */
    $servicio->recibirUnidad(
        $usuario->id,
        $envio->id,
        $unidadNormal->id,
        'Unidad recibida correctamente.',
        true
    );


    /*
     * ---------------------------------------------------------
     * 7. Segunda unidad: llegó, pero con incidencia
     * ---------------------------------------------------------
     */
    $detalleIncidencia =
        $servicio->registrarIncidenciaRecepcion(
            $usuario->id,
            $envio->id,
            $unidadConIncidencia->id,
            'El equipo llegó con un golpe visible en la carcasa.'
        );


    /*
     * El detalle debe quedar como INCIDENCIA.
     */
    $this->assertEquals(
        EnvioImportacionUnidad::ESTADO_INCIDENCIA,
        $detalleIncidencia->estado_recepcion
    );


    /*
     * Como físicamente sí llegó,
     * debe existir fecha de recepción.
     */
    $this->assertNotNull(
        $detalleIncidencia->fecha_recepcion
    );


    $this->assertEquals(
        $usuario->id,
        $detalleIncidencia->recibido_por_id
    );


    $this->assertStringContainsString(
        'golpe visible',
        $detalleIncidencia->observacion_recepcion
    );


    /*
     * ---------------------------------------------------------
     * 8. La unidad sí debe estar físicamente en Oruro
     * ---------------------------------------------------------
     */
    $this->assertDatabaseHas(
        'unidades_adquiridas',
        [
            'id' =>
                $unidadConIncidencia->id,

            'estado' =>
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO,

            'almacen_actual_id' =>
                $envio->almacen_destino_id,
        ]
    );


    /*
     * ---------------------------------------------------------
     * 9. Cerrar recepción
     * ---------------------------------------------------------
     */
    $envioParcial =
        $servicio->cerrarRecepcion(
            $usuario->id,
            $envio->id
        );


    /*
     * Aunque ambas unidades llegaron físicamente,
     * una presenta incidencia.
     *
     * El envío todavía NO está completamente resuelto.
     */
    $this->assertEquals(
        EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
        $envioParcial->estado
    );


    /*
     * Los campos generales de recepción completa
     * deben continuar vacíos.
     */
    $this->assertNull(
        $envioParcial->fecha_recepcion
    );


    $this->assertNull(
        $envioParcial->recibido_por_id
    );


    /*
     * ---------------------------------------------------------
     * 10. Verificar detalle en base de datos
     * ---------------------------------------------------------
     */
    $this->assertDatabaseHas(
        'envios_importacion_unidades',
        [
            'envio_importacion_id' =>
                $envio->id,

            'unidad_adquirida_id' =>
                $unidadConIncidencia->id,

            'estado_recepcion' =>
                EnvioImportacionUnidad::ESTADO_INCIDENCIA,

            'recibido_por_id' =>
                $usuario->id,
        ]
    );


    $this->assertDatabaseHas(
        'envios_importacion',
        [
            'id' =>
                $envio->id,

            'estado' =>
                EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
        ]
    );
}
}