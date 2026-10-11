<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\IndicadorComercialService;
use App\Services\ReporteConsultaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteConsultaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumen_comercial_respeta_periodo_y_excluye_anuladas_por_defecto(): void
    {
        $vendedor = $this->crearUsuario('Vendedor Reporte');
        $producto = $this->crearProducto();

        $ventaActual = $this->crearVenta(
            $vendedor,
            'VEN-REP-001',
            now(),
            3000,
            'REGISTRADA'
        );

        DetalleVenta::create([
            'venta_id' => $ventaActual->id,
            'producto_id' => $producto->id,
            'equipo_id' => null,
            'cantidad' => 2,
            'precio_lista_snapshot' => 1600,
            'descuento_unitario' => 100,
            'precio_unitario' => 1500,
            'costo_unitario_snapshot' => 1000,
            'subtotal' => 3000,
            'observacion' => null,
        ]);

        $this->crearVenta(
            $vendedor,
            'VEN-REP-ANT',
            now()->subMonths(2),
            9000,
            'REGISTRADA'
        );

        $this->crearVenta(
            $vendedor,
            'VEN-REP-ANU',
            now(),
            8000,
            'ANULADA'
        );

        $resultado = app(IndicadorComercialService::class)
            ->resumenGeneral([
                'fecha_desde' => now()->startOfMonth()->toDateString(),
                'fecha_hasta' => now()->endOfMonth()->toDateString(),
            ]);

        $this->assertSame(1, $resultado['cantidad_ventas']);
        $this->assertSame(3000.0, $resultado['total_vendido']);
        $this->assertSame(2000.0, $resultado['costo_total']);
        $this->assertSame(1000.0, $resultado['utilidad_total']);
        $this->assertSame(200.0, $resultado['descuentos_otorgados']);
        $this->assertSame(3000.0, $resultado['ticket_promedio']);
    }

    public function test_resumen_comercial_filtra_por_vendedor(): void
    {
        $vendedorUno = $this->crearUsuario('Vendedor Uno');
        $vendedorDos = $this->crearUsuario('Vendedor Dos');

        $this->crearVenta(
            $vendedorUno,
            'VEN-VEND-001',
            now(),
            2500,
            'REGISTRADA'
        );

        $this->crearVenta(
            $vendedorDos,
            'VEN-VEND-002',
            now(),
            4500,
            'REGISTRADA'
        );

        $resultado = app(IndicadorComercialService::class)
            ->resumenGeneral([
                'vendedor_id' => $vendedorUno->id,
            ]);

        $this->assertSame(1, $resultado['cantidad_ventas']);
        $this->assertSame(2500.0, $resultado['total_vendido']);
    }


    public function test_comparativo_usa_un_periodo_anterior_equivalente(): void
    {
        $vendedor = $this->crearUsuario('Vendedor Comparativo');

        $this->crearVenta(
            $vendedor,
            'VEN-COMP-ACTUAL',
            now(),
            3000,
            'REGISTRADA'
        );

        $this->crearVenta(
            $vendedor,
            'VEN-COMP-ANTERIOR',
            now()->subMonth(),
            2000,
            'REGISTRADA'
        );

        $resultado = app(ReporteConsultaService::class)
            ->compararVentasConPeriodoAnterior([
                'fecha_desde' => now()->startOfMonth()->toDateString(),
                'fecha_hasta' => now()->endOfMonth()->toDateString(),
            ]);

        $this->assertArrayHasKey('periodo_anterior', $resultado);
        $this->assertArrayHasKey('variaciones', $resultado);
        $this->assertSame(50.0, $resultado['variaciones']['total_vendido']);
    }

    private function crearUsuario(string $nombre): User
    {
        return User::create([
            'name' => $nombre,
            'email' => Str::uuid() . '@reportes.test',
            'password' => bcrypt('12345678'),
            'activo' => true,
        ]);
    }

    private function crearProducto(): Producto
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'REP-' . Str::upper(Str::random(6)),
            'nombre' => 'Categoría reporte ' . Str::random(5),
            'activo' => true,
        ]);

        return Producto::create([
            'categoria_producto_id' => $categoria->id,
            'codigo' => 'PROD-' . Str::upper(Str::random(8)),
            'nombre' => 'Equipo Reporte',
            'modelo' => 'RPT',
            'descripcion' => 'Producto de prueba',
            'es_serializado' => true,
            'activo' => true,
        ]);
    }

    private function crearVenta(
        User $vendedor,
        string $numero,
        $fecha,
        float $total,
        string $estado
    ): Venta {
        return Venta::create([
            'numero' => $numero,
            'cliente_id' => null,
            'vendedor_id' => $vendedor->id,
            'reserva_id' => null,
            'fecha_venta' => $fecha,
            'subtotal' => $total,
            'descuento_total' => 0,
            'total' => $total,
            'estado' => $estado,
            'anulado_por_id' => null,
            'fecha_anulacion' => $estado === 'ANULADA' ? $fecha : null,
            'motivo_anulacion' => $estado === 'ANULADA' ? 'Prueba' : null,
            'observacion' => null,
        ]);
    }
}
