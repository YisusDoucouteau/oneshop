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
        | CategorÃ­a
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
    | Debe guardar correctamente el primer cÃ¡lculo
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
        | Verificar relaciÃ³n
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
    | Un nuevo cÃ¡lculo debe generar una nueva fotografÃ­a
    | sin modificar el cÃ¡lculo anterior.
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
        | Primer cÃ¡lculo
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
        | Crear costo logÃ­stico
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

                'estado' => 'ACTIVO',

            ]);

        /*
        |--------------------------------------------------------------------------
        | Asignar costo directamente a la unidad adquirida
        |--------------------------------------------------------------------------
        |
        | Importante:
        |
        | En esta etapa la unidad todavÃ­a puede no tener equipo_id,
        | porque todavÃ­a se encuentra en el flujo de importaciÃ³n.
        |
        | Por eso la asignaciÃ³n se realiza mediante:
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
        | Segundo cÃ¡lculo
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
        | El primer cÃ¡lculo permanece intacto
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
        | El segundo cÃ¡lculo incluye el costo logÃ­stico asignado,
        | manteniÃ©ndolo separado dentro del desglose.
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            3700,
            $segundoHistorial
                ->fresh()
                ->costo_total
        );

        $this->assertEquals(
            200,
            $segundoHistorial
                ->fresh()
                ->costos_lote
        );

        /*
        |--------------------------------------------------------------------------
        | Deben existir dos fotografÃ­as
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
    | - Ãºltimo cÃ¡lculo
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
        | Primer cÃ¡lculo
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
        | Segundo cÃ¡lculo
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
        | Ãšltimo cÃ¡lculo
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
