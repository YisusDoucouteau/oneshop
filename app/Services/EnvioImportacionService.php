<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\EnvioImportacion;
use App\Models\EnvioImportacionUnidad;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Models\EventoLogisticoLote;
use App\Models\TipoEventoLogistico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EnvioImportacionService
{
    /**
     * Crea un envío de preinventario
     * Cochabamba -> Oruro.
     */
    public function crearBorrador(
        int $usuarioId,
        array $datos
    ): EnvioImportacion {
        return DB::transaction(
            function () use (
                $usuarioId,
                $datos
            ) {
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );

                $validator =
                    Validator::make(
                        $datos,
                        [
                            'codigo' => [
                                'required',
                                'string',
                                'max:60',
                                'unique:envios_importacion,codigo',
                            ],

                            'transportista' => [
                                'nullable',
                                'string',
                                'max:150',
                            ],

                            'numero_guia' => [
                                'nullable',
                                'string',
                                'max:120',
                            ],

                            'cantidad_bultos' => [
                                'nullable',
                                'integer',
                                'min:1',
                            ],

                            'observacion' => [
                                'nullable',
                                'string',
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

                $origen =
                    Almacen::query()
                        ->where(
                            'codigo',
                            'COCHABAMBA'
                        )
                        ->where(
                            'activo',
                            true
                        )
                        ->first();

                if (!$origen) {
                    throw new ReglaNegocioException(
                        'No se encuentra configurado el almacén de Cochabamba.'
                    );
                }

                $destino =
                    Almacen::query()
                        ->where(
                            'codigo',
                            'ORURO_PRINCIPAL'
                        )
                        ->where(
                            'activo',
                            true
                        )
                        ->first();

                if (!$destino) {
                    throw new ReglaNegocioException(
                        'No se encuentra configurado el almacén principal de Oruro.'
                    );
                }

                if (
                    $origen->id ===
                    $destino->id
                ) {
                    throw new ReglaNegocioException(
                        'El almacén de origen y destino no pueden ser el mismo.'
                    );
                }

                return EnvioImportacion::create([
                    'codigo' =>
                        trim(
                            $validados['codigo']
                        ),

                    'almacen_origen_id' =>
                        $origen->id,

                    'almacen_destino_id' =>
                        $destino->id,

                    'estado' =>
                        EnvioImportacion::ESTADO_BORRADOR,

                    /*
                     * Un BORRADOR todavía no fue
                     * preparado, despachado ni recibido.
                     */
                    'preparado_por_id' =>
                        null,

                    'despachado_por_id' =>
                        null,

                    'recibido_por_id' =>
                        null,

                    'fecha_preparacion' =>
                        null,

                    'fecha_despacho' =>
                        null,

                    'fecha_recepcion' =>
                        null,

                    'transportista' =>
                        isset(
                            $validados['transportista']
                        )
                            ? trim(
                                $validados['transportista']
                            )
                            : null,

                    'numero_guia' =>
                        isset(
                            $validados['numero_guia']
                        )
                            ? trim(
                                $validados['numero_guia']
                            )
                            : null,

                    'cantidad_bultos' =>
                        $validados['cantidad_bultos']
                        ?? 1,

                    'observacion' =>
                        $validados['observacion']
                        ?? null,
                ]);
            },
            3
        );
    }


    /**
     * Agrega una unidad física a un envío
     * mientras se encuentra en BORRADOR.
     */
    public function agregarUnidad(
        int $usuarioId,
        int $envioId,
        int $unidadId
    ): EnvioImportacionUnidad {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId,
                $unidadId
            ) {
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );

                $envio =
                    EnvioImportacion::query()
                        ->lockForUpdate()
                        ->find(
                            $envioId
                        );

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío de importación no existe.'
                    );
                }

                if (
                    !$envio->puedeModificarse()
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden modificarse los envíos que se encuentran en estado BORRADOR.'
                    );
                }

                $unidad =
                    UnidadAdquirida::query()
                        ->lockForUpdate()
                        ->find(
                            $unidadId
                        );

                if (!$unidad) {
                    throw new ReglaNegocioException(
                        'La unidad adquirida no existe.'
                    );
                }

                if (
                    $unidad
                        ->estaIncorporadaInventario()
                ) {
                    throw new ReglaNegocioException(
                        'La unidad ya fue incorporada formalmente al inventario.'
                    );
                }

                if (
                    $unidad->estado !==
                    UnidadAdquirida::ESTADO_LISTA_ENVIO
                ) {
                    throw ValidationException::withMessages([
                        'unidad_id' =>
                            'La unidad todavía no se encuentra lista para envío.',
                    ]);
                }

                if (
                    $unidad->almacen_actual_id !==
                    $envio->almacen_origen_id
                ) {
                    throw ValidationException::withMessages([
                        'unidad_id' =>
                            'La unidad no se encuentra físicamente en el almacén de origen del envío.',
                    ]);
                }

                /*
                 * Una unidad física solamente puede
                 * pertenecer a un envío.
                 */
                $asignacionExistente =
                    EnvioImportacionUnidad::query()
                        ->where(
                            'unidad_adquirida_id',
                            $unidad->id
                        )
                        ->lockForUpdate()
                        ->first();

                if ($asignacionExistente) {
                    if (
                        $asignacionExistente
                            ->envio_importacion_id ===
                        $envio->id
                    ) {
                        throw ValidationException::withMessages([
                            'unidad_id' =>
                                'La unidad ya se encuentra incluida en este envío.',
                        ]);
                    }

                    throw ValidationException::withMessages([
                        'unidad_id' =>
                            'La unidad ya se encuentra incluida en otro envío.',
                    ]);
                }

                return EnvioImportacionUnidad::create([
                    'envio_importacion_id' =>
                        $envio->id,

                    'unidad_adquirida_id' =>
                        $unidad->id,

                    'estado_recepcion' =>
                        EnvioImportacionUnidad::ESTADO_PENDIENTE,

                    'fecha_recepcion' =>
                        null,

                    'recibido_por_id' =>
                        null,

                    'observacion_recepcion' =>
                        null,
                ]);
            },
            3
        );
    }


    /**
     * Retira una unidad del envío.
     *
     * Solo puede realizarse mientras
     * el envío esté en BORRADOR.
     */
    public function quitarUnidad(
        int $usuarioId,
        int $envioId,
        int $unidadId
    ): void {
        DB::transaction(
            function () use (
                $usuarioId,
                $envioId,
                $unidadId
            ) {
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );

                $envio =
                    EnvioImportacion::query()
                        ->lockForUpdate()
                        ->find(
                            $envioId
                        );

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío de importación no existe.'
                    );
                }

                if (
                    !$envio->puedeModificarse()
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden retirarse unidades de envíos en estado BORRADOR.'
                    );
                }

                $unidad =
                    UnidadAdquirida::query()
                        ->lockForUpdate()
                        ->find(
                            $unidadId
                        );

                if (!$unidad) {
                    throw new ReglaNegocioException(
                        'La unidad adquirida no existe.'
                    );
                }

                $detalleEnvio =
                    EnvioImportacionUnidad::query()
                        ->where(
                            'envio_importacion_id',
                            $envio->id
                        )
                        ->where(
                            'unidad_adquirida_id',
                            $unidad->id
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$detalleEnvio) {
                    throw ValidationException::withMessages([
                        'unidad_id' =>
                            'La unidad no pertenece a este envío.',
                    ]);
                }

                $detalleEnvio->delete();
            },
            3
        );
    }


    /**
     * Confirma que el envío se encuentra
     * preparado para su despacho.
     */
    public function marcarPreparado(
        int $usuarioId,
        int $envioId
    ): EnvioImportacion {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $envio =
                    EnvioImportacion::query()
                        ->lockForUpdate()
                        ->with(
                            'unidadesEnvio.unidadAdquirida'
                        )
                        ->find(
                            $envioId
                        );

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío no existe.'
                    );
                }

                if (
                    !$envio->estaEnBorrador()
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden prepararse envíos en estado BORRADOR.'
                    );
                }

                if (
                    $envio->unidadesEnvio->isEmpty()
                ) {
                    throw new ReglaNegocioException(
                        'No se puede preparar un envío sin unidades.'
                    );
                }

                foreach (
                    $envio->unidadesEnvio
                    as $detalle
                ) {
                    $unidad =
                        $detalle->unidadAdquirida;

                    if (!$unidad) {
                        throw new ReglaNegocioException(
                            'Existe una unidad inválida dentro del envío.'
                        );
                    }

                    if (
                        $unidad->estado !==
                        UnidadAdquirida::ESTADO_LISTA_ENVIO
                    ) {
                        throw new ReglaNegocioException(
                            "La unidad {$unidad->id} no está lista para envío."
                        );
                    }

                    if (
                        $unidad->almacen_actual_id !==
                        $envio->almacen_origen_id
                    ) {
                        throw new ReglaNegocioException(
                            "La unidad {$unidad->id} ya no se encuentra en el almacén de origen."
                        );
                    }
                }

                $envio->update([
                    'estado' =>
                        EnvioImportacion::ESTADO_PREPARADO,

                    'preparado_por_id' =>
                        $usuario->id,

                    'fecha_preparacion' =>
                        now(),
                ]);

                return $envio->fresh();
            },
            3
        );
    }


    /**
     * Despacha físicamente las unidades.
     *
     * Las unidades pasan:
     * LISTA_ENVIO -> ENVIADA.
     */
    public function marcarDespachado(
        int $usuarioId,
        int $envioId,
        array $datos = []
    ): EnvioImportacion {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId,
                $datos
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $envio =
                    EnvioImportacion::query()
                        ->lockForUpdate()
                        ->with(
                            'unidadesEnvio.unidadAdquirida'
                        )
                        ->find(
                            $envioId
                        );

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío no existe.'
                    );
                }

                if (
                    !$envio->estaPreparado()
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden despacharse envíos en estado PREPARADO.'
                    );
                }

                if (
                    $envio->unidadesEnvio->isEmpty()
                ) {
                    throw new ReglaNegocioException(
                        'No se puede despachar un envío sin unidades.'
                    );
                }

                foreach (
                    $envio->unidadesEnvio
                    as $detalle
                ) {
                    $unidad =
                        $detalle->unidadAdquirida;

                    if (!$unidad) {
                        throw new ReglaNegocioException(
                            'Existe una unidad inválida dentro del envío.'
                        );
                    }

                    if (
                        $unidad->estado !==
                        UnidadAdquirida::ESTADO_LISTA_ENVIO
                    ) {
                        throw new ReglaNegocioException(
                            "La unidad {$unidad->id} no está lista para despacho."
                        );
                    }

                    if (
                        $unidad->almacen_actual_id !==
                        $envio->almacen_origen_id
                    ) {
                        throw new ReglaNegocioException(
                            "La unidad {$unidad->id} ya no se encuentra en el almacén de origen."
                        );
                    }
                }

                $envio->update([
                    'estado' =>
                        EnvioImportacion::ESTADO_DESPACHADO,

                    'despachado_por_id' =>
                        $usuario->id,

                    'fecha_despacho' =>
                        now(),

                    'transportista' =>
                        $datos['transportista']
                        ?? $envio->transportista,

                    'numero_guia' =>
                        $datos['numero_guia']
                        ?? $envio->numero_guia,
                ]);

                foreach (
    $envio->unidadesEnvio
    as $detalle
) {
    $detalle
        ->unidadAdquirida()
        ->update([
            'estado' =>
                UnidadAdquirida::ESTADO_ENVIADA,
        ]);
}


