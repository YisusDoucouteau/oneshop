<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\PoliticaDescuento;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Services\GestorPrecioEquipoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GestorPrecioEquipoServiceTest extends TestCase
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
            'codigo' => 'LAPTOP-' . Str::upper(Str::random(6)),
            'nombre' => 'Laptops',
            'descripcion' => 'Categoria para pruebas.',
            'activo' => true,
        ]);
    }

    private function crearAlmacen(): Almacen
    {
        return Almacen::create([
            'codigo' => 'ALM-' . Str::upper(Str::random(6)),
            'nombre' => 'Almacen principal',
            'ciudad' => 'Oruro',
            'direccion' => 'Direccion de prueba',
            'principal' => true,
            'activo' => true,
        ]);
    }

    private function crearEstadoDisponible(): EstadoEquipo
    {
        return EstadoEquipo::create([
            'codigo' => 'DISPONIBLE',
            'nombre' => 'Disponible',
            'descripcion' => 'Equipo disponible para venta.',
            'es_final' => false,
            'orden' => 1,
            'activo' => true,
        ]);
    }

    private function crearEquipo(
        ?CategoriaProducto $categoria = null,
        ?float $costo = 4300,
        ?float $precioPublicado = 5200,
        int $diasAntiguedad = 15
    ): Equipo {
        $categoria ??= $this->crearCategoria();

        $almacen = $this->crearAlmacen();
        $estado = $this->crearEstadoDisponible();

        $producto = Producto::create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => null,
            'codigo' => 'PROD-' . Str::uuid(),
            'nombre' => 'Laptop de prueba',
            'modelo' => 'TEST',
            'descripcion' => null,
            'es_serializado' => true,
            'activo' => true,
        ]);

        $fechaDisponible = now()
            ->subDays($diasAntiguedad);

        $equipo = Equipo::create([
            'producto_id' => $producto->id,
            'detalle_lote_id' => null,
            'almacen_actual_id' => $almacen->id,
            'estado_actual_id' => $estado->id,
            'condicion_fisica_id' => null,
            'codigo_interno' => 'EQ-' . Str::uuid(),
            'serial_fabricante' => null,
            'fecha_registro' => $fechaDisponible,
            'fecha_disponible' => $fechaDisponible,
            'observacion' => null,
            'activo' => true,
        ]);

        if (
            $costo !== null
            && $precioPublicado !== null
        ) {
            PrecioEquipo::create([
                'equipo_id' => $equipo->id,
                'tipo_cambio_id' => null,
                'costo_total_snapshot' => $costo,
                'precio_sugerido' => $precioPublicado,
                'precio_publico' => $precioPublicado,
                'precio_minimo_autorizado' => null,
                'vigente_desde' => now(),
                'vigente_hasta' => null,
                'vigente' => true,
                'aprobado_por_id' => null,
                'observacion' => 'Precio para prueba.',
            ]);
        }

        return $equipo;
    }

    private function crearPolitica(
        CategoriaProducto $categoria,
        int $diasDesde = 0,
        ?int $diasHasta = 30,
        float $porcentajeMaximo = 5,
        float $utilidadMinima = 300,
        bool $requiereAutorizacion = false,
        bool $permitePrecioCosto = false
    ): PoliticaDescuento {
        return PoliticaDescuento::create([
            'codigo' => 'POL-' . Str::upper(Str::random(8)),
            'nombre' => 'Politica de prueba',
            'categoria_producto_id' => $categoria->id,
            'base_antiguedad' => 'FECHA_DISPONIBLE',
            'dias_desde' => $diasDesde,
            'dias_hasta' => $diasHasta,
            'porcentaje_maximo' => $porcentajeMaximo,
            'utilidad_minima_bob' => $utilidadMinima,
            'permite_precio_costo' => $permitePrecioCosto,
            'requiere_autorizacion' => $requiereAutorizacion,
            'vigente_desde' => now()->toDateString(),
            'vigente_hasta' => null,
            'activo' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Evaluacion basica
    |--------------------------------------------------------------------------
    */

    public function test_evalua_un_equipo_con_su_precio_vigente(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            15
        );

        $this->crearPolitica(
            $categoria,
            0,
            30,
            5,
            300
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            5000
        );

        $this->assertSame(
            $equipo->id,
            $resultado['equipo_id']
        );

        $this->assertSame(
            4300.0,
            $resultado['costo_real']
        );

        $this->assertSame(
            5200.0,
            $resultado['precio_vigente']['precio_publico']
        );

        $this->assertSame(
            15,
            $resultado['antiguedad']['dias']
        );

        $this->assertSame(
            700.0,
            $resultado['propuesta']['utilidad']
        );

        $this->assertNotNull(
            $resultado['politica']
        );

        $this->assertNotNull(
            $resultado['evaluacion_politica']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Descuento
    |--------------------------------------------------------------------------
    */

    public function test_calcula_el_descuento_sobre_el_precio_publicado(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            15
        );

        $this->crearPolitica(
            $categoria,
            0,
            30,
            10,
            300
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            5000
        );

        $evaluacion =
            $resultado['evaluacion_politica'];

        $this->assertSame(
            200.0,
            $evaluacion['descuento']
        );

        $this->assertEquals(
            3.85,
            $evaluacion['porcentaje_descuento']
        );

        $this->assertSame(
            700.0,
            $evaluacion['utilidad']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Politica por antiguedad
    |--------------------------------------------------------------------------
    */

    public function test_aplica_politica_segun_antiguedad(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            45
        );

        $politica = $this->crearPolitica(
            $categoria,
            31,
            60,
            10,
            300
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            5000
        );

        $this->assertSame(
            45,
            $resultado['antiguedad']['dias']
        );

        $this->assertSame(
            $politica->id,
            $resultado['politica']['id']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Politica general
    |--------------------------------------------------------------------------
    */

    public function test_utiliza_politica_general_si_no_existe_una_especifica(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            15
        );

        $politica = PoliticaDescuento::create([
            'codigo' => 'GENERAL-' . Str::upper(Str::random(8)),
            'nombre' => 'Politica general',
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
        ]);

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            5000
        );

        $this->assertSame(
            $politica->id,
            $resultado['politica']['id']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Politica especifica tiene prioridad
    |--------------------------------------------------------------------------
    */

    public function test_prioriza_politica_especifica_sobre_politica_general(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            15
        );

        PoliticaDescuento::create([
            'codigo' => 'GENERAL-' . Str::upper(Str::random(8)),
            'nombre' => 'Politica general',
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
        ]);

        $especifica = $this->crearPolitica(
            $categoria,
            0,
            30,
            10,
            250
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            5000
        );

        $this->assertSame(
            $especifica->id,
            $resultado['politica']['id']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Caso excepcional: precio superior al publicado
    |--------------------------------------------------------------------------
    */

    public function test_permite_propuesta_superior_al_precio_publicado(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            2000,
            2500,
            15
        );

        $this->crearPolitica(
            $categoria,
            0,
            30,
            5,
            300
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            2900
        );

        $evaluacion =
            $resultado['evaluacion_politica'];

        $this->assertSame(
            0.0,
            $evaluacion['descuento']
        );

        $this->assertSame(
            900.0,
            $evaluacion['utilidad']
        );

        $this->assertFalse(
            $evaluacion['genera_perdida']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Descuento excesivo
    |--------------------------------------------------------------------------
    */

    public function test_detecta_descuento_superior_al_permitido(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            15
        );

        $this->crearPolitica(
            $categoria,
            0,
            30,
            5,
            300
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            4800
        );

        $evaluacion =
            $resultado['evaluacion_politica'];

        $this->assertTrue(
            $evaluacion['supera_descuento_maximo']
        );

        $this->assertTrue(
            $evaluacion['requiere_autorizacion']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidad minima
    |--------------------------------------------------------------------------
    */

    public function test_detecta_utilidad_inferior_a_la_recomendada(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            70
        );

        $this->crearPolitica(
            $categoria,
            61,
            90,
            20,
            300
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            4500
        );

        $evaluacion =
            $resultado['evaluacion_politica'];

        $this->assertSame(
            200.0,
            $evaluacion['utilidad']
        );

        $this->assertTrue(
            $evaluacion['utilidad_inferior_minima']
        );

        $this->assertTrue(
            $evaluacion['es_advertencia']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Venta al costo
    |--------------------------------------------------------------------------
    */

    public function test_detecta_venta_al_costo(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            100
        );

        $this->crearPolitica(
            $categoria,
            91,
            null,
            30,
            300,
            false,
            false
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            4300
        );

        $evaluacion =
            $resultado['evaluacion_politica'];

        $this->assertTrue(
            $evaluacion['vende_a_costo']
        );

        $this->assertTrue(
            $evaluacion['requiere_autorizacion']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Venta por debajo del costo
    |--------------------------------------------------------------------------
    */

    public function test_detecta_perdida(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            100
        );

        $this->crearPolitica(
            $categoria,
            91,
            null,
            30,
            300,
            false,
            true
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            4000
        );

        $evaluacion =
            $resultado['evaluacion_politica'];

        $this->assertTrue(
            $evaluacion['genera_perdida']
        );

        $this->assertTrue(
            $evaluacion['requiere_autorizacion']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Equipo inexistente
    |--------------------------------------------------------------------------
    */

    public function test_no_permite_evaluar_equipo_inexistente(): void
    {
        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $servicio->evaluar(
            999999,
            5000
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sin politica
    |--------------------------------------------------------------------------
    */

    public function test_requiere_revision_si_no_existe_politica_aplicable(): void
    {
        $categoria = $this->crearCategoria();

        $equipo = $this->crearEquipo(
            $categoria,
            4300,
            5200,
            15
        );

        $servicio = app(
            GestorPrecioEquipoService::class
        );

        $resultado = $servicio->evaluar(
            $equipo->id,
            5000
        );

        $this->assertNull(
            $resultado['politica']
        );

        $this->assertNull(
            $resultado['evaluacion_politica']
        );
    }
}