<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteTrazabilidadWebTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_administrador_operativo_puede_abrir_catalogo_y_expediente_de_trazabilidad(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $equipo = $this->crearEquipo();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.trazabilidad.index'))
            ->assertOk()
            ->assertSee('Trazabilidad de equipos')
            ->assertSee($equipo->codigo_interno)
            ->assertSee('data-oneshop-datatable', false);

        $this
            ->actingAs($usuario)
            ->get(route('reportes.trazabilidad.detalle', $equipo))
            ->assertOk()
            ->assertSee('Expediente cronológico')
            ->assertSee($equipo->codigo_interno)
            ->assertSee('Equipo registrado');
    }

    public function test_expediente_de_trazabilidad_tiene_impresion_y_pdf(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $equipo = $this->crearEquipo();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.trazabilidad.imprimir', $equipo))
            ->assertOk()
            ->assertSee('Trazabilidad del equipo')
            ->assertSee($equipo->codigo_interno);

        $this
            ->actingAs($usuario)
            ->get(route('reportes.trazabilidad.pdf', $equipo))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_vendedor_no_puede_acceder_a_trazabilidad_formal(): void
    {
        $usuario = $this->usuarioConRol('VENDEDOR');
        $equipo = $this->crearEquipo();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.trazabilidad.index'))
            ->assertForbidden();

        $this
            ->actingAs($usuario)
            ->get(route('reportes.trazabilidad.detalle', $equipo))
            ->assertForbidden();
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

    private function crearEquipo(): Equipo
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'CAT-TRAZ',
            'nombre' => 'Portátiles trazabilidad',
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'codigo' => 'PROD-TRAZ',
            'nombre' => 'Equipo trazabilidad',
            'modelo' => 'TRAZ-01',
            'activo' => true,
        ]);

        $almacen = Almacen::create([
            'codigo' => 'ORURO-TRAZ',
            'nombre' => 'Almacén Oruro Trazabilidad',
            'ciudad' => 'Oruro',
            'principal' => true,
            'activo' => true,
        ]);

        $estado = EstadoEquipo::create([
            'codigo' => 'DISPONIBLE-TRAZ',
            'nombre' => 'Disponible trazabilidad',
            'activo' => true,
        ]);

        return Equipo::create([
            'producto_id' => $producto->id,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'codigo_interno' => 'EQ-TRAZ-001',
            'serial_fabricante' => 'SER-TRAZ-001',
            'fecha_registro' => now(),
            'activo' => true,
        ]);
    }
}
