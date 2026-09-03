<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Garantia;
use App\Models\Venta;
use App\Services\CasoGarantiaService;
use App\Services\VentaService;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\EstadoEquipo;
use App\Models\PrecioEquipo;
use App\Models\ParametroSistema;
use App\Models\PoliticaGarantia;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CasoGarantiaServiceTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;
    protected Cliente $cliente;



    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            CatalogoSeeder::class
        );


        $this->usuario = User::create([

            'name' => 'Usuario garantía',

            'email' =>
                Str::uuid().'@test.com',

            'password' =>
                'password',

            'activo' =>
                true,

        ]);



        $this->cliente = Cliente::create([

            'nombre_completo' =>
                'Cliente garantía',

            'telefono' =>
                '70000001',

            'activo' =>
                true,

        ]);

    }



    public function test_abre_caso_de_garantia(): void
    {

        $garantia =
            $this->crearGarantia();


        $caso =
            app(CasoGarantiaService::class)
                ->abrirCaso(

                    garantiaId:
                        $garantia->id,

                    usuarioId:
                        $this->usuario->id,

                    motivoCliente:
                        'El equipo no enciende.'

                );



        $this->assertDatabaseHas(
            'casos_garantia',
            [

                'garantia_id' =>
                    $garantia->id,

                'estado' =>
                    'ABIERTO',

            ]
        );


        $this->assertNotNull(
            $caso->numero
        );

    }




    public function test_registra_diagnostico_intervencion_y_cierra_caso(): void
    {

        $garantia =
            $this->crearGarantia();



        $service =
            app(CasoGarantiaService::class);



        $caso =
            $service->abrirCaso(

                $garantia->id,

                $this->usuario->id,

                'Problema de funcionamiento'

            );



        $service->registrarDiagnostico(

            $caso->id,

            'Falla en placa principal.'

        );



        $service->registrarIntervencion(

            $caso->id,

            $this->usuario->id,

            'REPARACION',

            'Cambio de componente interno',

            'Equipo operativo'

        );



        $cerrado =
            $service->cerrarCaso(

                $caso->id,

                $this->usuario->id,

                'REPARADO'

            );



        $this->assertEquals(

            'CERRADO',

            $cerrado->estado

        );



        $this->assertDatabaseCount(

            'intervenciones_garantia',

            1

        );

    }




    private function crearGarantia(): Garantia
    {

        $categoria =
            CategoriaProducto::where(
                'codigo',
                'LAPTOP'
            )->firstOrFail();



        $almacen =
            Almacen::where(
                'activo',
                true
            )->firstOrFail();



        $estado =
            EstadoEquipo::where(
                'codigo',
                'DISPONIBLE'
            )->firstOrFail();



        $producto =
            Producto::create([

                'categoria_producto_id' =>
                    $categoria->id,

                'codigo' =>
                    'TEST-'.Str::uuid(),

                'nombre' =>
                    'Laptop garantía',

                'modelo' =>
                    'TEST',

                'es_serializado' =>
                    true,

                'activo' =>
                    true,

            ]);



        $equipo =
            Equipo::create([

                'producto_id' =>
                    $producto->id,

                'almacen_actual_id' =>
                    $almacen->id,

                'estado_actual_id' =>
                    $estado->id,

                'codigo_interno' =>
                    'EQ-'.Str::uuid(),

                'fecha_registro' =>
                    now(),

                'activo' =>
                    true,

            ]);



        $venta =
            Venta::create([

                'numero' =>
                    'VEN-'.Str::uuid(),

                'vendedor_id' =>
                    $this->usuario->id,

                'cliente_id' =>
                    $this->cliente->id,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    3500,

                'descuento_total' =>
                    0,

                'total' =>
                    3500,

                'estado' =>
                    'REGISTRADA',

            ]);



        $detalle =
            \App\Models\DetalleVenta::create([

                'venta_id' =>
                    $venta->id,

                'producto_id' =>
                    $producto->id,

                'equipo_id' =>
                    $equipo->id,

                'cantidad' =>
                    1,

                'precio_lista_snapshot' =>
                    3500,

                'descuento_unitario' =>
                    0,

                'precio_unitario' =>
                    3500,

                'subtotal' =>
                    3500,

            ]);

$politica = PoliticaGarantia::create([

    'codigo' =>
        'GAR-' . Str::uuid(),

    'nombre' =>
        'Garantía estándar laptops',

    'categoria_producto_id' =>
        $categoria->id,

    'producto_id' =>
        $producto->id,

    'duracion_meses' =>
        6,

    'condiciones' =>
        'Garantía por fallas de fabricación.',

    'exclusiones' =>
        'Golpes, humedad y daños físicos.',

    'vigente_desde' =>
        now()->subDay(),

    'vigente_hasta' =>
        null,

    'activo' =>
        true,

]);

        return Garantia::create([

            'numero' =>
                'GAR-'.Str::uuid(),

            'detalle_venta_id' =>
                $detalle->id,

            'politica_garantia_id' =>
                $politica->id,
            'fecha_inicio' =>
                now(),

            'fecha_fin' =>
                now()->addMonths(6),

            'duracion_meses_snapshot' =>
                6,

            'condiciones_snapshot' =>
                'Garantía estándar',

            'estado' =>
                'VIGENTE',

        ]);

    }

}