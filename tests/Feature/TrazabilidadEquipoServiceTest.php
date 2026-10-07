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
use App\Models\DetalleReserva;
use App\Models\ProrrogaReserva;
use App\Models\Reserva;
use App\Models\MovimientoAjusteGarantia;
use App\Models\CambioEquipo;
use App\Models\IntervencionUnidadAdquirida;
use App\Models\EnvioImportacionUnidad;
use App\Models\RevisionTecnicaUnidadAdquirida;
use App\Models\IncorporacionUnidadAdquirida;
use App\Models\PoliticaGarantia;
use App\Models\Pago;
use App\Models\PrecioEquipo;
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

    public function test_muestra_ciclo_de_reserva_prorroga_y_liberacion(): void
    {
        $equipo = $this->crearEquipo();

        $equipo->update([
            'fecha_registro' =>
                now()->subDays(10),
        ]);

        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente trazabilidad reserva',

            'telefono' =>
                '70009999',

            'activo' =>
                true,
        ]);

        $reserva = Reserva::create([
            'numero' =>
                'RES-TRAZ-001',

            'cliente_id' =>
                $cliente->id,

            'registrado_por_id' =>
                $usuario->id,

            'estado' =>
                'LIBERADA',

            'fecha_reserva' =>
                now()->subDays(5),

            'fecha_expiracion' =>
                now()->addDays(2),

            'fecha_cierre' =>
                now()->subDay(),

            'observacion' =>
                'Reserva de prueba para trazabilidad.',
        ]);

        DetalleReserva::create([
            'reserva_id' =>
                $reserva->id,

            'equipo_id' =>
                $equipo->id,

            'precio_acordado' =>
                3500,

            'descuento_acordado' =>
                100,

            'observacion' =>
                'Precio acordado con el cliente.',
        ]);

        $prorroga = ProrrogaReserva::create([
            'reserva_id' =>
                $reserva->id,

            'autorizado_por_id' =>
                $usuario->id,

            'fecha_expiracion_anterior' =>
                now()->subDays(2),

            'nueva_fecha_expiracion' =>
                now()->addDays(2),

            'motivo' =>
                'Cliente solicitó plazo adicional.',
        ]);

        $prorroga->forceFill([
            'created_at' =>
                now()->subDays(3),
        ])->saveQuietly();

        $eventos =
            app(TrazabilidadEquipoService::class)
                ->obtener(
                    $equipo->fresh()
                );

        $reservaCreada =
            $eventos->firstWhere(
                'titulo',
                'Equipo reservado'
            );

        $this->assertNotNull(
            $reservaCreada
        );

        $this->assertStringContainsString(
            'RES-TRAZ-001',
            $reservaCreada['detalle']
        );

        $this->assertStringContainsString(
            'Cliente trazabilidad reserva',
            $reservaCreada['detalle']
        );

        $this->assertStringContainsString(
            '3500.00',
            $reservaCreada['detalle']
        );

        $prorrogaEvento =
            $eventos->firstWhere(
                'titulo',
                'Reserva prorrogada'
            );

        $this->assertNotNull(
            $prorrogaEvento
        );

        $this->assertSame(
            'Cliente solicitó plazo adicional.',
            $prorrogaEvento['observacion']
        );

        $liberacion =
            $eventos->firstWhere(
                'titulo',
                'Reserva liberada'
            );

        $this->assertNotNull(
            $liberacion
        );

        $this->assertStringContainsString(
            'Estado final: Liberada',
            $liberacion['detalle']
        );

        $titulosReserva =
            $eventos
                ->filter(
                    fn ($evento) =>
                        $evento['tipo'] === 'reserva'
                )
                ->pluck('titulo')
                ->values()
                ->all();

        $this->assertSame(
            [
                'Equipo reservado',
                'Reserva prorrogada',
                'Reserva liberada',
            ],
            $titulosReserva
        );
    }

    public function test_muestra_conversion_de_reserva_venta_y_anulacion(): void
    {
        $equipo = $this->crearEquipo();

        $equipo->update([
            'fecha_registro' =>
                now()->subDays(10),
        ]);

        $vendedor = User::create([
            'name' =>
                'Vendedor trazabilidad',

            'email' =>
                'vendedor-traz-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,
        ]);

        $anulador = User::create([
            'name' =>
                'Administrador anulacion',

            'email' =>
                'anulador-traz-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,
        ]);

        $cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente conversion trazabilidad',

            'telefono' =>
                '70008888',

            'activo' =>
                true,
        ]);

        $fechaConversion =
            now()->subDays(3);

        $reserva = Reserva::create([
            'numero' =>
                'RES-TRAZ-CONV-001',

            'cliente_id' =>
                $cliente->id,

            'registrado_por_id' =>
                $vendedor->id,

            'estado' =>
                'CONVERTIDA',

            'fecha_reserva' =>
                now()->subDays(6),

            'fecha_expiracion' =>
                now()->addDay(),

            'fecha_cierre' =>
                $fechaConversion,

            'observacion' =>
                'Reserva convertida para prueba.',
        ]);

        DetalleReserva::create([
            'reserva_id' =>
                $reserva->id,

            'equipo_id' =>
                $equipo->id,

            'precio_acordado' =>
                3500,

            'descuento_acordado' =>
                0,

            'observacion' =>
                null,
        ]);

        $venta = Venta::create([
            'numero' =>
                'VEN-TRAZ-001',

            'cliente_id' =>
                $cliente->id,

            'cliente_nombre_snapshot' =>
                $cliente->nombre_completo,

            'cliente_telefono_snapshot' =>
                $cliente->telefono,

            'vendedor_id' =>
                $vendedor->id,

            'reserva_id' =>
                $reserva->id,

            'fecha_venta' =>
                $fechaConversion,

            'subtotal' =>
                3500,

            'descuento_total' =>
                0,

            'total' =>
                3500,

            'estado' =>
                'ANULADA',

            'anulado_por_id' =>
                $anulador->id,

            'fecha_anulacion' =>
                now()->subDay(),

            'motivo_anulacion' =>
                'Cliente desistió de la compra.',

            'observacion' =>
                'Venta anulada para trazabilidad.',
        ]);

        DetalleVenta::create([
            'venta_id' =>
                $venta->id,

            'producto_id' =>
                $equipo->producto_id,

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
                3000,

            'subtotal' =>
                3500,

            'observacion' =>
                null,
        ]);

        $eventos =
            app(TrazabilidadEquipoService::class)
                ->obtener(
                    $equipo->fresh()
                );

        $conversion =
            $eventos->firstWhere(
                'titulo',
                'Reserva convertida en venta'
            );

        $this->assertNotNull(
            $conversion
        );

        $this->assertStringContainsString(
            'Estado final: Convertida en venta',
            $conversion['detalle']
        );

        $ventaEvento =
            $eventos->firstWhere(
                'titulo',
                'Equipo vendido'
            );

        $this->assertNotNull(
            $ventaEvento
        );

        $this->assertStringContainsString(
            'VEN-TRAZ-001',
            $ventaEvento['detalle']
        );

        $this->assertSame(
            'Vendedor trazabilidad',
            $ventaEvento['usuario']
        );

        $anulacion =
            $eventos->firstWhere(
                'titulo',
                'Venta anulada'
            );

        $this->assertNotNull(
            $anulacion
        );

        $this->assertStringContainsString(
            'Cliente desistió de la compra.',
            $anulacion['detalle']
        );

        $this->assertSame(
            'Administrador anulacion',
            $anulacion['usuario']
        );

        $titulos =
            $eventos
                ->filter(
                    fn ($evento) =>
                        in_array(
                            $evento['tipo'],
                            [
                                'reserva',
                                'venta',
                            ],
                            true
                        )
                )
                ->pluck('titulo')
                ->values()
                ->all();

        $this->assertSame(
            [
                'Equipo reservado',
                'Reserva convertida en venta',
                'Equipo vendido',
                'Venta anulada',
            ],
            $titulos
        );
    }

    public function test_muestra_cambio_garantia_en_equipo_saliente_y_entrante(): void
    {
        $equipoSaliente =
            $this->crearEquipo();

        $equipoSaliente->update([
            'fecha_registro' =>
                now()->subDays(10),
        ]);

        $producto =
            $equipoSaliente->producto;

        $categoria =
            $producto->categoria;

        $usuario = User::create([
            'name' =>
                'Administrador cambio garantia',

            'email' =>
                'cambio-garantia-' . Str::uuid() . '@test.com',

            'password' =>
                bcrypt('123456'),

            'activo' =>
                true,
        ]);

        $cliente = Cliente::create([
            'nombre_completo' =>
                'Cliente cambio garantia',

            'telefono' =>
                '70009999',

            'activo' =>
                true,
        ]);

        $equipoEntrante = Equipo::create([
            'producto_id' =>
                $producto->id,

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $equipoSaliente->almacen_actual_id,

            'estado_actual_id' =>
                $equipoSaliente->estado_actual_id,

            'condicion_fisica_id' =>
                null,

            'codigo_interno' =>
                'EQ-REEMPLAZO-' . Str::uuid(),

            'serial_fabricante' =>
                'SER-REEMP-' . Str::uuid(),

            'fecha_registro' =>
                now()->subDays(8),

            'fecha_disponible' =>
                now()->subDays(8),

            'observacion' =>
                'Equipo destinado a reemplazo.',

            'activo' =>
                true,
        ]);

        $venta = Venta::create([
            'numero' =>
                'VEN-CAMBIO-' . Str::uuid(),

            'cliente_id' =>
                $cliente->id,

            'cliente_nombre_snapshot' =>
                $cliente->nombre_completo,

            'cliente_telefono_snapshot' =>
                $cliente->telefono,

            'vendedor_id' =>
                $usuario->id,

            'fecha_venta' =>
                now()->subDays(7),

            'subtotal' =>
                3500,

            'descuento_total' =>
                0,

            'total' =>
                3500,

            'estado' =>
                'REGISTRADA',
        ]);

        $detalleVenta = DetalleVenta::create([
            'venta_id' =>
                $venta->id,

            'producto_id' =>
                $producto->id,

            'equipo_id' =>
                $equipoSaliente->id,

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
                'GAR-CAMBIO-' . Str::uuid(),

            'nombre' =>
                'Garantia cambio trazabilidad',

            'categoria_producto_id' =>
                $categoria->id,

            'producto_id' =>
                $producto->id,

            'duracion_meses' =>
                6,

            'condiciones' =>
                'Garantia de prueba.',

            'exclusiones' =>
                'Danios fisicos.',

            'vigente_desde' =>
                now()->subDays(20),

            'vigente_hasta' =>
                null,

            'activo' =>
                true,
        ]);

        $garantia = Garantia::create([
            'numero' =>
                'GRT-CAMBIO-' . Str::uuid(),

            'detalle_venta_id' =>
                $detalleVenta->id,

            'politica_garantia_id' =>
                $politicaGarantia->id,

            'fecha_inicio' =>
                now()->subDays(7),

            'fecha_fin' =>
                now()->addMonths(6),

            'duracion_meses_snapshot' =>
                6,

            'condiciones_snapshot' =>
                'Garantia de prueba.',

            'estado' =>
                'VIGENTE',
        ]);

        $caso = CasoGarantia::create([
            'numero' =>
                'CAS-GAR-TRAZ-001',

            'garantia_id' =>
                $garantia->id,

            'equipo_afectado_id' =>
                $equipoSaliente->id,

            'recibido_por_id' =>
                $usuario->id,

            'tipo_caso' =>
                'GARANTIA',

            'estado' =>
                'EN_PROCESO',

            'fecha_apertura' =>
                now()->subDays(3),

            'motivo_cliente' =>
                'Equipo presenta falla.',

            'diagnostico_final' =>
                'Requiere sustitucion.',
        ]);

        $cambio = CambioEquipo::create([
            'caso_garantia_id' =>
                $caso->id,

            'equipo_saliente_id' =>
                $equipoSaliente->id,

            'equipo_entrante_id' =>
                $equipoEntrante->id,

            'autorizado_por_id' =>
                $usuario->id,

            'fecha_cambio' =>
                now()->subDay(),

            'motivo' =>
                'Cambio autorizado por garantia.',

            'observacion' =>
                'Reemplazo entregado al cliente.',
        ]);
        $metodoPagoId =
        \Illuminate\Support\Facades\DB::table(
            'metodos_pago'
        )->insertGetId([
            'codigo' =>
                'QR-TRAZ-' . Str::uuid(),

            'nombre' =>
                'QR trazabilidad',

            'requiere_verificacion' =>
                true,

            'activo' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

    MovimientoAjusteGarantia::create([
        'cambio_equipo_id' =>
            $cambio->id,

        'tipo_movimiento' =>
            'COBRO',

        'metodo_pago_id' =>
            $metodoPagoId,

        'monto' =>
            500,

        'fecha_movimiento' =>
            now()->subHours(6),

        'referencia' =>
            'QR-GAR-TRAZ-001',

        'comprobante' =>
            null,

        'estado' =>
            'VERIFICADO',

        'registrado_por_id' =>
            $usuario->id,

        'verificado_por_id' =>
            $usuario->id,

        'fecha_verificacion' =>
            now()->subHours(5),

        'motivo_rechazo' =>
            null,

        'observacion' =>
            'Cobro QR de prueba para trazabilidad.',
    ]);
    MovimientoAjusteGarantia::create([
        'cambio_equipo_id' =>
            $cambio->id,
        'tipo_movimiento' =>
            'COBRO',
        'metodo_pago_id' =>
            $metodoPagoId,
        'monto' =>
            100,
        'fecha_movimiento' =>
            now()->subHours(4),
        'referencia' =>
            'QR-GAR-TRAZ-RECHAZADO',
        'comprobante' =>
            null,
        'estado' =>
            'RECHAZADO',
        'registrado_por_id' =>
            $usuario->id,
        'verificado_por_id' =>
            $usuario->id,
        'fecha_verificacion' =>
            now()->subHours(3),
        'motivo_rechazo' =>
            'Comprobante inválido para prueba.',
        'observacion' =>
            'Cobro rechazado para trazabilidad.',
    ]);

        $eventosSaliente =
            app(TrazabilidadEquipoService::class)
                ->obtener(
                    $equipoSaliente->fresh()
                );

        $eventoSaliente =
            $eventosSaliente->firstWhere(
                'titulo',
                'Equipo sustituido por garantía'
            );

        $this->assertNotNull(
            $eventoSaliente
        );

        $this->assertStringContainsString(
            'CAS-GAR-TRAZ-001',
            $eventoSaliente['detalle']
        );

        $this->assertStringContainsString(
            $equipoEntrante->codigo_interno,
            $eventoSaliente['detalle']
        );

        $this->assertSame(
            'Administrador cambio garantia',
            $eventoSaliente['usuario']
        );

        $cobroRegistradoSaliente =
            $eventosSaliente->firstWhere(
                'titulo',
                'Cobro de ajuste de garantía registrado'
            );

        $this->assertNotNull(
            $cobroRegistradoSaliente
        );

        $this->assertStringContainsString(
            'BOB 500,00',
            $cobroRegistradoSaliente['detalle']
        );

        $this->assertStringContainsString(
            'Estado inicial: Pendiente de verificación',
            $cobroRegistradoSaliente['detalle']
        );

        $this->assertStringContainsString(
            'QR-GAR-TRAZ-001',
            $cobroRegistradoSaliente['detalle']
        );

        $cobroVerificadoSaliente =
            $eventosSaliente->firstWhere(
                'titulo',
                'Cobro de ajuste verificado'
            );

        $this->assertNotNull(
            $cobroVerificadoSaliente
        );

        $this->assertSame(
            'Administrador cambio garantia',
            $cobroVerificadoSaliente['usuario']
        );

        $cobroRechazadoSaliente =
            $eventosSaliente->firstWhere(
                'titulo',
                'Cobro de ajuste rechazado'
            );

        $this->assertNotNull(
            $cobroRechazadoSaliente
        );

        $this->assertStringContainsString(
            'Comprobante inválido para prueba.',
            $cobroRechazadoSaliente['detalle']
        );

        $this->assertSame(
            'Administrador cambio garantia',
            $cobroRechazadoSaliente['usuario']
        );

        $eventosEntrante =
            app(TrazabilidadEquipoService::class)
                ->obtener(
                    $equipoEntrante->fresh()
                );

        $eventoEntrante =
            $eventosEntrante->firstWhere(
                'titulo',
                'Equipo entregado como reemplazo'
            );

        $this->assertNotNull(
            $eventoEntrante
        );

        $this->assertStringContainsString(
            'CAS-GAR-TRAZ-001',
            $eventoEntrante['detalle']
        );

        $this->assertStringContainsString(
            $equipoSaliente->codigo_interno,
            $eventoEntrante['detalle']
        );

        $this->assertSame(
            'Administrador cambio garantia',
            $eventoEntrante['usuario']
        );

        $this->assertNotNull(
            $eventosEntrante->firstWhere(
                'titulo',
                'Cobro de ajuste de garantía registrado'
            )
        );

        $this->assertNotNull(
            $eventosEntrante->firstWhere(
                'titulo',
                'Cobro de ajuste verificado'
            )
        );

        $this->assertNotNull(
            $eventosEntrante->firstWhere(
                'titulo',
                'Cobro de ajuste rechazado'
            )
        );
    }

    public function test_muestra_historial_comercial_y_pagos_de_venta(): void
    {
        $equipo = $this->crearEquipo();

        $usuario = User::create([
            'name' => 'Usuario trazabilidad comercial',
            'email' => 'trazabilidad-comercial-' . Str::uuid() . '@test.com',
            'password' => bcrypt('123456'),
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre_completo' => 'Cliente trazabilidad comercial',
            'telefono' => '70001234',
            'activo' => true,
        ]);

        PrecioEquipo::create([
            'equipo_id' => $equipo->id,
            'costo_total_snapshot' => 3500,
            'precio_sugerido' => 4300,
            'precio_publico' => 4200,
            'precio_minimo_autorizado' => 4000,
            'vigente_desde' => now()->subDays(6),
            'vigente_hasta' => now()->subDays(5),
            'vigente' => false,
            'aprobado_por_id' => $usuario->id,
            'observacion' => 'Precio inicial de prueba.',
        ]);

        PrecioEquipo::create([
            'equipo_id' => $equipo->id,
            'costo_total_snapshot' => 3600,
            'precio_sugerido' => 4500,
            'precio_publico' => 4400,
            'precio_minimo_autorizado' => 4100,
            'vigente_desde' => now()->subDays(5),
            'vigente_hasta' => null,
            'vigente' => true,
            'aprobado_por_id' => $usuario->id,
            'observacion' => 'Precio comercial actualizado.',
        ]);

        $venta = Venta::create([
            'numero' => 'VEN-TP-' . Str::uuid(),
            'cliente_id' => $cliente->id,
            'vendedor_id' => $usuario->id,
            'fecha_venta' => now()->subDays(4),
            'subtotal' => 4400,
            'descuento_total' => 0,
            'total' => 4400,
            'estado' => 'REGISTRADA',
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $equipo->producto_id,
            'equipo_id' => $equipo->id,
            'cantidad' => 1,
            'precio_lista_snapshot' => 4400,
            'descuento_unitario' => 0,
            'precio_unitario' => 4400,
            'costo_unitario_snapshot' => 3600,
            'subtotal' => 4400,
        ]);

        $efectivoId =
            \Illuminate\Support\Facades\DB::table('metodos_pago')
                ->insertGetId([
                    'codigo' => 'EFECTIVO-TRAZ-' . Str::uuid(),
                    'nombre' => 'Efectivo',
                    'requiere_verificacion' => false,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        $qrId =
            \Illuminate\Support\Facades\DB::table('metodos_pago')
                ->insertGetId([
                    'codigo' => 'QR-TRAZ-' . Str::uuid(),
                    'nombre' => 'QR / transferencia',
                    'requiere_verificacion' => true,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        Pago::create([
            'venta_id' => $venta->id,
            'metodo_pago_id' => $efectivoId,
            'monto' => 1000,
            'fecha_pago' => now()->subDays(3),
            'estado' => 'VERIFICADO',
            'registrado_por_id' => $usuario->id,
            'verificado_por_id' => $usuario->id,
            'fecha_verificacion' => now()->subDays(3),
            'observacion' => 'Pago parcial en efectivo.',
        ]);

        Pago::create([
            'venta_id' => $venta->id,
            'metodo_pago_id' => $qrId,
            'monto' => 500,
            'fecha_pago' => now()->subDays(2),
            'referencia' => 'QR-TRAZ-PAGO-001',
            'estado' => 'RECHAZADO',
            'registrado_por_id' => $usuario->id,
            'verificado_por_id' => $usuario->id,
            'fecha_verificacion' => now()->subDay(),
            'motivo_rechazo' => 'Pago no localizado.',
            'observacion' => 'Pago QR de prueba.',
        ]);

        $eventos = app(TrazabilidadEquipoService::class)
            ->obtener($equipo->fresh());

        $this->assertNotNull(
            $eventos->firstWhere(
                'titulo',
                'Precio comercial definido'
            )
        );

        $precioActualizado = $eventos->firstWhere(
            'titulo',
            'Precio comercial actualizado'
        );

        $this->assertNotNull($precioActualizado);
        $this->assertStringContainsString(
            'Bs 4.400,00',
            $precioActualizado['detalle']
        );

        $pagosRegistrados = $eventos->where(
            'titulo',
            'Pago de venta registrado'
        );

        $this->assertCount(2, $pagosRegistrados);

        $pagoQr = $pagosRegistrados->first(
            fn ($evento) =>
                str_contains(
                    $evento['detalle'],
                    'QR-TRAZ-PAGO-001'
                )
        );

        $this->assertNotNull($pagoQr);
        $this->assertStringContainsString(
            'Pendiente de verificación',
            $pagoQr['detalle']
        );

        $pagoRechazado = $eventos->firstWhere(
            'titulo',
            'Pago de venta rechazado'
        );

        $this->assertNotNull($pagoRechazado);
        $this->assertStringContainsString(
            'Estado: Rechazado',
            $pagoRechazado['detalle']
        );
        $this->assertStringNotContainsString(
            'RECHAZADO',
            $pagoRechazado['detalle']
        );
    }

    public function test_muestra_diagnostico_y_cierre_de_garantia_con_textos_humanos(): void
    {
        $equipo = $this->crearEquipo();
        $producto = $equipo->producto;
        $categoria = $producto->categoria;

        $usuario = User::create([
            'name' => 'Usuario cierre garantia trazabilidad',
            'email' => 'cierre-garantia-' . Str::uuid() . '@test.com',
            'password' => bcrypt('123456'),
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre_completo' => 'Cliente cierre garantia',
            'telefono' => '70004321',
            'activo' => true,
        ]);

        $venta = Venta::create([
            'numero' => 'VEN-CG-' . Str::uuid(),
            'cliente_id' => $cliente->id,
            'vendedor_id' => $usuario->id,
            'fecha_venta' => now()->subDays(8),
            'subtotal' => 3500,
            'descuento_total' => 0,
            'total' => 3500,
            'estado' => 'REGISTRADA',
        ]);

        $detalleVenta = DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'equipo_id' => $equipo->id,
            'cantidad' => 1,
            'precio_lista_snapshot' => 3500,
            'descuento_unitario' => 0,
            'precio_unitario' => 3500,
            'costo_unitario_snapshot' => 2900,
            'subtotal' => 3500,
        ]);

        $politica = PoliticaGarantia::create([
            'codigo' => 'GAR-CIERRE-' . Str::uuid(),
            'nombre' => 'Garantía cierre trazabilidad',
            'categoria_producto_id' => $categoria->id,
            'producto_id' => $producto->id,
            'duracion_meses' => 6,
            'condiciones' => 'Condiciones de prueba.',
            'exclusiones' => 'Daño físico.',
            'vigente_desde' => now()->subDays(20),
            'vigente_hasta' => null,
            'activo' => true,
        ]);

        $garantia = Garantia::create([
            'numero' => 'GRT-CIERRE-' . Str::uuid(),
            'detalle_venta_id' => $detalleVenta->id,
            'politica_garantia_id' => $politica->id,
            'fecha_inicio' => now()->subDays(8),
            'fecha_fin' => now()->addMonths(6),
            'duracion_meses_snapshot' => 6,
            'condiciones_snapshot' => 'Condiciones de prueba.',
            'estado' => 'VIGENTE',
        ]);

        CasoGarantia::create([
            'numero' => 'CAS-GC-' . Str::uuid(),
            'garantia_id' => $garantia->id,
            'equipo_afectado_id' => $equipo->id,
            'recibido_por_id' => $usuario->id,
            'tipo_caso' => 'GARANTIA',
            'estado' => 'CERRADO',
            'fecha_apertura' => now()->subDays(3),
            'motivo_cliente' => 'Falla intermitente.',
            'diagnostico_final' => 'Falla confirmada en alimentación.',
            'resolucion' => 'CAMBIO_EQUIPO',
            'fecha_cierre' => now()->subDay(),
            'cerrado_por_id' => $usuario->id,
            'observacion' => 'Caso resuelto satisfactoriamente.',
        ]);

        $eventos = app(TrazabilidadEquipoService::class)
            ->obtener($equipo->fresh());

        $apertura = $eventos->firstWhere(
            'titulo',
            'Caso de garantía abierto'
        );

        $this->assertNotNull($apertura);
        $this->assertStringContainsString(
            'Estado: Cerrado',
            $apertura['detalle']
        );
        $this->assertStringNotContainsString(
            'CERRADO',
            $apertura['detalle']
        );

        $diagnostico = $eventos->firstWhere(
            'titulo',
            'Diagnóstico de garantía registrado'
        );

        $this->assertNotNull($diagnostico);
        $this->assertSame(
            'Falla confirmada en alimentación.',
            $diagnostico['detalle']
        );

        $cierre = $eventos->firstWhere(
            'titulo',
            'Caso de garantía cerrado'
        );

        $this->assertNotNull($cierre);
        $this->assertStringContainsString(
            'Resolución: Cambio equipo',
            $cierre['detalle']
        );
        $this->assertSame(
            'Usuario cierre garantia trazabilidad',
            $cierre['usuario']
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
