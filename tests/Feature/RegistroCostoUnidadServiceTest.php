<?php

namespace Tests\Feature;

use App\Models\AsignacionCostoUnidadAdquirida;
use App\Models\CategoriaProducto;
use App\Models\CostoLote;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\TipoCosto;
use App\Models\User;
use App\Services\LoteService;
use App\Services\RegistroCostoUnidadService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroCostoUnidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Producto $producto;

    private $detalle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        /*
        |--------------------------------------------------------------------------
        | Usuario operativo
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Proveedor
        |--------------------------------------------------------------------------
        */

        $proveedor =
            Proveedor::create([

                'nombre' => 'Proveedor Historial Costeo Test',

                'pais' => 'Estados Unidos',

                'ciudad' => 'Miami',

                'activo' => true,

            ]);

        /*
        |--------------------------------------------------------------------------
        | Categoría
        |--------------------------------------------------------------------------
        */

        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Marca
        |--------------------------------------------------------------------------
        */

        $marca =
            Marca::create([

                'nombre' => 'Dell Historial Test',

                'activo' => true,

            ]);

        /*
        |--------------------------------------------------------------------------
        | Producto
        |--------------------------------------------------------------------------
        */

        $this->producto =
            Producto::create([

                'categoria_producto_id' => $categoria->id,

                'marca_id' => $marca->id,

                'codigo' => 'HISTORIAL-TEST-001',

                'nombre' => 'Dell Latitude',

                'modelo' => '5420',

                'es_serializado' => true,

                'activo' => true,

            ]);

        /*
        |--------------------------------------------------------------------------
        | Lote
        |--------------------------------------------------------------------------
        */

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

                        'codigo' => 'LOTE-HISTORIAL-001',

                        'referencia_compra' => 'REF-HISTORIAL',

                        'origen' => 'Miami',

                    ]
                );

        /*
        |--------------------------------------------------------------------------
        | Detalle del lote
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Costo de compra
        |--------------------------------------------------------------------------
        */

        $this->detalle->update([

            'costo_unitario_bob' => 3500,

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Test 1
    |--------------------------------------------------------------------------
    |
    | Debe guardar correctamente el primer cálculo
    | realizado sobre una unidad.
    |
    */

    public function test_guarda_calculo_inicial_en_historial(): void
    {
        $unidad =
            app(
                UnidadAdquiridaService::class
            )
                ->registrarLlegadaCochabamba(
                    $this->usuario->id,
                    $this->detalle->id,
                    1,
                    '2026-08-30 10:00:00'
                )
                ->first();

        $servicio =
            app(
                RegistroCostoUnidadService::class
            );

        $historial =
            $servicio
                ->registrar(
                    $unidad,
                    $this->usuario->id
                );

        /*
        |--------------------------------------------------------------------------
        | Verificar registro
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas(
            'historial_costos_unidades',
            [

                'id' => $historial->id,

                'unidad_adquirida_id' => $unidad->id,

                'costo_compra' => 3500,

                'costos_lote' => 0,

                'intervenciones' => 0,

                'costo_total' => 3500,

                'completo' => 1,

                'calculado_por_id' => $this->usuario->id,

            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Verificar relación
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            $unidad->id,
            $historial
                ->unidadAdquirida
                ->id
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Test 2
    |--------------------------------------------------------------------------
    |
    | Un nuevo cálculo debe generar una nueva fotografía
    | sin modificar el cálculo anterior.
    |
    */

    public function test_conserva_calculos_anteriores_al_generar_un_nuevo_historial(): void
    {
        $unidad =
            app(
                UnidadAdquiridaService::class
            )
                ->registrarLlegadaCochabamba(
                    $this->usuario->id,
                    $this->detalle->id,
                    1,
                    '2026-08-30 11:00:00'
                )
                ->first();

        $servicio =
            app(
                RegistroCostoUnidadService::class
            );

        /*
        |--------------------------------------------------------------------------
        | Primer cálculo
        |--------------------------------------------------------------------------
        */

        $primerHistorial =
            $servicio
                ->registrar(
                    $unidad,
                    $this->usuario->id
                );

        /*
        |--------------------------------------------------------------------------
        | Crear costo logístico
        |--------------------------------------------------------------------------
        */

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

                'monto_origen' => 200,

                'monto_bob' => 200,

                'fecha_costo' => '2026-08-30',

            ]);

        /*
        |--------------------------------------------------------------------------
        | Asignar costo directamente a la unidad adquirida
        |--------------------------------------------------------------------------
        |
        | Importante:
        |
        | En esta etapa la unidad todavía puede no tener equipo_id,
        | porque todavía se encuentra en el flujo de importación.
        |
        | Por eso la asignación se realiza mediante:
        |
        | unidad_adquirida_id
        |
        */

        AsignacionCostoUnidadAdquirida::create([

            'costo_lote_id' => $costoLote->id,

            'unidad_adquirida_id' => $unidad->id,

            'metodo_asignacion' => 'PRORRATEO',

            'base_individual' => 3500,

            'base_total' => 3500,

            'porcentaje' => 100,

            'monto_asignado_bob' => 200,

            'ajuste_redondeo_bob' => 0,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Segundo cálculo
        |--------------------------------------------------------------------------
        */

        $segundoHistorial =
            $servicio
                ->registrar(
                    $unidad,
                    $this->usuario->id
                );

        /*
        |--------------------------------------------------------------------------
        | Deben ser registros diferentes
        |--------------------------------------------------------------------------
        */

        $this->assertNotEquals(
            $primerHistorial->id,
            $segundoHistorial->id
        );

        /*
        |--------------------------------------------------------------------------
        | El primer cálculo permanece intacto
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            3500,
            $primerHistorial
                ->fresh()
                ->costo_total
        );

        /*
        |--------------------------------------------------------------------------
        | El segundo cálculo conserva separado el costo logístico del lote
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            3500,
            $segundoHistorial
                ->fresh()
                ->costo_total
        );

        $this->assertEquals(
            0,
            $segundoHistorial
                ->fresh()
                ->costos_lote
        );

        /*
        |--------------------------------------------------------------------------
        | Deben existir dos fotografías
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseCount(
            'historial_costos_unidades',
            2
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Test 3
    |--------------------------------------------------------------------------
    |
    | Verifica que el servicio pueda obtener:
    |
    | - último cálculo
    | - historial completo
    |
    */

    public function test_puede_obtener_ultimo_calculo_y_historial_completo(): void
    {
        $unidad =
            app(
                UnidadAdquiridaService::class
            )
                ->registrarLlegadaCochabamba(
                    $this->usuario->id,
                    $this->detalle->id,
                    1,
                    '2026-08-30 12:00:00'
                )
                ->first();

        $servicio =
            app(
                RegistroCostoUnidadService::class
            );

        /*
        |--------------------------------------------------------------------------
        | Primer cálculo
        |--------------------------------------------------------------------------
        */

        $primerHistorial =
            $servicio
                ->registrar(
                    $unidad,
                    $this->usuario->id
                );

        /*
        |--------------------------------------------------------------------------
        | Segundo cálculo
        |--------------------------------------------------------------------------
        */

        $segundoHistorial =
            $servicio
                ->registrar(
                    $unidad,
                    $this->usuario->id
                );

        /*
        |--------------------------------------------------------------------------
        | Último cálculo
        |--------------------------------------------------------------------------
        */

        $ultimo =
            $servicio
                ->ultimo(
                    $unidad
                );

        /*
        |--------------------------------------------------------------------------
        | Historial completo
        |--------------------------------------------------------------------------
        */

        $historial =
            $servicio
                ->historial(
                    $unidad
                );

        /*
        |--------------------------------------------------------------------------
        | Validaciones
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            $segundoHistorial->id,
            $ultimo->id
        );

        $this->assertCount(
            2,
            $historial
        );

        $this->assertEquals(
            $segundoHistorial->id,
            $historial->first()->id
        );

        $this->assertEquals(
            $primerHistorial->id,
            $historial->last()->id
        );

    }
}
