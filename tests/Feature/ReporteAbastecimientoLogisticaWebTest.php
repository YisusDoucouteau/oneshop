<?php

namespace Tests\Feature;

use App\Models\Lote;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteAbastecimientoLogisticaWebTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_administrador_operativo_puede_abrir_reporte_logistico(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $proveedor = Proveedor::create([
            'nombre' => 'Proveedor Reporte ' . Str::upper(Str::random(6)),
            'contacto' => null,
            'telefono' => null,
            'email' => null,
            'direccion' => null,
            'activo' => true,
        ]);

        $lote = Lote::create([
            'proveedor_id' => $proveedor->id,
            'codigo' => 'LOT-REP-' . Str::upper(Str::random(6)),
            'fecha_compra' => now()->toDateString(),
            'estado' => 'ABIERTO',
        ]);

        $this->actingAs($usuario)
            ->get(route('reportes.abastecimiento-logistica.index'))
            ->assertOk()
            ->assertSee('Abastecimiento y logística')
            ->assertSee('data-oneshop-datatable', false)
            ->assertSee($lote->codigo)
            ->assertSee('Exportar CSV')
            ->assertSee('Generar PDF')
            ->assertSee('Imprimir');
    }

    public function test_reporte_logistico_tiene_csv_impresion_y_pdf(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $this->actingAs($usuario)
            ->get(route('reportes.abastecimiento-logistica.csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($usuario)
            ->get(route('reportes.abastecimiento-logistica.imprimir'))
            ->assertOk()
            ->assertSee('Imprimir reporte');

        $this->actingAs($usuario)
            ->get(route('reportes.abastecimiento-logistica.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_vendedor_no_puede_acceder_al_reporte_logistico(): void
    {
        $usuario = $this->usuarioConRol('VENDEDOR');

        foreach ([
            'reportes.abastecimiento-logistica.index',
            'reportes.abastecimiento-logistica.csv',
            'reportes.abastecimiento-logistica.pdf',
            'reportes.abastecimiento-logistica.imprimir',
        ] as $ruta) {
            $this->actingAs($usuario)->get(route($ruta))->assertForbidden();
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
