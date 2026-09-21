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
use Tests\TestCase;

class EnvioImportacionCierreRecepcionFase52Test extends TestCase
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

    public function test_no_permite_cerrar_sin_verificacion_fisica_general(): void
    {
        [$envio, $unidad] = $this->crearEnvioDespachado();

        $this->servicio->recibirUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id,
            'Equipo y cargador recibidos.',
            true
        );

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage(
            'Debe registrar la verificación física general antes de cerrar la recepción.'
        );

        $this->servicio->cerrarRecepcion(
            $this->admin->id,
            $envio->id
        );
    }

    public function test_cierra_completo_cuando_unidades_y_conteos_coinciden(): void
    {
        [$envio, $unidad] = $this->crearEnvioDespachado(
            cantidadBultos: 2,
            cargadoresAdicionales: 3,
            accesorios: 2
        );

        $this->verificarConteo(
            $envio,
            cajas: 2,
            cargadoresAdicionales: 3,
            accesorios: 2
        );

        $this->servicio->recibirUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id,
            'Equipo completo.',
            true
        );

        $cerrado = $this->servicio->cerrarRecepcion(
            $this->admin->id,
            $envio->id
        );

        $this->assertSame(
            EnvioImportacion::ESTADO_RECIBIDO,
            $cerrado->estado
        );
        $this->assertSame($this->admin->id, $cerrado->recibido_por_id);
        $this->assertNotNull($cerrado->fecha_recepcion);
        $this->assertFalse($cerrado->tieneDiferenciasConteoRecepcion());
    }

    public function test_cierra_con_diferencias_si_el_conteo_general_no_coincide(): void
    {
        [$envio, $unidad] = $this->crearEnvioDespachado(
            cantidadBultos: 2,
            cargadoresAdicionales: 3,
            accesorios: 2
        );

        $this->verificarConteo(
            $envio,
            cajas: 2,
            cargadoresAdicionales: 2,
            accesorios: 2,
            observacion: 'Falta un cargador adicional respecto al manifiesto.'
        );

        $this->servicio->recibirUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id,
            'Equipo recibido correctamente.',
            true
        );

        $cerrado = $this->servicio->cerrarRecepcion(
            $this->admin->id,
            $envio->id
        );

        $this->assertSame(
            EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
            $cerrado->estado
        );
        $this->assertNull($cerrado->recibido_por_id);
        $this->assertNull($cerrado->fecha_recepcion);
        $this->assertTrue($cerrado->tieneDiferenciasConteoRecepcion());

        $this->actingAs($this->admin->fresh())
            ->get(route('envios-importacion.show', $cerrado))
            ->assertOk()
            ->assertSee('Recibido con diferencias');
    }

    public function test_cierra_con_diferencias_si_falta_cargador_asociado_a_unidad(): void
    {
        [$envio, $unidad] = $this->crearEnvioDespachado();

        $this->verificarConteo(
            $envio,
            cajas: 1,
            cargadoresAdicionales: 0,
            accesorios: 0
        );

        $detalle = $this->servicio->recibirUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id,
            'La laptop llegó, pero no llegó su cargador.',
            false
        );

        $this->assertSame(
            EnvioImportacionUnidad::ESTADO_INCIDENCIA,
            $detalle->estado_recepcion
        );

        $cerrado = $this->servicio->cerrarRecepcion(
            $this->admin->id,
            $envio->id
        );

        $this->assertSame(
            EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
            $cerrado->estado
        );
    }

    public function test_interfaz_habilita_cierre_solo_cuando_verificacion_y_unidades_estan_resueltas(): void
    {
        [$envio, $unidad] = $this->crearEnvioDespachado();

        $this->actingAs($this->admin->fresh())
            ->get(route('envios-importacion.show', $envio))
            ->assertOk()
            ->assertDontSeeHtml('data-testid="accion-cerrar-recepcion"')
            ->assertSee('Registra primero el conteo físico general');

        $this->verificarConteo(
            $envio,
            cajas: 1,
            cargadoresAdicionales: 0,
            accesorios: 0
        );

        $this->servicio->recibirUnidad(
            $this->admin->id,
            $envio->id,
            $unidad->id,
            'Equipo recibido.',
            true
        );

        $this->actingAs($this->admin->fresh())
            ->get(route('envios-importacion.show', $envio->fresh()))
            ->assertOk()
            ->assertSeeHtml('data-testid="accion-cerrar-recepcion"')
            ->assertSee('Todo coincide con el manifiesto');
    }

    private function verificarConteo(
        EnvioImportacion $envio,
        int $cajas,
        int $cargadoresAdicionales,
        int $accesorios,
        ?string $observacion = null
    ): EnvioImportacion {
        return $this->servicio->registrarVerificacionRecepcion(
            $this->admin->id,
            $envio->id,
            [
                'cantidad_bultos_recibidos' => $cajas,
                'cantidad_cargadores_adicionales_recibidos' =>
                    $cargadoresAdicionales,
                'cantidad_accesorios_recibidos' => $accesorios,
                'observacion_recepcion_general' => $observacion,
            ]
        );
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
            ['codigo' => 'F52-LAPTOP'],
            [
                'nombre' => 'Laptops cierre recepción F5.2',
                'activo' => true,
            ]
        );

        $marca = Marca::query()->firstOrCreate(
            ['nombre' => 'Marca F5.2'],
            ['activo' => true]
        );

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'F52-' . uniqid(),
            'nombre' => 'Laptop cierre recepción Fase 5.2',
            'modelo' => 'Modelo F5.2',
            'es_serializado' => true,
            'activo' => true,
        ]);

        $unidad = UnidadAdquirida::create([
            'producto_id' => $producto->id,
            'almacen_actual_id' => $envio->almacen_origen_id,
            'estado' => UnidadAdquirida::ESTADO_LISTA_ENVIO,
            'serial_fabricante' => 'F52-' . uniqid(),
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
