<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\PoliticaGarantia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VentaService
{
    public function __construct(
        private readonly EstadoEquipoService $estadoEquipoService,
        private readonly GarantiaService $garantiaService,
        private readonly MovimientoInventarioService $movimientoInventarioService,
        private readonly RentabilidadRebajaService $rentabilidadRebajaService
    ) {
    }

    public function registrarVentaDirecta(
        int $vendedorId,
        array $equiposIds,
        ?int $clienteId = null,
        ?string $observacion = null,
        array $preciosAcordados = []
    ): Venta {
        return DB::transaction(function () use (
            $vendedorId,
            $equiposIds,
            $clienteId,
            $observacion,
            $preciosAcordados
        ) {
            $vendedor =
                $this->obtenerVendedorActivo(
                    $vendedorId
                );

            $cliente =
                $this->obtenerCliente(
                    $clienteId
                );

            $equiposIds =
                $this->normalizarEquiposIds(
                    $equiposIds
                );

            $equipos =
                Equipo::query()
                    ->whereIn(
                        'id',
                        $equiposIds
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

            if (
                $equipos->count()
                !==
                count($equiposIds)
            ) {
                throw new ReglaNegocioException(
                    'Uno o más equipos seleccionados no existen.'
                );
            }

            $estadoDisponible =
                EstadoEquipo::query()
                    ->where(
                        'codigo',
                        'DISPONIBLE'
                    )
                    ->where(
                        'activo',
                        true
                    )
                    ->firstOrFail();

            $datosDetalles = [];

            foreach ($equipos as $equipo) {
                if (!$equipo->activo) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} se encuentra inactivo."
                    );
                }

                if (
                    $equipo->estado_actual_id
                    !==
                    $estadoDisponible->id
                ) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no está disponible para venta."
                    );
                }

                $precio =
                    $equipo->precios()
                        ->where(
                            'vigente',
                            true
                        )
                        ->orderByDesc(
                            'vigente_desde'
                        )
                        ->first();

                if (!$precio) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no posee un precio vigente."
                    );
                }

                $precioLista =
                    (float)
                    $precio->precio_publico;

                $precioUnitario =
                    array_key_exists(
                        $equipo->id,
                        $preciosAcordados
                    )
                    ?
                    (float)
                    $preciosAcordados[$equipo->id]
                    :
                    $precioLista;

                if ($precioUnitario <= 0) {
                    throw new ReglaNegocioException(
                        "El precio acordado del equipo {$equipo->codigo_interno} debe ser mayor a cero."
                    );
                }

                $descuento =
                    max(
                        0,
                        $precioLista - $precioUnitario
                    );

                $economia =
                    $this->snapshotEconomico(
                        $equipo,
                        $precioUnitario
                    );

                $datosDetalles[$equipo->id] = [
                    'precio_lista' =>
                        $precioLista,

                    'descuento' =>
                        $descuento,

                    'precio_unitario' =>
                        $precioUnitario,

                    'economia' =>
                        $economia,
                ];
            }

            $subtotal =
                collect($datosDetalles)
                    ->sum(
                        fn($detalle) =>
                        (float)
                        $detalle['precio_lista']
                    );

            $descuentoTotal =
                collect($datosDetalles)
                    ->sum(
                        fn($detalle) =>
                        (float)
                        $detalle['descuento']
                    );

            $total =
                collect($datosDetalles)
                    ->sum(
                        fn($detalle) =>
                        (float)
                        $detalle['precio_unitario']
                    );

            $venta =
                Venta::create([
                    'numero' =>
                        $this->generarNumero(),

                    'cliente_id' =>
                        $cliente?->id,

                    'vendedor_id' =>
                        $vendedor->id,

                    'reserva_id' =>
                        null,

                    'fecha_venta' =>
                        now(),

                    'subtotal' =>
                        $subtotal,

                    'descuento_total' =>
                        $descuentoTotal,

                    'total' =>
                        $total,

                    'estado' =>
                        'REGISTRADA',

                    'observacion' =>
                        $observacion,
                ]);

            foreach ($equipos as $equipo) {
                $datos =
                    $datosDetalles[$equipo->id];

                $detalleVenta =
                    $venta->detalles()->create([
                        'producto_id' =>
                            $equipo->producto_id,

                        'equipo_id' =>
                            $equipo->id,

                        'cantidad' =>
                            1,

                        'precio_lista_snapshot' =>
                            $datos['precio_lista'],

                        'descuento_unitario' =>
                            $datos['descuento'],

                        'precio_unitario' =>
                            $datos['precio_unitario'],

                        'costo_unitario_snapshot' =>
                            $datos['economia']['costo_actualizado'],

                        'tipo_cambio_snapshot_id' =>
                            $datos['economia']['tipo_cambio_id'],

                        'tipo_cambio_valor_snapshot' =>
                            $datos['economia']['tipo_cambio'],

                        'moneda_origen_snapshot' =>
                            $datos['economia']['moneda_origen'],

                        'monto_origen_snapshot' =>
                            $datos['economia']['monto_origen'],

                        'fuente_costo_snapshot' =>
                            $datos['economia']['fuente_costo'],

                        'margen_total_snapshot' =>
                            $datos['economia']['margen_total'],

                        'ganancia_snapshot' =>
                            $datos['economia']['ganancia'],

                        'hugo_snapshot' =>
                            $datos['economia']['reparto']['hugo'],

                        'daniel_snapshot' =>
                            $datos['economia']['reparto']['daniel'],

                        'tienda_snapshot' =>
                            $datos['economia']['reparto']['tienda'],

                        'subtotal' =>
                            $datos['precio_unitario'],

                        'observacion' =>
                            null,
                    ]);

                /*
                 * Venta directa:
                 * la unidad sale de la cantidad disponible del almacén.
                 * Este movimiento debe ocurrir dentro de la misma transacción
                 * que crea la venta para evitar ventas sin salida física.
                 */
                $this->movimientoInventarioService
                    ->registrarSalida(
                        productoId:
                            $equipo->producto_id,

                        almacenId:
                            $equipo->almacen_actual_id,

                        cantidad:
                            1,

                        tipoCodigo:
                            'VENTA_DIRECTA',

                        usuarioId:
                            $vendedor->id,

                        tipoReferencia:
                            'VENTA_DIRECTA',

                        referenciaId:
                            $venta->id,

                        observacion:
                            "Venta directa {$venta->numero}"
                    );

                $this->estadoEquipoService
                    ->cambiarEstado(
                        equipoId:
                            $equipo->id,

                        codigoEstadoDestino:
                            'VENDIDO',

                        usuarioId:
                            $vendedor->id,

                        autorizadoPorId:
                            null,

                        motivo:
                            "Venta directa {$venta->numero}"
                    );

                $this->garantiaService
                    ->crearDesdeDetalleVenta(
                        $detalleVenta
                    );
            }

            return $venta->fresh([
                'cliente',
                'vendedor',
                'detalles.producto',
                'detalles.equipo.estadoActual',
            ]);
        }, 3);
    }

    public function convertirReservaEnVenta(
        int $reservaId,
        int $vendedorId,
        ?string $observacion = null
    ): Venta {
        return DB::transaction(function () use (
            $reservaId,
            $vendedorId,
            $observacion
        ) {
            $vendedor =
                $this->obtenerVendedorActivo(
                    $vendedorId
                );

            $reserva =
                Reserva::query()
                    ->with('detalles')
                    ->lockForUpdate()
                    ->find($reservaId);

            if (!$reserva) {
                throw new ReglaNegocioException(
                    'La reserva no existe.'
                );
            }

            if ($reserva->estado !== 'ACTIVA') {
                throw new ReglaNegocioException(
                    'La reserva ya no se encuentra activa.'
                );
            }

            if (
                $reserva->fecha_expiracion
                && $reserva->fecha_expiracion
                    ->lessThanOrEqualTo(now())
            ) {
                throw new ReglaNegocioException(
                    'La reserva se encuentra vencida y no puede convertirse en venta.'
                );
            }

            if ($reserva->detalles->isEmpty()) {
                throw new ReglaNegocioException(
                    'La reserva no contiene equipos.'
                );
            }

            $equiposIds =
                $reserva->detalles
                    ->pluck('equipo_id')
                    ->unique()
                    ->values()
                    ->all();

            $equipos =
                Equipo::query()
                    ->whereIn(
                        'id',
                        $equiposIds
                    )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

            $estadoReservado =
                EstadoEquipo::query()
                    ->where(
                        'codigo',
                        'RESERVADO'
                    )
                    ->where(
                        'activo',
                        true
                    )
                    ->firstOrFail();

            $detallesVenta = [];

            foreach ($reserva->detalles as $detalleReserva) {
                $equipo =
                    $equipos->get(
                        $detalleReserva->equipo_id
                    );

                if (!$equipo) {
                    throw new ReglaNegocioException(
                        'Equipo reservado no encontrado.'
                    );
                }

                if (!$equipo->activo) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} se encuentra inactivo."
                    );
                }

                if (
                    $equipo->estado_actual_id
                    !==
                    $estadoReservado->id
                ) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} ya no se encuentra reservado y la venta no puede consolidarse."
                    );
                }

                $precioUnitario =
                    (float)
                    $detalleReserva
                        ->precio_acordado;

                $economia =
                    $this->snapshotEconomico(
                        $equipo,
                        $precioUnitario
                    );

                $detallesVenta[] = [
                    'equipo' =>
                        $equipo,

                    'precio_lista' =>
                        (float)
                        $detalleReserva
                            ->precio_acordado
                        +
                        (float)
                        $detalleReserva
                            ->descuento_acordado,

                    'descuento' =>
                        (float)
                        $detalleReserva
                            ->descuento_acordado,

                    'precio_unitario' =>
                        $precioUnitario,

                    'economia' =>
                        $economia,
                ];
            }

            $venta =
                Venta::create([
                    'numero' =>
                        $this->generarNumero(),

                    'cliente_id' =>
                        $reserva->cliente_id,

                    'vendedor_id' =>
                        $vendedor->id,

                    'reserva_id' =>
                        $reserva->id,

                    'fecha_venta' =>
                        now(),

                    'subtotal' =>
                        collect($detallesVenta)
                            ->sum('precio_lista'),

                    'descuento_total' =>
                        collect($detallesVenta)
                            ->sum('descuento'),

                    'total' =>
                        collect($detallesVenta)
                            ->sum('precio_unitario'),

                    'estado' =>
                        'REGISTRADA',

                    'observacion' =>
                        $observacion,
                ]);

            foreach ($detallesVenta as $datos) {
                $equipo =
                    $datos['equipo'];

                $detalleVenta =
                    $venta->detalles()->create([
                        'producto_id' =>
                            $equipo->producto_id,

                        'equipo_id' =>
                            $equipo->id,

                        'cantidad' =>
                            1,

                        'precio_lista_snapshot' =>
                            $datos['precio_lista'],

                        'descuento_unitario' =>
                            $datos['descuento'],

                        'precio_unitario' =>
                            $datos['precio_unitario'],

                        'costo_unitario_snapshot' =>
                            $datos['economia']['costo_actualizado'],

                        'tipo_cambio_snapshot_id' =>
                            $datos['economia']['tipo_cambio_id'],

                        'tipo_cambio_valor_snapshot' =>
                            $datos['economia']['tipo_cambio'],

                        'moneda_origen_snapshot' =>
                            $datos['economia']['moneda_origen'],

                        'monto_origen_snapshot' =>
                            $datos['economia']['monto_origen'],

                        'fuente_costo_snapshot' =>
                            $datos['economia']['fuente_costo'],

                        'margen_total_snapshot' =>
                            $datos['economia']['margen_total'],

                        'ganancia_snapshot' =>
                            $datos['economia']['ganancia'],

                        'hugo_snapshot' =>
                            $datos['economia']['reparto']['hugo'],

                        'daniel_snapshot' =>
                            $datos['economia']['reparto']['daniel'],

                        'tienda_snapshot' =>
                            $datos['economia']['reparto']['tienda'],

                        'subtotal' =>
                            $datos['precio_unitario'],
                    ]);

                $this->estadoEquipoService
                    ->cambiarEstado(
                        equipoId:
                            $equipo->id,

                        codigoEstadoDestino:
                            'VENDIDO',

                        usuarioId:
                            $vendedor->id,

                        autorizadoPorId:
                            null,

                        motivo:
                            "Venta {$venta->numero} desde reserva"
                    );

                $this->movimientoInventarioService
                    ->registrarVentaReserva(
                        productoId:
                            $equipo->producto_id,

                        almacenId:
                            $equipo->almacen_actual_id,

                        cantidad:
                            1,

                        usuarioId:
                            $vendedor->id,

                        tipoReferencia:
                            'VENTA_RESERVADA',

                        referenciaId:
                            $venta->id,

                        observacion:
                            "Venta {$venta->numero} desde reserva"
                    );

                $this->garantiaService
                    ->crearDesdeDetalleVenta(
                        $detalleVenta
                    );
            }

            $reserva->estado =
                'CONVERTIDA';

            $reserva->fecha_cierre =
                now();

            $reserva->save();

            return $venta->fresh();
        }, 3);
    }

    /**
     * Obtiene la fotografía económica definitiva del equipo
     * en el instante en que se registra la venta.
     *
     * La fuente de verdad es el mismo motor utilizado por
     * Precios/Reservas para evaluar GANANCIA.
     */
    private function snapshotEconomico(
        Equipo $equipo,
        float $precioFinal
    ): array {
        return $this
            ->rentabilidadRebajaService
            ->evaluar(
                $equipo,
                $precioFinal
            );
    }


    private function obtenerVendedorActivo(
        int $vendedorId
    ): User {
        $vendedor =
            User::query()
                ->where(
                    'activo',
                    true
                )
                ->find($vendedorId);

        if (!$vendedor) {
            throw new ReglaNegocioException(
                'El vendedor no existe o está inactivo.'
            );
        }

        return $vendedor;
    }

    private function obtenerCliente(
        ?int $clienteId
    ): ?Cliente {
        if ($clienteId === null) {
            return null;
        }

        return Cliente::findOrFail(
            $clienteId
        );
    }

    private function normalizarEquiposIds(
        array $equiposIds
    ): array {
        $ids =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        $equiposIds
                    )
                )
            );

        if (empty($ids)) {
            throw new ReglaNegocioException(
                'La venta debe contener al menos un equipo.'
            );
        }

        return $ids;
    }

    private function generarNumero(): string
    {
        return 'VEN-'
            .
            now()->format('Ymd')
            .
            '-'
            .
            Str::upper(
                Str::ulid()
            );
    }
}
