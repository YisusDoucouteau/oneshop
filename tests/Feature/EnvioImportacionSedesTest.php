<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\EnvioImportacion;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\EnvioImportacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvioImportacionSedesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private EnvioImportacionService $servicio;
    private Almacen $cochabamba;
    private Almacen $oruro;
    private User $hugo;
    private User $daniel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(EnvioImportacionService::class);
        $this->cochabamba = Almacen::where('codigo', 'COCHABAMBA')->firstOrFail();
        $this->oruro = Almacen::where('codigo', 'ORURO_PRINCIPAL')->firstOrFail();

        $this->hugo = $this->crearOperativo($this->cochabamba, 'hugo@oneshop.test');
        $this->daniel = $this->crearOperativo($this->oruro, 'daniel@oneshop.test');
    }

    public function test_origen_puede_crear_preparar_y_despachar(): void
    {
        [$envio, $unidad] = $this->crearEnvioConUnidad($this->hugo);

        $preparado = $this->servicio->marcarPreparado(
            $this->hugo->id,
            $envio->id
        );

        $this->assertSame(EnvioImportacion::ESTADO_PREPARADO, $preparado->estado);

        $despachado = $this->servicio->marcarDespachado(
            $this->hugo->id,
            $envio->id
        );

        $this->assertSame(EnvioImportacion::ESTADO_DESPACHADO, $despachado->estado);
        $this->assertSame(
            UnidadAdquirida::ESTADO_ENVIADA,
            $unidad->fresh()->estado
        );
    }

    public function test_usuario_del_destino_no_puede_operar_el_despacho_del_origen(): void
    {
        [$envio] = $this->crearEnvioConUnidad($this->hugo);

        $this->expectException(ReglaNegocioException::class);

        $this->servicio->marcarPreparado(
            $this->daniel->id,
            $envio->id
        );
    }

    public function test_solo_destino_puede_recibir_unidad_despachada(): void
    {
        [$envio, $unidad] = $this->crearEnvioConUnidad($this->hugo);

        $this->servicio->marcarPreparado($this->hugo->id, $envio->id);
        $this->servicio->marcarDespachado($this->hugo->id, $envio->id);

        try {
            $this->servicio->recibirUnidad(
                $this->hugo->id,
                $envio->id,
                $unidad->id
            );

            $this->fail('Cochabamba no debería poder registrar una recepción destinada a Oruro.');
        } catch (ReglaNegocioException) {
            $this->assertTrue(true);
        }

        $detalle = $this->servicio->recibirUnidad(
            $this->daniel->id,
            $envio->id,
            $unidad->id
        );

        $this->assertSame('RECIBIDA', $detalle->estado_recepcion);
        $this->assertSame(
            $this->oruro->id,
            $unidad->fresh()->almacen_actual_id
        );
    }

    public function test_interfaz_muestra_acciones_segun_sede_operativa(): void
    {
        [$envio, $unidad] = $this->crearEnvioConUnidad($this->hugo);
        $this->servicio->marcarPreparado($this->hugo->id, $envio->id);

        $hugoActual = $this->hugo->fresh();

        $this->assertSame(
            (int) $this->cochabamba->id,
            (int) $hugoActual->almacen_operativo_id
        );
        $this->assertSame(
            (int) $this->cochabamba->id,
            (int) $envio->fresh()->almacen_origen_id
        );
        $this->assertTrue(
            $hugoActual->puedeOperarEnAlmacen($envio->almacen_origen_id)
        );

        $this->actingAs($hugoActual)
            ->get(route('envios-importacion.show', $envio))
            ->assertOk()
            ->assertViewHas('puedeOperarOrigen', true)
            ->assertViewHas('puedeOperarDestino', false)
            ->assertSeeHtml('data-testid="accion-corregir-envio"')
            ->assertSeeHtml('data-testid="accion-despachar-envio"');

        $this->actingAs($this->daniel->fresh())
            ->get(route('envios-importacion.show', $envio))
            ->assertOk()
            ->assertDontSeeHtml('data-testid="accion-corregir-envio"')
            ->assertDontSeeHtml('data-testid="accion-despachar-envio"');

        $this->servicio->marcarDespachado($this->hugo->id, $envio->id);

        $this->actingAs($this->daniel->fresh())
            ->get(route('envios-importacion.show', $envio->fresh()))
            ->assertOk()
            ->assertSeeHtml('data-testid="accion-recibir-unidad"')
            ->assertSeeHtml('data-testid="accion-marcar-faltante"')
            ->assertSeeHtml('data-testid="accion-registrar-incidencia"');

        $this->actingAs($this->hugo->fresh())
            ->get(route('envios-importacion.show', $envio->fresh()))
            ->assertOk()
            ->assertDontSeeHtml('data-testid="accion-recibir-unidad"')
            ->assertDontSeeHtml('data-testid="accion-marcar-faltante"')
            ->assertDontSeeHtml('data-testid="accion-registrar-incidencia"');
    }

    public function test_administrador_global_puede_operar_ambas_sedes(): void
    {
        $admin = User::factory()->create([
            'activo' => true,
            'almacen_operativo_id' => null,
        ]);

        $rol = Rol::where('codigo', 'ADMINISTRADOR')->firstOrFail();
        $admin->roles()->attach($rol->id);

        [$envio, $unidad] = $this->crearEnvioConUnidad($admin);
        $this->servicio->marcarPreparado($admin->id, $envio->id);
        $this->servicio->marcarDespachado($admin->id, $envio->id);
        $this->servicio->recibirUnidad($admin->id, $envio->id, $unidad->id);

        $this->assertSame(
            UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
            $unidad->fresh()->estado
        );
    }

    private function crearOperativo(Almacen $almacen, string $email): User
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'email' => $email,
            'almacen_operativo_id' => $almacen->id,
        ]);

        $rol = Rol::where('codigo', 'ADMIN_OPERATIVO')->firstOrFail();
        $usuario->roles()->attach($rol->id);

        return $usuario;
    }

    private function crearEnvioConUnidad(User $usuario): array
    {
        $envio = $this->servicio->crearBorrador(
            $usuario->id,
            ['cantidad_bultos' => 1]
        );

        $categoria = CategoriaProducto::where('codigo', 'LAPTOP')->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'codigo' => 'SED-' . uniqid(),
            'nombre' => 'Laptop prueba sedes',
            'modelo' => 'TEST',
            'es_serializado' => true,
            'activo' => true,
        ]);

        $unidad = UnidadAdquirida::create([
            'producto_id' => $producto->id,
            'almacen_actual_id' => $this->cochabamba->id,
            'estado' => UnidadAdquirida::ESTADO_LISTA_ENVIO,
            'serial_fabricante' => 'SED-' . uniqid(),
            'enciende' => true,
            'tiene_sistema_operativo' => true,
            'tiene_cargador' => true,
            'requiere_servicio' => false,
        ]);

        $this->servicio->agregarUnidad(
            $usuario->id,
            $envio->id,
            $unidad->id
        );

        return [$envio, $unidad];
    }
}
