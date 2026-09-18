<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_vendedor_puede_consultar_listado_de_ventas(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($vendedor)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Consulta ventas, pagos, garantías y saldos pendientes')
            ->assertSee('Todavía no existen ventas registradas');
    }

    public function test_tecnico_no_puede_consultar_ventas(): void
    {
        $tecnico = $this->usuarioConRol('TECNICO');

        $this
            ->actingAs($tecnico)
            ->get(route('ventas.index'))
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
