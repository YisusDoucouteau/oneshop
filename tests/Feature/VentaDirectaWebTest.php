<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use App\Services\ProcesadorVentaService;
use App\Services\RentabilidadRebajaService;
use App\Services\ValidadorVentaPrecioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class VentaDirectaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_vendedor_puede_abrir_formulario_de_venta_directa(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $equipo =
            $this->crearEquipoDisponible();

        $this
            ->actingAs($vendedor)
            ->get(
                route('ventas.create')
            )
            ->assertOk()
            ->assertSee(
                'Registrar venta directa'
            )
            ->assertSee(
                'Equipos disponibles'
            )
            ->assertSee(
                'Precio acordado'
            )
            ->assertSee(
                'Resumen de venta'
            )
            ->assertSee(
                $equipo->codigo_interno
            );
    }

    public function test_tecnico_no_puede_abrir_formulario_de_venta(): void
    {
        $tecnico =
            $this->usuarioConRol(
                'TECNICO'
            );

        $this
            ->actingAs($tecnico)
            ->get(
                route('ventas.create')
            )
            ->assertForbidden();
    }

    public function test_evaluacion_ajax_no_crea_solicitud_y_no_expone_detalle_interno(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $equipo =
            $this->crearEquipoDisponible();

        $validador =
            Mockery::mock(
                ValidadorVentaPrecioService::class
            );

        $validador
            ->shouldReceive('validar')
            ->once()
            ->with(
                $equipo->id,
                4800.0,
                null,
                null
            )
            ->andReturn([
                'permitido' =>
                    true,

                'precio_publicado' =>
                    5000.0,

                'precio_propuesto' =>
                    4800.0,

                'descuento' =>
                    200.0,

                'porcentaje_descuento' =>
                    4.0,

                'costo_actual' =>
                    3600.0,

                'utilidad' =>
                    1200.0,

                'tipo_cambio_id' =>
                    10,

                'tipo_cambio' =>
                    12.0,

                'moneda_origen' =>
                    'USD',

                'monto_origen' =>
                    300.0,

                'fuente_costo' =>
                    'TIPO_CAMBIO_COMERCIAL',

                'politica' =>
                    null,

                'requiere_aprobacion' =>
                    false,

                'solicitud' =>
                    null,

                'estado' =>
                    'APROBADO',
            ]);

        $rentabilidad =
            Mockery::mock(
                RentabilidadRebajaService::class
            );

        $rentabilidad
            ->shouldReceive('evaluar')
            ->once()
            ->withArgs(
                fn ($equipoRecibido, $precio) =>
                    $equipoRecibido->id
                        ===
                        $equipo->id
                    &&
                    $precio === 4800.0
            )
            ->andReturn([
                'equipo_id' =>
                    $equipo->id,

                'precio_publicado' =>
                    5000.0,

                'precio_rebaja' =>
                    4800.0,

                'costo_actualizado' =>
                    3600.0,

                'tipo_cambio_id' =>
                    10,

                'tipo_cambio' =>
                    12.0,

                'moneda_origen' =>
                    'USD',

                'monto_origen' =>
                    300.0,

                'fuente_costo' =>
                    'TIPO_CAMBIO_COMERCIAL',

                'ganancia' =>
                    400.0,

                'margen_total' =>
                    1200.0,

                'reparto' => [
                    'hugo' =>
                        400.0,

                    'daniel' =>
                        400.0,

                    'tienda' =>
                        400.0,
                ],
            ]);

        $this->app->instance(
            ValidadorVentaPrecioService::class,
            $validador
        );

        $this->app->instance(
            RentabilidadRebajaService::class,
            $rentabilidad
        );

        $respuesta =
            $this
                ->actingAs($vendedor)
                ->postJson(
                    route(
                        'ventas.equipos.evaluar-json',
                        $equipo->codigo_interno
                    ),
                    [
                        'precio' =>
                            4800,
                    ]
                );

        $respuesta
            ->assertOk()
            ->assertJsonPath(
                'permitido',
                true
            )
            ->assertJsonPath(
                'ganancia',
                400
            )
            ->assertJsonPath(
                'descuento',
                200
            )
            ->assertJsonMissing([
                'costo_actual',
            ])
            ->assertJsonMissing([
                'tipo_cambio',
            ])
            ->assertJsonMissing([
                'margen_total',
            ])
            ->assertJsonMissing([
                'reparto',
            ]);

        $this->assertDatabaseCount(
            'solicitudes_descuentos',
            0
        );
    }

    public function test_store_usa_usuario_autenticado_y_redirige_al_detalle(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $equipo =
            $this->crearEquipoDisponible();

        $venta =
            Venta::create([
                'numero' =>
                    'VEN-WEB-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'cliente_id' =>
                    null,

                'vendedor_id' =>
                    $vendedor->id,

                'reserva_id' =>
                    null,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    4800,

                'descuento_total' =>
                    200,

                'total' =>
                    4800,

                'estado' =>
                    'REGISTRADA',

                'observacion' =>
                    'Venta mostrador',
            ]);

        $procesador =
            Mockery::mock(
                ProcesadorVentaService::class
            );

        $procesador
            ->shouldReceive(
                'procesarVentaDirecta'
            )
            ->once()
            ->with(
                $vendedor->id,
                [
                    [
                        'equipo_id' =>
                            $equipo->id,

                        'precio' =>
                            4800.0,
                    ],
                ],
                null,
                'Venta mostrador'
            )
            ->andReturn(
                $venta
            );

        $this->app->instance(
            ProcesadorVentaService::class,
            $procesador
        );

        $this
            ->actingAs($vendedor)
            ->post(
                route('ventas.store'),
                [
                    'cliente_id' =>
                        null,

                    'equipos' => [
                        $equipo->id => [
                            'equipo_id' =>
                                $equipo->id,

                            'precio' =>
                                4800,
                        ],
                    ],

                    'observacion' =>
                        'Venta mostrador',
                ]
            )
            ->assertRedirect(
                route(
                    'ventas.show',
                    $venta
                )
            )
            ->assertSessionHas(
                'success',
                'Venta registrada correctamente.'
            );
    }

    public function test_error_de_negocio_regresa_al_formulario_con_mensaje(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $equipo =
            $this->crearEquipoDisponible();

        $procesador =
            Mockery::mock(
                ProcesadorVentaService::class
            );

        $procesador
            ->shouldReceive(
                'procesarVentaDirecta'
            )
            ->once()
            ->andThrow(
                new ReglaNegocioException(
                    'Existe una solicitud de aprobación pendiente para este precio.'
                )
            );

        $this->app->instance(
            ProcesadorVentaService::class,
            $procesador
        );

        $this
            ->actingAs($vendedor)
            ->from(
                route('ventas.create')
            )
            ->post(
                route('ventas.store'),
                [
                    'equipos' => [
                        $equipo->id => [
                            'equipo_id' =>
                                $equipo->id,

                            'precio' =>
                                4300,
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('ventas.create')
            )
            ->assertSessionHasErrors(
                'venta'
            );
    }

    private function crearEquipoDisponible(): Equipo
    {
        $categoria =
            CategoriaProducto::query()
                ->where('activo', true)
                ->first();

        if (!$categoria) {
            $categoria =
                CategoriaProducto::create([
                    'codigo' =>
                        'LAP-' .
                        Str::upper(
                            Str::random(5)
                        ),

                    'nombre' =>
                        'Laptop',

                    'activo' =>
                        true,
                ]);
        }

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    null,

                'codigo' =>
                    'PROD-VW-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Laptop venta web',

                'modelo' =>
                    'TEST',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        $almacen =
            Almacen::query()
                ->where('activo', true)
                ->first();

        if (!$almacen) {
            $almacen =
                Almacen::create([
                    'codigo' =>
                        'ORU-' .
                        Str::upper(
                            Str::random(4)
                        ),

                    'nombre' =>
                        'Almacén Oruro',

                    'ciudad' =>
                        'Oruro',

                    'direccion' =>
                        null,

                    'principal' =>
                        true,

                    'activo' =>
                        true,
                ]);
        }

        $estado =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $equipo =
            Equipo::create([
                'producto_id' =>
                    $producto->id,

                'almacen_actual_id' =>
                    $almacen->id,

                'estado_actual_id' =>
                    $estado->id,

                'codigo_interno' =>
                    'EQ-WEB-' .
                    Str::upper(
                        Str::random(8)
                    ),

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

            'costo_total_snapshot' =>
                3500,

            'precio_sugerido' =>
                5000,

            'precio_publico' =>
                5000,

            'precio_minimo_autorizado' =>
                4500,

            'vigente_desde' =>
                now(),

            'vigente' =>
                true,
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
