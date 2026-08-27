<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\EnvioImportacionUnidad;
use App\Models\IncidenciaLogisticaImportacion;
use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\EnvioImportacionService;
use App\Services\IncidenciaLogisticaImportacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidenciaLogisticaImportacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioOperativo;


    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();


        /*
        |--------------------------------------------------------------------------
        | Usuario autorizado
        |--------------------------------------------------------------------------
        */

        $this->usuarioOperativo =
            User::factory()->create([
                'activo' =>
                true,
            ]);


        $rolOperativo =
            Rol::query()
            ->where(
                'codigo',
                'ADMIN_OPERATIVO'
            )
            ->firstOrFail();


        /*
         * Garantizamos que el permiso exista
         * incluso si el seeder cambia posteriormente.
         */
        $permisoImportacion =
            Permiso::query()
            ->firstOrCreate(
                [
                    'codigo' =>
                    'importacion.gestionar',
                ],
                [
                    'nombre' =>
                    'Gestionar importaciones',

                    'descripcion' =>
                    null,

                    'activo' =>
                    true,
                ]
            );


        $rolOperativo
            ->permisos()
            ->syncWithoutDetaching([
                $permisoImportacion->id,
            ]);


        $this->usuarioOperativo
            ->roles()
            ->attach(
                $rolOperativo->id
            );
    }


    /**
     * Construye una unidad que:
     *
     * Cochabamba
     *      ↓
     * LISTA_ENVIO
     *      ↓
     * DESPACHADA
     *      ↓
     * llega a Oruro con INCIDENCIA.
     */
    private function crearDetalleConIncidencia(
        string $codigoEnvio
    ): EnvioImportacionUnidad {

        $envioService =
            app(
                EnvioImportacionService::class
            );


        $envio =
            $envioService
            ->crearBorrador(
                $this->usuarioOperativo->id,
                [
                    'codigo' =>
                    $codigoEnvio,

                    'cantidad_bultos' =>
                    1,
                ]
            );


        $categoria =
            CategoriaProducto::query()
            ->where(
                'codigo',
                'LAPTOP'
            )
            ->first();


        if (!$categoria) {
            $categoria =
                CategoriaProducto::create([
                    'codigo' =>
                    'LAPTOP',

                    'nombre' =>
                    'Laptops',

                    'activo' =>
                    true,
                ]);
        }


        $producto =
            Producto::create([
                'categoria_producto_id' =>
                $categoria->id,

                'codigo' =>
                'INC-PROD-' . uniqid(),

                'nombre' =>
                'Laptop prueba incidencia logística',

                'modelo' =>
                'Modelo Test',

                'es_serializado' =>
                true,

                'activo' =>
                true,
            ]);


        $unidad =
            UnidadAdquirida::create([
                'producto_id' =>
                $producto->id,

                'almacen_actual_id' =>
                $envio->almacen_origen_id,

                'estado' =>
                UnidadAdquirida::ESTADO_LISTA_ENVIO,

                'serial_fabricante' =>
                'INC-SERIAL-' . uniqid(),

                'enciende' =>
                true,

                'tiene_sistema_operativo' =>
                true,

                'tiene_cargador' =>
                true,

                'requiere_servicio' =>
                false,
            ]);


        $detalle =
            $envioService
            ->agregarUnidad(
                $this->usuarioOperativo->id,
                $envio->id,
                $unidad->id
            );


        $envioService
            ->marcarPreparado(
                $this->usuarioOperativo->id,
                $envio->id
            );


        $envioService
            ->marcarDespachado(
                $this->usuarioOperativo->id,
                $envio->id
            );


        $envioService
            ->registrarIncidenciaRecepcion(
                $this->usuarioOperativo->id,
                $envio->id,
                $unidad->id,
                'El equipo llegó con daño visible durante el transporte.'
            );


        return $detalle->fresh();
    }


    public function test_gestiona_ciclo_completo_de_incidencia_logistica(): void
    {
        $detalle =
            $this->crearDetalleConIncidencia(
                'ENV-INC-GESTION-001'
            );


        $servicio =
            app(
                IncidenciaLogisticaImportacionService::class
            );


        /*
        |--------------------------------------------------------------------------
        | ABIERTA
        |--------------------------------------------------------------------------
        */

        $incidencia =
            $servicio->abrirIncidencia(
                $this->usuarioOperativo->id,
                $detalle->id,
                [
                    'tipo' =>
                    'DANIO_TRANSPORTE',

                    'descripcion' =>
                    'Se detectó un golpe en la carcasa al abrir el paquete.',
                ]
            );


        $this->assertEquals(
            IncidenciaLogisticaImportacion::ESTADO_ABIERTA,
            $incidencia->estado
        );


        $this->assertEquals(
            $detalle->id,
            $incidencia
                ->envio_importacion_unidad_id
        );


        $this->assertEquals(
            $this->usuarioOperativo->id,
            $incidencia->abierta_por_id
        );


        $this->assertNotNull(
            $incidencia->fecha_apertura
        );


        $this->assertNull(
            $incidencia->fecha_resolucion
        );


        /*
        |--------------------------------------------------------------------------
        | EN_GESTION
        |--------------------------------------------------------------------------
        */

        $incidencia =
            $servicio->iniciarGestion(
                $this->usuarioOperativo->id,
                $incidencia->id
            );


        $this->assertEquals(
            IncidenciaLogisticaImportacion::ESTADO_EN_GESTION,
            $incidencia->estado
        );


        /*
        |--------------------------------------------------------------------------
        | RESUELTA
        |--------------------------------------------------------------------------
        |
        | RESULTADO_PRUEBA se usa solamente para comprobar
        | el mecanismo. Todavía no estamos definiendo el
        | catálogo oficial de resultados de OneShop.
        |
        */

        $incidencia =
            $servicio->resolverIncidencia(
                $this->usuarioOperativo->id,
                $incidencia->id,
                [
                    'resultado' =>
                    'RESULTADO_PRUEBA',

                    'detalle_resolucion' =>
                    'La incidencia fue revisada y cerrada para efectos de la prueba.',
                ]
            );


        $this->assertEquals(
            IncidenciaLogisticaImportacion::ESTADO_RESUELTA,
            $incidencia->estado
        );


        $this->assertEquals(
            'RESULTADO_PRUEBA',
            $incidencia->resultado
        );


        $this->assertEquals(
            $this->usuarioOperativo->id,
            $incidencia->resuelta_por_id
        );


        $this->assertNotNull(
            $incidencia->fecha_resolucion
        );


        $this->assertDatabaseHas(
            'incidencias_logisticas_importacion',
            [
                'id' =>
                $incidencia->id,

                'envio_importacion_unidad_id' =>
                $detalle->id,

                'estado' =>
                IncidenciaLogisticaImportacion::ESTADO_RESUELTA,

                'resultado' =>
                'RESULTADO_PRUEBA',
            ]
        );


        /*
         * Resolver la gestión administrativa NO debe
         * alterar por sí sola la recepción logística.
         */
        $detalle->refresh();


        $this->assertEquals(
            EnvioImportacionUnidad::ESTADO_INCIDENCIA,
            $detalle->estado_recepcion
        );
    }


    public function test_no_permite_dos_incidencias_activas_para_la_misma_unidad(): void
    {
        $detalle =
            $this->crearDetalleConIncidencia(
                'ENV-INC-DUP-001'
            );


        $servicio =
            app(
                IncidenciaLogisticaImportacionService::class
            );


        $servicio->abrirIncidencia(
            $this->usuarioOperativo->id,
            $detalle->id,
            [
                'tipo' =>
                'DANIO_TRANSPORTE',

                'descripcion' =>
                'Primera incidencia activa.',
            ]
        );


        try {

            $servicio->abrirIncidencia(
                $this->usuarioOperativo->id,
                $detalle->id,
                [
                    'tipo' =>
                    'OTRA_INCIDENCIA',

                    'descripcion' =>
                    'Intento de abrir una segunda incidencia activa.',
                ]
            );


            $this->fail(
                'Se esperaba rechazo porque la unidad ya tiene una incidencia activa.'
            );
        } catch (
            ReglaNegocioException $exception
        ) {

            $this->assertStringContainsString(
                'incidencia logística activa',
                $exception->getMessage()
            );
        }


        $this->assertSame(
            1,
            IncidenciaLogisticaImportacion::query()
                ->where(
                    'envio_importacion_unidad_id',
                    $detalle->id
                )
                ->whereIn(
                    'estado',
                    [
                        IncidenciaLogisticaImportacion::ESTADO_ABIERTA,
                        IncidenciaLogisticaImportacion::ESTADO_EN_GESTION,
                    ]
                )
                ->count()
        );
    }
    public function test_no_permite_resolver_incidencia_directamente_desde_abierta(): void
    {
        $detalle =
            $this->crearDetalleConIncidencia(
                'ENV-INC-DIRECTA-001'
            );


        $servicio =
            app(
                IncidenciaLogisticaImportacionService::class
            );


        /*
     * La incidencia nace ABIERTA.
     */
        $incidencia =
            $servicio->abrirIncidencia(
                $this->usuarioOperativo->id,
                $detalle->id,
                [
                    'tipo' =>
                    'DANIO_TRANSPORTE',

                    'descripcion' =>
                    'Equipo recibido con daño visible.',
                ]
            );


        $this->assertEquals(
            IncidenciaLogisticaImportacion::ESTADO_ABIERTA,
            $incidencia->estado
        );


        /*
     * Intentamos saltar directamente:
     *
     * ABIERTA -> RESUELTA
     *
     * Debe rechazarse.
     */
        try {

            $servicio->resolverIncidencia(
                $this->usuarioOperativo->id,
                $incidencia->id,
                [
                    'resultado' =>
                    'RESULTADO_PRUEBA',

                    'detalle_resolucion' =>
                    'Intento de resolver sin iniciar gestión.',
                ]
            );


            $this->fail(
                'Se esperaba rechazo porque la incidencia todavía se encuentra ABIERTA.'
            );
        } catch (
            ReglaNegocioException $exception
        ) {

            $this->assertStringContainsString(
                'EN_GESTION',
                $exception->getMessage()
            );
        }


        /*
     * Debe continuar ABIERTA.
     */
        $incidencia->refresh();


        $this->assertEquals(
            IncidenciaLogisticaImportacion::ESTADO_ABIERTA,
            $incidencia->estado
        );


        $this->assertNull(
            $incidencia->fecha_resolucion
        );


        $this->assertNull(
            $incidencia->resuelta_por_id
        );


        $this->assertNull(
            $incidencia->resultado
        );
    }


    public function test_no_permite_resolver_dos_veces_la_misma_incidencia(): void
    {
        $detalle =
            $this->crearDetalleConIncidencia(
                'ENV-INC-RESUELTA-001'
            );


        $servicio =
            app(
                IncidenciaLogisticaImportacionService::class
            );


        /*
     * ABIERTA
     */
        $incidencia =
            $servicio->abrirIncidencia(
                $this->usuarioOperativo->id,
                $detalle->id,
                [
                    'tipo' =>
                    'DANIO_TRANSPORTE',

                    'descripcion' =>
                    'Incidencia para comprobar doble resolución.',
                ]
            );


        /*
     * ABIERTA -> EN_GESTION
     */
        $incidencia =
            $servicio->iniciarGestion(
                $this->usuarioOperativo->id,
                $incidencia->id
            );


        /*
     * EN_GESTION -> RESUELTA
     */
        $incidencia =
            $servicio->resolverIncidencia(
                $this->usuarioOperativo->id,
                $incidencia->id,
                [
                    'resultado' =>
                    'RESULTADO_INICIAL',

                    'detalle_resolucion' =>
                    'Primera resolución válida.',
                ]
            );


        $this->assertEquals(
            IncidenciaLogisticaImportacion::ESTADO_RESUELTA,
            $incidencia->estado
        );


        /*
     * Guardamos los datos originales para comprobar
     * que el segundo intento no los sobrescriba.
     */
        $fechaResolucionOriginal =
            $incidencia->fecha_resolucion;

        $usuarioResolucionOriginal =
            $incidencia->resuelta_por_id;


        /*
     * Intentamos:
     *
     * RESUELTA -> RESUELTA
     *
     * Debe rechazarse.
     */
        try {

            $servicio->resolverIncidencia(
                $this->usuarioOperativo->id,
                $incidencia->id,
                [
                    'resultado' =>
                    'RESULTADO_MODIFICADO',

                    'detalle_resolucion' =>
                    'Este segundo intento no debe aplicarse.',
                ]
            );


            $this->fail(
                'Se esperaba rechazo porque la incidencia ya se encuentra RESUELTA.'
            );
        } catch (
            ReglaNegocioException $exception
        ) {

            $this->assertStringContainsString(
                'EN_GESTION',
                $exception->getMessage()
            );
        }


        $incidencia->refresh();


        /*
     * La resolución original debe permanecer intacta.
     */
        $this->assertEquals(
            IncidenciaLogisticaImportacion::ESTADO_RESUELTA,
            $incidencia->estado
        );


        $this->assertEquals(
            'RESULTADO_INICIAL',
            $incidencia->resultado
        );


        $this->assertEquals(
            'Primera resolución válida.',
            $incidencia->detalle_resolucion
        );


        $this->assertEquals(
            $usuarioResolucionOriginal,
            $incidencia->resuelta_por_id
        );


        $this->assertEquals(
            $fechaResolucionOriginal?->format('Y-m-d H:i:s'),
            $incidencia
                ->fecha_resolucion
                ?->format('Y-m-d H:i:s')
        );
    }
}
