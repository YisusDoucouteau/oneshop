<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\DetalleLote;
use App\Models\EspecificacionEsperadaDetalleLote;
use App\Models\Lote;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TipoCambio;
use App\Models\Rol;
use App\Models\UnidadAdquirida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportacionWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_administrador_operativo_recibe_codigo_sugerido_para_nuevo_lote(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $respuesta = $this
            ->actingAs($usuario)
            ->get(route('importaciones.create'));

        $respuesta
            ->assertOk()
            ->assertSee('Registrar lote de importación')
            ->assertSee('Generado automáticamente')
            ->assertSee('name="fecha_compra"', false)
            ->assertSee('IMP-'.now()->format('Y').'-001');
    }

    public function test_vendedor_no_puede_crear_lotes(): void
    {
        $vendedor = $this->usuarioConRol('VENDEDOR');

        $this
            ->actingAs($vendedor)
            ->get(route('importaciones.create'))
            ->assertForbidden();
    }

    public function test_formulario_del_lote_expone_datos_de_compra_y_caracteristicas_esperadas(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $lote = Lote::query()->create([
            'codigo' => 'IMP-WEB-001',
            'estado' => 'ABIERTO',
        ]);

        $this
            ->actingAs($usuario)
            ->get(route('importaciones.show', $lote))
            ->assertOk()
            ->assertSee('Costo unitario')
            ->assertSee('Características y accesorios esperados')
            ->assertSee('name="moneda_id"', false)
            ->assertSee('name="tipo_cambio_aplicado"', false)
            ->assertSee('name="especificacion_esperada[procesador]"', false)
            ->assertSee('id="agregarComponenteEsperado"', false)
            ->assertSee('Se mantienen separados')
            ->assertDontSee('Distribuir costos');
    }

    public function test_nuevo_lote_conserva_fecha_real_de_compra(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');

        $this
            ->actingAs($usuario)
            ->post(route('importaciones.store'), [
                'codigo' => 'IMP-FECHA-001',
                'fecha_compra' => '2026-09-10',
                'origen' => 'Estados Unidos',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lotes', [
            'codigo' => 'IMP-FECHA-001',
            'fecha_compra' => '2026-09-10',
        ]);
    }

    public function test_recepcion_hereda_compra_y_permite_corregir_lo_observado_fisicamente(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $lote = Lote::query()->create([
            'codigo' => 'IMP-RECEPCION-001',
            'fecha_compra' => '2026-09-05',
            'referencia_compra' => 'SUBASTA-7788',
            'estado' => 'ABIERTO',
        ]);
        $producto = $this->crearProducto('RECEPCION-001');
        $usd = Moneda::query()->where('codigo', 'USD')->firstOrFail();
        $bob = Moneda::query()->where('codigo', 'BOB')->firstOrFail();

        $tipoCambioCompra = TipoCambio::query()->create([
            'moneda_origen_id' => $usd->id,
            'moneda_destino_id' => $bob->id,
            'valor' => 10.500000,
            'fecha_vigencia' => '2026-09-05 12:00:00',
            'fuente' => 'COMPRA_REAL',
            'registrado_por_id' => $usuario->id,
        ]);

        $detalle = DetalleLote::query()->create([
            'lote_id' => $lote->id,
            'producto_id' => $producto->id,
            'moneda_id' => $usd->id,
            'tipo_cambio_compra_id' => $tipoCambioCompra->id,
            'cantidad_esperada' => 1,
            'cantidad_recibida' => 0,
            'costo_unitario_origen' => 300,
            'costo_unitario_bob' => 3150,
        ]);

        EspecificacionEsperadaDetalleLote::query()->create([
            'detalle_lote_id' => $detalle->id,
            'procesador' => 'Intel Core i5',
            'ram_gb' => 16,
            'almacenamiento_gb' => 512,
        ]);

        $cantidadTiposCambioAntes = TipoCambio::query()->count();

        $this
            ->actingAs($usuario)
            ->post(route('importaciones.unidades.store', $lote), [
                'detalle_lote_id' => $detalle->id,
                'cantidad' => 1,
                'ram_gb' => 8,
                'grado_recibido' => 'B',
                'tiene_cargador' => 0,
                'servicio_requerido' => 'Instalar RAM faltante',

                // Aunque un cliente manipule el formulario, la recepción no puede
                // redefinir la compra ya registrada en la línea del lote.
                'precio_compra' => 9999,
                'moneda_id' => $bob->id,
                'tipo_cambio_compra' => 1,
                'fecha_compra' => '2026-09-18',
            ])
            ->assertRedirect(route('importaciones.show', $lote));

        $this->assertDatabaseHas('unidades_adquiridas', [
            'detalle_lote_id' => $detalle->id,
            'precio_compra' => 300,
            'moneda_id' => $usd->id,
            'tipo_cambio_compra_id' => $tipoCambioCompra->id,
            'precio_compra_bob' => 3150,
            'fecha_compra' => '2026-09-05',
            'referencia_compra' => 'SUBASTA-7788',
            'procesador' => 'Intel Core i5',
            'ram_gb' => 8,
            'grado_recibido' => 'B',
            'almacenamiento_gb' => 512,
            'tiene_cargador' => 0,
            'requiere_servicio' => 1,
            'servicio_requerido' => 'Instalar RAM faltante',
        ]);

        $this->assertSame(
            $cantidadTiposCambioAntes,
            TipoCambio::query()->count(),
            'La recepción no debe registrar un nuevo tipo de cambio.'
        );

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'estado' => 'RECIBIDO',
        ]);

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $detalle->id,
            'cantidad_recibida' => 0,
        ]);
    }

    public function test_anular_la_unica_recepcion_reabre_el_lote_sin_tocar_cantidad_recibida(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        [$lote, $unidad] = $this->crearUnidadParaEdicion(
            $usuario,
            UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
            'ANULAR-001'
        );

        $lote->update(['estado' => 'RECIBIDO']);

        $this
            ->actingAs($usuario)
            ->from(route('importaciones.show', $lote))
            ->patch(route('importaciones.unidades.anular', $unidad), [
                'motivo_anulacion' => 'Registro físico duplicado',
            ])
            ->assertRedirect(route('importaciones.show', $lote));

        $this->assertDatabaseHas('unidades_adquiridas', [
            'id' => $unidad->id,
            'estado' => UnidadAdquirida::ESTADO_ANULADA,
        ]);

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'estado' => 'ABIERTO',
        ]);

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $unidad->detalle_lote_id,
            'cantidad_recibida' => 0,
        ]);
    }

    public function test_recepcion_parcial_del_lote_se_calcula_con_unidades_fisicas_activas(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $lote = Lote::query()->create([
            'codigo' => 'IMP-PARCIAL-001',
            'estado' => 'ABIERTO',
        ]);
        $detalle = DetalleLote::query()->create([
            'lote_id' => $lote->id,
            'producto_id' => $this->crearProducto('PARCIAL-001')->id,
            'cantidad_esperada' => 2,
            'cantidad_recibida' => 0,
        ]);

        $this
            ->actingAs($usuario)
            ->post(route('importaciones.unidades.store', $lote), [
                'detalle_lote_id' => $detalle->id,
                'cantidad' => 1,
                'procesador' => 'Intel Core i5',
                'grado_recibido' => 'B',
                'tiene_cargador' => 1,
            ])
            ->assertRedirect(route('importaciones.show', $lote));

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'estado' => 'RECEPCION_PARCIAL',
        ]);

        $this->assertDatabaseHas('detalles_lotes', [
            'id' => $detalle->id,
            'cantidad_recibida' => 0,
        ]);

        $this
            ->actingAs($usuario)
            ->get(route('importaciones.index'))
            ->assertOk()
            ->assertSeeText('1 / 2');
    }

    public function test_recepcion_exige_procesador_si_compra_no_tiene_especificacion_esperada(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $lote = Lote::query()->create([
            'codigo' => 'IMP-RECEPCION-SIN-CPU',
            'estado' => 'ABIERTO',
        ]);
        $detalle = DetalleLote::query()->create([
            'lote_id' => $lote->id,
            'producto_id' => $this->crearProducto('RECEPCION-SIN-CPU')->id,
            'cantidad_esperada' => 1,
            'cantidad_recibida' => 0,
        ]);

        $this
            ->actingAs($usuario)
            ->from(route('importaciones.show', $lote))
            ->post(route('importaciones.unidades.store', $lote), [
                'detalle_lote_id' => $detalle->id,
                'cantidad' => 1,
                'grado_recibido' => 'A',
                'tiene_cargador' => 1,
            ])
            ->assertRedirect(route('importaciones.show', $lote))
            ->assertSessionHasErrors('procesador');

        $this->assertDatabaseCount('unidades_adquiridas', 0);
    }

    public function test_pantalla_recepcion_muestra_compra_como_solo_lectura(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $lote = Lote::query()->create([
            'codigo' => 'IMP-LECTURA-001',
            'fecha_compra' => '2026-09-01',
            'estado' => 'ABIERTO',
        ]);
        $bob = Moneda::query()->where('codigo', 'BOB')->firstOrFail();
        DetalleLote::query()->create([
            'lote_id' => $lote->id,
            'producto_id' => $this->crearProducto('LECTURA-001')->id,
            'moneda_id' => $bob->id,
            'cantidad_esperada' => 1,
            'cantidad_recibida' => 0,
            'costo_unitario_origen' => 2500,
            'costo_unitario_bob' => 2500,
        ]);

        $this
            ->actingAs($usuario)
            ->get(route('importaciones.show', $lote))
            ->assertOk()
            ->assertSee('Origen de compra')
            ->assertSee('En recepción no se modifica el precio, la moneda ni el tipo de cambio.')
            ->assertDontSee('name="precio_compra"', false)
            ->assertDontSee('name="tipo_cambio_compra"', false);
    }

    public function test_recepcion_muestra_regla_de_negocio_en_formulario_en_lugar_de_error_500(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $lote = Lote::query()->create([
            'codigo' => 'IMP-CERRADO-001',
            'estado' => 'CERRADO',
        ]);
        $detalle = DetalleLote::query()->create([
            'lote_id' => $lote->id,
            'producto_id' => $this->crearProducto('CERRADO-001')->id,
            'cantidad_esperada' => 1,
            'cantidad_recibida' => 0,
        ]);

        $this
            ->actingAs($usuario)
            ->from(route('importaciones.show', $lote))
            ->post(route('importaciones.unidades.store', $lote), [
                'detalle_lote_id' => $detalle->id,
                'cantidad' => 1,
                'procesador' => 'Intel Core i5',
                'grado_recibido' => 'A',
                'tiene_cargador' => 1,
            ])
            ->assertRedirect(route('importaciones.show', $lote))
            ->assertSessionHasErrors('recepcion');

        $this->assertDatabaseCount('unidades_adquiridas', 0);
    }

    public function test_edicion_de_recepcion_actualiza_datos_fisicos_y_servicio_requerido(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        [$lote, $unidad] = $this->crearUnidadParaEdicion(
            $usuario,
            UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
            'EDITAR-001'
        );

        $this
            ->actingAs($usuario)
            ->patch(route('importaciones.unidades.actualizar', $unidad), [
                'procesador' => 'Intel Core i5-10310U',
                'generacion_procesador' => '10th',
                'ram_gb' => 0,
                'almacenamiento_gb' => 0,
                'tipo_almacenamiento' => null,
                'grado_recibido' => 'C',
                'tiene_cargador' => 0,
                'servicio_requerido' => 'Instalar RAM, SSD y cargador',
                'observacion_revision' => 'Llegó sin RAM ni almacenamiento.',
            ])
            ->assertRedirect(route('importaciones.show', $lote));

        $this->assertDatabaseHas('unidades_adquiridas', [
            'id' => $unidad->id,
            'ram_gb' => 0,
            'grado_recibido' => 'C',
            'almacenamiento_gb' => 0,
            'tiene_cargador' => 0,
            'requiere_servicio' => 1,
            'servicio_requerido' => 'Instalar RAM, SSD y cargador',
            'observacion_revision' => 'Llegó sin RAM ni almacenamiento.',
        ]);
    }

    public function test_recepcion_no_puede_editarse_ni_anularse_despues_de_cerrar_la_etapa(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        [$lote, $unidad] = $this->crearUnidadParaEdicion(
            $usuario,
            UnidadAdquirida::ESTADO_LISTA_ENVIO,
            'CERRADA-001'
        );

        $this
            ->actingAs($usuario)
            ->get(route('importaciones.unidades.editar', $unidad))
            ->assertRedirect(route('importaciones.show', $lote))
            ->assertSessionHasErrors('edicion');

        $this
            ->actingAs($usuario)
            ->from(route('importaciones.show', $lote))
            ->patch(route('importaciones.unidades.actualizar', $unidad), [
                'procesador' => 'Procesador manipulado',
                'tiene_cargador' => 0,
            ])
            ->assertRedirect(route('importaciones.show', $lote))
            ->assertSessionHasErrors('edicion');

        $this
            ->actingAs($usuario)
            ->from(route('importaciones.show', $lote))
            ->patch(route('importaciones.unidades.anular', $unidad), [
                'motivo_anulacion' => 'Intento fuera de etapa',
            ])
            ->assertRedirect(route('importaciones.show', $lote))
            ->assertSessionHasErrors('unidad');

        $unidad->refresh();

        $this->assertSame(UnidadAdquirida::ESTADO_LISTA_ENVIO, $unidad->estado);
        $this->assertSame('Intel Core i5', $unidad->procesador);
        $this->assertNull($unidad->motivo_anulacion);
    }

    public function test_recepcion_rechaza_detalle_que_pertenece_a_otro_lote(): void
    {
        $usuario = $this->usuarioConRol('ADMIN_OPERATIVO');
        $loteSolicitado = Lote::query()->create(['codigo' => 'IMP-A', 'estado' => 'ABIERTO']);
        $otroLote = Lote::query()->create(['codigo' => 'IMP-B', 'estado' => 'ABIERTO']);
        $detalleAjeno = DetalleLote::query()->create([
            'lote_id' => $otroLote->id,
            'producto_id' => $this->crearProducto('RECEPCION-002')->id,
            'cantidad_esperada' => 1,
            'cantidad_recibida' => 0,
        ]);

        $this
            ->actingAs($usuario)
            ->post(route('importaciones.unidades.store', $loteSolicitado), [
                'detalle_lote_id' => $detalleAjeno->id,
                'cantidad' => 1,
                'grado_recibido' => 'A',
                'tiene_cargador' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('unidades_adquiridas', 0);
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

    private function crearUnidadParaEdicion(
        User $usuario,
        string $estado,
        string $codigo
    ): array {
        $lote = Lote::query()->create([
            'codigo' => 'IMP-'.$codigo,
            'estado' => 'ABIERTO',
        ]);
        $producto = $this->crearProducto($codigo);
        $detalle = DetalleLote::query()->create([
            'lote_id' => $lote->id,
            'producto_id' => $producto->id,
            'cantidad_esperada' => 1,
            'cantidad_recibida' => 0,
        ]);
        $cochabamba = Almacen::query()
            ->where('codigo', 'COCHABAMBA')
            ->firstOrFail();

        $unidad = UnidadAdquirida::query()->create([
            'detalle_lote_id' => $detalle->id,
            'producto_id' => $producto->id,
            'almacen_actual_id' => $cochabamba->id,
            'estado' => $estado,
            'codigo_trazabilidad' => 'OS-TEST-'.$codigo,
            'procesador' => 'Intel Core i5',
            'grado_recibido' => 'B',
            'tiene_cargador' => true,
            'registrado_por_id' => $usuario->id,
        ]);

        return [$lote, $unidad];
    }

    private function crearProducto(string $codigo): Producto
    {
        $categoria = CategoriaProducto::query()->where('codigo', 'LAPTOP')->firstOrFail();
        $marca = Marca::query()->create([
            'nombre' => 'Marca '.$codigo,
            'activo' => true,
        ]);

        return Producto::query()->create([
            'categoria_producto_id' => $categoria->id,
            'marca_id' => $marca->id,
            'codigo' => $codigo,
            'nombre' => 'Laptop de prueba',
            'modelo' => $codigo,
            'es_serializado' => true,
            'activo' => true,
        ]);
    }

}
