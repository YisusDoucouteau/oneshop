<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteInventarioWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La suite completa reutiliza el ciclo de RefreshDatabase entre clases.
        // Sembramos explícitamente los catálogos oficiales para que este test
        // no dependa del orden de ejecución ni del estado dejado por otra clase.
        $this->seed();
    }


    public function test_administrador_operativo_puede_abrir_reporte_de_inventario(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $equipo = $this->crearEquipo('DISPONIBLE');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.index'))
            ->assertOk()
            ->assertSee('Reporte de inventario')
            ->assertSee('Equipos')
            ->assertSee('data-oneshop-datatable', false)
            ->assertSee('Exportar CSV')
            ->assertSee($equipo->codigo_interno)
            ->assertSee($equipo->serial_fabricante)
            ->assertSee('Generar PDF')
            ->assertSee('Imprimir');
    }

    public function test_reporte_de_inventario_busca_y_filtra_por_almacen(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $almacenVisible = Almacen::query()
            ->where('codigo', 'ORURO_PRINCIPAL')
            ->firstOrFail();

        $almacenOculto = Almacen::create([
            'codigo' => 'REP-' . Str::upper(Str::random(6)),
            'nombre' => 'Almacén Secundario Reporte',
            'ciudad' => 'Cochabamba',
            'direccion' => null,
            'principal' => false,
            'activo' => true,
        ]);

        $visible = $this->crearEquipo(
            'DISPONIBLE',
            $almacenVisible,
            'Equipo Alfa Reporte'
        );

        $oculto = $this->crearEquipo(
            'DISPONIBLE',
            $almacenOculto,
            'Equipo Beta Reporte'
        );

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.index', [
                'almacen_id' => $almacenVisible->id,
                'q' => 'Alfa',
            ]))
            ->assertOk()
            ->assertSee($visible->codigo_interno)
            ->assertDontSee($oculto->codigo_interno)
            ->assertSee('1');
    }

    public function test_reporte_de_inventario_tiene_salida_de_impresion_y_pdf(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $equipo = $this->crearEquipo('RESERVADO');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.imprimir'))
            ->assertOk()
            ->assertSee('Imprimir reporte')
            ->assertSee($equipo->codigo_interno);

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_vendedor_no_puede_acceder_al_reporte_formal_de_inventario(): void
    {
        $usuario = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.index'))
            ->assertForbidden();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.pdf'))
            ->assertForbidden();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.imprimir'))
            ->assertForbidden();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.inventario.csv'))
            ->assertForbidden();
    }

    private function crearEquipo(
        string $estadoCodigo,
        ?Almacen $almacen = null,
        ?string $nombreProducto = null
    ): Equipo {
        $categoria = CategoriaProducto::query()
            ->where('codigo', 'LAPTOP')
            ->firstOrFail();

        $marca = Marca::create([
            'nombre' => 'Marca Reporte ' . Str::upper(Str::random(6)),
            'descripcion' => 'Marca para pruebas de reportes.',
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => 'PROD-REP-' . Str::upper(Str::random(8)),
            'nombre' => $nombreProducto ?: 'Laptop Reporte Inventario',
            'modelo' => 'Modelo ' . Str::upper(Str::random(4)),
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        $estado = EstadoEquipo::query()
            ->where('codigo', $estadoCodigo)
            ->firstOrFail();

        $almacen ??= Almacen::query()
            ->where('codigo', 'ORURO_PRINCIPAL')
            ->firstOrFail();

        $codigo = 'EQ-REP-' . Str::upper(Str::random(8));

        return Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => $codigo,
            'serial_fabricante' => 'SER-' . Str::upper(Str::random(10)),
            'fecha_registro' => now(),
            'fecha_disponible' => $estadoCodigo === 'DISPONIBLE' ? now() : null,
            'observacion' => null,
            'activo' => true,
        ]);
    }

    private function usuarioConRol(string $codigoRol): User
    {
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
