<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\EnvioImportacion;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\EnvioImportacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvioImportacionCargadoresTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $usuario;
    private EnvioImportacionService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create([
            'activo' => true,
        ]);

        $rol = Rol::query()
            ->where('codigo', 'ADMINISTRADOR')
            ->firstOrFail();

        $this->usuario->roles()->attach($rol->id);
        $this->servicio = app(EnvioImportacionService::class);
    }

    public function test_al_agregar_unidad_hereda_si_tiene_cargador_pero_puede_cambiarse_en_borrador(): void
    {
        $envio = $this->servicio->crearBorrador(
            $this->usuario->id,
            ['cantidad_bultos' => 1]
        );

        $unidad = $this->crearUnidadLista($envio, true);

        $detalle = $this->servicio->agregarUnidad(
            $this->usuario->id,
            $envio->id,
            $unidad->id
        );

        $this->assertTrue($detalle->incluye_cargador);

        $detalle = $this->servicio->actualizarCargadorUnidad(
            $this->usuario->id,
            $envio->id,
            $unidad->id,
            false
        );

        $this->assertFalse($detalle->incluye_cargador);

        $preparado = $this->servicio->marcarPreparado(
            $this->usuario->id,
            $envio->id
        );

        $this->assertSame(
            EnvioImportacion::ESTADO_PREPARADO,
            $preparado->estado
        );
    }

    public function test_unidad_sin_cargador_puede_formar_parte_del_envio(): void
    {
        $envio = $this->servicio->crearBorrador(
            $this->usuario->id,
            ['cantidad_bultos' => 1]
        );

        $unidad = $this->crearUnidadLista($envio, false);

        $detalle = $this->servicio->agregarUnidad(
            $this->usuario->id,
            $envio->id,
            $unidad->id
        );

        $this->assertFalse($detalle->incluye_cargador);

        $this->servicio->marcarPreparado(
            $this->usuario->id,
            $envio->id
        );

        $this->assertDatabaseHas('envios_importacion', [
            'id' => $envio->id,
            'estado' => EnvioImportacion::ESTADO_PREPARADO,
        ]);
    }

    public function test_resumen_separa_cargadores_asociados_adicionales_y_total(): void
    {
        $envio = $this->servicio->crearBorrador(
            $this->usuario->id,
            [
                'cantidad_bultos' => 1,
                'cantidad_cargadores' => 3,
            ]
        );

        $unidadCon = $this->crearUnidadLista($envio, true);
        $unidadSin = $this->crearUnidadLista($envio, false);

        $this->servicio->agregarUnidad(
            $this->usuario->id,
            $envio->id,
            $unidadCon->id
        );

        $this->servicio->agregarUnidad(
            $this->usuario->id,
            $envio->id,
            $unidadSin->id
        );

        $envio = $envio->fresh('unidadesEnvio');

        $this->assertSame(1, $envio->cantidadCargadoresAsociados());
        $this->assertSame(4, $envio->cantidadCargadoresTotales());

        $this->actingAs($this->usuario)
            ->get(route('envios-importacion.show', $envio))
            ->assertOk()
            ->assertSee('Cargadores con equipos')
            ->assertSee('Cargadores adicionales')
            ->assertSee('Total cargadores')
            ->assertSee('Cargador con equipo');
    }

    public function test_endpoint_permite_definir_si_el_cargador_viaja_con_la_unidad(): void
    {
        $envio = $this->servicio->crearBorrador(
            $this->usuario->id,
            ['cantidad_bultos' => 1]
        );

        $unidad = $this->crearUnidadLista($envio, true);
        $this->servicio->agregarUnidad(
            $this->usuario->id,
            $envio->id,
            $unidad->id
        );

        $this->actingAs($this->usuario)
            ->postJson(
                route('envios-importacion.unidades.cargador', [
                    'envio' => $envio,
                    'unidad' => $unidad,
                ]),
                ['incluye_cargador' => false]
            )
            ->assertOk()
            ->assertJsonPath('incluye_cargador', false);

        $this->assertDatabaseHas('envios_importacion_unidades', [
            'envio_importacion_id' => $envio->id,
            'unidad_adquirida_id' => $unidad->id,
            'incluye_cargador' => 0,
        ]);
    }

    private function crearUnidadLista(
        EnvioImportacion $envio,
        bool $tieneCargador
    ): UnidadAdquirida {
        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'codigo' => 'CARG-' . uniqid(),
            'nombre' => 'Laptop control cargadores',
            'modelo' => 'TEST',
            'es_serializado' => true,
            'activo' => true,
        ]);

        return UnidadAdquirida::create([
            'producto_id' => $producto->id,
            'almacen_actual_id' => $envio->almacen_origen_id,
            'estado' => UnidadAdquirida::ESTADO_LISTA_ENVIO,
            'serial_fabricante' => 'CARG-' . uniqid(),
            'enciende' => true,
            'tiene_sistema_operativo' => true,
            'tiene_cargador' => $tieneCargador,
            'requiere_servicio' => false,
        ]);
    }
}
