<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\PoliticaDescuento;
use App\Services\GestorPoliticaDescuentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class GestorPoliticaDescuentoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function datosBase(array $sobrescrituras = []): array
    {
        return array_merge([
            'codigo' => 'POL-' . Str::upper(Str::random(8)),
            'nombre' => 'Política de prueba',
            'categoria_producto_id' => null,
            'base_antiguedad' => 'FECHA_DISPONIBLE',
            'dias_desde' => 0,
            'dias_hasta' => 30,
            'porcentaje_maximo' => 5,
            'utilidad_minima_bob' => 300,
            'permite_precio_costo' => false,
            'requiere_autorizacion' => false,
            'vigente_desde' => now()->toDateString(),
            'vigente_hasta' => null,
            'activo' => true,
        ], $sobrescrituras);
    }

    public function test_crea_una_politica(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase()
        );

        $this->assertInstanceOf(
            PoliticaDescuento::class,
            $politica
        );

        $this->assertDatabaseHas(
            'politicas_descuentos',
            [
                'id' => $politica->id,
                'nombre' => 'Política de prueba',
                'dias_desde' => 0,
                'dias_hasta' => 30,
                'porcentaje_maximo' => 5,
                'utilidad_minima_bob' => 300,
                'activo' => 1,
            ]
        );
    }

    public function test_actualiza_una_politica(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase()
        );

        $actualizada = $servicio->actualizar(
            $politica,
            [
                'codigo' => $politica->codigo,
                'nombre' => 'Política actualizada',
                'dias_desde' => 31,
                'dias_hasta' => 60,
                'porcentaje_maximo' => 10,
                'utilidad_minima_bob' => 250,
                'permite_precio_costo' => false,
                'requiere_autorizacion' => true,
                'vigente_desde' => now()->toDateString(),
                'vigente_hasta' => null,
                'activo' => true,
            ]
        );

        $this->assertSame(
            'Política actualizada',
            $actualizada->nombre
        );

        $this->assertSame(
            31,
            $actualizada->dias_desde
        );

        $this->assertSame(
            60,
            $actualizada->dias_hasta
        );

        $this->assertDatabaseHas(
            'politicas_descuentos',
            [
                'id' => $politica->id,
                'nombre' => 'Política actualizada',
                'dias_desde' => 31,
                'dias_hasta' => 60,
                'porcentaje_maximo' => 10,
                'utilidad_minima_bob' => 250,
                'requiere_autorizacion' => 1,
            ]
        );
    }

    public function test_activa_una_politica(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase([
                'activo' => false,
            ])
        );

        $resultado = $servicio->activar(
            $politica
        );

        $this->assertTrue(
            (bool) $resultado->activo
        );

        $this->assertDatabaseHas(
            'politicas_descuentos',
            [
                'id' => $politica->id,
                'activo' => 1,
            ]
        );
    }

    public function test_desactiva_una_politica(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase()
        );

        $resultado = $servicio->desactivar(
            $politica
        );

        $this->assertFalse(
            (bool) $resultado->activo
        );

        $this->assertDatabaseHas(
            'politicas_descuentos',
            [
                'id' => $politica->id,
                'activo' => 0,
            ]
        );
    }

    public function test_permite_politica_sin_limite_superior_de_antiguedad(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase([
                'dias_desde' => 91,
                'dias_hasta' => null,
                'porcentaje_maximo' => 20,
            ])
        );

        $this->assertNull(
            $politica->dias_hasta
        );

        $this->assertDatabaseHas(
            'politicas_descuentos',
            [
                'id' => $politica->id,
                'dias_desde' => 91,
                'dias_hasta' => null,
            ]
        );
    }

    public function test_permite_configurar_utilidad_minima(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase([
                'utilidad_minima_bob' => 450,
            ])
        );

        $this->assertSame(
            450.0,
            (float) $politica->utilidad_minima_bob
        );
    }

    public function test_permite_configurar_autorizacion(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase([
                'requiere_autorizacion' => true,
            ])
        );

        $this->assertTrue(
            (bool) $politica->requiere_autorizacion
        );
    }

    public function test_permite_configurar_venta_al_costo(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase([
                'permite_precio_costo' => true,
                'utilidad_minima_bob' => 0,
            ])
        );

        $this->assertTrue(
            (bool) $politica->permite_precio_costo
        );

        $this->assertSame(
            0.0,
            (float) $politica->utilidad_minima_bob
        );
    }

    public function test_no_permite_dias_negativos(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'dias_desde' => -1,
            ])
        );
    }

    public function test_no_permite_rango_de_dias_invertido(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'dias_desde' => 61,
                'dias_hasta' => 30,
            ])
        );
    }

    public function test_no_permite_porcentaje_superior_a_cien(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'porcentaje_maximo' => 101,
            ])
        );
    }

    public function test_no_permite_porcentaje_negativo(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'porcentaje_maximo' => -1,
            ])
        );
    }

    public function test_no_permite_utilidad_minima_negativa(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'utilidad_minima_bob' => -1,
            ])
        );
    }

    public function test_no_permite_fecha_de_vigencia_invertida(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'vigente_desde' => '2026-09-30',
                'vigente_hasta' => '2026-09-01',
            ])
        );
    }

    public function test_no_permite_codigo_vacio(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'codigo' => '   ',
            ])
        );
    }

    public function test_no_permite_nombre_vacio(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            GestorPoliticaDescuentoService::class
        )->crear(
            $this->datosBase([
                'nombre' => '   ',
            ])
        );
    }

    public function test_no_permite_codigo_duplicado(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $datos = $this->datosBase([
            'codigo' => 'POL-DUPLICADA',
        ]);

        $servicio->crear($datos);

        $this->expectException(
            InvalidArgumentException::class
        );

        $servicio->crear($datos);
    }

    public function test_actualizar_no_considera_su_propio_codigo_como_duplicado(): void
    {
        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase([
                'codigo' => 'POL-PROPIA',
            ])
        );

        $resultado = $servicio->actualizar(
            $politica,
            [
                'codigo' => 'POL-PROPIA',
                'nombre' => 'Nombre actualizado',
                'dias_desde' => 0,
                'dias_hasta' => 30,
                'porcentaje_maximo' => 5,
                'utilidad_minima_bob' => 300,
                'permite_precio_costo' => false,
                'requiere_autorizacion' => false,
                'vigente_desde' => now()->toDateString(),
                'vigente_hasta' => null,
                'activo' => true,
            ]
        );

        $this->assertSame(
            'POL-PROPIA',
            $resultado->codigo
        );
    }

    public function test_permite_politica_especifica_por_categoria(): void
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'LAP-' . Str::upper(
                Str::random(6)
            ),
            'nombre' => 'Laptops',
            'descripcion' => null,
            'activo' => true,
        ]);

        $servicio = app(
            GestorPoliticaDescuentoService::class
        );

        $politica = $servicio->crear(
            $this->datosBase([
                'categoria_producto_id' =>
                    $categoria->id,
            ])
        );

        $this->assertSame(
            $categoria->id,
            $politica->categoria_producto_id
        );
    }
}