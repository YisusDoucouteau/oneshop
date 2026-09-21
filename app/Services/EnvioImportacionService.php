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
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {
    }

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
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $validator =
                    Validator::make(
                        $datos,
                        [
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

                            'cantidad_cargadores' => [
                                'nullable',
                                'integer',
                                'min:0',
                            ],

                            'cantidad_accesorios' => [
                                'nullable',
                                'integer',
                                'min:0',
                            ],

                            'detalle_accesorios' => [
                                'nullable',
                                'string',
                                'max:1000',
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


                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $origen->id,
                    'crear y preparar envíos desde el almacén de origen'
                );

                $codigo = $this->generarCodigoEnvio();

                return EnvioImportacion::create([
                    'codigo' => $codigo,

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

                    'cantidad_cargadores' =>
                        $validados['cantidad_cargadores']
                        ?? 0,

                    'cantidad_accesorios' =>
                        $validados['cantidad_accesorios']
                        ?? 0,

                    'detalle_accesorios' =>
                        $validados['detalle_accesorios']
                        ?? null,

                    'observacion' =>
                        $validados['observacion']
                        ?? null,
                ]);
            },
            3
        );
    }


    private function generarCodigoEnvio(): string
    {
        $anio = now()->format('Y');
        $prefijo = "ENV-{$anio}-";

        $ultimoCodigo = EnvioImportacion::query()
            ->where('codigo', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('codigo')
            ->value('codigo');

        $correlativo = 1;

        if ($ultimoCodigo) {
            $correlativo = ((int) substr($ultimoCodigo, -3)) + 1;
        }

        return $prefijo . str_pad((string) $correlativo, 3, '0', STR_PAD_LEFT);
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
                        'El envío de importación no existe.'
                    );
                }

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_origen_id,
                    'operar el despacho desde el almacén de origen'
                );

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
                        ->whereHas(
                            'envioImportacion',
                            fn ($query) =>
                                $query->where(
                                    'estado',
                                    '!=',
                                    EnvioImportacion::ESTADO_CANCELADO
                                )
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

                    /*
                     * Por defecto heredamos si la unidad tiene cargador
                     * disponible, pero el usuario puede decidir que ese
                     * cargador no viaje (o agregar uno después) mientras
                     * el envío siga en BORRADOR.
                     */
                    'incluye_cargador' =>
                        (bool) ($unidad->tiene_cargador ?? false),

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
                        'El envío de importación no existe.'
                    );
                }

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_origen_id,
                    'operar el despacho desde el almacén de origen'
                );

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
     * Define si una unidad concreta viajará con cargador.
     *
     * Esto es independiente de que la unidad haya sido probada con un
     * cargador durante la preparación. Una máquina puede viajar sin su
     * cargador y seguir estando técnicamente lista para envío.
     */
    public function actualizarCargadorUnidad(
        int $usuarioId,
        int $envioId,
        int $unidadId,
        bool $incluyeCargador
    ): EnvioImportacionUnidad {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId,
                $unidadId,
                $incluyeCargador
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $envio = EnvioImportacion::query()
                    ->lockForUpdate()
                    ->find($envioId);

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío de importación no existe.'
                    );
                }

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_origen_id,
                    'operar el despacho desde el almacén de origen'
                );

                if (!$envio->estaEnBorrador()) {
                    throw new ReglaNegocioException(
                        'El cargador asociado a una unidad solo puede modificarse mientras el envío está en BORRADOR.'
                    );
                }

                $detalle = EnvioImportacionUnidad::query()
                    ->where('envio_importacion_id', $envio->id)
                    ->where('unidad_adquirida_id', $unidadId)
                    ->lockForUpdate()
                    ->first();

                if (!$detalle) {
                    throw ValidationException::withMessages([
                        'unidad_id' => 'La unidad no pertenece a este envío.',
                    ]);
                }

                $detalle->update([
                    'incluye_cargador' => $incluyeCargador,
                ]);

                return $detalle->fresh('unidadAdquirida');
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

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_origen_id,
                    'operar el despacho desde el almacén de origen'
                );

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
     * Devuelve un envío PREPARADO a BORRADOR antes del despacho.
     *
     * Las unidades continúan en LISTA_ENVIO y permanecen asociadas
     * al envío para que Hugo pueda corregir cajas, accesorios o
     * composición antes de prepararlo nuevamente.
     */
    public function reabrirPreparado(
        int $usuarioId,
        int $envioId,
        string $motivo
    ): EnvioImportacion {
        return DB::transaction(
            function () use ($usuarioId, $envioId, $motivo) {
                $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

                $motivo = trim($motivo);

                if ($motivo === '') {
                    throw ValidationException::withMessages([
                        'motivo' => 'Debe indicar el motivo de la reapertura.',
                    ]);
                }

                $envio = EnvioImportacion::query()
                    ->lockForUpdate()
                    ->find($envioId);

                if (!$envio) {
                    throw new ReglaNegocioException('El envío no existe.');
                }

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_origen_id,
                    'operar el despacho desde el almacén de origen'
                );

                if (!$envio->estaPreparado()) {
                    throw new ReglaNegocioException(
                        'Solo pueden reabrirse envíos que se encuentran PREPARADOS y todavía no fueron despachados.'
                    );
                }

                $anterior = [
                    'estado' => $envio->estado,
                    'preparado_por_id' => $envio->preparado_por_id,
                    'fecha_preparacion' => optional($envio->fecha_preparacion)?->toISOString(),
                ];

                $envio->update([
                    'estado' => EnvioImportacion::ESTADO_BORRADOR,
                    'preparado_por_id' => null,
                    'fecha_preparacion' => null,
                ]);

                $this->auditoriaService->registrar(
                    $usuario->id,
                    'REABRIR_ENVIO_IMPORTACION',
                    'EnvioImportacion',
                    $envio->id,
                    $anterior,
                    [
                        'estado' => EnvioImportacion::ESTADO_BORRADOR,
                        'motivo' => $motivo,
                    ]
                );

                return $envio->fresh();
            },
            3
        );
    }


    /**
     * Cancela un envío que aún no fue despachado.
     *
     * Las relaciones con sus unidades se conservan como historial,
     * pero dejan de bloquear a esas unidades para futuros envíos.
     */
    public function cancelarEnvio(
        int $usuarioId,
        int $envioId,
        string $motivo
    ): EnvioImportacion {
        return DB::transaction(
            function () use ($usuarioId, $envioId, $motivo) {
                $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

                $motivo = trim($motivo);

                if ($motivo === '') {
                    throw ValidationException::withMessages([
                        'motivo' => 'Debe indicar el motivo de la cancelación.',
                    ]);
                }

                $envio = EnvioImportacion::query()
                    ->lockForUpdate()
                    ->with('unidadesEnvio.unidadAdquirida')
                    ->find($envioId);

                if (!$envio) {
                    throw new ReglaNegocioException('El envío no existe.');
                }

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_origen_id,
                    'operar el despacho desde el almacén de origen'
                );

                if (!in_array(
                    $envio->estado,
                    [
                        EnvioImportacion::ESTADO_BORRADOR,
                        EnvioImportacion::ESTADO_PREPARADO,
                    ],
                    true
                )) {
                    throw new ReglaNegocioException(
                        'Solo pueden cancelarse envíos que todavía no fueron despachados.'
                    );
                }

                $unidades = $envio->unidadesEnvio
                    ->map(fn ($detalle) => [
                        'id' => $detalle->unidad_adquirida_id,
                        'codigo' => $detalle->unidadAdquirida?->codigo_trazabilidad,
                    ])
                    ->values()
                    ->all();

                $estadoAnterior = $envio->estado;

                $envio->update([
                    'estado' => EnvioImportacion::ESTADO_CANCELADO,
                ]);

                $this->auditoriaService->registrar(
                    $usuario->id,
                    'CANCELAR_ENVIO_IMPORTACION',
                    'EnvioImportacion',
                    $envio->id,
                    ['estado' => $estadoAnterior],
                    [
                        'estado' => EnvioImportacion::ESTADO_CANCELADO,
                        'motivo' => $motivo,
                        'unidades_liberadas' => $unidades,
                    ]
                );

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

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_origen_id,
                    'operar el despacho desde el almacén de origen'
                );

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
     * Registra el conteo físico general realizado en el almacén destino.
     *
     * Los valores enviados en Cochabamba se conservan como referencia y
     * estos campos representan lo que realmente se encontró al abrir el envío.
     */
    public function registrarVerificacionRecepcion(
        int $usuarioId,
        int $envioId,
        array $datos
    ): EnvioImportacion {
        return DB::transaction(
            function () use ($usuarioId, $envioId, $datos) {
                $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

                $validator = Validator::make(
                    $datos,
                    [
                        'cantidad_bultos_recibidos' => [
                            'required',
                            'integer',
                            'min:0',
                        ],
                        'cantidad_cargadores_adicionales_recibidos' => [
                            'required',
                            'integer',
                            'min:0',
                        ],
                        'cantidad_accesorios_recibidos' => [
                            'required',
                            'integer',
                            'min:0',
                        ],
                        'observacion_recepcion_general' => [
                            'nullable',
                            'string',
                            'max:2000',
                        ],
                    ]
                );

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $validados = $validator->validated();

                $envio = EnvioImportacion::query()
                    ->lockForUpdate()
                    ->find($envioId);

                if (!$envio) {
                    throw new ReglaNegocioException('El envío no existe.');
                }

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_destino_id,
                    'verificar físicamente el envío en el almacén de destino'
                );

                if (!in_array(
                    $envio->estado,
                    [
                        EnvioImportacion::ESTADO_DESPACHADO,
                        EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                    ],
                    true
                )) {
                    throw new ReglaNegocioException(
                        'El conteo de recepción solo puede registrarse para envíos despachados o con recepción parcial.'
                    );
                }

                $hayDiferencia =
                    (int) $validados['cantidad_bultos_recibidos'] !==
                        (int) $envio->cantidad_bultos
                    ||
                    (int) $validados['cantidad_cargadores_adicionales_recibidos'] !==
                        (int) ($envio->cantidad_cargadores ?? 0)
                    ||
                    (int) $validados['cantidad_accesorios_recibidos'] !==
                        (int) ($envio->cantidad_accesorios ?? 0);

                $observacion = isset($validados['observacion_recepcion_general'])
                    ? trim((string) $validados['observacion_recepcion_general'])
                    : '';

                if ($hayDiferencia && $observacion === '') {
                    throw ValidationException::withMessages([
                        'observacion_recepcion_general' =>
                            'Debe registrar una observación cuando el conteo recibido no coincide con lo enviado.',
                    ]);
                }

                $anterior = [
                    'cantidad_bultos_recibidos' => $envio->cantidad_bultos_recibidos,
                    'cantidad_cargadores_adicionales_recibidos' =>
                        $envio->cantidad_cargadores_adicionales_recibidos,
                    'cantidad_accesorios_recibidos' => $envio->cantidad_accesorios_recibidos,
                    'observacion_recepcion_general' => $envio->observacion_recepcion_general,
                ];

                $envio->update([
                    'cantidad_bultos_recibidos' =>
                        (int) $validados['cantidad_bultos_recibidos'],
                    'cantidad_cargadores_adicionales_recibidos' =>
                        (int) $validados['cantidad_cargadores_adicionales_recibidos'],
                    'cantidad_accesorios_recibidos' =>
                        (int) $validados['cantidad_accesorios_recibidos'],
                    'observacion_recepcion_general' =>
                        $observacion !== '' ? $observacion : null,
                    'verificado_recepcion_por_id' => $usuario->id,
                    'fecha_verificacion_recepcion' => now(),
                ]);

                $this->auditoriaService->registrar(
                    $usuario->id,
                    'VERIFICAR_RECEPCION_ENVIO_IMPORTACION',
                    'EnvioImportacion',
                    $envio->id,
                    $anterior,
                    [
                        'cantidad_bultos_recibidos' =>
                            (int) $validados['cantidad_bultos_recibidos'],
                        'cantidad_cargadores_adicionales_recibidos' =>
                            (int) $validados['cantidad_cargadores_adicionales_recibidos'],
                        'cantidad_accesorios_recibidos' =>
                            (int) $validados['cantidad_accesorios_recibidos'],
                        'observacion_recepcion_general' =>
                            $observacion !== '' ? $observacion : null,
                    ]
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
        ?string $observacion = null,
        ?bool $cargadorRecibido = null
    ): EnvioImportacionUnidad {
        return DB::transaction(
            function () use (
                $usuarioId,
                $envioId,
                $unidadId,
                $observacion,
                $cargadorRecibido
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

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_destino_id,
                    'registrar la recepción en el almacén de destino'
                );

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

                $estadoRecepcion =
                    EnvioImportacionUnidad::ESTADO_RECIBIDA;

                if (
                    $detalle->incluye_cargador
                    && $cargadorRecibido === false
                ) {
                    if ($observacionFinal === null || $observacionFinal === '') {
                        throw ValidationException::withMessages([
                            'observacion' =>
                                'Debe describir la incidencia cuando falta el cargador declarado con el equipo.',
                        ]);
                    }

                    $estadoRecepcion =
                        EnvioImportacionUnidad::ESTADO_INCIDENCIA;
                }

                $detalle->update([
                    'estado_recepcion' => $estadoRecepcion,

                    'cargador_recibido' =>
                        $detalle->incluye_cargador
                            ? $cargadorRecibido
                            : null,

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

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_destino_id,
                    'registrar la recepción en el almacén de destino'
                );

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

                    'cargador_recibido' =>
                        null,

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
    string $observacion,
    ?bool $cargadorRecibido = null
): EnvioImportacionUnidad {
    return DB::transaction(
        function () use (
            $usuarioId,
            $envioId,
            $unidadId,
            $observacion,
            $cargadorRecibido
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

            $this->exigirOperacionEnAlmacen(
                $usuario,
                $envio->almacen_destino_id,
                'registrar la recepción en el almacén de destino'
            );

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

                'cargador_recibido' =>
                    $detalle->incluye_cargador
                        ? $cargadorRecibido
                        : null,

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
     * Reglas:
     * - Debe existir una verificación física general del destino.
     * - No puede quedar ninguna unidad pendiente de recepción.
     * - RECIBIDO: unidades y conteos físicos coinciden completamente.
     * - RECIBIDO_PARCIAL: existen faltantes, incidencias o diferencias
     *   entre lo enviado y lo recibido.
     *
     * Una recepción con diferencias puede volver a cerrarse posteriormente
     * si los faltantes llegan o el conteo físico se corrige.
     */
    public function cerrarRecepcion(
        int $usuarioId,
        int $envioId
    ): EnvioImportacion {
        return DB::transaction(
            function () use ($usuarioId, $envioId) {
                $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

                $envio = EnvioImportacion::query()
                    ->lockForUpdate()
                    ->with('unidadesEnvio')
                    ->find($envioId);

                if (!$envio) {
                    throw new ReglaNegocioException(
                        'El envío no existe.'
                    );
                }

                $this->exigirOperacionEnAlmacen(
                    $usuario,
                    $envio->almacen_destino_id,
                    'registrar la recepción en el almacén de destino'
                );

                if (!in_array(
                    $envio->estado,
                    [
                        EnvioImportacion::ESTADO_DESPACHADO,
                        EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                    ],
                    true
                )) {
                    throw new ReglaNegocioException(
                        'Solo pueden cerrarse envíos despachados o con recepción con diferencias.'
                    );
                }

                if ($envio->unidadesEnvio->isEmpty()) {
                    throw new ReglaNegocioException(
                        'No se puede cerrar un envío sin unidades.'
                    );
                }

                if (!$envio->recepcionGeneralVerificada()) {
                    throw new ReglaNegocioException(
                        'Debe registrar la verificación física general antes de cerrar la recepción.'
                    );
                }

                foreach ($envio->unidadesEnvio as $detalle) {
                    if (!$detalle->estaResueltaEnRecepcion()) {
                        throw new ReglaNegocioException(
                            'Existen unidades pendientes de recepción.'
                        );
                    }
                }

                $todasUnidadesRecibidas = $envio->unidadesEnvio
                    ->every(
                        fn ($detalle) =>
                            $detalle->estado_recepcion ===
                            EnvioImportacionUnidad::ESTADO_RECIBIDA
                    );

                $hayDiferenciasConteo =
                    $envio->tieneDiferenciasConteoRecepcion();

                $hayDiferenciasCargadoresAsociados =
                    $envio->unidadesEnvio
                        ->contains(
                            fn ($detalle) =>
                                (bool) $detalle->incluye_cargador
                                && $detalle->estado_recepcion ===
                                    EnvioImportacionUnidad::ESTADO_RECIBIDA
                                && $detalle->cargador_recibido !== true
                        );

                $recepcionCompleta =
                    $todasUnidadesRecibidas
                    && !$hayDiferenciasConteo
                    && !$hayDiferenciasCargadoresAsociados;

                $nuevoEstado = $recepcionCompleta
                    ? EnvioImportacion::ESTADO_RECIBIDO
                    : EnvioImportacion::ESTADO_RECIBIDO_PARCIAL;

                $estadoAnterior = $envio->estado;

                $unidadesConDiferencia = $envio->unidadesEnvio
                    ->filter(
                        fn ($detalle) =>
                            $detalle->estado_recepcion !==
                                EnvioImportacionUnidad::ESTADO_RECIBIDA
                            || (
                                (bool) $detalle->incluye_cargador
                                && $detalle->cargador_recibido !== true
                            )
                    )
                    ->map(
                        fn ($detalle) => [
                            'unidad_adquirida_id' =>
                                $detalle->unidad_adquirida_id,
                            'estado_recepcion' =>
                                $detalle->estado_recepcion,
                            'incluye_cargador' =>
                                (bool) $detalle->incluye_cargador,
                            'cargador_recibido' =>
                                $detalle->cargador_recibido,
                        ]
                    )
                    ->values()
                    ->all();

                $envio->update([
                    'estado' => $nuevoEstado,

                    /*
                     * Estos campos continúan representando la recepción
                     * completa del traslado. Cuando existen diferencias,
                     * el cierre queda documentado mediante auditoría y el
                     * envío permanece en RECIBIDO_PARCIAL.
                     */
                    'recibido_por_id' =>
                        $recepcionCompleta
                            ? $usuario->id
                            : null,

                    'fecha_recepcion' =>
                        $recepcionCompleta
                            ? now()
                            : null,
                ]);

                $this->auditoriaService->registrar(
                    $usuario->id,
                    'CERRAR_RECEPCION_ENVIO_IMPORTACION',
                    'EnvioImportacion',
                    $envio->id,
                    [
                        'estado' => $estadoAnterior,
                    ],
                    [
                        'estado' => $nuevoEstado,
                        'recepcion_completa' => $recepcionCompleta,
                        'diferencias_conteo' => $hayDiferenciasConteo,
                        'diferencias_cargadores_asociados' =>
                            $hayDiferenciasCargadoresAsociados,
                        'unidades_con_diferencia' => $unidadesConDiferencia,
                        'conteo_enviado' => [
                            'cajas' => (int) $envio->cantidad_bultos,
                            'cargadores_adicionales' =>
                                (int) ($envio->cantidad_cargadores ?? 0),
                            'accesorios' =>
                                (int) ($envio->cantidad_accesorios ?? 0),
                        ],
                        'conteo_recibido' => [
                            'cajas' =>
                                (int) $envio->cantidad_bultos_recibidos,
                            'cargadores_adicionales' =>
                                (int) $envio->cantidad_cargadores_adicionales_recibidos,
                            'accesorios' =>
                                (int) $envio->cantidad_accesorios_recibidos,
                        ],
                    ]
                );

                $this->registrarEventoLogisticoLotesDelEnvio(
                    $envio,
                    'RECEPCION_ORURO',
                    $usuario,
                    $recepcionCompleta
                        ? 'Recepción completa de unidades en Oruro.'
                        : 'Recepción en Oruro cerrada con diferencias registradas.'
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

    private function exigirOperacionEnAlmacen(
        User $usuario,
        int $almacenId,
        string $operacion
    ): void {
        if ($usuario->puedeOperarEnAlmacen($almacenId)) {
            return;
        }

        $almacen = Almacen::query()->find($almacenId);
        $nombre = $almacen?->nombre ?? 'el almacén indicado';

        throw new ReglaNegocioException(
            "El usuario no está autorizado para {$operacion}. Sede requerida: {$nombre}."
        );
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