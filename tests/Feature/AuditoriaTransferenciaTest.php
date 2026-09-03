<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Almacen;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\User;
use App\Models\Rol;
use App\Models\Transferencia;
use App\Models\Permiso;
use App\Services\TransferenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditoriaTransferenciaTest extends TestCase
{
    use RefreshDatabase;


    protected User $usuario;


    protected function setUp(): void
    {
        parent::setUp();


        $this->usuario = User::create([

            'name' =>
                'Usuario auditoria',

            'email' =>
                Str::uuid().'@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,

        ]);


        $rol = Rol::create([

            'codigo' =>
                'TEST_AUDITORIA',

            'nombre' =>
                'Rol auditoria prueba',

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



    public function test_crear_transferencia_registra_auditoria()
    {

        $almacenOrigen = Almacen::create([

            'codigo' =>
                'ORU',

            'nombre' =>
                'Almacen Oruro',

            'ciudad' =>
                'Oruro',

            'activo' =>
                true,

        ]);


        $almacenDestino = Almacen::create([

            'codigo' =>
                'CBBA',

            'nombre' =>
                'Almacen Cochabamba',

            'ciudad' =>
                'Cochabamba',

            'activo' =>
                true,

        ]);


        $categoria = CategoriaProducto::create([

            'codigo' =>
                'CAT-TEST',

            'nombre' =>
                'Categoria prueba',

            'activo' =>
                true,

        ]);


        $producto = Producto::create([

    'categoria_producto_id' =>
        $categoria->id,

    'codigo' =>
        'PROD-TEST',

    'nombre' =>
        'Laptop prueba',

    'activo' =>
        true,

]);


        $estado = EstadoEquipo::where(
            'codigo',
            'DISPONIBLE'
        )->first();


        if (!$estado) {

            $estado = EstadoEquipo::create([

                'codigo' =>
                    'DISPONIBLE',

                'nombre' =>
                    'Disponible',

                'activo' =>
                    true,

            ]);
        }


        $equipo = Equipo::create([

            'producto_id' =>
                $producto->id,

            'almacen_actual_id' =>
                $almacenOrigen->id,

            'estado_actual_id' =>
                $estado->id,

            'codigo_interno' =>
                'EQ-TEST',

            'fecha_registro' =>
                now(),

            'activo' =>
                true,

        ]);


        $transferencia = app(
            TransferenciaService::class
        )->crearTransferencia(

            $this->usuario->id,

            $almacenOrigen->id,

            $almacenDestino->id,

            [
                $equipo->id
            ]

        );


        $auditoria = Auditoria::where(
            'accion',
            'CREAR_TRANSFERENCIA'
        )
        ->where(
            'entidad_id',
            $transferencia->id
        )
        ->first();


        $this->assertNotNull(
            $auditoria
        );


        $this->assertEquals(
            'Transferencia',
            $auditoria->entidad
        );


        $this->assertEquals(
            $this->usuario->id,
            $auditoria->usuario_id
        );

    }
    public function test_despachar_transferencia_registra_auditoria()
{
    $transferencia = $this->crearTransferenciaPrueba();


    app(TransferenciaService::class)
        ->despacharTransferencia(
            $transferencia->id,
            $this->usuario->id
        );


    $auditoria = Auditoria::where(
        'accion',
        'DESPACHAR_TRANSFERENCIA'
    )
    ->where(
        'entidad_id',
        $transferencia->id
    )
    ->first();


    $this->assertNotNull($auditoria);


    $this->assertEquals(
        'Transferencia',
        $auditoria->entidad
    );


    $this->assertEquals(
        $this->usuario->id,
        $auditoria->usuario_id
    );
}



public function test_recibir_transferencia_registra_auditoria()
{
    $transferencia = $this->crearTransferenciaPrueba();


    app(TransferenciaService::class)
        ->despacharTransferencia(
            $transferencia->id,
            $this->usuario->id
        );


    app(TransferenciaService::class)
        ->recibirTransferencia(
            $transferencia->id,
            $this->usuario->id
        );


    $auditoria = Auditoria::where(
        'accion',
        'RECIBIR_TRANSFERENCIA'
    )
    ->where(
        'entidad_id',
        $transferencia->id
    )
    ->first();


    $this->assertNotNull($auditoria);


    $this->assertEquals(
        'Transferencia',
        $auditoria->entidad
    );


    $this->assertEquals(
        $this->usuario->id,
        $auditoria->usuario_id
    );
}
private function crearTransferenciaPrueba(): Transferencia
{
    $almacenOrigen = Almacen::create([

        'codigo' =>
            'ORU-' . Str::uuid(),

        'nombre' =>
            'Oruro',

        'ciudad' =>
            'Oruro',

        'activo' =>
            true,

    ]);


    $almacenDestino = Almacen::create([

        'codigo' =>
            'CBBA-' . Str::uuid(),

        'nombre' =>
            'Cochabamba',

        'ciudad' =>
            'Cochabamba',

        'activo' =>
            true,

    ]);


    $categoria = CategoriaProducto::create([

        'codigo' =>
            'CAT-' . Str::uuid(),

        'nombre' =>
            'Categoria prueba',

        'activo' =>
            true,

    ]);


    $producto = Producto::create([

        'categoria_producto_id' =>
            $categoria->id,

        'codigo' =>
            'PROD-' . Str::uuid(),

        'nombre' =>
            'Equipo prueba',

        'activo' =>
            true,

    ]);


    $estado = EstadoEquipo::where(
        'codigo',
        'DISPONIBLE'
    )->first();


    if (!$estado) {

        $estado = EstadoEquipo::create([

            'codigo' =>
                'DISPONIBLE',

            'nombre' =>
                'Disponible',

            'descripcion' =>
                'Equipo disponible para transferencia',

            'es_final' =>
                false,

            'orden' =>
                1,

            'activo' =>
                true,

        ]);
    }


    $equipo = Equipo::create([

        'producto_id' =>
            $producto->id,

        'almacen_actual_id' =>
            $almacenOrigen->id,

        'estado_actual_id' =>
            $estado->id,

        'codigo_interno' =>
            'EQ-' . Str::uuid(),

        'fecha_registro' =>
            now(),

        'activo' =>
            true,

    ]);


    return app(TransferenciaService::class)
        ->crearTransferencia(

            $this->usuario->id,

            $almacenOrigen->id,

            $almacenDestino->id,

            [
                $equipo->id
            ]

        );
}
}