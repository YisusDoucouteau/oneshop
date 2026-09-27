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
use App\Models\UnidadAdquirida;
use App\Models\EnvioImportacion;
use App\Models\IntervencionUnidadAdquirida;
use App\Models\EnvioImportacionUnidad;
use App\Models\RevisionTecnicaUnidadAdquirida;
use App\Models\IncorporacionUnidadAdquirida;
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

public function test_reconstruye_trazabilidad_preinventario_desde_unidad_adquirida(): void
{
    $equipo = $this->crearEquipo();

    $usuario = User::create([
        'name' =>
            'Usuario trazabilidad preinventario',

        'email' =>
            'trazabilidad-' . Str::uuid() . '@test.com',

        'password' =>
            bcrypt('123456'),

        'activo' =>
            true,
    ]);

    $unidad = UnidadAdquirida::create([
        'producto_id' =>
            $equipo->producto_id,

        'almacen_actual_id' =>
            $equipo->almacen_actual_id,

        'estado' =>
            UnidadAdquirida::ESTADO_INCORPORADA,

        'codigo_trazabilidad' =>
            'OS-TRAZ-001',

        'fecha_llegada' =>
            now()->subDays(5),

        'fecha_lista_envio' =>
            now()->subDays(3),

        'registrado_por_id' =>
            $usuario->id,

        'revisado_por_id' =>
            $usuario->id,

        'equipo_id' =>
            $equipo->id,
    ]);

    IncorporacionUnidadAdquirida::create([
        'unidad_adquirida_id' =>
            $unidad->id,

        'equipo_id' =>
            $equipo->id,

        'usuario_id' =>
            $usuario->id,

        'condicion_fisica_id' =>
            null,

        'fecha_incorporacion' =>
            now()->subDay(),

        'observacion' =>
            'Incorporación para prueba de trazabilidad.',
    ]);

    $eventos =
        app(TrazabilidadEquipoService::class)
            ->obtener(
                $equipo->fresh()
            );

    $titulos =
        $eventos->pluck(
            'titulo'
        )->values();

    $this->assertTrue(
        $titulos->contains(
            'Unidad recibida en Cochabamba'
        )
    );

    $this->assertTrue(
        $titulos->contains(
            'Unidad lista para envío'
        )
    );

    $this->assertTrue(
        $titulos->contains(
            'Unidad incorporada al inventario'
        )
    );

    $this->assertFalse(
        $titulos->contains(
            'Equipo registrado'
        )
    );

    $this->assertSame(
        [
            'Unidad recibida en Cochabamba',
            'Unidad lista para envío',
            'Unidad incorporada al inventario',
        ],
        $titulos->all()
    );
}
public function test_reconstruye_ciclo_logistico_preinventario_en_orden_cronologico(): void
{
    $equipo =
        $this->crearEquipo();

    $usuario = User::create([
        'name' =>
            'Usuario ciclo trazabilidad',

        'email' =>
            'ciclo-' . Str::uuid() . '@test.com',

        'password' =>
            bcrypt('123456'),

        'activo' =>
            true,
    ]);

    $oruro =
        Almacen::findOrFail(
            $equipo->almacen_actual_id
        );

    $cochabamba =
        Almacen::create([
            'codigo' =>
                'CBBA-' . Str::uuid(),

            'nombre' =>
                'Almacén Cochabamba',

            'ciudad' =>
                'Cochabamba',

            'principal' =>
                false,

            'activo' =>
                true,
        ]);

    $unidad =
        UnidadAdquirida::create([
            'producto_id' =>
                $equipo->producto_id,

            'almacen_actual_id' =>
                $oruro->id,

            'estado' =>
                UnidadAdquirida::ESTADO_INCORPORADA,

            'codigo_trazabilidad' =>
                'OS-CICLO-001',

            'fecha_llegada' =>
                now()->subDays(8),

            'fecha_lista_envio' =>
                now()->subDays(6),

            'registrado_por_id' =>
                $usuario->id,

            'revisado_por_id' =>
                $usuario->id,

            'equipo_id' =>
                $equipo->id,
        ]);

    RevisionTecnicaUnidadAdquirida::create([
        'unidad_adquirida_id' =>
            $unidad->id,

        'usuario_id' =>
            $usuario->id,

        'fecha_revision' =>
            now()->subDays(7),

        'resultado' =>
            RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA,

        'requiere_servicio' =>
            false,

        'observacion' =>
            'Revisión aprobada para trazabilidad.',
    ]);

    $envio =
        EnvioImportacion::create([
            'codigo' =>
                'ENV-TRAZ-001',

            'almacen_origen_id' =>
                $cochabamba->id,

            'almacen_destino_id' =>
                $oruro->id,

            'estado' =>
                EnvioImportacion::ESTADO_RECIBIDO,

            'preparado_por_id' =>
                $usuario->id,

            'despachado_por_id' =>
                $usuario->id,

            'recibido_por_id' =>
                $usuario->id,

            'fecha_preparacion' =>
                now()->subDays(5),

            'fecha_despacho' =>
                now()->subDays(4),

            'fecha_recepcion' =>
                now()->subDays(3),

            'transportista' =>
                'Transporte prueba',

            'numero_guia' =>
                'GUIA-TRAZ-001',
        ]);

    EnvioImportacionUnidad::create([
        'envio_importacion_id' =>
            $envio->id,

        'unidad_adquirida_id' =>
            $unidad->id,

        'incluye_cargador' =>
            true,

        'cargador_recibido' =>
            true,

        'estado_recepcion' =>
            EnvioImportacionUnidad::ESTADO_RECIBIDA,

        'fecha_recepcion' =>
            now()->subDays(3),

        'recibido_por_id' =>
            $usuario->id,

        'observacion_recepcion' =>
            'Unidad recibida correctamente.',
    ]);

    IncorporacionUnidadAdquirida::create([
        'unidad_adquirida_id' =>
            $unidad->id,

        'equipo_id' =>
            $equipo->id,

        'usuario_id' =>
            $usuario->id,

        'condicion_fisica_id' =>
            null,

        'fecha_incorporacion' =>
            now()->subDays(2),

        'observacion' =>
            'Incorporación final.',
    ]);

    $eventos =
        app(TrazabilidadEquipoService::class)
            ->obtener(
                $equipo->fresh()
            );

    $titulos =
        $eventos
            ->pluck('titulo')
            ->values()
            ->all();

    $this->assertSame(
        [
            'Unidad recibida en Cochabamba',
            'Revisión técnica de la unidad',
            'Unidad lista para envío',
            'Envío preparado',
            'Unidad despachada',
            'Unidad recibida en Oruro',
            'Unidad incorporada al inventario',
        ],
        $titulos
    );

    $fechas =
        $eventos
            ->pluck('fecha')
            ->map(
                fn ($fecha) =>
                    $fecha->timestamp
            )
            ->all();

    $fechasOrdenadas =
        $fechas;

    sort(
        $fechasOrdenadas
    );

    $this->assertSame(
        $fechasOrdenadas,
        $fechas
    );

    $this->assertTrue(
        $eventos->last()['activo']
    );
}
public function test_muestra_intervenciones_de_preparacion_de_la_unidad(): void
{
    $equipo =
        $this->crearEquipo();

    $usuario = User::create([
        'name' =>
            'Tecnico preparacion',

        'email' =>
            'preparacion-' . Str::uuid() . '@test.com',

        'password' =>
            bcrypt('123456'),

        'activo' =>
            true,
    ]);

    $unidad =
        UnidadAdquirida::create([
            'producto_id' =>
                $equipo->producto_id,

            'almacen_actual_id' =>
                $equipo->almacen_actual_id,

            'estado' =>
                UnidadAdquirida::ESTADO_INCORPORADA,

            'codigo_trazabilidad' =>
                'OS-PREP-001',

            'fecha_llegada' =>
                now()->subDays(5),

            'fecha_lista_envio' =>
                now()->subDays(2),

            'registrado_por_id' =>
                $usuario->id,

            'revisado_por_id' =>
                $usuario->id,

            'equipo_id' =>
                $equipo->id,
        ]);

    IntervencionUnidadAdquirida::create([
        'unidad_adquirida_id' =>
            $unidad->id,

        'tipo' =>
            IntervencionUnidadAdquirida::TIPO_SERVICIO,

        'fecha_inicio' =>
            now()->subDays(4),

        'fecha_fin' =>
            now()->subDays(3),

        'descripcion' =>
            'Mantenimiento preventivo',

        'resultado' =>
            'Equipo preparado correctamente',

        'registrado_por_id' =>
            $usuario->id,
    ]);

    IncorporacionUnidadAdquirida::create([
        'unidad_adquirida_id' =>
            $unidad->id,

        'equipo_id' =>
            $equipo->id,

        'usuario_id' =>
            $usuario->id,

        'condicion_fisica_id' =>
            null,

        'fecha_incorporacion' =>
            now()->subDay(),
    ]);

    $eventos =
        app(TrazabilidadEquipoService::class)
            ->obtener(
                $equipo->fresh()
            );

    $intervencion =
        $eventos->firstWhere(
            'titulo',
            'Intervención de preparación'
        );

    $this->assertNotNull(
        $intervencion
    );

    $this->assertStringContainsString(
        'Mantenimiento preventivo',
        $intervencion['detalle']
    );

    $this->assertSame(
        'Tecnico preparacion',
        $intervencion['usuario']
    );

    $this->assertSame(
        'Equipo preparado correctamente',
        $intervencion['observacion']
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
