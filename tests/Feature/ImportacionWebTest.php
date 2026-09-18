<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportacionWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_administrador_operativo_recibe_codigo_sugerido_para_nuevo_lote(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $respuesta = $this
            ->actingAs($usuario)
            ->get(route('importaciones.create'));

        $respuesta
            ->assertOk()
            ->assertSee('Registrar lote de importación')
            ->assertSee('Generado automáticamente')
            ->assertSee('IMP-'.now()->format('Y').'-001');
    }

    public function test_vendedor_no_puede_crear_lotes(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($vendedor)
            ->get(route('importaciones.create'))
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
