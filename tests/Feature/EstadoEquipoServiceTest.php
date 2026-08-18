<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\TransicionEstadoEquipo;
use App\Services\EstadoEquipoService;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstadoEquipoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogoSeeder::class);
    }

    public function test_permite_cambiar_de_disponible_a_reservado_y_crea_historial(): void
    {
        $equipo = $this->crearEquipoEnEstado('DISPONIBLE');

        $estadoOrigenId = $equipo->estado_actual_id;

        $servicio = app(EstadoEquipoService::class);

        $resultado = $servicio->cambiarEstado(
            equipoId: $equipo->id,
            codigoEstadoDestino: 'RESERVADO',
            usuarioId: null,
            autorizadoPorId: null,
            motivo: 'Prueba automatizada de reserva'
        );

        $estadoReservado = EstadoEquipo::query()
            ->where('codigo', 'RESERVADO')
            ->firstOrFail();

        $this->assertEquals(
            $estadoReservado->id,
            $resultado->estado_actual_id
        );

        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'estado_actual_id' => $estadoReservado->id,
        ]);

        $this->assertDatabaseHas('historial_estados_equipos', [
            'equipo_id' => $equipo->id,
            'estado_origen_id' => $estadoOrigenId,
            'estado_destino_id' => $estadoReservado->id,
            'motivo' => 'Prueba automatizada de reserva',
        ]);
    }

    public function test_rechaza_una_transicion_no_permitida(): void
    {
        $equipo = $this->crearEquipoEnEstado('VENDIDO');

        $estadoOriginalId = $equipo->estado_actual_id;

        $servicio = app(EstadoEquipoService::class);

        try {
            $servicio->cambiarEstado(
                equipoId: $equipo->id,
                codigoEstadoDestino: 'RESERVADO'
            );

            $this->fail(
                'Se esperaba una ReglaNegocioException.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertStringContainsString(
                'No está permitida la transición',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'estado_actual_id' => $estadoOriginalId,
        ]);

        $this->assertDatabaseCount(
            'historial_estados_equipos',
            0
        );
    }

    public function test_rechaza_transicion_que_requiere_autorizacion_si_no_se_autoriza(): void
    {
        $equipo = $this->crearEquipoEnEstado('DISPONIBLE');

        $estadoReservado = EstadoEquipo::query()
            ->where('codigo', 'RESERVADO')
            ->firstOrFail();

        TransicionEstadoEquipo::query()
            ->where('estado_origen_id', $equipo->estado_actual_id)
            ->where('estado_destino_id', $estadoReservado->id)
            ->update([
                'requiere_autorizacion' => true,
            ]);

        $servicio = app(EstadoEquipoService::class);

        try {
            $servicio->cambiarEstado(
                equipoId: $equipo->id,
                codigoEstadoDestino: 'RESERVADO'
            );

            $this->fail(
                'La transición debía requerir autorización.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertStringContainsString(
                'requiere autorización',
                $exception->getMessage()
            );
        }

        $equipo->refresh();

        $estadoDisponible = EstadoEquipo::query()
            ->where('codigo', 'DISPONIBLE')
            ->firstOrFail();

        $this->assertEquals(
            $estadoDisponible->id,
            $equipo->estado_actual_id
        );

        $this->assertDatabaseCount(
            'historial_estados_equipos',
            0
        );
    }

    public function test_rechaza_cambiar_al_mismo_estado_actual(): void
    {
        $equipo = $this->crearEquipoEnEstado('DISPONIBLE');

        $servicio = app(EstadoEquipoService::class);

        try {
            $servicio->cambiarEstado(
                equipoId: $equipo->id,
                codigoEstadoDestino: 'DISPONIBLE'
            );

            $this->fail(
                'No debería permitirse cambiar al mismo estado.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertStringContainsString(
                'ya se encuentra en el estado solicitado',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'historial_estados_equipos',
            0
        );
    }

    private function crearEquipoEnEstado(
        string $codigoEstado
    ): Equipo {
        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $almacen = Almacen::query()
            ->where('activo', true)
            ->orderByDesc('principal')
            ->firstOrFail();

        $estado = EstadoEquipo::query()
            ->where('codigo', $codigoEstado)
            ->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'TEST-' . Str::uuid(),
            'nombre' => 'Laptop para prueba automatizada',
            'modelo' => 'TEST',
            'descripcion' => 'Producto temporal utilizado por PHPUnit.',
            'es_serializado' => true,
            'activo' => true,
        ]);

        return Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'TEST-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => now(),
            'fecha_disponible' => $codigoEstado === 'DISPONIBLE'
                ? now()
                : null,
            'observacion' => 'Equipo generado para prueba automatizada.',
            'activo' => true,
        ]);
    }
}