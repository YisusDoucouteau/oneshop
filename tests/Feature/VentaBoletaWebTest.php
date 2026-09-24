<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaBoletaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_vendedor_puede_generar_boleta_pdf_de_venta(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $venta =
            Venta::create([
                'numero' =>
                    'VEN-BOLETA-001',

                'cliente_id' =>
                    null,

                'cliente_nombre_snapshot' =>
                    'Cliente boleta',

                'cliente_telefono_snapshot' =>
                    '71234567',

                'vendedor_id' =>
                    $vendedor->id,

                'reserva_id' =>
                    null,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    3850,

                'descuento_total' =>
                    0,

                'total' =>
                    3850,

                'estado' =>
                    'REGISTRADA',

                'observacion' =>
                    null,
            ]);

        $categoria =
            CategoriaProducto::query()
                ->where('activo', true)
                ->firstOrFail();

        $producto =
            Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    null,

                'codigo' =>
                    'PROD-PDF-'
                    . Str::upper(
                        Str::random(6)
                    ),

                'nombre' =>
                    'Producto PDF',

                'modelo' =>
                    'Snapshot',

                'descripcion' =>
                    null,

                'es_serializado' =>
                    true,

                'activo' =>
                    true,
            ]);

        DetalleVenta::create([
            'venta_id' =>
                $venta->id,

            'producto_id' =>
                $producto->id,

            'equipo_id' =>
                null,

            'cantidad' =>
                1,

            'precio_lista_snapshot' =>
                3850,

            'descuento_unitario' =>
                0,

            'precio_unitario' =>
                3850,

            'costo_unitario_snapshot' =>
                3000,

            'marca_snapshot' =>
                'Lenovo',

            'modelo_snapshot' =>
                'ThinkPad T480',

            'codigo_interno_snapshot' =>
                '1726',

            'condicion_venta_snapshot' =>
                'USADO',

            'subtotal' =>
                3850,

            'observacion' =>
                null,
        ]);

        $respuesta =
            $this
                ->actingAs($vendedor)
                ->get(
                    route(
                        'ventas.boleta',
                        $venta
                    )
                );

        $respuesta->assertOk();

        $this->assertStringContainsString(
            'application/pdf',
            (string)
            $respuesta->headers->get(
                'content-type'
            )
        );

        $this->assertStringStartsWith(
            '%PDF',
            $respuesta->getContent()
        );
    }

    public function test_tecnico_no_puede_generar_boleta_de_venta(): void
    {
        $tecnico =
            $this->usuarioConRol(
                'TECNICO'
            );

        $vendedor =
            User::factory()->create([
                'activo' =>
                    true,
            ]);

        $venta =
            Venta::create([
                'numero' =>
                    'VEN-BOLETA-002',

                'cliente_id' =>
                    null,

                'cliente_nombre_snapshot' =>
                    'Cliente boleta',

                'cliente_telefono_snapshot' =>
                    null,

                'vendedor_id' =>
                    $vendedor->id,

                'reserva_id' =>
                    null,

                'fecha_venta' =>
                    now(),

                'subtotal' =>
                    1000,

                'descuento_total' =>
                    0,

                'total' =>
                    1000,

                'estado' =>
                    'REGISTRADA',

                'observacion' =>
                    null,
            ]);

        $this
            ->actingAs($tecnico)
            ->get(
                route(
                    'ventas.boleta',
                    $venta
                )
            )
            ->assertForbidden();
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
