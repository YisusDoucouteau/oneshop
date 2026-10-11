<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteVentasWebTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_administrador_operativo_puede_abrir_reporte_de_ventas(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $venta = $this->crearVenta(
            $usuario,
            'Cliente Reporte',
            5200
        );

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.index'))
            ->assertOk()
            ->assertSee('Reporte de ventas')
            ->assertSee('Operaciones')
            ->assertSee('Evolución de ventas')
            ->assertSee('data-oneshop-datatable', false)
            ->assertSee('Exportar CSV')
            ->assertSee($venta->numero)
            ->assertSee('Cliente Reporte')
            ->assertSee('5.200,00')
            ->assertSee('Generar PDF')
            ->assertSee('Imprimir');
    }

    public function test_reporte_de_ventas_busca_por_numero_y_cliente(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $visible = $this->crearVenta(
            $usuario,
            'Cliente Alfa',
            2100,
            'VEN-ALFA-' . Str::upper(Str::random(5))
        );

        $oculta = $this->crearVenta(
            $usuario,
            'Cliente Beta',
            9900,
            'VEN-BETA-' . Str::upper(Str::random(5))
        );

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.index', [
                'q' => 'Alfa',
            ]))
            ->assertOk()
            ->assertSee($visible->numero)
            ->assertDontSee($oculta->numero)
            ->assertSee('2.100,00')
            ->assertDontSee('9.900,00');
    }

    public function test_reporte_de_ventas_tiene_salida_de_impresion_y_pdf(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $venta = $this->crearVenta(
            $usuario,
            'Cliente Documento',
            3500
        );

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.imprimir'))
            ->assertOk()
            ->assertSee('Imprimir reporte')
            ->assertSee($venta->numero)
            ->assertSee('Cliente Documento');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_vendedor_no_puede_acceder_al_reporte_formal_de_ventas(): void
    {
        $usuario = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.index'))
            ->assertForbidden();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.pdf'))
            ->assertForbidden();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.imprimir'))
            ->assertForbidden();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.ventas.csv'))
            ->assertForbidden();
    }

    private function crearVenta(
        User $usuario,
        string $cliente,
        float $total,
        ?string $numero = null
    ): Venta {
        return Venta::create([
            'numero' => $numero
                ?? 'VEN-REP-' . Str::upper(Str::random(8)),
            'cliente_id' => null,
            'cliente_nombre_snapshot' => $cliente,
            'cliente_telefono_snapshot' => null,
            'vendedor_id' => $usuario->id,
            'reserva_id' => null,
            'fecha_venta' => now(),
            'subtotal' => $total,
            'descuento_total' => 0,
            'total' => $total,
            'estado' => 'REGISTRADA',
            'observacion' => null,
        ]);
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
