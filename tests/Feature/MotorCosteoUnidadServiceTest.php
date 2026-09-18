<?php

namespace Tests\Feature;

use App\Models\AsignacionCostoUnidadAdquirida;
use App\Models\CategoriaProducto;
use App\Models\CostoLote;
use App\Models\IntervencionUnidadAdquirida;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\TipoCosto;
use App\Models\User;
use App\Services\LoteService;
use App\Services\MotorCosteoUnidadService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotorCosteoUnidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Producto $producto;

    private $detalle;

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
                ->where(
                    'codigo',
                    'ADMIN_OPERATIVO'
                )
                ->firstOrFail();

        $this->usuario
            ->roles()
            ->attach(
                $rol->id
            );

        $proveedor =
            Proveedor::create([

                'nombre' => 'Proveedor Costeo Test',

                'pais' => 'Estados Unidos',

                'ciudad' => 'Miami',

                'activo' => true,

            ]);

        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $marca =
            Marca::create([

                'nombre' => 'Dell Costeo Test',

                'activo' => true,

            ]);

        $this->producto =
            Producto::create([

                'categoria_producto_id' => $categoria->id,

                'marca_id' => $marca->id,

                'codigo' => 'COSTEO-TEST-001',

                'nombre' => 'Dell Latitude',

                'modelo' => '5420',

                'es_serializado' => true,

                'activo' => true,

            ]);

        $loteService =
            app(
                LoteService::class
            );

        $lote =
            $loteService
                ->crearLote(
                    $this->usuario->id,
                    [

                        'proveedor_id' => $proveedor->id,

                        'codigo' => 'LOTE-COSTEO-001',

                        'referencia_compra' => 'REF-COSTEO',

                        'origen' => 'Miami',

                    ]
                );

        $this->detalle =
            $loteService
                ->agregarDetalle(
                    $this->usuario->id,
                    $lote->id,
                    [

                        'producto_id' => $this->producto->id,

                        'cantidad_esperada' => 1,

                    ]
                );

        $this->detalle->update([

            'costo_unitario_bob' => 3500,

        ]);

    }

    public function test_mantiene_costos_del_lote_separados_del_costo_de_la_unidad(): void
    {

        $unidadService =
            app(
                UnidadAdquiridaService::class
            );

        $unidad =
            $unidadService
                ->registrarLlegadaCochabamba(
                    $this->usuario->id,
                    $this->detalle->id,
                    1,
                    '2026-08-29 10:00:00'
                )
                ->first();

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

        $costoLote =
            CostoLote::create([

                'lote_id' => $this->detalle->lote_id,

                'tipo_costo_id' => $tipoCosto->id,

                'moneda_id' => $moneda->id,

                'monto_origen' => 250,

                'monto_bob' => 250,

                'fecha_costo' => '2026-08-29',

            ]);

        AsignacionCostoUnidadAdquirida::create([

            'unidad_adquirida_id' => $unidad->id,

            'costo_lote_id' => $costoLote->id,

            'metodo_asignacion' => 'PRORRATEO',

            'base_individual' => 3500,

            'base_total' => 7000,

            'porcentaje' => 50,

            'monto_asignado_bob' => 250,

        ]);

        $resultado =
            app(
                MotorCosteoUnidadService::class
            )
                ->calcularCostoUnidad(
                    $unidad
                );

        $this->assertEquals(
            3500,
            $resultado['costo_compra']
        );

        $this->assertEquals(
            0,
            $resultado['costos_lote']
        );

        $this->assertEquals(
            3500,
            $resultado['costo_total']
        );

    }

    public function test_suma_intervenciones_al_costo_real_de_la_unidad(): void
    {

        $unidad =
            app(
                UnidadAdquiridaService::class
            )
                ->registrarLlegadaCochabamba(
                    $this->usuario->id,
                    $this->detalle->id,
                    1,
                    '2026-08-29 11:00:00'
                )
                ->first();

        IntervencionUnidadAdquirida::create([

            'unidad_adquirida_id' => $unidad->id,

            'tipo' => 'COMPONENTE',

            'origen_componente' => 'COMPRA_EXTERNA',

            'cantidad' => 1,

            'monto_bob' => 180,

            'fecha_inicio' => '2026-08-29 12:00:00',

            'descripcion' => 'Cambio SSD',

        ]);

        $resultado =
            app(
                MotorCosteoUnidadService::class
            )
                ->calcularCostoUnidad(
                    $unidad
                );

        $this->assertEquals(
            180,
            $resultado['intervenciones']
        );

        $this->assertEquals(
            3680,
            $resultado['costo_total']
        );

    }

    public function test_marca_costo_incompleto_si_existe_intervencion_sin_valoracion(): void
    {

        $unidad =
            app(
                UnidadAdquiridaService::class
            )
                ->registrarLlegadaCochabamba(
                    $this->usuario->id,
                    $this->detalle->id,
                    1,
                    '2026-08-29 13:00:00'
                )
                ->first();

        IntervencionUnidadAdquirida::create([

            'unidad_adquirida_id' => $unidad->id,

            'tipo' => 'COMPONENTE',

            'origen_componente' => 'STOCK',

            'cantidad' => 1,

            'monto_bob' => null,

            'fecha_inicio' => '2026-08-29 14:00:00',

            'descripcion' => 'RAM tomada de stock sin valoración',

        ]);

        $resultado =
            app(
                MotorCosteoUnidadService::class
            )
                ->calcularCostoUnidad(
                    $unidad
                );

        $this->assertFalse(
            $resultado['completo']
        );

        $this->assertNotEmpty(
            $resultado['advertencias']
        );

    }
}
