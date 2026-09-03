<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditoriaServiceTest extends TestCase
{
    use RefreshDatabase;


    public function test_registra_auditoria_con_usuario_y_datos(): void
    {
        $usuario = User::create([

            'name' =>
                'Usuario auditoria',

            'email' =>
                Str::uuid() . '@oneshop.test',

            'password' =>
                'password',

            'activo' =>
                true,

        ]);


        $auditoria = app(AuditoriaService::class)
            ->registrar(

                usuarioId: $usuario->id,

                accion: 'CREAR',

                entidad: 'Venta',

                entidadId: 100,

                datosAnteriores: null,

                datosNuevos: [

                    'estado' =>
                        'REGISTRADA',

                    'total' =>
                        3500,

                ]

            );


        $this->assertInstanceOf(
            Auditoria::class,
            $auditoria
        );


        $this->assertEquals(
            $usuario->id,
            $auditoria->usuario_id
        );


        $this->assertEquals(
            'CREAR',
            $auditoria->accion
        );


        $this->assertEquals(
            'Venta',
            $auditoria->entidad
        );


        $this->assertEquals(
            3500,
            $auditoria->datos_nuevos['total']
        );


        $this->assertDatabaseHas(
            'auditorias',
            [
                'accion' =>
                    'CREAR',

                'entidad' =>
                    'Venta',

                'entidad_id' =>
                    100,
            ]
        );
    }


    public function test_registra_auditoria_sin_usuario(): void
    {
        $auditoria = app(AuditoriaService::class)
            ->registrar(

                usuarioId: null,

                accion: 'SISTEMA',

                entidad: 'Proceso',

                entidadId: null

            );


        $this->assertNull(
            $auditoria->usuario_id
        );


        $this->assertEquals(
            'SISTEMA',
            $auditoria->accion
        );
    }
}