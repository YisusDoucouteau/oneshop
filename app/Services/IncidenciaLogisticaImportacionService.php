<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\EnvioImportacionUnidad;
use App\Models\IncidenciaLogisticaImportacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IncidenciaLogisticaImportacionService
{
    /**
     * Abre formalmente una incidencia logística asociada a una unidad
     * que ya fue marcada como FALTANTE o INCIDENCIA durante recepción.
     */
    public function abrirIncidencia(
        int $usuarioId,
        int $envioImportacionUnidadId,
        array $datos
    ): IncidenciaLogisticaImportacion {
        return DB::transaction(function () use (
            $usuarioId,
            $envioImportacionUnidadId,
            $datos
        ) {
            $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

            $validator = Validator::make(
                $datos,
                [
                    'tipo' => ['required', 'string', 'max:60'],
                    'descripcion' => ['required', 'string', 'max:5000'],
                ]
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validados = $validator->validated();

            $detalle = EnvioImportacionUnidad::query()
                ->with('envioImportacion')
                ->lockForUpdate()
                ->find($envioImportacionUnidadId);

            if (!$detalle) {
                throw new ReglaNegocioException(
                    'El detalle de la unidad dentro del envío no existe.'
                );
            }

            $this->exigirOperacionEnDestino($usuario, $detalle);

            if (!in_array(
                $detalle->estado_recepcion,
                [
                    EnvioImportacionUnidad::ESTADO_FALTANTE,
                    EnvioImportacionUnidad::ESTADO_INCIDENCIA,
                ],
                true
            )) {
                throw new ReglaNegocioException(
                    'Solo pueden abrirse incidencias para unidades faltantes o con incidencia de recepción.'
                );
            }

            $incidenciaActiva = IncidenciaLogisticaImportacion::query()
                ->where('envio_importacion_unidad_id', $detalle->id)
                ->whereIn(
                    'estado',
                    [
                        IncidenciaLogisticaImportacion::ESTADO_ABIERTA,
                        IncidenciaLogisticaImportacion::ESTADO_EN_GESTION,
                    ]
                )
                ->lockForUpdate()
                ->first();

            if ($incidenciaActiva) {
                throw new ReglaNegocioException(
                    'La unidad ya tiene una incidencia logística activa.'
                );
            }

            return IncidenciaLogisticaImportacion::create([
                'envio_importacion_unidad_id' => $detalle->id,
                'tipo' => trim($validados['tipo']),
                'estado' => IncidenciaLogisticaImportacion::ESTADO_ABIERTA,
                'descripcion' => trim($validados['descripcion']),
                'fecha_apertura' => now(),
                'abierta_por_id' => $usuario->id,
                'resultado' => null,
                'detalle_resolucion' => null,
                'fecha_resolucion' => null,
                'resuelta_por_id' => null,
            ]);
        }, 3);
    }

    /**
     * ABIERTA -> EN_GESTION.
     */
    public function iniciarGestion(
        int $usuarioId,
        int $incidenciaId
    ): IncidenciaLogisticaImportacion {
        return DB::transaction(function () use ($usuarioId, $incidenciaId) {
            $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

            $incidencia = IncidenciaLogisticaImportacion::query()
                ->with('envioImportacionUnidad.envioImportacion')
                ->lockForUpdate()
                ->find($incidenciaId);

            if (!$incidencia) {
                throw new ReglaNegocioException(
                    'La incidencia logística no existe.'
                );
            }

            $this->exigirOperacionEnDestino(
                $usuario,
                $incidencia->envioImportacionUnidad
            );

            if (!$incidencia->estaAbierta()) {
                throw new ReglaNegocioException(
                    'Solo pueden ponerse en gestión las incidencias que se encuentran ABIERTAS.'
                );
            }

            $incidencia->update([
                'estado' => IncidenciaLogisticaImportacion::ESTADO_EN_GESTION,
            ]);

            return $incidencia->fresh();
        }, 3);
    }

    /**
     * EN_GESTION -> RESUELTA.
     *
     * Resolver el expediente administrativo no cambia por sí mismo el
     * resultado histórico de la recepción de la unidad.
     */
    public function resolverIncidencia(
        int $usuarioId,
        int $incidenciaId,
        array $datos
    ): IncidenciaLogisticaImportacion {
        return DB::transaction(function () use (
            $usuarioId,
            $incidenciaId,
            $datos
        ) {
            $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

            $validator = Validator::make(
                $datos,
                [
                    'resultado' => ['required', 'string', 'max:60'],
                    'detalle_resolucion' => ['required', 'string', 'max:5000'],
                ]
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validados = $validator->validated();

            $incidencia = IncidenciaLogisticaImportacion::query()
                ->with('envioImportacionUnidad.envioImportacion')
                ->lockForUpdate()
                ->find($incidenciaId);

            if (!$incidencia) {
                throw new ReglaNegocioException(
                    'La incidencia logística no existe.'
                );
            }

            $this->exigirOperacionEnDestino(
                $usuario,
                $incidencia->envioImportacionUnidad
            );

            if (!$incidencia->estaEnGestion()) {
                throw new ReglaNegocioException(
                    'Solo pueden resolverse incidencias que se encuentren EN_GESTION.'
                );
            }

            $incidencia->update([
                'estado' => IncidenciaLogisticaImportacion::ESTADO_RESUELTA,
                'resultado' => trim($validados['resultado']),
                'detalle_resolucion' => trim($validados['detalle_resolucion']),
                'fecha_resolucion' => now(),
                'resuelta_por_id' => $usuario->id,
            ]);

            return $incidencia->fresh();
        }, 3);
    }

    /**
     * Cierra automáticamente la incidencia activa cuando una unidad que había
     * sido declarada FALTANTE aparece físicamente en Oruro.
     *
     * Es una resolución del sistema motivada por el evento logístico real,
     * por eso puede pasar ABIERTA/EN_GESTION -> RESUELTA en una sola operación.
     */
    public function resolverPorRecepcionTardia(
        int $usuarioId,
        int $envioImportacionUnidadId,
        ?string $detalleResolucion = null
    ): ?IncidenciaLogisticaImportacion {
        return DB::transaction(function () use (
            $usuarioId,
            $envioImportacionUnidadId,
            $detalleResolucion
        ) {
            $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

            $detalle = EnvioImportacionUnidad::query()
                ->with('envioImportacion')
                ->lockForUpdate()
                ->find($envioImportacionUnidadId);

            if (!$detalle) {
                throw new ReglaNegocioException(
                    'El detalle de la unidad dentro del envío no existe.'
                );
            }

            $this->exigirOperacionEnDestino($usuario, $detalle);

            $incidencia = IncidenciaLogisticaImportacion::query()
                ->where('envio_importacion_unidad_id', $detalle->id)
                ->whereIn(
                    'estado',
                    [
                        IncidenciaLogisticaImportacion::ESTADO_ABIERTA,
                        IncidenciaLogisticaImportacion::ESTADO_EN_GESTION,
                    ]
                )
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (!$incidencia) {
                return null;
            }

            $incidencia->update([
                'estado' => IncidenciaLogisticaImportacion::ESTADO_RESUELTA,
                'resultado' => 'RECIBIDA_TARDIAMENTE',
                'detalle_resolucion' =>
                    trim((string) ($detalleResolucion
                        ?: 'La unidad faltante fue recibida posteriormente en Oruro.')),
                'fecha_resolucion' => now(),
                'resuelta_por_id' => $usuario->id,
            ]);

            return $incidencia->fresh();
        }, 3);
    }

    private function exigirOperacionEnDestino(
        User $usuario,
        EnvioImportacionUnidad $detalle
    ): void {
        $envio = $detalle->relationLoaded('envioImportacion')
            ? $detalle->envioImportacion
            : $detalle->envioImportacion()->first();

        if (!$envio) {
            throw new ReglaNegocioException(
                'No se encontró el envío asociado a la incidencia.'
            );
        }

        if ($usuario->puedeOperarEnAlmacen((int) $envio->almacen_destino_id)) {
            return;
        }

        $almacen = Almacen::query()->find($envio->almacen_destino_id);
        $nombre = $almacen?->nombre ?? 'el almacén de destino';

        throw new ReglaNegocioException(
            "El usuario no está autorizado para gestionar incidencias de recepción. Sede requerida: {$nombre}."
        );
    }

    private function obtenerUsuarioAutorizado(int $usuarioId): User
    {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (!$usuario->tienePermiso('importacion.gestionar')) {
            throw new ReglaNegocioException(
                'El usuario no tiene permiso para gestionar incidencias de importación.'
            );
        }

        return $usuario;
    }
}
