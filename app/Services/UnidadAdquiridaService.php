<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\DetalleLote;
use App\Models\EnvioImportacion;
use App\Models\EventoLogisticoLote;
use App\Models\Lote;
use App\Models\RevisionTecnicaUnidadAdquirida;
use App\Models\TipoEventoLogistico;
use App\Models\UnidadAdquirida;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UnidadAdquiridaService
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {
    }

    /**
     * Registra una llegada parcial de unidades físicas
     * correspondientes a una línea de compra.
     *
     * Ejemplo:
     * - Hugo compró 10 Dell.
     * - Hoy llegaron 3 al depósito de Cochabamba.
     * - Se crean 3 unidades adquiridas.
     *
     * La información económica se hereda siempre de la línea de compra.
     * La recepción física no puede redefinir precio, moneda ni tipo de cambio.
     */
    public function registrarLlegadaCochabamba(
        int $usuarioId,
        int $detalleLoteId,
        int $cantidad,
        ?string $fechaLlegada = null,
        ?string $observacion = null,
        array $datosFisicos = []
    ): Collection {

    return DB::transaction(
        function () use (
            $usuarioId,
            $detalleLoteId,
            $cantidad,
            $fechaLlegada,
            $observacion,
            $datosFisicos
        ) {

            /*
            |--------------------------------------------------------------------------
            | Usuario autorizado
            |--------------------------------------------------------------------------
            */

            $usuario =
                $this->obtenerUsuarioAutorizado(
                    $usuarioId
                );


            /*
            |--------------------------------------------------------------------------
            | Cantidad
            |--------------------------------------------------------------------------
            */

            if ($cantidad < 1) {

                throw ValidationException::withMessages([
                    'cantidad' =>
                        'La cantidad recibida en Cochabamba debe ser al menos 1.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Línea de compra
            |--------------------------------------------------------------------------
            */

            $detalle =
                DetalleLote::query()
                    ->with([
                        'lote.proveedor',
                        'producto',
                    ])
                    ->lockForUpdate()
                    ->find(
                        $detalleLoteId
                    );


            if (!$detalle) {

                throw new ReglaNegocioException(
                    'La línea de compra indicada no existe.'
                );
            }


            if (
                in_array(
                    $detalle->lote->estado,
                    [
                        'CANCELADO',
                        'CERRADO',
                    ],
                    true
                )
            ) {

                throw new ReglaNegocioException(
                    'No se pueden registrar llegadas en un lote cerrado o cancelado.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Compra de origen
            |--------------------------------------------------------------------------
            |
            | La recepción solo constata lo que llegó físicamente. Los datos
            | económicos pertenecen a la línea de compra y se copian sin
            | recalcular ni crear un nuevo tipo de cambio.
            |
            |--------------------------------------------------------------------------
            */

            $datosCompra = [
                'precio_compra' => $detalle->costo_unitario_origen,
                'moneda_id' => $detalle->moneda_id,
                'tipo_cambio_compra_id' => $detalle->tipo_cambio_compra_id,
                'precio_compra_bob' => $detalle->costo_unitario_bob,
                'fecha_compra' => $detalle->lote->fecha_compra?->toDateString(),
                'referencia_compra' => $detalle->lote->referencia_compra,
                'proveedor_compra' => $detalle->lote->proveedor?->nombre,
            ];


            /*
            |--------------------------------------------------------------------------
            | Unidades físicas ya registradas
            |--------------------------------------------------------------------------
            |
            | IMPORTANTE:
            |
            | Aquí NO usamos cantidad_recibida.
            |
            | cantidad_recibida corresponde a la recepción/incorporación
            | posterior en Oruro.
            |
            | Para saber cuántas máquinas ya llegaron físicamente a
            | Cochabamba contamos las UnidadAdquirida activas.
            |--------------------------------------------------------------------------
            */

            $registradas =
                UnidadAdquirida::query()
                    ->where(
                        'detalle_lote_id',
                        $detalle->id
                    )
                    ->where(
                        'estado',
                        '!=',
                        UnidadAdquirida::ESTADO_ANULADA
                    )
                    ->count();


            $pendientes =
                $detalle->cantidad_esperada
                - $registradas;


            if ($pendientes <= 0) {

                throw new ReglaNegocioException(
                    'Todas las unidades esperadas de esta línea ya fueron registradas físicamente.'
                );
            }


            if ($cantidad > $pendientes) {

                throw ValidationException::withMessages([
                    'cantidad' =>
                        "Solo quedan {$pendientes} unidad(es) pendientes para este producto.",
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Depósito Cochabamba
            |--------------------------------------------------------------------------
            */

            $almacenCochabamba =
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


            if (!$almacenCochabamba) {

                throw new ReglaNegocioException(
                    'No se encuentra disponible el depósito de Cochabamba.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Fecha real de llegada
            |--------------------------------------------------------------------------
            */

            $fecha =
                $fechaLlegada
                    ? Carbon::parse(
                        $fechaLlegada
                    )
                    : now();


            /*
            |--------------------------------------------------------------------------
            | Correlativo temporal de trazabilidad
            |--------------------------------------------------------------------------
            |
            | Formato:
            |
            | OS-YYMMDD-NNNN
            |
            | Este código identifica a la unidad antes de que Dani
            | le asigne su código interno comercial en Oruro.
            |--------------------------------------------------------------------------
            */

            $fechaCorrelativo =
                $fecha
                    ->copy()
                    ->startOfDay()
                    ->toDateString();


            DB::table(
                'correlativos_trazabilidad_unidades'
            )->insertOrIgnore([
                'fecha' =>
                    $fechaCorrelativo,

                'ultimo_correlativo' =>
                    0,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);


            $correlativo =
                DB::table(
                    'correlativos_trazabilidad_unidades'
                )
                    ->where(
                        'fecha',
                        $fechaCorrelativo
                    )
                    ->lockForUpdate()
                    ->first();


            if (!$correlativo) {

                throw new ReglaNegocioException(
                    'No fue posible obtener el correlativo de trazabilidad.'
                );
            }


            $primerCorrelativo =
                ((int) $correlativo->ultimo_correlativo)
                + 1;


            $ultimoCorrelativo =
                $primerCorrelativo
                + $cantidad
                - 1;


            if ($ultimoCorrelativo > 9999) {

                throw new ReglaNegocioException(
                    'Se alcanzó el límite diario de códigos de trazabilidad.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Reservar rango de correlativos
            |--------------------------------------------------------------------------
            */

            DB::table(
                'correlativos_trazabilidad_unidades'
            )
                ->where(
                    'fecha',
                    $fechaCorrelativo
                )
                ->update([
                    'ultimo_correlativo' =>
                        $ultimoCorrelativo,

                    'updated_at' =>
                        now(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | Crear unidades físicas
            |--------------------------------------------------------------------------
            */

            $unidades =
                collect();

            $servicioRequerido = trim(
                (string) ($datosFisicos['servicio_requerido'] ?? '')
            );


            for (
                $indice = 0;
                $indice < $cantidad;
                $indice++
            ) {

                $numeroCorrelativo =
                    $primerCorrelativo
                    + $indice;


                $codigoTrazabilidad =
                    sprintf(
                        'OS-%s-%04d',
                        $fecha->format('ymd'),
                        $numeroCorrelativo
                    );


                $unidad =
                    UnidadAdquirida::create([

                        /*
                         * Procedencia
                         */

                        'detalle_lote_id' =>
                            $detalle->id,

                        'adquisicion_directa_id' =>
                            null,

                        'producto_id' =>
                            $detalle->producto_id,


                        /*
                         * Datos económicos de compra
                         */

                        'precio_compra' =>
                            $datosCompra[
                                'precio_compra'
                            ]
                            ?? null,

                        'moneda_id' =>
                            $datosCompra[
                                'moneda_id'
                            ]
                            ?? null,

                        'tipo_cambio_compra_id' =>
                            $datosCompra[
                                'tipo_cambio_compra_id'
                            ]
                            ?? null,

                        'precio_compra_bob' =>
                            $datosCompra[
                                'precio_compra_bob'
                            ]
                            ?? null,

                        'fecha_compra' =>
                            $datosCompra[
                                'fecha_compra'
                            ]
                            ?? null,

                        'referencia_compra' =>
                            $datosCompra[
                                'referencia_compra'
                            ]
                            ?? null,

                        'proveedor_compra' =>
                            $datosCompra[
                                'proveedor_compra'
                            ]
                            ?? null,


                        /*
                         * Ubicación física
                         */

                        'almacen_actual_id' =>
                            $almacenCochabamba->id,


                        /*
                         * Estado inicial
                         */

                        'estado' =>
                            UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,


                        /*
                         * Trazabilidad
                         */

                        'codigo_trazabilidad' =>
                            $codigoTrazabilidad,


                        /*
                         * Fecha
                         */

                        'fecha_llegada' =>
                            $fecha,


                        /*
                         * Ficha física constatada en recepción
                         */

                        'procesador' =>
                            $datosFisicos['procesador'] ?? null,

                        'generacion_procesador' =>
                            $datosFisicos['generacion_procesador'] ?? null,

                        'ram_gb' =>
                            $datosFisicos['ram_gb'] ?? null,

                        'almacenamiento_gb' =>
                            $datosFisicos['almacenamiento_gb'] ?? null,

                        'tipo_almacenamiento' =>
                            $datosFisicos['tipo_almacenamiento'] ?? null,

                        'tarjeta_grafica' =>
                            $datosFisicos['tarjeta_grafica'] ?? null,

                        'serial_fabricante' =>
                            $datosFisicos['serial_fabricante'] ?? null,

                        'grado_recibido' =>
                            $datosFisicos['grado_recibido'] ?? null,

                        'tiene_cargador' =>
                            $datosFisicos['tiene_cargador'] ?? null,

                        'sistema_operativo' =>
                            $datosFisicos['sistema_operativo'] ?? null,

                        'resolucion' =>
                            $datosFisicos['resolucion'] ?? null,

                        'pantalla_pulgadas' =>
                            $datosFisicos['pantalla_pulgadas'] ?? null,


                        /*
                         * Usuario
                         */

                        'registrado_por_id' =>
                            $usuario->id,


                        /*
                         * Preparación
                         */

                        'requiere_servicio' =>
                            $servicioRequerido !== '',

                        'servicio_requerido' =>
                            $servicioRequerido !== ''
                                ? $servicioRequerido
                                : null,


                        /*
                         * Observación de llegada
                         */

                        'observacion_revision' =>
                            $observacion,
                    ]);


                $unidades->push(
                    $unidad
                );
            }


            /*
            |--------------------------------------------------------------------------
            | MUY IMPORTANTE
            |--------------------------------------------------------------------------
            |
            | Aquí NO se incrementa:
            |
            | $detalle->cantidad_recibida
            |
            | La llegada a Cochabamba únicamente genera unidades adquiridas.
            |
            | El flujo es:
            |
            | compra/lote
            |      ↓
            | llegada Cochabamba
            |      ↓
            | UnidadAdquirida
            |      ↓
            | revisión / preparación
            |      ↓
            | envío a Oruro
            |      ↓
            | recepción Oruro
            |      ↓
            | incorporación a inventario
            |
            |--------------------------------------------------------------------------
            */


            /*
            |--------------------------------------------------------------------------
            | Estado de recepción física del lote
            |--------------------------------------------------------------------------
            */

            $this->actualizarEstadoRecepcionLote(
                $detalle->lote_id
            );


            /*
            |--------------------------------------------------------------------------
            | Evento logístico del lote
            |--------------------------------------------------------------------------
            */

            $tipoEvento =
                TipoEventoLogistico::query()
                    ->where(
                        'codigo',
                        'RECEPCION_COCHABAMBA'
                    )
                    ->where(
                        'activo',
                        true
                    )
                    ->first();


            if ($tipoEvento) {

                EventoLogisticoLote::create([

                    'lote_id' =>
                        $detalle->lote_id,


                    'tipo_evento_logistico_id' =>
                        $tipoEvento->id,


                    'usuario_id' =>
                        $usuario->id,


                    'fecha_evento' =>
                        $fecha,


                    'ubicacion' =>
                        'Depósito Cochabamba',


                    'descripcion' =>
                        $this->descripcionLlegada(
                            $detalle,
                            $cantidad,
                            $registradas
                                + $cantidad,
                            $observacion
                        ),
                ]);
            }


            return $unidades;
        },
        3
    );
}

    /**
     * Recalcula el estado visible de recepción física de un lote.
     *
     * No modifica detalles_lotes.cantidad_recibida: ese contador continúa
     * reservado para la etapa posterior de Oruro.
     */
    public function sincronizarEstadoRecepcionLote(
        int $loteId
    ): void {
        DB::transaction(
            fn () => $this->actualizarEstadoRecepcionLote($loteId),
            3
        );
    }

    private function actualizarEstadoRecepcionLote(
        int $loteId
    ): void {
        $lote = Lote::query()
            ->lockForUpdate()
            ->find($loteId);

        if (!$lote) {
            return;
        }

        if (
            in_array(
                $lote->estado,
                ['CERRADO', 'CANCELADO'],
                true
            )
        ) {
            return;
        }

        $esperadas = (int) DetalleLote::query()
            ->where('lote_id', $lote->id)
            ->sum('cantidad_esperada');

        $recibidasFisicamente = UnidadAdquirida::query()
            ->whereHas(
                'detalleLote',
                fn ($query) => $query->where(
                    'lote_id',
                    $lote->id
                )
            )
            ->where(
                'estado',
                '!=',
                UnidadAdquirida::ESTADO_ANULADA
            )
            ->count();

        $estado = match (true) {
            $recibidasFisicamente === 0 => 'ABIERTO',
            $esperadas > 0 && $recibidasFisicamente >= $esperadas => 'RECIBIDO',
            default => 'RECEPCION_PARCIAL',
        };

        if ($lote->estado !== $estado) {
            $lote->update([
                'estado' => $estado,
            ]);
        }
    }

    private function descripcionLlegada(
        DetalleLote $detalle,
        int $cantidadLlegada,
        int $totalRegistradas,
        ?string $observacion
    ): string {
        $descripcion =
            "{$cantidadLlegada} unidad(es) de "
            . "{$detalle->producto->nombre} "
            . "registrada(s) en Cochabamba. "
            . "Total físico registrado para la línea: "
            . "{$totalRegistradas}/"
            . "{$detalle->cantidad_esperada}.";

        if (
            $observacion !== null
            && trim($observacion) !== ''
        ) {
            $descripcion .=
                ' Observación: '
                . trim($observacion);
        }

        return $descripcion;
    }

    public function registrarRevisionPreliminar(
        int $usuarioId,
        int $unidadId,
        array $datos,
        bool $finalizar = true
    ): UnidadAdquirida {
        return DB::transaction(
            function () use (
                $usuarioId,
                $unidadId,
                $datos,
                $finalizar
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $unidad =
                    UnidadAdquirida::query()
                        ->lockForUpdate()
                        ->find($unidadId);

                if (!$unidad) {
                    throw new ReglaNegocioException(
                        'La unidad adquirida no existe.'
                    );
                }

                /*
                 * La revisión y preparación pertenecen exclusivamente
                 * a la etapa previa al despacho desde Cochabamba.
                 */
                if (
                    in_array(
                        $unidad->estado,
                        [
                            UnidadAdquirida::ESTADO_ENVIADA,
                            UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
                            UnidadAdquirida::ESTADO_INCORPORADA,
                            UnidadAdquirida::ESTADO_ANULADA,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'La unidad ya salió de la etapa de revisión y preparación en Cochabamba.'
                    );
                }

                if (
                    $unidad->estado ===
                    UnidadAdquirida::ESTADO_LISTA_ENVIO
                ) {
                    throw new ReglaNegocioException(
                        'La unidad ya está lista para envío. Debe reabrir la preparación antes de modificar el checklist.'
                    );
                }

                $almacenCochabamba =
                    Almacen::query()
                        ->where('codigo', 'COCHABAMBA')
                        ->where('activo', true)
                        ->first();

                if (!$almacenCochabamba) {
                    throw new ReglaNegocioException(
                        'No se encuentra disponible el depósito de Cochabamba.'
                    );
                }

                if (
                    $unidad->almacen_actual_id
                    !== $almacenCochabamba->id
                ) {
                    throw new ReglaNegocioException(
                        'La revisión técnica solo puede realizarse mientras la unidad se encuentra en Cochabamba.'
                    );
                }

                $reglasChecklist = [];

                foreach (
                    array_keys(
                        RevisionTecnicaUnidadAdquirida::CHECKLIST
                    ) as $campo
                ) {
                    $reglasChecklist[
                        "checklist_tecnico.{$campo}"
                    ] = [
                        'nullable',
                        'in:'
                            . RevisionTecnicaUnidadAdquirida::CHECK_OK
                            . ','
                            . RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                            . ','
                            . RevisionTecnicaUnidadAdquirida::CHECK_NO_APLICA,
                    ];
                }

                $validator = Validator::make(
                    $datos,
                    [
                        'serial_fabricante' => ['nullable', 'string', 'max:150'],
                        'procesador' => ['nullable', 'string', 'max:150'],
                        'generacion_procesador' => ['nullable', 'string', 'max:80'],
                        'ram_gb' => ['nullable', 'integer', 'min:0', 'max:65535'],
                        'almacenamiento_gb' => ['nullable', 'integer', 'min:0'],
                        'tipo_almacenamiento' => ['nullable', 'string', 'max:50'],
                        'tarjeta_grafica' => ['nullable', 'string', 'max:150'],
                        'pantalla_pulgadas' => ['nullable', 'numeric', 'min:0', 'max:999.9'],
                        'resolucion' => ['nullable', 'string', 'max:50'],
                        'sistema_operativo' => ['nullable', 'string', 'max:100'],
                        'grado_final' => ['nullable', 'in:A,B,C'],
                        'bateria_porcentaje' => ['nullable', 'integer', 'min:0', 'max:100'],
                        'enciende' => ['nullable', 'boolean'],
                        'tiene_sistema_operativo' => ['nullable', 'boolean'],
                        'tiene_cargador' => ['nullable', 'boolean'],
                        'requiere_servicio' => ['nullable', 'boolean'],
                        'servicio_requerido' => ['nullable', 'string', 'max:2000'],
                        'observacion_revision' => ['nullable', 'string', 'max:2000'],
                        'checklist_tecnico' => ['nullable', 'array'],
                        ...$reglasChecklist,
                    ]
                );

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $validados = $validator->validated();

                $resolverBooleano =
                    static function (
                        string $campo,
                        array $validados,
                        mixed $actual
                    ): mixed {
                        return array_key_exists($campo, $validados)
                            ? $validados[$campo]
                            : $actual;
                    };

                $enciende = $resolverBooleano(
                    'enciende',
                    $validados,
                    $unidad->enciende
                );

                $tieneSistema = $resolverBooleano(
                    'tiene_sistema_operativo',
                    $validados,
                    $unidad->tiene_sistema_operativo
                );

                $tieneCargador = $resolverBooleano(
                    'tiene_cargador',
                    $validados,
                    $unidad->tiene_cargador
                );

                $requiereServicioManual = $resolverBooleano(
                    'requiere_servicio',
                    $validados,
                    $unidad->requiere_servicio
                );

                $gradoFinal = array_key_exists('grado_final', $validados)
                    ? $validados['grado_final']
                    : $unidad->grado_final;

                $bateriaPorcentaje = array_key_exists('bateria_porcentaje', $validados)
                    ? $validados['bateria_porcentaje']
                    : $unidad->bateria_porcentaje;

                /*
                |--------------------------------------------------------------------------
                | Checklist técnico acumulado
                |--------------------------------------------------------------------------
                | El registro actual de la unidad funciona como borrador de trabajo.
                | Solo al finalizar se crea un snapshot histórico inmutable.
                */
                $checklist = [];

                foreach (
                    RevisionTecnicaUnidadAdquirida::CHECKLIST
                    as $campo => $etiqueta
                ) {
                    $checklist[$campo] =
                        $unidad->checklist_tecnico[$campo]
                        ?? null;
                }

                foreach (
                    $validados['checklist_tecnico'] ?? []
                    as $campo => $valor
                ) {
                    if (
                        array_key_exists(
                            $campo,
                            RevisionTecnicaUnidadAdquirida::CHECKLIST
                        )
                    ) {
                        $checklist[$campo] = $valor;
                    }
                }

                $fallasChecklist = [];

                foreach ($checklist as $campo => $valor) {
                    if (
                        $valor ===
                        RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                    ) {
                        $fallasChecklist[] = $campo;
                    }
                }

                $problemasBasicos = [];

                if ($enciende === false) {
                    $problemasBasicos[] = 'Encendido';
                }

                if ($tieneSistema === false) {
                    $problemasBasicos[] = 'Sistema operativo';
                }

                if ($tieneCargador === false) {
                    $problemasBasicos[] = 'Cargador';
                }

                $requiereServicio =
                    $requiereServicioManual === true
                    || $problemasBasicos !== []
                    || $fallasChecklist !== [];

                $servicioRequerido =
                    $validados['servicio_requerido']
                    ?? $unidad->servicio_requerido;

                if (
                    $requiereServicio
                    && (
                        $servicioRequerido === null
                        || trim($servicioRequerido) === ''
                    )
                ) {
                    $motivos = $problemasBasicos;

                    foreach ($fallasChecklist as $campo) {
                        $motivos[] =
                            RevisionTecnicaUnidadAdquirida::CHECKLIST[$campo];
                    }

                    if ($motivos !== []) {
                        $servicioRequerido =
                            'Revisar/corregir: '
                            . implode(', ', $motivos)
                            . '.';
                    } elseif ($finalizar) {
                        throw ValidationException::withMessages([
                            'servicio_requerido' =>
                                'Debe indicar qué servicio o preparación necesita la unidad.',
                        ]);
                    }
                }

                if (!$requiereServicio) {
                    $servicioRequerido = null;
                }

                $datosUnidad = [
                    'serial_fabricante' => $validados['serial_fabricante'] ?? $unidad->serial_fabricante,
                    'procesador' => $validados['procesador'] ?? $unidad->procesador,
                    'generacion_procesador' => $validados['generacion_procesador'] ?? $unidad->generacion_procesador,
                    'ram_gb' => $validados['ram_gb'] ?? $unidad->ram_gb,
                    'almacenamiento_gb' => $validados['almacenamiento_gb'] ?? $unidad->almacenamiento_gb,
                    'tipo_almacenamiento' => $validados['tipo_almacenamiento'] ?? $unidad->tipo_almacenamiento,
                    'tarjeta_grafica' => $validados['tarjeta_grafica'] ?? $unidad->tarjeta_grafica,
                    'pantalla_pulgadas' => $validados['pantalla_pulgadas'] ?? $unidad->pantalla_pulgadas,
                    'resolucion' => $validados['resolucion'] ?? $unidad->resolucion,
                    'sistema_operativo' => $validados['sistema_operativo'] ?? $unidad->sistema_operativo,
                    'grado_final' => $gradoFinal,
                    'bateria_porcentaje' => $bateriaPorcentaje,
                    'checklist_tecnico' => $checklist,
                    'enciende' => $enciende,
                    'tiene_sistema_operativo' => $tieneSistema,
                    'tiene_cargador' => $tieneCargador,
                    'requiere_servicio' => $requiereServicio,
                    'servicio_requerido' => $servicioRequerido,
                    'observacion_revision' => $validados['observacion_revision'] ?? $unidad->observacion_revision,
                ];

                /*
                |--------------------------------------------------------------------------
                | Guardar borrador
                |--------------------------------------------------------------------------
                | No genera historial ni declara un resultado definitivo. Si la unidad ya
                | estaba en preparación se mantiene en ese estado; si recién comienza la
                | revisión pasa a EN_REVISION.
                */
                if (!$finalizar) {
                    $estadoBorrador =
                        $unidad->estado === UnidadAdquirida::ESTADO_EN_PREPARACION
                            ? UnidadAdquirida::ESTADO_EN_PREPARACION
                            : UnidadAdquirida::ESTADO_EN_REVISION;

                    $unidad->fill([
                        ...$datosUnidad,
                        'estado' => $estadoBorrador,
                    ]);

                    $unidad->save();

                    return $unidad->fresh([
                        'producto.marca',
                        'almacenActual',
                        'detalleLote.lote',
                        'revisadoPor',
                        'revisionesTecnicas.usuario',
                    ]);
                }

                $basicosCompletos =
                    $enciende !== null
                    && $tieneSistema !== null
                    && $tieneCargador !== null;

                $checklistCompleto = true;

                foreach ($checklist as $valor) {
                    if ($valor === null) {
                        $checklistCompleto = false;
                        break;
                    }
                }

                $revisionCompleta =
                    $basicosCompletos
                    && $checklistCompleto
                    && $gradoFinal !== null;

                $tieneProblemaConocido =
                    $requiereServicio
                    || $problemasBasicos !== []
                    || $fallasChecklist !== [];

                if (
                    $revisionCompleta
                    && !$tieneProblemaConocido
                ) {
                    $estado = UnidadAdquirida::ESTADO_LISTA_ENVIO;
                    $resultadoRevision = RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA;
                    $fechaListaEnvio = now();
                } elseif ($tieneProblemaConocido) {
                    $estado = UnidadAdquirida::ESTADO_EN_PREPARACION;
                    $resultadoRevision = RevisionTecnicaUnidadAdquirida::RESULTADO_REQUIERE_PREPARACION;
                    $fechaListaEnvio = null;
                } else {
                    $estado = UnidadAdquirida::ESTADO_EN_REVISION;
                    $resultadoRevision = RevisionTecnicaUnidadAdquirida::RESULTADO_INCOMPLETA;
                    $fechaListaEnvio = null;
                }

                $fechaRevision = now();

                $unidad->fill([
                    ...$datosUnidad,
                    'resultado_revision' => $resultadoRevision,
                    'estado' => $estado,
                    'fecha_revision' => $fechaRevision,
                    'fecha_lista_envio' => $fechaListaEnvio,
                    'revisado_por_id' => $usuario->id,
                ]);

                $unidad->save();

                RevisionTecnicaUnidadAdquirida::create([
                    'unidad_adquirida_id' => $unidad->id,
                    'usuario_id' => $usuario->id,
                    'fecha_revision' => $fechaRevision,
                    'grado_final' => $gradoFinal,
                    'bateria_porcentaje' => $bateriaPorcentaje,
                    'enciende' => $enciende,
                    'tiene_sistema_operativo' => $tieneSistema,
                    'tiene_cargador' => $tieneCargador,
                    'requiere_servicio' => $requiereServicio,
                    'servicio_requerido' => $servicioRequerido,
                    'checklist_tecnico' => $checklist,
                    'resultado' => $resultadoRevision,
                    'observacion' => $validados['observacion_revision'] ?? $unidad->observacion_revision,
                ]);

                return $unidad->fresh([
                    'producto.marca',
                    'almacenActual',
                    'detalleLote.lote',
                    'revisadoPor',
                    'revisionesTecnicas.usuario',
                ]);
            },
            3
        );
    }

    public function reabrirPreparacion(
        int $usuarioId,
        int $unidadId,
        string $motivo
    ): UnidadAdquirida {
        return DB::transaction(
            function () use ($usuarioId, $unidadId, $motivo) {
                $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

                $motivo = trim($motivo);

                if ($motivo === '') {
                    throw ValidationException::withMessages([
                        'motivo' => 'Debe indicar el motivo de reapertura de la preparación.',
                    ]);
                }

                if (mb_strlen($motivo) > 2000) {
                    throw ValidationException::withMessages([
                        'motivo' => 'El motivo no puede superar los 2000 caracteres.',
                    ]);
                }

                $unidad = UnidadAdquirida::query()
                    ->with('envioImportacionUnidad.envioImportacion')
                    ->lockForUpdate()
                    ->find($unidadId);

                if (!$unidad) {
                    throw new ReglaNegocioException('La unidad adquirida no existe.');
                }

                if ($unidad->estado !== UnidadAdquirida::ESTADO_LISTA_ENVIO) {
                    throw new ReglaNegocioException(
                        'Solo puede reabrirse una unidad que se encuentre lista para envío.'
                    );
                }

                $almacenCochabamba = Almacen::query()
                    ->where('codigo', 'COCHABAMBA')
                    ->where('activo', true)
                    ->first();

                if (
                    !$almacenCochabamba
                    || $unidad->almacen_actual_id !== $almacenCochabamba->id
                ) {
                    throw new ReglaNegocioException(
                        'La preparación solo puede reabrirse mientras la unidad permanece en Cochabamba.'
                    );
                }

                $detalleEnvio = $unidad->envioImportacionUnidad;
                $envio = $detalleEnvio?->envioImportacion;

                if ($envio) {
                    if ($envio->estado === EnvioImportacion::ESTADO_BORRADOR) {
                        /*
                         * Si apenas estaba agregada a un envío en borrador, se retira
                         * automáticamente para evitar que una unidad no lista siga viajando.
                         */
                        $detalleEnvio->delete();
                    } else {
                        throw new ReglaNegocioException(
                            'La unidad ya pertenece a un envío que fue marcado como preparado o despachado. Debe resolver ese envío antes de reabrir la preparación.'
                        );
                    }
                }

                $anterior = [
                    'estado' => $unidad->estado,
                    'resultado_revision' => $unidad->resultado_revision,
                    'fecha_lista_envio' => $unidad->fecha_lista_envio?->toDateTimeString(),
                ];

                $unidad->update([
                    'estado' => UnidadAdquirida::ESTADO_EN_PREPARACION,
                    'resultado_revision' => RevisionTecnicaUnidadAdquirida::RESULTADO_REQUIERE_PREPARACION,
                    'requiere_servicio' => true,
                    'servicio_requerido' => $motivo,
                    'fecha_lista_envio' => null,
                ]);

                $this->auditoriaService->registrar(
                    $usuario->id,
                    'REABRIR_PREPARACION',
                    'unidad_adquirida',
                    $unidad->id,
                    $anterior,
                    [
                        'estado' => UnidadAdquirida::ESTADO_EN_PREPARACION,
                        'motivo' => $motivo,
                    ]
                );

                return $unidad->fresh([
                    'producto.marca',
                    'almacenActual',
                    'revisionesTecnicas.usuario',
                    'envioImportacionUnidad.envioImportacion',
                ]);
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
                ->find($usuarioId);

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