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
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CasoGarantiaWebTest extends TestCase
{
    use RefreshDatabase;

    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente garantía web',
            'documento' => null,
            'telefono' => '70000992',
            'correo' =>
                'garantia-web@oneshop.test',
            'direccion' => null,
            'observacion' => null,
            'activo' => true,
        ]);
    }

    public function test_vendedor_puede_abrir_caso_desde_la_web(): void
    {
        $vendedor =
            $this->usuarioConRol('VENDEDOR');

        [$equipo, , $garantia] =
            $this->crearVentaConGarantia(
                $vendedor
            );

        $urlEquipo = route(
            'inventario.show',
            [
                'equipo' =>
                    $equipo->codigo_interno,
            ]
        );

        $response = $this
            ->actingAs($vendedor)
            ->from($urlEquipo)
            ->post(
                route(
                    'garantias.casos.store',
                    $garantia
                ),
                [
                    'motivo_cliente' =>
                        'El equipo presenta una falla intermitente al iniciar.',
                    'observacion' =>
                        'Caso creado desde prueba web.',
                ]
            );

        $response
            ->assertRedirect($urlEquipo)
            ->assertSessionHas(
                'success'
            );

        $this->assertDatabaseHas(
            'casos_garantia',
            [
                'garantia_id' =>
                    $garantia->id,
                'equipo_afectado_id' =>
                    $equipo->id,
                'recibido_por_id' =>
                    $vendedor->id,
                'estado' =>
                    'ABIERTO',
                'tipo_caso' =>
                    'GARANTIA',
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
                'id' => $equipo->id,
                'estado_actual_id' =>
                    $estadoVendido->id,
            ]
        );
    }

    public function test_tecnico_sin_permiso_no_puede_abrir_caso_desde_la_web(): void
    {
        $administrador =
            $this->usuarioConRol(
                'ADMINISTRADOR'
            );

        [$equipo, , $garantia] =
            $this->crearVentaConGarantia(
                $administrador
            );

        $tecnico =
            $this->usuarioConRol(
                'TECNICO'
            );

        $this
            ->actingAs($tecnico)
            ->post(
                route(
                    'garantias.casos.store',
                    $garantia
                ),
                [
                    'motivo_cliente' =>
                        'Este caso no debe registrarse.',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'casos_garantia',
            [
                'garantia_id' =>
                    $garantia->id,
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
                'id' => $equipo->id,
                'estado_actual_id' =>
                    $estadoVendido->id,
            ]
        );
    }

    private function crearVentaConGarantia(
        User $usuario
    ): array {
        $equipo =
            $this->crearEquipoDisponible();

        $venta = app(VentaService::class)
            ->registrarVentaDirecta(
                vendedorId:
                    $usuario->id,
                equiposIds: [
                    $equipo->id,
                ],
                clienteId:
                    $this->cliente->id
            );

        $garantia = Garantia::query()
            ->whereHas(
                'detalleVenta',
                function ($query) use ($venta, $equipo) {
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
            $venta->fresh(),
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
                ->where('activo', true)
                ->orderByDesc('principal')
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
                'PROD-WEB-' . Str::uuid(),
            'nombre' =>
                'Laptop garantía web',
            'modelo' =>
                'TEST-WEB-GAR',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-WEB-' . Str::uuid(),
            'nombre' =>
                'Garantía web prueba',
            'categoria_producto_id' =>
                $categoria->id,
            'producto_id' =>
                $producto->id,
            'duracion_meses' => 6,
            'condiciones' =>
                'Garantía estándar web.',
            'exclusiones' =>
                'Daños físicos.',
            'vigente_desde' =>
                now()->subDay(),
            'vigente_hasta' => null,
            'activo' => true,
        ]);

        $equipo = Equipo::create([
            'producto_id' =>
                $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' =>
                $almacen->id,
            'estado_actual_id' =>
                $estadoDisponible->id,
            'condicion_fisica_id' => null,
            'codigo_interno' =>
                'EQ-WEB-GAR-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'observacion' => null,
            'activo' => true,
        ]);

        PrecioEquipo::create([
            'equipo_id' =>
                $equipo->id,
            'tipo_cambio_id' => null,
            'costo_total_snapshot' => 2900,
            'precio_sugerido' => 3500,
            'precio_publico' => 3500,
            'precio_minimo_autorizado' => 3200,
            'vigente_desde' => now(),
            'vigente_hasta' => null,
            'vigente' => true,
            'aprobado_por_id' => null,
            'observacion' =>
                'Precio prueba web.',
        ]);

        DB::table(
            'existencias_productos'
        )->insert([
            'producto_id' =>
                $producto->id,
            'almacen_id' =>
                $almacen->id,
            'cantidad_disponible' => 1,
            'cantidad_reservada' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $equipo;
    }

    private function usuarioConRol(
        string $codigoRol
    ): User {
        $usuario =
            User::factory()->create([
                'activo' => true,
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
