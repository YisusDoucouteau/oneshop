<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\EnvioImportacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use App\Services\EnvioImportacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvioImportacionFase4WebTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $permiso = Permiso::where('codigo', 'importacion.gestionar')->firstOrFail();
        $rol = Rol::where('codigo', 'ADMINISTRADOR')->firstOrFail();
        $rol->permisos()->syncWithoutDetaching([$permiso->id]);

        $this->usuario = User::factory()->create(['activo' => true]);
        $this->usuario->roles()->attach($rol->id);

        Almacen::firstOrCreate(['codigo' => 'COCHABAMBA'], [
            'nombre' => 'Depósito Cochabamba', 'ciudad' => 'Cochabamba', 'principal' => false, 'activo' => true,
        ]);
        Almacen::firstOrCreate(['codigo' => 'ORURO_PRINCIPAL'], [
            'nombre' => 'Tienda Oruro', 'ciudad' => 'Oruro', 'principal' => true, 'activo' => true,
        ]);
    }

    public function test_codigo_de_envio_se_genera_automaticamente_y_correlativo(): void
    {
        $servicio = app(EnvioImportacionService::class);

        $primero = $servicio->crearBorrador($this->usuario->id, ['cantidad_bultos' => 1]);
        $segundo = $servicio->crearBorrador($this->usuario->id, ['cantidad_bultos' => 1]);

        $anio = now()->format('Y');
        $this->assertSame("ENV-{$anio}-001", $primero->codigo);
        $this->assertSame("ENV-{$anio}-002", $segundo->codigo);
    }

    public function test_envio_conserva_cajas_cargadores_y_accesorios(): void
    {
        $envio = app(EnvioImportacionService::class)->crearBorrador($this->usuario->id, [
            'cantidad_bultos' => 2,
            'cantidad_cargadores' => 3,
            'cantidad_accesorios' => 2,
            'detalle_accesorios' => 'Mouse y cable HDMI',
        ]);

        $this->assertDatabaseHas('envios_importacion', [
            'id' => $envio->id,
            'cantidad_bultos' => 2,
            'cantidad_cargadores' => 3,
            'cantidad_accesorios' => 2,
            'detalle_accesorios' => 'Mouse y cable HDMI',
        ]);
    }

    public function test_detalle_muestra_cajas_y_etiquetas_humanas(): void
    {
        $envio = app(EnvioImportacionService::class)->crearBorrador($this->usuario->id, [
            'cantidad_bultos' => 2,
            'cantidad_cargadores' => 1,
        ]);

        $this->actingAs($this->usuario)
            ->get(route('envios-importacion.show', $envio))
            ->assertOk()
            ->assertSee('Cajas')
            ->assertSee('Cargadores');
    }
    public function test_borrador_muestra_edicion_de_manifiesto_y_endpoint_actualiza_datos(): void
    {
        $envio = app(EnvioImportacionService::class)->crearBorrador(
            $this->usuario->id,
            [
                'transportista' => 'Inicial',
                'cantidad_bultos' => 1,
                'cantidad_cargadores' => 0,
                'cantidad_accesorios' => 0,
            ]
        );

        $this->actingAs($this->usuario)
            ->get(route('envios-importacion.show', $envio))
            ->assertOk()
            ->assertSeeHtml('data-testid="accion-editar-manifiesto"')
            ->assertSee('Editar manifiesto de salida');

        $this->actingAs($this->usuario)
            ->patchJson(
                route('envios-importacion.update', $envio),
                [
                    'transportista' => 'Trans Copacabana',
                    'numero_guia' => 'G-2026-0042',
                    'cantidad_bultos' => 4,
                    'cantidad_cargadores' => 2,
                    'cantidad_accesorios' => 3,
                    'detalle_accesorios' => 'Mouse, cables y adaptador',
                    'observacion' => 'Salida consolidada.',
                ]
            )
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('envio.estado', EnvioImportacion::ESTADO_BORRADOR);

        $this->assertDatabaseHas('envios_importacion', [
            'id' => $envio->id,
            'transportista' => 'Trans Copacabana',
            'numero_guia' => 'G-2026-0042',
            'cantidad_bultos' => 4,
            'cantidad_cargadores' => 2,
            'cantidad_accesorios' => 3,
            'detalle_accesorios' => 'Mouse, cables y adaptador',
            'observacion' => 'Salida consolidada.',
        ]);
    }

    public function test_listado_expone_filtrado_instantaneo_en_cliente(): void
    {
        $servicio = app(EnvioImportacionService::class);

        $primero = $servicio->crearBorrador(
            $this->usuario->id,
            [
                'transportista' => 'Transportista Uno',
                'cantidad_bultos' => 1,
            ]
        );

        $segundo = $servicio->crearBorrador(
            $this->usuario->id,
            [
                'transportista' => 'Transportista Dos',
                'cantidad_bultos' => 1,
            ]
        );

        $segundo->update([
            'estado' => EnvioImportacion::ESTADO_CANCELADO,
        ]);

        $this->actingAs($this->usuario)
            ->get(route('envios-importacion.index'))
            ->assertOk()
            ->assertSee($primero->codigo)
            ->assertSee($segundo->codigo)
            ->assertSeeHtml('data-testid="filtro-envios-busqueda"')
            ->assertSeeHtml('data-testid="filtro-envios-estado"')
            ->assertSeeHtml('x-model.debounce.150ms="filtros.q"')
            ->assertSeeHtml('x-model="filtros.estado"')
            ->assertSee('Filtrado instantáneo');
    }


}
