<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
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
    protected Almacen $almacen;

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

        $rolAdministrador = Rol::where(
            'codigo',
            'ADMINISTRADOR'
        )->firstOrFail();

        $this->administrador
            ->roles()
            ->attach($rolAdministrador->id);

        $this->vendedor = User::factory()->create([
            'activo' => true,
            'almacen_operativo_id' =>
                $this->almacen->id,
        ]);

        $rolVendedor = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $this->vendedor
            ->roles()
            ->attach($rolVendedor->id);
    }

    public function test_administrador_puede_crear_usuario_con_rol_y_sede(): void
    {
        $usuario = app(UsuarioService::class)
            ->crearUsuario(
                gestionadoPorId:
                    $this->administrador->id,

                nombre:
                    'Vendedor OneShop',

                email:
                    'vendedor@oneshop.test',

                password:
                    'ClaveSegura123',

                rolesCodigos:
                    ['VENDEDOR'],

                almacenOperativoId:
                    $this->almacen->id
            );

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'email' => 'vendedor@oneshop.test',
            'activo' => true,
            'almacen_operativo_id' =>
                $this->almacen->id,
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

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'CREAR_USUARIO',
            'entidad' => 'User',
            'entidad_id' => $usuario->id,
            'usuario_id' =>
                $this->administrador->id,
        ]);
    }

    public function test_administrador_global_puede_crearse_sin_sede(): void
    {
        $usuario = app(UsuarioService::class)
            ->crearUsuario(
                gestionadoPorId:
                    $this->administrador->id,

                nombre:
                    'Administrador Dos',

                email:
                    'admin2@oneshop.test',

                password:
                    'ClaveSegura123',

                rolesCodigos:
                    ['ADMINISTRADOR'],

                almacenOperativoId:
                    null
            );

        $this->assertNull(
            $usuario->almacen_operativo_id
        );

        $this->assertTrue(
            $usuario->tieneRol('ADMINISTRADOR')
        );
    }

    public function test_usuario_operativo_requiere_sede(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'Debe asignarse una sede operativa'
        );

        app(UsuarioService::class)
            ->crearUsuario(
                gestionadoPorId:
                    $this->administrador->id,

                nombre:
                    'Técnico sin sede',

                email:
                    'tecnico-sin-sede@oneshop.test',

                password:
                    'ClaveSegura123',

                rolesCodigos:
                    ['TECNICO']
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
                gestionadoPorId:
                    $this->vendedor->id,

                nombre:
                    'Usuario no autorizado',

                email:
                    'no-autorizado@oneshop.test',

                password:
                    'ClaveSegura123',

                rolesCodigos:
                    ['TECNICO'],

                almacenOperativoId:
                    $this->almacen->id
            );
    }

    public function test_administrador_puede_cambiar_roles_y_sede(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'almacen_operativo_id' =>
                $this->almacen->id,
        ]);

        $rolVendedor = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $usuario->roles()->attach(
            $rolVendedor->id
        );

        $otraSede = Almacen::query()
            ->where('activo', true)
            ->where(
                'id',
                '!=',
                $this->almacen->id
            )
            ->firstOrFail();

        $usuario = app(UsuarioService::class)
            ->actualizarRoles(
                usuarioId:
                    $usuario->id,

                rolesCodigos:
                    ['TECNICO'],

                gestionadoPorId:
                    $this->administrador->id,

                almacenOperativoId:
                    $otraSede->id
            );

        $this->assertTrue(
            $usuario->tieneRol('TECNICO')
        );

        $this->assertFalse(
            $usuario->tieneRol('VENDEDOR')
        );

        $this->assertSame(
            $otraSede->id,
            $usuario->almacen_operativo_id
        );

        $this->assertDatabaseHas('auditorias', [
            'accion' =>
                'ACTUALIZAR_ACCESO_USUARIO',
            'entidad' => 'User',
            'entidad_id' => $usuario->id,
            'usuario_id' =>
                $this->administrador->id,
        ]);
    }

    public function test_administrador_puede_actualizar_nombre_correo_rol_y_sede(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'almacen_operativo_id' =>
                $this->almacen->id,
        ]);

        $rolVendedor = Rol::where(
            'codigo',
            'VENDEDOR'
        )->firstOrFail();

        $usuario->roles()->attach(
            $rolVendedor->id
        );

        $actualizado = app(UsuarioService::class)
            ->actualizarUsuario(
                usuarioId:
                    $usuario->id,

                nombre:
                    'Técnico OneShop',

                email:
                    'tecnico@oneshop.test',

                rolesCodigos:
                    ['TECNICO'],

                almacenOperativoId:
                    $this->almacen->id,

                gestionadoPorId:
                    $this->administrador->id
            );

        $this->assertSame(
            'Técnico OneShop',
            $actualizado->name
        );

        $this->assertSame(
            'tecnico@oneshop.test',
            $actualizado->email
        );

        $this->assertTrue(
            $actualizado->tieneRol('TECNICO')
        );
    }

    public function test_administrador_no_puede_modificar_su_propio_acceso(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        app(UsuarioService::class)
            ->actualizarUsuario(
                usuarioId:
                    $this->administrador->id,

                nombre:
                    $this->administrador->name,

                email:
                    $this->administrador->email,

                rolesCodigos:
                    ['ADMINISTRADOR'],

                almacenOperativoId:
                    null,

                gestionadoPorId:
                    $this->administrador->id
            );
    }

    public function test_administrador_puede_desactivar_otro_usuario(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'almacen_operativo_id' =>
                $this->almacen->id,
        ]);

        $usuario = app(UsuarioService::class)
            ->cambiarEstado(
                usuarioId:
                    $usuario->id,

                activo:
                    false,

                gestionadoPorId:
                    $this->administrador->id
            );

        $this->assertFalse(
            $usuario->activo
        );

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'DESACTIVAR_USUARIO',
            'entidad' => 'User',
            'entidad_id' => $usuario->id,
        ]);
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
                usuarioId:
                    $this->administrador->id,

                activo:
                    false,

                gestionadoPorId:
                    $this->administrador->id
            );
    }

    public function test_administrador_puede_restablecer_contrasena_de_otro_usuario(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'password' => 'ClaveAnterior123',
        ]);

        app(UsuarioService::class)
            ->restablecerContrasena(
                usuarioId:
                    $usuario->id,

                nuevaContrasena:
                    'NuevaClave456',

                gestionadoPorId:
                    $this->administrador->id
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

        $auditoria = \App\Models\Auditoria::query()
            ->where(
                'accion',
                'RESTABLECER_CONTRASENA_USUARIO'
            )
            ->where(
                'entidad_id',
                $usuario->id
            )
            ->firstOrFail();

        $this->assertTrue(
            $auditoria
                ->datos_nuevos[
                    'contrasena_restablecida'
                ]
        );

        $this->assertArrayNotHasKey(
            'password',
            $auditoria->datos_nuevos
        );
    }

    public function test_administrador_no_restablece_su_contrasena_desde_administracion(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'Use Perfil'
        );

        app(UsuarioService::class)
            ->restablecerContrasena(
                usuarioId:
                    $this->administrador->id,

                nuevaContrasena:
                    'NuevaClave456',

                gestionadoPorId:
                    $this->administrador->id
            );
    }

    public function test_no_permite_asignar_rol_inexistente(): void
    {
        $this->expectException(
            ReglaNegocioException::class
        );

        app(UsuarioService::class)
            ->crearUsuario(
                gestionadoPorId:
                    $this->administrador->id,

                nombre:
                    'Usuario prueba',

                email:
                    'rol-invalido@oneshop.test',

                password:
                    'ClaveSegura123',

                rolesCodigos:
                    ['ROL_QUE_NO_EXISTE'],

                almacenOperativoId:
                    $this->almacen->id
            );
    }
}
