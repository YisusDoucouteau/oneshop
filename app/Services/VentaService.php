<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VentaService
{
    public function __construct(
        private readonly EstadoEquipoService $estadoEquipoService
    ) {
    }

    /**
     * Registra una venta directa de equipos serializados.
     *
     * No crea una reserva artificial.
     */
    public function registrarVentaDirecta(
        int $vendedorId,
        array $equiposIds,
        ?int $clienteId = null,
        ?string $observacion = null
    ): Venta {
        return DB::transaction(function () use (
            $vendedorId,
            $equiposIds,
            $clienteId,
            $observacion
        ) {
            $vendedor = $this->obtenerVendedorActivo(
                $vendedorId
            );

            $cliente = $this->obtenerCliente(
                $clienteId
            );

            $equiposIds = $this->normalizarEquiposIds(
                $equiposIds
            );

            $equipos = Equipo::query()
                ->whereIn('id', $equiposIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($equipos->count() !== count($equiposIds)) {
                throw new ReglaNegocioException(
                    'Uno o más equipos seleccionados no existen.'
                );
            }

            $estadoDisponible = EstadoEquipo::query()
                ->where('codigo', 'DISPONIBLE')
                ->where('activo', true)
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
                    !== $estadoDisponible->id
                ) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no está disponible para venta."
                    );
                }

                $precio = $equipo->precios()
                    ->where('vigente', true)
                    ->orderByDesc('vigente_desde')
                    ->first();

                if (!$precio) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no posee un precio vigente."
                    );
                }

                $datosDetalles[$equipo->id] = [
                    'precio_lista' => $precio->precio_publico,
                    'descuento' => 0,
                    'precio_unitario' => $precio->precio_publico,
                    'costo' => $precio->costo_total_snapshot,
                ];
            }

            $subtotal = collect($datosDetalles)
                ->sum(
                    fn (array $detalle) =>
                        (float) $detalle['precio_lista']
                );

            $descuentoTotal = collect($datosDetalles)
                ->sum(
                    fn (array $detalle) =>
                        (float) $detalle['descuento']
                );

            $total = collect($datosDetalles)
                ->sum(
                    fn (array $detalle) =>
                        (float) $detalle['precio_unitario']
                );

            $venta = Venta::create([
                'numero' => $this->generarNumero(),
                'cliente_id' => $cliente?->id,
                'vendedor_id' => $vendedor->id,
                'reserva_id' => null,
                'fecha_venta' => now(),
                'subtotal' => $subtotal,
                'descuento_total' => $descuentoTotal,
                'total' => $total,
                'estado' => 'REGISTRADA',
                'anulado_por_id' => null,
                'fecha_anulacion' => null,
                'motivo_anulacion' => null,
                'observacion' => $observacion,
            ]);

            foreach ($equipos as $equipo) {
                $datos = $datosDetalles[$equipo->id];

                $venta->detalles()->create([
                    'producto_id' => $equipo->producto_id,
                    'equipo_id' => $equipo->id,
                    'cantidad' => 1,
                    'precio_lista_snapshot' =>
                        $datos['precio_lista'],
                    'descuento_unitario' =>
                        $datos['descuento'],
                    'precio_unitario' =>
                        $datos['precio_unitario'],
                    'costo_unitario_snapshot' =>
                        $datos['costo'],
                    'subtotal' =>
                        $datos['precio_unitario'],
                    'observacion' => null,
                ]);

                $this->estadoEquipoService->cambiarEstado(
                    equipoId: $equipo->id,
                    codigoEstadoDestino: 'VENDIDO',
                    usuarioId: $vendedor->id,
                    autorizadoPorId: null,
                    motivo: "Venta directa {$venta->numero}"
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

    /**
     * Convierte una reserva existente en venta.
     */
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
            $vendedor = $this->obtenerVendedorActivo(
                $vendedorId
            );

            $reserva = Reserva::query()
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
                $reserva->fecha_expiracion !== null
                && $reserva->fecha_expiracion->isPast()
            ) {
                throw new ReglaNegocioException(
                    'La reserva se encuentra expirada.'
                );
            }

            if ($reserva->detalles->isEmpty()) {
                throw new ReglaNegocioException(
                    'La reserva no contiene equipos.'
                );
            }

            if (
                Venta::query()
                    ->where('reserva_id', $reserva->id)
                    ->exists()
            ) {
                throw new ReglaNegocioException(
                    'La reserva ya fue convertida en una venta.'
                );
            }

            $equiposIds = $reserva->detalles
                ->pluck('equipo_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $equipos = Equipo::query()
                ->whereIn('id', $equiposIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($equipos->count() !== count($equiposIds)) {
                throw new ReglaNegocioException(
                    'Uno o más equipos de la reserva ya no existen.'
                );
            }

            $estadoReservado = EstadoEquipo::query()
                ->where('codigo', 'RESERVADO')
                ->where('activo', true)
                ->firstOrFail();

            $detallesVenta = [];

            foreach ($reserva->detalles as $detalleReserva) {
                $equipo = $equipos->get(
                    $detalleReserva->equipo_id
                );

                if (!$equipo || !$equipo->activo) {
                    throw new ReglaNegocioException(
                        'Uno de los equipos reservados se encuentra inactivo.'
                    );
                }

                if (
                    $equipo->estado_actual_id
                    !== $estadoReservado->id
                ) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} ya no se encuentra reservado."
                    );
                }

                /*
                 * Para recuperar el precio de lista que existía
                 * al momento de reservar:
                 *
                 * precio_acordado + descuento_acordado.
                 */
                $precioLista =
                    (float) $detalleReserva->precio_acordado
                    + (float) $detalleReserva->descuento_acordado;

                $precioVigente = $equipo->precios()
                    ->where('vigente', true)
                    ->orderByDesc('vigente_desde')
                    ->first();

                if (!$precioVigente) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no posee información de costo vigente."
                    );
                }

                $detallesVenta[] = [
                    'equipo' => $equipo,
                    'precio_lista' => $precioLista,
                    'descuento' =>
                        (float) $detalleReserva->descuento_acordado,
                    'precio_unitario' =>
                        (float) $detalleReserva->precio_acordado,
                    'costo' =>
                        $precioVigente->costo_total_snapshot,
                ];
            }

            $subtotal = collect($detallesVenta)
                ->sum('precio_lista');

            $descuentoTotal = collect($detallesVenta)
                ->sum('descuento');

            $total = collect($detallesVenta)
                ->sum('precio_unitario');

            $venta = Venta::create([
                'numero' => $this->generarNumero(),
                'cliente_id' => $reserva->cliente_id,
                'vendedor_id' => $vendedor->id,
                'reserva_id' => $reserva->id,
                'fecha_venta' => now(),
                'subtotal' => $subtotal,
                'descuento_total' => $descuentoTotal,
                'total' => $total,
                'estado' => 'REGISTRADA',
                'anulado_por_id' => null,
                'fecha_anulacion' => null,
                'motivo_anulacion' => null,
                'observacion' => $observacion,
            ]);

            foreach ($detallesVenta as $datos) {
                /** @var Equipo $equipo */
                $equipo = $datos['equipo'];

                $venta->detalles()->create([
                    'producto_id' => $equipo->producto_id,
                    'equipo_id' => $equipo->id,
                    'cantidad' => 1,
                    'precio_lista_snapshot' =>
                        $datos['precio_lista'],
                    'descuento_unitario' =>
                        $datos['descuento'],
                    'precio_unitario' =>
                        $datos['precio_unitario'],
                    'costo_unitario_snapshot' =>
                        $datos['costo'],
                    'subtotal' =>
                        $datos['precio_unitario'],
                    'observacion' => null,
                ]);

                $this->estadoEquipoService->cambiarEstado(
                    equipoId: $equipo->id,
                    codigoEstadoDestino: 'VENDIDO',
                    usuarioId: $vendedor->id,
                    autorizadoPorId: null,
                    motivo: "Venta {$venta->numero} desde reserva {$reserva->numero}"
                );
            }

            $reserva->estado = 'CONVERTIDA';
            $reserva->fecha_cierre = now();
            $reserva->save();

            return $venta->fresh([
                'cliente',
                'vendedor',
                'reserva',
                'detalles.producto',
                'detalles.equipo.estadoActual',
            ]);
        }, 3);
    }

    private function obtenerVendedorActivo(
        int $vendedorId
    ): User {
        $vendedor = User::query()
            ->where('activo', true)
            ->find($vendedorId);

        if (!$vendedor) {
            throw new ReglaNegocioException(
                'El vendedor no existe o se encuentra inactivo.'
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

        $cliente = Cliente::query()
            ->where('activo', true)
            ->find($clienteId);

        if (!$cliente) {
            throw new ReglaNegocioException(
                'El cliente no existe o se encuentra inactivo.'
            );
        }

        return $cliente;
    }

    private function normalizarEquiposIds(
        array $equiposIds
    ): array {
        $equiposIds = array_values(
            array_unique(
                array_map('intval', $equiposIds)
            )
        );

        sort($equiposIds);

        if (empty($equiposIds)) {
            throw new ReglaNegocioException(
                'La venta debe contener al menos un equipo.'
            );
        }

        return $equiposIds;
    }

    private function generarNumero(): string
    {
        return 'VEN-' .
            now()->format('Ymd') .
            '-' .
            Str::upper(Str::ulid());
    }
}