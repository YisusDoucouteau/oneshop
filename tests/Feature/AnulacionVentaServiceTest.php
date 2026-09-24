<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CasoGarantia;
use App\Models\CategoriaProducto;
use App\Models\DetalleVenta;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Garantia;
use App\Models\MetodoPago;
use App\Models\PoliticaGarantia;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\ComercialSeeder;
use App\Services\AnulacionVentaService;
use App\Services\PagoService;
use Database\Seeders\CatalogoInventarioSeeder;
use Database\Seeders\CatalogoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnulacionVentaServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $administradorOperativo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogoInventarioSeeder::class);
        $this->seed(CatalogoSeeder::class);
        $this->seed(SeguridadSeeder::class);

        $this->administradorOperativo =
            User::factory()->create([
                'activo' => true,
            ]);

        $rol = Rol::query()
            ->where(
                'codigo',
                'ADMIN_OPERATIVO'
            )
            ->firstOrFail();

        $this->administradorOperativo
            ->roles()
            ->attach(
                $rol->id
            );
    }

    public function test_anula_venta_sin_pagos_y_revierte_inventario_garantia_y_estado(): void
    {
        [
            $venta,
            $equipo,
            $garantia,
        ] = $this->crearVentaRegistrada();

        $servicio =
            app(AnulacionVentaService::class);

        $resultado =
            $servicio->anular(
                ventaId:
                    $venta->id,

                usuarioId:
                    $this
                        ->administradorOperativo
                        ->id,

                motivo:
                    'Registro realizado por error.'
            );

        $this->assertSame(
            'ANULADA',
            $resultado->estado
        );

        $this->assertSame(
            $this->administradorOperativo->id,
            $resultado->anulado_por_id
        );

        $this->assertNotNull(
            $resultado->fecha_anulacion
        );

        $this->assertSame(
            'Registro realizado por error.',
            $resultado->motivo_anulacion
        );

        $estadoDisponible =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'DISPONIBLE'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'equipos',
            [
                'id' =>
                    $equipo->id,

                'estado_actual_id' =>
                    $estadoDisponible->id,
            ]
        );

        $this->assertDatabaseHas(
            'existencias_productos',
            [
                'producto_id' =>
                    $equipo->producto_id,

                'almacen_id' =>
                    $equipo->almacen_actual_id,

                'cantidad_disponible' =>
                    1,

                'cantidad_reservada' =>
                    0,
            ]
        );

        $tipoAnulacion = DB::table(
            'tipos_movimientos_inventario'
        )
            ->where(
                'codigo',
                'ANULACION_VENTA'
            )
            ->first();

        $this->assertNotNull(
            $tipoAnulacion
        );

        $this->assertDatabaseHas(
            'movimientos_inventario',
            [
                'producto_id' =>
                    $equipo->producto_id,

                'almacen_id' =>
                    $equipo->almacen_actual_id,

                'tipo_movimiento_id' =>
                    $tipoAnulacion->id,

                'cambio_disponible' =>
                    1,

                'cambio_reservado' =>
                    0,

                'tipo_referencia' =>
                    'ANULACION_VENTA',

                'referencia_id' =>
                    $venta->id,
            ]
        );

        $this->assertDatabaseHas(
            'garantias',
            [
                'id' =>
                    $garantia->id,

                'estado' =>
                    'ANULADA',
            ]
        );

        /*
         * La venta y su detalle permanecen como historial.
         */
        $this->assertDatabaseHas(
            'ventas',
            [
                'id' =>
                    $venta->id,

                'estado' =>
                    'ANULADA',
            ]
        );

        $this->assertDatabaseHas(
            'detalles_ventas',
            [
                'venta_id' =>
                    $venta->id,

                'equipo_id' =>
                    $equipo->id,

                'precio_unitario' =>
                    '4500.00',
            ]
        );
    }

    public function test_no_permite_anular_venta_con_pago_verificado(): void
    {
        [
            $venta,
            $equipo,
            $garantia,
        ] = $this->crearVentaRegistrada();

        $metodo = MetodoPago::create([
    'codigo' =>
        'EFECTIVO',

    'nombre' =>
        'Efectivo',

    'requiere_verificacion' =>
        false,

    'activo' =>
        true,
]);

        app(PagoService::class)
            ->registrarPagoVenta(
                ventaId:
                    $venta->id,

                metodoPagoId:
                    $metodo->id,

                monto:
                    '100.00',

                registradoPorId:
                    $this
                        ->administradorOperativo
                        ->id
            );

        try {
            app(AnulacionVentaService::class)
                ->anular(
                    ventaId:
                        $venta->id,

                    usuarioId:
                        $this
                            ->administradorOperativo
                            ->id,

                    motivo:
                        'Intento con pago.'
                );

            $this->fail(
                'La venta con pago debía ser rechazada.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertStringContainsString(
                'pagos registrados',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas(
            'ventas',
            [
                'id' =>
                    $venta->id,

                'estado' =>
                    'REGISTRADA',
            ]
        );

        $this->assertDatabaseHas(
            'garantias',
            [
                'id' =>
                    $garantia->id,

                'estado' =>
                    'VIGENTE',
            ]
        );

        $estadoVendido =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'VENDIDO'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'equipos',
            [
                'id' =>
                    $equipo->id,

                'estado_actual_id' =>
                    $estadoVendido->id,
            ]
        );
    }

    public function test_no_permite_anular_si_la_garantia_ya_tiene_un_caso(): void
    {
        [
            $venta,
            $equipo,
            $garantia,
        ] = $this->crearVentaRegistrada();

        CasoGarantia::create([
            'numero' =>
                'CAS-' .
                Str::upper(
                    Str::ulid()
                ),

            'garantia_id' =>
                $garantia->id,

            'equipo_afectado_id' =>
                $equipo->id,

            'recibido_por_id' =>
                $this
                    ->administradorOperativo
                    ->id,

            'tipo_caso' =>
                'GARANTIA',

            'estado' =>
                'ABIERTO',

            'fecha_apertura' =>
                now(),

            'motivo_cliente' =>
                'Prueba de caso registrado.',

            'diagnostico_final' =>
                null,

            'resolucion' =>
                null,

            'fecha_cierre' =>
                null,

            'cerrado_por_id' =>
                null,

            'observacion' =>
                null,
        ]);

        try {
            app(AnulacionVentaService::class)
                ->anular(
                    ventaId:
                        $venta->id,

                    usuarioId:
                        $this
                            ->administradorOperativo
                            ->id,

                    motivo:
                        'Intento con garantía atendida.'
                );

            $this->fail(
                'La venta con caso de garantía debía ser rechazada.'
            );
        } catch (ReglaNegocioException $exception) {
            $this->assertStringContainsString(
                'caso de garantía',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas(
            'ventas',
            [
                'id' =>
                    $venta->id,

                'estado' =>
                    'REGISTRADA',
            ]
        );

        $this->assertDatabaseHas(
            'garantias',
            [
                'id' =>
                    $garantia->id,

                'estado' =>
                    'VIGENTE',
            ]
        );
    }

    public function test_no_permite_anular_dos_veces(): void
    {
        [
            $venta,
        ] = $this->crearVentaRegistrada();

        $servicio =
            app(AnulacionVentaService::class);

        $servicio->anular(
            ventaId:
                $venta->id,

            usuarioId:
                $this
                    ->administradorOperativo
                    ->id,

            motivo:
                'Primera anulación.'
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'ya se encuentra anulada'
        );

        $servicio->anular(
            ventaId:
                $venta->id,

            usuarioId:
                $this
                    ->administradorOperativo
                    ->id,

            motivo:
                'Segunda anulación.'
        );
    }

    private function crearVentaRegistrada(): array
    {
        $categoria =
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    'LAPTOP'
                )
                ->first();

        if (!$categoria) {
            $categoria =
                CategoriaProducto::create([
                    'codigo' =>
                        'LAPTOP',

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
                    'PROD-ANU-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Laptop anulación prueba',

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
                ->where(
                    'activo',
                    true
                )
                ->orderByDesc(
                    'principal'
                )
                ->first();

        if (!$almacen) {
            $almacen =
                Almacen::create([
                    'codigo' =>
                        'ORU-ANU',

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

        $estadoVendido =
            EstadoEquipo::query()
                ->where(
                    'codigo',
                    'VENDIDO'
                )
                ->firstOrFail();

        $equipo =
            Equipo::create([
                'producto_id' =>
                    $producto->id,

                'detalle_lote_id' =>
                    null,

                'almacen_actual_id' =>
                    $almacen->id,

                'estado_actual_id' =>
                    $estadoVendido->id,

                'condicion_fisica_id' =>
                    null,

                'codigo_interno' =>
                    'ANU-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'serial_fabricante' =>
                    null,

                'fecha_registro' =>
                    now()
                        ->subMonth(),

                'fecha_disponible' =>
                    now()
                        ->subMonth(),

                'observacion' =>
                    null,

                'activo' =>
                    true,
            ]);

        DB::table(
            'existencias_productos'
        )->insert([
            'producto_id' =>
                $producto->id,

            'almacen_id' =>
                $almacen->id,

            'cantidad_disponible' =>
                0,

            'cantidad_reservada' =>
                0,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        $vendedor =
            User::factory()->create([
                'activo' =>
                    true,
            ]);

        $venta =
            Venta::create([
                'numero' =>
                    'VEN-ANU-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'cliente_id' =>
                    null,

                'cliente_nombre_snapshot' =>
                    'Cliente Anulación',

                'cliente_telefono_snapshot' =>
                    '70000000',

                'vendedor_id' =>
                    $vendedor->id,

                'reserva_id' =>
                    null,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    4500,

                'descuento_total' =>
                    0,

                'total' =>
                    4500,

                'estado' =>
                    'REGISTRADA',

                'anulado_por_id' =>
                    null,

                'fecha_anulacion' =>
                    null,

                'motivo_anulacion' =>
                    null,

                'observacion' =>
                    null,
            ]);

        $detalle =
            DetalleVenta::create([
                'venta_id' =>
                    $venta->id,

                'producto_id' =>
                    $producto->id,

                'equipo_id' =>
                    $equipo->id,

                'cantidad' =>
                    1,

                'precio_lista_snapshot' =>
                    4500,

                'descuento_unitario' =>
                    0,

                'precio_unitario' =>
                    4500,

                'costo_unitario_snapshot' =>
                    4050,

                'margen_total_snapshot' =>
                    450,

                'ganancia_snapshot' =>
                    150,

                'hugo_snapshot' =>
                    150,

                'daniel_snapshot' =>
                    150,

                'tienda_snapshot' =>
                    150,

                'marca_snapshot' =>
                    'HP',

                'modelo_snapshot' =>
                    '840 G7',

                'codigo_interno_snapshot' =>
                    $equipo->codigo_interno,

                'condicion_venta_snapshot' =>
                    'USADO',

                'subtotal' =>
                    4500,

                'observacion' =>
                    null,
            ]);

        $politica =
            PoliticaGarantia::create([
                'codigo' =>
                    'GAR-ANU-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Garantía anulación prueba',

                'categoria_producto_id' =>
                    $categoria->id,

                'producto_id' =>
                    $producto->id,

                'duracion_meses' =>
                    6,

                'condiciones' =>
                    'Garantía estándar.',

                'exclusiones' =>
                    'Daños físicos.',

                'vigente_desde' =>
                    now()
                        ->subDay(),

                'vigente_hasta' =>
                    null,

                'activo' =>
                    true,
            ]);

        $garantia =
            Garantia::create([
                'numero' =>
                    'GRT-ANU-' .
                    Str::upper(
                        Str::random(8)
                    ),

                'detalle_venta_id' =>
                    $detalle->id,

                'politica_garantia_id' =>
                    $politica->id,

                'fecha_inicio' =>
                    now(),

                'fecha_fin' =>
                    now()
                        ->addMonths(6),

                'fecha_limite_cambio_inicial' =>
                    now()
                        ->addDays(7),

                'duracion_meses_snapshot' =>
                    6,

                'condiciones_snapshot' =>
                    'Garantía estándar.',

                'exclusiones_snapshot' =>
                    'Daños físicos.',

                'estado' =>
                    'VIGENTE',
            ]);

        return [
            $venta,
            $equipo,
            $garantia,
        ];
    }
}
