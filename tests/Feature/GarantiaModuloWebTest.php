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

class GarantiaModuloWebTest extends TestCase
{
    use RefreshDatabase;

    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->cliente = Cliente::create([
            'nombre_completo' => 'Cliente módulo garantías',
            'telefono' => '70000123',
            'correo' => 'postventa@oneshop.test',
            'activo' => true,
        ]);
    }

    public function test_usuario_con_permiso_puede_abrir_bandeja_de_garantias(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($vendedor)
            ->get(route('garantias.index'))
            ->assertOk()
            ->assertSee('Garantías y postventa')
            ->assertSee('Buscar')
            ->assertSee('Casos activos')
            ->assertSee('data-auto-filter', false)
            ->assertSee('Filtrado automático');
    }

    public function test_bandeja_busca_por_equipo_serial_y_cliente(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        [$equipo, , $garantia] =
            $this->crearVentaConGarantia($vendedor);

        $this
            ->actingAs($vendedor)
            ->get(
                route(
                    'garantias.index',
                    ['buscar' => $equipo->codigo_interno]
                )
            )
            ->assertOk()
            ->assertSee($garantia->numero)
            ->assertSee($equipo->codigo_interno);

        $this
            ->actingAs($vendedor)
            ->get(
                route(
                    'garantias.index',
                    ['buscar' => $equipo->serial_fabricante]
                )
            )
            ->assertOk()
            ->assertSee($garantia->numero);

        $this
            ->actingAs($vendedor)
            ->get(
                route(
                    'garantias.index',
                    ['buscar' => 'Cliente módulo garantías']
                )
            )
            ->assertOk()
            ->assertSee($garantia->numero)
            ->assertSee('Cliente módulo garantías');
    }

    public function test_modulo_muestra_garantia_y_ficha_del_caso(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        [$equipo, , $garantia] =
            $this->crearVentaConGarantia($vendedor);

        $caso = app(CasoGarantiaService::class)
            ->abrirCaso(
                garantiaId: $garantia->id,
                usuarioId: $vendedor->id,
                motivoCliente:
                    'El equipo se reinicia de forma intermitente durante el uso.',
                observacion:
                    'Caso de prueba para la bandeja de garantías.'
            );

        $this
            ->actingAs($vendedor)
            ->get(route('garantias.show', $garantia))
            ->assertOk()
            ->assertSee($garantia->numero)
            ->assertSee($equipo->codigo_interno)
            ->assertSee($caso->numero)
            ->assertSee('Caso de garantía');

        $this
            ->actingAs($vendedor)
            ->get(route('garantias.casos.show', $caso))
            ->assertOk()
            ->assertSee('Caso ' . $caso->numero)
            ->assertSee($equipo->codigo_interno)
            ->assertSee('Abierto')
            ->assertSee('Ver garantía completa')
            ->assertSee('Progreso del caso')
            ->assertSee('Seguimiento del caso');
    }

    public function test_ficha_del_caso_muestra_equipo_disponible_para_reemplazo(): void
    {
        $administrador = $this->usuarioConRol('ADMINISTRADOR');

        [$equipoOriginal, , $garantia] =
            $this->crearVentaConGarantia($administrador);

        $caso = app(CasoGarantiaService::class)
            ->abrirCaso(
                garantiaId: $garantia->id,
                usuarioId: $administrador->id,
                motivoCliente:
                    'Falla confirmada para probar selector de reemplazo.',
                observacion:
                    'Caso de prueba del selector de equipos.'
            );

        app(CasoGarantiaService::class)
            ->registrarDiagnostico(
                casoId: $caso->id,
                usuarioId: $administrador->id,
                diagnostico:
                    'Diagnóstico confirmado para habilitar el cambio.'
            );

        $equipoReemplazo = $this->crearEquipoDisponible();

        $this
            ->actingAs($administrador)
            ->get(route('garantias.casos.show', $caso))
            ->assertOk()
            ->assertSee('Elegir equipo')
            ->assertSee($equipoReemplazo->codigo_interno)
            ->assertDontSee('No hay equipos disponibles para reemplazo.');

        $this->assertNotSame(
            $equipoOriginal->id,
            $equipoReemplazo->id
        );
    }

    public function test_usuario_sin_permiso_no_puede_ver_modulo(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $this
            ->actingAs($usuario)
            ->get(route('garantias.index'))
            ->assertForbidden();
    }

    private function crearVentaConGarantia(
        User $usuario
    ): array {
        $equipo = $this->crearEquipoDisponible();

        $venta = app(VentaService::class)
            ->registrarVentaDirecta(
                vendedorId: $usuario->id,
                equiposIds: [$equipo->id],
                clienteId: $this->cliente->id
            );

        $garantia = Garantia::query()
            ->whereHas(
                'detalleVenta',
                function ($query) use ($venta, $equipo) {
                    $query
                        ->where('venta_id', $venta->id)
                        ->where('equipo_id', $equipo->id);
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
        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $almacen = Almacen::query()
            ->where('activo', true)
            ->orderByDesc('principal')
            ->firstOrFail();

        $estadoDisponible = EstadoEquipo::query()
            ->where('codigo', 'DISPONIBLE')
            ->firstOrFail();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-MOD-GAR-' . Str::uuid(),
            'nombre' => 'Laptop módulo garantías',
            'modelo' => 'MOD-GAR-TEST',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        PoliticaGarantia::create([
            'codigo' => 'GAR-MOD-' . Str::uuid(),
            'nombre' => 'Garantía módulo postventa',
            'categoria_producto_id' => $categoria->id,
            'producto_id' => $producto->id,
            'duracion_meses' => 6,
            'condiciones' => 'Cobertura estándar de prueba.',
            'exclusiones' => 'Daños físicos.',
            'vigente_desde' => now()->subDay(),
            'vigente_hasta' => null,
            'activo' => true,
        ]);

        $equipo = Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estadoDisponible->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'EQ-MOD-GAR-' . Str::uuid(),
            'serial_fabricante' => 'SER-MOD-GAR-' . Str::uuid(),
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'observacion' => null,
            'activo' => true,
        ]);

        PrecioEquipo::create([
            'equipo_id' => $equipo->id,
            'tipo_cambio_id' => null,
            'costo_total_snapshot' => 2900,
            'precio_sugerido' => 3500,
            'precio_publico' => 3500,
            'precio_minimo_autorizado' => 3200,
            'vigente_desde' => now(),
            'vigente_hasta' => null,
            'vigente' => true,
            'aprobado_por_id' => null,
            'observacion' => 'Precio para módulo de garantías.',
        ]);

        DB::table('existencias_productos')->insert([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
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
            ->where('codigo', $codigoRol)
            ->firstOrFail();

        $usuario->roles()->attach($rol->id);

        return $usuario;
    }
}
