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

class CasoGarantiaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $administrador;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->administrador = $this->usuarioConRol(
            'ADMINISTRADOR'
        );

        $this->cliente = Cliente::create([
            'nombre_completo' => 'Cliente garantía prueba',
            'documento' => null,
            'telefono' => '70000991',
            'correo' => 'garantia@oneshop.test',
            'direccion' => null,
            'observacion' => null,
            'activo' => true,
        ]);
    }

    public function test_abre_caso_de_garantia_sin_alterar_venta_equipo_ni_stock(): void
    {
        [$equipo, $venta, $garantia] =
            $this->crearVentaConGarantia();

        $estadoVendidoId =
            EstadoEquipo::query()
                ->where('codigo', 'VENDIDO')
                ->value('id');

        $existenciaAntes = DB::table(
            'existencias_productos'
        )
            ->where(
                'producto_id',
                $equipo->producto_id
            )
            ->where(
                'almacen_id',
                $equipo->almacen_actual_id
            )
            ->first();

        $caso = app(CasoGarantiaService::class)
            ->abrirCaso(
                garantiaId: $garantia->id,
                usuarioId: $this->administrador->id,
                motivoCliente:
                    'El equipo no enciende correctamente.',
                observacion:
                    'Recepción de prueba de garantía.'
            );

        $this->assertSame(
            'ABIERTO',
            $caso->estado
        );

        $this->assertSame(
            $garantia->id,
            $caso->garantia_id
        );

        $this->assertSame(
            $equipo->id,
            $caso->equipo_afectado_id
        );

        $this->assertSame(
            $this->administrador->id,
            $caso->recibido_por_id
        );

        $this->assertStringStartsWith(
            'CAS-GAR-' . now()->format('Ymd') . '-',
            $caso->numero
        );

        $this->assertDatabaseHas(
            'casos_garantia',
            [
                'id' => $caso->id,
                'garantia_id' => $garantia->id,
                'equipo_afectado_id' => $equipo->id,
                'estado' => 'ABIERTO',
                'tipo_caso' => 'GARANTIA',
            ]
        );

        $equipo->refresh();
        $venta->refresh();
        $garantia->refresh();

        $this->assertSame(
            (int) $estadoVendidoId,
            (int) $equipo->estado_actual_id
        );

        $this->assertSame(
            'REGISTRADA',
            $venta->estado
        );

        $this->assertSame(
            'VIGENTE',
            $garantia->estado
        );

        $existenciaDespues = DB::table(
            'existencias_productos'
        )
            ->where(
                'producto_id',
                $equipo->producto_id
            )
            ->where(
                'almacen_id',
                $equipo->almacen_actual_id
            )
            ->first();

        $this->assertNotNull(
            $existenciaAntes
        );

        $this->assertNotNull(
            $existenciaDespues
        );

        $this->assertSame(
            (int) $existenciaAntes->cantidad_disponible,
            (int) $existenciaDespues->cantidad_disponible
        );

        $this->assertSame(
            (int) $existenciaAntes->cantidad_reservada,
            (int) $existenciaDespues->cantidad_reservada
        );
    }

    public function test_no_permite_dos_casos_activos_para_la_misma_garantia(): void
    {
        [, , $garantia] =
            $this->crearVentaConGarantia();

        $servicio =
            app(CasoGarantiaService::class);

        $servicio->abrirCaso(
            garantiaId: $garantia->id,
            usuarioId: $this->administrador->id,
            motivoCliente:
                'Primer caso activo.'
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'Ya existe un caso abierto para esta garantía.'
        );

        $servicio->abrirCaso(
            garantiaId: $garantia->id,
            usuarioId: $this->administrador->id,
            motivoCliente:
                'Segundo caso que no debe crearse.'
        );
    }

    public function test_no_permite_abrir_caso_con_garantia_vencida(): void
{
    [, , $garantia] =
        $this->crearVentaConGarantia();

    /*
     * Simulamos una garantía históricamente válida,
     * pero cuya vigencia ya terminó.
     *
     * No podemos modificar solamente fecha_fin porque
     * la BD exige coherencia cronológica.
     */
    $fechaInicio =
        now()->subMonths(2);

    $garantia->update([
        'fecha_inicio' =>
            $fechaInicio,

        'fecha_limite_cambio_inicial' =>
            $fechaInicio
                ->copy()
                ->addDays(7),

        'fecha_fin' =>
            now()->subMonth(),
    ]);

    $this->expectException(
        ReglaNegocioException::class
    );

    $this->expectExceptionMessage(
        'La garantía no se encuentra vigente.'
    );

    app(CasoGarantiaService::class)
        ->abrirCaso(
            garantiaId:
                $garantia->id,

            usuarioId:
                $this->administrador->id,

            motivoCliente:
                'Intento sobre garantía vencida.'
        );
}

    public function test_usuario_sin_permiso_no_puede_abrir_caso(): void
    {
        [, , $garantia] =
            $this->crearVentaConGarantia();

        $tecnico = $this->usuarioConRol(
            'TECNICO'
        );

        $this->expectException(
            ReglaNegocioException::class
        );

        $this->expectExceptionMessage(
            'El usuario no cuenta con permiso para registrar casos de garantía.'
        );

        app(CasoGarantiaService::class)
            ->abrirCaso(
                garantiaId: $garantia->id,
                usuarioId: $tecnico->id,
                motivoCliente:
                    'Caso que no debe registrarse.'
            );
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
                ->where('codigo', 'LAPTOP')
                ->firstOrFail();

        $almacen =
            Almacen::query()
                ->where('activo', true)
                ->orderByDesc('principal')
                ->firstOrFail();

        $estadoDisponible =
            EstadoEquipo::query()
                ->where('codigo', 'DISPONIBLE')
                ->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,
            'marca_id' => null,
            'codigo' =>
                'PROD-' . Str::uuid(),
            'nombre' =>
                'Laptop garantía prueba',
            'modelo' => 'TEST-GAR',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        PoliticaGarantia::create([
            'codigo' =>
                'GAR-' . Str::uuid(),
            'nombre' =>
                'Garantía prueba postventa',
            'categoria_producto_id' =>
                $categoria->id,
            'producto_id' =>
                $producto->id,
            'duracion_meses' => 6,
            'condiciones' =>
                'Garantía estándar de prueba.',
            'exclusiones' =>
                'Golpes, humedad y daños físicos.',
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
                'EQ-GAR-' . Str::uuid(),
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
                'Precio prueba garantía.',
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
        $usuario = User::factory()->create([
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
