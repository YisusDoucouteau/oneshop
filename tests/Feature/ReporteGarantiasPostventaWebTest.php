<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteGarantiasPostventaWebTest extends TestCase
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


    public function test_administrador_operativo_puede_abrir_reporte_de_garantias_y_postventa(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.garantias-postventa.index'))
            ->assertOk()
            ->assertSee('Garantías y postventa')
            ->assertSee('Casos activos')
            ->assertSee('Tiempo promedio')
            ->assertSee('data-oneshop-datatable', false)
            ->assertSee('Exportar CSV')
            ->assertSee('Generar PDF')
            ->assertSee('Imprimir');
    }

    public function test_reporte_de_garantias_tiene_csv_impresion_y_pdf(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.garantias-postventa.csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.garantias-postventa.imprimir'))
            ->assertOk()
            ->assertSee('Imprimir reporte');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.garantias-postventa.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_vendedor_no_puede_acceder_al_reporte_de_garantias(): void
    {
        $usuario = $this->usuarioConRol('VENDEDOR');

        foreach ([
            'reportes.garantias-postventa.index',
            'reportes.garantias-postventa.csv',
            'reportes.garantias-postventa.pdf',
            'reportes.garantias-postventa.imprimir',
        ] as $ruta) {
            $this
                ->actingAs($usuario)
                ->get(route($ruta))
                ->assertForbidden();
        }
    }

    private function usuarioConRol(string $codigoRol): User
    {
        $usuario = User::factory()->create(['activo' => true]);
        $rol = Rol::query()->where('codigo', $codigoRol)->firstOrFail();
        $usuario->roles()->attach($rol->id);

        return $usuario;
    }
}
