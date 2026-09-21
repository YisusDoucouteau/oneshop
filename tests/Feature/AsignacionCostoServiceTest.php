<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\CostoLote;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\TipoCosto;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\AsignacionCostoService;
use App\Services\LoteService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionCostoServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private $detalle;
    private $lote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario =
            User::factory()->create([
                'activo' => true,
            ]);

        $rol =
            Rol::query()
                ->where('codigo', 'ADMIN_OPERATIVO')
                ->firstOrFail();

        $this->usuario
            ->roles()
            ->attach($rol->id);

        $proveedor =
            Proveedor::create([
                'nombre' =>
                    'Proveedor Prorrateo Test',

                'pais' =>
                    'Estados Unidos',

                'ciudad' =>
                    'Miami',

                'activo' =>
                    true,
            ]);

        $categoria =
            CategoriaProducto::query()
                ->where('codigo', 'LAPTOP')
                ->firstOrFail();

        $marca =
            Marca::create([
                'nombre' =>
                    'Marca Prorrateo Test',

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
                    'PRORRATEO-TEST-001',

                'nombre' =>
                    'Laptop Prorrateo',

                'modelo' =>
                    'TEST',

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        $loteService =
            app(LoteService::class);

        $this->lote =
            $loteService->crearLote(
                $this->usuario->id,
                [
                    'proveedor_id' =>
                        $proveedor->id,

                    'codigo' =>
                        'LOTE-PRORRATEO-001',

                    'referencia_compra' =>
                        'REF-PRORRATEO',

                    'origen' =>
                        'Miami',
                ]
            );

        $this->detalle =
            $loteService->agregarDetalle(
                $this->usuario->id,
                $this->lote->id,
                [
                    'producto_id' =>
                        $producto->id,

                    'cantidad_esperada' =>
                        3,
                ]
            );

        $this->detalle->update([
            'costo_unitario_bob' =>
                3500,
        ]);

        app(UnidadAdquiridaService::class)
            ->registrarLlegadaCochabamba(
                $this->usuario->id,
                $this->detalle->id,
                3,
                '2026-09-01 10:00:00'
            );
    }

    public function test_distribuye_un_costo_exactamente_entre_las_unidades(): void
    {
        $costo =
            $this->crearCostoLote(100);

        app(AsignacionCostoService::class)
            ->distribuirPorUnidad($costo);

        $asignaciones =
            $costo
                ->asignacionesUnidades()
                ->orderBy('unidad_adquirida_id')
                ->get();

        $this->assertCount(
            3,
            $asignaciones
        );

        $montos =
            $asignaciones
                ->map(
                    fn ($asignacion) =>
                        $asignacion->montoFinal()
                )
                ->values();

        $this->assertEqualsWithDelta(
            33.34,
            $montos[0],
            0.001
        );

        $this->assertEqualsWithDelta(
            33.33,
            $montos[1],
            0.001
        );

        $this->assertEqualsWithDelta(
            33.33,
            $montos[2],
            0.001
        );

        $this->assertEqualsWithDelta(
            100.00,
            $montos->sum(),
            0.001
        );
    }

    public function test_redistribuir_reemplaza_asignaciones_sin_duplicarlas(): void
    {
        $costo =
            $this->crearCostoLote(100);

        $servicio =
            app(AsignacionCostoService::class);

        $servicio
            ->distribuirPorUnidad($costo);

        $costo->update([
            'monto_origen' =>
                120,

            'monto_bob' =>
                120,
        ]);

        $servicio
            ->redistribuirCosto(
                $costo->fresh()
            );

        $asignaciones =
            $costo
                ->asignacionesUnidades()
                ->get();

        $this->assertCount(
            3,
            $asignaciones
        );

        $total =
            $asignaciones
                ->sum(
                    fn ($asignacion) =>
                        $asignacion->montoFinal()
                );

        $this->assertEqualsWithDelta(
            120.00,
            $total,
            0.001
        );
    }

    public function test_no_prorratea_sobre_unidad_anulada(): void
    {
        $unidadAnulada =
            UnidadAdquirida::query()
                ->where(
                    'detalle_lote_id',
                    $this->detalle->id
                )
                ->orderBy('id')
                ->firstOrFail();

        $unidadAnulada->update([
            'estado' =>
                UnidadAdquirida::ESTADO_ANULADA,
        ]);

        $costo =
            $this->crearCostoLote(100);

        app(AsignacionCostoService::class)
            ->distribuirPorUnidad($costo);

        $asignaciones =
            $costo
                ->asignacionesUnidades()
                ->orderBy('unidad_adquirida_id')
                ->get();

        $this->assertCount(
            2,
            $asignaciones
        );

        $this->assertFalse(
            $asignaciones
                ->pluck('unidad_adquirida_id')
                ->contains($unidadAnulada->id)
        );

        foreach ($asignaciones as $asignacion) {
            $this->assertEqualsWithDelta(
                50.00,
                $asignacion->montoFinal(),
                0.001
            );
        }
    }

    private function crearCostoLote(
        float $monto
    ): CostoLote {

        $tipoCosto =
            TipoCosto::query()
                ->where(
                    'codigo',
                    'FLETE_INTERNACIONAL'
                )
                ->firstOrFail();

        $moneda =
            Moneda::query()
                ->where(
                    'codigo',
                    'BOB'
                )
                ->firstOrFail();

        return CostoLote::create([
            'lote_id' =>
                $this->lote->id,

            'tipo_costo_id' =>
                $tipoCosto->id,

            'moneda_id' =>
                $moneda->id,

            'tipo_cambio_id' =>
                null,

            'monto_origen' =>
                $monto,

            'monto_bob' =>
                $monto,

            'fecha_costo' =>
                '2026-09-01',

            'referencia' =>
                'PRORRATEO-TEST',

            'registrado_por_id' =>
                $this->usuario->id,

            'observacion' =>
                'Costo general para prueba de prorrateo.',

            'estado' =>
                'ACTIVO',
        ]);
    }
}
