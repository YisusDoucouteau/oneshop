<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Marca;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\User;
use App\Services\VentaService;
use Database\Seeders\CatalogoInventarioSeeder;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaDocumentoSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            CatalogoSeeder::class
        );

        $this->seed(
            CatalogoInventarioSeeder::class
        );
    }

    public function test_venta_directa_congela_cliente_y_datos_de_boleta(): void
    {
        $vendedor =
            User::factory()->create([
                'activo' => true,
            ]);

        $equipo =
            $this->crearEquipoDisponible();

        $venta =
            app(VentaService::class)
                ->registrarVentaDirecta(
                    vendedorId:
                        $vendedor->id,

                    equiposIds:
                        [$equipo->id],

                    clienteId:
                        null,

                    observacion:
                        'Venta con cliente manual.',

                    preciosAcordados: [
                        $equipo->id =>
                            3500,
                    ],

                    clienteNombreSnapshot:
                        'Juan Pérez',

                    clienteTelefonoSnapshot:
                        '71234567',

                    condicionesVenta: [
                        $equipo->id =>
                            'USADO',
                    ]
                );

        $this->assertNull(
            $venta->cliente_id
        );

        $this->assertSame(
            'Juan Pérez',
            $venta->cliente_nombre_snapshot
        );

        $this->assertSame(
            '71234567',
            $venta->cliente_telefono_snapshot
        );

        $detalle =
            $venta->detalles()
                ->firstOrFail();

        $this->assertSame(
            'Lenovo Snapshot Test',
            $detalle->marca_snapshot
        );

        $this->assertSame(
            'ThinkPad T480',
            $detalle->modelo_snapshot
        );

        $this->assertSame(
            '1726',
            $detalle->codigo_interno_snapshot
        );

        $this->assertSame(
            'USADO',
            $detalle->condicion_venta_snapshot
        );

        /*
         * Mutar catálogo después no debe cambiar la fotografía documental.
         */
        $equipo->producto->update([
            'modelo' =>
                'MODELO CAMBIADO',
        ]);

        $equipo->update([
            'codigo_interno' =>
                '9999',
        ]);

        $detalle->refresh();

        $this->assertSame(
            'ThinkPad T480',
            $detalle->modelo_snapshot
        );

        $this->assertSame(
            '1726',
            $detalle->codigo_interno_snapshot
        );
    }

    private function crearEquipoDisponible(): Equipo
    {
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

        $marca =
            Marca::create([
                'nombre' =>
                    'Lenovo Snapshot Test',

                'descripcion' =>
                    null,

                'activo' =>
                    true,
            ]);

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'PROD-BOLETA-'
                    . Str::upper(
                        Str::random(6)
                    ),

                'nombre' =>
                    'Laptop Lenovo',

                'modelo' =>
                    'ThinkPad T480',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-BOLETA-'
                . Str::upper(
                    Str::random(6)
                ),

            'nombre' =>
                'Garantía boleta',

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
                    '1726',

                'serial_fabricante' =>
                    'PF-FACTORY-TEST',

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
                'Precio prueba boleta.',
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
