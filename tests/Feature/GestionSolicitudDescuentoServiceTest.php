<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\SolicitudDescuento;
use App\Models\User;
use App\Services\GestionSolicitudDescuentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class GestionSolicitudDescuentoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_aprueba_y_registra_trazabilidad_de_la_decision(): void
    {
        [$equipo, $precio] = $this->crearEquipoConPrecio();
        $vendedor = User::factory()->create();
        $admin = User::factory()->create();

        $solicitud = SolicitudDescuento::create([
            'precio_equipo_id' => $precio->id,
            'politica_descuento_id' => null,
            'cliente_id' => null,
            'solicitado_por_id' => $vendedor->id,
            'precio_publico_snapshot' => 5000,
            'precio_solicitado' => 4500,
            'descuento_solicitado' => 500,
            'porcentaje_descuento' => 10,
            'costo_total_snapshot' => 3500,
            'utilidad_proyectada' => 1000,
            'estado' => 'PENDIENTE',
            'motivo' => 'Cliente listo para cerrar.',
        ]);

        $resultado = app(
            GestionSolicitudDescuentoService::class
        )->aprobar(
            $solicitud->id,
            $admin->id,
            'Autorizado por margen suficiente.',
            'WHATSAPP'
        );

        $this->assertSame('APROBADA', $resultado->estado);
        $this->assertSame($admin->id, $resultado->respondido_por_id);
        $this->assertSame('WHATSAPP', $resultado->medio_respuesta);
        $this->assertNotNull($resultado->fecha_respuesta);
    }

    public function test_rechaza_solicitud_pendiente(): void
    {
        [, $precio] = $this->crearEquipoConPrecio();
        $vendedor = User::factory()->create();
        $admin = User::factory()->create();

        $solicitud = SolicitudDescuento::create([
            'precio_equipo_id' => $precio->id,
            'politica_descuento_id' => null,
            'cliente_id' => null,
            'solicitado_por_id' => $vendedor->id,
            'precio_publico_snapshot' => 5000,
            'precio_solicitado' => 4200,
            'descuento_solicitado' => 800,
            'porcentaje_descuento' => 16,
            'costo_total_snapshot' => 3500,
            'utilidad_proyectada' => 700,
            'estado' => 'PENDIENTE',
            'motivo' => 'Solicitud especial.',
        ]);

        $resultado = app(
            GestionSolicitudDescuentoService::class
        )->rechazar(
            $solicitud->id,
            $admin->id,
            'Margen demasiado bajo.',
            'SISTEMA'
        );

        $this->assertSame('RECHAZADA', $resultado->estado);
        $this->assertSame('Margen demasiado bajo.', $resultado->motivo_respuesta);
    }

    public function test_no_procesa_dos_veces_la_misma_solicitud(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [, $precio] = $this->crearEquipoConPrecio();
        $vendedor = User::factory()->create();
        $admin = User::factory()->create();

        $solicitud = SolicitudDescuento::create([
            'precio_equipo_id' => $precio->id,
            'politica_descuento_id' => null,
            'cliente_id' => null,
            'solicitado_por_id' => $vendedor->id,
            'precio_publico_snapshot' => 5000,
            'precio_solicitado' => 4500,
            'descuento_solicitado' => 500,
            'porcentaje_descuento' => 10,
            'costo_total_snapshot' => 3500,
            'utilidad_proyectada' => 1000,
            'estado' => 'PENDIENTE',
        ]);

        $service = app(GestionSolicitudDescuentoService::class);
        $service->aprobar($solicitud->id, $admin->id);
        $service->aprobar($solicitud->id, $admin->id);
    }

    private function crearEquipoConPrecio(): array
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'AUTH-' . Str::upper(Str::random(8)),
            'nombre' => 'Laptops autorización',
            'activo' => true,
        ]);

        $almacen = Almacen::create([
            'codigo' => 'ALM-' . Str::upper(Str::random(8)),
            'nombre' => 'Almacén autorización',
            'ciudad' => 'Oruro',
            'principal' => true,
            'activo' => true,
        ]);

        $estado = EstadoEquipo::create([
            'codigo' => 'DISP-' . Str::upper(Str::random(8)),
            'nombre' => 'Disponible',
            'es_final' => true,
            'orden' => 1,
            'activo' => true,
        ]);

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-' . Str::uuid(),
            'nombre' => 'Laptop autorización',
            'modelo' => 'TEST',
            'es_serializado' => true,
            'activo' => true,
        ]);

        $equipo = Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'EQ-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => now(),
            'fecha_disponible' => now(),
            'observacion' => null,
            'activo' => true,
        ]);

        $precio = PrecioEquipo::create([
            'equipo_id' => $equipo->id,
            'tipo_cambio_id' => null,
            'costo_total_snapshot' => 3500,
            'precio_sugerido' => 5000,
            'precio_publico' => 5000,
            'precio_minimo_autorizado' => 4600,
            'vigente_desde' => now(),
            'vigente_hasta' => null,
            'vigente' => true,
            'aprobado_por_id' => null,
            'observacion' => null,
        ]);

        return [$equipo, $precio];
    }
}
