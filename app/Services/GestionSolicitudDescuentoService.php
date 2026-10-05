<?php

namespace App\Services;

use App\Models\SolicitudDescuento;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GestionSolicitudDescuentoService
{
    public function aprobar(
        int $solicitudId,
        int $usuarioId,
        ?string $motivo = null,
        string $medio = 'SISTEMA'
    ): SolicitudDescuento {
        return DB::transaction(function () use (
            $solicitudId,
            $usuarioId,
            $motivo,
            $medio
        ) {
            $solicitud = SolicitudDescuento::query()
                ->lockForUpdate()
                ->find($solicitudId);

            if (!$solicitud) {
                throw new InvalidArgumentException(
                    'La solicitud de autorización no existe.'
                );
            }

            $this->validarPendiente($solicitud);

            $solicitud->update([
                'estado' => 'APROBADA',
                'respondido_por_id' => $usuarioId,
                'fecha_respuesta' => now(),
                'motivo_respuesta' => $motivo,
                'medio_respuesta' => $medio,
            ]);

            return $solicitud->refresh();
        });
    }

    public function rechazar(
        int $solicitudId,
        int $usuarioId,
        string $motivo,
        string $medio = 'SISTEMA'
    ): SolicitudDescuento {
        return DB::transaction(function () use (
            $solicitudId,
            $usuarioId,
            $motivo,
            $medio
        ) {
            $solicitud = SolicitudDescuento::query()
                ->lockForUpdate()
                ->find($solicitudId);

            if (!$solicitud) {
                throw new InvalidArgumentException(
                    'La solicitud de autorización no existe.'
                );
            }

            $this->validarPendiente($solicitud);

            $solicitud->update([
                'estado' => 'RECHAZADA',
                'respondido_por_id' => $usuarioId,
                'fecha_respuesta' => now(),
                'motivo_respuesta' => $motivo,
                'medio_respuesta' => $medio,
            ]);

            return $solicitud->refresh();
        });
    }

    private function validarPendiente(
        SolicitudDescuento $solicitud
    ): void {
        if ($solicitud->estado !== 'PENDIENTE') {
            throw new InvalidArgumentException(
                'La solicitud ya fue procesada.'
            );
        }
    }
}
