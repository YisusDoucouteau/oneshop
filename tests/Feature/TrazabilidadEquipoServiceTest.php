<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Lote;
use App\Models\Proveedor;
use App\Models\DetalleLote;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\EstadoEquipo;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Garantia;
use App\Models\CasoGarantia;
use App\Models\PoliticaGarantia;
use App\Services\TrazabilidadEquipoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrazabilidadEquipoServiceTest extends TestCase
{
    use RefreshDatabase;


    public function test_muestra_origen_del_equipo_por_lote(): void
    {
        $equipo = $this->crearEquipoConLote();


        $eventos =
            app(TrazabilidadEquipoService::class)
            ->obtener($equipo);


        $this->assertTrue(
            $eventos->contains(
                fn ($evento) =>
                $evento['tipo'] === 'importacion'
            )
        );
    }



    public function test_muestra_registro_inicial_del_equipo(): void
    {
        $equipo =
            $this->crearEquipo();


        $eventos =
            app(TrazabilidadEquipoService::class)
            ->obtener($equipo);


        $this->assertTrue(
            $eventos->contains(
                fn ($evento) =>
                $evento['titulo']
                    ===
                    'Equipo registrado'
            )
        );
    }



   public function test_muestra_casos_de_garantia()
{
   $equipo = $this->crearEquipo();

    $producto = $equipo->producto;

    $categoria = $producto->categoria;


    $cliente = Cliente::create([
        'nombre_completo' =>
            'Cliente prueba garantia',

        'telefono' =>
            '70000001',

        'activo' =>
            true,
    ]);


    $usuario = User::create([
        'name' =>
            'Usuario prueba garantia',

        'email' =>
            'garantia-'.Str::uuid().'@test.com',

        'password' =>
            bcrypt('123456'),

        'activo' =>
            true,
    ]);


    $venta = Venta::create([

        'numero' =>
            'VEN-'.Str::uuid(),

        'cliente_id' =>
            $cliente->id,

        'vendedor_id' =>
            $usuario->id,

        'estado' =>
            'REGISTRADA',

        'subtotal' =>
            3500,

        'total' =>
            3500,

        'fecha_venta' =>
            now(),

    ]);


    $detalleVenta = DetalleVenta::create([

        'venta_id' =>
            $venta->id,

        'producto_id' =>
            $producto->id,

        'equipo_id' =>
            $equipo->id,

        'cantidad' =>
            1,

        'precio_lista_snapshot' =>
            3500,

        'descuento_unitario' =>
            0,

        'precio_unitario' =>
            3500,

        'costo_unitario_snapshot' =>
            2900,

        'subtotal' =>
            3500,

    ]);


    $politicaGarantia = PoliticaGarantia::create([

        'codigo' =>
            'GAR-TEST-'.Str::uuid(),

        'nombre' =>
            'Garantía estándar prueba',

        'categoria_producto_id' =>
            $categoria->id,

        'producto_id' =>
            $producto->id,

        'duracion_meses' =>
            6,

        'condiciones' =>
            'Garantía estándar para pruebas.',

        'exclusiones' =>
            'Golpes, humedad y daños físicos.',

        'vigente_desde' =>
            now()->subDay(),

        'vigente_hasta' =>
            null,

        'activo' =>
            true,

    ]);


    Garantia::create([

        'numero' =>
            'GAR-'.Str::uuid(),

        'detalle_venta_id' =>
            $detalleVenta->id,

        'politica_garantia_id' =>
            $politicaGarantia->id,

        'fecha_inicio' =>
            now(),

        'fecha_fin' =>
            now()->addMonths(6),

        'duracion_meses_snapshot' =>
            6,

        'condiciones_snapshot' =>
            'Garantía estándar.',

        'estado' =>
            'VIGENTE',

    ]);


    $resultado = app(TrazabilidadEquipoService::class)
        ->obtener($equipo);


   $this->assertTrue(
    $resultado->contains(
        fn ($evento) =>
            str_contains(
                mb_strtolower($evento['titulo']),
                'garant'
            )
    )
);
}



    private function crearEquipo(): Equipo
    {

        $categoria =
            CategoriaProducto::create([

                'codigo' =>
                'LAPTOP',

                'nombre' =>
                'Laptop',

                'activo' =>
                true,

            ]);



        $producto =
            Producto::create([

                'categoria_producto_id' =>
                $categoria->id,

                'codigo' =>
                'PROD-' . Str::uuid(),

                'nombre' =>
                'Laptop prueba',

                'modelo' =>
                'TEST',

                'es_serializado' =>
                true,

                'activo' =>
                true,

            ]);



        $almacen =
            Almacen::create([

                'codigo' =>
                'ORURO',

                'nombre' =>
                'Almacen Oruro',

                'ciudad' =>
                'Oruro',

                'principal' =>
                true,

                'activo' =>
                true,

            ]);



        $estado =
            EstadoEquipo::create([

                'codigo' =>
                'DISPONIBLE',

                'nombre' =>
                'Disponible',

                'activo' =>
                true,

            ]);



        return Equipo::create([

            'producto_id' =>
            $producto->id,

            'almacen_actual_id' =>
            $almacen->id,

            'estado_actual_id' =>
            $estado->id,

            'codigo_interno' =>
            'EQ-' . Str::uuid(),

            'fecha_registro' =>
            now(),

            'activo' =>
            true,

        ]);
    }





    private function crearEquipoConLote(): Equipo
    {

        $equipo =
            $this->crearEquipo();


        $proveedor =
            Proveedor::create([

                'nombre' =>
                'Proveedor prueba',

                'activo' =>
                true,

            ]);



        $lote =
            Lote::create([

                'proveedor_id' =>
                $proveedor->id,

                'codigo' =>
                'IMP-001',

                'estado' =>
                'RECIBIDO',

            ]);



        $detalle =
            DetalleLote::create([

                'lote_id' =>
                $lote->id,

                'producto_id' =>
                $equipo->producto_id,

                'cantidad_esperada' =>
                1,

                'cantidad_recibida' =>
                1,

            ]);



        $equipo->update([

            'detalle_lote_id' =>
            $detalle->id,

        ]);


        return $equipo->fresh();
    }
}
