<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\CondicionFisica;
use App\Models\DetalleLote;
use App\Models\Equipo;
use App\Models\EnvioImportacion;
use App\Models\EnvioImportacionUnidad;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\RevisionTecnicaUnidadAdquirida;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\IncorporacionUnidadAdquiridaService;
use App\Services\LoteService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $almacenOruro =
            Almacen::query()
                ->where('codigo', 'ORURO_PRINCIPAL')
                ->where('activo', true)
                ->firstOrFail();

        $this->usuarioOperativo =
            User::factory()->create([
                'activo' => true,
                'almacen_operativo_id' =>
                    $almacenOruro->id,
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
            app(
                LoteService::class
            );

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
            app(
                UnidadAdquiridaService::class
            );

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


    private function prepararUnidadRecibidaEnOruro(bool $envioCerrado = true): UnidadAdquirida
    {
        $this->unidad->refresh();

        /*
         * Para estas pruebas aislamos la responsabilidad
         * del servicio de incorporación.
         *
         * El flujo de traslado y recepción a Oruro
         * tiene sus propias pruebas.
         */

        $this->unidad->estado =
            UnidadAdquirida::ESTADO_RECIBIDA_ORURO;


        /*
         * La unidad debe encontrarse físicamente
         * en el almacén principal de Oruro.
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


        /*
         * La unidad importada debe conservar su contexto logístico.
         * En producción esto lo crea el módulo de envíos; aquí se
         * prepara de forma explícita para aislar la incorporación.
         */

        $almacenCochabamba =
            Almacen::query()
                ->where('codigo', 'COCHABAMBA')
                ->where('activo', true)
                ->firstOrFail();

        $envio = EnvioImportacion::create([
            'codigo' => 'ENV-INCORPORACION-TEST-001',
            'almacen_origen_id' => $almacenCochabamba->id,
            'almacen_destino_id' => $almacenOruro->id,
            'estado' => $envioCerrado
                ? EnvioImportacion::ESTADO_RECIBIDO
                : EnvioImportacion::ESTADO_DESPACHADO,
            'cantidad_bultos' => 1,
            'fecha_despacho' => now()->subHour(),
            'fecha_recepcion' => $envioCerrado
                ? now()
                : null,
        ]);

        EnvioImportacionUnidad::create([
            'envio_importacion_id' => $envio->id,
            'unidad_adquirida_id' => $this->unidad->id,
            'incluye_cargador' => false,
            'estado_recepcion' => $envioCerrado
                ? EnvioImportacionUnidad::ESTADO_RECIBIDA
                : EnvioImportacionUnidad::ESTADO_PENDIENTE,
            'fecha_recepcion' => $envioCerrado
                ? now()
                : null,
            'recibido_por_id' => $envioCerrado
                ? $this->usuarioOperativo->id
                : null,
        ]);

        return $this->unidad->fresh([
            'producto',
            'almacenActual',
            'envioImportacionUnidad.envioImportacion',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Pruebas de incorporación
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


        /*
         * El código interno ahora se genera
         * automáticamente al incorporar.
         */

        $this->assertNotNull(
            $unidad
                ->equipo
                ->codigo_interno
        );

        $this->assertNotSame(
            '',
            trim(
                $unidad
                    ->equipo
                    ->codigo_interno
            )
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
                $this->obtenerCondicionFisicaId()
            );

        $this->assertSame(
            $codigoTrazabilidad,
            $unidad->codigo_trazabilidad
        );


        /*
         * El código de inventario se genera al
         * incorporar y es distinto del código
         * técnico de trazabilidad.
         */

        $this->assertNotNull(
            $unidad
                ->equipo
                ->codigo_interno
        );

        $this->assertNotSame(
            '',
            trim(
                $unidad
                    ->equipo
                    ->codigo_interno
            )
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


    public function test_transfiere_bateria_y_checklist_tecnico_al_equipo_formal(): void
    {
        $unidad = $this->prepararUnidadRecibidaEnOruro();

        $checklist = array_fill_keys(
            array_keys(RevisionTecnicaUnidadAdquirida::CHECKLIST),
            RevisionTecnicaUnidadAdquirida::CHECK_OK
        );

        $unidad->update([
            'bateria_porcentaje' => 84,
            'grado_final' => 'A',
            'resultado_revision' => RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA,
            'checklist_tecnico' => $checklist,
        ]);

        $unidad = app(IncorporacionUnidadAdquiridaService::class)
            ->incorporar(
                $this->usuarioOperativo->id,
                $unidad->id,
                $this->obtenerCondicionFisicaId()
            );

        $especificacion = $unidad->equipo->especificacion;

        $this->assertNotNull($especificacion);
        $this->assertSame(84, $especificacion->bateria_porcentaje);
        $this->assertSame('A', $especificacion->datos_adicionales['grado_final']);
        $this->assertSame(
            RevisionTecnicaUnidadAdquirida::CHECK_OK,
            $especificacion->datos_adicionales['checklist_preparacion']['wifi']
        );
    }


    public function test_no_permite_incorporar_unidad_que_no_llego_a_oruro(): void
    {
        $this->unidad->refresh();

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
            $this->obtenerCondicionFisicaId()
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $servicio->incorporar(
            $this->usuarioOperativo->id,
            $unidad->id,
            $this->obtenerCondicionFisicaId()
        );
    }


    public function test_no_permite_incorporar_antes_de_cerrar_recepcion_del_envio(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro(false);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'recepción del envío debe estar cerrada'
        );

        app(IncorporacionUnidadAdquiridaService::class)
            ->incorporar(
                $this->usuarioOperativo->id,
                $unidad->id,
                $this->obtenerCondicionFisicaId()
            );
    }


    public function test_usuario_de_otra_sede_no_puede_incorporar_en_oruro(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $almacenCochabamba =
            Almacen::query()
                ->where('codigo', 'COCHABAMBA')
                ->where('activo', true)
                ->firstOrFail();

        $usuarioCochabamba =
            User::factory()->create([
                'activo' => true,
                'almacen_operativo_id' =>
                    $almacenCochabamba->id,
            ]);

        $rolOperativo =
            Rol::query()
                ->where('codigo', 'ADMIN_OPERATIVO')
                ->firstOrFail();

        $usuarioCochabamba
            ->roles()
            ->attach($rolOperativo->id);

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'no puede incorporar equipos en el almacén de destino'
        );

        app(IncorporacionUnidadAdquiridaService::class)
            ->incorporar(
                $usuarioCochabamba->id,
                $unidad->id,
                $this->obtenerCondicionFisicaId()
            );
    }


    public function test_exige_condicion_fisica_activa_para_incorporar(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'Debe seleccionar la condición física'
        );

        app(IncorporacionUnidadAdquiridaService::class)
            ->incorporar(
                $this->usuarioOperativo->id,
                $unidad->id,
                null
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Pruebas de integración con costeo
    |--------------------------------------------------------------------------
    */

    public function test_registra_historial_de_costo_al_incorporar(): void
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
                $this->obtenerCondicionFisicaId()
            );

        $this->assertDatabaseHas(
            'historial_costos_unidades',
            [
                'unidad_adquirida_id' =>
                    $unidad->id,

                'calculado_por_id' =>
                    $this->usuarioOperativo->id,
            ]
        );

        $this->assertNotNull(
            $unidad
                ->historialCostos()
                ->latest(
                    'fecha_calculo'
                )
                ->first()
        );
    }


    public function test_conserva_el_historial_de_costo_si_la_incorporacion_es_exitosa(): void
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
                $this->obtenerCondicionFisicaId()
            );

        $historial =
            $unidad
                ->historialCostos()
                ->latest(
                    'fecha_calculo'
                )
                ->first();

        $this->assertNotNull(
            $historial
        );

        $this->assertSame(
            $unidad->id,
            $historial
                ->unidad_adquirida_id
        );

        $this->assertSame(
            $this->usuarioOperativo->id,
            $historial
                ->calculado_por_id
        );

        $this->assertGreaterThanOrEqual(
            0,
            (float)
            $historial
                ->costo_total
        );
    }

    public function test_equipo_incorporado_conserva_acceso_al_costo_historico_de_origen_sin_duplicarlo(): void
    {
        $unidad =
            $this->prepararUnidadRecibidaEnOruro();

        $unidad =
            app(IncorporacionUnidadAdquiridaService::class)
                ->incorporar(
                    $this->usuarioOperativo->id,
                    $unidad->id,
                    $this->obtenerCondicionFisicaId()
                );

        $equipo = Equipo::query()
            ->with([
                'incorporacionUnidad.unidadAdquirida.historialCostos',
            ])
            ->findOrFail($unidad->equipo_id);

        $incorporacion =
            $equipo->incorporacionUnidad;

        $this->assertNotNull(
            $incorporacion
        );

        $this->assertSame(
            $unidad->id,
            $incorporacion
                ->unidad_adquirida_id
        );

        $unidadOrigen =
            $incorporacion
                ->unidadAdquirida;

        $this->assertNotNull(
            $unidadOrigen
        );

        $historial =
            $unidadOrigen
                ->historialCostos()
                ->latest('fecha_calculo')
                ->first();

        $this->assertNotNull(
            $historial
        );

        $this->assertSame(
            $unidadOrigen->id,
            $historial->unidad_adquirida_id
        );

        /*
         * El costo de origen permanece en el historial de la unidad.
         * No se duplica como movimiento en costos_equipos durante
         * la incorporación. Los costos posteriores del equipo
         * corresponden a una etapa distinta.
         */
        $this->assertDatabaseMissing(
            'costos_equipos',
            [
                'equipo_id' =>
                    $equipo->id,
            ]
        );
    }

}