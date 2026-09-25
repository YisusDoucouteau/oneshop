<?php

namespace Tests\Feature;

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
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\CasoGarantiaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CasoGarantiaCambioEquipoWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $administrador;
    protected User $tecnico;
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

        $this->cliente =
            Cliente::create([
                'nombre_completo' =>
                    'Cliente cambio web 10C',

                'telefono' =>
                    '70000995',

                'activo' =>
                    true,
            ]);
    }

    public function test_administrador_puede_registrar_cambio_de_equipo_por_web(): void
    {
        [
            $caso,
            $equipoOriginal,
            $garantia,
        ] = $this->crearCasoDiagnosticado();

        $equipoReemplazo =
            $this->crearEquipoReemplazo(
                $equipoOriginal
            );

        $origen =
            '/inventario/'
            . $equipoOriginal->codigo_interno;

        $this
            ->actingAs(
                $this->administrador
            )
            ->from($origen)
            ->post(
                route(
                    'garantias.casos.cambio-equipo',
                    $caso
                ),
                [
                    '_caso_id' =>
                        $caso->id,

                    'equipo_entrante_id' =>
                        $equipoReemplazo->id,

                    'motivo' =>
                        'Cambio autorizado por falla confirmada.',

                    'observacion' =>
                        'Cambio realizado desde flujo web 10C.',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHas('success');

        $caso->refresh();
        $equipoOriginal->refresh();
        $equipoReemplazo->refresh();
        $garantia->refresh();

        $estadoGarantia =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'GARANTIA'
                )
                ->firstOrFail();

        $estadoVendido =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'VENDIDO'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'cambios_equipos',
            [
                'caso_garantia_id' =>
                    $caso->id,

                'equipo_saliente_id' =>
                    $equipoOriginal->id,

                'equipo_entrante_id' =>
                    $equipoReemplazo->id,

                'autorizado_por_id' =>
                    $this->administrador->id,
            ]
        );

        $this->assertSame(
            'EN_PROCESO',
            $caso->estado
        );

        $this->assertSame(
            $estadoGarantia->id,
            $equipoOriginal->estado_actual_id
        );

        $this->assertSame(
            $estadoVendido->id,
            $equipoReemplazo->estado_actual_id
        );

        /*
         * La garantia comercial no se anula.
         */
        $this->assertSame(
            'VIGENTE',
            $garantia->estado
        );

        /*
         * El stock disponible del reemplazo
         * debe quedar en cero.
         */
        $existencia =
            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $equipoReemplazo->producto_id
                )
                ->where(
                    'almacen_id',
                    $equipoReemplazo->almacen_actual_id
                )
                ->first();

        $this->assertNotNull(
            $existencia
        );

        $this->assertSame(
            0,
            (int) $existencia
                ->cantidad_disponible
        );

        /*
         * Debe existir la salida de inventario
         * correspondiente al cambio.
         */
        $tipoMovimiento =
            TipoMovimientoInventario::query()
                ->where(
                    'codigo',
                    'CAMBIO_GARANTIA'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'movimientos_inventario',
            [
                'producto_id' =>
                    $equipoReemplazo->producto_id,

                'almacen_id' =>
                    $equipoReemplazo->almacen_actual_id,

                'tipo_movimiento_id' =>
                    $tipoMovimiento->id,

                'cambio_disponible' =>
                    -1,

                'tipo_referencia' =>
                    'CAMBIO_GARANTIA',
            ]
        );

        /*
         * El detalle historico de venta
         * conserva el equipo original.
         */
        $garantiaConDetalle =
            Garantia::query()
                ->with('detalleVenta')
                ->findOrFail(
                    $garantia->id
                );

        $this->assertSame(
            $equipoOriginal->id,
            (int) $garantiaConDetalle
                ->detalleVenta
                ->equipo_id
        );

        /*
         * El caso tambien conserva como afectado
         * el equipo originalmente vendido.
         */
        $this->assertSame(
            $equipoOriginal->id,
            (int) $caso->equipo_afectado_id
        );
    }

    public function test_tecnico_no_puede_autorizar_cambio_por_web(): void
    {
        [
            $caso,
            $equipoOriginal,
        ] = $this->crearCasoDiagnosticado();

        $equipoReemplazo =
            $this->crearEquipoReemplazo(
                $equipoOriginal
            );

        $this
            ->actingAs(
                $this->tecnico
            )
            ->post(
                route(
                    'garantias.casos.cambio-equipo',
                    $caso
                ),
                [
                    '_caso_id' =>
                        $caso->id,

                    'equipo_entrante_id' =>
                        $equipoReemplazo->id,

                    'motivo' =>
                        'Intento sin autorizacion especial.',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );

        $this->assertSame(
            'DIAGNOSTICADO',
            $caso->fresh()->estado
        );

        $estadoVendido =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'VENDIDO'
                )
                ->firstOrFail();

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $this->assertSame(
            $estadoVendido->id,
            $equipoOriginal
                ->fresh()
                ->estado_actual_id
        );

        $this->assertSame(
            $estadoDisponible->id,
            $equipoReemplazo
                ->fresh()
                ->estado_actual_id
        );
    }

    public function test_web_valida_equipo_entrante_inexistente(): void
    {
        [
            $caso,
            $equipoOriginal,
        ] = $this->crearCasoDiagnosticado();

        $origen =
            '/inventario/'
            . $equipoOriginal->codigo_interno;

        $this
            ->actingAs(
                $this->administrador
            )
            ->from($origen)
            ->post(
                route(
                    'garantias.casos.cambio-equipo',
                    $caso
                ),
                [
                    '_caso_id' =>
                        $caso->id,

                    'equipo_entrante_id' =>
                        999999999,

                    'motivo' =>
                        'Cambio con equipo inexistente.',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHasErrors([
                'equipo_entrante_id',
            ]);

        $this->assertDatabaseCount(
            'cambios_equipos',
            0
        );

        $this->assertSame(
            'DIAGNOSTICADO',
            $caso->fresh()->estado
        );
    }

    private function crearCasoDiagnosticado(): array
    {
        [
            $caso,
            $equipo,
            $garantia,
        ] = $this->crearCasoAbierto();

        $caso =
            app(CasoGarantiaService::class)
                ->registrarDiagnostico(
                    casoId:
                        $caso->id,

                    usuarioId:
                        $this->tecnico->id,

                    diagnostico:
                        'Falla confirmada que requiere evaluar reemplazo.'
                );

        return [
            $caso,
            $equipo,
            $garantia,
        ];
    }

    private function crearCasoAbierto(): array
    {
        $equipo =
            $this->crearEquipoDisponible();

        $venta =
            app(VentaService::class)
                ->registrarVentaDirecta(
                    vendedorId:
                        $this->administrador->id,

                    equiposIds: [
                        $equipo->id,
                    ],

                    clienteId:
                        $this->cliente->id
                );

        $garantia =
            Garantia::query()
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

        $caso =
            app(CasoGarantiaService::class)
                ->abrirCaso(
                    garantiaId:
                        $garantia->id,

                    usuarioId:
                        $this->administrador->id,

                    motivoCliente:
                        'Equipo reportado con falla para prueba 10C.'
                );

        return [
            $caso,
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

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    null,

                'codigo' =>
                    'P10W-'
                    . Str::ulid(),

                'nombre' =>
                    'Laptop cambio garantia web',

                'modelo' =>
                    'WEB-10C',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'G10W-'
                . Str::ulid(),

            'nombre' =>
                'Garantia web cambio 10C',

            'categoria_producto_id' =>
                $categoria->id,

            'producto_id' =>
                $producto->id,

            'duracion_meses' =>
                6,

            'condiciones' =>
                'Garantia de prueba web 10C.',

            'exclusiones' =>
                'Danos fisicos.',

            'vigente_desde' =>
                now()->subDay(),

            'vigente_hasta' =>
                null,

            'activo' =>
                true,
        ]);

        $equipo =
            Equipo::create([
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
                    'E10O-'
                    . Str::ulid(),

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
                'Precio web 10C.',
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

    private function crearEquipoReemplazo(
        Equipo $equipoOriginal
    ): Equipo {
        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $equipo =
            Equipo::create([
                'producto_id' =>
                    $equipoOriginal->producto_id,

                'detalle_lote_id' =>
                    null,

                'almacen_actual_id' =>
                    $equipoOriginal->almacen_actual_id,

                'estado_actual_id' =>
                    $estadoDisponible->id,

                'condicion_fisica_id' =>
                    null,

                'codigo_interno' =>
                    'E10R-'
                    . Str::ulid(),

                'serial_fabricante' =>
                    null,

                'fecha_registro' =>
                    now(),

                'fecha_disponible' =>
                    now(),

                'observacion' =>
                    'Equipo reemplazo web 10C.',

                'activo' =>
                    true,
            ]);

        /*
         * La venta del equipo original dejo
         * disponible = 0.
         *
         * Incorporamos el reemplazo fisico al mismo
         * producto/almacen para que vuelva a existir
         * una unidad disponible.
         */
        DB::table(
            'existencias_productos'
        )
            ->where(
                'producto_id',
                $equipoOriginal->producto_id
            )
            ->where(
                'almacen_id',
                $equipoOriginal->almacen_actual_id
            )
            ->increment(
                'cantidad_disponible',
                1,
                [
                    'updated_at' =>
                        now(),
                ]
            );

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

        $rol =
            Rol::query()
                ->where(
                    'codigo',
                    $codigoRol
                )
                ->firstOrFail();

        $usuario
            ->roles()
            ->attach(
                $rol->id
            );

        return $usuario;
    }
}