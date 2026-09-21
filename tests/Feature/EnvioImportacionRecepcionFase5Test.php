<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\EnvioImportacion;
use App\Models\EnvioImportacionUnidad;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\EnvioImportacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EnvioImportacionRecepcionFase5Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private EnvioImportacionService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(\Database\Seeders\TipoEventoLogisticoSeeder::class);

        Almacen::firstOrCreate(
            ['codigo' => 'COCHABAMBA'],
            [
                'nombre' => 'Depósito Cochabamba',
                'ciudad' => 'Cochabamba',
                'principal' => false,
                'activo' => true,
            ]
        );

        Almacen::firstOrCreate(
            ['codigo' => 'ORURO_PRINCIPAL'],
            [
                'nombre' => 'Tienda Oruro',
                'ciudad' => 'Oruro',
                'principal' => true,
                'activo' => true,
            ]
        );

        $this->admin = User::factory()->create([
            'activo' => true,
            'almacen_operativo_id' => null,
        ]);

        $rol = Rol::query()
            ->where('codigo', 'ADMINISTRADOR')
            ->firstOrFail();

        $this->admin->roles()->attach($rol->id);

        $this->servicio = app(EnvioImportacionService::class);
    }

    public function test_registra_conteo_fisico_y_exige_observacion_si_hay_diferencia(): void
    {
        [$envio] = $this->crearEnvioDespachado(
            cantidadBultos: 2,
            cargadoresAdicionales: 3,
            accesorios: 2
        );

        try {
            $this->servicio->registrarVerificacionRecepcion(
                $this->admin->id,
                $envio->id,
                [
                    'cantidad_bultos_recibidos' => 2,
                    'cantidad_cargadores_adicionales_recibidos' => 2,
                    'cantidad_accesorios_recibidos' => 2,
                    'observacion_recepcion_general' => '',
                ]
            );

            $this->fail('Se esperaba observación obligatoria por diferencia en el conteo.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'observacion_recepcion_general',
                $exception->errors()
            );
        }

        $actualizado = $this->servicio->registrarVerificacionRecepcion(
            $this->admin->id,
            $envio->id,
            [
                'cantidad_bultos_recibidos' => 2,
                'cantidad_cargadores_adicionales_recibidos' => 2,
                'cantidad_accesorios_recibidos' => 2,
                'observacion_recepcion_general' =>
                    'Llegaron dos cargadores adicionales; queda uno pendiente de verificación.',
            ]
        );

        $this->assertSame(2, $actualizado->cantidad_bultos_recibidos);
        $this->assertSame(2, $actualizado->cantidad_cargadores_adicionales_recibidos);
        $this->assertSame(2, $actualizado->cantidad_accesorios_recibidos);
        $this->assertSame($this->admin->id, $actualizado->verificado_recepcion_por_id);
        $this->assertNotNull($actualizado->fecha_verificacion_recepcion);
    }

    public function test_recepcion_guarda_si_el_cargador_declarado_llego(): void
    {
        [$envio, $unidad] = $this->crearEnvioDespachado();

        $detalle = $this->servicio->recibirUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id,
            'Equipo y cargador recibidos correctamente.',
            true
        );

        $this->assertSame(
            EnvioImportacionUnidad::ESTADO_RECIBIDA,
            $detalle->estado_recepcion
        );
        $this->assertTrue($detalle->cargador_recibido);
        $this->assertSame(
            UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
            $unidad->fresh()->estado
        );
    }

    public function test_cargador_declarado_faltante_convierte_la_recepcion_en_incidencia(): void
    {
        [$envio, $unidad] = $this->crearEnvioDespachado();

        $detalle = $this->servicio->recibirUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id,
            'El equipo llegó, pero no llegó el cargador declarado en Cochabamba.',
            false
        );

        $this->assertSame(
            EnvioImportacionUnidad::ESTADO_INCIDENCIA,
            $detalle->estado_recepcion
        );
        $this->assertFalse($detalle->cargador_recibido);
        $this->assertNotNull($detalle->fecha_recepcion);
        $this->assertSame(
            UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
            $unidad->fresh()->estado
        );
    }

    public function test_interfaz_muestra_recepcion_humana_y_conteo_fisico(): void
    {
        [$envio] = $this->crearEnvioDespachado();

        $this->actingAs($this->admin->fresh())
            ->get(route('envios-importacion.show', $envio->fresh()))
            ->assertOk()
            ->assertSee('Verificación física en destino')
            ->assertSee('Pendiente de recepción')
            ->assertSeeHtml('data-testid="accion-verificar-recepcion"')
            ->assertSeeHtml('data-testid="recepcion-cajas"');
    }

    private function crearEnvioDespachado(
        int $cantidadBultos = 1,
        int $cargadoresAdicionales = 0,
        int $accesorios = 0
    ): array {
        $envio = $this->servicio->crearBorrador(
            $this->admin->id,
            [
                'cantidad_bultos' => $cantidadBultos,
                'cantidad_cargadores' => $cargadoresAdicionales,
                'cantidad_accesorios' => $accesorios,
            ]
        );

        $categoria = CategoriaProducto::query()->firstOrCreate(
            ['codigo' => 'F5-LAPTOP'],
            [
                'nombre' => 'Laptops recepción F5',
                'activo' => true,
            ]
        );

        $marca = Marca::query()->firstOrCreate(
            ['nombre' => 'Marca F5'],
            ['activo' => true]
        );

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'F5-' . uniqid(),
            'nombre' => 'Laptop recepción Fase 5',
            'modelo' => 'Modelo F5',
            'es_serializado' => true,
            'activo' => true,
        ]);

        $unidad = UnidadAdquirida::create([
            'producto_id' => $producto->id,
            'almacen_actual_id' => $envio->almacen_origen_id,
            'estado' => UnidadAdquirida::ESTADO_LISTA_ENVIO,
            'serial_fabricante' => 'F5-' . uniqid(),
            'enciende' => true,
            'tiene_sistema_operativo' => true,
            'tiene_cargador' => true,
            'requiere_servicio' => false,
        ]);

        $this->servicio->agregarUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id
        );

        $this->servicio->marcarPreparado(
            $this->admin->id,
            $envio->id
        );

        $this->servicio->marcarDespachado(
            $this->admin->id,
            $envio->id
        );

        return [$envio->fresh(), $unidad->fresh()];
    }
}
