<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\CambioEquipo;
use App\Models\MetodoPago;
use App\Models\MovimientoAjusteGarantia;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AjusteGarantiaService
{
    public function registrarMovimiento(
        int $cambioEquipoId,
        int $metodoPagoId,
        string|int|float $monto,
        int $registradoPorId,
        ?string $referencia = null,
        ?string $comprobante = null,
        ?string $observacion = null
    ): MovimientoAjusteGarantia {
        return DB::transaction(function () use (
            $cambioEquipoId,
            $metodoPagoId,
            $monto,
            $registradoPorId,
            $referencia,
            $comprobante,
            $observacion
        ) {
            $cambio = CambioEquipo::query()
                ->lockForUpdate()
                ->find($cambioEquipoId);

            if (!$cambio) {
                throw new ReglaNegocioException(
                    'El cambio de equipo no existe.'
                );
            }

            $this->validarCambioAjustable($cambio);

            if ($cambio->estado_ajuste === 'LIQUIDADO') {
                throw new ReglaNegocioException(
                    'El ajuste económico ya se encuentra liquidado.'
                );
            }

            $usuario = $this->obtenerUsuarioActivoConPermiso(
                $registradoPorId,
                'garantias.ajustes.registrar'
            );

            $metodo = MetodoPago::query()
                ->where('activo', true)
                ->find($metodoPagoId);

            if (!$metodo) {
                throw new ReglaNegocioException(
                    'El método de pago no existe o se encuentra inactivo.'
                );
            }

            $monto = $this->normalizarMonto($monto);

            $referencia = $this->normalizarTexto(
                $referencia,
                150,
                'La referencia no puede superar los 150 caracteres.'
            );

            $comprobante = $this->normalizarTexto(
                $comprobante,
                500,
                'El comprobante no puede superar los 500 caracteres.'
            );

            $observacion = $this->normalizarTexto(
                $observacion,
                null,
                null
            );

            if (
                $metodo->requiere_verificacion
                && $referencia === null
                && $comprobante === null
            ) {
                throw new ReglaNegocioException(
                    'El movimiento requiere una referencia o comprobante para su posterior verificación.'
                );
            }

            $objetivo = $this->obtenerMontoObjetivo($cambio);

            $comprometido = $this->sumarMovimientos(
                cambioEquipoId: $cambio->id,
                estados: ['PENDIENTE', 'VERIFICADO']
            );

            $saldoDisponible = bcsub(
                $objetivo,
                $comprometido,
                2
            );

            $this->validarMontoContraSaldo(
                $monto,
                $saldoDisponible
            );

            $tipoMovimiento = match ($cambio->tipo_ajuste) {
                'COBRO_CLIENTE' => 'COBRO',
                'SALDO_FAVOR_CLIENTE' => 'DEVOLUCION',
                default => throw new ReglaNegocioException(
                    'El tipo de ajuste económico no admite movimientos.'
                ),
            };

            $requiereVerificacion =
                (bool) $metodo->requiere_verificacion;

            $movimiento = MovimientoAjusteGarantia::query()
                ->create([
                    'cambio_equipo_id' => $cambio->id,
                    'tipo_movimiento' => $tipoMovimiento,
                    'metodo_pago_id' => $metodo->id,
                    'monto' => $monto,
                    'fecha_movimiento' => now(),
                    'referencia' => $referencia,
                    'comprobante' => $comprobante,
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

            $this->recalcularEstadoAjuste($cambio);

            return $movimiento->fresh([
                'cambioEquipo',
                'metodoPago',
                'registradoPor',
                'verificadoPor',
            ]);
        }, 3);
    }

    public function verificarMovimiento(
        int $movimientoId,
        int $verificadoPorId
    ): MovimientoAjusteGarantia {
        return DB::transaction(function () use (
            $movimientoId,
            $verificadoPorId
        ) {
            $movimientoBase = MovimientoAjusteGarantia::query()
                ->select(['id', 'cambio_equipo_id'])
                ->find($movimientoId);

            if (!$movimientoBase) {
                throw new ReglaNegocioException(
                    'El movimiento de ajuste no existe.'
                );
            }

            $cambio = CambioEquipo::query()
                ->lockForUpdate()
                ->find($movimientoBase->cambio_equipo_id);

            if (!$cambio) {
                throw new ReglaNegocioException(
                    'El cambio de equipo asociado ya no existe.'
                );
            }

            $this->validarCambioAjustable($cambio);

            $movimiento = MovimientoAjusteGarantia::query()
                ->lockForUpdate()
                ->find($movimientoId);

            if (!$movimiento) {
                throw new ReglaNegocioException(
                    'El movimiento de ajuste no existe.'
                );
            }

            if ($movimiento->estado !== 'PENDIENTE') {
                throw new ReglaNegocioException(
                    'Solo se pueden verificar movimientos pendientes.'
                );
            }

            $usuario = $this->obtenerUsuarioActivoConPermiso(
                $verificadoPorId,
                'garantias.ajustes.verificar'
            );

            $objetivo = $this->obtenerMontoObjetivo($cambio);

            $verificadoActual = $this->sumarMovimientos(
                cambioEquipoId: $cambio->id,
                estados: ['VERIFICADO']
            );

            $verificadoPotencial = bcadd(
                $verificadoActual,
                (string) $movimiento->monto,
                2
            );

            if (bccomp($verificadoPotencial, $objetivo, 2) === 1) {
                throw new ReglaNegocioException(
                    'La verificación del movimiento superaría el importe total del ajuste.'
                );
            }

            $movimiento->estado = 'VERIFICADO';
            $movimiento->verificado_por_id = $usuario->id;
            $movimiento->fecha_verificacion = now();
            $movimiento->motivo_rechazo = null;
            $movimiento->save();

            $this->recalcularEstadoAjuste($cambio);

            return $movimiento->fresh([
                'cambioEquipo',
                'metodoPago',
                'registradoPor',
                'verificadoPor',
            ]);
        }, 3);
    }

    public function rechazarMovimiento(
        int $movimientoId,
        int $verificadoPorId,
        string $motivo
    ): MovimientoAjusteGarantia {
        return DB::transaction(function () use (
            $movimientoId,
            $verificadoPorId,
            $motivo
        ) {
            $movimientoBase = MovimientoAjusteGarantia::query()
                ->select(['id', 'cambio_equipo_id'])
                ->find($movimientoId);

            if (!$movimientoBase) {
                throw new ReglaNegocioException(
                    'El movimiento de ajuste no existe.'
                );
            }

            $cambio = CambioEquipo::query()
                ->lockForUpdate()
                ->find($movimientoBase->cambio_equipo_id);

            if (!$cambio) {
                throw new ReglaNegocioException(
                    'El cambio de equipo asociado ya no existe.'
                );
            }

            $this->validarCambioAjustable($cambio);

            $movimiento = MovimientoAjusteGarantia::query()
                ->lockForUpdate()
                ->find($movimientoId);

            if (!$movimiento) {
                throw new ReglaNegocioException(
                    'El movimiento de ajuste no existe.'
                );
            }

            if ($movimiento->estado !== 'PENDIENTE') {
                throw new ReglaNegocioException(
                    'Solo se pueden rechazar movimientos pendientes.'
                );
            }

            $usuario = $this->obtenerUsuarioActivoConPermiso(
                $verificadoPorId,
                'garantias.ajustes.verificar'
            );

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new ReglaNegocioException(
                    'Debe indicar el motivo del rechazo.'
                );
            }

            if (mb_strlen($motivo) > 255) {
                throw new ReglaNegocioException(
                    'El motivo del rechazo no puede superar los 255 caracteres.'
                );
            }

            $movimiento->estado = 'RECHAZADO';
            $movimiento->verificado_por_id = $usuario->id;
            $movimiento->fecha_verificacion = now();
            $movimiento->motivo_rechazo = $motivo;
            $movimiento->save();

            $this->recalcularEstadoAjuste($cambio);

            return $movimiento->fresh([
                'cambioEquipo',
                'metodoPago',
                'registradoPor',
                'verificadoPor',
            ]);
        }, 3);
    }

    public function obtenerResumen(
        int $cambioEquipoId
    ): array {
        $cambio = CambioEquipo::query()
            ->find($cambioEquipoId);

        if (!$cambio) {
            throw new ReglaNegocioException(
                'El cambio de equipo no existe.'
            );
        }

        if (
            $cambio->diferencia_snapshot === null
            || $cambio->tipo_ajuste === null
            || $cambio->estado_ajuste === null
        ) {
            return [
                'disponible' => false,
                'moneda' => $cambio->moneda_ajuste,
                'tipo_ajuste' => $cambio->tipo_ajuste,
                'tipo_movimiento' => null,
                'estado_ajuste' => $cambio->estado_ajuste,
                'objetivo' => '0.00',
                'verificado' => '0.00',
                'pendiente_verificacion' => '0.00',
                'rechazado' => '0.00',
                'comprometido' => '0.00',
                'saldo' => '0.00',
                'saldo_disponible' => '0.00',
            ];
        }

        $objetivo = $this->obtenerMontoObjetivo($cambio);

        $verificado = $this->sumarMovimientos(
            cambioEquipoId: $cambio->id,
            estados: ['VERIFICADO']
        );

        $pendiente = $this->sumarMovimientos(
            cambioEquipoId: $cambio->id,
            estados: ['PENDIENTE']
        );

        $rechazado = $this->sumarMovimientos(
            cambioEquipoId: $cambio->id,
            estados: ['RECHAZADO']
        );

        $comprometido = bcadd($verificado, $pendiente, 2);
        $saldo = bcsub($objetivo, $verificado, 2);
        $saldoDisponible = bcsub($objetivo, $comprometido, 2);

        if (bccomp($saldo, '0.00', 2) < 0) {
            $saldo = '0.00';
        }

        if (bccomp($saldoDisponible, '0.00', 2) < 0) {
            $saldoDisponible = '0.00';
        }

        $tipoMovimiento = match ($cambio->tipo_ajuste) {
            'COBRO_CLIENTE' => 'COBRO',
            'SALDO_FAVOR_CLIENTE' => 'DEVOLUCION',
            default => null,
        };

        return [
            'disponible' => true,
            'moneda' => $cambio->moneda_ajuste,
            'tipo_ajuste' => $cambio->tipo_ajuste,
            'tipo_movimiento' => $tipoMovimiento,
            'estado_ajuste' => $cambio->estado_ajuste,
            'objetivo' => $objetivo,
            'verificado' => $verificado,
            'pendiente_verificacion' => $pendiente,
            'rechazado' => $rechazado,
            'comprometido' => $comprometido,
            'saldo' => $saldo,
            'saldo_disponible' => $saldoDisponible,
        ];
    }

    private function validarCambioAjustable(
        CambioEquipo $cambio
    ): void {
        if (
            $cambio->valor_original_snapshot === null
            || $cambio->valor_reemplazo_snapshot === null
            || $cambio->diferencia_snapshot === null
            || $cambio->moneda_ajuste === null
            || $cambio->tipo_ajuste === null
            || $cambio->estado_ajuste === null
        ) {
            throw new ReglaNegocioException(
                'El cambio de equipo no posee un ajuste económico registrado.'
            );
        }

        $diferencia = bcadd(
            (string) $cambio->diferencia_snapshot,
            '0',
            2
        );

        if ($cambio->tipo_ajuste === 'SIN_DIFERENCIA') {
            throw new ReglaNegocioException(
                'El cambio de equipo no posee diferencia económica pendiente de liquidación.'
            );
        }

        if (!in_array(
            $cambio->tipo_ajuste,
            ['COBRO_CLIENTE', 'SALDO_FAVOR_CLIENTE'],
            true
        )) {
            throw new ReglaNegocioException(
                'El tipo de ajuste económico no es válido.'
            );
        }

        if (
            $cambio->tipo_ajuste === 'COBRO_CLIENTE'
            && bccomp($diferencia, '0.00', 2) <= 0
        ) {
            throw new ReglaNegocioException(
                'El ajuste de cobro al cliente debe poseer una diferencia positiva.'
            );
        }

        if (
            $cambio->tipo_ajuste === 'SALDO_FAVOR_CLIENTE'
            && bccomp($diferencia, '0.00', 2) >= 0
        ) {
            throw new ReglaNegocioException(
                'El saldo a favor del cliente debe poseer una diferencia negativa.'
            );
        }

        if (!in_array(
            $cambio->estado_ajuste,
            ['PENDIENTE', 'LIQUIDADO'],
            true
        )) {
            throw new ReglaNegocioException(
                'El estado del ajuste económico no es válido.'
            );
        }
    }

    private function obtenerMontoObjetivo(
        CambioEquipo $cambio
    ): string {
        $diferencia = bcadd(
            (string) $cambio->diferencia_snapshot,
            '0',
            2
        );

        if (bccomp($diferencia, '0.00', 2) < 0) {
            return bcsub('0.00', $diferencia, 2);
        }

        return $diferencia;
    }

    private function recalcularEstadoAjuste(
        CambioEquipo $cambio
    ): void {
        if ($cambio->tipo_ajuste === 'SIN_DIFERENCIA') {
            $cambio->estado_ajuste = 'LIQUIDADO';
            $cambio->save();
            return;
        }

        $objetivo = $this->obtenerMontoObjetivo($cambio);

        $verificado = $this->sumarMovimientos(
            cambioEquipoId: $cambio->id,
            estados: ['VERIFICADO']
        );

        if (bccomp($verificado, $objetivo, 2) === 1) {
            throw new ReglaNegocioException(
                'Los movimientos verificados superan el importe total del ajuste.'
            );
        }

        $nuevoEstado = bccomp($verificado, $objetivo, 2) === 0
            ? 'LIQUIDADO'
            : 'PENDIENTE';

        if ($cambio->estado_ajuste !== $nuevoEstado) {
            $cambio->estado_ajuste = $nuevoEstado;
            $cambio->save();
        }
    }

    private function sumarMovimientos(
        int $cambioEquipoId,
        array $estados
    ): string {
        $monto = MovimientoAjusteGarantia::query()
            ->where('cambio_equipo_id', $cambioEquipoId)
            ->whereIn('estado', $estados)
            ->sum('monto');

        return bcadd((string) $monto, '0', 2);
    }

    private function normalizarMonto(
        string|int|float $monto
    ): string {
        if (!is_numeric($monto)) {
            throw new ReglaNegocioException(
                'El monto del movimiento no es válido.'
            );
        }

        $monto = bcadd((string) $monto, '0', 2);

        if (bccomp($monto, '0.00', 2) <= 0) {
            throw new ReglaNegocioException(
                'El monto del movimiento debe ser mayor a cero.'
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
                'El ajuste económico ya no tiene saldo disponible.'
            );
        }

        if (bccomp($monto, $saldo, 2) === 1) {
            throw new ReglaNegocioException(
                "El movimiento supera el saldo disponible de Bs {$saldo}."
            );
        }
    }

    private function normalizarTexto(
        ?string $valor,
        ?int $maximo,
        ?string $mensajeMaximo
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        if ($maximo !== null && mb_strlen($valor) > $maximo) {
            throw new ReglaNegocioException(
                $mensajeMaximo
                ?? 'El texto supera la longitud permitida.'
            );
        }

        return $valor;
    }

    private function obtenerUsuarioActivoConPermiso(
        int $usuarioId,
        string $permiso
    ): User {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (!$usuario->tienePermiso($permiso)) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para gestionar el ajuste económico de garantía.'
            );
        }

        return $usuario;
    }
}
