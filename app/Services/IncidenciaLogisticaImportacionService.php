<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\EnvioImportacionUnidad;
use App\Models\IncidenciaLogisticaImportacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IncidenciaLogisticaImportacionService
{
    /**
     * Abre formalmente una incidencia logística
     * asociada a una unidad de un envío.
     *
     * La unidad debe encontrarse previamente
     * marcada como FALTANTE o INCIDENCIA
     * durante el proceso de recepción.
     */
    public function abrirIncidencia(
        int $usuarioId,
        int $envioImportacionUnidadId,
        array $datos
    ): IncidenciaLogisticaImportacion {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioImportacionUnidadId,
                $datos
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $validator =
                    Validator::make(
                        $datos,
                        [
                            'tipo' => [
                                'required',
                                'string',
                                'max:60',
                            ],

                            'descripcion' => [
                                'required',
                                'string',
                                'max:5000',
                            ],
                        ]
                    );

                if ($validator->fails()) {
                    throw new ValidationException(
                        $validator
                    );
                }

                $validados =
                    $validator->validated();

                $detalle =
                    EnvioImportacionUnidad::query()
                    ->lockForUpdate()
                    ->find(
                        $envioImportacionUnidadId
                    );

                if (!$detalle) {
                    throw new ReglaNegocioException(
                        'El detalle de la unidad dentro del envío no existe.'
                    );
                }

                /*
                 * Solo tiene sentido abrir una incidencia
                 * formal cuando la recepción previamente
                 * detectó un problema.
                 */
                if (
                    !in_array(
                        $detalle->estado_recepcion,
                        [
                            EnvioImportacionUnidad::ESTADO_FALTANTE,
                            EnvioImportacionUnidad::ESTADO_INCIDENCIA,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden abrirse incidencias para unidades faltantes o con incidencia de recepción.'
                    );
                }

                /*
                 * Permitimos múltiples incidencias
                 * históricas sobre una misma unidad,
                 * pero solamente una incidencia activa
                 * al mismo tiempo.
                 */
                $incidenciaActiva =
                    IncidenciaLogisticaImportacion::query()
                    ->where(
                        'envio_importacion_unidad_id',
                        $detalle->id
                    )
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
                    'envio_importacion_unidad_id' =>
                    $detalle->id,

                    'tipo' =>
                    trim(
                        $validados['tipo']
                    ),

                    'estado' =>
                    IncidenciaLogisticaImportacion::ESTADO_ABIERTA,

                    'descripcion' =>
                    trim(
                        $validados['descripcion']
                    ),

                    'fecha_apertura' =>
                    now(),

                    'abierta_por_id' =>
                    $usuario->id,

                    'resultado' =>
                    null,

                    'detalle_resolucion' =>
                    null,

                    'fecha_resolucion' =>
                    null,

                    'resuelta_por_id' =>
                    null,
                ]);
            },
            3
        );
    }


    /**
     * Cambia una incidencia:
     *
     * ABIERTA -> EN_GESTION
     */
    public function iniciarGestion(
        int $usuarioId,
        int $incidenciaId
    ): IncidenciaLogisticaImportacion {
        return DB::transaction(
            function () use (
                $usuarioId,
                $incidenciaId
            ) {
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );

                $incidencia =
                    IncidenciaLogisticaImportacion::query()
                    ->lockForUpdate()
                    ->find(
                        $incidenciaId
                    );

                if (!$incidencia) {
                    throw new ReglaNegocioException(
                        'La incidencia logística no existe.'
                    );
                }

                if (
                    !$incidencia->estaAbierta()
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden ponerse en gestión las incidencias que se encuentran ABIERTAS.'
                    );
                }

                $incidencia->update([
                    'estado' =>
                    IncidenciaLogisticaImportacion::ESTADO_EN_GESTION,
                ]);

                return $incidencia->fresh();
            },
            3
        );
    }


    /**
     * Cierra administrativamente una incidencia.
     *
     * Importante:
     * Resolver la incidencia NO modifica todavía
     * el estado de recepción de la unidad.
     *
     * La consecuencia logística dependerá del
     * resultado real de la resolución.
     */
    public function resolverIncidencia(
        int $usuarioId,
        int $incidenciaId,
        array $datos
    ): IncidenciaLogisticaImportacion {
        return DB::transaction(
            function () use (
                $usuarioId,
                $incidenciaId,
                $datos
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $validator =
                    Validator::make(
                        $datos,
                        [
                            'resultado' => [
                                'required',
                                'string',
                                'max:60',
                            ],

                            'detalle_resolucion' => [
                                'required',
                                'string',
                                'max:5000',
                            ],
                        ]
                    );

                if ($validator->fails()) {
                    throw new ValidationException(
                        $validator
                    );
                }

                $validados =
                    $validator->validated();

                $incidencia =
                    IncidenciaLogisticaImportacion::query()
                    ->lockForUpdate()
                    ->find(
                        $incidenciaId
                    );

                if (!$incidencia) {
                    throw new ReglaNegocioException(
                        'La incidencia logística no existe.'
                    );
                }

                if (
                    !$incidencia->estaEnGestion()
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden resolverse incidencias que se encuentren EN_GESTION.'
                    );
                }

                $incidencia->update([
                    'estado' =>
                    IncidenciaLogisticaImportacion::ESTADO_RESUELTA,

                    'resultado' =>
                    trim(
                        $validados['resultado']
                    ),

                    'detalle_resolucion' =>
                    trim(
                        $validados['detalle_resolucion']
                    ),

                    'fecha_resolucion' =>
                    now(),

                    'resuelta_por_id' =>
                    $usuario->id,
                ]);

                return $incidencia->fresh();
            },
            3
        );
    }


    /**
     * Verifica que el usuario esté activo y tenga
     * permiso para gestionar importaciones.
     */
    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {
        $usuario =
            User::query()
            ->where(
                'activo',
                true
            )
            ->find(
                $usuarioId
            );

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (
            !$usuario->tienePermiso(
                'importacion.gestionar'
            )
        ) {
            throw new ReglaNegocioException(
                'El usuario no tiene permiso para gestionar incidencias de importación.'
            );
        }

        return $usuario;
    }
}
