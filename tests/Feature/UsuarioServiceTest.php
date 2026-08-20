<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Rol;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $administrador;
    protected User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->administrador = User::factory()->create([
            'activo' => true,
        ]);

        $rolAdministrador = Rol::where(
            'codigo',
            'ADMINISTRADOR'
        )->firstOrFail();

        $this->administrador
            ->roles()
            ->attach($rolAdministrador->id);

        $this->vendedor = User::factory()->create([
            'activo' => true,
        ]);

        $rolVendedor = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $this->vendedor
            ->roles()
            ->attach($rolVendedor->id);
    }

    public function test_administrador_puede_crear_usuario_con_rol(): void
    {
        $usuario = app(UsuarioService::class)
            ->crearUsuario(
                gestionadoPorId: $this->administrador->id,
                nombre: 'Vendedor OneShop',
                email: 'vendedor@oneshop.test',
                password: 'ClaveSegura123',
                rolesCodigos: ['VENDEDOR']
            );

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'email' => 'vendedor@oneshop.test',
            'activo' => true,
        ]);

        $this->assertTrue(
            $usuario->tieneRol('VENDEDOR')
        );

        $this->assertTrue(
            Hash::check(
                'ClaveSegura123',
                $usuario->password
            )
        );
    }

    public function test_vendedor_no_puede_gestionar_usuarios(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no cuenta con permiso'
        );

        app(UsuarioService::class)
            ->crearUsuario(
                gestionadoPorId: $this->vendedor->id,
                nombre: 'Usuario no autorizado',
                email: 'no-autorizado@oneshop.test',
                password: 'ClaveSegura123',
                rolesCodigos: ['TECNICO']
            );
    }

    public function test_administrador_puede_cambiar_roles(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rolVendedor = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $usuario->roles()->attach(
            $rolVendedor->id
        );

        $usuario = app(UsuarioService::class)
            ->actualizarRoles(
                usuarioId: $usuario->id,
                rolesCodigos: ['TECNICO'],
                gestionadoPorId: $this->administrador->id
            );

        $this->assertTrue(
            $usuario->tieneRol('TECNICO')
        );

        $this->assertFalse(
            $usuario->tieneRol('VENDEDOR')
        );
    }

    public function test_administrador_puede_desactivar_otro_usuario(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $usuario = app(UsuarioService::class)
            ->cambiarEstado(
                usuarioId: $usuario->id,
                activo: false,
                gestionadoPorId: $this->administrador->id
            );

        $this->assertFalse(
            $usuario->activo
        );
    }

    public function test_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no puede desactivar su propia cuenta'
        );

        app(UsuarioService::class)
            ->cambiarEstado(
                usuarioId: $this->administrador->id,
                activo: false,
                gestionadoPorId: $this->administrador->id
            );
    }

    public function test_administrador_puede_restablecer_contrasena(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'password' => 'ClaveAnterior123',
        ]);

        app(UsuarioService::class)
            ->restablecerContrasena(
                usuarioId: $usuario->id,
                nuevaContrasena: 'NuevaClave456',
                gestionadoPorId: $this->administrador->id
            );

        $this->assertTrue(
            Hash::check(
                'NuevaClave456',
                $usuario->fresh()->password
            )
        );

        $this->assertFalse(
            Hash::check(
                'ClaveAnterior123',
                $usuario->fresh()->password
            )
        );
    }

    public function test_no_permite_asignar_rol_inexistente(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        app(UsuarioService::class)
            ->crearUsuario(
                gestionadoPorId: $this->administrador->id,
                nombre: 'Usuario prueba',
                email: 'rol-invalido@oneshop.test',
                password: 'ClaveSegura123',
                rolesCodigos: ['ROL_QUE_NO_EXISTE']
            );
    }
}