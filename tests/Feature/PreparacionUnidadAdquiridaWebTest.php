<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\RevisionTecnicaUnidadAdquirida;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\LoteService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreparacionUnidadAdquiridaWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sembrar catálogos y seguridad una sola vez durante migrate:fresh.
     * Evita ejecutar db:seed dentro de la transacción de cada test.
     */
    protected $seed = true;

    private User $usuarioOperativo;
    private UnidadAdquirida $unidad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuarioOperativo = User::factory()->create([
            'activo' => true,
        ]);

        $rolOperativo = Rol::query()
            ->where('codigo', 'ADMIN_OPERATIVO')
            ->firstOrFail();

        $this->usuarioOperativo
            ->roles()
            ->attach($rolOperativo->id);

        $proveedor = Proveedor::create([
            'nombre' => 'Proveedor Preparación Web Test',
            'pais' => 'Estados Unidos',
            'ciudad' => 'Miami',
            'telefono' => null,
            'correo' => null,
            'contacto' => null,
            'observacion' => null,
            'activo' => true,
        ]);

        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $marca = Marca::create([
            'nombre' => 'Lenovo Preparación Web Test',
            'descripcion' => null,
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'PREP-WEB-001',
            'nombre' => 'Lenovo ThinkPad T14',
            'modelo' => 'T14',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        $loteService = app(LoteService::class);

        $lote = $loteService->crearLote(
            $this->usuarioOperativo->id,
            [
                'proveedor_id' => $proveedor->id,
                'codigo' => 'IMP-PREP-WEB-001',
                'referencia_compra' => 'REF-PREP-WEB-001',
                'origen' => 'Miami, Estados Unidos',
                'observacion' => 'Lote para pruebas web de preparación.',
            ]
        );

        $detalle = $loteService->agregarDetalle(
            $this->usuarioOperativo->id,
            $lote->id,
            [
                'producto_id' => $producto->id,
                'cantidad_esperada' => 1,
            ]
        );

        $this->unidad = app(UnidadAdquiridaService::class)
            ->registrarLlegadaCochabamba(
                $this->usuarioOperativo->id,
                $detalle->id,
                1,
                '2026-09-18 08:00:00',
                null,
                [
                    'procesador' => 'Intel Core i5',
                    'ram_gb' => 16,
                    'almacenamiento_gb' => 512,
                    'tipo_almacenamiento' => 'SSD',
                    'grado_recibido' => 'B',
                    'tiene_cargador' => true,
                ]
            )
            ->firstOrFail();
    }

    public function test_lote_ofrece_acceso_directo_a_preparacion_de_la_unidad_recibida(): void
    {
        $lote = $this->unidad
            ->detalleLote
            ->lote;

        $response = $this
            ->actingAs($this->usuarioOperativo)
            ->get(route('importaciones.show', $lote));

        $response
            ->assertOk()
            ->assertSee('Preparar / Revisar')
            ->assertSee(
                route('unidades-adquiridas.show', $this->unidad),
                false
            );
    }

    public function test_detalle_muestra_checklist_e_intervenciones_de_preparacion(): void
    {
        $response = $this
            ->actingAs($this->usuarioOperativo)
            ->get(
                route(
                    'unidades-adquiridas.show',
                    $this->unidad
                )
            );

        $response
            ->assertOk()
            ->assertSee('Preparación y revisión técnica')
            ->assertSee('Checklist funcional')
            ->assertSee('Registrar intervención')
            ->assertSee('Batería')
            ->assertSee('Guardar borrador')
            ->assertSee('Finalizar revisión');
    }

    public function test_revision_web_completa_habilita_unidad_para_envio_y_crea_historial(): void
    {
        $response = $this
            ->actingAs($this->usuarioOperativo)
            ->patchJson(
                route(
                    'unidades-adquiridas.revision',
                    $this->unidad
                ),
                [
                    'procesador' => 'Intel Core i5',
                    'ram_gb' => 16,
                    'almacenamiento_gb' => 512,
                    'tipo_almacenamiento' => 'SSD',
                    'sistema_operativo' => 'Windows 11 Pro',
                    'bateria_porcentaje' => 87,
                    'grado_final' => 'A',
                    'enciende' => true,
                    'tiene_sistema_operativo' => true,
                    'tiene_cargador' => true,
                    'requiere_servicio' => false,
                    'checklist_tecnico' => $this->checklistTodoOk(),
                    'observacion_revision' => 'Unidad verificada completamente.',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath(
                'unidad.estado',
                UnidadAdquirida::ESTADO_LISTA_ENVIO
            );

        $this->assertDatabaseHas(
            'unidades_adquiridas',
            [
                'id' => $this->unidad->id,
                'estado' => UnidadAdquirida::ESTADO_LISTA_ENVIO,
                'resultado_revision' => RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA,
                'grado_final' => 'A',
                'bateria_porcentaje' => 87,
            ]
        );

        $this->assertDatabaseHas(
            'revisiones_tecnicas_unidades_adquiridas',
            [
                'unidad_adquirida_id' => $this->unidad->id,
                'usuario_id' => $this->usuarioOperativo->id,
                'resultado' => RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA,
            ]
        );
    }


    public function test_guardar_borrador_no_crea_historial_y_deja_revision_en_curso(): void
    {
        $response = $this
            ->actingAs($this->usuarioOperativo)
            ->patchJson(
                route('unidades-adquiridas.revision', $this->unidad),
                [
                    'accion' => 'BORRADOR',
                    'bateria_porcentaje' => 72,
                    'enciende' => true,
                    'tiene_sistema_operativo' => true,
                    'tiene_cargador' => true,
                    'checklist_tecnico' => [
                        'wifi' => RevisionTecnicaUnidadAdquirida::CHECK_OK,
                        'teclado' => RevisionTecnicaUnidadAdquirida::CHECK_OK,
                    ],
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath(
                'unidad.estado',
                UnidadAdquirida::ESTADO_EN_REVISION
            );

        $this->assertDatabaseHas('unidades_adquiridas', [
            'id' => $this->unidad->id,
            'estado' => UnidadAdquirida::ESTADO_EN_REVISION,
            'bateria_porcentaje' => 72,
        ]);

        $this->assertDatabaseCount(
            'revisiones_tecnicas_unidades_adquiridas',
            0
        );
    }

    public function test_finalizar_revision_despues_de_borrador_crea_un_solo_snapshot(): void
    {
        $this
            ->actingAs($this->usuarioOperativo)
            ->patchJson(
                route('unidades-adquiridas.revision', $this->unidad),
                [
                    'accion' => 'BORRADOR',
                    'bateria_porcentaje' => 90,
                    'grado_final' => 'A',
                    'enciende' => true,
                    'tiene_sistema_operativo' => true,
                    'tiene_cargador' => true,
                    'requiere_servicio' => false,
                    'checklist_tecnico' => $this->checklistTodoOk(),
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'revisiones_tecnicas_unidades_adquiridas',
            0
        );

        $response = $this
            ->actingAs($this->usuarioOperativo)
            ->patchJson(
                route('unidades-adquiridas.revision', $this->unidad),
                [
                    'accion' => 'FINALIZAR',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'unidad.estado',
                UnidadAdquirida::ESTADO_LISTA_ENVIO
            );

        $this->assertDatabaseCount(
            'revisiones_tecnicas_unidades_adquiridas',
            1
        );

        $this->assertDatabaseHas(
            'revisiones_tecnicas_unidades_adquiridas',
            [
                'unidad_adquirida_id' => $this->unidad->id,
                'resultado' => RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA,
            ]
        );
    }

    public function test_unidad_lista_puede_reabrir_preparacion_con_motivo_sin_crear_revision_falsa(): void
    {
        $this
            ->actingAs($this->usuarioOperativo)
            ->patchJson(
                route('unidades-adquiridas.revision', $this->unidad),
                [
                    'accion' => 'FINALIZAR',
                    'bateria_porcentaje' => 88,
                    'grado_final' => 'A',
                    'enciende' => true,
                    'tiene_sistema_operativo' => true,
                    'tiene_cargador' => true,
                    'requiere_servicio' => false,
                    'checklist_tecnico' => $this->checklistTodoOk(),
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'revisiones_tecnicas_unidades_adquiridas',
            1
        );

        $response = $this
            ->actingAs($this->usuarioOperativo)
            ->patchJson(
                route(
                    'unidades-adquiridas.reabrir-preparacion',
                    $this->unidad
                ),
                [
                    'motivo' => 'Al embalar se detectó una falla nueva de batería.',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'unidad.estado',
                UnidadAdquirida::ESTADO_EN_PREPARACION
            );

        $this->assertDatabaseHas('unidades_adquiridas', [
            'id' => $this->unidad->id,
            'estado' => UnidadAdquirida::ESTADO_EN_PREPARACION,
            'requiere_servicio' => 1,
            'servicio_requerido' => 'Al embalar se detectó una falla nueva de batería.',
        ]);

        $this->assertDatabaseCount(
            'revisiones_tecnicas_unidades_adquiridas',
            1
        );

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'REABRIR_PREPARACION',
            'entidad' => 'unidad_adquirida',
            'entidad_id' => $this->unidad->id,
        ]);

        $this
            ->actingAs($this->usuarioOperativo)
            ->get(route('unidades-adquiridas.show', $this->unidad))
            ->assertOk()
            ->assertSee('Preparación reabierta')
            ->assertSee('Al embalar se detectó una falla nueva de batería.');
    }

    private function checklistTodoOk(): array
    {
        return array_fill_keys(
            array_keys(RevisionTecnicaUnidadAdquirida::CHECKLIST),
            RevisionTecnicaUnidadAdquirida::CHECK_OK
        );
    }
}
