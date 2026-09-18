<?php

namespace Tests\Feature;

use App\Models\Lote;
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

    public function test_formulario_del_lote_expone_datos_de_compra_y_caracteristicas_esperadas(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $lote = Lote::query()->create([
            'codigo' => 'IMP-WEB-001',
            'estado' => 'ABIERTO',
        ]);

        $this
            ->actingAs($usuario)
            ->get(route('importaciones.show', $lote))
            ->assertOk()
            ->assertSee('Costo unitario')
            ->assertSee('Características y accesorios esperados')
            ->assertSee('name="moneda_id"', false)
            ->assertSee('name="tipo_cambio_aplicado"', false)
            ->assertSee('name="especificacion_esperada[procesador]"', false)
            ->assertSee('id="agregarComponenteEsperado"', false);
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
