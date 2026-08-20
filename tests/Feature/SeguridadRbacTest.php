<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SeguridadRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Route::middleware([
            'web',
            'auth',
            'usuario.activo',
            'permiso:ventas.crear',
        ])->get('/prueba-rbac-ventas', function () {
            return response('OK', 200);
        });
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $usuario->roles()->attach($rol->id);

        $respuesta = $this
            ->actingAs($usuario)
            ->get('/prueba-rbac-ventas');

        $respuesta->assertOk();
    }

    public function test_usuario_sin_permiso_recibe_403(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::where(
            'codigo',
            'TECNICO'
        )->firstOrFail();

        $usuario->roles()->attach($rol->id);

        $respuesta = $this
            ->actingAs($usuario)
            ->get('/prueba-rbac-ventas');

        $respuesta->assertForbidden();
    }

    public function test_usuario_inactivo_pierde_acceso(): void
    {
        $usuario = User::factory()->create([
            'activo' => false,
        ]);

        $rol = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $usuario->roles()->attach($rol->id);

        $respuesta = $this
            ->actingAs($usuario)
            ->get('/prueba-rbac-ventas');

        $respuesta->assertRedirect(
            route('login')
        );

        $this->assertGuest();
    }

    public function test_metodo_tiene_rol_identifica_rol_asignado(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $usuario->roles()->attach($rol->id);

        $this->assertTrue(
            $usuario->tieneRol('VENDEDOR')
        );

        $this->assertFalse(
            $usuario->tieneRol('TECNICO')
        );
    }

    public function test_metodo_tiene_permiso_consulta_permisos_del_rol(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $usuario->roles()->attach($rol->id);

        $this->assertTrue(
            $usuario->tienePermiso('ventas.crear')
        );

        $this->assertFalse(
            $usuario->tienePermiso('auditoria.ver')
        );
    }
}