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
use App\Models\User;
use App\Services\CasoGarantiaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CasoGarantiaGestionWebTest extends TestCase
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

        $this->cliente =
            Cliente::create([
                'nombre_completo' =>
                    'Cliente gestión web',
                'telefono' =>
                    '70000994',
                'activo' =>
                    true,
            ]);
    }

    public function test_tecnico_puede_recorrer_diagnostico_intervencion_y_cierre_por_web(): void
    {
        [$caso, $equipo] =
            $this->crearCasoAbierto();

        $origen =
            '/inventario/'
            . $equipo->codigo_interno;

        $this
            ->actingAs($this->tecnico)
            ->from($origen)
            ->post(
                route(
                    'garantias.casos.diagnostico',
                    $caso
                ),
                [
                    '_caso_id' =>
                        $caso->id,
                    'diagnostico_final' =>
                        'Falla de alimentación identificada.',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHas('success');

        $this->assertDatabaseHas(
            'casos_garantia',
            [
                'id' =>
                    $caso->id,
                'estado' =>
                    'DIAGNOSTICADO',
            ]
        );

        $this
            ->actingAs($this->tecnico)
            ->from($origen)
            ->post(
                route(
                    'garantias.casos.intervenciones.store',
                    $caso
                ),
                [
                    '_caso_id' =>
                        $caso->id,
                    'tipo_intervencion' =>
                        'REPARACION',
                    'descripcion' =>
                        'Se corrigió el contacto del conector de alimentación.',
                    'resultado' =>
                        'Equipo estable después de las pruebas.',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHas('success');

        $this->assertDatabaseHas(
            'casos_garantia',
            [
                'id' =>
                    $caso->id,
                'estado' =>
                    'EN_PROCESO',
            ]
        );

        $this->assertDatabaseHas(
            'intervenciones_garantia',
            [
                'caso_garantia_id' =>
                    $caso->id,
                'usuario_id' =>
                    $this->tecnico->id,
                'tipo_intervencion' =>
                    'REPARACION',
            ]
        );

        $this
            ->actingAs($this->tecnico)
            ->from($origen)
            ->post(
                route(
                    'garantias.casos.cerrar',
                    $caso
                ),
                [
                    '_caso_id' =>
                        $caso->id,
                    'resolucion' =>
                        'Equipo probado y funcionando correctamente.',
                ]
            )
            ->assertRedirect($origen)
            ->assertSessionHas('success');

        $this->assertDatabaseHas(
            'casos_garantia',
            [
                'id' =>
                    $caso->id,
                'estado' =>
                    'CERRADO',
                'cerrado_por_id' =>
                    $this->tecnico->id,
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

    public function test_vendedor_no_puede_usar_rutas_de_gestion_tecnica(): void
    {
        [$caso] =
            $this->crearCasoAbierto();

        $this
            ->actingAs($this->vendedor)
            ->post(
                route(
                    'garantias.casos.diagnostico',
                    $caso
                ),
                [
                    '_caso_id' =>
                        $caso->id,
                    'diagnostico_final' =>
                        'No debería registrarse.',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'casos_garantia',
            [
                'id' =>
                    $caso->id,
                'estado' =>
                    'ABIERTO',
                'diagnostico_final' =>
                    null,
            ]
        );
    }

    private function crearCasoAbierto(): array
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
                        'Falla reportada por cliente.'
                );

        return [
            $caso,
            $equipo->fresh(),
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
                    'PROD-WEB-10B-'
                    . Str::uuid(),
                'nombre' =>
                    'Laptop web gestión garantía',
                'modelo' =>
                    'TEST-WEB-10B',
                'descripcion' =>
                    null,
                'es_serializado' =>
                    true,
                'activo' =>
                    true,
            ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-WEB-10B-'
                . Str::uuid(),
            'nombre' =>
                'Garantía web 10B',
            'categoria_producto_id' =>
                $categoria->id,
            'producto_id' =>
                $producto->id,
            'duracion_meses' =>
                6,
            'condiciones' =>
                'Garantía estándar web.',
            'exclusiones' =>
                'Daños físicos.',
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
                    'EQ-WEB-10B-'
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
                'Precio web 10B.',
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

        $rol =
            Rol::query()
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
