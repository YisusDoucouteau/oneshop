<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\VentaService;
use Database\Seeders\CatalogoInventarioSeeder;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaDirectaInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            CatalogoSeeder::class
        );

        $this->seed(
            CatalogoInventarioSeeder::class
        );

        $this->vendedor =
            User::factory()->create([
                'activo' => true,
            ]);
    }

    public function test_venta_directa_descuenta_existencia_disponible_y_registra_movimiento(): void
    {
        $equipo =
            $this->crearEquipoDisponible(
                cantidadDisponible: 1
            );

        $venta =
            app(VentaService::class)
                ->registrarVentaDirecta(
                    vendedorId:
                        $this->vendedor->id,

                    equiposIds:
                        [$equipo->id]
                );

        $this->assertDatabaseHas(
            'existencias_productos',
            [
                'producto_id' =>
                    $equipo->producto_id,

                'almacen_id' =>
                    $equipo->almacen_actual_id,

                'cantidad_disponible' =>
                    0,

                'cantidad_reservada' =>
                    0,
            ]
        );

        $tipo =
            TipoMovimientoInventario::query()
                ->where(
                    'codigo',
                    'VENTA_DIRECTA'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'movimientos_inventario',
            [
                'producto_id' =>
                    $equipo->producto_id,

                'almacen_id' =>
                    $equipo->almacen_actual_id,

                'tipo_movimiento_id' =>
                    $tipo->id,

                'usuario_id' =>
                    $this->vendedor->id,

                'cambio_disponible' =>
                    -1,

                'cambio_reservado' =>
                    0,

                'saldo_disponible_resultante' =>
                    0,

                'saldo_reservado_resultante' =>
                    0,

                'tipo_referencia' =>
                    'VENTA_DIRECTA',

                'referencia_id' =>
                    $venta->id,
            ]
        );
    }

    public function test_si_no_hay_existencia_disponible_la_venta_directa_se_revierte_completa(): void
    {
        $equipo =
            $this->crearEquipoDisponible(
                cantidadDisponible: 0
            );

        try {
            app(VentaService::class)
                ->registrarVentaDirecta(
                    vendedorId:
                        $this->vendedor->id,

                    equiposIds:
                        [$equipo->id]
                );

            $this->fail(
                'La venta debía rechazarse por falta de inventario disponible.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertStringContainsString(
                'No existe suficiente inventario disponible',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'ventas',
            0
        );

        $this->assertDatabaseCount(
            'detalles_ventas',
            0
        );

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'equipos',
            [
                'id' =>
                    $equipo->id,

                'estado_actual_id' =>
                    $estadoDisponible->id,
            ]
        );

        $this->assertDatabaseHas(
            'existencias_productos',
            [
                'producto_id' =>
                    $equipo->producto_id,

                'almacen_id' =>
                    $equipo->almacen_actual_id,

                'cantidad_disponible' =>
                    0,

                'cantidad_reservada' =>
                    0,
            ]
        );
    }

    private function crearEquipoDisponible(
        int $cantidadDisponible
    ): Equipo {
        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $almacen =
            Almacen::query()
                ->where(
                    'activo',
                    true
                )
                ->orderByDesc(
                    'principal'
                )
                ->firstOrFail();

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    null,

                'codigo' =>
                    'PROD-VD-' .
                    Str::uuid(),

                'nombre' =>
                    'Laptop venta directa inventario',

                'modelo' =>
                    'TEST',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-VD-' .
                    Str::uuid(),

            'nombre' =>
                'Garantía venta directa',

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

            'vigente_hasta' =>
                null,

            'activo' =>
                true,
        ]);

        $equipo =
            Equipo::create([
                'producto_id' =>
                    $producto->id,

                'detalle_lote_id' =>
                    null,

                'almacen_actual_id' =>
                    $almacen->id,

                'estado_actual_id' =>
                    $estadoDisponible->id,

                'condicion_fisica_id' =>
                    null,

                'codigo_interno' =>
                    'EQ-VD-' .
                    Str::uuid(),

                'serial_fabricante' =>
                    null,

                'fecha_registro' =>
                    now(),

                'fecha_disponible' =>
                    now(),

                'observacion' =>
                    null,

                'activo' =>
                    true,
            ]);

        PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,

            'tipo_cambio_id' =>
                null,

            'costo_total_snapshot' =>
                2900,

            'precio_sugerido' =>
                3500,

            'precio_publico' =>
                3500,

            'precio_minimo_autorizado' =>
                3200,

            'vigente_desde' =>
                now(),

            'vigente_hasta' =>
                null,

            'vigente' =>
                true,

            'aprobado_por_id' =>
                null,

            'observacion' =>
                'Precio prueba inventario.',
        ]);

        DB::table(
            'existencias_productos'
        )->insert([
            'producto_id' =>
                $producto->id,

            'almacen_id' =>
                $almacen->id,

            'cantidad_disponible' =>
                $cantidadDisponible,

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
