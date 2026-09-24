<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class PagoService
{
    public function registrarPagoReserva(
        int $reservaId,
        int $metodoPagoId,
        string|int|float $monto,
        int $registradoPorId,
        ?string $referencia = null,
        ?string $comprobanteRuta = null,
        ?string $observacion = null
    ): Pago {
        return DB::transaction(function () use (
            $reservaId,
            $metodoPagoId,
            $monto,
            $registradoPorId,
            $referencia,
            $comprobanteRuta,
            $observacion
        ) {
            $reserva = Reserva::query()
                ->lockForUpdate()
                ->find($reservaId);

            if (!$reserva) {
                throw new ReglaNegocioException(
                    'La reserva no existe.'
                );
            }

            if ($reserva->estado !== 'ACTIVA') {
                throw new ReglaNegocioException(
                    'Solo se pueden registrar pagos en reservas activas.'
                );
            }

            $monto = $this->normalizarMonto($monto);

            $totalReserva = $this->calcularTotalReserva(
                $reserva
            );

            $comprometido = $this->sumarPagosReserva(
                reservaId: $reserva->id,
                estados: ['PENDIENTE', 'VERIFICADO']
            );

            $saldoDisponible = bcsub(
                $totalReserva,
                $comprometido,
                2
            );

            $this->validarMontoContraSaldo(
                $monto,
                $saldoDisponible
            );

            return $this->crearPago(
                reservaId: $reserva->id,
                ventaId: null,
                metodoPagoId: $metodoPagoId,
                monto: $monto,
                registradoPorId: $registradoPorId,
                referencia: $referencia,
                comprobanteRuta: $comprobanteRuta,
                observacion: $observacion
            );
        }, 3);
    }

    public function registrarPagoVenta(
        int $ventaId,
        int $metodoPagoId,
        string|int|float $monto,
        int $registradoPorId,
        ?string $referencia = null,
        ?string $comprobanteRuta = null,
        ?string $observacion = null
    ): Pago {
        return DB::transaction(function () use (
            $ventaId,
            $metodoPagoId,
            $monto,
            $registradoPorId,
            $referencia,
            $comprobanteRuta,
            $observacion
        ) {
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
                    'No se pueden registrar pagos en una venta anulada.'
                );
            }

            $monto = $this->normalizarMonto($monto);

            /*
             * Para evitar sobrepagos consideramos:
             *
             * - pagos VERIFICADOS
             * - pagos PENDIENTES
             *
             * tanto de la venta como de la reserva original.
             */
            $comprometido = $this->calcularPagadoVenta(
                venta: $venta,
                estados: ['PENDIENTE', 'VERIFICADO']
            );

            $saldoDisponible = bcsub(
                (string) $venta->total,
                $comprometido,
                2
            );

            $this->validarMontoContraSaldo(
                $monto,
                $saldoDisponible
            );

            return $this->crearPago(
                reservaId: null,
                ventaId: $venta->id,
                metodoPagoId: $metodoPagoId,
                monto: $monto,
                registradoPorId: $registradoPorId,
                referencia: $referencia,
                comprobanteRuta: $comprobanteRuta,
                observacion: $observacion
            );
        }, 3);
    }

    public function verificarPago(
        int $pagoId,
        int $verificadoPorId
    ): Pago {
        return DB::transaction(function () use (
            $pagoId,
            $verificadoPorId
        ) {
            $usuario = $this->obtenerUsuarioActivo(
                $verificadoPorId
            );

            $pago = Pago::query()
                ->lockForUpdate()
                ->find($pagoId);

            if (!$pago) {
                throw new ReglaNegocioException(
                    'El pago no existe.'
                );
            }

            if ($pago->estado !== 'PENDIENTE') {
                throw new ReglaNegocioException(
                    'Solo se pueden verificar pagos pendientes.'
                );
            }

            $pago->estado = 'VERIFICADO';
            $pago->verificado_por_id = $usuario->id;
            $pago->fecha_verificacion = now();
            $pago->motivo_rechazo = null;
            $pago->save();

            return $pago->fresh([
                'metodoPago',
                'registradoPor',
                'verificadoPor',
            ]);
        }, 3);
    }

    public function rechazarPago(
        int $pagoId,
        int $verificadoPorId,
        string $motivo
    ): Pago {
        return DB::transaction(function () use (
            $pagoId,
            $verificadoPorId,
            $motivo
        ) {
            $usuario = $this->obtenerUsuarioActivo(
                $verificadoPorId
            );

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new ReglaNegocioException(
                    'Debe indicar el motivo del rechazo.'
                );
            }

            $pago = Pago::query()
                ->lockForUpdate()
                ->find($pagoId);

            if (!$pago) {
                throw new ReglaNegocioException(
                    'El pago no existe.'
                );
            }

            if ($pago->estado !== 'PENDIENTE') {
                throw new ReglaNegocioException(
                    'Solo se pueden rechazar pagos pendientes.'
                );
            }

            $pago->estado = 'RECHAZADO';
            $pago->verificado_por_id = $usuario->id;
            $pago->fecha_verificacion = now();
            $pago->motivo_rechazo = $motivo;
            $pago->save();

            return $pago->fresh([
                'metodoPago',
                'registradoPor',
                'verificadoPor',
            ]);
        }, 3);
    }

    public function obtenerResumenVenta(
        int $ventaId
    ): array {
        $venta = Venta::query()
            ->find($ventaId);

        if (!$venta) {
            throw new ReglaNegocioException(
                'La venta no existe.'
            );
        }

        $pagadoReserva = '0.00';

        if ($venta->reserva_id !== null) {
            $pagadoReserva = $this->sumarPagosReserva(
                reservaId: $venta->reserva_id,
                estados: ['VERIFICADO']
            );
        }

        $pagadoVenta = $this->sumarPagosVenta(
            ventaId: $venta->id,
            estados: ['VERIFICADO']
        );

        $pagadoTotal = bcadd(
            $pagadoReserva,
            $pagadoVenta,
            2
        );

        $pendienteReserva = '0.00';

        if ($venta->reserva_id !== null) {
            $pendienteReserva = $this->sumarPagosReserva(
                reservaId: $venta->reserva_id,
                estados: ['PENDIENTE']
            );
        }

        $pendienteVenta = $this->sumarPagosVenta(
            ventaId: $venta->id,
            estados: ['PENDIENTE']
        );

        $pendienteTotal = bcadd(
            $pendienteReserva,
            $pendienteVenta,
            2
        );

        $comprometidoTotal = bcadd(
            $pagadoTotal,
            $pendienteTotal,
            2
        );

        $saldo = bcsub(
            (string) $venta->total,
            $pagadoTotal,
            2
        );

        $saldoDisponible = bcsub(
            (string) $venta->total,
            $comprometidoTotal,
            2
        );

        if (bccomp($saldoDisponible, '0.00', 2) < 0) {
            $saldoDisponible = '0.00';
        }

        return [
            'total' => bcadd(
                (string) $venta->total,
                '0',
                2
            ),

            'pagado_reserva' => $pagadoReserva,
            'pagado_venta' => $pagadoVenta,
            'pagado_total' => $pagadoTotal,

            'pendiente_reserva' => $pendienteReserva,
            'pendiente_venta' => $pendienteVenta,
            'pendiente_total' => $pendienteTotal,

            'comprometido_total' => $comprometidoTotal,

            'saldo' => $saldo,
            'saldo_disponible' => $saldoDisponible,
        ];
    }

    private function crearPago(
        ?int $reservaId,
        ?int $ventaId,
        int $metodoPagoId,
        string $monto,
        int $registradoPorId,
        ?string $referencia,
        ?string $comprobanteRuta,
        ?string $observacion
    ): Pago {
        $usuario = $this->obtenerUsuarioActivo(
            $registradoPorId
        );

        $metodo = MetodoPago::query()
            ->where('activo', true)
            ->find($metodoPagoId);

        if (!$metodo) {
            throw new ReglaNegocioException(
                'El método de pago no existe o se encuentra inactivo.'
            );
        }

        if (
            $metodo->requiere_verificacion
            && empty(trim((string) $referencia))
            && empty(trim((string) $comprobanteRuta))
        ) {
            throw new ReglaNegocioException(
                'El pago requiere una referencia o comprobante para su posterior verificación.'
            );
        }

        $requiereVerificacion =
            (bool) $metodo->requiere_verificacion;

        return Pago::create([
            'reserva_id' => $reservaId,
            'venta_id' => $ventaId,
            'metodo_pago_id' => $metodo->id,
            'monto' => $monto,
            'fecha_pago' => now(),
            'referencia' => $referencia,
            'comprobante_ruta' => $comprobanteRuta,

            'estado' => $requiereVerificacion
                ? 'PENDIENTE'
                : 'VERIFICADO',

            'registrado_por_id' => $usuario->id,

            'verificado_por_id' => $requiereVerificacion
                ? null
                : $usuario->id,

            'fecha_verificacion' => $requiereVerificacion
                ? null
                : now(),

            'motivo_rechazo' => null,
            'observacion' => $observacion,
        ]);
    }

    private function calcularTotalReserva(
        Reserva $reserva
    ): string {
        $total = '0.00';

        foreach ($reserva->detalles()->get() as $detalle) {
            $total = bcadd(
                $total,
                (string) $detalle->precio_acordado,
                2
            );
        }

        return $total;
    }

    private function calcularPagadoVenta(
        Venta $venta,
        array $estados
    ): string {
        $total = $this->sumarPagosVenta(
            ventaId: $venta->id,
            estados: $estados
        );

        if ($venta->reserva_id !== null) {
            $total = bcadd(
                $total,
                $this->sumarPagosReserva(
                    reservaId: $venta->reserva_id,
                    estados: $estados
                ),
                2
            );
        }

        return $total;
    }

    private function sumarPagosReserva(
        int $reservaId,
        array $estados
    ): string {
        $monto = Pago::query()
            ->where('reserva_id', $reservaId)
            ->whereIn('estado', $estados)
            ->sum('monto');

        return bcadd(
            (string) $monto,
            '0',
            2
        );
    }

    private function sumarPagosVenta(
        int $ventaId,
        array $estados
    ): string {
        $monto = Pago::query()
            ->where('venta_id', $ventaId)
            ->whereIn('estado', $estados)
            ->sum('monto');

        return bcadd(
            (string) $monto,
            '0',
            2
        );
    }

    private function normalizarMonto(
        string|int|float $monto
    ): string {
        if (!is_numeric($monto)) {
            throw new ReglaNegocioException(
                'El monto del pago no es válido.'
            );
        }

        $monto = bcadd(
            (string) $monto,
            '0',
            2
        );

        if (bccomp($monto, '0.00', 2) <= 0) {
            throw new ReglaNegocioException(
                'El monto del pago debe ser mayor a cero.'
            );
        }

        return $monto;
    }

    private function validarMontoContraSaldo(
        string $monto,
        string $saldo
    ): void {
        if (bccomp($saldo, '0.00', 2) <= 0) {
            throw new ReglaNegocioException(
                'La operación ya no tiene saldo pendiente.'
            );
        }

        if (bccomp($monto, $saldo, 2) === 1) {
            throw new ReglaNegocioException(
                "El pago supera el saldo pendiente de Bs {$saldo}."
            );
        }
    }

    private function obtenerUsuarioActivo(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        return $usuario;
    }
}