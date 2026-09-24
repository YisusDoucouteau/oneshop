<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Garantia;
use App\Models\PoliticaGarantia;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Services\CasoGarantiaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CasoGarantiaGestionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $administrador;
    protected User $tecnico;
    protected User $vendedor;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->administrador =
            $this->usuarioConRol(
                'ADMINISTRADOR'
            );

        $this->tecnico =
            $this->usuarioConRol(
                'TECNICO'
            );

        $this->vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $this->cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente gestión garantía',
            'telefono' =>
                '70000993',
            'activo' =>
                true,
        ]);
    }

    public function test_tecnico_puede_diagnosticar_caso_abierto(): void
    {
        [$caso] =
            $this->crearCasoAbierto();

        $caso = app(
            CasoGarantiaService::class
        )->registrarDiagnostico(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            diagnostico:
                'Falla de alimentación detectada en pruebas.'
        );

        $this->assertSame(
            'DIAGNOSTICADO',
            $caso->estado
        );

        $this->assertSame(
            'Falla de alimentación detectada en pruebas.',
            $caso->diagnostico_final
        );
    }

    public function test_no_permite_intervenir_antes_del_diagnostico(): void
    {
        [$caso] =
            $this->crearCasoAbierto();

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'Debe registrar el diagnóstico antes de agregar intervenciones.'
        );

        app(CasoGarantiaService::class)
            ->registrarIntervencion(
                casoId: $caso->id,
                usuarioId:
                    $this->tecnico->id,
                tipo:
                    'PRUEBA',
                descripcion:
                    'Prueba que no debería registrarse.'
            );
    }

    public function test_intervencion_pasa_caso_a_en_proceso_y_equipo_sigue_vendido(): void
    {
        [$caso, $equipo] =
            $this->crearCasoAbierto();

        $servicio =
            app(CasoGarantiaService::class);

        $servicio->registrarDiagnostico(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            diagnostico:
                'Falla de alimentación confirmada.'
        );

        $intervencion =
            $servicio->registrarIntervencion(
                casoId: $caso->id,
                usuarioId: $this->tecnico->id,
                tipo: 'REPARACION',
                descripcion:
                    'Se ajustó el conector interno de alimentación.',
                resultado:
                    'Equipo enciende de forma estable.'
            );

        $caso->refresh();
        $equipo->refresh();

        $estadoVendido =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'VENDIDO'
                )
                ->firstOrFail();

        $this->assertSame(
            'EN_PROCESO',
            $caso->estado
        );

        $this->assertSame(
            $this->tecnico->id,
            $intervencion->usuario_id
        );

        $this->assertSame(
            $estadoVendido->id,
            $equipo->estado_actual_id
        );
    }

    public function test_permite_varias_intervenciones_en_caso_en_proceso(): void
    {
        [$caso] =
            $this->crearCasoAbierto();

        $servicio =
            app(CasoGarantiaService::class);

        $servicio->registrarDiagnostico(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            diagnostico:
                'Falla confirmada.'
        );

        $servicio->registrarIntervencion(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            tipo: 'AJUSTE',
            descripcion:
                'Primera intervención.'
        );

        $servicio->registrarIntervencion(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            tipo: 'PRUEBA',
            descripcion:
                'Segunda intervención.',
            resultado:
                'Prueba satisfactoria.'
        );

        $this->assertDatabaseCount(
            'intervenciones_garantia',
            2
        );

        $this->assertSame(
            'EN_PROCESO',
            $caso->fresh()->estado
        );
    }

    public function test_caso_diagnosticado_puede_cerrarse_sin_intervencion_ficticia(): void
    {
        [$caso] =
            $this->crearCasoAbierto();

        $servicio =
            app(CasoGarantiaService::class);

        $servicio->registrarDiagnostico(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            diagnostico:
                'No se encontró falla de hardware; configuración corregida.'
        );

        $caso = $servicio->cerrarCaso(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            resolucion:
                'Se corrigió configuración y el equipo quedó operativo.'
        );

        $this->assertSame(
            'CERRADO',
            $caso->estado
        );

        $this->assertNotNull(
            $caso->fecha_cierre
        );

        $this->assertSame(
            $this->tecnico->id,
            $caso->cerrado_por_id
        );

        $this->assertDatabaseCount(
            'intervenciones_garantia',
            0
        );
    }

    public function test_caso_cerrado_es_inmutable(): void
    {
        [$caso] =
            $this->crearCasoAbierto();

        $servicio =
            app(CasoGarantiaService::class);

        $servicio->registrarDiagnostico(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            diagnostico:
                'Diagnóstico de prueba.'
        );

        $servicio->cerrarCaso(
            casoId: $caso->id,
            usuarioId: $this->tecnico->id,
            resolucion:
                'Caso cerrado para prueba.'
        );

        try {
            $servicio->registrarIntervencion(
                casoId: $caso->id,
                usuarioId: $this->tecnico->id,
                tipo: 'PRUEBA',
                descripcion:
                    'No debe registrarse.'
            );

            $this->fail(
                'Un caso cerrado no debe admitir intervenciones.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertStringContainsString(
                'cerrado',
                mb_strtolower(
                    $exception->getMessage()
                )
            );
        }

        $this->assertDatabaseCount(
            'intervenciones_garantia',
            0
        );
    }

    public function test_vendedor_no_puede_gestionar_caso_tecnico(): void
    {
        [$caso] =
            $this->crearCasoAbierto();

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'El usuario no cuenta con permiso para gestionar casos de garantía.'
        );

        app(CasoGarantiaService::class)
            ->registrarDiagnostico(
                casoId: $caso->id,
                usuarioId: $this->vendedor->id,
                diagnostico:
                    'Diagnóstico que no debe registrarse.'
            );
    }

    private function crearCasoAbierto(): array
    {
        [$equipo, $garantia] =
            $this->crearVentaConGarantia();

        $caso =
            app(CasoGarantiaService::class)
                ->abrirCaso(
                    garantiaId:
                        $garantia->id,
                    usuarioId:
                        $this->administrador->id,
                    motivoCliente:
                        'Equipo con falla intermitente.',
                    observacion:
                        'Caso de prueba para gestión técnica.'
                );

        return [
            $caso,
            $equipo,
            $garantia,
        ];
    }

    private function crearVentaConGarantia(): array
    {
        $equipo =
            $this->crearEquipoDisponible();

        $venta = app(VentaService::class)
            ->registrarVentaDirecta(
                vendedorId:
                    $this->administrador->id,
                equiposIds: [
                    $equipo->id,
                ],
                clienteId:
                    $this->cliente->id
            );

        $garantia = Garantia::query()
            ->whereHas(
                'detalleVenta',
                function ($query) use (
                    $venta,
                    $equipo
                ) {
                    $query
                        ->where(
                            'venta_id',
                            $venta->id
                        )
                        ->where(
                            'equipo_id',
                            $equipo->id
                        );
                }
            )
            ->firstOrFail();

        return [
            $equipo->fresh(),
            $garantia->fresh(),
        ];
    }

    private function crearEquipoDisponible(): Equipo
    {
        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->firstOrFail();

        $almacen =
            Almacen::query()
                ->where(
                    'activo',
                    true
                )
                ->orderByDesc(
                    'principal'
                )
                ->firstOrFail();

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,
            'marca_id' => null,
            'codigo' =>
                'PROD-GEST-GAR-'
                . Str::uuid(),
            'nombre' =>
                'Laptop gestión garantía',
            'modelo' =>
                'TEST-10B',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-GEST-'
                . Str::uuid(),
            'nombre' =>
                'Garantía gestión 10B',
            'categoria_producto_id' =>
                $categoria->id,
            'producto_id' =>
                $producto->id,
            'duracion_meses' =>
                6,
            'condiciones' =>
                'Garantía estándar de prueba.',
            'exclusiones' =>
                'Daños físicos.',
            'vigente_desde' =>
                now()->subDay(),
            'vigente_hasta' =>
                null,
            'activo' =>
                true,
        ]);

        $equipo = Equipo::create([
            'producto_id' =>
                $producto->id,
            'detalle_lote_id' =>
                null,
            'almacen_actual_id' =>
                $almacen->id,
            'estado_actual_id' =>
                $estadoDisponible->id,
            'condicion_fisica_id' =>
                null,
            'codigo_interno' =>
                'EQ-10B-'
                . Str::uuid(),
            'serial_fabricante' =>
                null,
            'fecha_registro' =>
                now(),
            'fecha_disponible' =>
                now(),
            'observacion' =>
                null,
            'activo' =>
                true,
        ]);

        PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,
            'tipo_cambio_id' =>
                null,
            'costo_total_snapshot' =>
                2900,
            'precio_sugerido' =>
                3500,
            'precio_publico' =>
                3500,
            'precio_minimo_autorizado' =>
                3200,
            'vigente_desde' =>
                now(),
            'vigente_hasta' =>
                null,
            'vigente' =>
                true,
            'aprobado_por_id' =>
                null,
            'observacion' =>
                'Precio para prueba 10B.',
        ]);

        DB::table(
            'existencias_productos'
        )->insert([
            'producto_id' =>
                $producto->id,
            'almacen_id' =>
                $almacen->id,
            'cantidad_disponible' =>
                1,
            'cantidad_reservada' =>
                0,
            'created_at' =>
                now(),
            'updated_at' =>
                now(),
        ]);

        return $equipo;
    }

    private function usuarioConRol(
        string $codigoRol
    ): User {
        $usuario =
            User::factory()->create([
                'activo' =>
                    true,
            ]);

        $rol = Rol::query()
            ->where(
                'codigo',
                $codigoRol
            )
            ->firstOrFail();

        $usuario->roles()->attach(
            $rol->id
        );

        return $usuario;
    }
}
