<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PrecioRolesPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_vendedor_solo_conserva_visibilidad_del_precio_publico(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        $this->assertTrue(
            $vendedor->tienePermiso('precios.ver')
        );

        $this->assertFalse(
            $vendedor->tienePermiso('precios.modificar')
        );

        $this->assertFalse(
            $vendedor->tienePermiso('precios.autorizar_descuento')
        );
    }


    public function test_vendedor_ve_precio_publico_sin_datos_internos_en_ficha(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        $this->actingAs($vendedor);

        $equipo = (object) [
            'codigo_interno' => 'OS-TEST',
            'estadoActual' => (object) [
                'nombre' => 'Disponible',
            ],
            'precioVigente' => (object) [
                'precio_publico' => 5200,
                'precio_sugerido' => 4600,
                'precio_minimo_autorizado' => 4250,
                'vigente_desde' => now(),
                'observacion' => 'Nota interna de administración.',
            ],
        ];

        $html = Blade::render(
            '<x-inventario.comercial :equipo="$equipo" />',
            [
                'equipo' => $equipo,
            ]
        );

        $this->assertStringContainsString(
            'Bs 5,200.00',
            $html
        );

        $this->assertStringNotContainsString(
            'Gestionar precio',
            $html
        );

        $this->assertStringNotContainsString(
            'Sugerencia OneShop registrada',
            $html
        );

        $this->assertStringNotContainsString(
            'Mínimo sin autorización',
            $html
        );

        $this->assertStringNotContainsString(
            'Margen negociable',
            $html
        );

        $this->assertStringNotContainsString(
            'Nota interna de administración.',
            $html
        );
    }


    public function test_tecnico_no_ve_informacion_de_precio(): void
    {
        $tecnico = $this->usuarioConRol('TECNICO');

        $this->assertFalse(
            $tecnico->tienePermiso('precios.ver')
        );

        $this->assertFalse(
            $tecnico->tienePermiso('precios.modificar')
        );

        $this->actingAs($tecnico);

        $equipo = (object) [
            'codigo_interno' => 'OS-TECH',
            'estadoActual' => (object) [
                'nombre' => 'Disponible',
            ],
            'precioVigente' => (object) [
                'precio_publico' => 5200,
                'precio_sugerido' => 4600,
                'precio_minimo_autorizado' => 4250,
                'vigente_desde' => now(),
                'observacion' => 'Nota interna de administración.',
            ],
        ];

        $html = Blade::render(
            '<x-inventario.comercial :equipo="$equipo" />',
            [
                'equipo' => $equipo,
            ]
        );

        $this->assertStringContainsString(
            'Información restringida',
            $html
        );

        $this->assertStringNotContainsString(
            'Bs 5,200.00',
            $html
        );

        $this->assertStringNotContainsString(
            'Gestionar precio',
            $html
        );
    }

    public function test_admin_operativo_puede_gestionar_precios_sin_autorizar_excepciones(): void
    {
        $adminOperativo = $this->usuarioConRol('ADMIN_OPERATIVO');

        $this->assertTrue(
            $adminOperativo->tienePermiso('precios.ver')
        );

        $this->assertTrue(
            $adminOperativo->tienePermiso('precios.modificar')
        );

        $this->assertFalse(
            $adminOperativo->tienePermiso('precios.autorizar_descuento')
        );
    }

    public function test_administrador_conserva_todos_los_permisos_comerciales(): void
    {
        $admin = $this->usuarioConRol('ADMINISTRADOR');

        $this->assertTrue(
            $admin->tienePermiso('precios.ver')
        );

        $this->assertTrue(
            $admin->tienePermiso('precios.modificar')
        );

        $this->assertTrue(
            $admin->tienePermiso('precios.autorizar_descuento')
        );
    }

    private function usuarioConRol(string $codigoRol): User
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::query()
            ->where('codigo', $codigoRol)
            ->firstOrFail();

        $usuario->roles()->attach($rol->id);

        return $usuario;
    }
}