/*
 * Registro histórico del despacho.
 *
 * Se registra a nivel de lote porque
 * representa la trazabilidad de la importación.
 */
$this->registrarEventoLogisticoLotesDelEnvio(
    $envio,
    'DESPACHO_ORURO',
    $usuario,
    'Despacho de unidades desde Cochabamba hacia Oruro.'
);

                return $envio->fresh();
            },
            3
        );
    }


    /**
     * Registra la recepción física de una unidad.
     *
     * Soporta:
     *
     * PENDIENTE -> RECIBIDA
     *
     * y también:
     *
     * FALTANTE -> RECIBIDA
     *
     * cuando una unidad llega posteriormente.
     */
    public function recibirUnidad(
        int $usuarioId,
        int $envioId,
        int $unidadId,
        ?string $observacion = null
    ): EnvioImportacionUnidad {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId,
                $unidadId,
                $observacion
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $envio =
                    EnvioImportacion::query()
                        ->lockForUpdate()
                        ->find(
                            $envioId
                        );

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío no existe.'
                    );
                }

                /*
                 * Una recepción normal ocurre cuando
                 * el envío está DESPACHADO.
                 *
                 * Una recepción tardía puede ocurrir
                 * cuando el envío ya fue cerrado
                 * parcialmente.
                 */
                if (
                    !in_array(
                        $envio->estado,
                        [
                            EnvioImportacion::ESTADO_DESPACHADO,
                            EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden recibirse unidades de envíos DESPACHADOS o con RECEPCIÓN PARCIAL.'
                    );
                }

                $detalle =
                    EnvioImportacionUnidad::query()
                        ->where(
                            'envio_importacion_id',
                            $envio->id
                        )
                        ->where(
                            'unidad_adquirida_id',
                            $unidadId
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$detalle) {
                    throw new ReglaNegocioException(
                        'La unidad no pertenece a este envío.'
                    );
                }

                /*
                 * Una unidad FALTANTE puede aparecer
                 * posteriormente.
                 */
                $esRecepcionTardia =
                    $detalle->estaFaltante();

                if (
                    !$detalle->estaPendiente() &&
                    !$esRecepcionTardia
                ) {
                    throw new ReglaNegocioException(
                        'La unidad ya fue procesada en recepción y no puede registrarse nuevamente.'
                    );
                }

                $unidad =
                    UnidadAdquirida::query()
                        ->lockForUpdate()
                        ->find(
                            $unidadId
                        );

                if (!$unidad) {
                    throw new ReglaNegocioException(
                        'La unidad adquirida no existe.'
                    );
                }

                if (
                    $unidad->estado !==
                    UnidadAdquirida::ESTADO_ENVIADA
                ) {
                    throw new ReglaNegocioException(
                        'La unidad no se encuentra enviada.'
                    );
                }

                /*
                 * Conservamos el historial descriptivo
                 * cuando previamente estuvo FALTANTE.
                 */
                $observacionFinal =
                    $observacion !== null
                        ? trim(
                            $observacion
                        )
                        : null;

                if ($esRecepcionTardia) {
                    $observacionAnterior =
                        $detalle->observacion_recepcion !== null &&
                        trim(
                            $detalle->observacion_recepcion
                        ) !== ''
                            ? trim(
                                $detalle->observacion_recepcion
                            ) . "\n"
                            : '';

                    $notaTardia =
                        $observacion !== null &&
                        trim(
                            $observacion
                        ) !== ''
                            ? trim(
                                $observacion
                            )
                            : 'La unidad fue recibida posteriormente.';

                    $observacionFinal =
                        $observacionAnterior .
                        '[RECEPCIÓN TARDÍA] ' .
                        $notaTardia;
                }

                $detalle->update([
                    'estado_recepcion' =>
                        EnvioImportacionUnidad::ESTADO_RECIBIDA,

                    'fecha_recepcion' =>
                        now(),

                    'recibido_por_id' =>
                        $usuario->id,

                    'observacion_recepcion' =>
                        $observacionFinal,
                ]);

                /*
                 * Solo ahora que físicamente llegó
                 * se mueve la unidad al almacén Oruro.
                 */
                $unidad->update([
                    'almacen_actual_id' =>
                        $envio->almacen_destino_id,

                    'estado' =>
                        UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
                ]);

                return $detalle->fresh();
            },
            3
        );
    }


    /**
     * Marca una unidad como faltante durante
     * la recepción del envío.
     *
     * Importante:
     * La unidad NO se mueve a Oruro.
     * Continúa en estado ENVIADA hasta que
     * se resuelva la situación.
     */
    public function marcarUnidadFaltante(
        int $usuarioId,
        int $envioId,
        int $unidadId,
        string $observacion
    ): EnvioImportacionUnidad {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId,
                $unidadId,
                $observacion
            ) {
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );

                $validator =
                    Validator::make(
                        [
                            'observacion' =>
                                $observacion,
                        ],
                        [
                            'observacion' => [
                                'required',
                                'string',
                                'max:1000',
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

                $envio =
                    EnvioImportacion::query()
                        ->lockForUpdate()
                        ->find(
                            $envioId
                        );

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío no existe.'
                    );
                }

                if (
                    !in_array(
                        $envio->estado,
                        [
                            EnvioImportacion::ESTADO_DESPACHADO,
                            EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden registrarse unidades faltantes en envíos despachados o con recepción parcial.'
                    );
                }

                $detalle =
                    EnvioImportacionUnidad::query()
                        ->where(
                            'envio_importacion_id',
                            $envio->id
                        )
                        ->where(
                            'unidad_adquirida_id',
                            $unidadId
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$detalle) {
                    throw new ReglaNegocioException(
                        'La unidad no pertenece a este envío.'
                    );
                }

                /*
                 * Solo una unidad todavía PENDIENTE
                 * puede declararse FALTANTE.
                 */
                if (
                    !$detalle->estaPendiente()
                ) {
                    throw new ReglaNegocioException(
                        'La unidad ya fue procesada durante la recepción.'
                    );
                }

                $unidad =
                    UnidadAdquirida::query()
                        ->lockForUpdate()
                        ->find(
                            $unidadId
                        );

                if (!$unidad) {
                    throw new ReglaNegocioException(
                        'La unidad adquirida no existe.'
                    );
                }

                if (
                    $unidad->estado !==
                    UnidadAdquirida::ESTADO_ENVIADA
                ) {
                    throw new ReglaNegocioException(
                        'Solo puede marcarse como faltante una unidad que se encuentre enviada.'
                    );
                }

                $detalle->update([
                    'estado_recepcion' =>
                        EnvioImportacionUnidad::ESTADO_FALTANTE,

                    /*
                     * No existe recepción física.
                     */
                    'fecha_recepcion' =>
                        null,

                    'recibido_por_id' =>
                        null,

                    'observacion_recepcion' =>
                        trim(
                            $validados['observacion']
                        ),
                ]);

                /*
                 * La UnidadAdquirida permanece:
                 *
                 * estado = ENVIADA
                 * almacen_actual_id = origen
                 *
                 * porque todavía no existe evidencia
                 * de recepción física en Oruro.
                 */
                return $detalle->fresh();
            },
            3
        );
    }
    /**
 * Registra una incidencia detectada durante
 * la recepción física de una unidad.
 *
 * A diferencia de FALTANTE, la unidad sí
 * llegó al almacén destino, pero presenta
 * alguna anomalía.
 */
