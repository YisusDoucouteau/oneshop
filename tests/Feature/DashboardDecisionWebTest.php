<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardDecisionWebTest extends TestCase
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


    public function test_administrador_operativo_ve_indicadores_para_toma_de_decisiones(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        Venta::create([
            'numero' => 'VEN-DASH-' . Str::upper(Str::random(6)),
            'cliente_id' => null,
            'cliente_nombre_snapshot' => 'Cliente Dashboard',
            'cliente_telefono_snapshot' => null,
            'vendedor_id' => $usuario->id,
            'reserva_id' => null,
            'fecha_venta' => now(),
            'subtotal' => 2450,
            'descuento_total' => 0,
            'total' => 2450,
            'estado' => 'REGISTRADA',
            'observacion' => null,
        ]);

        $this
            ->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Decisiones del mes')
            ->assertSee('Ventas del mes')
            ->assertSee('2.450,00')
            ->assertSee('Cobros pendientes')
            ->assertSee('Vendedor destacado');
    }

    public function test_vendedor_no_ve_resumen_financiero_global(): void
    {
        $usuario = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Decisiones del mes')
            ->assertDontSee('Ventas del mes');
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
