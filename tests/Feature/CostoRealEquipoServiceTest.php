<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\CostoEquipo;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\HistorialCostoUnidad;
use App\Models\IncorporacionUnidadAdquirida;
use App\Models\Moneda;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\TipoCosto;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\CostoRealEquipoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CostoRealEquipoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_historial_de_unidad_tiene_prioridad_sobre_snapshot_y_suma_costos_posteriores(): void
    {
        $equipo = $this->crearEquipoConSnapshot(4300);

        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $unidad = UnidadAdquirida::create([
            'producto_id' =>
                $equipo->producto_id,

            'nombre_equipo' =>
                'Unidad costo real',

            'modelo_equipo' =>
                'TEST',

            'almacen_actual_id' =>
                $equipo->almacen_actual_id,

            'estado' =>
                UnidadAdquirida::ESTADO_INCORPORADA,

            'codigo_trazabilidad' =>
                'UA-' . Str::upper(Str::random(12)),

            'equipo_id' =>
                $equipo->id,

            'registrado_por_id' =>
                $usuario->id,
        ]);

        IncorporacionUnidadAdquirida::create([
            'unidad_adquirida_id' =>
                $unidad->id,

            'equipo_id' =>
                $equipo->id,

            'usuario_id' =>
                $usuario->id,

            'condicion_fisica_id' =>
                null,

            'fecha_incorporacion' =>
                now(),

            'observacion' =>
                'Incorporación para prueba de costo.',
        ]);

        HistorialCostoUnidad::create([
            'unidad_adquirida_id' =>
                $unidad->id,

            'costo_compra' =>
                4300,

            'costos_lote' =>
                0,

            'intervenciones' =>
                300,

            'costo_total' =>
                4600,

            'completo' =>
                true,

            'detalle_json' =>
                [
                    'costo_compra' => 4300,
                    'intervenciones' => 300,
                    'costo_total' => 4600,
                ],

            'calculado_por_id' =>
                $usuario->id,

            'fecha_calculo' =>
                now(),
        ]);

        $tipoCosto = TipoCosto::query()
            ->where('activo', true)
            ->firstOrFail();

        $moneda = Moneda::query()
            ->where('activo', true)
            ->firstOrFail();

        CostoEquipo::create([
            'equipo_id' =>
                $equipo->id,

            'tipo_costo_id' =>
                $tipoCosto->id,

            'moneda_id' =>
                $moneda->id,

            'tipo_cambio_id' =>
                null,

            'monto_origen' =>
                125,

            'monto_bob' =>
                125,

            'fecha_costo' =>
                now()->toDateString(),

            'referencia' =>
                'COSTO-POST-001',

            'registrado_por_id' =>
                $usuario->id,

            'descripcion' =>
                'Costo posterior a incorporación.',
        ]);

        $resultado =
            app(CostoRealEquipoService::class)
                ->calcular($equipo->id);

        $this->assertSame(
            'HISTORIAL_UNIDAD',
            $resultado['fuente_base']
        );

        $this->assertSame(
            4600.0,
            $resultado['costo_base']
        );

        $this->assertSame(
            125.0,
            $resultado['costos_posteriores']
        );

        $this->assertSame(
            4725.0,
            $resultado['costo_total']
        );

        $this->assertTrue(
            $resultado['completo']
        );
    }

    public function test_snapshot_solo_se_usa_como_fallback_legacy(): void
    {
        $equipo = $this->crearEquipoConSnapshot(4300);

        $resultado =
            app(CostoRealEquipoService::class)
                ->calcular($equipo->id);

        $this->assertSame(
            'SNAPSHOT_PRECIO_LEGACY',
            $resultado['fuente_base']
        );

        $this->assertSame(
            4300.0,
            $resultado['costo_total']
        );
    }

    private function crearEquipoConSnapshot(
        float $costoSnapshot
    ): Equipo {
        $categoria = CategoriaProducto::create([
            'codigo' =>
                'CAT-' . Str::upper(Str::random(8)),

            'nombre' =>
                'Categoria costo real',

            'descripcion' =>
                null,

            'activo' =>
                true,
        ]);

        $almacen = Almacen::create([
            'codigo' =>
                'ALM-' . Str::upper(Str::random(8)),

            'nombre' =>
                'Almacen prueba costo',

            'ciudad' =>
                'Oruro',

            'direccion' =>
                null,

            'principal' =>
                true,

            'activo' =>
                true,
        ]);

        $estado = EstadoEquipo::create([
            'codigo' =>
                'EST-' . Str::upper(Str::random(8)),

            'nombre' =>
                'Estado prueba costo',

            'descripcion' =>
                null,

            'es_final' =>
                false,

            'orden' =>
                1,

            'activo' =>
                true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'marca_id' =>
                null,

            'codigo' =>
                'PROD-' . Str::uuid(),

            'nombre' =>
                'Laptop costo real',

            'modelo' =>
                'TEST',

            'descripcion' =>
                null,

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);

        $equipo = Equipo::create([
            'producto_id' =>
                $producto->id,

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estado->id,

            'condicion_fisica_id' =>
                null,

            'codigo_interno' =>
                'EQ-' . Str::uuid(),

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
                $costoSnapshot,

            'precio_sugerido' =>
                $costoSnapshot + 500,

            'precio_publico' =>
                $costoSnapshot + 500,

            'precio_minimo_autorizado' =>
                null,

            'vigente_desde' =>
                now(),

            'vigente_hasta' =>
                null,

            'vigente' =>
                true,

            'aprobado_por_id' =>
                null,

            'observacion' =>
                'Snapshot legacy de prueba.',
        ]);

        return $equipo;
    }
}
