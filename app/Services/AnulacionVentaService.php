<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleVenta;
use App\Models\Equipo;
use App\Models\Pago;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class AnulacionVentaService
{
    public function __construct(
        private readonly MovimientoInventarioService $movimientoInventarioService,
        private readonly EstadoEquipoService $estadoEquipoService,
        private readonly GarantiaService $garantiaService
    ) {
    }

    /**
     * Anula una venta conservando su información histórica.
     *
     * La operación completa es transaccional:
     * - valida permisos y estado;
     * - bloquea ventas con pagos comprometidos;
     * - bloquea garantías que ya tengan casos registrados;
     * - devuelve los equipos a inventario;
     * - cambia los equipos a DISPONIBLE;
     * - anula las garantías;
     * - conserva venta, detalles y valores históricos.
     */
    public function anular(
        int $ventaId,
        int $usuarioId,
        string $motivo
    ): Venta {
        return DB::transaction(function () use (
            $ventaId,
            $usuarioId,
            $motivo
        ) {
            $usuario = User::query()
                ->where('activo', true)
                ->find($usuarioId);

            if (!$usuario) {
                throw new ReglaNegocioException(
                    'El usuario no existe o se encuentra inactivo.'
                );
            }

            if (!$usuario->tienePermiso('ventas.anular')) {
                throw new ReglaNegocioException(
                    'El usuario no cuenta con permiso para anular ventas.'
                );
            }

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new ReglaNegocioException(
                    'Debe indicar el motivo de la anulación.'
                );
            }

            $venta = Venta::query()
                ->lockForUpdate()
                ->find($ventaId);

            if (!$venta) {
                throw new ReglaNegocioException(
                    'La venta no existe.'
                );
            }

            if ($venta->estado === 'ANULADA') {
                throw new ReglaNegocioException(
                    'La venta ya se encuentra anulada.'
                );
            }

            if ($venta->estado !== 'REGISTRADA') {
                throw new ReglaNegocioException(
                    'Solo se pueden anular ventas registradas.'
                );
            }

            /*
             * PagoService también bloquea la venta antes de registrar pagos.
             * Al bloquear aquí la misma fila de venta evitamos que un pago y
             * una anulación se confirmen de manera concurrente.
             */
            $pagoComprometido = Pago::query()
                ->whereIn(
                    'estado',
                    [
                        'PENDIENTE',
                        'VERIFICADO',
                    ]
                )
                ->where(function ($query) use ($venta) {
                    $query->where(
                        'venta_id',
                        $venta->id
                    );

                    if ($venta->reserva_id !== null) {
                        $query->orWhere(
                            'reserva_id',
                            $venta->reserva_id
                        );
                    }
                })
                ->first();

            if ($pagoComprometido) {
                throw new ReglaNegocioException(
                    'La venta tiene pagos registrados y no puede anularse sin un proceso de devolución.'
                );
            }

            $detalles = DetalleVenta::query()
                ->where(
                    'venta_id',
                    $venta->id
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($detalles->isEmpty()) {
                throw new ReglaNegocioException(
                    'La venta no contiene equipos para revertir.'
                );
            }

            $equiposIds = $detalles
                ->pluck('equipo_id')
                ->filter()
                ->map(
                    fn ($id) => (int) $id
                )
                ->unique()
                ->values();

            if (
                $equiposIds->count()
                !==
                $detalles->count()
            ) {
                throw new ReglaNegocioException(
                    'La venta contiene un detalle sin equipo asociado y requiere revisión administrativa.'
                );
            }

            $equipos = Equipo::query()
                ->with('estadoActual')
                ->whereIn(
                    'id',
                    $equiposIds->all()
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (
                $equipos->count()
                !==
                $equiposIds->count()
            ) {
                throw new ReglaNegocioException(
                    'Uno o más equipos de la venta ya no existen.'
                );
            }

            /*
             * Primero se valida todo. No se modifica nada hasta comprobar
             * que cada unidad puede regresar correctamente a inventario.
             */
            foreach ($detalles as $detalle) {
                $equipo = $equipos->get(
                    (int) $detalle->equipo_id
                );

                if (
                    !$equipo
                    ||
                    $equipo->estadoActual?->codigo
                        !== 'VENDIDO'
                ) {
                    throw new ReglaNegocioException(
                        'Todos los equipos deben continuar en estado VENDIDO para anular la venta.'
                    );
                }

                if ($equipo->almacen_actual_id === null) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no tiene un almacén asignado."
                    );
                }

                /*
                 * Este método también valida que la garantía no tenga
                 * casos de atención registrados.
                 */
                $this->garantiaService
                    ->validarAnulacionDesdeDetalleVenta(
                        $detalle
                    );
            }

            foreach ($detalles as $detalle) {
                $equipo = $equipos->get(
                    (int) $detalle->equipo_id
                );

                $this->garantiaService
                    ->anularDesdeDetalleVenta(
                        $detalle
                    );

                $this->movimientoInventarioService
                    ->registrarEntrada(
                        productoId:
                            $equipo->producto_id,

                        almacenId:
                            $equipo->almacen_actual_id,

                        cantidad:
                            (int) $detalle->cantidad,

                        tipoCodigo:
                            'ANULACION_VENTA',

                        usuarioId:
                            $usuario->id,

                        tipoReferencia:
                            'ANULACION_VENTA',

                        referenciaId:
                            $venta->id,

                        observacion:
                            "Anulación de venta {$venta->numero}: {$motivo}"
                    );

                $this->estadoEquipoService
                    ->cambiarEstado(
                        equipoId:
                            $equipo->id,

                        codigoEstadoDestino:
                            'DISPONIBLE',

                        usuarioId:
                            $usuario->id,

                        autorizadoPorId:
                            null,

                        motivo:
                            "Anulación de venta {$venta->numero}",

                        observacion:
                            $motivo
                    );
            }

            $venta->estado = 'ANULADA';
            $venta->anulado_por_id = $usuario->id;
            $venta->fecha_anulacion = now();
            $venta->motivo_anulacion = $motivo;
            $venta->save();

            /*
             * La reserva de origen, si existe, permanece CONVERTIDA.
             * No se reactiva automáticamente una reserva antigua.
             */
            return $venta->fresh([
                'anuladoPor',
                'detalles.equipo.estadoActual',
                'detalles.garantia',
            ]);
        }, 3);
    }
}
