<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\EnvioImportacion;
use App\Models\EnvioImportacionUnidad;
use App\Models\UnidadAdquirida;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EnvioImportacionService
{
    /**
     * Crea un envío en estado BORRADOR.
     *
     * Por ahora este flujo corresponde específicamente
     * al traslado de preinventario:
     *
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
                $usuario =
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

                if ($origen->id === $destino->id) {
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
     * Incluye una unidad física en un envío todavía
     * no despachado.
     *
     * Reglas:
     * - el envío debe estar en BORRADOR;
     * - la unidad debe estar LISTA_ENVIO;
     * - la unidad debe estar físicamente en el
     *   almacén de origen del envío;
     * - no puede estar incorporada al inventario;
     * - no puede pertenecer a otro envío.
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
                 * Bloqueamos cualquier asignación previa
                 * de la misma unidad.
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
                    ->with('unidadesEnvio')
                    ->find($envioId);


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


                if (
                    $unidad->estado !==
                    UnidadAdquirida::ESTADO_LISTA_ENVIO
                ) {
                    throw new ReglaNegocioException(
                        "La unidad {$unidad->id} no está lista para envío."
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


            return $envio;
        },
        3
    );
}
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
                    ->with('unidadesEnvio.unidadAdquirida')
                    ->find($envioId);


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


            return $envio->fresh();

        },
        3
    );
}
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
                    ->find($envioId);


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


            if (!$detalle->estaPendiente()) {
                throw new ReglaNegocioException(
                    'La unidad ya fue procesada durante la recepción.'
                );
            }


            $unidad =
                UnidadAdquirida::query()
                    ->lockForUpdate()
                    ->find($unidadId);


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
                 * No se registra fecha de recepción
                 * porque físicamente la unidad no llegó.
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
             * La UnidadAdquirida continúa ENVIADA.
             *
             * No debe moverse a Oruro porque precisamente
             * todavía no se confirmó su recepción física.
             */
            return $detalle->fresh();

        },
        3
    );
}

    /**
     * Retira una unidad de un envío mientras todavía
     * se encuentra en BORRADOR.
     *
     * La UnidadAdquirida no cambia de estado:
     * continúa LISTA_ENVIO y puede incluirse luego
     * en otro despacho.
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
                        'No pueden retirarse unidades después de despachar el envío.'
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
                    ->find($envioId);


            if (!$envio) {
                throw new ReglaNegocioException(
                    'El envío no existe.'
                );
            }


            if (
                !$envio->estaDespachado()
            ) {
                throw new ReglaNegocioException(
                    'Solo pueden recibirse envíos en estado DESPACHADO.'
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


            if (
                !$detalle->estaPendiente()
            ) {
                throw new ReglaNegocioException(
                    'La unidad ya fue procesada en recepción.'
                );
            }


            $unidad =
                UnidadAdquirida::query()
                    ->lockForUpdate()
                    ->find($unidadId);


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


            $detalle->update([

                'estado_recepcion' =>
                    EnvioImportacionUnidad::ESTADO_RECIBIDA,

                'fecha_recepcion' =>
                    now(),

                'recibido_por_id' =>
                    $usuario->id,

                'observacion_recepcion' =>
                    $observacion,

            ]);


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
                    ->with('unidadesEnvio')
                    ->find($envioId);


            if (!$envio) {
                throw new ReglaNegocioException(
                    'El envío no existe.'
                );
            }


            if (
                !$envio->estaDespachado()
            ) {
                throw new ReglaNegocioException(
                    'Solo pueden cerrarse recepciones de envíos despachados.'
                );
            }


            if (
                $envio->unidadesEnvio->isEmpty()
            ) {
                throw new ReglaNegocioException(
                    'No se puede cerrar un envío sin unidades.'
                );
            }


            foreach (
                $envio->unidadesEnvio as $detalle
            ) {

                if (
                    !$detalle->estaResueltaEnRecepcion()
                ) {
                    throw new ReglaNegocioException(
                        'Existen unidades pendientes de recepción.'
                    );
                }
            }


            $todasRecibidas =
                $envio->unidadesEnvio
                    ->every(
                        fn ($detalle) =>
                            $detalle->estado_recepcion ===
                            EnvioImportacionUnidad::ESTADO_RECIBIDA
                    );


            $nuevoEstado =
                $todasRecibidas
                    ? EnvioImportacion::ESTADO_RECIBIDO
                    : EnvioImportacion::ESTADO_RECIBIDO_PARCIAL;


            $envio->update([

    'estado' =>
        $nuevoEstado,

    'recibido_por_id' =>
        $todasRecibidas
            ? $usuario->id
            : null,

    'fecha_recepcion' =>
        $todasRecibidas
            ? now()
            : null,

]);

            return $envio->fresh();
        },
        3
    );
}
}