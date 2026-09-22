<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Moneda;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\TipoCambio;
use App\Models\User;
use App\Services\RentabilidadRebajaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class VentaSnapshotEconomicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_venta_congela_costo_tc_ganancia_y_reparto(): void
    {
        /*
         * Usamos el seeder general porque este escenario económico
         * necesita también los catálogos monetarios (BOB / USD),
         * además de inventario y estados.
         */
        $this->seed();

        $usuario =
            User::factory()->create([
                'activo' => true,
            ]);

        $equipo =
            $this->crearEquipoDisponible();

        $bob =
            Moneda::query()
                ->where('codigo', 'BOB')
                ->firstOrFail();

        $usd =
            Moneda::query()
                ->where('codigo', 'USD')
                ->first();

        if (!$usd) {
            $usd =
                Moneda::create([
                    'codigo' => 'USD',
                    'nombre' => 'Dólar estadounidense',
                    'simbolo' => '$',
                    'activo' => true,
                ]);
        }

        $tipoCambio =
            TipoCambio::create([
                'moneda_origen_id' =>
                    $usd->id,

                'moneda_destino_id' =>
                    $bob->id,

                'valor' =>
                    12,

                'fecha_vigencia' =>
                    now(),

                'fuente' =>
                    'COMERCIAL_PRECIO',

                'registrado_por_id' =>
                    $usuario->id,
            ]);

        $rentabilidad =
            Mockery::mock(
                RentabilidadRebajaService::class
            );

        $rentabilidad
            ->shouldReceive('evaluar')
            ->once()
            ->withArgs(
                fn ($equipoRecibido, $precio) =>
                    $equipoRecibido->id
                        === $equipo->id
                    &&
                    (float) $precio
                        === 5300.0
            )
            ->andReturn([
                'equipo_id' =>
                    $equipo->id,

                'precio_publicado' =>
                    5900.0,

                'precio_rebaja' =>
                    5300.0,

                'costo_actualizado' =>
                    3827.0,

                'tipo_cambio_id' =>
                    $tipoCambio->id,

                'tipo_cambio' =>
                    12.0,

                'moneda_origen' =>
                    'USD',

                'monto_origen' =>
                    250.0,

                'fuente_costo' =>
                    'TIPO_CAMBIO_COMERCIAL',

                'ganancia' =>
                    491.0,

                'margen_total' =>
                    1473.0,

                'reparto' => [
                    'hugo' => 491.0,
                    'daniel' => 491.0,
                    'tienda' => 491.0,
                ],
            ]);

        $this->app->instance(
            RentabilidadRebajaService::class,
            $rentabilidad
        );

        $venta =
            app(VentaService::class)
                ->registrarVentaDirecta(
                    vendedorId:
                        $usuario->id,

                    equiposIds:
                        [$equipo->id],

                    preciosAcordados: [
                        $equipo->id =>
                            5300,
                    ]
                );

        $detalle =
            $venta->detalles()
                ->firstOrFail();

        $this->assertSame(
            '3827.00',
            (string)
            $detalle->costo_unitario_snapshot
        );

        $this->assertSame(
            $tipoCambio->id,
            $detalle->tipo_cambio_snapshot_id
        );

        $this->assertSame(
            '12.000000',
            (string)
            $detalle->tipo_cambio_valor_snapshot
        );

        $this->assertSame(
            'USD',
            $detalle->moneda_origen_snapshot
        );

        $this->assertSame(
            '250.00',
            (string)
            $detalle->monto_origen_snapshot
        );

        $this->assertSame(
            'TIPO_CAMBIO_COMERCIAL',
            $detalle->fuente_costo_snapshot
        );

        $this->assertSame(
            '1473.00',
            (string)
            $detalle->margen_total_snapshot
        );

        $this->assertSame(
            '491.00',
            (string)
            $detalle->ganancia_snapshot
        );

        $this->assertSame(
            '491.00',
            (string)
            $detalle->hugo_snapshot
        );

        $this->assertSame(
            '491.00',
            (string)
            $detalle->daniel_snapshot
        );

        $this->assertSame(
            '491.00',
            (string)
            $detalle->tienda_snapshot
        );

        /*
         * Cambiar posteriormente TC y precio no puede alterar
         * la fotografía económica de una venta ya registrada.
         */
        $tipoCambio->update([
            'valor' => 13.5,
        ]);

        $equipo
            ->precios()
            ->where('vigente', true)
            ->update([
                'precio_publico' => 6500,
                'costo_total_snapshot' => 4100,
            ]);

        $detalle->refresh();

        $this->assertSame(
            '3827.00',
            (string)
            $detalle->costo_unitario_snapshot
        );

        $this->assertSame(
            '12.000000',
            (string)
            $detalle->tipo_cambio_valor_snapshot
        );

        $this->assertSame(
            '491.00',
            (string)
            $detalle->ganancia_snapshot
        );
    }

    private function crearEquipoDisponible(): Equipo
    {
        $categoria =
            CategoriaProducto::query()
                ->where('codigo', 'LAPTOP')
                ->firstOrFail();

        $almacen =
            Almacen::query()
                ->where('activo', true)
                ->orderByDesc('principal')
                ->firstOrFail();

        $estado =
            EstadoEquipo::query()
                ->where('codigo', 'DISPONIBLE')
                ->firstOrFail();

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'codigo' =>
                    'PROD-' . Str::uuid(),

                'nombre' =>
                    'Laptop snapshot económico',

                'modelo' =>
                    'TEST',

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-' . Str::uuid(),

            'nombre' =>
                'Garantía snapshot',

            'categoria_producto_id' =>
                $categoria->id,

            'producto_id' =>
                $producto->id,

            'duracion_meses' =>
                6,

            'condiciones' =>
                'Garantía de prueba.',

            'exclusiones' =>
                'Daños físicos.',

            'vigente_desde' =>
                now()->subDay(),

            'activo' =>
                true,
        ]);

        $equipo =
            Equipo::create([
                'producto_id' =>
                    $producto->id,

                'almacen_actual_id' =>
                    $almacen->id,

                'estado_actual_id' =>
                    $estado->id,

                'codigo_interno' =>
                    'EQ-SNAP-' . Str::uuid(),

                'fecha_registro' =>
                    now(),

                'fecha_disponible' =>
                    now(),

                'activo' =>
                    true,
            ]);

        PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,

            /*
             * Valor deliberadamente distinto al costo final
             * para demostrar que VentaService toma la economía
             * desde RentabilidadRebajaService.
             */
            'costo_total_snapshot' =>
                3000,

            'precio_sugerido' =>
                5900,

            'precio_publico' =>
                5900,

            'precio_minimo_autorizado' =>
                5000,

            'vigente_desde' =>
                now(),

            'vigente' =>
                true,
        ]);

        DB::table(
            'existencias_productos'
        )->insert([
            'producto_id' =>
                $producto->id,

            'almacen_id' =>
                $almacen->id,

            'cantidad_disponible' =>
                1,

            'cantidad_reservada' =>
                0,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        return $equipo;
    }
}
