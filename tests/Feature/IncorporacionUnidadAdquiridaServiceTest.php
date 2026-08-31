<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\CondicionFisica;
use App\Models\DetalleLote;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\IncorporacionUnidadAdquiridaService;
use App\Services\LoteService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IncorporacionUnidadAdquiridaServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioOperativo;

    private Producto $producto;

    private Proveedor $proveedor;

    private DetalleLote $detalle;

    private UnidadAdquirida $unidad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        /*
        |--------------------------------------------------------------------------
        | Usuario operativo
        |--------------------------------------------------------------------------
        */

        $this->usuarioOperativo =
            User::factory()->create([
                'activo' => true,
            ]);

        $rolOperativo =
            Rol::query()
                ->where(
                    'codigo',
                    'ADMIN_OPERATIVO'
                )
                ->firstOrFail();

        $this->usuarioOperativo
            ->roles()
            ->attach(
                $rolOperativo->id
            );

        /*
        |--------------------------------------------------------------------------
        | Proveedor
        |--------------------------------------------------------------------------
        */

        $this->proveedor =
            Proveedor::create([
                'nombre' =>
                    'Proveedor Incorporacion Test',

                'pais' =>
                    'Estados Unidos',

                'ciudad' =>
                    'Miami',

                'telefono' =>
                    null,

                'correo' =>
                    null,

                'contacto' =>
                    null,

                'observacion' =>
                    null,

                'activo' =>
                    true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Producto serializado
        |--------------------------------------------------------------------------
        */

        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $marca =
            Marca::create([
                'nombre' =>
                    'Dell Incorporacion Test',

                'descripcion' =>
                    null,

                'activo' =>
                    true,
            ]);

        $this->producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca->id,

                'codigo' =>
                    'INCORPORACION-TEST-P001',

                'nombre' =>
                    'Dell Latitude Incorporacion',

                'modelo' =>
                    '5420',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Lote
        |--------------------------------------------------------------------------
        */

        $loteService =
            app(LoteService::class);

        $lote =
            $loteService->crearLote(
                $this->usuarioOperativo->id,
                [
                    'proveedor_id' =>
                        $this->proveedor->id,

                    'codigo' =>
                        'IMP-INCORPORACION-TEST-001',

                    'referencia_compra' =>
                        'REF-INCORPORACION-001',

                    'origen' =>
                        'Miami, Estados Unidos',

                    'observacion' =>
                        'Lote para pruebas de incorporación a inventario.',
                ]
            );

        $this->detalle =
            $loteService->agregarDetalle(
                $this->usuarioOperativo->id,
                $lote->id,
                [
                    'producto_id' =>
                        $this->producto->id,

                    'cantidad_esperada' =>
                        1,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Llegada a Cochabamba
        |--------------------------------------------------------------------------
        */

        $unidadAdquiridaService =
            app(UnidadAdquiridaService::class);

        $unidades =
            $unidadAdquiridaService
                ->registrarLlegadaCochabamba(
                    $this->usuarioOperativo->id,
                    $this->detalle->id,
                    1,
                    '2026-08-30 09:00:00',
                    'Llegada para prueba de incorporación.'
                );

        $this->unidad =
            $unidades->first();

        $this->assertNotNull(
            $this->unidad
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function obtenerCondicionFisicaId(): int
    {
        $condicion =
            CondicionFisica::query()
                ->where(
                    'activo',
                    true
                )
                ->first();

        if (!$condicion) {
            $this->fail(
                'No existe una condición física activa para ejecutar la prueba.'
            );
        }

        return $condicion->id;
    }

    private function prepararUnidadRecibidaEnOruro(): UnidadAdquirida
    {
        $this->unidad->refresh();

        /*
         * El objetivo de estas pruebas es la incorporación.
         *
         * El flujo de traslado/recepción de importación ya cuenta
         * con sus propias pruebas. Por eso colocamos la unidad en
         * RECIBIDA_ORURO para aislar la responsabilidad de este
         * servicio.
         */

        $this->unidad->estado =
            UnidadAdquirida::ESTADO_RECIBIDA_ORURO;

        /*
         * La incorporación exige que exista un almacén actual.
         * Para la prueba utilizamos el almacén principal de Oruro.
         */

        $almacenOruro =
            Almacen::query()
                ->where(
                    'codigo',
                    'ORURO_PRINCIPAL'
                )
                ->where(
                    'activo',
                    true
                )
                ->firstOrFail();

        $this->unidad->almacen_actual_id =
            $almacenOruro->id;

        $this->unidad->save();

        return $this->unidad->fresh([
            'producto',
            'almacenActual',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pruebas
    |--------------------------------------------------------------------------
    */

    public function test_incorpora_unidad_recibida_en_oruro(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $servicio =
            app(
                IncorporacionUnidadAdquiridaService::class
            );

        $unidad =
            $servicio->incorporar(
                $this->usuarioOperativo->id,
                $unidad->id,
                '1542',
                $this->obtenerCondicionFisicaId(),
                'SN-INCORPORACION-001',
                'Equipo incorporado desde importación.'
            );

        $this->assertNotNull(
            $unidad->equipo_id
        );

        $this->assertInstanceOf(
            Equipo::class,
            $unidad->equipo
        );

        $this->assertSame(
            '1542',
            $unidad
                ->equipo
                ->codigo_interno
        );

        $this->assertSame(
            'SN-INCORPORACION-001',
            $unidad
                ->equipo
                ->serial_fabricante
        );

        $this->assertSame(
            UnidadAdquirida::ESTADO_INCORPORADA,
            $unidad->estado
        );
    }

    public function test_crea_equipo_en_almacen_principal_de_oruro(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $servicio =
            app(
                IncorporacionUnidadAdquiridaService::class
            );

        $unidad =
            $servicio->incorporar(
                $this->usuarioOperativo->id,
                $unidad->id,
                '1543',
                $this->obtenerCondicionFisicaId()
            );

        $almacenOruro =
            Almacen::query()
                ->where(
                    'codigo',
                    'ORURO_PRINCIPAL'
                )
                ->firstOrFail();

        $this->assertSame(
            $almacenOruro->id,
            $unidad
                ->equipo
                ->almacen_actual_id
        );
    }

    public function test_conserva_codigo_de_trazabilidad_de_la_unidad(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $codigoTrazabilidad =
            $unidad->codigo_trazabilidad;

        $servicio =
            app(
                IncorporacionUnidadAdquiridaService::class
            );

        $unidad =
            $servicio->incorporar(
                $this->usuarioOperativo->id,
                $unidad->id,
                '1544',
                $this->obtenerCondicionFisicaId()
            );

        $this->assertSame(
            $codigoTrazabilidad,
            $unidad->codigo_trazabilidad
        );

        $this->assertSame(
            '1544',
            $unidad
                ->equipo
                ->codigo_interno
        );

        $this->assertNotSame(
            $unidad->codigo_trazabilidad,
            $unidad
                ->equipo
                ->codigo_interno
        );
    }

    public function test_equipo_inicia_en_estado_recibido(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $servicio =
            app(
                IncorporacionUnidadAdquiridaService::class
            );

        $unidad =
            $servicio->incorporar(
                $this->usuarioOperativo->id,
                $unidad->id,
                '1545',
                $this->obtenerCondicionFisicaId()
            );

        $this->assertSame(
            'RECIBIDO',
            $unidad
                ->equipo
                ->estadoActual
                ->codigo
        );
    }

    public function test_no_permite_incorporar_unidad_que_no_llego_a_oruro(): void
    {
        $this->unidad->refresh();

        /*
         * La unidad recién creada por el servicio de llegada
         * todavía no está en RECIBIDA_ORURO.
         */

        $this->assertNotSame(
            UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
            $this->unidad->estado
        );

        $servicio =
            app(
                IncorporacionUnidadAdquiridaService::class
            );

        $this->expectException(
            ReglaNegocioException::class
        );

        $servicio->incorporar(
            $this->usuarioOperativo->id,
            $this->unidad->id,
            '1546',
            $this->obtenerCondicionFisicaId()
        );
    }

    public function test_no_permite_incorporar_dos_veces_la_misma_unidad(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $servicio =
            app(
                IncorporacionUnidadAdquiridaService::class
            );

        $servicio->incorporar(
            $this->usuarioOperativo->id,
            $unidad->id,
            '1547',
            $this->obtenerCondicionFisicaId()
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $servicio->incorporar(
            $this->usuarioOperativo->id,
            $unidad->id,
            '1548',
            $this->obtenerCondicionFisicaId()
        );
    }

    public function test_no_permite_codigo_interno_vacio(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $servicio =
            app(
                IncorporacionUnidadAdquiridaService::class
            );

        $this->expectException(
        ValidationException::class
        );

        $servicio->incorporar(
            $this->usuarioOperativo->id,
            $unidad->id,
            '   ',
            $this->obtenerCondicionFisicaId()
        );
    }
}