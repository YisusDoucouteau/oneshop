<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\EnvioImportacion;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\EnvioImportacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvioImportacionFase42Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

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

    public function test_envio_preparado_puede_reabrirse_a_borrador_con_motivo(): void
    {
        [$envio, $unidad] = $this->crearEnvioConUnidad();

        $this->servicio->marcarPreparado(
            $this->usuario->id,
            $envio->id
        );

        $reabierto = $this->servicio->reabrirPreparado(
            $this->usuario->id,
            $envio->id,
            'Se corrigirá la cantidad de cajas.'
        );

        $this->assertSame(
            EnvioImportacion::ESTADO_BORRADOR,
            $reabierto->estado
        );

        $this->assertDatabaseHas('envios_importacion_unidades', [
            'envio_importacion_id' => $envio->id,
            'unidad_adquirida_id' => $unidad->id,
        ]);

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'REABRIR_ENVIO_IMPORTACION',
            'entidad' => 'EnvioImportacion',
            'entidad_id' => $envio->id,
            'usuario_id' => $this->usuario->id,
        ]);
    }

    public function test_cancelar_envio_conserva_historial_y_libera_unidad_para_otro_envio(): void
    {
        [$envio, $unidad] = $this->crearEnvioConUnidad();

        $cancelado = $this->servicio->cancelarEnvio(
            $this->usuario->id,
            $envio->id,
            'Se reemplazará el transporte.'
        );

        $this->assertSame(
            EnvioImportacion::ESTADO_CANCELADO,
            $cancelado->estado
        );

        // El vínculo histórico no se destruye.
        $this->assertDatabaseHas('envios_importacion_unidades', [
            'envio_importacion_id' => $envio->id,
            'unidad_adquirida_id' => $unidad->id,
        ]);

        $unidad->refresh();
        $this->assertNull($unidad->envioImportacionUnidad);

        // La misma unidad puede incorporarse a un nuevo envío activo.
        $nuevo = $this->servicio->crearBorrador(
            $this->usuario->id,
            ['cantidad_bultos' => 1]
        );

        $detalleNuevo = $this->servicio->agregarUnidad(
            $this->usuario->id,
            $nuevo->id,
            $unidad->id
        );

        $this->assertSame(
            $nuevo->id,
            $detalleNuevo->envio_importacion_id
        );

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'CANCELAR_ENVIO_IMPORTACION',
            'entidad' => 'EnvioImportacion',
            'entidad_id' => $envio->id,
        ]);
    }

    public function test_no_se_puede_cancelar_un_envio_ya_despachado(): void
    {
        [$envio] = $this->crearEnvioConUnidad();

        $this->servicio->marcarPreparado(
            $this->usuario->id,
            $envio->id
        );

        $this->servicio->marcarDespachado(
            $this->usuario->id,
            $envio->id
        );

        $this->expectException(ReglaNegocioException::class);

        $this->servicio->cancelarEnvio(
            $this->usuario->id,
            $envio->id,
            'Ya no debería permitirse.'
        );
    }

    public function test_vista_de_envio_preparado_ofrece_corregir_y_cancelar(): void
    {
        [$envio] = $this->crearEnvioConUnidad();

        $this->servicio->marcarPreparado(
            $this->usuario->id,
            $envio->id
        );

        $response = $this
            ->actingAs($this->usuario)
            ->get(route('envios-importacion.show', $envio));

        $response
            ->assertOk()
            ->assertSee('Corregir envío')
            ->assertSee('Cancelar envío')
            ->assertSee('Despachar a Oruro');
    }

    private function crearEnvioConUnidad(): array
    {
        $envio = $this->servicio->crearBorrador(
            $this->usuario->id,
            [
                'cantidad_bultos' => 1,
                'cantidad_cargadores' => 1,
            ]
        );

        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'codigo' => 'F42-' . uniqid(),
            'nombre' => 'Laptop prueba Fase 4.2',
            'modelo' => 'TEST',
            'es_serializado' => true,
            'activo' => true,
        ]);

        $unidad = UnidadAdquirida::create([
            'producto_id' => $producto->id,
            'almacen_actual_id' => $envio->almacen_origen_id,
            'estado' => UnidadAdquirida::ESTADO_LISTA_ENVIO,
            'serial_fabricante' => 'F42-' . uniqid(),
            'enciende' => true,
            'tiene_sistema_operativo' => true,
            'tiene_cargador' => true,
            'requiere_servicio' => false,
        ]);

        $this->servicio->agregarUnidad(
            $this->usuario->id,
            $envio->id,
            $unidad->id
        );

        return [$envio, $unidad];
    }
}
