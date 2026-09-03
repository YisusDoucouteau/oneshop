<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\User;
use App\Models\Rol;
use App\Models\Permiso;
use App\Services\TransferenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransferenciaServiceTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;

    protected function setUp(): void
{
    parent::setUp();


    $this->usuario = User::create([

        'name' => 'Usuario transferencia',

        'email' =>
            Str::uuid().'@test.com',

        'password' =>
            bcrypt('123456'),

        'activo' => true,

    ]);


    $rol = Rol::create([

        'codigo' =>
            'TEST_TRANSFERENCIAS',

        'nombre' =>
            'Gestor transferencias prueba',

        'descripcion' =>
            'Rol para pruebas',

        'activo' =>
            true,

    ]);


    $permiso = Permiso::where(
        'codigo',
        'transferencias.gestionar'
    )->first();


    if (!$permiso) {

        $permiso = Permiso::create([

            'codigo' =>
                'transferencias.gestionar',

            'nombre' =>
                'Gestionar transferencias',

            'descripcion' =>
                'Permite gestionar transferencias',

            'activo' =>
                true,

        ]);

    }


    $rol->permisos()->attach(
        $permiso->id
    );


    $this->usuario->roles()->attach(
        $rol->id
    );
}



    public function test_crea_transferencia_con_equipo_disponible(): void
    {

        $origen = $this->crearAlmacen('ORU');

        $destino = $this->crearAlmacen('CBBA');


        $equipo = $this->crearEquipo(
            $origen
        );


        $transferencia =
            app(TransferenciaService::class)
                ->crearTransferencia(

                    usuarioId:
                        $this->usuario->id,

                    almacenOrigenId:
                        $origen->id,

                    almacenDestinoId:
                        $destino->id,

                    equiposIds:
                        [$equipo->id]

                );


        $this->assertEquals(
            'SOLICITADA',
            $transferencia->estado
        );


        $this->assertDatabaseHas(
            'transferencias_equipos',
            [
                'transferencia_id' =>
                    $transferencia->id,

                'equipo_id' =>
                    $equipo->id,
            ]
        );
    }




    public function test_no_permite_transferir_equipo_vendido(): void
    {

        $origen = $this->crearAlmacen('ORU');

        $destino = $this->crearAlmacen('CBBA');


        $estadoVendido =
            EstadoEquipo::create([

                'codigo'=>'VENDIDO',

                'nombre'=>'Vendido',

                'activo'=>true,

            ]);


        $equipo =
            $this->crearEquipo(
                $origen,
                $estadoVendido
            );



        $this->expectException(
            ReglaNegocioException::class
        );


        app(TransferenciaService::class)
            ->crearTransferencia(

                $this->usuario->id,

                $origen->id,

                $destino->id,

                [$equipo->id]

            );
    }





    public function test_recibir_transferencia_mueve_equipo_de_almacen(): void
    {

        $origen =
            $this->crearAlmacen('ORU');


        $destino =
            $this->crearAlmacen('CBBA');


        $equipo =
            $this->crearEquipo(
                $origen
            );


        $service =
            app(TransferenciaService::class);



        $transferencia =
            $service->crearTransferencia(

                $this->usuario->id,

                $origen->id,

                $destino->id,

                [$equipo->id]

            );



        $service->despacharTransferencia(

            $transferencia->id,

            $this->usuario->id

        );



        $service->recibirTransferencia(

            $transferencia->id,

            $this->usuario->id

        );



        $equipo->refresh();


        $this->assertEquals(

            $destino->id,

            $equipo->almacen_actual_id

        );


        $this->assertDatabaseHas(

            'transferencias',

            [

                'id'=>$transferencia->id,

                'estado'=>'RECIBIDA'

            ]

        );
    }





    private function crearAlmacen(
        string $codigo
    ): Almacen {

        return Almacen::create([

            'codigo'=>$codigo,

            'nombre'=>'Almacen '.$codigo,

            'ciudad'=>$codigo,

            'principal'=>false,

            'activo'=>true,

        ]);
    }





    private function crearEquipo(
        Almacen $almacen,
        ?EstadoEquipo $estado = null
    ): Equipo {


        $categoria =
            CategoriaProducto::create([

                'codigo'=>'LAP',

                'nombre'=>'Laptop',

                'activo'=>true,

            ]);



        $producto =
            Producto::create([

                'categoria_producto_id'=>
                    $categoria->id,

                'codigo'=>
                    'PROD-'.Str::uuid(),

                'nombre'=>
                    'Laptop prueba',

                'modelo'=>
                    'TEST',

                'es_serializado'=>true,

                'activo'=>true,

            ]);



        if (!$estado) {

            $estado =
                EstadoEquipo::create([

                    'codigo'=>'DISPONIBLE',

                    'nombre'=>'Disponible',

                    'activo'=>true,

                ]);

        }



        return Equipo::create([

            'producto_id'=>
                $producto->id,

            'almacen_actual_id'=>
                $almacen->id,

            'estado_actual_id'=>
                $estado->id,

            'codigo_interno'=>
                'EQ-'.Str::uuid(),

            'activo'=>true,

        ]);
    }
}