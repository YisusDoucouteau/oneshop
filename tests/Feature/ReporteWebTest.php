<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La suite completa reutiliza el ciclo de RefreshDatabase entre clases.
        // Sembramos explícitamente los catálogos oficiales para que este test
        // no dependa del orden de ejecución ni del estado dejado por otra clase.
        $this->seed();
    }


    public function test_administrador_operativo_puede_abrir_centro_de_reportes(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Centro de información')
            ->assertSee('Reportes habilitados')
            ->assertSee('Ventas')
            ->assertSee('Inventario')
            ->assertSee('Abastecimiento y logística')
            ->assertSee('Garantías y postventa')
            ->assertDontSee('Ventas y rentabilidad')
            ->assertDontSee('PDF · CSV');
    }

    public function test_centro_de_reportes_enlaza_a_los_reportes_habilitados(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee(route('reportes.ventas.index'), false)
            ->assertSee(route('reportes.inventario.index'), false)
            ->assertSee(route('reportes.abastecimiento-logistica.index'), false)
            ->assertSee(route('reportes.garantias-postventa.index'), false);
    }

    public function test_vendedor_no_puede_acceder_a_reportes(): void
    {
        $usuario = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.index'))
            ->assertForbidden();
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
