<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PoliticaDescuento;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Services\EvaluacionPropuestaPrecioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class EvaluacionPropuestaPrecioServiceTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Datos auxiliares
    |--------------------------------------------------------------------------
    */

    private function crearCategoria(): CategoriaProducto
    {
        return CategoriaProducto::create([
            'codigo' => 'LAP-' . Str::upper(
                Str::random(6)
            ),

            'nombre' => 'Laptops',

            'descripcion' => null,

            'activo' => true,
        ]);
    }

    private function crearAlmacen(): Almacen
    {
        return Almacen::create([
            'codigo' => 'ORU-' . Str::upper(
                Str::random(6)
            ),

            'nombre' => 'Almacén Oruro',

            'ciudad' => 'Oruro',

            'direccion' => null,

            'principal' => true,

            'activo' => true,
        ]);
    }

    private function crearEstadoDisponible(): EstadoEquipo
    {
        return EstadoEquipo::create([
            'codigo' => 'DISP-' . Str::upper(
                Str::random(6)
            ),

            'nombre' => 'Disponible',

            'descripcion' => null,

            'es_final' => false,

            'orden' => 1,

            'activo' => true,
        ]);
    }

    private function crearEquipoConPrecio(
        float $costo = 4300,
        float $precioPublicado = 5200
    ): Equipo {
        $categoria =
            $this->crearCategoria();

        $almacen =
            $this->crearAlmacen();

        $estado =
            $this->crearEstadoDisponible();

        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'marca_id' => null,

            'codigo' =>
                'PROD-' . Str::uuid(),

            'nombre' =>
                'Laptop de evaluación',

            'modelo' =>
                'TEST',

            'descripcion' =>
                null,

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);

        $equipo = Equipo::create([
            'producto_id' =>
                $producto->id,

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estado->id,

            'condicion_fisica_id' =>
                null,

            'codigo_interno' =>
                'EQ-' . Str::uuid(),

            'serial_fabricante' =>
                null,

            'fecha_registro' =>
                now()->subDays(15),

            'fecha_disponible' =>
                now()->subDays(15),

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
                $costo,

            'precio_sugerido' =>
                $precioPublicado,

            'precio_publico' =>
                $precioPublicado,

            'precio_minimo_autorizado' =>
                null,

            'vigente_desde' =>
                now()->subDays(15),

            'vigente_hasta' =>
                null,

            'vigente' =>
                true,

            'aprobado_por_id' =>
                null,

            'observacion' =>
                'Precio para evaluación.',
        ]);

        return $equipo;
    }

    private function crearPolitica(
        Equipo $equipo,
        array $sobrescrituras = []
    ): PoliticaDescuento {
        return PoliticaDescuento::create(
            array_merge([
                'codigo' =>
                    'POL-' . Str::upper(
                        Str::random(8)
                    ),

                'nombre' =>
                    'Política 0 a 30 días',

                'categoria_producto_id' =>
                    $equipo
                        ->producto
                        ->categoria_producto_id,

                'base_antiguedad' =>
                    'FECHA_DISPONIBLE',

                'dias_desde' =>
                    0,

                'dias_hasta' =>
                    30,

                'porcentaje_maximo' =>
                    10,

                'utilidad_minima_bob' =>
                    300,

                'permite_precio_costo' =>
                    false,

                'requiere_autorizacion' =>
                    false,

                'vigente_desde' =>
                    now()
                        ->subDay()
                        ->toDateString(),

                'vigente_hasta' =>
                    null,

                'activo' =>
                    true,
            ], $sobrescrituras)
        );
    }

    private function servicio(): EvaluacionPropuestaPrecioService
    {
        return app(
            EvaluacionPropuestaPrecioService::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Evaluación básica
    |--------------------------------------------------------------------------
    */

    public function test_evalua_una_propuesta_comercial(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $this->crearPolitica(
            $equipo
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                5000
            );

        $this->assertSame(
            5200.0,
            $resultado['precio_publicado']
        );

        $this->assertSame(
            5000.0,
            $resultado['precio_propuesto']
        );

        $this->assertSame(
            200.0,
            $resultado['descuento']
        );

        $this->assertEqualsWithDelta(
            3.8462,
            $resultado['porcentaje_descuento'],
            0.0001
        );

        $this->assertSame(
            4300.0,
            $resultado['costo_real']
        );

        $this->assertSame(
            700.0,
            $resultado['ganancia']
        );

        $this->assertEqualsWithDelta(
            16.2791,
            $resultado['porcentaje_utilidad'],
            0.0001
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Descuento
    |--------------------------------------------------------------------------
    */

    public function test_calcula_correctamente_el_descuento(): void
    {
        $equipo =
            $this->crearEquipoConPrecio(
                4300,
                5200
            );

        $this->crearPolitica(
            $equipo
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                4950
            );

        $this->assertSame(
            250.0,
            $resultado['descuento']
        );

        $this->assertEqualsWithDelta(
            4.8077,
            $resultado['porcentaje_descuento'],
            0.0001
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ganancia
    |--------------------------------------------------------------------------
    */

    public function test_calcula_la_ganancia_sobre_el_costo_real(): void
    {
        $equipo =
            $this->crearEquipoConPrecio(
                4300,
                5200
            );

        $this->crearPolitica(
            $equipo
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                4900
            );

        $this->assertSame(
            600.0,
            $resultado['ganancia']
        );

        $this->assertEqualsWithDelta(
            13.9535,
            $resultado['porcentaje_utilidad'],
            0.0001
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Precio superior al publicado
    |--------------------------------------------------------------------------
    */

    public function test_permite_propuesta_superior_al_precio_publicado(): void
    {
        $equipo =
            $this->crearEquipoConPrecio(
                2500,
                2500
            );

        $this->crearPolitica(
            $equipo
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                2900
            );

        /*
         * El precio propuesto supera al publicado.
         *
         * Por tanto:
         *
         * 2500 - 2900 = -400
         *
         * No es un descuento.
         * Es un incremento de Bs 400.
         */

        $this->assertSame(
            -400.0,
            $resultado['descuento']
        );

        $this->assertSame(
            2900.0,
            $resultado['precio_propuesto']
        );

        $this->assertSame(
            400.0,
            $resultado['ganancia']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Política aplicable
    |--------------------------------------------------------------------------
    */

    public function test_identifica_la_politica_aplicable(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $politica =
            $this->crearPolitica(
                $equipo
            );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                5000
            );

        $this->assertNotNull(
            $resultado['politica']
        );

        $this->assertSame(
            $politica->id,
            $resultado['politica']['id']
        );

        $this->assertSame(
            'Política 0 a 30 días',
            $resultado['politica']['nombre']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Propuesta dentro de política
    |--------------------------------------------------------------------------
    */

    public function test_marca_como_aprobable_una_propuesta_dentro_de_politica(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $this->crearPolitica(
            $equipo,
            [
                'porcentaje_maximo' =>
                    10,

                'utilidad_minima_bob' =>
                    300,
            ]
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                5000
            );

        $this->assertTrue(
            $resultado['cumple_politica']
        );

        $this->assertFalse(
            $resultado['requiere_autorizacion']
        );

        $this->assertSame(
            'APROBABLE',
            $resultado['estado']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Descuento superior al permitido
    |--------------------------------------------------------------------------
    */

    public function test_detecta_descuento_superior_al_permitido(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $this->crearPolitica(
            $equipo,
            [
                'porcentaje_maximo' =>
                    5,
            ]
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                4800
            );

        $this->assertFalse(
            $resultado['cumple_politica']
        );

        $this->assertSame(
            'NO_CUMPLE_POLITICA',
            $resultado['estado']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidad inferior a la mínima
    |--------------------------------------------------------------------------
    */

    public function test_detecta_utilidad_inferior_a_la_minima(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $this->crearPolitica(
            $equipo,
            [
                'utilidad_minima_bob' =>
                    1000,
            ]
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                5000
            );

        /*
         * La utilidad de la propuesta es 700 Bs.
         *
         * La política recomienda como mínimo 1000 Bs,
         * por lo que existe una advertencia.
         *
         * No representa una pérdida ni un incumplimiento
         * duro de la política.
         */

        $this->assertTrue(
            $resultado['cumple_politica']
        );

        $this->assertTrue(
            $resultado['evaluacion_politica']['es_advertencia']
        );

        $this->assertSame(
            'APROBABLE',
            $resultado['estado']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Autorización
    |--------------------------------------------------------------------------
    */

    public function test_identifica_cuando_se_requiere_autorizacion(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $this->crearPolitica(
            $equipo,
            [
                'requiere_autorizacion' =>
                    true,
            ]
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                5000
            );

        $this->assertTrue(
            $resultado['requiere_autorizacion']
        );

        $this->assertTrue(
            $resultado['cumple_politica']
        );

        $this->assertSame(
            'REQUIERE_AUTORIZACION',
            $resultado['estado']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Venta al costo
    |--------------------------------------------------------------------------
    */

    public function test_identifica_venta_al_costo(): void
    {
        $equipo =
            $this->crearEquipoConPrecio(
                4300,
                5200
            );

        $this->crearPolitica(
            $equipo,
            [
                'permite_precio_costo' =>
                    true,

                'utilidad_minima_bob' =>
                    0,
            ]
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                4300
            );

        $this->assertSame(
            0.0,
            $resultado['ganancia']
        );

        $this->assertSame(
            'AL_COSTO',
            $resultado['resultado_economico']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Pérdida
    |--------------------------------------------------------------------------
    */

    public function test_identifica_perdida(): void
    {
        $equipo =
            $this->crearEquipoConPrecio(
                4300,
                5200
            );

        $this->crearPolitica(
            $equipo,
            [
                'permite_precio_costo' =>
                    true,

                'utilidad_minima_bob' =>
                    0,
            ]
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                4000
            );

        $this->assertSame(
            -300.0,
            $resultado['ganancia']
        );

        $this->assertSame(
            'PERDIDA',
            $resultado['resultado_economico']
        );

        $this->assertSame(
            'NO_RECOMENDADA',
            $resultado['estado']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sin política
    |--------------------------------------------------------------------------
    */

    public function test_requiere_revision_si_no_existe_politica(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                5000
            );

        $this->assertNull(
            $resultado['politica']
        );

        $this->assertNull(
            $resultado['cumple_politica']
        );

        $this->assertSame(
            'REQUIERE_REVISION',
            $resultado['estado']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Equipo inexistente
    |--------------------------------------------------------------------------
    */

    public function test_no_permite_evaluar_equipo_inexistente(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->servicio()->evaluar(
            999999,
            5000
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Precio negativo
    |--------------------------------------------------------------------------
    */

    public function test_no_permite_precio_propuesto_negativo(): void
    {
        $equipo =
            $this->crearEquipoConPrecio();

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->servicio()->evaluar(
            $equipo->id,
            -1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Descuento y utilidad
    |--------------------------------------------------------------------------
    */

    public function test_no_confunde_descuento_con_utilidad(): void
    {
        $equipo =
            $this->crearEquipoConPrecio(
                4300,
                5200
            );

        $this->crearPolitica(
            $equipo
        );

        $resultado =
            $this->servicio()->evaluar(
                $equipo->id,
                5000
            );

        /*
         * Descuento:
         *
         * 5200 - 5000 = 200
         */

        $this->assertSame(
            200.0,
            $resultado['descuento']
        );

        /*
         * Ganancia:
         *
         * 5000 - 4300 = 700
         */

        $this->assertSame(
            700.0,
            $resultado['ganancia']
        );

        $this->assertNotSame(
            $resultado['descuento'],
            $resultado['ganancia']
        );
    }
}