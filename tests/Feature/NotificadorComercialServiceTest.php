<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\User;
use App\Services\NotificadorComercialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificadorComercialServiceTest extends TestCase
{
    use RefreshDatabase;


    public function test_crea_notificacion_comercial_interna(): void
    {

        $usuario = User::create([

            'name' =>
                'Usuario Test',

            'email' =>
                'usuario-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,

        ]);



        $servicio = app(
            NotificadorComercialService::class
        );


        $notificacion = $servicio->crear(

            $usuario->id,

            'APROBACION_PRECIO',

            'Solicitud aprobada',

            'El precio especial solicitado fue autorizado.',

            'SolicitudAprobacionPrecio',

            1

        );



        $this->assertDatabaseHas(

            'notificacions',

            [

                'id' =>
                    $notificacion->id,

                'usuario_id' =>
                    $usuario->id,

                'tipo' =>
                    'APROBACION_PRECIO',

                'canal' =>
                    'INTERNO',

                'leido' =>
                    false,

            ]

        );

    }



    public function test_marca_notificacion_como_leida(): void
    {

        $usuario = User::create([

            'name' =>
                'Usuario Test',

            'email' =>
                'usuario-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,

        ]);



        $notificacion = Notificacion::create([

            'usuario_id' =>
                $usuario->id,

            'tipo' =>
                'SOLICITUD_APROBACION_PRECIO',

            'titulo' =>
                'Nueva solicitud',

            'mensaje' =>
                'Existe una solicitud pendiente.',

            'canal' =>
                'INTERNO',

            'leido' =>
                false,

        ]);



        $servicio = app(
            NotificadorComercialService::class
        );


        $servicio->marcarComoLeida(
            $notificacion
        );



        $this->assertDatabaseHas(

            'notificacions',

            [

                'id' =>
                    $notificacion->id,

                'leido' =>
                    true,

            ]

        );

    }
}