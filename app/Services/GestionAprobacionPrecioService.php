<?php

namespace App\Services;

use App\Models\SolicitudAprobacionPrecio;
use InvalidArgumentException;

class GestionAprobacionPrecioService
{
    /**
     * Crear una solicitud de aprobación.
     */
    public function crearSolicitud(
        int $equipoId,
        float $precioPublicado,
        float $precioPropuesto,
        float $gananciaEstimada,
        ?int $usuarioSolicitanteId = null,
        ?string $motivo = null
    ): SolicitudAprobacionPrecio {

        if ($precioPublicado < 0) {
            throw new InvalidArgumentException(
                'El precio publicado no puede ser negativo.'
            );
        }

        if ($precioPropuesto < 0) {
            throw new InvalidArgumentException(
                'El precio propuesto no puede ser negativo.'
            );
        }

        if ($gananciaEstimada < 0) {
            throw new InvalidArgumentException(
                'La ganancia estimada no puede ser negativa.'
            );
        }


        $descuento =
            $precioPublicado
            - $precioPropuesto;


        return SolicitudAprobacionPrecio::create([

            'equipo_id' =>
                $equipoId,

            'usuario_solicitante_id' =>
                $usuarioSolicitanteId,

            'precio_publicado' =>
                $precioPublicado,

            'precio_propuesto' =>
                $precioPropuesto,

            'descuento_solicitado' =>
                $descuento,

            'ganancia_estimada' =>
                $gananciaEstimada,

            'motivo' =>
                $motivo,

            'estado' =>
                'PENDIENTE',

        ]);
    }


    /**
     * Aprobar solicitud.
     */
    public function aprobar(
        int $solicitudId,
        int $usuarioAprobadorId,
        ?string $observacion = null
    ): SolicitudAprobacionPrecio {

        $solicitud =
            $this->obtenerSolicitud(
                $solicitudId
            );


        $this->validarPendiente(
            $solicitud
        );


        $solicitud->update([

            'estado' =>
                'APROBADA',

            'usuario_aprobador_id' =>
                $usuarioAprobadorId,

            'fecha_aprobacion' =>
                now(),

            'observacion_aprobacion' =>
                $observacion,

        ]);


        return $solicitud->refresh();
    }


    /**
     * Rechazar solicitud.
     */
    public function rechazar(
        int $solicitudId,
        int $usuarioAprobadorId,
        string $observacion
    ): SolicitudAprobacionPrecio {

        $solicitud =
            $this->obtenerSolicitud(
                $solicitudId
            );


        $this->validarPendiente(
            $solicitud
        );


        $solicitud->update([

            'estado' =>
                'RECHAZADA',

            'usuario_aprobador_id' =>
                $usuarioAprobadorId,

            'fecha_aprobacion' =>
                now(),

            'observacion_aprobacion' =>
                $observacion,

        ]);


        return $solicitud->refresh();
    }


    /**
     * Obtener solicitudes pendientes.
     */
    public function pendientes()
    {
        return SolicitudAprobacionPrecio::query()
            ->where(
                'estado',
                'PENDIENTE'
            )
            ->orderBy(
                'created_at'
            )
            ->get();
    }


    /**
     * Obtener solicitud.
     */
    private function obtenerSolicitud(
        int $id
    ): SolicitudAprobacionPrecio {

        $solicitud =
            SolicitudAprobacionPrecio::find(
                $id
            );


        if (!$solicitud) {
            throw new InvalidArgumentException(
                'La solicitud de aprobación no existe.'
            );
        }


        return $solicitud;
    }


    /**
     * Validar que siga pendiente.
     */
    private function validarPendiente(
        SolicitudAprobacionPrecio $solicitud
    ): void {

        if (
            $solicitud->estado !== 'PENDIENTE'
        ) {

            throw new InvalidArgumentException(
                'La solicitud ya fue procesada.'
            );
        }
    }
}