public function registrarIncidenciaRecepcion(
    int $usuarioId,
    int $envioId,
    int $unidadId,
    string $observacion
): EnvioImportacionUnidad {
    return DB::transaction(
        function () use (
            $usuarioId,
            $envioId,
            $unidadId,
            $observacion
        ) {
            $usuario =
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );

            $validator =
                Validator::make(
                    [
                        'observacion' =>
                            $observacion,
                    ],
                    [
                        'observacion' => [
                            'required',
                            'string',
                            'max:1000',
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

            $envio =
                EnvioImportacion::query()
                    ->lockForUpdate()
                    ->find(
                        $envioId
                    );

            if (!$envio) {
                throw new ReglaNegocioException(
                    'El envío no existe.'
                );
            }

            if (
                !in_array(
                    $envio->estado,
                    [
                        EnvioImportacion::ESTADO_DESPACHADO,
                        EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                    ],
                    true
                )
            ) {
                throw new ReglaNegocioException(
                    'Solo pueden registrarse incidencias en envíos despachados o con recepción parcial.'
                );
            }

            $detalle =
                EnvioImportacionUnidad::query()
                    ->where(
                        'envio_importacion_id',
                        $envio->id
                    )
                    ->where(
                        'unidad_adquirida_id',
                        $unidadId
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$detalle) {
                throw new ReglaNegocioException(
                    'La unidad no pertenece a este envío.'
                );
            }

            /*
             * Por ahora la incidencia corresponde
             * a una anomalía detectada en la primera
             * recepción física.
             */
            if (
                !$detalle->estaPendiente()
            ) {
                throw new ReglaNegocioException(
                    'La unidad ya fue procesada durante la recepción.'
                );
            }

            $unidad =
                UnidadAdquirida::query()
                    ->lockForUpdate()
                    ->find(
                        $unidadId
                    );

            if (!$unidad) {
                throw new ReglaNegocioException(
                    'La unidad adquirida no existe.'
                );
            }

            if (
                $unidad->estado !==
                UnidadAdquirida::ESTADO_ENVIADA
            ) {
                throw new ReglaNegocioException(
                    'Solo puede registrarse una incidencia de recepción para una unidad enviada.'
                );
            }

            /*
             * La unidad sí llegó físicamente.
             * Por eso registramos fecha y receptor.
             */
            $detalle->update([
                'estado_recepcion' =>
                    EnvioImportacionUnidad::ESTADO_INCIDENCIA,

                'fecha_recepcion' =>
                    now(),

                'recibido_por_id' =>
                    $usuario->id,

                'observacion_recepcion' =>
                    trim(
                        $validados['observacion']
                    ),
            ]);

            /*
             * Aunque exista una incidencia,
             * físicamente la unidad ya se encuentra
             * en Oruro.
             */
            $unidad->update([
                'almacen_actual_id' =>
                    $envio->almacen_destino_id,

                'estado' =>
                    UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
            ]);

            return $detalle->fresh();
        },
        3
    );
}

    /**
     * Cierra la recepción del envío.
     *
     * Todas RECIBIDAS:
     *      -> RECIBIDO
     *
     * Existe FALTANTE o INCIDENCIA:
     *      -> RECIBIDO_PARCIAL
     *
     * Existe PENDIENTE:
     *      -> no permite cerrar
     */
    public function cerrarRecepcion(
        int $usuarioId,
        int $envioId
    ): EnvioImportacion {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $envio =
                    EnvioImportacion::query()
                        ->lockForUpdate()
                        ->with(
                            'unidadesEnvio'
                        )
                        ->find(
                            $envioId
                        );

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío no existe.'
                    );
                }

                /*
                 * También puede volver a cerrarse una
                 * recepción parcial después de que una
                 * unidad faltante llegue posteriormente.
                 */
                if (
                    !in_array(
                        $envio->estado,
                        [
                            EnvioImportacion::ESTADO_DESPACHADO,
                            EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'Solo pueden cerrarse envíos despachados o con recepción parcial.'
                    );
                }

                if (
                    $envio->unidadesEnvio->isEmpty()
                ) {
                    throw new ReglaNegocioException(
                        'No se puede cerrar un envío sin unidades.'
                    );
                }

                /*
                 * No permitimos cerrar mientras alguna
                 * unidad todavía siga PENDIENTE.
                 */
                foreach (
                    $envio->unidadesEnvio
                    as $detalle
                ) {
                    if (
                        !$detalle
                            ->estaResueltaEnRecepcion()
                    ) {
                        throw new ReglaNegocioException(
                            'Existen unidades pendientes de recepción.'
                        );
                    }
                }

                /*
                 * Solo se considera RECIBIDO cuando
                 * todas las unidades llegaron físicamente.
                 */
                $todasRecibidas =
                    $envio->unidadesEnvio
                        ->every(
                            fn ($detalle) =>
                                $detalle
                                    ->estado_recepcion ===
                                EnvioImportacionUnidad::ESTADO_RECIBIDA
                        );

                $nuevoEstado =
                    $todasRecibidas
                        ? EnvioImportacion::ESTADO_RECIBIDO
                        : EnvioImportacion::ESTADO_RECIBIDO_PARCIAL;

                $envio->update([
                    'estado' =>
                        $nuevoEstado,

                    /*
                     * Estos campos generales significan
                     * recepción completa del envío.
                     *
                     * En una recepción parcial quedan NULL.
                     */
                    'recibido_por_id' =>
                        $todasRecibidas
                            ? $usuario->id
                            : null,

                    'fecha_recepcion' =>
                        $todasRecibidas
                            ? now()
                            : null,
                ]);
                $this->registrarEventoLogisticoLotesDelEnvio(
                    $envio,
                    'RECEPCION_ORURO',
                    $usuario,
                    'Recepción de unidades en Oruro.'
                );
                return $envio->fresh();
            },
            3
        );
    }
   private function registrarEventoLogisticoLotesDelEnvio(
    EnvioImportacion $envio,
    string $codigoEvento,
    User $usuario,
    string $descripcion
): void {

    $tipoEvento =
        TipoEventoLogistico::where(
            'codigo',
            $codigoEvento
        )->first();


    if (!$tipoEvento) {
        throw new ReglaNegocioException(
            "No existe el tipo de evento logístico {$codigoEvento}."
        );
    }


    $lotes =
        $envio
            ->unidadesEnvio()
            ->with(
                'unidadAdquirida.detalleLote.lote'
            )
            ->get()
            ->map(function ($detalle) {

                return optional(
                    optional(
                        $detalle->unidadAdquirida
                    )->detalleLote
                )->lote;

            })
            ->filter()
            ->unique(function ($lote) {
                return $lote->id;
            });


    foreach ($lotes as $lote) {
$existe =
    EventoLogisticoLote::where(
        'lote_id',
        $lote->id
    )
    ->where(
        'tipo_evento_logistico_id',
        $tipoEvento->id
    )
    ->exists();


if ($existe) {
    continue;
}
        EventoLogisticoLote::create([

            'lote_id' =>
                $lote->id,

            'tipo_evento_logistico_id' =>
                $tipoEvento->id,

            'usuario_id' =>
                $usuario->id,

            'fecha_evento' =>
                now(),

            'ubicacion' =>
                $codigoEvento === 'DESPACHO_ORURO'
                    ? 'Cochabamba'
                    : 'Oruro',

            'descripcion' =>
                $descripcion,

        ]);
    }
}

    /**
     * Obtiene un usuario habilitado para
     * gestionar importaciones.
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
                'El usuario no tiene permiso para gestionar importaciones.'
            );
        }

        return $usuario;
    }
        
}