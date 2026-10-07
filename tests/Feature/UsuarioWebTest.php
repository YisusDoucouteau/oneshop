<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioWebTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;
    private User $administradorOperativo;
    private Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->almacen = Almacen::query()
            ->where('activo', true)
            ->firstOrFail();

        $this->administrador = User::factory()->create([
            'activo' => true,
        ]);

        $this->administrador
            ->roles()
            ->attach(
                Rol::query()
                    ->where(
                        'codigo',
                        'ADMINISTRADOR'
                    )
                    ->firstOrFail()
                    ->id
            );

        $this->administradorOperativo =
            User::factory()->create([
                'activo' => true,
                'almacen_operativo_id' =>
                    $this->almacen->id,
            ]);

        $this->administradorOperativo
            ->roles()
            ->attach(
                Rol::query()
                    ->where(
                        'codigo',
                        'ADMIN_OPERATIVO'
                    )
                    ->firstOrFail()
                    ->id
            );
    }

    public function test_administrador_puede_abrir_bandeja_y_crear_usuario(): void
    {
        $this
            ->actingAs($this->administrador)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Usuarios y accesos')
            ->assertSee('Nuevo usuario');

        $response = $this
            ->actingAs($this->administrador)
            ->post(
                route('usuarios.store'),
                [
                    'name' =>
                        'Vendedor Web',

                    'email' =>
                        'vendedor-web@oneshop.test',

                    'password' =>
                        'ClaveSegura123',

                    'password_confirmation' =>
                        'ClaveSegura123',

                    'roles' =>
                        ['VENDEDOR'],

                    'almacen_operativo_id' =>
                        $this->almacen->id,

                    'activo' =>
                        '1',
                ]
            );

        $usuario = User::query()
            ->where(
                'email',
                'vendedor-web@oneshop.test'
            )
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'usuarios.show',
                $usuario
            )
        );

        $this->assertTrue(
            $usuario->tieneRol('VENDEDOR')
        );

        $this->assertSame(
            $this->almacen->id,
            $usuario->almacen_operativo_id
        );
    }

    public function test_administrador_operativo_puede_consultar_pero_no_gestionar(): void
    {
        $this
            ->actingAs(
                $this->administradorOperativo
            )
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertDontSee('Nuevo usuario');

        $this
            ->actingAs(
                $this->administradorOperativo
            )
            ->get(route('usuarios.create'))
            ->assertForbidden();

        $this
            ->actingAs(
                $this->administradorOperativo
            )
            ->post(
                route('usuarios.store'),
                [
                    'name' => 'No permitido',
                    'email' =>
                        'no-permitido@oneshop.test',
                    'password' =>
                        'ClaveSegura123',
                    'password_confirmation' =>
                        'ClaveSegura123',
                    'roles' =>
                        ['VENDEDOR'],
                    'almacen_operativo_id' =>
                        $this->almacen->id,
                    'activo' => '1',
                ]
            )
            ->assertForbidden();
    }

    public function test_administrador_puede_actualizar_rol_y_sede_desde_web(): void
    {
        $usuario = $this->crearUsuarioVendedor();

        $otraSede = Almacen::query()
            ->where('activo', true)
            ->where(
                'id',
                '!=',
                $this->almacen->id
            )
            ->firstOrFail();

        $response = $this
            ->actingAs($this->administrador)
            ->put(
                route(
                    'usuarios.update',
                    $usuario
                ),
                [
                    'name' =>
                        'Técnico Actualizado',

                    'email' =>
                        'tecnico-actualizado@oneshop.test',

                    'roles' =>
                        ['TECNICO'],

                    'almacen_operativo_id' =>
                        $otraSede->id,
                ]
            );

        $response->assertRedirect(
            route(
                'usuarios.show',
                $usuario
            )
        );

        $usuario->refresh();

        $this->assertSame(
            'Técnico Actualizado',
            $usuario->name
        );

        $this->assertSame(
            $otraSede->id,
            $usuario->almacen_operativo_id
        );

        $this->assertTrue(
            $usuario->tieneRol('TECNICO')
        );

        $this->assertFalse(
            $usuario->tieneRol('VENDEDOR')
        );
    }

    public function test_administrador_puede_desactivar_usuario_desde_web(): void
    {
        $usuario = $this->crearUsuarioVendedor();

        $this
            ->actingAs($this->administrador)
            ->patch(
                route(
                    'usuarios.estado',
                    $usuario
                ),
                [
                    'activo' => '0',
                ]
            )
            ->assertRedirect(
                route(
                    'usuarios.show',
                    $usuario
                )
            );

        $this->assertFalse(
            $usuario->fresh()->activo
        );
    }

    public function test_administrador_puede_restablecer_contrasena_desde_web(): void
    {
        $usuario = $this->crearUsuarioVendedor();

        $this
            ->actingAs($this->administrador)
            ->put(
                route(
                    'usuarios.contrasena',
                    $usuario
                ),
                [
                    'password' =>
                        'NuevaClave456',

                    'password_confirmation' =>
                        'NuevaClave456',
                ]
            )
            ->assertRedirect(
                route(
                    'usuarios.show',
                    $usuario
                )
            );

        $this->assertTrue(
            Hash::check(
                'NuevaClave456',
                $usuario->fresh()->password
            )
        );
    }

    public function test_usuario_sin_permiso_no_puede_ver_modulo(): void
    {
        $vendedor = $this->crearUsuarioVendedor();

        $this
            ->actingAs($vendedor)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    private function crearUsuarioVendedor(): User
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'almacen_operativo_id' =>
                $this->almacen->id,
        ]);

        $usuario
            ->roles()
            ->attach(
                Rol::query()
                    ->where(
                        'codigo',
                        'VENDEDOR'
                    )
                    ->firstOrFail()
                    ->id
            );

        return $usuario;
    }
}
